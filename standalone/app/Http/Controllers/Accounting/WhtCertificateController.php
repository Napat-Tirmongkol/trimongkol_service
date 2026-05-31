<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\WhtCertificate;

class WhtCertificateController extends AccountingController
{
    public function index()
    {
        $workspace = $this->currentWorkspace();
        $certificates = $workspace
            ? WhtCertificate::forWorkspace($workspace)->with('partner')
                ->latest('paid_on')->latest('id')->paginate(30)
            : collect();

        return view('accounting.wht-certificates.index', compact('workspace', 'certificates'));
    }

    public function print(WhtCertificate $certificate)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($certificate->workspace_id === $workspace->id, 404);
        $certificate->load('partner');

        return view('accounting.print.wht-certificate', compact('certificate'));
    }
}
