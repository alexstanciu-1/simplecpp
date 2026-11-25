<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

class Project_Lock_Probe
{
    public static function run(array $arguments): void
    {
        $manifest = $arguments[2];
        if ($arguments[1] === 'hold')
        {
            $lock = \compile\Project_Lock::acquire($manifest);
            try
            {
                $session = new \compile\Compiler_Session($lock);
                $session->compile($manifest);
                if (file_put_contents($arguments[3], 'ready') !== 5) {
                    throw new Exception('Cannot signal lock readiness');
                }
                fgets(STDIN); // Test-only wait; the parent releases or kills us.
            }
            finally {
                $lock->release();
            }
            return;
        }
        if ($arguments[1] !== 'compile') {
            throw new Exception('Unknown probe mode');
        }
        $session = new \compile\Compiler_Session();
        try {
            $session->compile($manifest);
        }
        catch (Throwable $exception) {
            if (($session->observed?->inputs !== null) || ($session->generation !== 0)) {
                throw new Exception('Rejected compile changed session state', 0, $exception);
            }
            throw $exception;
        }
    }
}

try {
    Project_Lock_Probe::run($argv);
}
catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
