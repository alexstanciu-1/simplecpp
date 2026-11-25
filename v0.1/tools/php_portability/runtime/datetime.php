<?php
declare(strict_types=1);
namespace scpp;

/** Scheduler precision is host-dependent; nonpositive waits return immediately. */
function dt_sleep_ms(int $millis): void {
    if ($millis <= 0) { return; }
    $seconds = intdiv($millis, 1000);
    $nanoseconds = ($millis % 1000) * 1000000;
    do {
        $remaining = time_nanosleep($seconds, $nanoseconds);
        if ($remaining === false) { throw new \RuntimeException('Cannot suspend the current thread'); }
        if ($remaining === true) { return; }
        $seconds = $remaining['seconds'];
        $nanoseconds = $remaining['nanoseconds'];
    } while (true);
}
