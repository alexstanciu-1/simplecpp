<?php
declare(strict_types=1);

/*
 * Role: Resolve projected storage and permissions against fixed concrete types.
 * Used by: Body_Worker (private methods composed on this owner)
 * Call map: begin_place() -> local_writable(); advance_place() -> [action] request an index value
 * check_expression() resumes place cursors; check_place() drives statement destinations
 * Output: root/field/index locations, ordered index values and shared type dependencies.
 */
namespace check_bodies;

use parse\Syntax_Access;
use parse\syntax_kind;
use type_model\representation_kind;

trait Place_Checking
{
    /** Parameter permissions come from the same signature used by calls; ordinary locals own writable storage. */
    private function local_writable(int $local_id): bool
    {
        return ($this->types->signature_for($this->callable_id)->parameter_passing[$local_id - 1] ?? \type_model\argument_passing::value)
            !== \type_model\argument_passing::borrow_const;
    }

    /** Mutable borrowing needs permission through the complete place, not just its root binding. */
    private function place_writable(place $place): bool
    {
        if (!$this->local_writable($place->local_id)) {
            return false;
        }
        $type = $this->local_type($place->local_id);
        foreach ($place->projections as $projection) {
            if (($projection->kind === projection_kind::field)
                && !$this->types->types->field_for($type, $projection->operand)->writable) {
                return false;
            }
            $type = $projection->type_id;
        }
        return true;
    }

    /** Check a statement destination; nested index reads use the iterative expression engine. */
    private function check_place(int $id, bool $write): array
    {
        $cursor = $this->begin_place($id, $write);
        $index = $this->advance_place($cursor);
        while ($index !== 0) {
            $index = $this->advance_place($cursor, $this->check_expression($index));
        }
        return [new place($cursor->local_id, $cursor->projections), $cursor->type_id];
    }

    /** Resolve root permissions and retain a private cursor over the ordered projection syntax. */
    private function begin_place(int $id, bool $write): place_cursor
    {
        $tree = $this->owner->frontend->syntax;
        $root = Syntax_Access::place_root($tree, $id);
        $binding = $this->names->binding_for($root);
        $expected = $write ? \resolve_symbols\local_access::write : \resolve_symbols\local_access::read;
        if ($binding->access !== $expected) {
            throw new \LogicException('Inconsistent resolved location access');
        }
        if (($write) && !$this->local_writable($binding->local_id)) {
            $this->fail($id, 'Cannot write through a const reference parameter');
        }
        $nodes = [];
        for ($node = $id; $node !== $root; $node = $tree->nodes[$node - 1]->first_child_id) {
            $nodes[] = $node;
        }
        return new place_cursor($id, $binding->local_id, $this->local_type($binding->local_id),
            array_reverse($nodes), $write);
    }

    /** Advance fields or accept an evaluated index; return the next index syntax, zero when complete. */
    private function advance_place(place_cursor $cursor, ?int $value = null): int
    {
        $tree = $this->owner->frontend->syntax;
        while ($cursor->position < count($cursor->nodes))
        {
            $node_id = $cursor->nodes[$cursor->position];
            $node = $tree->nodes[$node_id - 1];
            $operand = $tree->nodes[$node->first_child_id - 1]->next_sibling_id;
            if ($node->kind === syntax_kind::field_expression)
            {
                $name = \collect_symbols\Declaration_Syntax::name_text($this->owner->frontend, $operand);
                $index = $this->types->types->field_index($cursor->type_id, $name);
                if ($index < 0) {
                    $this->fail($node_id, 'Unknown or unavailable field: ' . $name);
                }
                $field = $this->types->types->field_for($cursor->type_id, $index);
                if (($cursor->write) && !$field->writable) {
                    $this->fail($node_id, 'Field is read-only');
                }
                $cursor->type_id = $field->type_id;
                $cursor->projections[] = new place_projection(projection_kind::field, $index, $cursor->type_id);
            }
            else
            {
                $shape = $this->types->definition_for($cursor->type_id)->representation;
                $storage = $this->types->definition_for($cursor->type_id)->element_storage;
                if (($shape->kind !== representation_kind::fixed_array) && ($storage === null)) {
                    $this->fail($node_id, 'Indexed access requires a fixed array or typed storage');
                }
                if ($value === null) {
                    return $operand;
                }
                if (($value === 0) || ($this->types->definition_for($this->values[$value - 1]->type_id)->representation->kind !== representation_kind::integer)) {
                    $this->fail($operand, 'Array index must be an integer');
                }
                $this->select_place_access($value, false);
                $cursor->type_id = $storage?->element_type ?? $shape->payload->element_type;
                $cursor->projections[] = new place_projection($storage === null ? projection_kind::index : projection_kind::element, $value, $cursor->type_id, count($this->calls));
                $value = null;
            }
            ++$cursor->position;
            $type = $cursor->type_id;
            $this->retain_type($type);
        }
        return 0;
    }

    /** Resolve a method receiver without selecting its signature-defined access yet. */
    private function check_place_value(int $node_id): int
    {
        [$location, $type] = $this->check_place($node_id, false);
        return $this->append_value(new pending_place_value($node_id, $type, $location));
    }

    /** Preserve the resolved location until an argument, write or other consumer chooses its access. */
    private function finish_place_value(place_cursor $cursor): int
    {
        return $this->append_value(new pending_place_value($cursor->source_node_id, $cursor->type_id,
            new place($cursor->local_id, $cursor->projections)));
    }

    /** Complete a pending location exactly once; already selected accesses cannot be reclassified. */
    private function select_place_access(int $id, bool $borrow): void
    {
        if ($id === 0) {
            return;
        }
        $value = $this->values[$id - 1];
        $kind = $borrow ? value_kind::local_borrow : value_kind::local_read;
        if ($value instanceof pending_place_value) {
            $this->values[$id - 1] = new typed_value($value->source_node_id, $value->type_id, $kind, $value->location);
            --$this->pending_place_count;
        }
        elseif (in_array($value->kind, [value_kind::local_read, value_kind::local_borrow], true) && ($value->kind !== $kind)) {
            throw new \LogicException('Location access was already selected by another consumer');
        }
    }
}
