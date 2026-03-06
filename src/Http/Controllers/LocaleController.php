<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Controllers;

use Canvastack\Canvastack\Components\Table\Support\LocaleIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LocaleController - Handles locale switching requests.
 *
 * Provides endpoints for switching locale without page reload.
 * Persists locale preference via UserPreferences.
 *
 * @package Canvastack\Canvastack\Http\Controllers
 * @version 1.0.0
 */
class LocaleController
{
    /**
     * Locale integration instance.
     */
    protected LocaleIntegration $localeIntegration;

    /**
     * Constructor.
     */
    public function __construct(LocaleIntegration $localeIntegration)
    {
        $this->localeIntegration = $localeIntegration;
    }

    /**
     * Switch locale.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function switch(Request $request): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'locale' => 'required|string|size:2',
        ]);

        $locale = $validated['locale'];

        // Check if locale is available
        if (!$this->localeIntegration->isAvailable($locale)) {
            return response()->json([
                'success' => false,
                'message' => __('components.table.locale_switcher.invalid_locale'),
                'errors' => [
                    'locale' => [__('components.table.locale_switcher.locale_not_available')],
                ],
            ], 422);
        }

        // Switch locale and persist preference
        $result = $this->localeIntegration->setLocale($locale);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => __('components.table.locale_switcher.switch_failed'),
            ], 500);
        }

        // Get locale information
        $localeInfo = $this->localeIntegration->getLocaleInfo($locale);

        // Return success response with locale data
        return response()->json([
            'success' => true,
            'message' => __('components.table.locale_switcher.switch_success', [
                'locale' => $localeInfo['native'] ?? $locale,
            ]),
            'data' => [
                'locale' => $locale,
                'direction' => $this->localeIntegration->getDirection($locale),
                'isRtl' => $this->localeIntegration->isRtl($locale),
                'localeInfo' => $localeInfo,
            ],
            'translations' => $this->getTranslationsForLocale($locale),
        ]);
    }

    /**
     * Get current locale information.
     *
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        $locale = $this->localeIntegration->getLocale();
        $localeInfo = $this->localeIntegration->getLocaleInfo($locale);

        return response()->json([
            'success' => true,
            'data' => [
                'locale' => $locale,
                'direction' => $this->localeIntegration->getDirection(),
                'isRtl' => $this->localeIntegration->isRtl(),
                'localeInfo' => $localeInfo,
                'availableLocales' => $this->localeIntegration->getAvailableLocales(),
            ],
        ]);
    }

    /**
     * Get available locales.
     *
     * @return JsonResponse
     */
    public function available(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'locales' => $this->localeIntegration->getAvailableLocales(),
                'current' => $this->localeIntegration->getLocale(),
            ],
        ]);
    }

    /**
     * Get translations for locale.
     *
     * Returns common translations needed for table components.
     *
     * @param  string  $locale
     * @return array
     */
    protected function getTranslationsForLocale(string $locale): array
    {
        // Set locale temporarily to get translations
        $currentLocale = app()->getLocale();
        app()->setLocale($locale);

        $translations = [
            // Table translations
            'table' => [
                'search' => __('components.table.search'),
                'search_in' => __('components.table.search_in'),
                'clear_search' => __('components.table.clear_search'),
                'showing' => __('components.table.showing'),
                'first' => __('components.table.first'),
                'previous' => __('components.table.previous'),
                'next' => __('components.table.next'),
                'last' => __('components.table.last'),
                'page_size' => __('components.table.page_size'),
                'sort_asc' => __('components.table.sort_asc'),
                'sort_desc' => __('components.table.sort_desc'),
                'unsorted' => __('components.table.unsorted'),
                'filters' => __('components.table.filters'),
                'active_filters' => __('components.table.active_filters'),
                'clear_filters' => __('components.table.clear_filters'),
                'apply_filters' => __('components.table.apply_filters'),
                'filter_by' => __('components.table.filter_by'),
                'select_all' => __('components.table.select_all'),
                'deselect_all' => __('components.table.deselect_all'),
                'selected_count' => __('components.table.selected_count'),
                'actions' => __('components.table.actions'),
                'view' => __('components.table.view'),
                'edit' => __('components.table.edit'),
                'delete' => __('components.table.delete'),
                'delete_confirm' => __('components.table.delete_confirm'),
                'bulk_actions' => __('components.table.bulk_actions'),
                'export' => __('components.table.export'),
                'export_excel' => __('components.table.export_excel'),
                'export_csv' => __('components.table.export_csv'),
                'export_pdf' => __('components.table.export_pdf'),
                'print' => __('components.table.print'),
                'loading' => __('components.table.loading'),
                'no_data' => __('components.table.no_data'),
                'error' => __('components.table.error'),
                'retry' => __('components.table.retry'),
                'empty_title' => __('components.table.empty_title'),
                'empty_description' => __('components.table.empty_description'),
                'show_columns' => __('components.table.show_columns'),
                'hide_columns' => __('components.table.hide_columns'),
                'refresh' => __('components.table.refresh'),
                'reset' => __('components.table.reset'),
            ],

            // Button translations
            'buttons' => [
                'save' => __('ui.buttons.save'),
                'cancel' => __('ui.buttons.cancel'),
                'close' => __('ui.buttons.close'),
                'ok' => __('ui.buttons.ok'),
            ],

            // Message translations
            'messages' => [
                'success' => __('ui.messages.success'),
                'error' => __('ui.messages.error'),
                'confirm' => __('ui.messages.confirm'),
            ],
        ];

        // Restore original locale
        app()->setLocale($currentLocale);

        return $translations;
    }
}
