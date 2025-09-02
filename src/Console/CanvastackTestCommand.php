<?php

namespace Canvastack\Canvastack\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CanvastackTestCommand extends Command
{
    protected $signature = 'canvastack:test {file? : Target test filename or class (without path)} {--filter= : PHPUnit filter pattern} {--suite= : Test suite name}';

    protected $description = 'Run CanvaStack Table tests located under package tests folder';

    public function handle(): int
    {
        $base = base_path();
        $packageTestsPath = $base.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'canvastack'.DIRECTORY_SEPARATOR.'canvastack'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Library'.DIRECTORY_SEPARATOR.'Components'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR.'tests';

        if (! is_dir($packageTestsPath)) {
            $this->error('Package tests folder not found: '.$packageTestsPath);

            return self::FAILURE;
        }

        $phpunit = $base.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'phpunit';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $phpunit .= '.bat';
        }

        $args = [$phpunit, '--configuration', $packageTestsPath.DIRECTORY_SEPARATOR.'phpunit.xml'];

        // Optional: target file/class filter
        if ($target = $this->argument('file')) {
            // If a simple name is given, pass as --filter
            if (! preg_match('/[\\\\\/]/', $target)) {
                $args[] = '--filter';
                $args[] = $target;
            } else {
                // If a path was given, run that path
                $args[] = $target;
            }
        }

        if ($filter = $this->option('filter')) {
            $args[] = '--filter';
            $args[] = $filter;
        }

        if ($suite = $this->option('suite')) {
            $args[] = '--testsuite';
            $args[] = $suite;
        }

        $this->info('Running: '.implode(' ', array_map(function ($s) {
            return escapeshellarg($s);
        }, $args)));

        $process = new Process($args, $base, [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
        ]);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }
}
