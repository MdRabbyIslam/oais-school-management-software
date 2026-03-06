<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Exception;

class ConsoleHelper
{
    /**
     * Execute a raw shell command in the background.
     * Cross-platform (Windows vs. Unix).
     */
    public static function execInBackground(string $cmd): void
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            // Windows
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            // Unix/Linux/macOS
            exec($cmd . " > /dev/null 2>&1 &");
        }
    }

    /**
     * Find the PHP binary path, prepend the `artisan` path if requested,
     * then exec the given Artisan subcommand in the background.
     *
     * Example: ConsoleHelper::foundPhpAndExecInBackground('invoices:generate --force', true);
     *
     * @param  string  $artisanSubcommand   The part after "php artisan", e.g. "invoices:generate --force"
     * @param  bool    $prependArtisanPath  If true, we prefix with base_path('artisan')
     * @throws \Exception
     */
    public static function foundPhpAndExecInBackground(string $artisanSubcommand, bool $prependArtisanPath = false): void
    {
        $phpPath = self::foundPhpExecutableDynamically();

        if (! $phpPath) {
            throw new Exception("PHP executable not found. Please set a valid PHP_PATH in your .env or ensure `which php` succeeds.");
        }

        $artisanPathPart = $prependArtisanPath
            ? escapeshellarg(base_path('artisan'))
            : '';

        // Build:  /usr/bin/php  /full/project/path/artisan  invoices:generate --force
        $cmd = implode(' ', array_filter([
            escapeshellcmd($phpPath),
            $artisanPathPart,
            $artisanSubcommand,
        ]));

        self::execInBackground($cmd);
    }

    /**
     * Find the PHP binary path, prepend the `artisan` path if requested,
     * then exec the given Artisan subcommand synchronously.
     *
     * Returns the first line of output, or null if exit code ≠ 0 or no output.
     *
     * Example: ConsoleHelper::foundPhpAndExec('invoices:generate --force', true);
     *
     * @param  string  $artisanSubcommand
     * @param  bool    $prependArtisanPath
     * @return string|null
     * @throws \Exception
     */
    public static function foundPhpAndExec(string $artisanSubcommand, bool $prependArtisanPath = false): ?string
    {
        $phpPath = self::foundPhpExecutableDynamically();
        if (! $phpPath) {
            throw new Exception("PHP executable not found. Please set a valid PHP_PATH in your .env or ensure `which php` succeeds.");
        }

        $artisanPathPart = $prependArtisanPath
            ? escapeshellarg(base_path('artisan'))
            : '';

        $cmd = implode(' ', array_filter([
            escapeshellcmd($phpPath),
            $artisanPathPart,
            $artisanSubcommand,
        ]));

        exec($cmd, $output, $returnVar);

        if ($returnVar === 0 && ! empty($output)) {
            return $output[0];
        }

        return null;
    }

    /**
     * Try various methods to find a usable `php` binary path.
     * Returns the first valid path whose `php -v` contains a version ≥ 7.4 (for example).
     */
    public static function foundPhpExecutableDynamically(): ?string
    {
        $pathsToCheck = [];

        // // 1) $_SERVER['PHP_BINARY']
        // if (! empty($_SERVER['PHP_BINARY'])) {
        //     $pathsToCheck[] = $_SERVER['PHP_BINARY'];
        // }

        // // 2) `whereis php` (Unix)
        // @exec('whereis php', $whereisOutput);
        // if (! empty($whereisOutput) && preg_match('/php:\s*(\S+)/', $whereisOutput[0], $m)) {
        //     $pathsToCheck[] = $m[1];
        // }

        // // 3) `which php` (Unix)
        // $whichPhp = trim((string) @shell_exec('which php'));
        // if (! empty($whichPhp)) {
        //     $pathsToCheck[] = $whichPhp;
        // }

        // // 4) `php-config --php-binary`
        // $phpConfig = trim((string) @shell_exec('php-config --php-binary'));
        // if (! empty($phpConfig)) {
        //     $pathsToCheck[] = $phpConfig;
        // }

        // 5) fallback from config/app.php_path (if present)
        $configPath = Config::get('app.php_path');

        if (! empty($configPath)) {
            $pathsToCheck[] = $configPath;
        }

        // 6) Deduplicate
        $pathsToCheck = array_unique($pathsToCheck);

        foreach ($pathsToCheck as $candidate) {
            if (self::isUsablePhpPath($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Given a candidate path, run `$candidate -v` to check if it is a working PHP and version ≥ 7.4.
     */
    protected static function isUsablePhpPath(string $path): bool
    {
        if (empty($path) || ! file_exists($path)) {
            return false;
        }

        // Try running `php -v` to see if it responds
        $output = @shell_exec(escapeshellarg($path) . ' -v 2>&1');
        if (stripos((string) $output, 'php') !== false) {
            // Extract version number (e.g. “PHP 8.0.12 …”)
            if (preg_match('/PHP\s+(\d+\.\d+\.\d+)/', $output, $m)) {
                $version = $m[1];
                // Require ≥ 7.4.0 (adjust if you need a different minimum)
                return version_compare($version, '7.4.0', '>=');
            }
        }

        return false;
    }
}
