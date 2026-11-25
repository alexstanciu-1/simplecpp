<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';
use Body_Test_Stages as Check;

final class Preparation_Test
{
    /** Re-run only concrete preparation against fixed semantic inputs, with a private type candidate. */
    public static function prepare(\compile\Compile_Result $program, bool $full): \resolve_types\Concrete_Preparation
    {
        $types = $full ? new \type_model\Type_Store($program->types->types->context) : clone $program->types->types;
        $tasks = \resolve_types\Record_Preparation::select($program->symbols->current,
            $program->types->catalog, $types, $full, $program->resolutions);
        $step = new \resolve_types\Concrete_Preparation($program->symbols->current, $program->resolutions,
            $program->types->catalog, $types, $full ? null : $program->types->instances, $full, ordinary_records: $tasks);
        $step->init();
        $step->run();
        $step->finalize();
        return $step;
    }
}

$root = getcwd() . '/concrete-preparation';
mkdir($root);
$manifest = $root . '/project.json';
file_put_contents($manifest, json_encode(['source_folders' => ['.'], 'entry' => 'main.phs']));
$source = $root . '/main.phs';
$definitions = $root . '/definitions.phs';
file_put_contents($source, '');
file_put_contents($definitions, '');

// Ordinary concrete contracts and bodies are checked independently of calls.
foreach ([
    ['public function bad($v void): int { return 1; }', 'void has no value'],
    ['public function bad(): int { return; }', 'return'],
    ['public function bad(): void { $this->value = "wrong"; }', 'byte-literal construction'],
] as [$method, $reason])
{
    Check::edit($source, 'return 0;');
    Check::edit($definitions, 'struct row { public int32 $value; ' . $method . ' }');
    $error = Check::rejects(static fn() => (new \compile\Compiler_Session())->compile($manifest), $reason);
    Check::check($error instanceof \diagnostics\Source_Error, 'Unused ordinary methods produce source diagnostics');
}

// Each demanded generic method discovers exactly one successor; the graph is linear.
$depth = 128;
$methods = [];
for ($i = 0; $i < $depth; ++$i) {
    $result = $i + 1 === $depth ? '0' : '$this->m' . ($i + 1) . '()';
    $methods[] = 'public const function m' . $i . '(): int { return ' . $result . '; }';
}
Check::edit($definitions, 'template<typename T> struct chain { public T $value; ' . implode("\n", $methods)
    . ' public function unused($v void): int { return; } }'
    . ' struct ordinary { public int32 $value; public const function unused(): int { return 1; } }'
    . ' template<typename T> function accept(const T &$v): int { return 0; }');
$main = '$v chain<int32>; $o ordinary; accept<ordinary>($o); return $v->m0();';
Check::edit($source, $main);
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest);
$before = serialize($first);
$step = Preparation_Test::prepare($first, true);
$counts = $step->work_counts();
Check::check(($counts['members'] === $depth + 1) && ($counts['records'] === 2)
    && ($counts['applications'] <= 3), 'Readiness selects each chain member once; ordinary type arguments resume after their layout');
$ordinary = array_filter($step->result()->functions(), static fn($context) => !$context->definition->is_template());
Check::check(count($ordinary) === 1, 'Only the ordinary unused method is concrete; the dependent invalid method remains undemanded');
// Ordinary declarations use the same private worker and validated member acceptance protocol.
$context = array_values($ordinary)[0];
$owner = $first->symbols->current->symbol_by_id($context->definition->owner_symbol_id);
$task = new \instantiate\member_task(new \instantiate\instance_context($owner), declaration: $context->definition);
$view = new \resolve_types\Definition_View($first->types->catalog, $first->types->types);
$result = \instantiate\Member_Worker::run($task, $first->resolutions, $view, $first->types->instances, $first->symbols->current);
$candidate = new \instantiate\Instance_Store($first->types->instances);
$join = new \instantiate\Member_Join($candidate, [$task], clone $first->types->types, $first->resolutions,
    $view, $first->symbols->current, $first->types->instances);
$join->join([$result]);
$fixed = serialize($candidate->snapshot());
$forged = new \instantiate\member_result($task, clone $result->receiver, $result->definition, $result->arguments);
Check::rejects(static fn() => $join->join([$forged]), 'stale member');
Check::check(serialize($candidate->snapshot()) === $fixed, 'Forged declaration results leave the private candidate unchanged');

$warm = Preparation_Test::prepare($first, false);
Check::check(($warm->work_counts()['applications'] === 0) && ($warm->work_counts()['members'] === 0)
    && ($warm->work_counts()['records'] === 0), 'Unchanged application, record and ordinary/template member work is reused before computation');

// One body update removes a demand without mutating retained contexts or method bodies.
Check::edit($source, str_replace('m0()', 'm1()', $main));
$second = $session->compile($manifest);
Check::check(!$second->inputs->context->full_rebuild, 'Method demand changes in an entry body stay incremental');
foreach ($first->types->instances->functions() as $context)
{
    if ($context->definition->name === 'm0') {
        Check::check($second->types->instances->context_for($context->context_id) === null, 'Removed demand drops current membership');
    }
    else {
        Check::check(($second->types->instances->context_for($context->context_id) === $context)
            && ($second->bodies->for_callable($context->context_id) === $first->bodies->for_callable($context->context_id)),
            'Unchanged concrete implementations and bodies retain exact identity');
    }
}
Check::check(serialize($first) === $before, 'Preparation, exports and one increment preserve the prior snapshot');
echo "concrete preparation ok: ordinary diagnostics, deferred dependent methods, indexed readiness, selected reuse and one pure demand replacement\n";
