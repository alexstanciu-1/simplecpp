<?php
declare(strict_types=1);
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Local type invariant '.$count);}$count++;}
function rejects(callable $call): void {$caught=false;try{$call();}catch(LogicException $e){$caught=true;}catch(OutOfBoundsException $e){$caught=true;}verify($caught);}
$f=\local_types_fixture\Fixture::prepare('template',0);$signatures=\local_types_test\Probe::signatures($f);$history=new \resolve_types\Local_Type_Validity();
$before=serialize([$f,$signatures]);
$tasks=\resolve_types\Local_Type_Resolver::select($f->symbols,$f->reader,$f->types,$history,false,$f->entry);verify(count($tasks)===2);verify(serialize([$f,$signatures])===$before);
$requests=[];foreach($tasks as $task){$requests[]=\resolve_types\Local_Type_Resolver::resolve($f->symbols,$f->reader,$task);}
verify(serialize([$f,$signatures])===$before);verify($requests[0]->size()===2);verify($requests[0]->at(0)===$f->catalog->find_type('uint32',''));verify($requests[0]->at(1)===$f->catalog->find_type('uint8',''));
verify($requests[1]->size()===1);verify($requests[1]->at(0)===$f->catalog->find_type('int32',''));
$definitions=[$requests[0]->at(0),$requests[0]->at(1)];$copy=new \resolve_types\Local_Type_Request($tasks[0],$requests[0]->names,$definitions);$definitions=[];verify($copy->size()===2);rejects(fn()=>$copy->at(2));
$join=new \resolve_types\Local_Type_Join($f->symbols,$f->reader,$f->types,$history,array_reverse($tasks),$f->entry,$signatures);$saved=$tasks;$tasks=[];
rejects(fn()=>$join->join([$requests[0],$requests[0]]));verify(serialize([$f,$signatures])===$before);
$locals=$join->join(array_reverse($requests));verify(count($locals)===2);verify($locals[0]->callable_id===$saved[0]->callable_id);verify($locals[1]->callable_id===$saved[1]->callable_id);
verify($locals[0]->names===$requests[0]->names);verify($locals[1]->instance===$saved[1]->instance);
$snapshot=new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,$signatures,$locals,$f->reader->annotations->names,$f->instances->snapshot([]),[]);$prior=serialize($snapshot);
$history=new \resolve_types\Local_Type_Validity($snapshot);verify($history->retained($locals[0]->callable_id)===$locals[0]);rejects(fn()=>$history->retained(0));
rejects(fn()=>(new \resolve_types\Local_Type_Join($f->symbols,$f->reader,$f->types,$history,[],$f->entry,$signatures))->join([]));verify(serialize($snapshot)===$prior);
$candidate=$f->types->fork();verify($history->is_current($candidate,$locals[0]->names,$saved[0]));$candidate->invalidate_definition($candidate->find_type('uint32',''));verify(!$history->is_current($candidate,$locals[0]->names,$saved[0]));verify($history->is_current($candidate,$locals[1]->names,$saved[1]));verify(serialize($snapshot)===$prior);
$provider=$f->symbols->symbol_by_id($f->symbols->find_symbol('external',\collect_symbols\SYMBOL_FUNCTION,0,''));rejects(fn()=>\resolve_types\Local_Type_Validity::names_for($provider,$f->reader->annotations->names));
echo 'Host local type invariants: ',$count," passed\n";
