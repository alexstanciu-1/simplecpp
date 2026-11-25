<?php
declare(strict_types=1);
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Construction/debug invariant '.$count);}$count++;}
foreach(['scalar','method'] as $mode){
    $f=\construction_fixture\Fixture::prepare($mode,0);$snapshot=\construction_types_test\Probe::snapshot($f,false);$before=serialize([$f,$snapshot]);
    $lookup=\resolve_types\Construction_Types::for_snapshot($snapshot);$node=\construction_types_test\Probe::name_node($f,'int32');
    verify($lookup->resolve($f->input,$node)===$f->types->find_type('int32',''));verify($lookup->diagnostic()===null);verify(serialize([$f,$snapshot])===$before);
    $json=\resolve_types\Type_Association_Debug::encode($snapshot);$data=json_decode($json,true,512,JSON_THROW_ON_ERROR);
    verify($json===\resolve_types\Type_Association_Debug::encode($snapshot));verify(serialize([$f,$snapshot])===$before);
    verify(array_keys($data)===['signatures','local_types','prepared_families','entry_symbol_id']);
    verify($data['entry_symbol_id']===$f->entry->symbol->symbol_id);verify($data['prepared_families']===[]);
    $found=false;$provider=false;
    foreach($data['signatures'] as $row){
        if($row['callable_id']===$f->input->callable_id){$found=true;verify($row['symbol_id']===$f->input->owner->symbol_id);verify($row['source_path']==='/signatures.phs');verify($row['provider_operation']===null);verify($row['declaration_node_id']===$f->input->owner->source_fact()->declaration_node_id);verify($row['body_node_id']===$f->input->owner->source_fact()->body_node_id);verify($row['return_type_id']===$f->types->find_type('int32',''));
            if($mode==='scalar'){verify($row['receiver_index']===null);verify($row['parameter_passing']===['value','value']);verify($row['parameter_type_ids']===[$f->types->find_type('int32',''),$f->types->find_type('uint8','')]);}
            else{verify($row['receiver_index']===0);verify($row['parameter_passing']===['borrow_mutable']);verify($row['parameter_type_ids']===[$f->types->find_type('Point','')]);}
        }
        if($row['provider_operation']==='call'){$provider=true;verify($row['source_path']===null);verify($row['body_node_id']===0);verify($row['declaration_node_id']===0);verify($row['return_annotation_id']===0);}
    }
    verify($found);verify($provider);verify(count($data['local_types'])===1);
    $local=$snapshot->locals_for($f->input->callable_id);$expected=[];for($i=1;$i<$local->size()+1;$i++){$expected[]=['local_id'=>$i,'type_id'=>$local->type_for($i)];}verify($data['local_types'][0]['locals']===$expected);
    if($mode==='scalar'){
        $lookup=\resolve_types\Construction_Types::for_snapshot($snapshot);$node=\construction_types_test\Probe::name_node($f,'uint32');$caught=false;try{$lookup->resolve($f->input,$node);}catch(RuntimeException $e){$caught=true;}verify($caught);verify($lookup->diagnostic()!==null);verify(serialize([$f,$snapshot])===$before);
    }
}
$f=\construction_fixture\Fixture::prepare('scalar',0);$f->input->owner->source_frontend()->tokens->source->path="/quoted \"é\"\n.php";$snapshot=\construction_types_test\Probe::snapshot($f,true);$before=serialize($snapshot);
$data=json_decode(\resolve_types\Type_Association_Debug::encode($snapshot),true,512,JSON_THROW_ON_ERROR);verify($data['local_types'][0]['source_path']==="/quoted \"é\"\n.php");verify(count($data['prepared_families'])===1);verify($data['prepared_families'][0]['operations']===['read']);verify(serialize($snapshot)===$before);
echo 'Host construction/debug invariants: ',$count," passed\n";
