<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Undefined fields and other unexpected PHP warnings must fail these proofs.
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

require_once __DIR__ . '/step_support.php';
