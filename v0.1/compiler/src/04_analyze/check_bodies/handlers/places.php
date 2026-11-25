<?php
declare(strict_types=1);
namespace check_bodies;
trait Place_Checking {
    private function local_writable(int $local): bool {
        $shape = $this->context->types->signature_for($this->context->input->callable_id);
        if ($local > $shape->member_count()) { return true; }
        return $shape->parameter_passing($local - 1) !== \type_model\PASS_BORROW_CONST;
    }
    private function place_writable(Place $place): bool {
        if (!$this->local_writable($place->local_id)) { return false; }
        $type = $this->context->local_type($place->local_id);
        for ($i = 0; $i < $place->size(); $i++) {
            $projection = $place->at($i);
            if ($projection->kind === \check_bodies\PROJECTION_FIELD) {
                if (!$this->context->types->types->field_for($type,$projection->operand)->writable) { return false; }
            }
            $type = $projection->type_id;
        }
        return true;
    }
    private function begin_place(int $id, bool $write): Place_Cursor {
        $tree = $this->tree(); $root = \parse\Syntax_Access::place_root($tree,$id);
        $binding = $this->context->names->binding_for($root);
        $expected = $write ? \resolve_symbols\LOCAL_WRITE : \resolve_symbols\LOCAL_READ;
        if ((int)$binding->access !== $expected) { throw new \LogicException('Inconsistent resolved location access'); }
        if ($write) { if (!$this->local_writable((int)$binding->local_id)) { $this->fail($id,'Cannot write through a const reference parameter'); } }
        $reverse /** vector<int> */ = [];
        for ($node = $id; $node !== $root; $node = (int)$tree->row($node)->first_child) { $reverse[] = $node; }
        $nodes /** vector<int> */ = [];
        for ($i = q_count($reverse); $i > 0; $i = $i - 1) { $nodes[] = $reverse[$i - 1]; }
        return new Place_Cursor($id,(int)$binding->local_id,$this->context->local_type((int)$binding->local_id),$nodes,$write);
    }
    /** Negative value means no index has been evaluated; zero is an evaluated void. */
    private function advance_place(Place_Cursor $cursor, int $value): int {
        $tree = $this->tree();
        while ($cursor->position < q_count($cursor->nodes)) {
            $node_id = $cursor->nodes[$cursor->position]; $node = $tree->row($node_id);
            $operand = (int)$tree->row((int)$node->first_child)->next_sibling;
            if ((int)$node->kind === \parse\SYNTAX_FIELD_EXPRESSION) {
                $name = \collect_symbols\File_Collector::name_text($this->context->input->owner->source_frontend(),$operand);
                $index = $this->context->types->types->field_index($cursor->type,$name);
                if ($index < 0) { $this->fail($node_id,'Unknown or unavailable field: ' . $name); }
                $field = $this->context->types->types->field_for($cursor->type,$index);
                if ($cursor->write) { if (!$field->writable) { $this->fail($node_id,'Field is read-only'); } }
                $cursor->type = $field->type_id;
                $cursor->projections[] = new Place_Projection(\check_bodies\PROJECTION_FIELD,$index,$cursor->type);
            } else {
                $definition = $this->context->types->definition_for($cursor->type); $shape = $definition->representation; $storage = $definition->element_storage;
                if (($shape->kind() !== \type_model\REPRESENTATION_ARRAY) && ($storage === null)) { $this->fail($node_id,'Indexed access requires a fixed array or typed storage'); }
                if ($value < 0) { return $operand; }
                if ($value === 0) { $this->fail($operand,'Array index must be an integer'); }
                if ($this->context->types->definition_for($this->output->value_for($value)->type_id)->representation->kind() !== \type_model\REPRESENTATION_INTEGER) { $this->fail($operand,'Array index must be an integer'); }
                $this->output->select_place_access($value,false);
                $kind = \check_bodies\PROJECTION_INDEX;
                if ($storage === null) { $cursor->type = $shape->element(); }
                else { $cursor->type = $storage->element_type; $kind = \check_bodies\PROJECTION_ELEMENT; }
                $cursor->projections[] = new Place_Projection($kind,$value,$cursor->type,$this->output->call_count());
                $value = -1;
            }
            $cursor->position++; $this->context->retain_type($cursor->type);
        }
        return 0;
    }
    private function check_place(int $id, bool $write): Place_Cursor {
        $cursor = $this->begin_place($id,$write); $index = $this->advance_place($cursor,-1);
        while ($index !== 0) { $index = $this->advance_place($cursor,$this->check_expression($index,0)); }
        return $cursor;
    }
}
