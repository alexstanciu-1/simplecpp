<?php
declare(strict_types=1);

/*
 * Role: Own typed values, statements and dependency recording.
 * Used by: Body_Checker::run()
 * Call map:
 *   Body_Worker::check()
 *     -> check_statements() [trait]; [action] assemble Checked_Body
 *   signature() / local_type() / append_value() -> retain_type() [implicit element closure]
 */

namespace check_bodies;

use parse\syntax_kind;
use type_model\representation_kind;

// Per-callable worker: fixed phase inputs, private cursor/output collections.
// Calls read signatures, never recursively check or wait for another body.
/**
 * @compiler-internal Checking implementation behind Body_Checker::run(); constructor/check are local
 * to this process. Private traversal/dependency builders are not cross-step APIs.
 */
class Body_Worker
{
    use Statement_Checking;
    use Control_Statement_Checking;
    use Expression_Checking;
    use Place_Checking;
    use Local_Write_Checking;

    private readonly \collect_symbols\symbol_record $owner;
    private readonly \resolve_symbols\Symbol_Resolution $names;
    private readonly \resolve_types\Type_Resolution $types;
    private readonly int $integer_literal_type;
    private readonly int $callable_id;
    private readonly ?\instantiate\instance_context $instance;
    private ?\resolve_types\Local_Types $local_types;

    /** @var list<typed_scope|null> Private placeholders are completed on block exit. */
    private array $scopes = [];

    /** @var list<typed_call> */
    private array $calls = [];

    /** @var list<typed_argument|null> Call ranges are reserved and filled before handoff. */
    private array $arguments = [];

    /** @var list<typed_value|pending_place_value> Private rows are completed at their consumer boundary. */
    private array $values = [];
    private int $pending_place_count = 0;

    /** @var list<typed_statement> */
    private array $statements = [];

    /** @var array<int, \type_model\type_record> */
    private array $type_dependencies = [];

    /** @var array<int, signature_dependency> */
    private array $signature_dependencies = [];

    /** @var array<string, array<int, array<int, \type_model\operation_contract>>> Selected contracts shared by this body. */
    private array $operation_contracts = [];

    /** @compiler-internal Initialize private state for one task; use the process entry point externally. */
    public function __construct(body_check_task $task)
    {
        $owner = $task->owner;
        $bindings = $task->names;
        $types = $task->types;
        if ($owner->is_template()) {
            ($types->instances ?? throw new \LogicException('Missing template instances'))->template_checks()
                ->require_definition($owner, $types->names, $types->catalog);
        }
        if (($bindings->symbol_id !== $owner->symbol_id) || ($bindings->syntax !== $owner->frontend->syntax)
            || (($bindings->scopes[0] ?? null)?->block_node_id !== $owner->body_node_id)) {
            throw new \LogicException('Body task requires current callable name bindings');
        }
        $signature = $types->for_callable($task->callable_id);
        if (($signature === null) || ($signature->instance !== $task->instance) || ($signature->syntax !== $owner->frontend->syntax)
            || ($signature->declaration_node_id !== $owner->declaration_node_id) || ($signature->body_node_id !== $owner->body_node_id)) {
            throw new \LogicException('Body task requires its current resolved callable contract');
        }
        $local_types = $types->locals_for($task->callable_id);
        if ((($bindings->locals !== []) && ($local_types?->names !== $bindings))
            || (($bindings->locals === []) && ($local_types !== null))) {
            throw new \LogicException('Body task requires current resolved local types');
        }
        $this->callable_id = $task->callable_id;
        $this->instance = $task->instance;
        $this->owner = $owner;
        $this->names = $bindings;
        $this->types = $types;
        $this->integer_literal_type = $types->integer_literal_type();
        $this->local_types = $local_types;
    }

    /** @compiler-internal Build a private typed callable result; callers outside check_bodies use Body_Checker. */
    public function check(): Checked_Body
    {
        $return_type = $this->signature($this->callable_id)->return_type;
        $void = $this->types->types->representation_for_type($return_type)->kind === representation_kind::void_type;
        $this->scopes = array_fill(0, count($this->names->scopes), null);
        $blocks = $this->check_statements($return_type, $void);
        $falls_through = false;
        foreach (Flow_Graph::reachable($blocks) as $block_id) {
            if ($blocks[$block_id - 1]->end === flow_end::fallthrough) {
                $falls_through = true;
            }
        }
        if (($falls_through) && (!$void)) {
            $this->fail($this->owner->body_node_id, 'Callable can finish without returning a value');
        }
        if ($this->pending_place_count !== 0) {
            throw new \LogicException('Checked body has unresolved location access');
        }
        return new Checked_Body($this->owner, $this->names, $this->values, $this->calls, $this->statements,
            $falls_through, $this->type_dependencies, $this->signature_dependencies, $this->local_types, $this->scopes, $this->arguments, $blocks, $this->instance);
    }

