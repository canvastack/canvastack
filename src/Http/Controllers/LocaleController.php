<?php

namespace Canvastack\Canvastack\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * LocaleController.
 *
 * Handles locale switching requests.
 */
class LocaleController extends Controller
{
    /**
     * Switch application locale.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => 'required|string|size:2',
        ]);

        $locale = $request->input('locale');
        $localeManager = app('canvastack.locale');

        if ($localeManager->isAvailable($locale)) {
            $localeManager->setLocale($locale);

            return redirect()->back()->with('success', __('ui.messages.locale_changed'));
        }

        return redirect()->back()->with('error', __('ui.messages.locale_not_available'));
    }
}
