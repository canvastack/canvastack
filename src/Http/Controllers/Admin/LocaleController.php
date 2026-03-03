<?php

namespace Canvastack\Canvastack\Http\Controllers\Admin;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Admin LocaleController.
 *
 * Manages locale configuration in the admin panel.
 */
class LocaleController extends Controller
{
    /**
     * Display locale management page.
     *
     * @param  \Canvastack\Canvastack\Components\Table\TableBuilder  $table
     * @param  \Canvastack\Canvastack\Library\Components\MetaTags  $meta
     * @return \Illuminate\View\View
     */
    public function index(TableBuilder $table, MetaTags $meta): View
    {
        $meta->title(__('ui.labels.language') . ' ' . __('ui.navigation.settings'));
        $meta->description('Manage application locales and language settings');

        $localeManager = app('canvastack.locale');
        $availableLocales = $localeManager->getAvailableLocales();
        $currentLocale = $localeManager->getLocale();

        // Prepare data for table
        $localesData = collect($availableLocales)->map(function ($info, $code) use ($currentLocale) {
            return [
                'code' => $code,
                'name' => $info['name'] ?? $code,
                'native' => $info['native'] ?? $info['name'] ?? $code,
                'flag' => $info['flag'] ?? '',
                'direction' => $info['direction'] ?? 'ltr',
                'is_active' => $code === $currentLocale,
            ];
        })->values()->toArray();

        $table->setContext('admin');
        $table->setData($localesData);
        $table->setFields([
            'flag:Flag',
            'code:Code',
            'name:Name',
            'native:Native Name',
            'direction:Direction',
            'is_active:Active',
        ]);

        // Custom column renderers
        $table->setColumnRenderer('flag', function ($row) {
            return '<span class="text-2xl">' . ($row['flag'] ?? '') . '</span>';
        });

        $table->setColumnRenderer('code', function ($row) {
            return '<span class="font-mono font-semibold text-indigo-600 dark:text-indigo-400">' . strtoupper($row['code']) . '</span>';
        });

        $table->setColumnRenderer('direction', function ($row) {
            $direction = $row['direction'] ?? 'ltr';
            $badge = $direction === 'rtl' ? 'badge-warning' : 'badge-info';

            return '<span class="badge ' . $badge . '">' . strtoupper($direction) . '</span>';
        });

        $table->setColumnRenderer('is_active', function ($row) {
            if ($row['is_active']) {
                return '<span class="badge badge-success"><i data-lucide="check" class="w-3 h-3 inline"></i> Active</span>';
            }

            return '<span class="badge badge-ghost">Inactive</span>';
        });

        $table->format();

        return view('canvastack::admin.locales.index', [
            'table' => $table,
            'meta' => $meta,
            'localeManager' => $localeManager,
            'currentLocale' => $currentLocale,
            'availableLocales' => $availableLocales,
        ]);
    }
}
