<?php
[$count, $length, $loops] = array_map('intval', array_slice($argv, 1));
$wire = stream_get_contents(STDIN);
$half = intdiv($length, 2);
$word = str_repeat('a', $half) . 'b' . str_repeat('z', $length - $half - 1);
if (json_decode($wire, true, 512, JSON_THROW_ON_ERROR)['value'] !== array_fill(0, $count, $word)) exit(1);
foreach ([max(1, intdiv($loops, 8)), $loops] as $k) {
    $total = 0;
    $start = hrtime(true);
    for ($i = 0; $i < $k; ++$i) $total += count(json_decode($wire, true, 512, JSON_THROW_ON_ERROR)['value']);
    $elapsed = hrtime(true) - $start;
    if ($total !== $k * $count) exit(2);
    if ($k === $loops) echo $elapsed / $k, "\n";
}
