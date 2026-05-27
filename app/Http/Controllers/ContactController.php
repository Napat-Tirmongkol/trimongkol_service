<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'phone' => 'nullable|string|max:40',
            'company' => 'nullable|string|max:160',
            'message' => 'required|string|max:2000',
        ]);

        return redirect()
            ->route('contact')
            ->with('contact_success', true)
            ->withInput($data);
    }
}
