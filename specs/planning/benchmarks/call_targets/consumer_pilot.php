<?php
declare(strict_types=1);
require $argv[1];
use scpp\compiler\{Incremental_Project, entry_target, diagnostic, diagnostic_group};

[$app, $out, $mode] = array_slice($argv, 2);
function monitor_targets(): void {
    $names = ['scpp\compiler\ast_call_expression->prepared_target'];
    foreach (['symbol_entry','called_method_instance','runtime_function','ast_local_declaration',
              'ast_parameter_declaration','ast_produced_binding'] as $class) {
        foreach (['call_signature','lowering_operand'] as $method) {
            $names[] = 'scpp\\compiler\\' . $class . '->' . $method;
        }
    }
    xdebug_start_function_monitor($names);
}
function end_monitor(string $stage): void {
    $calls = xdebug_get_monitored_functions(true);
    xdebug_stop_function_monitor();
    $counts = [];
    foreach ($calls as $call) {
        $name = substr($call['function'], strrpos($call['function'], '->') + 2);
        $counts[$name] = ($counts[$name] ?? 0) + 1;
    }
    $GLOBALS['stage_counts'][$stage] = $counts;
}
function save(string $path, string $source): void {
    static $stamp = 1990000000;
    file_put_contents($path, $source);
    touch($path, ++$stamp);
    clearstatcache(true, $path);
}
function worker(int $i, int $value): string {
    return 'function worker'.$i.'(string $text):int { $g = function ():int {return '.$value.';}; '
        .'return answer() + strlen($text) + Right::read(3) + $g(); }';
}
function targets(int $value, bool $bad = false): string {
    return 'function answer('.($bad ? 'int $required' : '').'):int {return '.$value.';}';
}
function stamp(string $out): string {
    $h = hash_init('sha256');
    $paths = array_merge(glob("$out/units/*"), glob("$out/*.cpp"), glob("$out/*.hpp"));
    sort($paths);
    foreach ($paths as $p) {
        hash_update($h, basename($p));
        hash_update_file($h, $p);
    }
    return hash_final($h);
}
save("$app/module.json", '{"entry":"main.py"}');
save("$app/main.py", "counter: Counter = Counter(7)\nreturn worker0(\"abc\") + counter.value\n");
save("$app/record.py", "class Counter:\n    value: int = 0\n    def __init__(self, n: int):\n        self.value = n\n");
save("$app/methods.php", 'class Base {public static function read(int $value):int {return $value;}} class Right extends Base {}');
save("$app/answer.php", targets(1));
for ($i = 1; $i < 16; ++$i) save("$app/work$i.php", worker($i, 1));
$make = static fn() => new Incremental_Project($app, $out, new entry_target(31));
if ($mode === 'timing' || $mode === 'counts') {
    for ($i = 0; $i < ($mode === 'counts' ? 1 : 12); ++$i) {
        $project = $make();
        foreach (['fresh'=>1, 'edit'=>2] as $phase=>$value) {
            save("$app/work0.php", worker(0, $value));
            $result = $project->run();
            if ($phase === 'edit' && ($result['preparation']['prepared'] !== 1 || count($result['emitted_sources']) !== 1))
                throw new RuntimeException('Caller edit did not reuse the remaining program');
            if ($mode === 'counts') $GLOBALS['query_counts'][$phase] = $GLOBALS['stage_counts'];
            if ($i) $samples[$phase][] = $GLOBALS['phase_times'];
            $work[$phase] = [$result['preparation'], count($result['emitted_sources'])];
        }
        unset($project);
        gc_collect_cycles();
    }
    echo json_encode($mode === 'counts' ? $GLOBALS['query_counts'] : compact('samples','work'), JSON_THROW_ON_ERROR);
    return;
}

save("$app/work0.php", worker(0, 1));
$project = $make();
foreach (['fresh','caller','body','shadow','restore','bad','repair','bad_local','bad_method','bad_runtime','bad_ctor','retire','rebind'] as $phase) {
    match ($phase) {
        'caller' => save("$app/work0.php", worker(0, 2)),
        'body' => save("$app/answer.php", targets(2)),
        'shadow' => save("$app/shadow.php", 'function strlen(string $text):int {return 6;}'),
        'restore' => unlink("$app/shadow.php"),
        'bad' => save("$app/answer.php", targets(2, true)),
        'bad_local' => save("$app/work0.php", str_replace('$g();', '$g(1);', worker(0, 2))),
        'bad_method' => save("$app/work0.php", str_replace('read(3)', 'read()', worker(0, 2))),
        'bad_runtime' => save("$app/work0.php", str_replace('strlen($text)', 'strlen(7)', worker(0, 2))),
        'bad_ctor' => save("$app/work0.php", worker(0, 2)),
        'repair' => save("$app/answer.php", targets(1)),
        default => null
    };
    if ($phase === 'bad_ctor') save("$app/main.py", "counter: Counter = Counter(7, 8)\nreturn worker0(\"abc\") + counter.value\n");
    if ($phase === 'retire' || $phase === 'rebind') {
        save("$app/main.py", "counter: Counter = Counter(7)\nreturn worker0(\"abc\") + counter.value\n");
        for ($i = 0; $i < 16; ++$i) {
            $worker = worker($i, $i === 0 ? 2 : 1);
            save("$app/work$i.php", $phase === 'retire' ? str_replace('Right::read(3)', '3', $worker) : $worker);
        }
    }
    try {
        $result = $project->run();
        if (str_starts_with($phase, 'bad')) throw new RuntimeException('Required argument accepted');
    } catch (diagnostic|diagnostic_group $error) {
        $expected = $phase === 'bad_runtime' ? 'strlen requires string' : 'Argument count mismatch';
        if (!str_starts_with($phase, 'bad') || !str_contains($error->getMessage(), $expected)) throw $error;
        $rows[$phase] = ['error'=>$error->getMessage(), 'bundle'=>stamp($out)];
        if ($rows[$phase]['bundle'] !== $rows[$phase === 'bad' ? 'restore' : 'repair']['bundle']) throw new RuntimeException('Failed edit altered output');
        continue;
    }
    if ($phase === 'fresh') {
        $local = WeakReference::create($project->session()->scope->resolve('func:worker0')->body()->statements[0]);
        $origin = $project->session()->scope->resolve('method:Base::read');
        $private = array_values($origin->root()->static_implementations($origin))[0];
        $instance = WeakReference::create($private->instance);
        unset($origin, $private);
    }
    if ($phase === 'caller' || $phase === 'retire') {
        gc_collect_cycles();
        $observer = $phase === 'caller' ? $local : $instance;
        if ($observer->get() !== null) throw new RuntimeException("Stale target retained after $phase");
    }
    $rows[$phase] = ['work'=>$result['preparation'], 'emitted'=>array_map('basename', $result['emitted_sources']), 'bundle'=>stamp($out)];
    sort($rows[$phase]['emitted']);
    $clean = new Incremental_Project($app, $out.'-fresh', new entry_target(31));
    $clean->run();
    unset($clean);
    if (stamp($out.'-fresh') !== $rows[$phase]['bundle']) throw new RuntimeException("Fresh artifacts differ after $phase");
}
echo json_encode($rows, JSON_THROW_ON_ERROR);
