{{--
    Receipt import — phase 1. Parses a 7-Eleven style e-receipt PDF entirely in
    the browser with pdf.js (loaded lazily from CDN — no composer/npm dep, no
    server processing) and pre-fills the existing "add entry" ledger form. The
    user always reviews before saving. Falls back gracefully when a field can't
    be read.
--}}
<script>
(function () {
    window.LEDGER_RECEIPT_MSG = {
        onlyPdf:  @js(__('app.portfolio.ledger.receipt_only_pdf')),
        noText:   @js(__('app.portfolio.ledger.receipt_no_text')),
        loadFail: @js(__('app.portfolio.ledger.receipt_load_fail')),
        failed:   @js(__('app.portfolio.ledger.receipt_failed')),
        filled:   @js(__('app.portfolio.ledger.receipt_filled')),
    };

    var PDFJS_VER = '3.11.174';
    var _pdfjsPromise = null;

    function loadPdfjs() {
        if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
        if (_pdfjsPromise) return _pdfjsPromise;
        _pdfjsPromise = new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/' + PDFJS_VER + '/pdf.min.js';
            s.onload = function () {
                try {
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/' + PDFJS_VER + '/pdf.worker.min.js';
                } catch (e) {}
                resolve(window.pdfjsLib);
            };
            s.onerror = function () { reject(new Error(window.LEDGER_RECEIPT_MSG.loadFail)); };
            document.head.appendChild(s);
        });
        return _pdfjsPromise;
    }

    // Group text fragments into visual lines using their x/y positions so a
    // label and its number on the same printed row stay together (pdf.js linear
    // order alone splits them apart on these receipts).
    function groupLines(items) {
        items.sort(function (a, b) { return (a.page - b.page) || (b.y - a.y) || (a.x - b.x); });
        var lines = [], cur = null;
        items.forEach(function (it) {
            if (!cur || it.page !== cur.page || Math.abs(it.y - cur.y) > 2.5) {
                cur = { page: it.page, y: it.y, parts: [it] };
                lines.push(cur);
            } else {
                cur.parts.push(it);
            }
        });
        return lines.map(function (l) {
            l.parts.sort(function (a, b) { return a.x - b.x; });
            return { text: l.parts.map(function (p) { return p.str; }).join('') };
        });
    }

    function mostFrequent(arr) {
        var c = {}, best = arr[0], bestN = 0;
        arr.forEach(function (v) { c[v] = (c[v] || 0) + 1; if (c[v] > bestN) { bestN = c[v]; best = v; } });
        return best;
    }

    var NUM = /\d{1,3}(?:,\d{3})*\.\d{2}/;
    var NUM_G = /\d{1,3}(?:,\d{3})*\.\d{2}/g;

    function parseFields(lines) {
        var fullText = lines.map(function (l) { return l.text; }).join('\n');
        var out = { date: '', amount: '', label: '7-Eleven', notes: '' };

        // ── Date: dd/mm/yyyy, convert Buddhist year (2569) → CE (2026) ──
        var dates = [], m, re = /(\d{1,2})\/(\d{1,2})\/(\d{2,4})/g;
        while ((m = re.exec(fullText))) {
            var y = parseInt(m[3], 10);
            if (y < 100) y += 2500;
            var ce = y > 2400 ? y - 543 : y;
            if (ce >= 2015 && ce <= 2035) {
                out.date = out.date; // noop keep linter calm
                dates.push(ce + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0'));
            }
        }
        if (dates.length) out.date = mostFrequent(dates);

        // ── Grand total: prefer a line carrying a "total" keyword, else the
        //    last money figure before the notes/footer section ──
        var totalKw = /(ทั้งสิ้น|รวมเงิน|รวมทั้งสิ้น|ยอดสุทธิ|รวมสุทธิ|grand\s*total|net\s*total|amount\s*due)/i;
        lines.forEach(function (l) {
            if (totalKw.test(l.text)) {
                var ms = l.text.match(NUM_G);
                if (ms && ms.length) out.amount = ms[ms.length - 1].replace(/,/g, '');
            }
        });
        if (!out.amount) {
            var body = fullText.split(/หมายเหตุ|หมยเหตุ|remark/i)[0];
            var ms2 = body.match(NUM_G);
            if (ms2 && ms2.length) out.amount = ms2[ms2.length - 1].replace(/,/g, '');
        }

        // ── Store / branch ──
        var lm = fullText.match(/7[\s\-]?eleven[^\n]{0,25}/i);
        if (lm) out.label = lm[0].replace(/vat.*$/i, '').replace(/\s+/g, ' ').trim();

        // ── Tax-invoice number (e.g. H111976906150002) → into notes ──
        var inv = fullText.match(/\b([A-Z]\d{12,})\b/);
        if (inv) out.notes = 'ใบกำกับภาษี ' + inv[1];

        return out;
    }

    window.parseReceipt = async function (file) {
        if (!file) throw new Error(window.LEDGER_RECEIPT_MSG.failed);
        var isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name || '');
        if (!isPdf) throw new Error(window.LEDGER_RECEIPT_MSG.onlyPdf);

        var pdfjsLib = await loadPdfjs();
        var buf = await file.arrayBuffer();
        var pdf = await pdfjsLib.getDocument({ data: new Uint8Array(buf) }).promise;

        var items = [];
        for (var p = 1; p <= pdf.numPages; p++) {
            var page = await pdf.getPage(p);
            var tc = await page.getTextContent();
            tc.items.forEach(function (it) {
                if (it.str && it.str.trim() !== '') {
                    items.push({ str: it.str, x: it.transform[4], y: it.transform[5], page: p });
                }
            });
        }
        if (!items.length) throw new Error(window.LEDGER_RECEIPT_MSG.noText);

        return parseFields(groupLines(items));
    };

    // Fill the existing add-entry form (scoped by id so per-row edit forms,
    // which reuse the same field names, are never touched).
    window.fillLedgerAddForm = function (f) {
        var form = document.getElementById('ledger-add-form');
        if (!form) return;
        var set = function (name, val) {
            var el = form.querySelector('[name="' + name + '"]');
            if (el && val != null && val !== '') el.value = val;
        };
        set('date', f.date);
        set('amount', f.amount);
        set('label', f.label);
        set('notes', f.notes);
    };
})();
</script>
