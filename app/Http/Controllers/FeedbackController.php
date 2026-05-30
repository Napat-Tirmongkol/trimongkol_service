<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\CurrentWorkspace;
use Illuminate\Http\Request;
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
        ]);

        $user = $request->user();
        $workspace = CurrentWorkspace::get($user);

        Lead::create([
            'name' => $user->name,
            'email' => $user->email,
            'message' => $data['message'],
            'source' => Lead::SOURCE_FEEDBACK,
            'company' => $workspace?->name,
            'context' => [
                'subject' => $data['subject'],
                'category' => $data['category'] ?? null,
                'page_url' => $data['page_url'] ?? $request->headers->get('referer'),
                'user_id' => $user->id,
                'workspace_id' => $workspace?->id,
                'workspace_name' => $workspace?->name,
                'locale' => app()->getLocale(),
            ],
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return back()->with('status', __('app.feedback.thanks'));
    }
}
