<?php
declare(strict_types=1);
namespace body_storage_test;
final class Probe {
    public static function snapshot(\body_storage_fixture\Fixture $f): \resolve_types\Type_Resolution {
        for ($i = 0; $i < $f->catalog->size(); $i++) { \resolve_types\Type_Cache::materialize($f->types,$f->catalog->definition_at($i)); }
        $none /** vector<\resolve_types\Callable_Signature> */ = [];
        $old = new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context),$none);
        $tasks = \resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$old,$f->prepared,false);
        $requests /** vector<\resolve_types\Signature_Request> */ = [];
        foreach ($tasks as $task) { $requests[] = \resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$task,$f->entry,$f->prepared); }
        $signatures = (new \resolve_types\Signature_Join($f->symbols,$f->reader,$f->types,$old,$tasks,$f->entry,$f->prepared))->join($requests);
        $history = new \resolve_types\Local_Type_Validity();
        $local_tasks = \resolve_types\Local_Type_Resolver::select($f->symbols,$f->reader,$f->types,$history,false,$f->entry);
        $local_requests /** vector<\resolve_types\Local_Type_Request> */ = [];
        foreach ($local_tasks as $task) { $local_requests[] = \resolve_types\Local_Type_Resolver::resolve($f->symbols,$f->reader,$task); }
        $locals = (new \resolve_types\Local_Type_Join($f->symbols,$f->reader,$f->types,$history,$local_tasks,$f->entry,$signatures))->join($local_requests);
        $bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [];
        for ($i = 0; $i < $f->reader->annotations->names->size(); $i++) { $bindings[] = $f->reader->annotations->names->at($i); }
        $families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
        return new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,$signatures,$locals,$f->reader->annotations->names,$f->instances->snapshot($bindings),$families);
    }
    private static function place(\check_bodies\Checked_Body $body, \check_bodies\Place $place): bool {
        $type = $body->local_type_for($place->local_id); $has_element = false;
        for ($i = 0; $i < $place->size(); $i++) {
            $projection = $place->at($i); $definition = $body->definition_for($type);
            if ($projection->kind === \check_bodies\PROJECTION_ELEMENT) {
                $storage = $definition->element_storage;
                if ($storage === null) { return false; }
                if ($projection->type_id !== $storage->element_type) { return false; }
                $index = $body->value_for($projection->operand);
                if ($body->definition_for($index->type_id)->representation->kind() !== \type_model\REPRESENTATION_INTEGER) { return false; }
                $has_element = true;
            }
            $type = $projection->type_id;
        }
        return ($body->place_type($place) === $type) && ($place->allocation_backed() === $has_element);
    }
    private static function details(\check_bodies\Checked_Body $body, \resolve_types\Type_Resolution $snapshot, string $name): bool {
        for ($i = 1; $i < $body->value_count()+1; $i++) {
            $value = $body->value_for($i);
            if (($value->kind === \check_bodies\VALUE_LOCAL_READ) || ($value->kind === \check_bodies\VALUE_LOCAL_BORROW)) {
                if (!Probe::place($body,$value->place())) { return false; }
            }
        }
        for ($i = 0; $i < $body->statement_count(); $i++) {
            $target = $body->statement_at($i)->target;
            if ($target !== null) { if (!Probe::place($body,$target)) { return false; } }
        }
        for ($i = 1; $i < $body->call_count()+1; $i++) {
            $call = $body->call_for($i); $signature = $snapshot->for_callable($call->target_callable_id);
            if ($signature === null) { return false; }
            $storage = $signature->storage;
            if ($storage !== null) {
                if ($body->signature_for($call->target_callable_id)->storage !== $storage) { return false; }
                if ($body->allocation_effect_for($call->target_callable_id) !== $storage->allocation_effect()) { return false; }
                $owner = $body->argument_for($i,1); $passing = \type_model\PASS_BORROW_MUTABLE;
                if ($storage->role === \type_model\STORAGE_COUNT) { $passing = \type_model\PASS_BORROW_CONST; }
                if (($owner->passing !== $passing) || ($body->value_for($owner->value_id)->kind !== \check_bodies\VALUE_LOCAL_BORROW)) { return false; }
            }
        }
        if ($name === 'index_before_rhs') {
            $target = $body->statement_at(1)->target; if ($target === null) { return false; }
            if (($target->size() !== 1) || ($target->at(0)->kind !== \check_bodies\PROJECTION_ELEMENT)
                || ($target->at(0)->operand !== 2) || ($target->at(0)->call_end !== 1)) { return false; }
            $first = $snapshot->for_callable($body->call_for(1)->target_callable_id);
            $second = $snapshot->for_callable($body->call_for(2)->target_callable_id);
            if (($first === null) || ($second === null)) { return false; }
            if (($first->input->owner->name !== 'index') || ($second->input->owner->name !== 'value')
                || ($body->statement_at(1)->value_id !== 3) || ($body->statement_at(1)->call_count !== 2)) { return false; }
        }
        if ($name === 'nested_index') {
            if (($body->value_for(3)->place()->at(0)->operand !== 2) || ($body->value_for(4)->place()->at(0)->operand !== 3)) { return false; }
        }
        if (($name === 'record_field') || ($name === 'record_field_write')) {
            $place = $body->value_for($body->value_count())->place();
            if (($place->size() !== 2) || ($place->at(0)->kind !== \check_bodies\PROJECTION_ELEMENT)
                || ($place->at(1)->kind !== \check_bodies\PROJECTION_FIELD) || ($place->at(1)->operand !== 0)) { return false; }
        }
        if (($name === 'record_borrow') || ($name === 'record_mutable_borrow')) {
            if (($body->value_for(3)->kind !== \check_bodies\VALUE_LOCAL_BORROW) || (!$body->value_for(3)->place()->allocation_backed())) { return false; }
        }
        if ($name === 'record_push') {
            if ($body->argument_for(1,2)->passing !== \type_model\PASS_BORROW_CONST) { return false; }
        }
        return true;
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $case_data = $cases->at($ci); $f = \body_storage_fixture\Fixture::prepare($case_data->member('source')->text());
            $snapshot = Probe::snapshot($f); $names = $f->reader->annotations->bindings($f->input->owner);
            $worker = \check_bodies\Body_Worker::prepare($f->input,$names,$snapshot);
            $error_text = ''; $valid = true;
            $before_types = $f->types->type_count(); $before_shapes = $f->types->representation_count(); $before_members = $f->types->member_count();
            try {
                $body = $worker->check();
                if (($body->statement_count() !== $case_data->member('statements')->integer()) || ($body->call_count() !== $case_data->member('calls')->integer()) || ($body->value_count() !== $case_data->member('values')->integer()) || ($body->falls_through !== $case_data->member('fall')->boolean())) { $valid = false; }
                if ($body->scope_count() !== $names->scopes_count()) { $valid = false; }

                if (!Probe::details($body,$snapshot,$case_data->member('name')->text())) { $valid = false; }
                for ($i = 0; $i < $body->statement_count(); $i++) {
                    $statement = $body->statement_at($i);
                    $call_start = $statement->call_start; $target = $statement->target;
                    if ($target !== null) {
                        for ($pi = 0; $pi < $target->size(); $pi++) {
                            $projection = $target->at($pi);
                            if ($projection->kind !== \check_bodies\PROJECTION_FIELD) {
                                $order = \check_bodies\Expression_Order::steps($body,$projection->operand,$call_start,$projection->call_end);
                                while ($order->next() !== null) {}
                                $call_start = $projection->call_end;
                            }
                        }
                    }
                    $order = \check_bodies\Expression_Order::steps($body,$statement->value_id,$call_start,$statement->call_start + $statement->call_count);
                    while ($order->next() !== null) {}
                }
            } catch (\RuntimeException $error) { $error_text = $error->getMessage(); }
            if ($error_text !== $case_data->member('error')->text()) { $valid = false; }
            if ($error_text !== '') {
                $diagnostic = $worker->diagnostic();
                if ($diagnostic === null) { $valid = false; }
                else { if (($diagnostic->path !== '/signatures.phs') || ($diagnostic->reason !== $error_text) || ($diagnostic->length < 1)) { $valid = false; } }
            }
            if (($before_types !== $f->types->type_count()) || ($before_shapes !== $f->types->representation_count()) || ($before_members !== $f->types->member_count())) { $valid = false; }
            $repeated = false;
            try { $worker->check(); } catch (\LogicException $error) { $repeated = $error->getMessage() === 'Body worker is one-shot'; }
            if (!$repeated) { $valid = false; }
            echo $valid ? "true\n" : "false\n";
        }
    }
}
