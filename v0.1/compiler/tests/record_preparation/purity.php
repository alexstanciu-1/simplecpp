<?php
declare(strict_types=1);
require __DIR__.'/probe.php';
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Record purity assertion '.$count);}$count++;}
foreach(['scalar','array','lifecycle','provider','template','late_invalid'] as $mode){
    $f=\record_preparation_test\Fixture::prepare($mode);$snapshot=serialize([$f->symbols,$f->names,$f->instances,$f->catalog,$f->types,$f->tasks]);$results=[];
    foreach($f->tasks as $task){$results[]=\resolve_types\Record_Preparation::resolve($task);}
    verify(serialize([$f->symbols,$f->names,$f->instances,$f->catalog,$f->types,$f->tasks])===$snapshot);
    if($mode==='late_invalid'){$results[1]=new \resolve_types\Record_Result($f->tasks[1],\record_preparation_test\Probe::changed($results[1]->declaration,'wrong_field'));}
    // Task readers intentionally observe this private candidate after publication.
    $declarations=array_map(fn($r)=>$r->declaration,$results);
    $task_ids=array_map(fn($t)=>spl_object_id($t),$f->tasks);
    $inputs=serialize([$f->symbols,$f->names,$f->instances,$f->catalog,$task_ids,$declarations]);$tasks=$f->tasks;$join=new \resolve_types\Record_Join($f->types,$tasks,$f->symbols);$tasks=[];
    $rejected=false;try{$join->join($results);}catch(LogicException $error){$rejected=true;}
    verify($rejected===($mode==='late_invalid'));
    verify(serialize([$f->symbols,$f->names,$f->instances,$f->catalog,array_map(fn($t)=>spl_object_id($t),$f->tasks),array_map(fn($r)=>$r->declaration,$results)])===$inputs);
    if($rejected){verify(serialize([$f->symbols,$f->names,$f->instances,$f->catalog,$f->types,$f->tasks])===$snapshot);}
    else{foreach($f->tasks as $task){verify($f->types->find_type($task->name(),$task->namespace_name())!==0);}}
}
$rejected=false;try{$invalid=\record_preparation_test\Fixture::prepare('duplicate_field');}catch(LogicException $error){$rejected=str_contains($error->getMessage(),'Duplicate field');}verify($rejected);
$rejected=false;try{$invalid=\record_preparation_test\Fixture::prepare('provider_collision');}catch(LogicException $error){$rejected=str_contains($error->getMessage(),'Duplicate source/provider type');}verify($rejected);
echo 'Host record invariants: ',$count," passed\n";
