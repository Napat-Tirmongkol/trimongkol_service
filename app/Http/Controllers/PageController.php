<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function home()
    {
        return view('pages.home');
    }

    public function services()
    {
        return view('pages.services');
    }

    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        $num1 = rand(1, 9);
        $num2 = rand(1, 9);
        $operators = ['+', '-'];
        $operator = $operators[array_rand($operators)];

        if ($operator === '-') {
            if ($num1 < $num2) {
                $temp = $num1;
                $num1 = $num2;
                $num2 = $temp;
            }
            $answer = $num1 - $num2;
        } else {
            $answer = $num1 + $num2;
        }

        $question = "{$num1} {$operator} {$num2}";
        session(['contact_captcha_answer' => $answer]);
        session(['contact_captcha_question' => $question]);

        return view('pages.contact');
    }

    public function donate()
    {
        return view('pages.donate');
    }

    public function ocr()
    {
        return view('pages.ocr');
    }

    public function privacyPolicy()
    {
        return view('pages.privacy-policy');
    }
}
