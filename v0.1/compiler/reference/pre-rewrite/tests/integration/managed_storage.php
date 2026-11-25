<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/managed_storage_support.php';
require_once __DIR__ . '/../support/lifecycle_support.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

$root = getcwd() . '/managed-storage';
Managed_Storage_Fixture::prepare($root);
Files::write_json($root . '/project/project.json', ['source_folders' => ['.'], 'entry' => 'main.phs']);
Files::write($root . '/project/main.phs', 'return objects() + records() + no_default() + finished();');
Files::write($root . '/project/types.phs', 'struct pair { public observed $first; public observed $last; }');
$source = <<<'PHS'
function objects(): int {
    $a observed = make_observed(3);
    $b observed = make_observed(9);
    $s cells<observed>;
    $t cells<observed>;
    cells_allocate<observed>($s, 3);
    cells_push<observed>($s, $a);
    cells_push<observed>($s, $b);
    cells_push<observed>($s, $s[0]);
    $s[0] = $b;
    $copy observed = $s[2];
    cells_transfer<observed>($s, $t);
    cells_pop<observed>($t);
    cells_pop<observed>($t);
    cells_pop<observed>($t);
    cells_release<observed>($t);
    cells_release<observed>($s);
    return read_observed($copy) + read_observed($a);
}
PHS;
$path = $root . '/project/objects.phs';
Files::write($path, $source);
Files::write($root . '/project/records.phs', <<<'PHS'
function records(): int {
    $a observed = make_observed(3);
    $b observed = make_observed(9);
    $p pair;
    $p->first = $a;
    $p->last = $b;
    $s cells<pair>;
    cells_allocate<pair>($s, 2);
    cells_push<pair>($s, $p);
    $p->first = $b;
    cells_push<pair>($s, $p);
    $s[0]->last = $a;
    $copy pair = $s[0];
    $s[1] = $copy;
    $s[1]->first = $b;
    $result int = read_observed($s[0]->first) + read_observed($s[1]->last);
    cells_pop<pair>($s);
    cells_pop<pair>($s);
    cells_release<pair>($s);
    return $result;
}
PHS);
Files::write($root . '/project/manual.phs', <<<'PHS'
function no_default(): int {
    $a manual = make_manual(5);
    $s cells<manual>;
    cells_allocate<manual>($s, 4);
    cells_push<manual>($s, $a);
    $n int = read_manual($s[0]);
    cells_pop<manual>($s);
    cells_release<manual>($s);
    return $n;
}
PHS);
$runtime = $root . '/runtime';
$session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$first = $session->compile($root . '/project/project.json', $root . '/program');
$object_trace = "C:3\nC:9\nK:3\nK:9\nK:3\nA:3:9\nK:3\nD:3\nD:9\nD:9\nD:3\nD:9\nD:3\n";
$record_trace = "C:3\nC:9\nC:0\nC:0\nA:0:3\nA:0:9\nK:3\nK:9\nA:3:9\nK:9\nK:9\n"
    . "A:9:3\nK:3\nK:3\nA:9:3\nA:9:3\nA:3:9\nD:3\nD:9\nD:3\nD:3\nD:3\nD:3\nD:9\nD:9\nD:9\nD:3\n";
$trace = $object_trace . $record_trace . "C:5\nK:5\nD:5\nD:5\n";
Check::check(Managed_Storage_Fixture::run([$root . '/program']) === [17, $trace, ''],
    'Aligned independent element copies, assignment, reverse field/element cleanup and transfer execute with balanced allocations');
Lifecycle_Test::preparation($first);
$before = serialize($first);
$artifacts = Files::read($runtime . '/current.json');

// Selected workers read fixed inputs, publish private plans, and accept reordered completion.
$tasks = \Step_Test::select(\lower\Lowerer::class, $first->lifetimes, $first->backend, new \lower\Lowered_Set(), true);
$results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), array_reverse($tasks));
$join = new \lower\Lowering_Join($first->lifetimes, $first->backend, new \lower\Lowered_Set(), $tasks);
Check::check($join->join($results)->to_json() === $first->lowered->to_json(), 'Managed storage lowering joins deterministically');
Check::rejects(static fn() => $join->join(array_slice($results, 1)), 'Incomplete');
Check::rejects(static fn() => $join->join([...$results, $results[0]]), 'duplicate');
foreach ($results as $result)
{
    foreach ($result->instructions as $index => $instruction)
    {
        if ($instruction->kind === \lower\instruction_kind::storage_begin)
        {
            $expected = $instruction->payload->call->target->storage->role === \type_model\storage_role::push
                ? \lower\instruction_kind::copy_construct : \lower\instruction_kind::destroy;
            Check::check(($result->instructions[$index + 1]->kind === $expected)
                && ($result->instructions[$index + 1]->payload->destination === $instruction->payload->destination)
                && ($result->instructions[$index + 2]->kind === \lower\instruction_kind::storage_end),
                'Each managed prefix transition contains the ordinary selected lifecycle operation');
        }
    }
}

