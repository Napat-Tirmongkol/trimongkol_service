<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePostContentJob;
use App\Models\SocialFeedSource;
use App\Models\SocialPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchSocialFeedsCommand extends Command
{
    protected $signature = 'social:fetch-feeds {--source= : Fetch only this feed source ID}';
    protected $description = 'Fetch RSS feeds and queue AI content generation for new items';

    public function handle(): int
    {
        $query = SocialFeedSource::where('is_active', true);
        if ($sourceId = $this->option('source')) {
            $query->where('id', $sourceId);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->info('No active feed sources.');
            return self::SUCCESS;
        }

        $newCount = 0;

        foreach ($sources as $source) {
            try {
                $items = $this->parseFeed($source->url);
                foreach ($items as $item) {
                    if (SocialPost::where('source_url', $item['url'])->exists()) {
                        continue;
                    }

                    $post = SocialPost::create([
                        'feed_source_id'     => $source->id,
                        'title'              => $item['title'],
                        'source_url'         => $item['url'],
                        'source_published_at'=> $item['published_at'],
                        'status'             => 'pending_ai',
                    ]);

                    GeneratePostContentJob::dispatch($post->id);
                    $newCount++;
                }

                $source->update(['last_fetched_at' => now(), 'fail_count' => 0]);
            } catch (\Throwable $e) {
                Log::warning("social: failed to fetch feed {$source->id}: ".$e->getMessage());
                $source->increment('fail_count');
                $this->warn("Feed #{$source->id} ({$source->name}) failed: ".$e->getMessage());
            }
        }

        $this->info("Done. {$newCount} new item(s) queued for AI generation.");
        return self::SUCCESS;
    }

    private function parseFeed(string $url): array
    {
        $response = Http::timeout(15)->withUserAgent('Mozilla/5.0 (compatible; TrimongkolBot/1.0)')->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()} from {$url}");
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response->body());
        libxml_clear_errors();

        if ($xml === false) {
            throw new \RuntimeException("Could not parse XML from {$url}");
        }

        $items = [];

        // RSS 2.0
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $link = (string) ($item->link ?? $item->guid ?? '');
                $title = (string) ($item->title ?? '');
                $pubDate = (string) ($item->pubDate ?? '');

                if (! $link || ! $title) continue;

                $items[] = [
                    'title'        => html_entity_decode(trim($title), ENT_QUOTES | ENT_HTML5),
                    'url'          => trim($link),
                    'published_at' => $pubDate ? \Carbon\Carbon::parse($pubDate)->toDateTimeString() : null,
                ];
            }
        }

        // Atom
        if (isset($xml->entry)) {
            foreach ($xml->entry as $entry) {
                $link = (string) ($entry->link['href'] ?? $entry->id ?? '');
                $title = (string) ($entry->title ?? '');
                $published = (string) ($entry->published ?? $entry->updated ?? '');

                if (! $link || ! $title) continue;

                $items[] = [
                    'title'        => html_entity_decode(trim($title), ENT_QUOTES | ENT_HTML5),
                    'url'          => trim($link),
                    'published_at' => $published ? \Carbon\Carbon::parse($published)->toDateTimeString() : null,
                ];
            }
        }

        // Return newest 10 items to avoid flooding
        return array_slice($items, 0, 10);
    }
}
