<?php
declare(strict_types=1);

$bytes = stream_get_contents(STDIN);
$expected = json_decode(file_get_contents('expected.json'), true, flags: JSON_THROW_ON_ERROR);
if (json_decode($bytes, true, flags: JSON_THROW_ON_ERROR)['value'] !== $expected) {
    throw new RuntimeException('decoded value mismatch');
}

function measure(string $bytes, int $loops, int $length): float {
    $sum = 0;
    $start = hrtime(true);
    for ($i = 0; $i < $loops; ++$i) {
        $sum += count(json_decode($bytes, true, flags: JSON_THROW_ON_ERROR)['value']);
    }
    $elapsed = hrtime(true) - $start;
    if ($sum !== $length * $loops) throw new RuntimeException('length mismatch');
    return $elapsed / $loops;
}

$loops = (int)$argv[1];
measure($bytes, max(1, $loops >> 3), count($expected));
echo measure($bytes, $loops, count($expected)), PHP_EOL;
