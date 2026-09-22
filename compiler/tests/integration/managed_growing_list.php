<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/managed_storage_support.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

$root = getcwd() . '/managed-growing-list';
Managed_Storage_Fixture::prepare($root);
$runtime = $root . '/runtime';
$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['.'], 'entry' => 'main.phs']);
Files::write($root . '/project/main.phs', '$result int = exercise_manual() + exercise_record(); finished(); return $result + buffer_count();');

// Test-only expansion produces ordinary concrete source records. Dependent generic storage remains rejected.
$list = <<<'PHS'
struct LIST {
    public cells<ELEMENT> $data;

    public function __construct(): void
    {
        cells_allocate<ELEMENT>($this->data, 1);
    }

    /** Allocate an independent prefix and copy its live elements without default-constructing capacity. */
    public function __copy_construct(const LIST &$source): void
    {
        $length int = $source->count();
        cells_allocate<ELEMENT>($this->data, $length + 1);
        $index int = 0;
        while ($index < $length) {
            cells_push<ELEMENT>($this->data, $source->data[$index]);
            $index = $index + 1;
        }
    }

    /** Preserve the source, including self-assignment, until all replacement copies are complete. */
    public function __copy_assign(const LIST &$source): void
    {
        $length int = $source->count();
        $replacement cells<ELEMENT>;
        cells_allocate<ELEMENT>($replacement, $length + 1);
        $index int = 0;
        while ($index < $length) {
            cells_push<ELEMENT>($replacement, $source->data[$index]);
            $index = $index + 1;
        }
        $this->clear();
        cells_release<ELEMENT>($this->data);
        cells_transfer<ELEMENT>($replacement, $this->data);
    }

    /** Read all borrowed inputs before ending the old elements and replacing their allocation. */
    public function append(const ELEMENT &$value): void
    {
        $length int = $this->count();
        $replacement cells<ELEMENT>;
        cells_allocate<ELEMENT>($replacement, $length + 1);
        $index int = 0;
        while ($index < $length) {
            cells_push<ELEMENT>($replacement, $this->data[$index]);
            $index = $index + 1;
        }
        cells_push<ELEMENT>($replacement, $value);
        $this->clear();
        cells_release<ELEMENT>($this->data);
        cells_transfer<ELEMENT>($replacement, $this->data);
    }

    public const function count(): int
    {
        return cells_count<ELEMENT>($this->data);
    }

    public const function get($index int): ELEMENT
    {
        return $this->data[$index];
    }

    public function set($index int, const ELEMENT &$value): void
    {
        $this->data[$index] = $value;
    }

    public function clear(): void
    {
        while (cells_count<ELEMENT>($this->data)) {
            cells_pop<ELEMENT>($this->data);
        }
    }

    public function __destruct(): void
    {
        $this->clear();
        cells_release<ELEMENT>($this->data);
    }
}
PHS;
$lists = strtr($list, ['LIST' => 'manual_list', 'ELEMENT' => 'manual']) . "\n"
    . strtr($list, ['LIST' => 'record_list', 'ELEMENT' => 'item']);
Files::write($root . '/project/lists.phs', $lists);
Files::write($root . '/project/types.phs', 'struct item { public int32 $tag; public observed $payload; }');
$workload = <<<'PHS'
/** Growth, list copy/assignment, element assignment, owned reads and early return share ordinary lifecycle. */
function EXERCISE(): int
{
    INITIALIZE
    $values LIST;
    $values->append($first);
    $values->append($last);
    $saved ELEMENT = $values->get(0);
    $values->append($saved);
    $copy LIST = $values;
    $copy->set(0, $last);
    $values = $values;
    $assigned LIST;
    $assigned->append($last);
    $assigned = $values;
    $values->clear();
    $values->append($last);
    $owned ELEMENT = $assigned->get(0);
    $other ELEMENT = $copy->get(0);
    $result int = READ_OWNED + READ_OTHER + $assigned->count() + $values->count();
    if ($result) {
        return $result;
    }
    return 0;
}
PHS;
$manual_source = strtr($workload, ['EXERCISE' => 'exercise_manual', 'LIST' => 'manual_list', 'ELEMENT' => 'manual',
    'INITIALIZE' => '$first manual = make_manual(3); $last manual = make_manual(9);',
    'READ_OWNED' => 'read_manual($owned)', 'READ_OTHER' => 'read_manual($other)']);
