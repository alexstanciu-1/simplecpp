<?php
declare(strict_types=1);
namespace ownership_selection_test;
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
    private static function baseline(): void {
        $f = \resource_locations_fixture\Fixture::prepare('function f(const Nested &$n): int { return 0; } return 0;');
        $box_definition = $f->types->definition_for_type($f->types->find_type('Box',''));
        $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('first',\type_model\Field_Type::named($box_definition),true),new \type_model\Field_Declaration('second',\type_model\Field_Type::named($box_definition),true)];
        $twin = \resolve_types\Record_Definitions::materialize($f->types,new \type_model\Record_Declaration('Twin','',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0));
        $types = Probe::snapshot($f);
        $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$types)->check();
        $rows /** vector<\check_bodies\Checked_Body> */ = [$body]; $bodies = new \check_bodies\Body_Set($rows);
        $requests = (new \analyze_lifetimes\Ownership_Selection($bodies,$types->types))->select();
        $seen /** hash<bool> */ = [];
        foreach ($requests as $request) { $seen[$request->key] = true; }
        foreach ($requests as $request) { foreach ($request->dependencies() as $key) { if (!isset($seen[$key])) { throw new \LogicException('Unselected dependency'); } } }
        $empty /** hash<\analyze_lifetimes\Ownership_Result> */ = [];
        $accepted = (new \analyze_lifetimes\Ownership_Queue($requests,$empty,false))->run();
        if (q_count($accepted) !== q_count($requests)) { throw new \LogicException('Incomplete selected run'); }
        $nested = $body->local_type_for(1); $box = $types->types->field_for($nested,0)->type_id;
        $required /** vector<string> */ = ['construct:' . $nested,'destroy:' . $nested,'construct:' . $box,'destroy:' . $box,'body:' . $body->callable_id];
        foreach ($required as $key) { if (!isset($seen[$key])) { throw new \LogicException('Missing required ownership request'); } }
        $forbidden /** vector<string> */ = ['copy:' . $nested,'assign:' . $nested,'copy:' . $twin,'assign:' . $twin];
        foreach ($forbidden as $key) { if (isset($seen[$key])) { throw new \LogicException('Unavailable lifecycle role selected'); } }
        $twin_destroy = 'destroy:' . $twin; $twin_construct = 'construct:' . $twin;
        if (!isset($seen[$twin_destroy]) || !isset($seen[$twin_construct])) { throw new \LogicException('Missing twin lifecycle'); }
        foreach ($requests as $request) {
            $subject = $request->lifecycle;
            if ($subject !== null) {
                if ($subject->type_id === $twin) {
                    $children = $subject->children(); if (q_count($children) !== 2) { throw new \LogicException('Missing twin children'); }
                    $first = 0; $second = 1;
                    if ($subject->kind === \type_model\LIFECYCLE_DESTROY) { $first = 1; $second = 0; }
                    if (($children[0]->ordinal !== $first) || ($children[1]->ordinal !== $second)) { throw new \LogicException('Incorrect lifecycle member order'); }
                    if (q_count($request->dependencies()) !== 1) { throw new \LogicException('Repeated child type dependencies not deduplicated'); }
                }
            }
        }
        $without_types = (new \analyze_lifetimes\Ownership_Selection($bodies,null))->select();
        if (q_count($without_types) !== 1) { throw new \LogicException('Body-only selection membership'); }
        if ($without_types[0]->body !== $body) { throw new \LogicException('Lost selected body identity'); }
        $again = (new \analyze_lifetimes\Ownership_Queue((new \analyze_lifetimes\Ownership_Selection($bodies,$types->types))->select(),$accepted,false))->run();
        foreach ($accepted as $key => $result) { if ($again[$key] !== $result) { throw new \LogicException('Selected subject failed incremental reuse'); } }
        foreach ($requests as $request) {
            $subject = $request->lifecycle;
            if ($subject !== null) {
                if ($subject->type_id === $nested) {
                    $children = $subject->children();
                    if (q_count($children) !== 1) { throw new \LogicException('Missing nested lifecycle child'); }
                    if ($children[0]->dependency !== \analyze_lifetimes\Ownership_Selection::lifecycle_key($subject->kind,$box)) { throw new \LogicException('Incorrect nested lifecycle role'); }
                }
            }
        }

    }

    private static function source_case(\scpp\Json_View $item): void {
        $f = \resource_locations_fixture\Fixture::prepare($item->member('source')->text()); $types = Probe::snapshot($f);
        $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$types)->check();
        $rows /** vector<\check_bodies\Checked_Body> */ = [$body];
        $callee_id = $f->symbols->find_symbol('g',\collect_symbols\SYMBOL_FUNCTION,0,'');
        if ($callee_id !== 0) {
            $input = new \resolve_types\Callable_Input($f->symbols->symbol_by_id($callee_id));
            $rows[] = \check_bodies\Body_Worker::prepare($input,$f->reader->annotations->bindings($input->owner),$types)->check();
        }
        $bodies = new \check_bodies\Body_Set($rows);
        $requests = (new \analyze_lifetimes\Ownership_Selection($bodies,$types->types))->select();
        $found = false; $actual /** vector<string> */ = [];
        foreach ($requests as $request) {
            if ($request->key === 'body:' . $body->callable_id) { $found = true; $actual = $request->dependencies(); }
        }
        if ($found !== $item->member('selected')->boolean()) { throw new \LogicException('Incorrect source request selection'); }
        $expected = $item->member('dependencies');
        if (q_count($actual) !== $expected->size()) { throw new \LogicException('Incorrect body prerequisite count'); }
        $seen /** hash<bool> */ = []; foreach ($actual as $key) { $seen[$key] = true; }
        for ($i = 0; $i < $expected->size(); $i++) {
            $entry = $expected->at($i); $prefix = $entry->at(0)->text(); $name = $entry->at(1)->text();
            $id = $types->types->find_type($name,''); if ($prefix === 'body:') { $id = $callee_id; }
            $key = $prefix . $id; if (!isset($seen[$key])) { throw new \LogicException('Missing expected body prerequisite'); }
        }
        $empty /** hash<\analyze_lifetimes\Ownership_Result> */ = [];
        $accepted = (new \analyze_lifetimes\Ownership_Queue($requests,$empty,false))->run();
        if (q_count($accepted) !== q_count($requests)) { throw new \LogicException('Incomplete selected ownership execution'); }
        if ($item->member('owned_return')->boolean()) {
            $key = 'body:' . $body->callable_id; $fields = $accepted[$key]->summary->result();
            if (q_count($fields) !== 1) { throw new \LogicException('Incorrect owned result field count'); }
            if (!isset($fields['0.0'])) { throw new \LogicException('Missing nested owned result field'); }
            if ($fields['0.0'] !== 5) { throw new \LogicException('Incorrect transferred empty resource state'); }
        }
    }
    public static function run(string $text): void {
        Probe::baseline(); $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) { Probe::source_case($cases->at($i)); echo json_quote('accepted') . "\n"; }
    }
}
