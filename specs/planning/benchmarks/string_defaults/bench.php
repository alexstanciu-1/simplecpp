<?php
// Standard host PHP; deliberately not PHS or portability input.
declare(strict_types=1);
function batch(string $op, string $s, string $t, string $needle, string $repl, int $count): array {
    $sum = 0;
    $start = hrtime(true);
    if ($op === 'replace') {
        for ($i=0; $i<$count; ++$i) $sum += strlen(str_replace($needle, $repl, $s));
    } elseif ($op === 'compare') {
        for ($i=0; $i<$count; ++$i) { $v = strcmp($s, $t); $sum += 1 + ($v > 0) - ($v < 0); }
    } else {
        for ($i=0; $i<$count; ++$i) $sum += strlen($s);
    }
    return [hrtime(true)-$start, $sum];
}
foreach (file($argv[1], FILE_IGNORE_NEW_LINES) as $line) {
    [$id,$op,$s,$t,$needle,$repl,$expected] = explode("\t",$line);
    if ($op === 'replace' && str_replace($needle,$repl,$s) !== $expected) throw new Exception('replacement mismatch');
    if ($op === 'compare' && (strcmp($s,$t) <=> 0) !== (int)$expected) throw new Exception('comparison mismatch');
    $count=1;
    while (batch($op,$s,$t,$needle,$repl,$count)[0] < 20000000 && $count < (1<<26)) $count*=2;
    for ($trial=0;$trial<7;++$trial) {
        [$ns,$sum] = batch($op,$s,$t,$needle,$repl,$count);
        echo "$id,php,$trial,$count,$ns,$sum\n";
    }
}
