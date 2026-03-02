<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

class NicknameGeneratorController extends Controller
{
    public function index()
    {
        return view('frontend.tools.nickname-generator', [
            'seo' => [
                'title'       => __('messages.ng_seo_title'),
                'description' => __('messages.ng_seo_description'),
                'canonical'   => route('tools.nickname-generator'),
            ],
        ]);
    }
}
