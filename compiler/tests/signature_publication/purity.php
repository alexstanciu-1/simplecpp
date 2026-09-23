<?php
declare(strict_types=1);
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Signature publication invariant '.$count);}$count++;}
$f=\signature_requests_test\Fixture::prepare('scalar',0);
$previous=new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context),[]);
$before=serialize($f);
$tasks=\resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$previous,$f->prepared,false);
verify(serialize($f)===$before);verify(count($tasks)===3);
$results=\signature_publication_test\Probe::requests($f,$tasks);verify(serialize($f)===$before);
$join=new \resolve_types\Signature_Join($f->symbols,$f->reader,$f->types,$previous,$tasks,$f->entry,$f->prepared);
$tasks_copy=$tasks;$tasks=[];
$bad=$results;$bad[]=$results[0];$caught=false;
try{$join->join($bad);}catch(LogicException $e){$caught=true;}
verify($caught);verify(serialize($f)===$before);
$joined=$join->join($results);verify($joined->size()===count($tasks_copy));
$old=serialize($joined);$candidate=$f->types->fork();$candidate->invalidate_definition($candidate->find_type('uint8',''));
$next=\resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$candidate,$joined,$f->prepared,false);
verify(count($next)===1);verify($next[0]->owner===$f->input->owner);
$reader=\signature_publication_test\Probe::reader($f,$candidate);
$request=\resolve_types\Signature_Resolver::resolve($f->symbols,$reader,$next[0],$f->entry,$f->prepared);
$again=(new \resolve_types\Signature_Join($f->symbols,$reader,$candidate,$joined,$next,$f->entry,$f->prepared))->join([$request]);
verify(serialize($joined)===$old);
verify($again->for_callable($f->input->callable_id)!==$joined->for_callable($f->input->callable_id));
verify($again->for_callable($f->entry->symbol->symbol_id)===$joined->for_callable($f->entry->symbol->symbol_id));
verify(\resolve_types\Signature_Validity::is_current($again,$candidate->fork(),$f->input,$f->prepared));
$rows=[];for($i=0;$i<$joined->size();$i++){$rows[]=$joined->at($i);}
$set=new \resolve_types\Signature_Set($joined->types,$rows);$rows=[];verify($set->size()===$joined->size());
$caught=false;try{new \resolve_types\Signature_Set($joined->types,[$joined->at(0),$joined->at(0)]);}catch(LogicException $e){$caught=true;}verify($caught);
verify($set->for_callable(0)===null);
$caught=false;try{$set->at(-1);}catch(OutOfBoundsException $e){$caught=true;}verify($caught);
foreach(['template','method','const_method','copy','family','storage'] as $mode){
    $g=\signature_requests_test\Fixture::prepare($mode,0);
    $none=new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($g->types->context),[]);
    $selected=\resolve_types\Signature_Selection::select($g->symbols,$g->instances->view(),$g->entry,$g->types,$none,$g->prepared,false);
    verify(count($selected)===4);
    verify($selected[3]->callable_id===$g->input->callable_id);
}
echo 'Host signature publication invariants: '
,$count," passed\n";
