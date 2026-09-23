<?php
declare(strict_types=1);
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Type snapshot invariant '.$count);}$count++;}
function rejects(callable $call): void {$caught=false;try{$call();}catch(LogicException $e){$caught=true;}catch(OutOfBoundsException $e){$caught=true;}verify($caught);}
$f=\signature_requests_test\Fixture::prepare('scalar',0);
$empty=new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context),[]);
$tasks=\resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$empty,$f->prepared,false);
$requests=[];foreach($tasks as $task){$requests[]=\resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$task,$f->entry,$f->prepared);}
$set=(new \resolve_types\Signature_Join($f->symbols,$f->reader,$f->types,$empty,$tasks,$f->entry,$f->prepared))->join($requests);
$bool=$f->catalog->boolean_type;if($bool!==null){\resolve_types\Type_Cache::materialize($f->types,$bool);}
$names=$f->reader->annotations->names;$bindings=$names->for_symbol($f->input->owner->symbol_id);
$ids=[$f->types->find_type('int32',''),$f->types->find_type('uint8','')];$local=new \resolve_types\Local_Types($bindings,$ids);$locals=[$local];
$instances=$f->instances->snapshot([]);$before=serialize([$f,$set,$instances,$locals]);
$snapshot=new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,$set,$locals,$names,$instances,[]);
verify(serialize([$f,$set,$instances,$locals])===$before);
verify($snapshot->callables===$set);verify($snapshot->names===$names);verify($snapshot->instances===$instances);
$locals=[];verify($snapshot->locals_for($f->input->callable_id)===$local);
$body=$snapshot->body_signatures();verify(count($body)===2);$body=[];verify(count($snapshot->body_signatures())===2);
verify($snapshot->definition_for($ids[0])===$f->catalog->find_type('int32',''));
verify($snapshot->for_symbol($f->input->owner->symbol_id)===$set->for_callable($f->input->callable_id));
verify($snapshot->for_symbol(\collect_symbols\MAX_SYMBOL_ID+1)===null);
if($bool!==null){verify($snapshot->boolean_type()===$f->types->find_type($bool->name,$bool->namespace_name));}
rejects(fn()=>$snapshot->parameter_type_for($f->input->callable_id,3));rejects(fn()=>$snapshot->signature_for(0));
rejects(fn()=>new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,$set,[$local,$local],$names,$instances,[]));verify(serialize([$f,$set,$instances,[$local]])===$before);
$definitions=[];for($i=0;$i<$f->catalog->size();$i++){$definitions[]=$f->catalog->definition_at($i);}
$literal=$f->catalog->find_type('uint32','');verify($literal!==null);
$catalog=new \type_model\Type_Catalog('alternate','alternate','language_values',$definitions,$literal,$f->entry->return_type,null);
$alternate=new \resolve_types\Type_Resolution($f->types,$catalog,$f->entry,$set,[$local],$names,$instances,[]);
verify($alternate->boolean_type()===0);rejects(fn()=>$alternate->integer_literal_type());
$family=$f->symbols->symbol_by_id($f->symbols->find_symbol('Family',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,''));
$arg=$f->catalog->find_type('int32','');$args=[new \instantiate\Template_Argument($arg)];$id=$f->instances->allocate($f->types,$family->symbol_id,$args);
$context=new \instantiate\Instance_Context($family,$id,$args);$f->instances->accept($context);$f->instances->accept_type($context,$arg);
$ops=['read'];$sources=[];$task=new \load_runtime\Family_Preparation_Task($context,$ops,$sources);$ops=[];verify($task->operation_count()===1);verify($task->operation_at(0)==='read');rejects(fn()=>$task->operation_at(1));
$call=\signature_requests_test\Fixture::callable('int32','int32',false);$calls=['read'=>$call];
$result=new \load_runtime\Family_Preparation_Result($task,\type_snapshot_test\Probe::package($f->catalog,$arg),'instance',$calls);$calls=[];verify($result->operation_for('read')===$call);rejects(fn()=>$result->operation_for('missing'));
$copy=$result->callable_operations();$copy=[];verify(count($result->callable_operations())===1);
$family_rows=[$id=>$result];$snapshot=new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,$set,[$local],$names,$f->instances->snapshot([]),$family_rows);$family_rows=[];
verify($snapshot->family_for($id)===$result);$copy=$snapshot->prepared_families();$copy=[];verify(count($snapshot->prepared_families())===1);
echo 'Host type snapshot invariants: ',$count," passed\n";
