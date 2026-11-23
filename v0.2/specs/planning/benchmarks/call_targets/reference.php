<?php
declare(strict_types=1);
require $argv[1];
use scpp\compiler\{Project_Compiler, Incremental_Preparation, entry_target};

// A production reference for the five target roles. Builds and edits use one file per body group.
$app = $argv[2];
file_put_contents("$app/module.json", '{"entry":"main.py"}');
file_put_contents("$app/main.py", "Counter(7)\nreturn work(\"abc\")\n");
file_put_contents("$app/record.py", "class Counter:\n    value: int = 0\n    def __init__(self, n: int):\n        self.value = n\n");
file_put_contents("$app/targets.php", 'function answer():int {return 1;} class Base {public static function read():int {return 3;}} class Right extends Base {}');
$target = new entry_target(31);
foreach (range(0, 41) as $run) {
    $compiler = new Project_Compiler();
    $session = null;
    foreach (['fresh', 'edit'] as $phase) {
        $n = $phase === 'fresh' ? 1 : 2;
        $source = 'function work(string $text):int { $g = function ():int {return '.$n.';}; answer(); strlen($text); Right::read(); return $g(); }';
        file_put_contents("$app/work.php", $source);
        touch("$app/work.php", 1990000000 + 2*$run + $n);
        clearstatcache();
        $session = $compiler->frontend($app, $session);
        $start = hrtime(true);
        $report = Incremental_Preparation::prepare($session, $target);
        $elapsed = hrtime(true) - $start;
        if ($phase === 'edit' && ($report['prepared'] !== 1 || $report['reused'] === 0)) throw new RuntimeException(json_encode($report));
        if ($run > 0) $samples[$phase][] = $elapsed;
        $bodies[$phase] = $report;
    }
}
foreach ($samples as $phase => $times) {
    sort($times);
    printf("PHP reference %s prepared=%d reused=%d median_ms=%.4f\n", $phase, $bodies[$phase]['prepared'], $bodies[$phase]['reused'], $times[20]/1e6);
}
