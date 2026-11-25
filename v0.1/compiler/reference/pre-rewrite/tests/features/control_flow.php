<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';

class Control_Flow_Test extends Body_Test_Stages
{
    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w']], $pipes);
        self::check(is_resource($process), 'Start flow executable');
        $deadline = hrtime(true) + 3_000_000_000;
        try
        {
            do
            {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    return $status['exitcode'];
                }
                self::check(hrtime(true) < $deadline, 'Control flow executable timed out');
                usleep(10000);
            }
            while (true);
        }
        finally {
            if (proc_get_status($process)['running']) {
                proc_terminate($process, 9);
            }
            proc_close($process);
        }
    }
}
use Control_Flow_Test as Check;

$manifest = '../fixtures/three_files/project.json';
$main = realpath('../fixtures/three_files/src/main.phs');
$value_path = realpath('../fixtures/three_files/src/nested/value.phs');
$output = getcwd() . '/flow';
$source = 'return choose(0) + choose(1) + sum(3) + early(2);
function choose($c int): int { $x int = 1; if ($c) { $x = 20; } else { $x = 10; } return $x; }
function sum($i int): int {
    $sum int = 0; $step int = 9223372036854775807 + 9223372036854775807 + 1;
    while ($i) { $v int = $i; $sum = $sum + $v; $i = $i + $step; }
    return $sum;
}
function early($n int): int {
    while ($n) { if ($n) { return value(); } else { return 99; } }
    return 0;
}';
Check::edit($main, $source);
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest, $output);
Check::check(Check::run($output) === 78, 'Both branches, repeated local initialization, loop-carried writes and early return execute');
$fixed = serialize($first);
$sum_id = $first->symbols->current->find_symbol('sum', '', \collect_symbols\symbol_kind::function_symbol);
$analysis = $first->lifetimes->for_symbol($sum_id);
Check::check((count($analysis->reachable_blocks) > 1) && (count($first->lowered->for_symbol($sum_id)->blocks) > 1),
    'Checked and lowered control flow retain explicit blocks');
$update = new \compile\Update_Context();
$update->full_rebuild = true;
$rechecked = \compile\Phases::run_bodies($first->symbols->current, $first->resolutions, $first->types,
    new \check_bodies\Body_Set(), $update);
$tasks = \Step_Test::select(\analyze_lifetimes\Lifetime_Analyzer::class, $rechecked, new \analyze_lifetimes\Lifetime_Set(), true);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \analyze_lifetimes\Lifetime_Worker($task))->analyze();
}
$joined = (new \analyze_lifetimes\Lifetime_Join($rechecked, new \analyze_lifetimes\Lifetime_Set(), $tasks))->join($results);
Check::check(($joined->to_json() === $first->lifetimes->to_json()) && (serialize($first) === $fixed),
    'Independent flow workers preserve snapshots and deterministic exports');
Check::edit($value_path, 'function value(): int { return 43; }');
$edited = $session->compile($manifest, $output);
Check::check((Check::run($output) === 79) && (!$edited->inputs->context->full_rebuild)
    && ($edited->lifetimes->for_symbol($sum_id) === $analysis), 'Resident callee body edit preserves unrelated flow analysis');
$fresh = (new \compile\Compiler_Session())->compile($manifest);
Check::check($fresh->lifetimes->to_json() === $edited->lifetimes->to_json(), 'Fresh and resident flow facts agree');

Check::edit($main, str_replace('if ($c)', 'if (0)', $source));
$branch_edit = $session->compile($manifest, $output);
Check::check((Check::run($output) === 69) && (!$branch_edit->inputs->context->full_rebuild),
    'Changing a condition uses the admitted body-edit path and updates branch execution');
$fresh_branch = (new \compile\Compiler_Session())->compile($manifest);
Check::check($fresh_branch->lifetimes->to_json() === $branch_edit->lifetimes->to_json(),
    'Changed control flow agrees with a fresh build');
$lower_tasks = \Step_Test::select(\lower\Lowerer::class, $first->lifetimes, $first->backend, new \lower\Lowered_Set(), true);
$lower_results = [];
foreach (array_reverse($lower_tasks) as $task) {
    $lower_results[] = (new \lower\Lowering_Worker($task))->lower();
}
$lower_joined = (new \lower\Lowering_Join($first->lifetimes, $first->backend, new \lower\Lowered_Set(), $lower_tasks))->join($lower_results);
Check::check(($lower_joined->to_json() === $first->lowered->to_json()) && (serialize($first) === $fixed),
    'Reversed lowering workers preserve multi-block plans and retained inputs');

$cases = [
    ['if (0) { return 9; } return 7;', 7],
    ['if (2) { return 9; } else { return 7; }', 9],
    ['while (0) { return 9; } return 7;', 7],
    ['$x int = 1; if (0) {} else { $x = 5; } return $x;', 5],
    ['$x int = 2; if ($x) { $x int = 8; if ($x) { $x = 9; } } return $x;', 2],
    ['return 7; while (1) { return 8; }', 7],
    ['empty(1); return 7; function empty($c int): void { if ($c) {} else {} while (0) {} }', 7],
    ['$x int = 1; if (1) { return $x; } else { return $x + 1; }', 1],
    ['$i int = 2; $step int = 9223372036854775807 + 9223372036854775807 + 1; $sum int = 0;
      while ($i) { $j int = 2; while ($j) { $sum = $sum + 1; $j = $j + $step; } $i = $i + $step; } return $sum;', 4],
];
foreach ($cases as [$text, $expected]) {
    Check::edit($main, $text);
    $session->compile($manifest, $output);
    Check::check(Check::run($output) === $expected, 'Native control case: ' . $text);
}
foreach ([
    ['if (1) { return 2; }', 'can finish without returning'],
    ['while (1) { return 2; }', 'can finish without returning'],
    ['if (nothing()) { return 1; } return 0; function nothing(): void {}', 'Condition requires an integer'],
    ['if (1) { $x int = 1; } return $x;', 'Unknown local'],
    ['if (1) return 2;', "'{' to open block"],
    ['return 1; if (0) { missing(); }', 'Unknown function'],
    ['return 0; function f($x float): int { if ($x) { return 1; } return 0; }', 'Condition requires an integer'],
] as [$text, $reason]) {
    Check::edit($main, $text);
    Check::rejects(static fn() => $session->compile($manifest, $output), $reason);
}
Check::edit($main, 'return 12;');
$session->compile($manifest, $output);
Check::check((Check::run($output) === 12) && (serialize($first) === $fixed), 'Flow failure/repair preserves retained snapshots');
echo "control flow ok: branches, nested loops, scope exits, repeated execution, return paths, worker purity, native updates and repair\n";