$record_source = strtr($workload, ['EXERCISE' => 'exercise_record', 'LIST' => 'record_list', 'ELEMENT' => 'item',
    'INITIALIZE' => '$a observed = make_observed(3); $b observed = make_observed(9); $first item; $last item; $first->payload = $a; $last->payload = $b;',
    'READ_OWNED' => 'read_observed($owned->payload)', 'READ_OTHER' => 'read_observed($other->payload)']);
$path = $root . '/project/manual.phs';
Files::write($path, $manual_source);
Files::write($root . '/project/record.phs', $record_source);
$session = new \compile\Compiler_Session(runtime_package_path: $runtime);
$first = $session->compile($manifest, $root . '/program');
[$status, $trace, $error] = Managed_Storage_Fixture::run([$root . '/program']);
// Each workload returns 16 and acquires ten backing buffers; native finished() verifies every release.
Check::check(($status === 52) && ($error === ''), 'Managed lists preserve independent contents and release twenty buffers: ' . $error);
Check::check(str_contains($trace, "K:3\nK:9\nK:3\nD:9\nD:3\n"),
    'Growth constructs the complete replacement before reverse destruction of the old prefix');
Check::check(substr_count($trace, "C:") === 6, 'Spare capacity never default-constructs managed elements');
$snapshot = serialize($first);
$artifacts = Files::read($runtime . '/current.json');

// Ownership summaries and lowering use fixed inputs, private outputs and the existing joins.
$tasks = array_map(static fn($result) => $result->task, array_values($first->lifetimes->ownership_results));
$task_snapshot = serialize($tasks);
$outputs = array_map(\analyze_lifetimes\Ownership_Worker::prepare(...), array_reverse($tasks));
$joined = (new \analyze_lifetimes\Ownership_Join($tasks, $first->lifetimes->ownership_results))->join($outputs);
foreach ($joined as $key => $result) {
    Check::check($result->summary === $first->lifetimes->ownership_results[$key]->summary, 'Equivalent ownership work retains summary identity');
}
Check::check(serialize($tasks) === $task_snapshot, 'Managed list ownership workers preserve their inputs');
$lower_tasks = \Step_Test::select(\lower\Lowerer::class, $first->lifetimes, $first->backend, new \lower\Lowered_Set(), true);
$lower_outputs = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), array_reverse($lower_tasks));
$lower_join = new \lower\Lowering_Join($first->lifetimes, $first->backend, new \lower\Lowered_Set(), $lower_tasks);
Check::check($lower_join->join($lower_outputs)->to_json() === $first->lowered->to_json(), 'Managed list lowering accepts reordered completion');

// A single body replacement changes real copied contents while retaining type/native preparation.
Check::edit($path, str_replace('make_manual(3)', 'make_manual(4)', $manual_source));
$second = $session->compile($manifest, $root . '/program');
[$status, $changed_trace, $error] = Managed_Storage_Fixture::run([$root . '/program']);
Check::check(($status === 53) && ($error === '') && ($changed_trace !== $trace), 'One managed-list body edit changes execution');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && (serialize($first) === $snapshot) && (Files::read($runtime . '/current.json') === $artifacts),
    'Body increment preserves native artifacts, layouts, ABIs and the previous snapshot');

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
    Check::check(($status === 0) && (Managed_Storage_Fixture::run([$root . '/optimized']) === [52, $trace, '']),
        'Managed growing lists O1/ThinLTO: ' . $error);
}

// The call-scoped borrow cannot survive invalidation of its backing storage.
Files::write($root . '/borrow.phs', $lists . 'struct item { public int32 $tag; public observed $payload; } '
    . '$a manual = make_manual(3); $values manual_list; $values->append($a); $values->append($values->data[0]); return 0;');
Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $runtime))->compile($root . '/borrow.phs'), 'active call borrow');
Check::check(serialize($first) === $snapshot, 'Managed list proofs preserve the retained compilation');
echo "managed growing lists ok: source growth/copy/assignment, owned reads, early cleanup, private workers, one increment and O1/ThinLTO\n";
