<?php
declare(strict_types=1);
namespace body_conversions_test;
final class Probe {
    public static function bound(\construction_fixture\Fixture $f, int $id, int $purpose, string $source_name, string $destination_name): \resolve_types\Callable_Signature {
        $original = \construction_fixture\Fixture::callable($source_name,$destination_name,false);
        $call = new \type_model\Runtime_Callable('p','conversion_'.$id,'conversion_'.$id,'',$original->signature,$original->abi,null,false,$purpose);
        $owner = \collect_symbols\Symbol_Record::from_provider($id,0,new \collect_symbols\Provider_Declaration($call,null,null,null,null));
        $parameters /** vector<int> */ = [$f->types->find_type($source_name,'')];
        $passing /** vector<int> */ = [\type_model\PASS_VALUE];
        $shape = $f->types->intern_signature($f->types->find_type($destination_name,''),$parameters,$passing);
        return new \resolve_types\Callable_Signature(new \resolve_types\Callable_Input($owner),0,$shape,$call);
    }
    public static function run(string $text): void {
        $f = \construction_fixture\Fixture::prepare('scalar',0);
        for ($i = 0; $i < $f->catalog->size(); $i++) { \resolve_types\Type_Cache::materialize($f->types,$f->catalog->definition_at($i)); }
        $empty /** vector<\resolve_types\Callable_Signature> */ = [];
        $previous = new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context),$empty);
        $tasks = \resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$previous,$f->prepared,false);
        $requests /** vector<\resolve_types\Signature_Request> */ = [];
        foreach ($tasks as $task) { $requests[] = \resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$task,$f->entry,$f->prepared); }
        $set = (new \resolve_types\Signature_Join($f->symbols,$f->reader,$f->types,$previous,$tasks,$f->entry,$f->prepared))->join($requests);
        $rows /** vector<\resolve_types\Callable_Signature> */ = [];
        for ($i = 0; $i < $set->size(); $i++) { $rows[] = $set->at($i); }
        $rows[] = \body_conversions_test\Probe::bound($f,10000,\type_model\CONVERSION_EXPLICIT,'int32','uint8');
        $rows[] = \body_conversions_test\Probe::bound($f,10001,\type_model\CONVERSION_TEXT,'int32','uint8');
        // Even indexed implicit/condition providers do not change these policies.
        $rows[] = \body_conversions_test\Probe::bound($f,10002,\type_model\CONVERSION_IMPLICIT,'uint32','uint8');
        $rows[] = \body_conversions_test\Probe::bound($f,10003,\type_model\CONVERSION_CONDITION,'int32','uint8');
        $locals /** vector<\resolve_types\Local_Types> */ = [];
        $bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [];
        $families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
        $context = new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,new \resolve_types\Signature_Set($f->types,$rows),$locals,$f->reader->annotations->names,$f->instances->snapshot($bindings),$families);
        $count = $f->types->type_count(); $repr_count = $f->types->representation_count();
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $row = $cases->at($i);
            $source = $f->types->find_type($row->member('source')->text(),'');
            $destination = $f->types->find_type($row->member('destination')->text(),'');
            $request = new \check_bodies\Conversion_Request($source,$destination,$row->member('purpose')->integer());
            $selection = \check_bodies\Conversion_Resolver::resolve($context,$request);
            $form = $row->member('form')->integer(); $valid = true;
            if ($form === 0) { $valid = $selection === null; }
            else {
                if ($selection === null) { $valid = false; }
                else {
                    if (($selection->form !== $form) || ($selection->primitive !== $row->member('primitive')->integer()) || ($selection->callable_id !== $row->member('target')->integer())) { $valid = false; }
                }
            }
            if (($f->types->type_count() !== $count) || ($f->types->representation_count() !== $repr_count)) { $valid = false; }
            echo $valid ? "true\n" : "false\n";
        }
        for ($form = 1; $form < 4; $form++) {
            for ($primitive = 0; $primitive < 2; $primitive++) {
                for ($has_target = 0; $has_target < 2; $has_target++) {
                    $target = $has_target * 7;
                    $valid = (($form === 2) === ($primitive !== 0)) && (($form === 3) === ($target > 0));
                    $failed = false;
                    try { $selection = new \check_bodies\Conversion_Selection($form,$primitive,$target); }
                    catch (\InvalidArgumentException $error) { $failed = true; }
                    if ($failed === $valid) { throw new \LogicException('Conversion alternatives overlap'); }
                }
            }
        }
    }
}
