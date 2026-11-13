<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\Holding;
use App\Models\Portfolio\PaperAccount;
use App\Models\Portfolio\PaperTrade;
use App\Models\Portfolio\WatchlistItem;
use App\Models\Setting;
use App\Services\Portfolio\PriceFetcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StockAiController extends Controller
{
    private const THAI_STOCK_TICKERS = [
        'PTT', 'PTTEP', 'PTTGC', 'TOP', 'OR', 'ADVANC', 'INTUCH', 'TRUE', 'DELTA', 'HANA',
        'KCE', 'SCB', 'KBANK', 'BBL', 'KTB', 'TTB', 'TISCO', 'KKP', 'CPALL', 'CPAXT',
        'CRC', 'HMPRO', 'GLOBAL', 'BDMS', 'BH', 'BCH', 'CHG', 'AOT', 'BEM', 'BTS',
        'MINT', 'CENTEL', 'AWC', 'CPN', 'LH', 'AP', 'SPALI', 'SIRI', 'ORI', 'SC',
        'GULF', 'GPSC', 'BGRIM', 'EA', 'RATCH', 'EGCO', 'BANPU', 'SCC', 'SCGP', 'IVL',
        'TU', 'CPF', 'CBG', 'OSP', 'ICHI', 'SAPPE', 'MTC', 'SAWAD', 'TIDLOR', 'JMT',
        'JMART', 'COM7', 'SYNEX', 'SIS', 'WHA', 'AMATA', 'PLANB', 'VGI',
    ];

    public function index(PriceFetcher $prices)
    {
        $userId = $this->resolvePortfolioUserId();
        $this->ensurePaperTradingTablesExist();

        // 1. Real Holdings
        $holdings = collect();
        if (Schema::hasTable('portfolio_holdings')) {
            $holdings = Holding::query()
                ->forUser($userId)
                ->whereIn('kind', ['stock', 'fund', 'crypto'])
                ->orderByDesc('current_value_thb')
                ->get();
        }

        $totalInvestedThb = max(0.0, (float) $holdings->sum('current_value_thb'));
        $totalCostBasis   = max(0.0, (float) $holdings->sum('cost_basis'));
        $totalRealPnlThb  = $totalInvestedThb - $totalCostBasis;
        $totalRealPnlPct  = $totalCostBasis > 0 ? round(($totalRealPnlThb / $totalCostBasis) * 100, 2) : 0.0;

        $portfolioTop3 = [];
        if ($holdings->count() >= 1 && $totalInvestedThb > 0) {
            foreach ($holdings->take(3) as $h) {
                $pct = round(((float) $h->current_value_thb / $totalInvestedThb) * 100, 1);
                $portfolioTop3[] = [
                    'symbol' => $h->symbol ?: $h->label,
                    'pct'    => $pct,
                ];
            }
        }

        $p6A   = $portfolioTop3[0]['symbol'] ?? 'NVDA';
        $p6X   = $portfolioTop3[0]['pct'] ?? 45;
        $p6B   = $portfolioTop3[1]['symbol'] ?? 'ADVANC.BK';
        $p6Y   = $portfolioTop3[1]['pct'] ?? 35;
        $p6C   = $portfolioTop3[2]['symbol'] ?? 'PTT.BK';
        $p6Z   = $portfolioTop3[2]['pct'] ?? 20;

        // 2. Watchlist
        $watchlist = collect();
        if (Schema::hasTable('portfolio_watchlist_items')) {
            $watchlist = WatchlistItem::query()
                ->forUser($userId)
                ->orderBy('kind')
                ->orderBy('symbol')
                ->get();
        }

        // 3. Paper Trading Account & Trades
        $paperAccount = PaperAccount::getOrCreateForUser($userId);
        $openPaperTrades = PaperTrade::forUser($userId)
            ->open()
            ->orderByDesc('opened_at')
            ->get();
        $closedPaperTrades = PaperTrade::forUser($userId)
            ->closed()
            ->orderByDesc('closed_at')
            ->limit(25)
            ->get();

        $paperHoldingsValue = (float) $openPaperTrades->sum(
            fn ($t) => $t->current_value_thb !== null ? (float) $t->current_value_thb : (float) $t->entry_total_thb
        );
        $paperCash        = (float) $paperAccount->cash_balance;
        $paperNetWorth    = $paperCash + $paperHoldingsValue;
        $paperUnrealized  = (float) $openPaperTrades->sum(fn ($t) => $t->unrealizedPnlThb());
        $paperRealized    = (float) $closedPaperTrades->sum('realized_pnl_thb');
        $paperTotalPnl    = $paperUnrealized + $paperRealized;
        $closedCount      = $closedPaperTrades->count();
        $winCount         = $closedPaperTrades->where('realized_pnl_thb', '>', 0)->count();
        $paperWinRate     = $closedCount > 0 ? round(($winCount / $closedCount) * 100, 1) : 0.0;

        // 4. 10 Prompts Catalog
        $prompts = [
            [
                'id'       => 1,
                'category' => 'Fundamental',
                'badge'    => 'ปัจจัยพื้นฐาน & เปรียบเทียบ',
                'color'    => 'indigo',
                'title'    => '1. ปัจจัยพื้นฐาน (Fundamental) และเปรียบเทียบ 3 หุ้น',
                'template' => 'วิเคราะห์งบการเงินย้อนหลัง 3 ปีของหุ้น {stockA}, {stockB}, และ {stockC} โดยเปรียบเทียบอัตราส่วน ROE, P/E และ อัตรากำไรสุทธิ เพื่อหาหุ้นที่มีพื้นฐานดีที่สุดในกลุ่ม',
                'fields'   => [
                    ['key' => 'stockA', 'label' => 'หุ้น A', 'default' => 'ADVANC.BK', 'placeholder' => 'เช่น ADVANC.BK หรือ NVDA'],
                    ['key' => 'stockB', 'label' => 'หุ้น B', 'default' => 'TRUE.BK', 'placeholder' => 'เช่น TRUE.BK หรือ AMD'],
                    ['key' => 'stockC', 'label' => 'หุ้น C', 'default' => 'INTUCH.BK', 'placeholder' => 'เช่น INTUCH.BK หรือ MSFT'],
                ],
            ],
            [
                'id'       => 2,
                'category' => 'Technical',
                'badge'    => 'แนวรับ / แนวต้าน / จุดเข้าซื้อ',
                'color'    => 'emerald',
                'title'    => '2. วิเคราะห์เทคนิค (Technical) แนวรับ/แนวต้าน & จุด Stop Loss',
                'template' => 'กำหนด แนวรับ และ แนวต้าน สำคัญของหุ้น {stock} ในกรอบเวลารายวัน (Daily) และวิเคราะห์ว่าควรใช้กลยุทธ์เข้าซื้อ (Entry) เมื่อราคาเข้าใกล้แนวรับใด พร้อมจุดตัดขาดทุน (Stop Loss) ที่เหมาะสม',
                'fields'   => [
                    ['key' => 'stock', 'label' => 'ชื่อหุ้น (Ticker)', 'default' => 'NVDA', 'placeholder' => 'เช่น NVDA, AAPL, PTT.BK'],
                ],
            ],
            [
                'id'       => 3,
                'category' => 'Momentum',
                'badge'    => 'กราฟ MA & RSI Momentum',
                'color'    => 'sky',
                'title'    => '3. วิเคราะห์เทคนิค (Technical) กราฟ Moving Average และ RSI',
                'template' => 'วิเคราะห์กราฟหุ้น {stock} โดยใช้ Moving Average (MA) และ RSI เพื่อระบุแนวโน้ม (Trend) และโมเมนตัม (Momentum) ปัจจุบัน สรุปว่าหุ้นอยู่ในภาวะ Overbought หรือ Oversold',
                'fields'   => [
                    ['key' => 'stock', 'label' => 'ชื่อหุ้น (Ticker)', 'default' => 'DELTA.BK', 'placeholder' => 'เช่น DELTA.BK, TSLA, BTC-USD'],
                ],
            ],
            [
                'id'       => 4,
                'category' => 'Hybrid',
                'badge'    => 'ปันผล + รูปแบบกราฟ',
                'color'    => 'amber',
                'title'    => '4. การเปรียบเทียบหุ้น (Fundamental + Technical เน้นปันผล)',
                'template' => 'เปรียบเทียบหุ้น {stockA} กับ {stockB} ในประเด็น อัตราเงินปันผลตอบแทน (Dividend Yield) และวิเคราะห์รูปแบบกราฟราคา (Chart Pattern) ล่าสุด เพื่อตัดสินใจว่าหุ้นใดน่าสนใจในการลงทุนแบบเน้นปันผล',
                'fields'   => [
                    ['key' => 'stockA', 'label' => 'หุ้น A', 'default' => 'SCB.BK', 'placeholder' => 'เช่น SCB.BK'],
                    ['key' => 'stockB', 'label' => 'หุ้น B', 'default' => 'TISCO.BK', 'placeholder' => 'เช่น TISCO.BK'],
                ],
            ],
            [
                'id'       => 5,
                'category' => 'Macro & Sector',
                'badge'    => 'โอกาสลงทุน & Breakout',
                'color'    => 'violet',
                'title'    => '5. วิเคราะห์โอกาสลงทุนตามสภาวะเศรษฐกิจ (Investment Opportunity)',
                'template' => 'จากสถานการณ์ที่ {macroSituation} หุ้นกลุ่ม {sector} ตัวใด ({stockList}) ได้รับประโยชน์มากที่สุด และมีสัญญาณเทคนิคที่ยืนยันการขึ้น (Breakout) หรือไม่',
                'fields'   => [
                    ['key' => 'macroSituation', 'label' => 'สภาวะเศรษฐกิจ / อุตสาหกรรม', 'default' => 'ดอกเบี้ยขาลงและการลงทุน Data Center & AI เติบโตสูง', 'placeholder' => 'เช่น ดอกเบี้ยขาลง / ท่องเที่ยวฟื้นตัว'],
                    ['key' => 'sector', 'label' => 'กลุ่มอุตสาหกรรม', 'default' => 'โรงไฟฟ้าและนิคมอุตสาหกรรม', 'placeholder' => 'เช่น โรงไฟฟ้า / การแพทย์ / เซมิคอนดักเตอร์'],
                    ['key' => 'stockList', 'label' => 'ระบุ 2-3 ชื่อหุ้น', 'default' => 'GULF.BK, WHA.BK, GPSC.BK', 'placeholder' => 'เช่น GULF.BK, WHA.BK, GPSC.BK'],
                ],
            ],
            [
                'id'       => 6,
                'category' => 'Portfolio',
                'badge'    => 'กระจายความเสี่ยงพอร์ต',
                'color'    => 'rose',
                'title'    => '6. การวิเคราะห์พอร์ตลงทุน (Portfolio Concentration & Diversification)',
                'template' => 'พอร์ตลงทุนปัจจุบันของฉันประกอบด้วย {stockA} {pctA}%, {stockB} {pctB}%, และ {stockC} {pctC}% วิเคราะห์ว่าพอร์ตนี้มีความเสี่ยงในด้านใดบ้าง (Sector Concentration) และแนะนำหุ้นในกลุ่มอื่นที่ควรเพิ่มเพื่อกระจายความเสี่ยง',
                'fields'   => [
                    ['key' => 'stockA', 'label' => 'หุ้น A', 'default' => (string) $p6A, 'placeholder' => 'เช่น NVDA'],
                    ['key' => 'pctA', 'label' => 'สัดส่วน X (%)', 'default' => (string) $p6X, 'placeholder' => '45'],
                    ['key' => 'stockB', 'label' => 'หุ้น B', 'default' => (string) $p6B, 'placeholder' => 'เช่น ADVANC.BK'],
                    ['key' => 'pctB', 'label' => 'สัดส่วน Y (%)', 'default' => (string) $p6Y, 'placeholder' => '35'],
                    ['key' => 'stockC', 'label' => 'หุ้น C', 'default' => (string) $p6C, 'placeholder' => 'เช่น PTT.BK'],
                    ['key' => 'pctC', 'label' => 'สัดส่วน Z (%)', 'default' => (string) $p6Z, 'placeholder' => '20'],
                ],
            ],
            [
                'id'       => 7,
                'category' => 'Rebalance',
                'badge'    => 'ปรับพอร์ตลดความเสี่ยง',
                'color'    => 'emerald',
                'title'    => '7. การปรับพอร์ต (Portfolio Adjustment & Risk Reduction)',
                'template' => 'ฉันต้องการ ลดความเสี่ยง ของพอร์ตลง {reducePct}% โดยการขายหุ้นที่ผันผวนสูง แนะนำว่าควรขายหุ้นใดออกไป ({stockList}) และนำเงินไปลงทุนในสินทรัพย์ที่มีความเสี่ยงต่ำกว่า (เช่น {safeAsset}) อย่างไร',
                'fields'   => [
                    ['key' => 'reducePct', 'label' => 'ลดความเสี่ยงลง (%)', 'default' => '20', 'placeholder' => '20'],
                    ['key' => 'stockList', 'label' => 'หุ้นในพอร์ต (2-3 ชื่อ)', 'default' => "{$p6A}, {$p6B}, {$p6C}", 'placeholder' => 'เช่น TSLA, NVDA, DELTA.BK'],
                    ['key' => 'safeAsset', 'label' => 'สินทรัพย์ความเสี่ยงต่ำ', 'default' => 'กองทุนตราสารหนี้ระยะสั้น และทองคำ', 'placeholder' => 'เช่น กองทุนตราสารหนี้ / พันธบัตร'],
                ],
            ],
            [
                'id'       => 8,
                'category' => 'Sentiment',
                'badge'    => 'ข่าวล่าสุด & Sentiment',
                'color'    => 'sky',
                'title'    => '8. การหาข่าวและวิเคราะห์ Market Sentiment (7 วันล่าสุด)',
                'template' => 'สรุป ข่าวล่าสุด และ มุมมองนักวิเคราะห์ ที่มีผลกระทบต่อราคาหุ้น {stock} ในช่วง 7 วันที่ผ่านมา พร้อมวิเคราะห์ Sentiment ตลาดว่าเป็นบวกหรือลบ',
                'fields'   => [
                    ['key' => 'stock', 'label' => 'ชื่อหุ้น (Ticker)', 'default' => 'AAPL', 'placeholder' => 'เช่น AAPL, NVDA, CPALL.BK'],
                ],
            ],
            [
                'id'       => 9,
                'category' => 'Valuation',
                'badge'    => 'ประเมินมูลค่า P/E & DCF',
                'color'    => 'amber',
                'title'    => '9. การประเมินมูลค่าที่เหมาะสม (Fair Value: P/E & DCF Model)',
                'template' => 'ประเมิน มูลค่าที่เหมาะสม (Fair Value) ของหุ้น {stock} โดยใช้วิธี P/E และ Discounted Cash Flow (DCF) Model เมื่อเทียบกับราคาตลาดปัจจุบัน',
                'fields'   => [
                    ['key' => 'stock', 'label' => 'ชื่อหุ้น (Ticker)', 'default' => 'BDMS.BK', 'placeholder' => 'เช่น BDMS.BK, MSFT, PTT.BK'],
                ],
            ],
            [
                'id'       => 10,
                'category' => 'Advanced Tech',
                'badge'    => 'Fibonacci & Volume',
                'color'    => 'violet',
                'title'    => '10. วิเคราะห์เทคนิคขั้นสูง (Fibonacci Retracement & Volume Analysis)',
                'template' => 'วิเคราะห์หุ้น {stock} โดยใช้ Fibonacci Retracement ในการกำหนดระดับการย่อตัว (Pullback) ที่สำคัญ และใช้ Volume Analysis เพื่อยืนยันความแข็งแกร่งของแนวโน้มปัจจุบัน',
                'fields'   => [
                    ['key' => 'stock', 'label' => 'ชื่อหุ้น (Ticker)', 'default' => 'TSLA', 'placeholder' => 'เช่น TSLA, NVDA, KBANK.BK'],
                ],
            ],
        ];

        // 5. Debt to Investment Cashflow Milestones
        $debtMilestones = [
            [
                'period'       => 'พ.ย. 2569 (เดือนนี้)',
                'unlocked_thb' => 2021.08,
                'status'       => 'สะสมเงินสำรองฉุกเฉิน e-Savings + ซ้อมมือในพอร์ตจำลอง',
                'badge'        => 'ตั้งฐานการเงิน',
                'color'        => 'amber',
            ],
            [
                'period'       => 'ธ.ค. 2569 (เดือนหน้า)',
                'unlocked_thb' => 4109.81,
                'status'       => 'ปลดหนี้คืนแม่ (-฿1,000) + ยอดใบแจ้งยอดลดลง เริ่มแบ่งไม้ DCA',
                'badge'        => '+฿2,088/ด.',
                'color'        => 'sky',
            ],
            [
                'period'       => 'พ.ค. 2570 (อีก 6 เดือน)',
                'unlocked_thb' => 8977.05,
                'status'       => 'ปิดจบใบแจ้งยอดล่วงหน้าครบ 6 งวด + จบ SPayLater เม็ดเงินลงทุนพุ่งสูง',
                'badge'        => '+฿6,955/ด.',
                'color'        => 'emerald',
            ],
            [
                'period'       => 'ต.ค. 2570 (อีก 11 เดือน)',
                'unlocked_thb' => 14849.07,
                'status'       => 'ปิด S24 Ultra, Tiktok, mono max เหลือเฉพาะมอไซต์ ฿2,989/ด.',
                'badge'        => '+฿12,828/ด.',
                'color'        => 'brand',
            ],
        ];

        $hasGeminiKey = (bool) $this->resolveApiKey();

        return view('portfolio.ai-analyst', compact(
            'prompts',
            'holdings',
            'totalInvestedThb',
            'totalCostBasis',
            'totalRealPnlThb',
            'totalRealPnlPct',
            'watchlist',
            'paperAccount',
            'openPaperTrades',
            'closedPaperTrades',
            'paperCash',
            'paperHoldingsValue',
            'paperNetWorth',
            'paperUnrealized',
            'paperRealized',
            'paperTotalPnl',
            'paperWinRate',
            'closedCount',
            'debtMilestones',
            'hasGeminiKey'
        ));
    }

    public function saveApiKey(Request $request)
    {
        $data = $request->validate([
            'gemini_key' => 'required|string|max:250',
        ]);

        Setting::updateOrCreate(
            ['key' => 'portfolio.gemini_key'],
            ['value' => Crypt::encryptString(trim($data['gemini_key']))]
        );

        return redirect()->route('portfolio.ai.index')
            ->with('status', 'บันทึก Gemini API Key เรียบร้อยแล้ว');
    }

    public function analyze(Request $request)
    {
        $data = $request->validate([
            'prompt_id' => 'nullable|integer|min:1|max:10',
            'prompt'    => 'required|string|max:3000',
            'tickers'   => 'nullable|string|max:300',
        ]);

        $promptText = trim($data['prompt']);
        $promptId   = (int) ($data['prompt_id'] ?? 1);

        $tickers = $this->extractTickers(($data['tickers'] ?? '') . ' ' . $promptText);

        $marketSnapshots = [];
        foreach (array_slice($tickers, 0, 4) as $symbol) {
            $snap = $this->fetchTickerTechnicals($symbol);
            if ($snap !== null) {
                $marketSnapshots[] = $snap;
            }
        }

        // Identify primary ticker for news analysis
        $primarySymbol = $tickers[0] ?? null;
        if (!$primarySymbol && !empty($data['tickers'])) {
            $cleanFirst = strtoupper(trim(explode(',', (string) $data['tickers'])[0]));
            if ($cleanFirst !== '') {
                $primarySymbol = $cleanFirst;
            }
        }
        if (!$primarySymbol && preg_match('/\b([A-Z]{2,10}(?:\.BK|-USD)?)\b/i', $promptText, $m)) {
            $candidate = strtoupper($m[1]);
            if (!in_array($candidate, ['ROE', 'PE', 'RSI', 'SMA', 'EMA', 'AI', 'SET', 'USD', 'THB'], true)) {
                $primarySymbol = $candidate;
            }
        }

        if ($primarySymbol && !str_contains($primarySymbol, '.') && !str_contains($primarySymbol, '-') && in_array($primarySymbol, self::THAI_STOCK_TICKERS, true)) {
            $primarySymbol .= '.BK';
        }

        // Fetch live financial news for the primary ticker
        $liveNews = [];
        if ($primarySymbol) {
            $liveNews = $this->fetchLiveStockNews($primarySymbol);
        }
        $sentiment = $this->evaluateHeadlineSentiment($liveNews);

        $apiKey     = $this->resolveApiKey();
        $aiMarkdown = null;
        $usedGemini = false;
        $aiModel    = 'Quant Technical & News Engine (Live Yahoo Finance & Google News)';

        if ($apiKey) {
            try {
                $geminiResult = $this->callGeminiAnalyst($apiKey, $promptText, $marketSnapshots, $liveNews);
                if ($geminiResult) {
                    $aiMarkdown = $geminiResult['text'];
                    $aiModel    = $geminiResult['model'];
                    $usedGemini = true;
                }
            } catch (\Throwable $e) {
                Log::warning('portfolio.ai_analyst.gemini_failed', ['error' => $e->getMessage()]);
            }
        }

        if (!$aiMarkdown) {
            $aiMarkdown = $this->buildQuantAnalysisReport($promptId, $promptText, $marketSnapshots, $liveNews, $sentiment);
        }

        return response()->json([
            'success'      => true,
            'prompt_id'    => $promptId,
            'prompt_used'  => $promptText,
            'used_gemini'  => $usedGemini,
            'model_label'  => $aiModel,
            'primary_sym'  => $primarySymbol,
            'market_data'  => $marketSnapshots,
            'news'         => $liveNews,
            'sentiment'    => $sentiment,
            'analysis'     => $aiMarkdown,
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    public function fetchNewsApi(Request $request)
    {
        $symbol = trim((string) $request->input('symbol', ''));
        if ($symbol === '') {
            return response()->json(['success' => false, 'message' => 'กรุณาระบุชื่อหุ้น (Ticker)'], 422);
        }

        $symbol = strtoupper($symbol);
        if (!str_contains($symbol, '.') && !str_contains($symbol, '-') && in_array($symbol, self::THAI_STOCK_TICKERS, true)) {
            $symbol .= '.BK';
        }

        $news = $this->fetchLiveStockNews($symbol);
        $sentiment = $this->evaluateHeadlineSentiment($news);

        return response()->json([
            'success'   => true,
            'symbol'    => $symbol,
            'news'      => $news,
            'sentiment' => $sentiment,
            'count'     => count($news),
        ]);
    }

    public function storePaperTrade(Request $request, PriceFetcher $prices)
    {
        $this->ensurePaperTradingTablesExist();
        $userId = $this->resolvePortfolioUserId();

        $data = $request->validate([
            'symbol'       => 'required|string|max:32',
            'quantity'     => 'required|numeric|min:0.0001',
            'entry_price'  => 'nullable|numeric|min:0.0001',
            'target_price' => 'nullable|numeric|min:0',
            'stop_loss'    => 'nullable|numeric|min:0',
            'kind'         => 'nullable|string|in:stock,crypto,fund',
            'trade_thesis' => 'nullable|string|max:1000',
        ]);

        $symbol = strtoupper(trim($data['symbol']));
        if (!str_contains($symbol, '.') && !str_contains($symbol, '-') && in_array($symbol, self::THAI_STOCK_TICKERS, true)) {
            $symbol .= '.BK';
        }

        $kind = $data['kind'] ?? (str_contains($symbol, '-USD') ? 'crypto' : 'stock');
        $currency = str_ends_with($symbol, '.BK') ? 'THB' : (str_contains($symbol, '-USD') ? 'USD' : 'USD');

        // Resolve entry price: if user didn't enter, fetch live price
        $entryPrice = (float) ($data['entry_price'] ?? 0);
        if ($entryPrice <= 0) {
            $snap = $this->fetchTickerTechnicals($symbol);
            $entryPrice = $snap ? (float) $snap['price'] : (float) ($prices->fetchUnitPrice($kind, $symbol) ?? 0);
            if ($snap) {
                $currency = $snap['currency'];
            }
        }

        if ($entryPrice <= 0) {
            return redirect()->to(route('portfolio.ai.index') . '#paper')
                ->with('error', "ไม่สามารถดึงราคาตลาดของ {$symbol} ได้ กรุณากรอกราคาเข้าซื้อด้วยตนเอง");
        }

        $qty = (float) $data['quantity'];
        $fxRate = $currency === 'THB' ? 1.0 : ($prices->fxRateToThb($currency) ?? 34.50);
        $totalThb = round($qty * $entryPrice * $fxRate, 2);

        $account = PaperAccount::getOrCreateForUser($userId);
        $account->cash_balance = max(0.0, (float) $account->cash_balance - $totalThb);
        $account->save();

        PaperTrade::create([
            'user_id'          => $userId,
            'paper_account_id' => $account->id,
            'symbol'           => $symbol,
            'label'            => $symbol,
            'kind'             => $kind,
            'currency'         => $currency,
            'side'             => 'buy',
            'status'           => PaperTrade::STATUS_OPEN,
            'quantity'         => $qty,
            'entry_price'      => $entryPrice,
            'entry_total_thb'  => $totalThb,
            'current_price'    => $entryPrice,
            'current_value_thb'=> $totalThb,
            'target_price'     => $data['target_price'] ?? null,
            'stop_loss'        => $data['stop_loss'] ?? null,
            'trade_thesis'     => $data['trade_thesis'] ?? null,
            'opened_at'        => now(),
        ]);

        return redirect()->to(route('portfolio.ai.index') . '#paper')
            ->with('status', "เปิดคำสั่งซื้อจำลอง {$symbol} จำนวน {$qty} หุ้น (฿" . number_format($totalThb, 2) . ") สำเร็จแล้ว");
    }

    public function closePaperTrade(Request $request, PaperTrade $trade, PriceFetcher $prices)
    {
        $userId = $this->resolvePortfolioUserId();
        abort_unless((int) $trade->user_id === $userId, 403);

        $exitPrice = (float) $request->input('exit_price', 0);
        if ($exitPrice <= 0) {
            $snap = $this->fetchTickerTechnicals($trade->symbol);
            $exitPrice = $snap ? (float) $snap['price'] : (float) ($prices->fetchUnitPrice($trade->kind, $trade->symbol) ?? $trade->current_price ?? $trade->entry_price);
        }

        $fxRate = $trade->currency === 'THB' ? 1.0 : ($prices->fxRateToThb($trade->currency) ?? 34.50);
        $exitTotalThb = round((float) $trade->quantity * $exitPrice * $fxRate, 2);
        $pnlThb = round($exitTotalThb - (float) $trade->entry_total_thb, 2);
        $pnlPct = (float) $trade->entry_total_thb > 0 ? round(($pnlThb / (float) $trade->entry_total_thb) * 100, 2) : 0.0;

        $trade->update([
            'status'           => PaperTrade::STATUS_CLOSED,
            'exit_price'       => $exitPrice,
            'exit_total_thb'   => $exitTotalThb,
            'realized_pnl_thb' => $pnlThb,
            'realized_pnl_pct' => $pnlPct,
            'closed_at'        => now(),
        ]);

        $account = PaperAccount::getOrCreateForUser($userId);
        $account->cash_balance = (float) $account->cash_balance + $exitTotalThb;
        $account->save();

        $sign = $pnlThb >= 0 ? '+' : '';
        return redirect()->to(route('portfolio.ai.index') . '#paper')
            ->with('status', "ปิดออเดอร์จำลอง {$trade->symbol} สำเร็จ (กำไร/ขาดทุน: {$sign}฿" . number_format($pnlThb, 2) . " / {$sign}{$pnlPct}%) คืนเงินสด ฿" . number_format($exitTotalThb, 2) . " เข้าพอร์ตจำลองแล้ว");
    }

    public function destroyPaperTrade(PaperTrade $trade)
    {
        $userId = $this->resolvePortfolioUserId();
        abort_unless((int) $trade->user_id === $userId, 403);

        if ($trade->isOpen()) {
            $account = PaperAccount::getOrCreateForUser($userId);
            $account->cash_balance = (float) $account->cash_balance + (float) $trade->entry_total_thb;
            $account->save();
        }

        $symbol = $trade->symbol;
        $trade->delete();

        return redirect()->to(route('portfolio.ai.index') . '#paper')
            ->with('status', "ยกเลิกและลบออเดอร์ {$symbol} เรียบร้อยแล้ว");
    }

    public function resetPaperAccount(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();
        $initialBalance = max(1000.0, (float) $request->input('initial_balance', 100000.0));

        $account = PaperAccount::getOrCreateForUser($userId);
        $account->initial_balance = $initialBalance;
        $account->cash_balance    = $initialBalance;
        $account->save();

        if ($request->boolean('clear_trades', true)) {
            PaperTrade::forUser($userId)->delete();
        }

        return redirect()->to(route('portfolio.ai.index') . '#paper')
            ->with('status', "รีเซ็ตพอร์ตจำลองเป็นเงินสด ฿" . number_format($initialBalance, 2) . " เรียบร้อยแล้ว");
    }

    public function convertToRealHolding(Request $request, PaperTrade $trade)
    {
        $userId = $this->resolvePortfolioUserId();
        abort_unless((int) $trade->user_id === $userId, 403);

        $holding = Holding::create([
            'user_id'           => $userId,
            'kind'              => $trade->kind ?: Holding::KIND_STOCK,
            'label'             => $trade->label ?: $trade->symbol,
            'symbol'            => $trade->symbol,
            'quantity'          => $trade->quantity,
            'cost_basis'        => $trade->entry_total_thb,
            'currency'          => $trade->currency,
            'current_price'     => $trade->current_price ?: $trade->entry_price,
            'current_value_thb' => $trade->current_value_thb ?: $trade->entry_total_thb,
            'last_priced_at'    => now(),
            'notes'             => "โอนจากพอร์ตจำลองเมื่อ " . now()->format('d/m/Y H:i'),
            'metadata'          => [
                'target_price'        => $trade->target_price,
                'stop_loss'           => $trade->stop_loss,
                'from_paper_trade_id' => $trade->id,
            ],
        ]);

        $trade->update([
            'trade_thesis' => trim(($trade->trade_thesis ?? '') . " [โอนเข้าพอร์ตจริงแล้ว #{$holding->id}]"),
        ]);

        return redirect()->to(route('portfolio.ai.index') . '#real')
            ->with('status', "โอน {$trade->symbol} เข้าพอร์ตจริงเรียบร้อยแล้ว (สามารถตรวจดูและตั้งค่า Target / Stop Loss ได้ในแท็บพอร์ตจริง)");
    }

    public function updateHoldingGuard(Request $request, Holding $holding)
    {
        $userId = $this->resolvePortfolioUserId();
        abort_unless((int) $holding->user_id === $userId, 403);

        $meta = (array) ($holding->metadata ?? []);
        $meta['target_price']   = $request->filled('target_price') ? (float) $request->input('target_price') : null;
        $meta['stop_loss']      = $request->filled('stop_loss') ? (float) $request->input('stop_loss') : null;
        $meta['strategy_notes'] = $request->input('strategy_notes', '');

        $holding->metadata = $meta;
        $holding->save();

        return redirect()->to(route('portfolio.ai.index') . '#real')
            ->with('status', "อัปเดตเป้าขาย (Target) และจุดตัดขาดทุน (Stop Loss) ของ {$holding->label} เรียบร้อยแล้ว");
    }

    public function refreshPrices(PriceFetcher $prices)
    {
        $userId = $this->resolvePortfolioUserId();

        // 1. Refresh real holdings
        $prices->refreshForUser($userId);

        // 2. Refresh open paper trades
        $openTrades = PaperTrade::forUser($userId)->open()->get();
        foreach ($openTrades as $trade) {
            try {
                $snap = $this->fetchTickerTechnicals($trade->symbol);
                $price = $snap ? (float) $snap['price'] : (float) ($prices->fetchUnitPrice($trade->kind, $trade->symbol) ?? 0);
                if ($price > 0) {
                    $fxRate = $trade->currency === 'THB' ? 1.0 : ($prices->fxRateToThb($trade->currency) ?? 34.50);
                    $trade->update([
                        'current_price'     => $price,
                        'current_value_thb' => round((float) $trade->quantity * $price * $fxRate, 2),
                    ]);
                }
            } catch (\Throwable $e) {
                // skip failed ticker
            }
        }

        return redirect()->back()
            ->with('status', 'อัปเดตราคาตลาดสดจาก Yahoo Finance ให้พอร์ตจริงและพอร์ตจำลองเรียบร้อยแล้ว');
    }

    public function ensurePaperTradingTablesExist(): void
    {
        if (!Schema::hasTable('portfolio_paper_accounts')) {
            Schema::create('portfolio_paper_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->decimal('initial_balance', 15, 2)->default(100000.00);
                $table->decimal('cash_balance', 15, 2)->default(100000.00);
                $table->string('currency', 3)->default('THB');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique('user_id');
            });
        }

        if (!Schema::hasTable('portfolio_paper_trades')) {
            Schema::create('portfolio_paper_trades', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('paper_account_id')->nullable();
                $table->string('symbol', 32);
                $table->string('label', 120)->nullable();
                $table->string('kind', 20)->default('stock');
                $table->string('currency', 3)->default('THB');
                $table->string('side', 10)->default('buy');
                $table->string('status', 20)->default('open');
                $table->decimal('quantity', 15, 4);
                $table->decimal('entry_price', 15, 4);
                $table->decimal('entry_total_thb', 15, 2)->default(0.00);
                $table->decimal('current_price', 15, 4)->nullable();
                $table->decimal('current_value_thb', 15, 2)->nullable();
                $table->decimal('target_price', 15, 4)->nullable();
                $table->decimal('stop_loss', 15, 4)->nullable();
                $table->decimal('exit_price', 15, 4)->nullable();
                $table->decimal('exit_total_thb', 15, 2)->nullable();
                $table->decimal('realized_pnl_thb', 15, 2)->nullable();
                $table->decimal('realized_pnl_pct', 8, 2)->nullable();
                $table->text('trade_thesis')->nullable();
                $table->timestamp('opened_at')->useCurrent();
                $table->timestamp('closed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('symbol');
            });
        }
    }

    private function extractTickers(string $text): array
    {
        preg_match_all('/\b([A-Z]{2,10}(?:\.BK|-USD)?)\b/', strtoupper($text), $matches);
        $raw = $matches[1] ?? [];

        $stopWords = [
            'ROE', 'PE', 'MA', 'RSI', 'DCF', 'SMA', 'EMA', 'AI', 'SET', 'THB', 'USD',
            'DAILY', 'STOP', 'LOSS', 'ENTRY', 'YIELD', 'CHART', 'OVERBOUGHT', 'OVERSOLD',
        ];

        $result = [];
        foreach ($raw as $sym) {
            if (in_array($sym, $stopWords, true)) {
                continue;
            }
            if (!str_contains($sym, '.') && !str_contains($sym, '-') && in_array($sym, self::THAI_STOCK_TICKERS, true)) {
                $sym .= '.BK';
            }
            if (!in_array($sym, $result, true)) {
                $result[] = $sym;
            }
        }

        return $result;
    }

    private function fetchTickerTechnicals(string $symbol): ?array
    {
        $yahooSymbol = strtoupper(trim($symbol));
        if ($yahooSymbol === 'BTC') {
            $yahooSymbol = 'BTC-USD';
        } elseif ($yahooSymbol === 'ETH') {
            $yahooSymbol = 'ETH-USD';
        }

        try {
            $url = 'https://query1.finance.yahoo.com/v8/finance/chart/' . rawurlencode($yahooSymbol);
            $res = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; TrimongkolPortfolio/1.0)'])
                ->get($url, [
                    'range'    => '6mo',
                    'interval' => '1d',
                ]);

            if (!$res->successful()) {
                return null;
            }

            $result = data_get($res->json(), 'chart.result.0');
            if (!$result) {
                return null;
            }

            $meta       = data_get($result, 'meta', []);
            $currency   = data_get($meta, 'currency', str_ends_with($yahooSymbol, '.BK') ? 'THB' : 'USD');
            $shortName  = data_get($meta, 'shortName') ?: data_get($meta, 'longName') ?: $yahooSymbol;
            $high52w    = (float) (data_get($meta, 'fiftyTwoWeekHigh') ?? 0);
            $low52w     = (float) (data_get($meta, 'fiftyTwoWeekLow') ?? 0);

            $closesRaw  = (array) data_get($result, 'indicators.quote.0.close', []);
            $highsRaw   = (array) data_get($result, 'indicators.quote.0.high', []);
            $lowsRaw    = (array) data_get($result, 'indicators.quote.0.low', []);
            $volumesRaw = (array) data_get($result, 'indicators.quote.0.volume', []);

            $closes  = [];
            $highs   = [];
            $lows    = [];
            $volumes = [];

            foreach ($closesRaw as $idx => $c) {
                if ($c !== null && is_numeric($c) && (float) $c > 0) {
                    $closes[]  = (float) $c;
                    $highs[]   = isset($highsRaw[$idx]) && is_numeric($highsRaw[$idx]) ? (float) $highsRaw[$idx] : (float) $c;
                    $lows[]    = isset($lowsRaw[$idx]) && is_numeric($lowsRaw[$idx]) ? (float) $lowsRaw[$idx] : (float) $c;
                    $volumes[] = isset($volumesRaw[$idx]) && is_numeric($volumesRaw[$idx]) ? (float) $volumesRaw[$idx] : 0.0;
                }
            }

            $n = count($closes);
            if ($n < 5) {
                return null;
            }

            $currentPrice = (float) (data_get($meta, 'regularMarketPrice') ?: $closes[$n - 1]);
            $prevClose    = $n >= 2 ? $closes[$n - 2] : $currentPrice;
            $change1dPct  = $prevClose > 0 ? round((($currentPrice - $prevClose) / $prevClose) * 100, 2) : 0.0;

            $close1mAgo   = $closes[max(0, $n - 22)];
            $close3mAgo   = $closes[max(0, $n - 64)];
            $close6mAgo   = $closes[0];

            $change1mPct  = $close1mAgo > 0 ? round((($currentPrice - $close1mAgo) / $close1mAgo) * 100, 2) : 0.0;
            $change3mPct  = $close3mAgo > 0 ? round((($currentPrice - $close3mAgo) / $close3mAgo) * 100, 2) : 0.0;
            $change6mPct  = $close6mAgo > 0 ? round((($currentPrice - $close6mAgo) / $close6mAgo) * 100, 2) : 0.0;

            $slice20 = array_slice($closes, -min(20, $n));
            $slice50 = array_slice($closes, -min(50, $n));
            $sma20   = round(array_sum($slice20) / count($slice20), 2);
            $sma50   = round(array_sum($slice50) / count($slice50), 2);

            $rsi14 = $this->computeRsi($closes, 14);

            $recentHighs = array_slice($highs, -min(60, $n));
            $recentLows  = array_slice($lows, -min(60, $n));
            $swingHigh   = round(max($recentHighs), 2);
            $swingLow    = round(min($recentLows), 2);

            $high20      = round(max(array_slice($highs, -min(20, $n))), 2);
            $low20       = round(min(array_slice($lows, -min(20, $n))), 2);

            $support1    = $low20;
            $support2    = $swingLow;
            $resistance1 = $high20;
            $resistance2 = $swingHigh;
            $stopLoss    = round($support1 * 0.965, 2);

            $diff = max(0.01, $swingHigh - $swingLow);
            $fibLevels = [
                '0.0% (Swing High)' => $swingHigh,
                '23.6%'             => round($swingHigh - ($diff * 0.236), 2),
                '38.2%'             => round($swingHigh - ($diff * 0.382), 2),
                '50.0%'             => round($swingHigh - ($diff * 0.500), 2),
                '61.8% (Golden)'    => round($swingHigh - ($diff * 0.618), 2),
                '78.6%'             => round($swingHigh - ($diff * 0.786), 2),
                '100.0% (Swing Low)'=> $swingLow,
            ];

            $volSlice20 = array_slice($volumes, -min(20, count($volumes)));
            $avgVol20   = count($volSlice20) > 0 ? array_sum($volSlice20) / count($volSlice20) : 0.0;
            $latestVol  = end($volumes) ?: 0.0;
            $volRatio   = $avgVol20 > 0 ? round($latestVol / $avgVol20, 2) : 1.0;

            if ($currentPrice > $sma20 && $sma20 >= $sma50) {
                $trendLabel = 'ขาขึ้นแข็งแกร่ง (Strong Uptrend — ราคา > SMA20 > SMA50)';
                $trendState = 'bullish';
            } elseif ($currentPrice < $sma20 && $sma20 <= $sma50) {
                $trendLabel = 'ขาลง (Downtrend — ราคา < SMA20 < SMA50)';
                $trendState = 'bearish';
            } elseif ($currentPrice >= $sma20) {
                $trendLabel = 'ฟื้นตัวระยะสั้น (Short-Term Recovery — ราคา > SMA20)';
                $trendState = 'neutral-bull';
            } else {
                $trendLabel = 'พักฐาน/ย่อตัว (Pullback / Sideways)';
                $trendState = 'neutral-bear';
            }

            if ($rsi14 >= 70) {
                $rsiStatus = 'Overbought (ซื้อมากเกินไป > 70 — ระวังแรงเทขายทำกำไร)';
            } elseif ($rsi14 <= 30) {
                $rsiStatus = 'Oversold (ขายมากเกินไป < 30 — จับจังหวะ Technical Rebound)';
            } elseif ($rsi14 >= 55) {
                $rsiStatus = 'Bullish Momentum (โมเมนตัมเชิงบวก 55–70)';
            } elseif ($rsi14 <= 45) {
                $rsiStatus = 'Weak Momentum (โมเมนตัมชะลอตัว 30–45)';
            } else {
                $rsiStatus = 'Neutral (แกว่งตัวสมดุล 45–55)';
            }

            $returns = [];
            for ($k = 1; $k < $n; $k++) {
                if ($closes[$k - 1] > 0) {
                    $returns[] = ($closes[$k] - $closes[$k - 1]) / $closes[$k - 1];
                }
            }
            $volatilityPct = 0.0;
            if (count($returns) > 2) {
                $mean = array_sum($returns) / count($returns);
                $variance = 0.0;
                foreach ($returns as $r) {
                    $variance += ($r - $mean) ** 2;
                }
                $dailyStd = sqrt($variance / count($returns));
                $volatilityPct = round($dailyStd * sqrt(252) * 100, 1);
            }

            return [
                'symbol'         => $yahooSymbol,
                'name'           => $shortName,
                'currency'       => $currency,
                'price'          => round($currentPrice, 2),
                'change_1d_pct'  => $change1dPct,
                'change_1m_pct'  => $change1mPct,
                'change_3m_pct'  => $change3mPct,
                'change_6m_pct'  => $change6mPct,
                'sma20'          => $sma20,
                'sma50'          => $sma50,
                'rsi14'          => $rsi14,
                'rsi_status'     => $rsiStatus,
                'trend_label'    => $trendLabel,
                'trend_state'    => $trendState,
                'support1'       => $support1,
                'support2'       => $support2,
                'resistance1'    => $resistance1,
                'resistance2'    => $resistance2,
                'stop_loss'      => $stopLoss,
                'fib_levels'     => $fibLevels,
                'vol_ratio'      => $volRatio,
                'volatility_pct' => $volatilityPct,
                'high_52w'       => $high52w > 0 ? round($high52w, 2) : $swingHigh,
                'low_52w'        => $low52w > 0 ? round($low52w, 2) : $swingLow,
            ];
        } catch (\Throwable $e) {
            Log::warning("portfolio.ai_analyst.yahoo_failed: {$yahooSymbol}: " . $e->getMessage());
            return null;
        }
    }

    private function computeRsi(array $closes, int $period = 14): float
    {
        $n = count($closes);
        if ($n <= $period) {
            return 50.0;
        }

        $gains = 0.0;
        $losses = 0.0;
        for ($i = 1; $i <= $period; $i++) {
            $diff = $closes[$i] - $closes[$i - 1];
            if ($diff >= 0) {
                $gains += $diff;
            } else {
                $losses += abs($diff);
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        for ($i = $period + 1; $i < $n; $i++) {
            $diff = $closes[$i] - $closes[$i - 1];
            $gain = $diff > 0 ? $diff : 0.0;
            $loss = $diff < 0 ? abs($diff) : 0.0;

            $avgGain = (($avgGain * ($period - 1)) + $gain) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $loss) / $period;
        }

        if ($avgLoss == 0) {
            return 100.0;
        }

        $rs = $avgGain / $avgLoss;
        return round(100 - (100 / (1 + $rs)), 1);
    }

    public function fetchLiveStockNews(string $symbol): array
    {
        $symbol = strtoupper(trim($symbol));
        if ($symbol === '') {
            return [];
        }

        $cacheKey = 'portfolio.stock_news.' . md5($symbol);

        return Cache::remember($cacheKey, 900, function () use ($symbol) {
            $clean = str_ireplace('.BK', '', $symbol);
            $clean = preg_replace('/[^A-Z0-9_-]/', '', $clean);

            $isThai = str_ends_with($symbol, '.BK') || in_array($symbol, self::THAI_STOCK_TICKERS, true);
            $items = [];

            // 1. For Global / US stocks, query Yahoo Finance search news API
            if (!$isThai) {
                try {
                    $yahooUrl = "https://query2.finance.yahoo.com/v1/finance/search?q=" . rawurlencode($clean) . "&newsCount=6";
                    $resp = Http::timeout(5)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                        ->get($yahooUrl);

                    if ($resp->successful()) {
                        $newsList = $resp->json('news') ?? [];
                        foreach ($newsList as $n) {
                            $items[] = [
                                'title'     => (string) ($n['title'] ?? ''),
                                'publisher' => (string) ($n['publisher'] ?? 'Yahoo Finance'),
                                'link'      => (string) ($n['link'] ?? ''),
                                'pub_date'  => isset($n['providerPublishTime']) ? date('d/m/Y H:i', (int) $n['providerPublishTime']) : '',
                                'source'    => 'Yahoo Finance',
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::debug("portfolio.news.yahoo_failed", ['symbol' => $symbol, 'error' => $e->getMessage()]);
                }
            }

            // 2. Fetch from Google News RSS (supports both Thai SET stocks and Global stocks)
            if (count($items) < 6) {
                try {
                    if ($isThai) {
                        $query = rawurlencode('หุ้น ' . $clean . ' OR ' . $clean . '.BK when:7d');
                        $rssUrl = "https://news.google.com/rss/search?q={$query}&hl=th&gl=TH&ceid=TH:th";
                    } else {
                        $query = rawurlencode($clean . ' stock when:7d');
                        $rssUrl = "https://news.google.com/rss/search?q={$query}&hl=en-US&gl=US&ceid=US:en";
                    }

                    $resp = Http::timeout(6)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                        ->get($rssUrl);

                    if ($resp->successful()) {
                        $body = $resp->body();
                        $prevUseErrors = libxml_use_internal_errors(true);
                        $xml = simplexml_load_string($body);
                        libxml_use_internal_errors($prevUseErrors);

                        if ($xml && isset($xml->channel->item)) {
                            foreach ($xml->channel->item as $item) {
                                $rawTitle = trim((string) $item->title);
                                $source = trim((string) ($item->source ?? ''));
                                if (!$source && str_contains($rawTitle, ' - ')) {
                                    $parts = explode(' - ', $rawTitle);
                                    $source = trim(array_pop($parts));
                                    $rawTitle = trim(implode(' - ', $parts));
                                }

                                $items[] = [
                                    'title'     => $rawTitle,
                                    'publisher' => $source ?: ($isThai ? 'สำนักข่าวการเงิน' : 'Google News'),
                                    'link'      => (string) $item->link,
                                    'pub_date'  => isset($item->pubDate) ? date('d/m/Y H:i', strtotime((string) $item->pubDate)) : '',
                                    'source'    => 'Google News',
                                ];

                                if (count($items) >= 7) {
                                    break;
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::debug("portfolio.news.google_failed", ['symbol' => $symbol, 'error' => $e->getMessage()]);
                }
            }

            return array_slice($items, 0, 7);
        });
    }

    public function evaluateHeadlineSentiment(array $newsItems): array
    {
        $bullishKeywords = [
            'พุ่ง', 'ทะยาน', 'นิวไฮ', 'new high', 'กำไรโต', 'กำไรพุ่ง', 'ฟื้น', 'แกร่ง', 'ซื้อ', 'เคาะ',
            'เป้าใหม่', 'อัพเป้า', 'overweight', 'buy', 'bullish', 'rally', 'surge', 'soar', 'beat',
            'growth', 'dividend', 'ปันผล', 'คว้างาน', 'ขยาย', 'ชนะ', 'บวก', 'record', 'outperform'
        ];
        $bearishKeywords = [
            'ดิ่ง', 'ร่วง', 'ทรุด', 'หลุด', 'กังวล', 'เสี่ยง', 'ขาดทุน', 'ลดเป้า', 'ขาย', 'downgrade',
            'sell', 'bearish', 'drop', 'fall', 'plunge', 'slump', 'miss', 'warning', 'ลบ', 'ปรับฐาน',
            'หน่วง', 'ชะลอ', 'underperform', 'ฟ้อง', 'คดี', 'สอบ'
        ];

        $bullScore = 0;
        $bearScore = 0;

        foreach ($newsItems as $n) {
            $t = mb_strtolower($n['title'] ?? '', 'UTF-8');
            foreach ($bullishKeywords as $bk) {
                if (str_contains($t, $bk)) {
                    $bullScore++;
                }
            }
            foreach ($bearishKeywords as $rk) {
                if (str_contains($t, $rk)) {
                    $bearScore++;
                }
            }
        }

        $total = $bullScore + $bearScore;
        if ($total === 0) {
            $score = 50;
            $label = 'Neutral (ทรงตัว / เฝ้ารอความชัดเจน)';
            $badgeColor = 'amber';
        } else {
            $score = round(($bullScore / $total) * 100);
            if ($score >= 65) {
                $label = 'Bullish (แนวโน้มเชิงบวกชัดเจน)';
                $badgeColor = 'emerald';
            } elseif ($score <= 35) {
                $label = 'Bearish (มีความเสี่ยง / ปัจจัยลบกดดัน)';
                $badgeColor = 'rose';
            } else {
                $label = 'Mixed / Neutral (มีทั้งปัจจัยบวกและลบผสมกัน)';
                $badgeColor = 'sky';
            }
        }

        return [
            'score'       => $score,
            'label'       => $label,
            'color'       => $badgeColor,
            'bull_count'  => $bullScore,
            'bear_count'  => $bearScore,
            'total_count' => count($newsItems),
        ];
    }

    private function callGeminiAnalyst(string $apiKey, string $promptText, array $marketSnapshots, array $liveNews = []): ?array
    {
        $marketContext = '';
        if (!empty($marketSnapshots)) {
            $marketContext .= "\n\n### ข้อมูลตลาดและอินดิเคเตอร์จริงล่าสุดจาก Yahoo Finance (ใช้อ้างอิงตัวเลขนี้ในการวิเคราะห์):\n"
                . json_encode($marketSnapshots, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        if (!empty($liveNews)) {
            $marketContext .= "\n\n### ข้อมูลข่าวสารล่าสุดจากสำนักข่าวการเงินจริง (Google News & Yahoo Finance):\n";
            foreach ($liveNews as $i => $n) {
                $idx = $i + 1;
                $marketContext .= "{$idx}. \"{$n['title']}\" (สำนักข่าว: {$n['publisher']}, วันที่เผยแพร่: {$n['pub_date']})\n";
            }
            $marketContext .= "\nโปรดวิเคราะห์แนวโน้มราคาหุ้นและความเชื่อมั่นตลาด (Market Sentiment) จากข้อมูลข่าวสารล่าสุดข้างต้นร่วมกับสัญญาณทางเทคนิคและสถิติตลาดจริง:";
            $marketContext .= "\n1. ประเมิน Sentiment ภาพรวม (Bullish / Neutral / Bearish พร้อมคะแนน %)";
            $marketContext .= "\n2. วิเคราะห์ประเด็นบวก (Positive Catalysts) และประเด็นลบ/ความเสี่ยง (Negative Catalysts)";
            $marketContext .= "\n3. คาดการณ์ผลกระทบต่อแนวโน้มราคาในระยะสั้น (1–7 วัน) และระยะกลาง (1–3 เดือน)";
            $marketContext .= "\n4. คำแนะนำกลยุทธ์การลงทุน จุดเข้าซื้อ/ชะลอ และจุดตัดขาดทุน (Stop Loss) ที่ปลอดภัย.";
        }

        $systemPrompt = implode(' ', [
            'คุณคือ ที่ปรึกษาการลงทุนอาวุโส (Senior Investment Strategist & CFA) ประจำระบบพอร์ต Trimongkol.',
            'ผู้ใช้งานมี DNA และแนวทางการลงทุนชัดเจนคือ: **ไม่ใช่สายเก็งกำไรเดย์เทรด แต่เป็นสาย "สะสมหุ้นระยะยาว (Accumulator / DCA)"** ด้วยกลยุทธ์ **Core & Satellite Model**:',
            '1. แกนหลักชนะเงินเฟ้อ (Core 60-70%): เน้น "หุ้นพื้นฐานแกร่งชนะเงินเฟ้อ (Beat Inflation)" มี Pricing Power (ส่งผ่านต้นทุนเงินเฟ้อได้), กระแสเงินสดอิสระสูง, คูเมืองทางธุรกิจแข็งแกร่ง (Economic Moat) และจ่ายเงินปันผลเติบโตต่อเนื่องสม่ำเสมอ.',
            '2. ดาวเทียมเกาะกระแส (Satellite 30-40%): "หุ้นเกาะเมกะเทรนด์และกระแสใหม่ๆ (New Trends & Exponential Growth)" เช่น AI, Semiconductor, Data Center, Clean Energy หรือนวัตกรรมใหม่ แต่ต้องวิเคราะห์ว่ากระแสไหนของจริง vs กระแสฟองสบู่ชั่วคราว.',
            '3. สไตล์การเข้าซื้อ: เน้นการทยอยสะสม (DCA รายเดือน) และจังหวะย่อตัวสะสมเมื่อราคาต่ำกว่ามูลค่าที่เหมาะสม (Buy on Valuation Dip) ไม่แนะนำการตั้ง Stop Loss คัตลอสบ่อยๆ แบบสายเดย์เทรด.',
            'ตอบเป็นภาษาไทยที่อ่านเข้าใจง่าย มีโครงสร้างชัดเจน (วิเคราะห์ความแข็งแกร่งของธุรกิจ, การชนะเงินเฟ้อ, โอกาสเกาะกระแส, โซนราคาที่น่าทยอยสะสม, และคำแนะนำแบ่งไม้ DCA).',
            'หากมีข้อมูลราคาจริงจาก Yahoo Finance หรือข่าวจริงจากสำนักข่าวการเงินแนบไป ให้ใช้ข้อมูลจริงเหล่านั้นในการวิเคราะห์อย่างตรงไปตรงมาและแม่นยำทันที.',
        ]);

        $models = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'];

        foreach ($models as $model) {
            $response = Http::timeout(45)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                    'contents'           => [[
                        'role'  => 'user',
                        'parts' => [['text' => "คำสั่งวิเคราะห์:\n\"{$promptText}\"{$marketContext}"]],
                    ]],
                    'generationConfig'   => [
                        'temperature'     => 0.35,
                        'maxOutputTokens' => 2500,
                    ],
                ]
            );

            if ($response->successful()) {
                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                if ($text && trim($text) !== '') {
                    return [
                        'text'  => trim($text),
                        'model' => "Google Gemini ({$model}) + Live Yahoo & News Feed",
                    ];
                }
            }
        }

        return null;
    }

    private function buildQuantAnalysisReport(int $promptId, string $promptText, array $marketSnapshots, array $liveNews = [], array $sentiment = []): string
    {
        $lines = [];
        $lines[] = "## 📊 บทวิเคราะห์เจาะลึก: ตามคำสั่ง Prompt #{$promptId}";
        $lines[] = "> **โจทย์การวิเคราะห์:** \"{$promptText}\"";
        $lines[] = "";

        if (!empty($marketSnapshots)) {
            $lines[] = "### 1. สรุปข้อมูลตลาดและสัญญาณเทคนิคจริง (Real-Time Yahoo Finance)";
            foreach ($marketSnapshots as $s) {
                $ccy  = $s['currency'];
                $sign = $s['change_1d_pct'] >= 0 ? '+' : '';
                $lines[] = "- **{$s['symbol']} ({$s['name']})**: ราคาปัจจุบัน **{$s['price']} {$ccy}** ({$sign}{$s['change_1d_pct']}% วันนี้ | ย้อนหลัง 1 เดือน {$s['change_1m_pct']}% | 6 เดือน {$s['change_6m_pct']}%)";
                $lines[] = "  - **แนวโน้ม (Trend):** {$s['trend_label']} (`SMA20 = {$s['sma20']}`, `SMA50 = {$s['sma50']}`)";
                $lines[] = "  - **โมเมนตัม (RSI 14 วัน):** `{$s['rsi14']}` — {$s['rsi_status']}";
                $lines[] = "  - **กรอบแนวรับ / แนวต้าน:** แนวรับ 1 = `{$s['support1']}` | แนวรับ 2 = `{$s['support2']}` | แนวต้าน 1 = `{$s['resistance1']}` | แนวต้าน 2 = `{$s['resistance2']}`";
                $lines[] = "  - **จุดตัดขาดทุนแนะนำ (Stop Loss -3.5%):** `{$s['stop_loss']} {$ccy}` | **ความผันผวนต่อปี (Volatility):** `{$s['volatility_pct']}%` | **Volume ต่อค่าเฉลี่ย 20 วัน:** `{$s['vol_ratio']}x`";
            }
            $lines[] = "";
        }

        if (!empty($liveNews)) {
            $lines[] = "### 📰 สรุปข่าวสารและการประเมิน Sentiment ล่าสุด (Google News & Yahoo Finance)";
            if (!empty($sentiment['label'])) {
                $lines[] = "- **ผลการประเมิน Sentiment จากพาดหัวข่าว:** **{$sentiment['label']} (คะแนนความเชื่อมั่น {$sentiment['score']}%)**";
                $lines[] = "- **สัดส่วนข่าว:** ข่าวเชิงบวก `{$sentiment['bull_count']}` ข่าว | ข่าวเชิงลบ/ความเสี่ยง `{$sentiment['bear_count']}` ข่าว (จากทั้งหมด `{$sentiment['total_count']}` ข่าวล่าสุด)";
            }
            $lines[] = "- **รายการข่าวล่าสุดที่มีผลต่อแนวโน้มราคา:**";
            foreach ($liveNews as $i => $n) {
                $num = $i + 1;
                $linkMarkdown = !empty($n['link']) ? "[{$n['title']}]({$n['link']})" : $n['title'];
                $lines[] = "  {$num}. **{$linkMarkdown}** — *{$n['publisher']}* ({$n['pub_date']})";
            }
            $lines[] = "";
        }

        $primary = $marketSnapshots[0] ?? null;

        switch ($promptId) {
            case 1:
                $lines[] = "### 2. การเปรียบเทียบปัจจัยพื้นฐาน 3 หุ้น (ROE, P/E, Net Profit Margin)";
                $lines[] = "- **เกณฑ์การคัดกรองหุ้นพื้นฐานแกร่ง (Quality Growth & Value):**";
                $lines[] = "  1. **ROE (Return on Equity > 15%):** สะท้อนความสามารถในการทำกำไรจากส่วนของผู้ถือหุ้นอย่างมีประสิทธิภาพ";
                $lines[] = "  2. **P/E Ratio เทียบค่าเฉลี่ยอุตสาหกรรม:** เลือกหุ้นที่ P/E ไม่ตึงตัวเกินไปเมื่อเทียบกับการเติบโตของกำไรสุทธิ (PEG < 1.5)";
                $lines[] = "  3. **อัตรากำไรสุทธิ (Net Profit Margin):** มีแนวโน้มรักษาระดับหรือขยายตัวต่อเนื่องตลอด 3 ปี จากอำนาจในการตั้งราคา (Pricing Power)";
                if (count($marketSnapshots) >= 2) {
                    $bestMomentum = collect($marketSnapshots)->sortByDesc('change_6m_pct')->first();
                    $lowestVol    = collect($marketSnapshots)->sortBy('volatility_pct')->first();
                    $lines[] = "- **สรุปเปรียบเทียบจากข้อมูลตลาดจริง:**";
                    $lines[] = "  - หุ้นที่มีความแข็งแกร่งของราคา (Relative Strength 6 เดือน) สูงสุดในกลุ่มคือ **{$bestMomentum['symbol']}** (`{$bestMomentum['change_6m_pct']}%`)";
                    $lines[] = "  - หุ้นที่มีความผันผวนต่ำที่สุด (เหมาะถือลงทุนระยะกลาง-ยาว) คือ **{$lowestVol['symbol']}** (ความผันผวน `{$lowestVol['volatility_pct']}%/ปี`)";
                }
                break;

            case 2:
                $lines[] = "### 2. แผนกลยุทธ์เข้าซื้อ (Entry) แนวรับ-แนวต้าน และจุดตัดขาดทุน (Stop Loss)";
                if ($primary) {
                    $ccy = $primary['currency'];
                    $rrGain = max(0.01, $primary['resistance1'] - $primary['support1']);
                    $rrLoss = max(0.01, $primary['support1'] - $primary['stop_loss']);
                    $rrRatio = round($rrGain / $rrLoss, 2);
                    $lines[] = "- **ไม้ที่ 1 (สะสมเมื่อย่อตัวใกล้แนวรับแรก):** โซน **`{$primary['support1']} {$ccy}`** (กรอบ Low 20 วัน)";
                    $lines[] = "- **ไม้ที่ 2 (แนวรับสำคัญ Major Support):** โซน **`{$primary['support2']} {$ccy}`** (กรอบ Swing Low 60 วัน)";
                    $lines[] = "- **เป้าหมายทำกำไร (Take Profit):** แนวต้านแรก **`{$primary['resistance1']} {$ccy}`** และแนวต้านหลัก **`{$primary['resistance2']} {$ccy}`**";
                    $lines[] = "- **จุดตัดขาดทุน (Stop Loss):** ปิดความเสี่ยงทันทีหากราคาปิดต่ำกว่า **`{$primary['stop_loss']} {$ccy}`** (-3.5% จากแนวรับแรก) คิดเป็น **Risk/Reward Ratio ≈ 1 : {$rrRatio}**";
                }
                break;

            case 3:
                $lines[] = "### 2. การวิเคราะห์แนวโน้ม (Moving Average) และโมเมนตัม (RSI)";
                if ($primary) {
                    $lines[] = "- **สัญญาณเส้นค่าเฉลี่ย (MA Analysis):** ราคาปัจจุบัน (`{$primary['price']}`) เทียบกับ `SMA20 ({$primary['sma20']})` และ `SMA50 ({$primary['sma50']})` บ่งชี้สภาวะ **{$primary['trend_label']}**";
                    $lines[] = "- **สัญญาณโมเมนตัม (RSI 14 วัน = `{$primary['rsi14']}`):** อยู่ในโซน **{$primary['rsi_status']}**";
                    $lines[] = "- **กลยุทธ์แนะนำ:** " . ($primary['rsi14'] >= 70
                        ? "หลีกเลี่ยงการไล่ราคา (Chase Buy) รอจังหวะราคาย่อตัวลงมาทดสอบเส้น SMA20 ที่ `{$primary['sma20']}`"
                        : ($primary['rsi14'] <= 35
                            ? "ราคาเข้าใกล้เขตขายมากเกินไป (Oversold) สามารถทยอยแบ่งไม้สะสมเมื่อเห็นแท่งเทียนกลับตัวเหนือแนวรับ `{$primary['support1']}`"
                            : "แนวโน้มยังสมดุล สามารถถือรันเทรนด์ (Let Profits Run) โดยมีเส้น SMA20 (`{$primary['sma20']}`) เป็นจุด Trailing Stop"));
                }
                break;

            case 4:
                $lines[] = "### 2. เปรียบเทียบหุ้นปันผล (Dividend Yield) และจังหวะกราฟเทคนิค (Chart Pattern)";
                $lines[] = "- **หลักการเลือกหุ้นปันผล (Dividend Capture & Total Return):** ไม่ควรดูเพียงอัตราปันผลสูงอย่างเดียว แต่ต้องเลือกตัวที่กราฟไม่เป็นขาลงรุนแรงเพื่อป้องกัน **กับดักปันผล (Dividend Trap)**";
                if (count($marketSnapshots) >= 2) {
                    foreach ($marketSnapshots as $s) {
                        $lines[] = "  - **{$s['symbol']}**: ราคา `{$s['price']} {$s['currency']}` | แนวโน้ม: {$s['trend_label']} | RSI `{$s['rsi14']}` | โซนรับสะสมเพื่อรับปันผล: **`{$s['support1']} – {$s['support2']} {$s['currency']}`**";
                    }
                }
                break;

            case 5:
                $lines[] = "### 2. การคัดกรองหุ้นรับประโยชน์จากธีมเศรษฐกิจ & สัญญาณ Breakout";
                $lines[] = "- **เงื่อนไขยืนยันการ Breakout ที่แท้จริง:** ราคาต้องทะลุแนวต้าน 20 วัน (`Resistance 1`) พร้อมปริมาณการซื้อขาย (`Volume Ratio`) สูงกว่าค่าเฉลี่ย 20 วันตั้งแต่ **`1.30x` ขึ้นไป** และ RSI ยืนเหนือ `55`";
                foreach ($marketSnapshots as $s) {
                    $isBreakout = $s['price'] >= ($s['resistance1'] * 0.985) && $s['rsi14'] >= 55;
                    $status = $isBreakout ? "✅ **จ่อ Breakout แนวต้าน `{$s['resistance1']}`**" : "⏳ **สะสมกำลังในกรอบ `{$s['support1']} – {$s['resistance1']}`**";
                    $lines[] = "  - **{$s['symbol']}**: {$status} (Volume ปัจจุบัน `{$s['vol_ratio']}x` ของค่าเฉลี่ย 20 วัน, RSI `{$s['rsi14']}`)";
                }
                break;

            case 6:
                $lines[] = "### 2. การวิเคราะห์ความเสี่ยงพอร์ตลงทุน (Sector Concentration & Diversification)";
                $lines[] = "- **การประเมินความกระจุกตัว:** หากหุ้นตัวใดตัวหนึ่งมีสัดส่วนเกิน **35%–40%** ของพอร์ต จะทำให้พอร์ตมีความเสี่ยงเฉพาะตัว (Idiosyncratic Risk) สูงเกินไป";
                $lines[] = "- **คำแนะนำการกระจายความเสี่ยง (Asset & Sector Allocation):**";
                $lines[] = "  1. **กลุ่ม Defensive / การแพทย์ / ค้าปลีกจำเป็น** (เช่น `BDMS.BK`, `CPALL.BK`, หรือ Healthcare ETF) สัดส่วน **15–20%** เพื่อลดแรงกระแทกยามตลาดผันผวน";
                $lines[] = "  2. **กลุ่มปันผลมั่นคง (High Dividend / Telecom / Bank)** สัดส่วน **20–25%** เพื่อสร้างกระแสเงินสดสม่ำเสมอ";
                $lines[] = "  3. **สินทรัพย์สภาพคล่อง/ตราสารหนี้ระยะสั้น** สัดส่วน **10–15%** สำหรับเป็นกระสุนสำรอง (Dry Powder) ช้อนซื้อเมื่อตลาดปรับฐาน";
                break;

            case 7:
                $lines[] = "### 2. แผนการปรับพอร์ตลดความเสี่ยง (Portfolio De-risking 20%)";
                if (!empty($marketSnapshots)) {
                    $sortedByVol = collect($marketSnapshots)->sortByDesc('volatility_pct')->values();
                    $highestRisk = $sortedByVol->first();
                    $lines[] = "- **ลำดับหุ้นที่ควรทยอยลดสัดส่วน (เรียงตามความผันผวนจริงสูงสุด):**";
                    foreach ($sortedByVol as $idx => $s) {
                        $rank = $idx + 1;
                        $lines[] = "  {$rank}. **{$s['symbol']}** — ความผันผวนต่อปี **`{$s['volatility_pct']}%`**, RSI `{$s['rsi14']}` (แนะนำทยอย Trim ขายเมื่อราคาดีดตัวใกล้แนวต้าน `{$s['resistance1']}`)";
                    }
                    $lines[] = "- **การจัดสรรเงิน 20% ที่ขายออก:** แบ่ง **12%** เข้ากองทุนตราสารหนี้ระยะสั้น/ตลาดเงิน (สภาพคล่องสูง ความผันผวนต่ำกว่า 2%) และอีก **8%** เข้าสินทรัพย์ป้องกันความเสี่ยงหรือหุ้นปันผลผันผวนต่ำ";
                }
                break;

            case 8:
                $lines[] = "### 2. การวิเคราะห์ Market Sentiment และผลกระทบต่อแนวโน้มราคา (Trend Outlook)";
                if (!empty($liveNews) && !empty($sentiment['label'])) {
                    $sym = $primary['symbol'] ?? 'หุ้นเป้าหมาย';
                    $ccy = $primary['currency'] ?? 'THB';
                    $lines[] = "- **ผลการสังเคราะห์ Sentiment ข่าวของ {$sym}:** **{$sentiment['label']} (คะแนนความเชื่อมั่น {$sentiment['score']}%)**";
                    $lines[] = "- **การคาดการณ์แนวโน้มและทิศทางราคา (Trend & Price Impact):**";
                    if ($sentiment['score'] >= 60) {
                        $lines[] = "  - **ระยะสั้น (1–7 วัน):** ได้รับแรงหนุนเชิงบวกจากกระแสข่าวล่าสุด มีโอกาสเกิด Momentum Breakout หรือทดสอบแนวต้านสำคัญ `{$primary['resistance1']} {$ccy}`";
                        $lines[] = "  - **ระยะกลาง (1–3 เดือน):** หากปัจจัยบวกจากข่าวยังคงหนุนเนื่อง ให้จับตาการยืนเหนือระดับเส้นค่าเฉลี่ย `SMA20 = {$primary['sma20']} {$ccy}` เพื่อยืนยันแนวโน้มขาขึ้นรอบใหม่";
                    } elseif ($sentiment['score'] <= 40) {
                        $lines[] = "  - **ระยะสั้น (1–7 วัน):** มีแรงกดดันจากประเด็นลบหรือความกังวลในข่าว แนะนำชะลอการไล่ราคา และรอดูแรงรับที่แนวรับ 1 (`{$primary['support1']} {$ccy}`) หรือแนวรับ 2 (`{$primary['support2']} {$ccy}`)";
                        $lines[] = "  - **ระยะกลาง (1–3 เดือน):** รอให้ราคาเริ่มสร้างฐาน (Base Formation) และโมเมนตัม RSI พลิกกลับมายืนเหนือ 45–50 ก่อนจึงพิจารณาสะสม";
                    } else {
                        $lines[] = "  - **ระยะสั้น (1–7 วัน):** ปัจจัยข่าวสารยังมีความสมดุลระหว่างแรงซื้อและแรงขาย คาดว่าจะเคลื่อนไหวแบบ Sideway ในกรอบ `{$primary['support1']} – {$primary['resistance1']} {$ccy}`";
                        $lines[] = "  - **ระยะกลาง (1–3 เดือน):** รอปัจจัยเร่งใหม่ (Catalyst) เช่น การรายงานผลประกอบการไตรมาสใหม่ หรือการปรับเป้าของโบรกเกอร์";
                    }
                    if ($primary) {
                        $lines[] = "- **คำแนะนำการบริหารความเสี่ยง:** จุด Stop Loss เคร่งครัดอยู่ที่ **`{$primary['stop_loss']} {$ccy}`** (หากราคาหลุดระดับนี้ให้พิจารณาตัดขาดทุนหรือลดขนาดพอร์ตทันที)";
                    }
                } elseif ($primary) {
                    $st = ($primary['change_1m_pct'] >= 0 && $primary['rsi14'] >= 50)
                        ? "เชิงบวก (Bullish Sentiment — มีแรงซื้อสะสมต่อเนื่อง)"
                        : "ระมัดระวัง / พักฐาน (Cautious Sentiment — ตลาดรอดูปัจจัยบวกใหม่)";
                    $lines[] = "- **Sentiment ตลาดปัจจุบันของ {$primary['symbol']}:** **{$st}**";
                    $lines[] = "- **สัญญาณจาก Volume & Price Action:** ผลตอบแทน 1 เดือนอยู่ที่ `{$primary['change_1m_pct']}%` ด้วย Volume Ratio `{$primary['vol_ratio']}x` ของค่าเฉลี่ย 20 วัน";
                    $lines[] = "- **กรอบราคาสำคัญที่ตลาดจับตา:** หากยืนเหนือ `{$primary['sma20']}` ได้ต่อเนื่อง มุมมองตลาดจะยังคงเป็นบวก โดยมีแนวต้านถัดไปที่ `{$primary['resistance1']}`";
                }
                break;

            case 9:
                $lines[] = "### 2. การประเมินกรอบมูลค่าที่เหมาะสม (Valuation Range & Margin of Safety)";
                if ($primary) {
                    $ccy = $primary['currency'];
                    $fairMid = round(($primary['sma50'] + $primary['high_52w']) / 2, 2);
                    $mosPrice = round($fairMid * 0.85, 2);
                    $upsidePct = $primary['price'] > 0 ? round((($fairMid - $primary['price']) / $primary['price']) * 100, 1) : 0;
                    $lines[] = "- **กรอบราคา 52 สัปดาห์ของ {$primary['symbol']}:** ต่ำสุด `{$primary['low_52w']} {$ccy}` — สูงสุด `{$primary['high_52w']} {$ccy}` (ราคาปัจจุบัน `{$primary['price']} {$ccy}`)";
                    $lines[] = "- **ระดับราคาค่าเฉลี่ยระยะกลาง (Mean Reversion / Base Value):** `{$fairMid} {$ccy}` (คิดเป็น Upside/Downside `{$upsidePct}%` จากราคาปัจจุบัน)";
                    $lines[] = "- **โซนเข้าซื้อที่มี Margin of Safety (เผื่อส่วนลดความปลอดภัย ~15%):** โซน **`{$mosPrice} – {$primary['support1']} {$ccy}`**";
                }
                break;

            case 10:
            default:
                $lines[] = "### 2. การวิเคราะห์ระดับการย่อตัวด้วย Fibonacci Retracement & Volume Analysis";
                if ($primary) {
                    $ccy = $primary['currency'];
                    $lines[] = "- **ระดับ Fibonacci Retracement ของ {$primary['symbol']} (คำนวณจากกรอบ Swing Low `{$primary['support2']}` → Swing High `{$primary['resistance2']}`):**";
                    foreach ($primary['fib_levels'] as $lvl => $val) {
                        $marker = abs($primary['price'] - $val) <= ($primary['price'] * 0.02) ? ' 👈 **(ใกล้ราคาปัจจุบัน)**' : '';
                        $lines[] = "  - **ระดับ {$lvl}:** `{$val} {$ccy}`{$marker}";
                    }
                    $lines[] = "- **การยืนยันด้วย Volume Analysis:** Volume ล่าสุดอยู่ที่ **`{$primary['vol_ratio']}x`** เทียบค่าเฉลี่ย 20 วัน — โซนตั้งรับที่แข็งแกร่งที่สุดตามหลัก Golden Ratio คือระดับ **38.2% (`{$primary['fib_levels']['38.2%']} {$ccy}`)** และ **61.8% (`{$primary['fib_levels']['61.8% (Golden)']} {$ccy}`)** โดยมีจุด Stop Loss หากหลุด `{$primary['stop_loss']} {$ccy}`";
                }
                break;
        }

        return implode("\n", $lines);
    }

    private function resolveApiKey(): ?string
    {
        foreach (['portfolio.gemini_key', 'social.gemini_key'] as $keyName) {
            $stored = Setting::where('key', $keyName)->value('value');
            if ($stored) {
                try {
                    return Crypt::decryptString($stored);
                } catch (\Throwable) {
                    // ignore invalid encrypted value
                }
            }
        }

        return config('services.gemini.key') ?: null;
    }

    private function resolvePortfolioUserId(): int
    {
        $allowed      = (array) config('portfolio.allowed_emails', []);
        $primaryEmail = !empty($allowed) ? strtolower($allowed[0]) : null;
        if ($primaryEmail) {
            $owner = \App\Models\User::where('email', $primaryEmail)->first();
            if ($owner) return $owner->id;
        }
        return (int) auth()->id();
    }
}
