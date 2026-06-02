<?php

namespace App\Http\Controllers;

use App\Models\Lead;
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
            'captcha' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $expected = session('contact_captcha_answer');
                    if ($expected === null || (int)$value !== (int)$expected) {
                        $fail(trans('site.contact.captchaInvalid'));
                    }
                },
            ],
        ]);

        session()->forget(['contact_captcha_answer', 'contact_captcha_question']);

        unset($data['captcha']);

        Lead::create([
            ...$data,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return redirect()
            ->route('contact')
            ->with('contact_success', true)
            ->withInput($data);
    }
}
