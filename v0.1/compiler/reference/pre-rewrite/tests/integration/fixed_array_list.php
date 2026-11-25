<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';
use Body_Test_Stages as Check;

final class List_Test
{
    /** Run the real executable; bounds failures are expected in separate cases. */
    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w']], $pipes);
        return proc_close($process);
    }
}
$root = getcwd() . '/fixed-list';
mkdir($root);
$manifest = $root . '/project.json';
file_put_contents($manifest, json_encode(['source_folders' => ['.'], 'entry' => 'main.phs']));
$definitions = <<<'PHS'
const STEP: int32 = 1;
const TEN: int32 = 10;
const BYTE: uint8 = 7;
template<int N>
struct list_i32 {
    public int32 $data[N];
    public int32 $size;
    public function append($value int32): void {
        $this->data[$this->size] = $value;
        $this->size = $this->size + STEP;
    }
    public const function get($index int): int32 { return $this->data[$index]; }
    public const function first(): int32 { return $this->get(0); }
    public const function capacity(): int { return N; }
    public const function count(): int32 { return $this->size; }
    // Only demanded method bodies are instantiated.
    public function unused(): void { $this->size = "unsupported assignment"; }
}
template<int N>
struct list_byte {
    public uint8 $data[N];
    public int32 $size;
    public function append($value uint8): void {
        $this->data[$this->size] = $value;
        $this->size = $this->size + STEP;
    }
    public const function get($index int): uint8 { return $this->data[$index]; }
    public const function first(): uint8 { return $this->get(0); }
    public const function capacity(): int { return N; }
    public const function count(): int32 { return $this->size; }
    // Only demanded method bodies are instantiated.
    public function unused(): void { $this->size = "unsupported assignment"; }
}
struct counter {
    public int32 $value;
    public function append($value int32): void { $this->value = $this->value + $value; }
    public const function count(): int32 { return $this->value; }
}
function count_list(const list_i32<16> &$value): int32 { return $value->count(); }
function count_counter(const counter &$value): int32 { return $value->count(); }
function read(const list_i32<16> &$value): int32 { return $value->first(); }
PHS;
$path = $root . '/definitions.phs';
file_put_contents($path, $definitions);
$main = <<<'PHS'
$a list_i32<16>;
$a->append(TEN);
$a->append(TEN + STEP);
$copy list_i32<16> = $a;
$a->data[0] = STEP;
$b list_byte<3>;
$b->append(BYTE);
$b->append(BYTE);
$b->append(BYTE);
$a->capacity();
$b->capacity();
$c counter;
$c->append(TEN);
if ($b->get(2)) {
    return read($copy) + $copy->get(1) + count_list($copy) + count_counter($c);
}
return 99;
PHS;
file_put_contents($root . '/main.phs', $main);
$session = new \compile\Compiler_Session();
$output = $root . '-program';
$first = $session->compile($manifest, $output);
Check::check(List_Test::run($output) === 33, 'Source lists use demanded methods, explicit receivers and independent inline copies');
$before = serialize($first);
$registry = $first->types->instances;
$method_contexts = [];
foreach ($registry->contexts as $context)
{
    if ($context->definition->owner_symbol_id === 0) {
        continue;
    }
    Check::check(($context->receiver_type !== null) && ($context->definition->name !== 'unused'), 'Only demanded methods acquire concrete receiver contracts');
    $signature = $first->types->signature_for($context->context_id);
    Check::check($signature->parameter_passing[0] === ($context->definition->receiver_const
        ? \type_model\argument_passing::borrow_const : \type_model\argument_passing::borrow_mutable), 'Receiver shares the ordinary parameter-passing contract');
    $method_contexts[$context->context_id] = $context;
}
Check::check(count($method_contexts) >= 7, 'Both template and ordinary owned method identities participate');
$export = $registry->to_array();
Check::check(count(array_filter($export['instances'], static fn($row) => $row['receiver'] !== null)) === count($method_contexts),
    'Instance exports retain concrete receiver provenance');
