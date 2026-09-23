<?php
declare(strict_types=1);
namespace body_language_test;
final class Probe {
    public static function snapshot(\body_language_fixture\Fixture $f): \resolve_types\Type_Resolution {
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
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $case_data = $cases->at($ci); $f = \body_language_fixture\Fixture::prepare($case_data->member('source')->text(),$case_data->member('default')->boolean());
            $snapshot = Probe::snapshot($f); $names = $f->reader->annotations->bindings($f->input->owner);
            $worker = \check_bodies\Body_Worker::prepare($f->input,$names,$snapshot);
            $error_text = ''; $valid = true;
            $before_types = $f->types->type_count(); $before_shapes = $f->types->representation_count(); $before_members = $f->types->member_count();
            try {
                $body = $worker->check();
                if (($body->statement_count() !== $case_data->member('statements')->integer()) || ($body->call_count() !== $case_data->member('calls')->integer()) || ($body->value_count() !== $case_data->member('values')->integer()) || ($body->falls_through !== $case_data->member('fall')->boolean())) { $valid = false; }
                if ($body->scope_count() !== $names->scopes_count()) { $valid = false; }
                $byte_index = 0; $expected_bytes = $case_data->member('bytes');
                for ($i = 1; $i < $body->value_count()+1; $i++) {
                    $value = $body->value_for($i);
                    if ($value->kind === \check_bodies\VALUE_BYTE_LITERAL) {
                        if ($byte_index >= $expected_bytes->size()) { $valid = false; }
                        else { if ($value->byte_literal()->to_json() !== $expected_bytes->at($byte_index)->text()) { $valid = false; } }
                        $byte_index = $byte_index + 1;
                    }
                }
                if ($byte_index !== $expected_bytes->size()) { $valid = false; }
                $targets = $case_data->member('targets'); $arguments = $case_data->member('arguments');
                if ($targets->size() !== $body->call_count()) { $valid = false; }
                else {
                    for ($i = 0; $i < $targets->size(); $i++) {
                        $call = $body->call_for($i+1);
                        if ($body->argument_for($i+1,1)->value_id !== $arguments->at($i)->integer()) { $valid = false; }
                        $signature = $snapshot->for_callable($call->target_callable_id);
                        if ($signature === null) { $valid = false; }
                        else {
                            $external = $signature->external;
                            if ($external === null) { $valid = false; }
                            else { if ($external->id !== $targets->at($i)->text()) { $valid = false; } }
                            if ($body->signature_for($call->target_callable_id)->external !== $external) { $valid = false; }
                            if (($call->argument_count !== 1) || ($body->argument_for($i+1,1)->passing !== $snapshot->signature_for($call->target_callable_id)->parameter_passing(0))) { $valid = false; }
                        }
                    }
                }
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
