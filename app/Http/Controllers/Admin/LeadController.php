<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Services\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $leads = Lead::query()
            ->with('assignee:id,name')
            ->when($status, fn ($qb) => $qb->where('status', $status))
            ->when($q !== '', fn ($qb) => $qb->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('company', 'like', "%{$q}%")
                  ->orWhere('message', 'like', "%{$q}%")
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = Lead::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        return view('admin.leads.index', compact('leads', 'status', 'q', 'counts'));
    }

    public function show(Lead $lead)
    {
        $lead->load('assignee');
        $admins = User::where('is_admin', true)->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.leads.show', compact('lead', 'admins'));
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', Lead::STATUSES),
            'assigned_to' => 'nullable|exists:users,id',
            'internal_notes' => 'nullable|string|max:5000',
        ]);

        $changes = [];
        if ($lead->status !== $data['status']) {
            $changes['status'] = ['from' => $lead->status, 'to' => $data['status']];
        }
        if ((int) $lead->assigned_to !== (int) ($data['assigned_to'] ?? 0)) {
            $changes['assigned_to'] = ['from' => $lead->assigned_to, 'to' => $data['assigned_to']];
        }

        $lead->fill($data);
        if ($lead->status !== 'new' && ! $lead->replied_at) {
            $lead->replied_at = now();
        }
        $lead->save();

        if ($changes) {
            AuditLog::record('lead.update', $lead, $lead->email, $changes);
        }

        return back()->with('status', __('app.admin.leads.updated'));
    }

    public function destroy(Lead $lead)
    {
        $label = $lead->email;
        // Drop the attachment when the lead goes too, so we don't leave
        // orphaned screenshots in storage/app/feedback.
        $path = data_get($lead->context, 'attachment_path');
        if ($path) {
            Storage::disk('local')->delete($path);
        }
        $lead->delete();
        AuditLog::record('lead.delete', null, $label);

        return redirect()->route('admin.leads.index')
            ->with('status', __('app.admin.leads.deleted'));
    }

    /**
     * Stream the attachment for a feedback lead. Lives behind leads.view
     * so screenshots that may contain student data never get a public URL.
     */
    public function attachment(Lead $lead)
    {
        $path = data_get($lead->context, 'attachment_path');
        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        $mime = data_get($lead->context, 'attachment_mime', 'application/octet-stream');
        $original = data_get($lead->context, 'attachment_original', basename($path));

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($original) . '"',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }
}
