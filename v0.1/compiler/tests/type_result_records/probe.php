<?php
declare(strict_types=1);
namespace type_result_records_test;
final class Probe {
    public static function storage(\resolve_types\Callable_Input $input): ?\type_model\Storage_Function {
        if (!$input->owner->is_source()) { if ($input->owner->provider()->kind() === \collect_symbols\PROVIDER_STORAGE_FUNCTION) { return $input->owner->provider()->storage_function(); } }
        return null;
    }
    public static function run(string $text): void {
        $cases=json_read($text);
        for($i=0;$i<$cases->size();$i++) {
            $mode=$cases->at($i)->member('mode')->text();$fixture_mode='scalar';
            if(($mode==='entry')||($mode==='template')||($mode==='provider')||($mode==='family')||($mode==='storage')){$fixture_mode=$mode;}
            if(($mode==='provider_annotation')||($mode==='wrong_external')){$fixture_mode='provider';}
            if(($mode==='storage_origin')||($mode==='storage_instance')){$fixture_mode='storage';}
            if($mode==='wrong_instance'){$fixture_mode='template';}
            $f=\signature_requests_test\Fixture::prepare($fixture_mode,0);$ok=false;$rejected=false;$expect_error=false;
            try {
                if(($mode==='local_copy')||($mode==='local_count')||($mode==='local_zero')||($mode==='local_bounds')||($mode==='wrong_instance')) {
                    $names=$f->reader->annotations->bindings($f->input->owner);
                    $ids /** vector<int> */ = [];for($j=0;$j<$names->locals_count();$j++){$ids[]=7+$j;}
                    if($mode==='local_count'){$expect_error=true;$ids[]=99;}
                    if($mode==='local_zero'){$expect_error=true;$ids[0]=0;}
                    if($mode==='wrong_instance'){$expect_error=true;$names=$f->reader->annotations->bindings($f->entry->symbol);}
                    $locals=new \resolve_types\Local_Types($names,$ids,$f->input->instance);
                    if($mode==='local_bounds'){$expect_error=true;$locals->type_for(0);}
                    if($mode==='local_copy'){$ids[0]=99;$ok=($locals->type_for(1)===7)&&($locals->size()===2)&&($locals->names===$names)&&($locals->callable_id===$f->input->callable_id);}
                } else {
                    $external=\resolve_types\Callable_Inputs::external($f->input,$f->prepared);
                    $storage=\type_result_records_test\Probe::storage($f->input);
                    if($fixture_mode==='storage'){$storage=$f->input->owner->provider()->storage_function();}
                    $annotation=0;$representation=1;$receiver /** nullable<int> */ = null;$input=$f->input;
                    if($mode==='source_external'){$expect_error=true;$external=\signature_requests_test\Fixture::callable('int32','int32',false);}
                    if($mode==='provider_annotation'){$expect_error=true;$annotation=1;}
                    if($mode==='wrong_external'){$expect_error=true;$external=\signature_requests_test\Fixture::callable('int32','int32',false);}
                    if($mode==='storage_origin'){$expect_error=true;$external=\signature_requests_test\Fixture::callable('int32','int32',false);}
                    if($mode==='storage_instance'){$expect_error=true;$input=new \resolve_types\Callable_Input($input->owner);}
                    if($mode==='representation_zero'){$expect_error=true;$representation=0;}
                    if($mode==='receiver_negative'){$expect_error=true;$receiver=-1;}
                    $result=new \resolve_types\Callable_Signature($input,$annotation,$representation,$external,$storage,$receiver);
                    $ok=($result->input===$input)&&($result->callable_id===$input->callable_id)&&($result->representation_id===1)&&($result->external===$external)&&($result->storage===$storage);
                }
            } catch(\LogicException $error){$rejected=true;} catch(\OutOfBoundsException $error){$rejected=true;}
            if($expect_error){$ok=$rejected;}else{if($rejected){$ok=false;}}
            echo $ok ? "true\n" : "false\n";
        }
    }
}