foreach ($method_contexts as $context)
{
    $definition = $context->definition->to_array();
    Check::check($definition['receiver_const'] === $context->definition->receiver_const,
        'Symbol exports retain the receiver contract');
    $resolution = $first->resolutions->for_symbol($context->definition->symbol_id);
    Check::check(count($resolution->to_array()['members']) === count($resolution->members),
        'Resolution exports retain deferred member requests');
}

// Prove selected member workers and complete join acceptance independently of arrival order.
$uses = $registry->uses;
$tasks = [];
foreach ($registry->contexts as $context) {
    foreach ($first->resolutions->for_symbol($context->definition->symbol_id)->members as $use) {
        $tasks[] = new \instantiate\member_task($context, $use);
        unset($uses[$context->context_id][$use->use_node_id]);
    }
}
$input = new \instantiate\Instance_Set($registry->contexts, $uses, $registry->concrete_types, $registry->constants,
    $registry->keys, $registry->next_id, $registry->constant_owners, lineage: $first->types->types->lineage, templates: $registry->template_checks());
$fixed = serialize([$input, $first]);
$view = new \resolve_types\Definition_View($first->types->catalog, $first->types->types);
$results = array_map(static fn($task) => \instantiate\Member_Worker::run($task, $first->resolutions, $view,
    $input, $first->symbols->current), $tasks);
$candidate = new \instantiate\Instance_Store($input);
$join = new \instantiate\Member_Join($candidate, $tasks, clone $first->types->types, $first->resolutions,
    $view, $first->symbols->current, $registry);
$accepted = $join->join(array_reverse($results))->snapshot();
Check::check($accepted->uses === $registry->uses, 'Reversed member completion preserves exact call targets');
$accepted_state = serialize($candidate->snapshot());
Check::rejects(static fn() => $join->join(array_slice($results, 1)), 'Incomplete');
Check::rejects(static fn() => $join->join([...$results, $results[0]]), 'duplicate');
Check::check((serialize([$input, $first]) === $fixed) && (serialize($candidate->snapshot()) === $accepted_state),
    'Member workers preserve fixed inputs; rejected joins do not adopt partial candidate results');

// A method-body edit changes execution but neither array contracts nor callable identities.
Check::edit($path, str_replace('return $this->data[$index];', 'return $this->data[0];', $definitions));
$second = $session->compile($manifest, $output);
Check::check(List_Test::run($output) === 32, 'A method body replacement changes its demanded implementations');
Check::check(!$second->inputs->context->full_rebuild, 'A method body edit is a supported selective update');
Check::check(($second->backend->layouts === $first->backend->layouts) && (serialize($first) === $before), 'Method replacement preserves layout and retained snapshots');
Check::check($second->types->instances->keys === $registry->keys, 'Method specialization IDs survive the body replacement');

$invalid = [
    ['function bad(const list_i32<16> &$a): void { $a->append(TEN); } return 0;', 'const reference'],
    ['$a list_i32<16>; $a->append(BYTE); return 0;', 'conversion'],
    ['$a list_i32<16>; $a->missing(); return 0;', 'Unknown'],
    ['$a list_i32<16>; $a->get(); return 0;', 'argument count'],
    ['$a list_i32<16>; $a->count(1); return 0;', 'argument count'],
];
foreach ($invalid as [$source, $reason]) {
    Check::edit($root . '/main.phs', $source);
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session())->compile($manifest), $reason);
    Check::check($error instanceof \diagnostics\Source_Error, 'Invalid member use produces a source diagnostic');
}
Check::edit($root . '/main.phs', '$a list_byte<3>; $a->append(BYTE); $a->append(BYTE); $a->append(BYTE); $a->append(BYTE); return 0;');
(new \compile\Compiler_Session())->compile($manifest, $output);
Check::check(List_Test::run($output) !== 0, 'Appending beyond capacity stops at the normal array boundary');
echo "fixed list ok: source methods, const/mutable receivers, demanded concrete implementations, private joins, templated capacities and one method-body replacement\n";
