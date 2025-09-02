<?php

namespace Canvastack\Table\Tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\FeatureFlag;
use PHPUnit\Framework\TestCase;

class FeatureFlagTest extends TestCase
{
    public function test_pipeline_enabled_default_off_when_config_missing()
    {
        // Without Laravel config helper, fallback env should be false by default
        if (! function_exists('config')) {
            putenv('CANVASTACK_DT_ENABLED');
            $this->assertFalse(FeatureFlag::pipelineEnabled());

            return;
        }
        try {
            config()->set('canvastack.datatables.pipeline_enabled', null);
        } catch (\Throwable $e) {
        }
        putenv('CANVASTACK_DT_ENABLED');
        $this->assertFalse(FeatureFlag::pipelineEnabled());
    }

    public function test_mode_default_legacy_when_config_missing()
    {
        // If Laravel app or env sets a mode, skip deterministic default assertion
        if (function_exists('app')) {
            try {
                $app = app();
                if ($app && method_exists($app, 'bound') && $app->bound('config')) {
                    $current = config('canvastack.datatables.mode');
                    if ($current !== null) {
                        $this->markTestSkipped('Project config/env defines mode='.$current);
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        if (getenv('CANVASTACK_DT_MODE')) {
            $this->markTestSkipped('Env CANVASTACK_DT_MODE is set.');
        }
        // No env/config -> should default to legacy
        putenv('CANVASTACK_DT_MODE');
        $this->assertSame('legacy', FeatureFlag::mode());
    }
}
