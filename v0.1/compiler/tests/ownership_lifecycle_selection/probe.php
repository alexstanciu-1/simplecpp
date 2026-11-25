<?php
declare(strict_types=1);
namespace ownership_lifecycle_selection_test;
final class Probe {
    public static function snapshot(\ownership_lifecycle_fixture\Fixture $f): \resolve_types\Type_Resolution {
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
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $item = $cases->at($i); $f = \ownership_lifecycle_fixture\Fixture::prepare($item->member('mode')->text()); $types = Probe::snapshot($f);
            $rows /** vector<\check_bodies\Checked_Body> */ = [];
            foreach ($f->methods as $input) {
                $body = \check_bodies\Body_Worker::prepare($input,$f->reader->annotations->bindings($input->owner),$types)->check();
                if (!$item->member('missing')->boolean()) { $rows[] = $body; }
            }
            $bodies = new \check_bodies\Body_Set($rows); $message = 'accepted';
            try {
                $requests = (new \analyze_lifetimes\Ownership_Selection($bodies,$types->types))->select();
                $box = $types->types->find_type('Box',''); $matched = 0;
                foreach ($requests as $request) {
                    $subject = $request->lifecycle; if ($subject === null) { continue; }
                    if ($subject->type_id !== $box) { continue; }
                    $callable = 0; if (!take_nullable($callable,$subject->body_id)) { continue; }
                    $found = false;
                    foreach ($f->methods as $input) {
                        if ($input->callable_id === $callable) {
                            if (\resolve_types\Source_Lifecycle::role($input->owner) !== $subject->kind) { throw new \LogicException('Incorrect source lifecycle identity'); }
                            $found = true;
                        }
                    }
                    if (!$found) { throw new \LogicException('Unknown lifecycle body'); }
                    $dependencies = $request->dependencies();
                    if (q_count($dependencies) !== 1) { throw new \LogicException('Unexpected leaf lifecycle dependency'); }
                    if ($dependencies[0] !== 'body:' . $callable) { throw new \LogicException('Missing source lifecycle dependency'); }
                    $matched++;
                }
                if ($matched !== q_count($f->methods)) { throw new \LogicException('Missing custom lifecycle subject'); }
                $empty /** hash<\analyze_lifetimes\Ownership_Result> */ = [];
                $accepted = (new \analyze_lifetimes\Ownership_Queue($requests,$empty,false))->run();
                if (q_count($accepted) !== q_count($requests)) { throw new \LogicException('Incomplete lifecycle execution'); }
            } catch (\LogicException $error) { $message = $error->getMessage(); }
            echo json_quote($message) . "\n";
        }
    }
}
