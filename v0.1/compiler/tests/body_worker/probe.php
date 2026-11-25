<?php
declare(strict_types=1);
namespace body_worker_test;
final class Probe {
    public static function snapshot(\body_worker_fixture\Fixture $f): \resolve_types\Type_Resolution {
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
        $families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
        return new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,$signatures,$locals,$f->reader->annotations->names,$f->instances->snapshot($bindings),$families);
    }
    private static function details(\check_bodies\Checked_Body $body, string $name): bool {
        if ($name === 'two_arguments') {
            $results /** vector<int> */ = [3,6,7]; $starts /** vector<int> */ = [2,4,0];
            for ($i = 0; $i < 3; $i++) {
                $call = $body->call_for($i+1);
                if (($call->result_value_id !== $results[$i]) || ($call->argument_start !== $starts[$i]) || ($call->argument_count !== 2)) { return false; }
            }
            $arguments /** vector<int> */ = [3,6,1,2,4,5];
            for ($i = 0; $i < 6; $i++) { if ($body->argument_at($i)->value_id !== $arguments[$i]) { return false; } }
        }
        if ($name === 'retained_parameter_plan') {
            $results /** vector<int> */ = [3,5,6,0,10,11];
            for ($i = 0; $i < 6; $i++) { if ($body->call_for($i+1)->result_value_id !== $results[$i]) { return false; } }
            if (($body->argument_count() !== 8) || ($body->argument_for(3,1)->value_id !== 3) || ($body->argument_for(3,2)->value_id !== 5) || ($body->argument_for(6,1)->value_id !== 8) || ($body->argument_for(6,2)->value_id !== 10)) { return false; }
        }
        if (($name === 'array_read') || ($name === 'array_write') || ($name === 'nested_index') || ($name === 'index_call')) {
            $value = $body->value_for($body->value_count()); $place = $value->place();
            if (($place->size() !== 2) || ($place->at(0)->kind !== \check_bodies\PROJECTION_FIELD) || ($place->at(1)->kind !== \check_bodies\PROJECTION_INDEX) || ($body->place_type($place) !== $value->type_id)) { return false; }
        }
        if ($name === 'widen') {
            if (($body->value_for(1)->kind !== \check_bodies\VALUE_LOCAL_READ) || ($body->value_for(2)->kind !== \check_bodies\VALUE_CONVERSION) || ($body->conversion_for(2)->input_value_id !== 1)) { return false; }
        }
        if (($name === 'addition') || ($name === 'less')) {
            $operation = $body->operation_for(3);
            if (($operation->left !== 1) || ($operation->right !== 2)) { return false; }
        }
        if ($name === 'unused_parameter') { if ($body->value_for(1)->kind !== \check_bodies\VALUE_LOCAL_BORROW) { return false; } }
        if ($name === 'void_call') { if ($body->call_for(1)->result_value_id !== 0) { return false; } }
        if ($name === 'nested_block') {
            if (($body->scope_for(1)->statement_start !== 0) || ($body->scope_for(1)->statement_count !== 2) || ($body->scope_for(2)->statement_start !== 0) || ($body->scope_for(2)->statement_count !== 1)) { return false; }
        }
        if (($name === 'record_default') || ($name === 'record_construct') || ($name === 'record_copy') || ($name === 'record_assignment')) {
            if ($body->statement_at(0)->write_kind !== \check_bodies\WRITE_ZERO_INITIALIZE) { return false; }
            $last = $body->value_for($body->value_count());
            if (($last->kind !== \check_bodies\VALUE_LOCAL_READ) || ($last->place()->size() !== 1)) { return false; }
            $projection = $last->place()->at(0);
            if (($projection->kind !== \check_bodies\PROJECTION_FIELD) || ($projection->operand !== 0)) { return false; }
            if ($body->place_type($last->place()) !== $last->type_id) { return false; }
        }
        if ($name === 'record_copy') { if ($body->statement_at(1)->write_kind !== \check_bodies\WRITE_VALUE_COPY) { return false; } }
        if ($name === 'record_assignment') { if ($body->statement_at(2)->write_kind !== \check_bodies\WRITE_VALUE_COPY) { return false; } }
        if (($name === 'owned_local_return') || ($name === 'owned_fresh_return') || ($name === 'owned_borrow_return')) {
            if ($body->statement_at($body->statement_count()-1)->return_mode !== \check_bodies\RETURN_STORE) { return false; }
        }
        if (($name === 'const_record_call') || ($name === 'mutable_record_call')) {
            $passing = \type_model\PASS_BORROW_CONST;
            if ($name === 'mutable_record_call') { $passing = \type_model\PASS_BORROW_MUTABLE; }
            if (($body->argument_at(0)->passing !== $passing) || ($body->value_for(2)->kind !== \check_bodies\VALUE_LOCAL_BORROW)) { return false; }
        }
        return true;
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $case_data = $cases->at($ci); $f = \body_worker_fixture\Fixture::prepare($case_data->member('source')->text());
            $snapshot = Probe::snapshot($f); $names = $f->reader->annotations->bindings($f->input->owner);
            $worker = \check_bodies\Body_Worker::prepare($f->input,$names,$snapshot);
            $error_text = ''; $valid = true;
            $before_types = $f->types->type_count(); $before_shapes = $f->types->representation_count(); $before_members = $f->types->member_count();
            try {
                $body = $worker->check();
                if (($body->statement_count() !== $case_data->member('statements')->integer()) || ($body->call_count() !== $case_data->member('calls')->integer()) || ($body->value_count() !== $case_data->member('values')->integer()) || ($body->falls_through !== $case_data->member('fall')->boolean())) { $valid = false; }
                if ($body->scope_count() !== $names->scopes_count()) { $valid = false; }
                if (!Probe::details($body,$case_data->member('name')->text())) { $valid = false; }
                for ($i = 0; $i < $body->statement_count(); $i++) {
                    $statement = $body->statement_at($i);
                    $order = \check_bodies\Expression_Order::steps($body,$statement->value_id,$statement->call_start,$statement->call_start + $statement->call_count);
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
