<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LanguageController extends Controller
{
    private const ALLOWED_LOCALES = ['es', 'en', 'fr', 'pt', 'de', 'it', 'zh_CN'];

    public function change(Request $request)
    {
        $locale = $request->input('locale');

        if (in_array($locale, self::ALLOWED_LOCALES, true)) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    }
}