Check::edit($path, str_replace('return read_observed($copy)', 'return 1 + read_observed($copy)', $source));
$second = $session->compile($root . '/project/project.json', $root . '/program');
Check::check(Managed_Storage_Fixture::run([$root . '/program']) === [18, $trace, ''], 'One managed-storage body increment executes');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && (serialize($first) === $before) && (Files::read($runtime . '/current.json') === $artifacts),
    'Increment reuses prepared layouts/operations/artifacts and preserves the old snapshot');

// Run both optimization modes through the same accepted modules; ThinLTO is a compatibility proof.
$modules = [];
foreach ($first->llvm->ir_by_file() as $id => $ir) {
    $modules[] = $root . '/module-' . $id . '.ll';
    Files::write($modules[count($modules) - 1], $ir);
}
$package = Files::json($runtime . '/package/manifest.json');
foreach ([[], ['-flto=thin', '-fuse-ld=lld']] as $flags)
{
    [$status, , $error] = Managed_Storage_Fixture::run([$package['link_driver']['executable'], '--driver-mode=g++',
        '--target=' . $package['target']['triple'], '-O1', ...$flags, ...$modules,
        ...$first->backend->runtime->modules_for($flags === [] ? \load_runtime\runtime_module_kind::ordinary : \load_runtime\runtime_module_kind::thin_lto),
        ...$first->backend->runtime->link_arguments, '-o', $root . '/optimized']);
    Check::check(($status === 0) && (Managed_Storage_Fixture::run([$root . '/optimized']) === [17, $trace, '']),
        'Managed storage O1/ThinLTO: ' . $error);
}

// Fatal prefix failures must happen before a copy/destructor touches an invalid slot.
foreach ([
    ['cells_pop<observed>($s);', 'index', "C:3\n"],
    ['cells_push<observed>($s, $a); cells_push<observed>($s, $a); cells_pop<observed>($s);', 'full', "C:3\nK:3\n"],
    ['cells_push<observed>($s, $a);', 'live', "C:3\nK:3\n"],
    ['$n int = read_observed($s[0]);', '', "C:3\n"],
] as [$body, $message, $events])
{
    Files::write($root . '/failure.phs', '$a observed = make_observed(3); $s cells<observed>; cells_allocate<observed>($s, 1); '
        . $body . ' cells_release<observed>($s); return 0;');
    (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/failure.phs', $root . '/failure');
    [$status, $out, $error] = Managed_Storage_Fixture::run([$root . '/failure']);
    Check::check(($status !== 0) && ($out === $events) && (($message === '') || str_contains($error, $message)),
        'Invalid prefix rejects before lifecycle action: ' . $error);
}
foreach ([
    ['$s cells<observed>; cells_allocate<observed>($s, 1); cells_push<observed>($s, 3); cells_release<observed>($s); return 0;', 'argument'],
    ['struct owner { public cells<observed> $items; } $s cells<owner>; return 0;', 'no compiler-tracked allocation ownership'],
    ['$a manual; return 0;', 'Default construction'],
    ['template<typename T> function bad(): void { $s cells<T>; } return 0;', 'provider storage family'],
] as [$source, $message]) {
    Files::write($root . '/bad.phs', $source);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/bad.phs'), $message);
    Check::check($error instanceof \diagnostics\Source_Error, 'Unsupported managed storage reports its source');
}
Files::write($root . '/borrow.phs', <<<'PHS'
struct holder {
    public cells<observed> $data;
    public function __construct(): void {
        cells_allocate<observed>($this->data, 1);
        $a observed = make_observed(3);
        cells_push<observed>($this->data, $a);
    }
    public function reset(): int {
        cells_pop<observed>($this->data);
        cells_release<observed>($this->data);
        return 0;
    }
}
function consume(const observed &$arg, $n int): int { return read_observed($arg); }
$h holder;
return consume($h->data[0], $h->reset());
PHS);
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/borrow.phs'), 'active call borrow');
Check::check(serialize($first) === $before, 'All managed storage proofs preserve retained inputs');
echo "managed storage ok: native/source lifecycle, assignment, transfer, prefix failures, joins, increment and O1/ThinLTO\n";
