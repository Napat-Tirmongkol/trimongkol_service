<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\CurrentWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * In-app bug / feedback submissions from signed-in users. Captures the
 * page they were on and their current workspace as context so the team
 * can reproduce without a back-and-forth.
 */
class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:4000',
            'category' => ['nullable', Rule::in(['bug', 'suggestion', 'question'])],
            'page_url' => 'nullable|string|max:500',
            // 5 MB cap leaves room for a phone screenshot without tripping the
            // default Plesk PHP post_max_size on most configs.
            'attachment' => 'nullable|image|mimes:jpeg,png,gif,webp|max:5120',
        ]);

        $user = $request->user();
        $workspace = CurrentWorkspace::get($user);

        $context = [
            'subject' => $data['subject'],
            'category' => $data['category'] ?? null,
            'page_url' => $data['page_url'] ?? $request->headers->get('referer'),
            'user_id' => $user->id,
            'workspace_id' => $workspace?->id,
            'workspace_name' => $workspace?->name,
            'locale' => app()->getLocale(),
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $ext = $file->getClientOriginalExtension() ?: $file->extension();
            $filename = (string) Str::uuid() . ($ext ? '.' . $ext : '');
            // Private disk — served back through an admin-only route, not
            // public/storage, so feedback screenshots never leak to the web.
            $path = $file->storeAs('feedback', $filename, 'local');

            $context['attachment_path'] = $path;
            $context['attachment_mime'] = $file->getMimeType();
            $context['attachment_size'] = $file->getSize();
            $context['attachment_original'] = $file->getClientOriginalName();
        }

        Lead::create([
            'name' => $user->name,
            'email' => $user->email,
            'message' => $data['message'],
            'source' => Lead::SOURCE_FEEDBACK,
            'company' => $workspace?->name,
            'context' => $context,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return back()->with('status', __('app.feedback.thanks'));
    }
}
