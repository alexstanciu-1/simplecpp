<?php
declare(strict_types=1);
namespace resource_locations_test;
final class Probe {
    public static function snapshot(\resource_locations_fixture\Fixture $f): \resolve_types\Type_Resolution {
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
    private static function path_text(array $path /** vector<int> */): string {
        $out = '['; $separator = ''; foreach ($path as $ordinal) { $out .= $separator . $ordinal; $separator = ','; } return $out . ']';
    }
    private static function body_inventory(string $source): string {
        $f = \resource_locations_fixture\Fixture::prepare($source); $snapshot = Probe::snapshot($f);
        $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$snapshot)->check();
        $locations = \analyze_lifetimes\Resource_Locations::locals($body); $parameters = \analyze_lifetimes\Resource_Locations::parameters($body);
        $out = '[['; $separator = '';
        // Publication order is local ID order, independent of native hash iteration.
        for ($id = 1; $id < $body->names->locals_count()+1; $id++) {
            foreach (\analyze_lifetimes\Resource_Locations::paths($body->definition_for($body->local_type_for($id))) as $path) {
                $location = new \analyze_lifetimes\Resource_Location($id,$path); $key = $location->key();
                if (!isset($locations[$key])) { throw new \LogicException('Missing resource local'); }
                if ($locations[$key]->key() !== $key) { throw new \LogicException('Incorrect resource local'); }
                $out .= $separator . json_quote($key); $separator = ',';
            }
        }
        $out .= '],['; $separator = '';
        foreach ($parameters as $parameter) {
            $out .= $separator.'['.$parameter->position.',['; $path_separator = '';
            for ($i = 0; $i < $parameter->size(); $i++) { $out .= $path_separator.json_quote(\analyze_lifetimes\Resource_Locations::path_key($parameter->at($i))); $path_separator = ','; }
            $out .= ']]'; $separator = ',';
        }
        $node = $body->statement_at(0)->source_node_id; $diagnostic = \analyze_lifetimes\Resource_Locations::diagnostic($body,$node,'resource probe');
        if (($diagnostic->path !== '/signatures.phs') || ($diagnostic->reason !== 'resource probe') || ($diagnostic->length < 1)) { throw new \LogicException('Lost resource diagnostic attribution'); }
        return $out . ']]';
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $case_data = $cases->at($ci); $kind = $case_data->member('kind')->text(); $result = 'false';
            try {
                if (($kind === 'project') || ($kind === 'overlaps')) {
                    $path /** vector<int> */ = []; $data = $case_data->member('path');
                    for ($i = 0; $i < $data->size(); $i++) { $path[] = $data->at($i)->integer(); }
                    $base = new \analyze_lifetimes\Resource_Location($case_data->member('local')->integer(),$path);
                    if ($kind === 'project') {
                        $suffix = $case_data->member('suffix')->text(); $projected = \analyze_lifetimes\Resource_Locations::project($base,$suffix);
                        $result = '['.json_quote($base->key()).','.json_quote($projected->key()).','.Probe::path_text($projected->path()).','.json_quote(\analyze_lifetimes\Resource_Locations::prefix(\analyze_lifetimes\Resource_Locations::path_key($path),$suffix)).']';
                        $copy = $base->path(); $copy[] = 99;
                        if ($base->size() !== q_count($path)) { throw new \LogicException('Resource path membership escaped'); }
                    } else {
                        $other /** vector<int> */ = []; $data = $case_data->member('other'); for ($i = 0; $i < $data->size(); $i++) { $other[] = $data->at($i)->integer(); }
                        $right = new \analyze_lifetimes\Resource_Location($case_data->member('right_local')->integer(),$other);
                        $result = \analyze_lifetimes\Resource_Locations::overlaps($base,$right) ? 'true' : 'false';
                    }
                } else if ($kind === 'endpoint') {
                    $parts = \analyze_lifetimes\Resource_Locations::parameter_parts($case_data->member('key')->text());
                    if (\analyze_lifetimes\Resource_Locations::parameter_key($parts->position,$parts->path) !== $case_data->member('key')->text()) { throw new \LogicException('Endpoint failed round trip'); }
                    $result = '['.$parts->position.','.json_quote($parts->path).']';
                } else if ($kind === 'distinct') {
                    $pair = \analyze_lifetimes\Resource_Locations::distinct_pair($case_data->member('left')->text(),$case_data->member('right')->text());
                    $result = '[['.json_quote($pair->left).','.json_quote($pair->right).'],'.json_quote(\analyze_lifetimes\Resource_Locations::distinct_key($pair)).']';
                } else if ($kind === 'place') {
                    $rows /** vector<\check_bodies\Place_Projection> */ = []; $data = $case_data->member('kinds');
                    for ($i = 0; $i < $data->size(); $i++) { $tag = $data->at($i)->integer(); $operand = $i; if ($tag !== \check_bodies\PROJECTION_FIELD) { $operand = 1; } $rows[] = new \check_bodies\Place_Projection($tag,$operand,1); }
                    $place = new \check_bodies\Place(4,$rows); $location = \analyze_lifetimes\Resource_Locations::place($place); $result = json_quote($location->key());
                } else { $result = Probe::body_inventory($case_data->member('source')->text()); }
            } catch (\LogicException $error) { $result = 'false'; }
            echo $result . "\n";
        }
    }
}
