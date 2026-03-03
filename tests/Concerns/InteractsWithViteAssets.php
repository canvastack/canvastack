<?php

namespace Canvastack\Canvastack\Tests\Concerns;

/**
 * Trait for managing Vite assets in tests.
 *
 * This trait provides helper methods for copying and managing Vite built assets
 * in the Orchestra Testbench environment.
 */
trait InteractsWithViteAssets
{
    /**
     * Copy Vite built assets to Orchestra Testbench public directory.
     *
     * Orchestra Testbench uses vendor/orchestra/testbench-core/laravel/public
     * as the public directory, not our package's public directory.
     *
     * This method copies our built assets to the testbench public directory
     * so Vite can find the manifest.json file.
     *
     * @return void
     */
    protected function copyViteAssets(): void
    {
        $sourceDir = $this->getPackagePublicPath() . '/build';
        $targetDir = $this->getTestbenchPublicPath() . '/build';

        // Skip if source doesn't exist (assets not built yet)
        if (!is_dir($sourceDir)) {
            return;
        }

        // Create target directory if it doesn't exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Copy the entire build directory recursively
        $this->recursiveCopy($sourceDir, $targetDir);

        // IMPORTANT: Vite 5+ stores manifest in .vite subdirectory,
        // but Laravel may still look for it in the root build directory.
        // Copy manifest to both locations for compatibility.
        $this->copyViteManifest($sourceDir, $targetDir);
    }

    /**
     * Copy Vite manifest to root build directory for compatibility.
     *
     * @param string $sourceDir Source build directory
     * @param string $targetDir Target build directory
     * @return void
     */
    protected function copyViteManifest(string $sourceDir, string $targetDir): void
    {
        $viteManifest = $sourceDir . '/.vite/manifest.json';
        $rootManifest = $targetDir . '/manifest.json';

        if (file_exists($viteManifest) && !file_exists($rootManifest)) {
            copy($viteManifest, $rootManifest);
        }
    }

    /**
     * Recursively copy directory contents.
     *
     * @param string $source Source directory
     * @param string $target Target directory
     * @return void
     */
    protected function recursiveCopy(string $source, string $target): void
    {
        $dir = opendir($source);

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $targetPath = $target . '/' . $file;

            if (is_dir($sourcePath)) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
                $this->recursiveCopy($sourcePath, $targetPath);
            } else {
                copy($sourcePath, $targetPath);
            }
        }

        closedir($dir);
    }

    /**
     * Get the package public directory path.
     *
     * @return string
     */
    protected function getPackagePublicPath(): string
    {
        return realpath(__DIR__ . '/../../public');
    }

    /**
     * Get the Orchestra Testbench public directory path.
     *
     * @return string
     */
    protected function getTestbenchPublicPath(): string
    {
        return realpath(__DIR__ . '/../../vendor/orchestra/testbench-core/laravel/public');
    }

    /**
     * Assert that Vite manifest exists.
     *
     * @return void
     */
    protected function assertViteManifestExists(): void
    {
        $manifestPath = $this->getTestbenchPublicPath() . '/build/manifest.json';

        $this->assertFileExists(
            $manifestPath,
            'Vite manifest.json not found. Run "npm run build" to generate assets.'
        );
    }

    /**
     * Assert that Vite assets are built.
     *
     * @return void
     */
    protected function assertViteAssetsBuilt(): void
    {
        $buildDir = $this->getPackagePublicPath() . '/build';

        $this->assertDirectoryExists(
            $buildDir,
            'Vite build directory not found. Run "npm run build" to generate assets.'
        );

        $this->assertViteManifestExists();
    }

    /**
     * Clean up Vite assets from testbench public directory.
     *
     * @return void
     */
    protected function cleanupViteAssets(): void
    {
        $targetDir = $this->getTestbenchPublicPath() . '/build';

        if (is_dir($targetDir)) {
            $this->recursiveDelete($targetDir);
        }
    }

    /**
     * Recursively delete directory contents.
     *
     * @param string $dir Directory to delete
     * @return void
     */
    protected function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
