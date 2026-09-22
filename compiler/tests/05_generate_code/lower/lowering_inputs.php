<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use prepare_backend\Backend_Context;
use prepare_backend\backend_configuration;
use prepare_backend\callable_binding;
use collect_symbols\symbol_kind;

class Lowering_Inputs_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    /** Require a malformed lowering input to fail with the expected diagnostic. */
    public static function rejects(callable $action, string $message): void
    {
        try {
            $action();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $message), $error->getMessage());
            return;
        }
        throw new Exception('Expected rejection: ' . $message);
    }
}

$session = new \compile\Compiler_Session();
$first = $session->compile('../fixtures/three_files/project.json');
$entry = $first->types->entry->symbol->symbol_id;
$answer = $first->symbols->current->find_symbol('answer', '', symbol_kind::function_symbol);
$value = $first->symbols->current->find_symbol('value', '', symbol_kind::function_symbol);
$body = $first->bodies->for_symbol($answer);
$signature = $body->signature_for($value);
Lowering_Inputs_Test::check(($signature === $body->signature_dependencies[$value]->representation->payload)
    && ($body->definition_for($signature->return_type) === $first->types->types->definition_for_type($signature->return_type)),
    'Accessors return the exact shared contracts');
Lowering_Inputs_Test::rejects(static fn() => $body->signature_for(0), 'No resolved signature');
Lowering_Inputs_Test::rejects(static fn() => $body->definition_for(0), 'No resolved type');
Lowering_Inputs_Test::rejects(static fn() => $first->lowering_input_for(0), 'No analyzed body');
$input = $first->lowering_input_for($entry);
$input->require_callables();
Lowering_Inputs_Test::check(($input->analysis === $first->lifetimes->for_symbol($entry)) && ($input->backend === $first->backend),
    'Lowering inputs share analysis and the independently prepared context');
$before = serialize($first);
$warm = $session->compile('../fixtures/three_files/project.json');
Lowering_Inputs_Test::check($input->is_current($warm->lifetimes->for_symbol($entry), $warm->backend) && (serialize($first) === $before),
    'Warm preparation preserves input identity and retained snapshots');
Lowering_Inputs_Test::rejects(static fn() => (new \lower\lowering_input($input->analysis, new Backend_Context()))->require_callables(), 'not configured');
Lowering_Inputs_Test::rejects(static fn() => (new \lower\lowering_input($input->analysis, new Backend_Context($first->backend->configuration)))->require_callables(), 'not prepared');

$config = $first->backend->configuration;
$bindings = [];
foreach ($first->bodies->bodies() as $checked) {
    $bindings[] = $first->backend->binding_for($checked->owner->symbol_id);
}
Lowering_Inputs_Test::check(Backend_Context::prepare(new Backend_Context(clone $config, array_reverse($bindings)), $first->backend) === $first->backend,
    'Equivalent snapshots canonicalize independently of binding order');
$fields = array_values(get_object_vars($config));
foreach (array_keys($fields) as $index)
{
    $changed = $fields;
    $changed[$index] .= '-changed';
    $different = new backend_configuration(...$changed);
    Lowering_Inputs_Test::rejects(static fn() => new Backend_Context($different, $bindings), 'different configuration');
    Lowering_Inputs_Test::check(!$input->is_current($input->analysis, Backend_Context::prepare(new Backend_Context($different), $first->backend)),
        'Every configuration fact participates in lowering reuse');
}
foreach (['link_name', 'linkage', 'calling_convention'] as $field)
{
    $binding = $bindings[0];
    $changed = $bindings;
    $changed[0] = new callable_binding($config, $binding->callable_id, $binding->signature, $binding->return_definition,
        $binding->link_name . ($field === 'link_name' ? '_changed' : ''),
        $binding->linkage . ($field === 'linkage' ? '_changed' : ''),
        $binding->calling_convention . ($field === 'calling_convention' ? '_changed' : ''));
    Lowering_Inputs_Test::check(Backend_Context::prepare(new Backend_Context($config, $changed), $first->backend) !== $first->backend,
        'Changed bindings cannot keep a previous context identity');
}
Lowering_Inputs_Test::rejects(static fn() => new Backend_Context(null, $bindings), 'require an explicit configuration');
Lowering_Inputs_Test::rejects(static fn() => new Backend_Context($config, [...$bindings, $bindings[0]]), 'Duplicate');
$collision = new callable_binding($config, $bindings[1]->callable_id, $bindings[1]->signature, $bindings[1]->return_definition,
    $bindings[0]->link_name, $bindings[1]->linkage, $bindings[1]->calling_convention);
Lowering_Inputs_Test::rejects(static fn() => new Backend_Context($config, [$bindings[0], $collision]), 'Duplicate');
Lowering_Inputs_Test::rejects(static fn() => new backend_configuration('', 't', 'd', '', '', 'a', 'r'), 'requires explicit');
$fresh = (new \compile\Compiler_Session())->compile('../fixtures/three_files/project.json');
Lowering_Inputs_Test::rejects(static fn() => (new \lower\lowering_input($fresh->lifetimes->for_symbol($fresh->types->entry->symbol->symbol_id),
            $first->backend))->require_callables(), 'Stale backend callable contract');
echo "lowering inputs ok: shared contract access, frozen context, missing/stale contracts, all configuration dependencies, binding changes and lineage\n";
