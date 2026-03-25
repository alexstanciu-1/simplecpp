<?php
declare(strict_types=1);

final class A {}

function run(): void
{
    $a = new A();
    $b = $a;

    if ($a === $b) {
        echo "same\n";
    }
}

run();
