<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\Language;
use App\Model\LanguageText;
use Illuminate\Http\Response;

class LanguageController extends Controller {

    public function getLanguageText(Request $request, $languageId, $module) {
        $headers = $request->header();
        $accessToken = getenv('accessToken');
        $default_language_id = 1;
        $languageText = LanguageText::where('language_id', $languageId)->where('module', $module)->select('id', 'language_id', 'module', 'label', 'sysname')->get();
        if ($languageText->isEmpty())
            $languageText = LanguageText::where('language_id', $default_language_id)->where('module', $module)->select('id', 'language_id', 'module', 'label', 'sysname')->get();

        if ($languageText) {

            $languageGlobalText = LanguageText::where('language_id', $languageId)->where('module', 'global')->select('id', 'language_id', 'module', 'label', 'sysname')->get();
            if ($languageGlobalText->isEmpty()) {
                $languageId = $default_language_id;
                $languageGlobalText = LanguageText::where('language_id', $default_language_id)->where('module', 'global')->select('id', 'language_id', 'module', 'label', 'sysname')->get();
            }
            $langArray = [];
            foreach ($languageText as $text) {
                $langArray[$text->sysname] = $text->label;
            }
            foreach ($languageGlobalText as $globaltext) {
                $langArray[$globaltext->sysname] = $globaltext->label;
            }

            $langArray['language'] = Language::find($languageId);
            return $this->sendResponse(200, 'record_found', $langArray);
        } else {
            return $this->sendResponse(204, 'record_not_found');
        }
    }

}
