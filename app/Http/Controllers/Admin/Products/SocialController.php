<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePostContentJob;
use App\Jobs\PublishToFacebookJob;
use App\Models\Setting;
use App\Models\SocialFeedSource;
use App\Models\SocialPost;
use App\Services\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;

class SocialController extends Controller
{
    // --- Dashboard ---

    public function dashboard()
    {
        $stats = [
            'feeds'          => SocialFeedSource::count(),
            'feeds_active'   => SocialFeedSource::where('is_active', true)->count(),
            'posts_total'    => SocialPost::count(),
            'posts_draft'    => SocialPost::where('status', 'draft')->count(),
            'posts_approved' => SocialPost::where('status', 'approved')->count(),
            'posts_published_today' => SocialPost::where('status', 'published')
                ->whereDate('published_at', today())
                ->count(),
        ];

        $recent = SocialPost::with('feedSource:id,name')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.products.social.dashboard', compact('stats', 'recent'));
    }

    // --- Feed Sources ---

    public function feeds()
    {
        $feeds = SocialFeedSource::latest()->get();
        return view('admin.products.social.feeds', compact('feeds'));
    }

    public function storeFeed(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'url'  => 'required|url|max:500',
        ]);

        SocialFeedSource::create($data);
        AuditLog::record('social.feed.store', null, $data['name']);

        return back()->with('status', __('app.admin.products.social.feed_created'));
    }

    public function toggleFeed(SocialFeedSource $feed)
    {
        $feed->update(['is_active' => ! $feed->is_active]);
        AuditLog::record('social.feed.toggle', null, $feed->name, ['active' => $feed->is_active]);

        return back()->with('status', __('app.admin.products.social.feed_updated'));
    }

    public function destroyFeed(SocialFeedSource $feed)
    {
        $label = $feed->name;
        $feed->delete();
        AuditLog::record('social.feed.destroy', null, $label);

        return back()->with('status', __('app.admin.products.social.feed_deleted'));
    }

    // --- Posts ---

    public function posts(Request $request)
    {
        $status = $request->query('status', '');

        $posts = SocialPost::with('feedSource:id,name')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = SocialPost::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('admin.products.social.posts', compact('posts', 'status', 'counts'));
    }

    public function approvePost(SocialPost $post)
    {
        if (! $post->isDraft()) {
            return back()->with('error', __('app.admin.products.social.post_not_draft'));
        }

        $post->update(['status' => 'approved']);
        AuditLog::record('social.post.approve', null, $post->title);

        return back()->with('status', __('app.admin.products.social.post_approved'));
    }

    public function rejectPost(SocialPost $post)
    {
        if (! $post->isDraft() && ! $post->isApproved()) {
            return back()->with('error', __('app.admin.products.social.post_not_approvable'));
        }

        $post->update(['status' => 'failed', 'error_message' => 'Rejected by admin']);
        AuditLog::record('social.post.reject', null, $post->title);

        return back()->with('status', __('app.admin.products.social.post_rejected'));
    }

    public function publishPost(SocialPost $post)
    {
        if (! $post->isApproved()) {
            return back()->with('error', __('app.admin.products.social.post_not_approved'));
        }

        PublishToFacebookJob::dispatch($post->id);
        AuditLog::record('social.post.publish', null, $post->title);

        return back()->with('status', __('app.admin.products.social.post_queued'));
    }

    public function destroyPost(SocialPost $post)
    {
        $label = $post->title;
        $post->delete();
        AuditLog::record('social.post.destroy', null, $label);

        return back()->with('status', __('app.admin.products.social.post_deleted'));
    }

    public function retryPost(SocialPost $post)
    {
        if (! $post->isFailed()) {
            return back();
        }

        $post->update(['status' => 'pending_ai', 'error_message' => null, 'ai_content' => null]);
        GeneratePostContentJob::dispatch($post->id);

        return back()->with('status', __('app.admin.products.social.post_retried'));
    }

    // --- Settings ---

    public function settings()
    {
        $pageIdSet   = (bool) Setting::where('key', 'social.facebook_page_id')->value('value');
        $tokenSet    = (bool) Setting::where('key', 'social.facebook_page_token')->value('value');
        $anthropicSet = (bool) Setting::where('key', 'social.anthropic_key')->value('value');

        $pageId = Setting::where('key', 'social.facebook_page_id')->value('value') ?? '';

        return view('admin.products.social.settings', compact('pageIdSet', 'tokenSet', 'anthropicSet', 'pageId'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'facebook_page_id'    => 'nullable|string|max:50',
            'facebook_page_token' => 'nullable|string|max:1000',
            'anthropic_key'       => 'nullable|string|max:200',
        ]);

        if (! empty($data['facebook_page_id'])) {
            Setting::updateOrCreate(
                ['key' => 'social.facebook_page_id'],
                ['value' => trim($data['facebook_page_id'])]
            );
        }

        if (! empty($data['facebook_page_token'])) {
            Setting::updateOrCreate(
                ['key' => 'social.facebook_page_token'],
                ['value' => Crypt::encryptString(trim($data['facebook_page_token']))]
            );
        }

        if (! empty($data['anthropic_key'])) {
            Setting::updateOrCreate(
                ['key' => 'social.anthropic_key'],
                ['value' => Crypt::encryptString(trim($data['anthropic_key']))]
            );
        }

        AuditLog::record('social.settings.update', null, 'Social media settings updated');

        return back()->with('status', __('app.admin.products.social.settings_saved'));
    }

    public function fetchNow()
    {
        Artisan::call('social:fetch-feeds');
        AuditLog::record('social.fetch.manual', null, 'Manual feed fetch triggered');

        return back()->with('status', __('app.admin.products.social.fetch_queued'));
    }
}
