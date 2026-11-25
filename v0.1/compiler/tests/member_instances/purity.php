<?php
declare(strict_types=1);
require __DIR__.'/probe.php';
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Member purity assertion '.$count);}$count++;}
function rejects(callable $call): void {try{$call();}catch(LogicException $error){verify(true);return;}throw new LogicException('Expected member rejection');}
foreach(['ordinary','pending','template','declaration','wrong_receiver'] as $mode){
    $f=\member_instances_test\Fixture::prepare($mode);$before=serialize([$f->symbols,$f->names,$f->initial,$f->types,$f->store,$f->tasks]);
    $results=[];foreach($f->tasks as $task){$results[]=\instantiate\Member_Worker::run($task,$f->reader,$f->symbols);}
    verify(serialize([$f->symbols,$f->names,$f->initial,$f->types,$f->store,$f->tasks])===$before);
    if($mode==='wrong_receiver'){$last=count($results)-1;$results[$last]=new \instantiate\Member_Result($f->tasks[$last],clone $results[$last]->receiver,$results[$last]->definition,[]);}
    $fixed=serialize([$f->symbols,$f->names,$f->initial,$f->tasks,$results]);
    if($mode==='wrong_receiver'){rejects(fn()=>$f->join()->join($results));verify(serialize([$f->symbols,$f->names,$f->initial,$f->types,$f->store,$f->tasks])===$before);}
    else{$f->join()->join($results);}
    verify(serialize([$f->symbols,$f->names,$f->initial,$f->tasks,$results])===$fixed);
    if($mode==='template'){$args=$results[0]->arguments();$result=new \instantiate\Member_Result($f->tasks[0],$results[0]->receiver,$results[0]->definition,$args);$args=[];verify($result->argument_count()===1);$export=$result->arguments();$export=[];verify($result->argument_count()===1);}
    if($mode==='ordinary'){
        $binding=$f->names->for_symbol($f->context->definition->symbol_id)->members_at(0);$task=\instantiate\Member_Task::occurrence($f->context,$binding);$original=$task->receiver_node_id;$binding->receiver_node_id=1;verify($task->receiver_node_id===$original);
        rejects(fn()=>new \instantiate\Member_Task($f->context,0,0));
        rejects(fn()=>new \instantiate\Member_Task($f->context,1,1,$results[0]->definition));
        rejects(fn()=>new \instantiate\Member_Result($task,null,$results[0]->definition,[]));
        rejects(fn()=>new \instantiate\Member_Result($task,$results[0]->receiver,null,[]));
        $bad=\instantiate\Member_Task::definition($f->context,$results[0]->definition);
        $join=new \instantiate\Member_Join($f->store,[$bad],$f->types,$f->names,$f->definitions,$f->catalog,$f->symbols);
        rejects(fn()=>$join->join([new \instantiate\Member_Result($bad,$results[0]->receiver,$results[0]->definition,[])]));
    }
}
echo 'Host member invariants: ',$count," passed\n";
