<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Attachment;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Services\Accounting\AccountingAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends AccountingController
{
    public function store(Request $request, string $type, int $id)
    {
        $workspace = $this->requireWorkspace();

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        // Resolve subject
        $subject = match ($type) {
            'invoice' => Invoice::find($id),
            'bill'    => Bill::find($id),
            default   => null,
        };

        abort_unless($subject && $subject->workspace_id === $workspace->id, 404);

        $uploaded = $request->file('file');
        $path = $uploaded->store("accounting/{$workspace->id}", 'local');

        $attachment = Attachment::create([
            'workspace_id'      => $workspace->id,
            'attachable_type'   => class_basename($subject),
            'attachable_id'     => $subject->id,
            'disk'              => 'local',
            'path'              => $path,
            'original_name'     => $uploaded->getClientOriginalName(),
            'mime_type'         => $uploaded->getMimeType(),
            'size'              => $uploaded->getSize(),
            'accounting_user_id' => auth('accounting')->id(),
        ]);

        AccountingAuditLog::record($workspace, 'attachment.uploaded', $subject, $subject->no);

        return back()->with('status', __('app.accounting.attachment_uploaded'));
    }

    public function download(Attachment $attachment)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($attachment->workspace_id === $workspace->id, 404);

        return response()->download(
            storage_path('app/'.$attachment->path),
            $attachment->original_name
        );
    }

    public function destroy(Attachment $attachment)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($attachment->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return back()->with('status', __('app.accounting.attachment_deleted'));
    }
}
