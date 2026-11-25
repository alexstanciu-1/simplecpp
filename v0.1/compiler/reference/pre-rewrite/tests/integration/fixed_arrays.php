<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';
use Body_Test_Stages as Check;

final class Array_Test
{
    /** Execute native output without allowing an expected trap to print a shell diagnostic. */
    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w']], $pipes);
        return proc_close($process);
    }

    /** Capture the configured Clang oracle's output and diagnostics. */
    public static function capture(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }

}

$root = getcwd() . '/fixed-arrays';
mkdir($root);
$manifest = $root . '/project.json';
file_put_contents($manifest, json_encode(['source_folders' => ['.'], 'entry' => 'main.phs']));
$definitions = <<<'PHS'
const STEP: int32 = 1;
const TEN: int32 = 10;
const BYTE: uint8 = 129;
const CAPACITY = 16;
template<int N>
struct buffer_i32 { public int32 $data[N]; public int32 $size; }
template<int N>
function append_i32(buffer_i32<N> &$b, $value int32): void {
    $b->data[$b->size] = $value;
    $b->size = $b->size + STEP;
}
template<int N>
function read_i32(const buffer_i32<N> &$b, $index int): int32 { return $b->data[$index]; }
template<int N>
struct buffer_byte { public uint8 $data[N]; public int32 $size; }
template<int N>
function append_byte(buffer_byte<N> &$b, $value uint8): void {
    $b->data[$b->size] = $value;
    $b->size = $b->size + STEP;
}
template<int N>
function read_byte(const buffer_byte<N> &$b, $index int): uint8 { return $b->data[$index]; }
function next(buffer_i32<CAPACITY> &$b): int32 {
    $b->size = $b->size + STEP;
    return $b->size;
}
function change(buffer_i32<CAPACITY> &$b): int32 {
    $b->size = TEN;
    return TEN;
}
PHS;
file_put_contents($root . '/definitions.phs', $definitions);
$main = <<<'PHS'
$a buffer_i32<CAPACITY>;
append_i32<CAPACITY>($a, TEN);
append_i32<CAPACITY>($a, TEN + STEP);
$copy buffer_i32<CAPACITY> = $a;
$a->data[0] = STEP;
$a->data[next($a)] = change($a);
$b buffer_byte<3>;
append_byte<3>($b, BYTE);
append_byte<3>($b, BYTE);
append_byte<3>($b, BYTE);
// An unsigned narrow index above its sign bit must stay positive.
$wide buffer_byte<130>;
$wide->data[BYTE] = BYTE;
if ($wide->data[129]) {
    return read_i32<CAPACITY>($copy, 0) + read_i32<CAPACITY>($copy, 1)
        + $a->data[3] + $a->data[10];
}
return 99;
PHS;
$source = $root . '/main.phs';
file_put_contents($source, $main);
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest, $root . '-program');
Check::check(Array_Test::run($root . '-program') === 31, 'Arrays preserve zero initialization, copies, unsigned indices and target-before-value evaluation');
// Check the shared layout path against an independent native C storage declaration.
$toolchain = new \prepare_backend\LLVM_Toolchain();
$configuration = $toolchain->configuration();
[$command, $launcher] = $toolchain->layout_tools($configuration);
file_put_contents($root . '/layout.c', <<<'C'
#include <stddef.h>
#include <stdint.h>
#include <stdio.h>
struct wide { int32_t data[16]; int32_t size; };
struct small { uint8_t data[3]; int32_t size; };
int main(void) {
    printf("%zu %zu %zu %zu\n", sizeof(struct wide), _Alignof(struct wide), offsetof(struct wide, data), offsetof(struct wide, size));
    printf("%zu %zu %zu %zu\n", sizeof(struct small), _Alignof(struct small), offsetof(struct small, data), offsetof(struct small, size));
}
C);
[$status, , $error] = Array_Test::capture([$command[0], '--target=' . $configuration->target_triple,
    '-std=c11', $root . '/layout.c', '-o', $root . '-layout']);