    /** Read a resolved signature and retain its exact type and provider dependencies for body reuse. */
    private function signature(int $callable_id): \type_model\signature_representation
    {
        $known = $this->signature_dependencies[$callable_id] ?? null;
        if ($known !== null) {
            return $known->representation->payload;
        }
        $signature = $this->types->for_callable($callable_id) ?? throw new \LogicException('Missing resolved function signature');
        $representation = $this->types->types->representation_by_id($signature->representation_id);
        if ($representation->kind !== representation_kind::function_signature) {
            throw new \LogicException('Expected function signature');
        }

        // Retain the exact signature and type rows that make this checked body reusable.
        $this->signature_dependencies[$callable_id] ??= new signature_dependency($callable_id, $signature->representation_id, $representation, $signature->external, $signature->storage);
        $type = $representation->payload->return_type;
        $this->retain_type($type);
        for ($i = 1; $i <= $representation->payload->count; ++$i) {
            $type = $this->types->parameter_type_for($callable_id, $i);
            $this->retain_type($type);
        }
        return $representation->payload;
    }

    /** Retain explicit types and their implicit element dependencies before handing a body to downstream stages. */
    private function retain_type(int $type_id): void
    {
        $pending = [$type_id];
        while ($pending !== [])
        {
            $id = array_pop($pending);
            if (isset($this->type_dependencies[$id])) {
                continue;
            }
            $record = $this->types->types->type_by_id($id);
            $this->type_dependencies[$id] = $record;
            $definition = $record->definition;

            // Prefix lifecycle uses the element even without a source expression reading that element.
            if ($definition->element_storage !== null) {
                $pending[] = $definition->element_storage->element_type;
            }
            if ($definition->representation->kind === representation_kind::fixed_array) {
                $pending[] = $definition->representation->payload->element_type;
            }
        }
    }

    /** Start a private traversal cursor for a resolved lexical block. */
    private function enter(int $block_id): body_cursor
    {
        $scope_id = $this->names->scope_for_block($block_id);
        $block = $this->owner->frontend->syntax->nodes[$block_id - 1];
        if ($block->kind !== syntax_kind::block) {
            throw new \LogicException('Expected callable block');
        }
        return new body_cursor($scope_id, count($this->statements), $block->first_child_id);
    }

    private function local_type(int $local_id): int
    {
        $id = ($this->local_types ?? throw new \LogicException('Missing local type associations'))->type_for($local_id);
        $this->retain_type($id);
        return $id;
    }

    /** Resolve an implicit conversion for this use and append a conversion value only when needed. */
    private function convert(int $value_id, int $destination, conversion_use $use, int $node_id): int
    {
        $role = match ($use) {
            conversion_use::return_value => 'return',
            conversion_use::initialization => 'initialization',
            conversion_use::assignment => 'assignment',
            conversion_use::argument => 'argument',
        };
        if ($value_id === 0) {
            $this->fail($node_id, ucfirst($role) . ' expression produces no value');
        }
        $source = $this->values[$value_id - 1]->type_id;
        $conversion = Conversion_Resolver::resolve($this->types, new conversion_request($source, $destination,
            \type_model\conversion_purpose::implicit_boundary));
        if ($conversion === null) {
            $this->fail($node_id, 'Unsupported implicit ' . $role . ' conversion from '
                . $this->types->definition_for($source)->name . ' to ' . $this->types->definition_for($destination)->name);
        }
        if ($conversion->form === conversion_form::identity) {
            return $value_id;
        }
        $this->select_place_access($value_id, false);
        return $this->append_value(new typed_value($node_id, $destination, value_kind::conversion,
                new conversion_value($value_id, $conversion->primitive)));
    }

    /** Append a private expression row; pending locations retain IDs while their consumers select access. */
    private function append_value(typed_value|pending_place_value $value): int
    {
        $this->retain_type($value->type_id);
        $this->values[] = $value;
        if ($value instanceof pending_place_value) {
            ++$this->pending_place_count;
        }
        return count($this->values);
    }

    private function fail(int $node_id, string $message): never
    {
        $node = $this->owner->frontend->syntax->nodes[$node_id - 1];
        $source = $this->owner->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length, $message);
    }
}
