<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';

class Addition_Test extends Body_Test_Stages
{
    // A test-only native caller compares the entire result, not just the process exit byte.
    public static function full_width(\compile\Compile_Result $result, int $symbol, string $left, string $right, string $expected): void
    {
        $toolchain = new \prepare_backend\LLVM_Toolchain();
        $configuration = $toolchain->configuration();
        self::check($configuration == $result->backend->configuration, 'Addition probe uses the compiled target');
        $function = $result->llvm->function_for($symbol);
        $binding = $function->body->binding;
        $type = \prepare_backend\LLVM_Types::storage($binding->return_definition->representation);
        $native = 'i' . $result->llvm->entry->native_bits;
        $ir = 'target triple = ' . \prepare_backend\LLVM_Types::quote($configuration->target_triple) . "\n"
            . 'target datalayout = ' . \prepare_backend\LLVM_Types::quote($configuration->data_layout) . "\n"
            . $function->ir . 'define ' . $result->llvm->entry->calling_convention . ' ' . $native . " @main() { entry:\n"
            . ' %value = call ' . $binding->calling_convention . ' ' . $type . ' @' . \prepare_backend\LLVM_Types::quote($binding->link_name)
            . '(' . $type . ' ' . $left . ', ' . $type . ' ' . $right . ")\n"
            . ' %ok = icmp eq ' . $type . ' %value, ' . $expected . "\n"
            . ' %status = select i1 %ok, ' . $native . ' 0, ' . $native . " 1\n ret " . $native . " %status\n}\n"
            . 'attributes #0 = { "target-cpu"=' . \prepare_backend\LLVM_Types::quote($configuration->cpu)
            . ' "target-features"=' . \prepare_backend\LLVM_Types::quote($configuration->features) . " }\n";
        $object = getcwd() . '/addition-probe.o';
        $output = getcwd() . '/addition-probe';
        $toolchain->compile_object($ir, $object, $configuration);
        $toolchain->link_objects([$object], $output, $configuration);
        self::check(self::run($output) === 0, 'Emitted addition returns the exact full-width wrapping result');
    }

    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w']], $pipes);
        self::check(is_resource($process), 'Start addition executable');
        return proc_close($process);
    }
}
use Addition_Test as Check;

$manifest = '../fixtures/three_files/project.json';
$main = realpath('../fixtures/three_files/src/main.phs');
$value_path = realpath('../fixtures/three_files/src/nested/value.phs');
$output = getcwd() . '/addition';
$source = '$x int = 2; $x = add($x + 3, answer() + (4 + 5)); $x + 1; return $x;
function add($a int, $b int): int { return $a + $b; }';
Check::edit($main, $source);
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest, $output);
Check::check(Check::run($output) === 56, 'Nested addition composes with calls, locals, assignment and groups');
$fixed = serialize($first);
$add_id = $first->symbols->current->find_symbol('add', '', \collect_symbols\symbol_kind::function_symbol);
Check::full_width($first, $add_id, '9223372036854775807', '1', '-9223372036854775808');
Check::full_width($first, $add_id, '-9223372036854775808', '-1', '9223372036854775807');

$tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $first->symbols->current, $first->resolutions, $first->types,
    new \check_bodies\Body_Set(), true);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \check_bodies\Body_Worker($task))->check();
}
$joined = (new \check_bodies\Body_Join($first->symbols->current, $first->resolutions, $first->types, new \check_bodies\Body_Set(), $tasks))->join($results);
Check::check(($joined->to_json() === $first->bodies->to_json()) && (serialize($first) === $fixed),
    'Reversed addition workers preserve fixed inputs and exports');
$warm = $session->compile($manifest, $output);
Check::check($warm->bodies === $first->bodies, 'Unchanged addition reuses checked bodies');
Check::edit($value_path, 'function value(): int { return 43; }');
$edited = $session->compile($manifest, $output);
Check::check((Check::run($output) === 57) && (!$edited->inputs->context->full_rebuild), 'Addition executes a resident callee body edit');
$fresh = (new \compile\Compiler_Session())->compile($manifest);
Check::check($fresh->bodies->to_json() === $edited->bodies->to_json(), 'Resident and fresh addition facts agree');

Check::edit($main, 'return 9223372036854775807 + 1 + 9223372036854775807 + 1;');
$session->compile($manifest, $output);
Check::check(Check::run($output) === 0, 'Signed addition wraps without LLVM poison flags');
Check::edit($main, 'return 0; function mixed($a int32, $b int): int { return $a + $b; }');
Check::rejects(static fn() => $session->compile($manifest, $output), 'Unsupported addition operand types');
Check::edit($main, 'return f() + 1; function f(): void {}');
Check::rejects(static fn() => $session->compile($manifest, $output), 'requires value operands');
Check::edit($main, 'return ' . str_repeat('(', 1500) . '1' . str_repeat(' + 1)', 1500) . ';');
$deep = $session->compile($manifest, $output);
Check::check(Check::run($output) === (1501 % 256), 'Deep addition uses bounded explicit traversal stacks');

$catalog = json_decode(file_get_contents(dirname(__DIR__, 2) . '/language/named_types.json'), true, 512, JSON_THROW_ON_ERROR);
$catalog['literal_types']['integer']['name'] = 'uint8';
$catalog['entry_return_type']['name'] = 'uint8';
$valid_catalog = json_encode($catalog, JSON_THROW_ON_ERROR);
$catalog_path = getcwd() . '/types.json';
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
Check::edit($main, 'return 250 + 10;');
Check::edit($value_path, '');
Check::edit(realpath('../fixtures/three_files/src/answer.phs'), '');
$small = new \compile\Compiler_Session(type_catalog_path: $catalog_path);
$small->compile($manifest, $output);
Check::check(Check::run($output) === 4, 'Unsigned arithmetic uses the provider width');
foreach ($catalog['types'] as &$row) {
    if ($row['name'] === 'uint8') {
        unset($row['addition']);
    }
}
unset($row);
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
Check::rejects(static fn() => $small->compile($manifest, $output), 'Unsupported addition operand types');
file_put_contents($catalog_path, $valid_catalog);
$small->compile($manifest, $output);
Check::check(Check::run($output) === 4, 'Catalog failure repairs through the common stages');
echo "addition ok: composition, wrapping, provider permission, deep operands, fixed workers, native update and repair\n";