Check::check($status === 0, 'Native array layout oracle compiles: ' . $error);
[$status, $measurements] = Array_Test::capture([$root . '-layout']);
Check::check($status === 0, 'Native array layout oracle executes');
$measurements = explode("\n", trim($measurements));
foreach ($first->types->instances->contexts as $context)
{
    if (!in_array($context->definition->name, ['buffer_i32', 'buffer_byte'], true)) {
        continue;
    }
    $capacity = $context->arguments[0]->value;
    if (!in_array($capacity, ['16', '3'], true)) {
        continue;
    }
    $id = $first->types->types->find_type($context->type_name(), $context->type_namespace());
    $layout = $first->backend->layouts[$id];
    Check::check(implode(' ', [$layout->size, $layout->alignment, ...$layout->offsets]) === $measurements[$capacity === '16' ? 0 : 1],
        'Array storage, padding and field offsets agree with the selected native target');
}
$layout_input = \prepare_backend\Layout_Preparation::capture($first->types->types, array_keys($first->backend->layouts));
$tasks = \prepare_backend\Layout_Preparation::select($layout_input, $first->backend->configuration,
    [], true, $command, $launcher);
$results = array_map(\prepare_backend\Layout_Preparation::prepare(...), $tasks);
$layouts = (new \prepare_backend\Layout_Join($layout_input, $first->backend->configuration, [], $tasks))->join(array_reverse($results));
Check::check(serialize($layouts) === serialize($first->backend->layouts), 'Private array layout workers join independently of completion order');
$before = serialize($first);
Check::edit($source, str_replace('$a->data[3] + $a->data[10]', '$a->data[3] + $a->data[10] + STEP', $main));
$second = $session->compile($manifest, $root . '-program');
Check::check(Array_Test::run($root . '-program') === 32, 'One body increment executes with array layouts and instances retained');
Check::check(serialize($first) === $before, 'Increment leaves all retained snapshots unchanged');
Check::check($second->backend->layouts === $first->backend->layouts, 'Unchanged record layouts retain exact identity');

// Bounds failures include negative values, reads, writes, and overflow after filling every slot.
$failures = [
    '$a buffer_i32<3>; return $a->data[3];',
    '$a buffer_i32<3>; $a->data[3] = TEN; return 0;',
    '$a buffer_i32<3>; append_i32<3>($a, TEN); append_i32<3>($a, TEN); append_i32<3>($a, TEN); append_i32<3>($a, TEN); return 0;',
    '$a buffer_i32<3>; $i int = 9223372036854775807 + 1; return $a->data[$i];',
];
foreach ($failures as $code) {
    Check::edit($source, $code);
    (new \compile\Compiler_Session())->compile($manifest, $root . '-failure');
    Check::check(Array_Test::run($root . '-failure') !== 0, 'Out-of-bounds access stops execution');
}
$invalid = [
    ['struct a { public int32 $data[0]; } return 0;', 'positive integer'],
    ['struct a { public int32 $data[1 + 2]; } return 0;', 'constant evaluation'],
    ['struct a { public int $data[3]; } return 0;', 'unsupported struct field'],
    ['function fail(const buffer_i32<3> &$a): void { $a->data[0] = TEN; } return 0;', 'const reference'],
    ['$a buffer_i32<3>; $a->data[0] = BYTE; return 0;', 'conversion'],
    ['const BAD: uint8 = 256; return 0;', 'outside the range'],
];
foreach ($invalid as [$code, $reason]) {
    Check::edit($source, $code);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session())->compile($manifest), $reason);
    Check::check($error instanceof \diagnostics\Source_Error, 'Invalid arrays retain source diagnostics');
}
echo "fixed arrays ok: templated capacities/concrete elements, copy/zero storage, const references, target order, checked bounds and one pure incremental replacement\n";
