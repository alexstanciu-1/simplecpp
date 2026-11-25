<?php
declare(strict_types=1);

namespace runtime_preparation;

require_once __DIR__ . '/bootstrap.php';

/** Standalone CLI; does not start an application compilation. */
final class Command
{
    /**
     * Prepare the configured package and report its manifest, or a diagnostic with a failing exit status.
     * @param list<string> $arguments
     */
    public static function run(array $arguments): int
    {
        if ($arguments === ['--help']) {
            fwrite(STDOUT, "Usage: php main.php [--config path/to/config.json]\n");
            return 0;
        }
        try
        {
            if (($arguments !== []) && ((count($arguments) !== 2) || ($arguments[0] !== '--config'))) {
                throw new \RuntimeException('Expected --config PATH or no arguments');
            }
            $result = (new Runtime_Preparation())->run($arguments[1] ?? __DIR__ . '/config.json');
            fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
            return 0;
        }
        catch (\Throwable $error) {
            fwrite(STDERR, 'Runtime preparation failed: ' . $error->getMessage() . "\n");
            return 1;
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(Command::run(array_slice($argv, 1)));
}
