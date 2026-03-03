<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Providers;

use Illuminate\Support\ServiceProvider;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Chart\ChartBuilder;

/**
 * CompatibilityServiceProvider.
 *
 * Provides backward compatibility with CanvaStack Origin API.
 * Registers facades and bindings for old API calls.
 */
class CompatibilityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register compatibility layer
        $this->app->singleton('canvastack.compatibility', function ($app) {
            return new \Canvastack\Canvastack\Support\Compatibility\OriginCompatibility();
        });

        // Register form builder for facade
        $this->app->singleton('canvastack.form', function ($app) {
            $form = $app->make(FormBuilder::class);
            $form->setContext('admin'); // Default context
            
            return $form;
        });

        // Register table builder for facade
        $this->app->singleton('canvastack.table', function ($app) {
            $table = $app->make(TableBuilder::class);
            $table->setContext('admin'); // Default context
            
            return $table;
        });

        // Register chart builder for facade
        $this->app->singleton('canvastack.chart', function ($app) {
            $chart = $app->make(ChartBuilder::class);
            $chart->setContext('admin'); // Default context
            
            return $chart;
        });

        // Register old namespace aliases for backward compatibility
        $this->registerNamespaceAliases();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register facade aliases
        $this->registerFacadeAliases();
    }

    /**
     * Register namespace aliases for backward compatibility.
     *
     * Maps old CanvaStack Origin namespaces to new ones.
     *
     * @return void
     */
    protected function registerNamespaceAliases(): void
    {
        // Old namespace: Canvastack\Origin\Library\Components\Form
        // New namespace: Canvastack\Canvastack\Components\Form\FormBuilder
        
        $aliases = [
            // Form aliases
            'Canvastack\\Origin\\Library\\Components\\Form' => FormBuilder::class,
            
            // Table aliases
            'Canvastack\\Origin\\Library\\Components\\Datatables' => TableBuilder::class,
            'Canvastack\\Origin\\Library\\Components\\Table' => TableBuilder::class,
            
            // Chart aliases
            'Canvastack\\Origin\\Library\\Components\\Chart' => ChartBuilder::class,
        ];

        foreach ($aliases as $alias => $original) {
            if (!class_exists($alias)) {
                class_alias($original, $alias);
            }
        }
    }

    /**
     * Register facade aliases.
     *
     * @return void
     */
    protected function registerFacadeAliases(): void
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();

        // Register facade aliases
        $loader->alias('CanvastackForm', \Canvastack\Canvastack\Support\Compatibility\Facades\Form::class);
        $loader->alias('CanvastackTable', \Canvastack\Canvastack\Support\Compatibility\Facades\Table::class);
        $loader->alias('CanvastackChart', \Canvastack\Canvastack\Support\Compatibility\Facades\Chart::class);
    }
}
