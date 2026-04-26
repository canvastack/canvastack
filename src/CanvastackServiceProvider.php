<?php
namespace Canvastack\Canvastack;

use Illuminate\Support\ServiceProvider;
use Canvastack\Canvastack\Controllers\Core\Controller as CanvaStack;

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
		// Load routes only if NOT cached
		// When routes are cached, Laravel uses bootstrap/cache/routes-v7.php instead
		if (!$this->app->routesAreCached()) {
			// Routes are defined in app's routes/web.php, not in package
			// No need to load routes here as they're loaded by RouteServiceProvider
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
			
			// Phase 4: Register console commands
			$this->commands([
				\Canvastack\Canvastack\Console\Commands\WarmTableCache::class,
			]);
		}
		
		// Phase 4: Cache Warming - Boot warming
		if (config('canvastack.cache.warming.on_boot', false)) {
			$this->warmCacheOnBoot();
		}
		
		// Phase 4: Cache Warming - Scheduled warming
		if (config('canvastack.cache.warming.scheduled', false)) {
			$this->registerScheduledWarming();
		}
		
		// Load SMTP configuration from preference
		$this->loadMailConfiguration();
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
		
		// Register Mail Configuration Service
		$this->app->singleton(\Canvastack\Canvastack\Services\MailConfigService::class, function ($app) {
			return new \Canvastack\Canvastack\Services\MailConfigService();
		});
	}
	
	/**
	 * Warm cache on application boot
	 * 
	 * Phase 4: Cache Warming - Boot warming
	 * Warms cache asynchronously to avoid blocking application boot.
	 * Only runs in production environment.
	 * 
	 * @return void
	 */
	protected function warmCacheOnBoot(): void
	{
		// Only warm in production to avoid slowing down development
		if (!$this->app->environment('production')) {
			return;
		}
		
		$tables = config('canvastack.cache.warming.tables', []);
		
		if (empty($tables)) {
			return;
		}
		
		// Warm cache asynchronously to avoid blocking boot
		dispatch(function () use ($tables) {
			foreach ($tables as $table) {
				try {
					canvastack_table_get_cached_schema($table);
				} catch (\Exception $e) {
					\Log::warning('Cache warming failed on boot', [
						'table' => $table,
						'error' => $e->getMessage(),
					]);
				}
			}
		})->afterResponse();
	}
	
	/**
	 * Register scheduled cache warming
	 * 
	 * Phase 4: Cache Warming - Scheduled warming
	 * Registers the cache warming command to run on a schedule.
	 * 
	 * Note: This requires the Laravel scheduler to be configured in crontab:
	 * * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
	 * 
	 * @return void
	 */
	protected function registerScheduledWarming(): void
	{
		// Register callback for scheduler
		$this->app->booted(function () {
			$schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
			
			$cronExpression = config('canvastack.cache.warming.schedule', '0 */6 * * *');
			$schedule->command('canvastack:warm-cache')
			         ->cron($cronExpression)
			         ->withoutOverlapping()
			         ->runInBackground();
		});
	}
	
	/**
	 * Load Mail Configuration from Preference
	 * 
	 * Loads SMTP settings from database preferences and applies them
	 * to Laravel's mail configuration at runtime.
	 * 
	 * @return void
	 */
	protected function loadMailConfiguration(): void
	{
		// Only load in web context, not in console (to avoid DB issues during migrations)
		if ($this->app->runningInConsole()) {
			return;
		}
		
		try {
			// Load SMTP configuration from preference
			$mailService = $this->app->make(\Canvastack\Canvastack\Services\MailConfigService::class);
			$mailService->loadSmtpFromPreference();
		} catch (\Exception $e) {
			// Log error but don't break application boot
			\Log::warning('Failed to load mail configuration from preference', [
				'error' => $e->getMessage(),
			]);
		}
	}
}
