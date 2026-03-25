<?php
declare(strict_types=1);

final class Counter
{
    public static int $d = 0;

    public function __destruct()
    {
        self::$d++;
    }
}

function run(): void
{
    $a = new Counter();
    $b = new Counter();

    unset($a);
    unset($b);

    echo Counter::$d, "\n";
}

run();
