<?php
declare(strict_types=1);
namespace body_calls_test;
final class Probe {
    public static function snapshot(\body_calls_fixture\Fixture $f): \resolve_types\Type_Resolution {
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
    private static function calls(\check_bodies\Checked_Body $body, \body_calls_fixture\Fixture $f, \resolve_types\Type_Resolution $snapshot, int $receiver_mode, int $receiver_local): bool {
        $seen /** hash<bool,int> */ = [];
        for ($i = 1; $i < $body->call_count()+1; $i++) {
            $call = $body->call_for($i); $input = $snapshot->for_callable($call->target_callable_id);
            if ($input === null) { throw new \LogicException('Missing accepted call signature'); }
            $instance = $input->input->instance;
            if ($instance === null) { throw new \LogicException('Expected concrete call instance'); }
            if ($f->instances->context_for($call->target_callable_id) !== $instance) { return false; }
            $shape = $snapshot->signature_for($call->target_callable_id);
            if ($call->argument_count !== $shape->member_count()) { return false; }
            if ($receiver_mode !== 0) {
                $receiver = $input->receiver_index;
                if ($receiver === null) { return false; }
                $argument = $body->argument_at($call->argument_start + $receiver);
                if (($argument->passing !== $receiver_mode) || ($body->value_for($argument->value_id)->kind !== \check_bodies\VALUE_LOCAL_BORROW)) { return false; }
                if ($body->value_for($argument->value_id)->place()->local_id !== $receiver_local) { return false; }
            }
            $seen[$call->target_callable_id] = true;
        }
        foreach ($snapshot->instances->functions() as $instance) {
            if (!$instance->definition->is_source()) { continue; }
            $names = $f->reader->annotations->bindings($instance->definition);
            $worker = \check_bodies\Body_Worker::prepare(new \resolve_types\Callable_Input($instance->definition,$instance),$names,$snapshot);
            $checked = $worker->check();
            if (($checked->callable_id !== $instance->context_id) || ($checked->input->instance !== $instance)) { return false; }
            if ($instance->definition->name === 'literal') {
                if (($checked->value_count() !== 1) || ($checked->value_for(1)->integer_text() !== '7')) { return false; }
            }
            for ($i = 0; $i < $checked->statement_count(); $i++) {
                $statement = $checked->statement_at($i);
                $order = \check_bodies\Expression_Order::steps($checked,$statement->value_id,$statement->call_start,$statement->call_start+$statement->call_count);
                while ($order->next() !== null) {}
            }
        }
        return true;
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $case_data = $cases->at($ci); $f = \body_calls_fixture\Fixture::prepare($case_data->member('source')->text());
            $snapshot = Probe::snapshot($f); $names = $f->reader->annotations->bindings($f->input->owner);
            $worker = \check_bodies\Body_Worker::prepare($f->input,$names,$snapshot);
            $error_text = ''; $valid = true;
            $before_types = $f->types->type_count(); $before_shapes = $f->types->representation_count(); $before_members = $f->types->member_count();
            try {
                $body = $worker->check();
                if (($body->statement_count() !== $case_data->member('statements')->integer()) || ($body->call_count() !== $case_data->member('calls')->integer()) || ($body->value_count() !== $case_data->member('values')->integer()) || ($body->falls_through !== $case_data->member('fall')->boolean())) { $valid = false; }
                if ($body->scope_count() !== $names->scopes_count()) { $valid = false; }

                if (!Probe::calls($body,$f,$snapshot,$case_data->member('receiver')->integer(),$case_data->member('receiver_local')->integer())) { $valid = false; }
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
