<?php
namespace Canvastack\Origin;

use Illuminate\Support\ServiceProvider;
use Canvastack\Origin\Controllers\Core\Controller as CanvaStack;

/**
 * Created on Mar 22, 2018
 * Time Created : 4:52:52 PM
 * Filename :  Canvastack\CanvastackServiceProvider.php
 *
 * @filesource Canvastack\CanvastackServiceProvider.php
 *            
 * @author    wisnuwidi@canvastack.com - 2018
 * @copyright wisnuwidi
 * @email     wisnuwidi@canvastack.com
 */
class CanvastackServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot() {
		if ($this->app->routesAreCached()) {
			require_once __DIR__ . '/routes/web.php';
		}

		$this->loadViewsFrom(base_path('resources/views'), 'CanvaStack');
		$publish_path = __DIR__ . '/Publisher/';
		
		if ($this->app->runningInConsole()) {
			$this->publishes([ 
				"{$publish_path}database/migrations" => database_path('migrations'),
				"{$publish_path}database/seeders"    => database_path('seeders'),
				"{$publish_path}config"              => base_path('config'),
				"{$publish_path}routes"              => base_path('routes'),
				"{$publish_path}app"                 => base_path('app'),
				"{$publish_path}resources/views"     => base_path('resources/views')
			], 'CanvaStack');
			
			$this->publishes([ 
				"{$publish_path}public" => base_path('public')
			], 'CanvaStack Public Folder');
		}
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton(CanvaStack::class, function ($app) {
			return new CanvaStack();
		});
	}
}
