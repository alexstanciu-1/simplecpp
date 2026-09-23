<?php
declare(strict_types=1);
require __DIR__.'/probe.php';
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Instance join purity assertion '.$count);}$count++;}
foreach(['success','incomplete','wrong_value','stale_task','pending'] as $mode){
    $entry='$a Target<int,7>; $b Target<int,8>;';if($mode==='pending'){$entry='$a Target<Source,7>;';}
    $f=\instance_join_test\Fixture::prepare($entry,'typename T,int N',true,'');
    $tasks=$f->tasks;$results=$f->results;
    if($mode==='incomplete'){array_pop($results);}
    if($mode==='wrong_value'){$args=$results[1]->arguments();$args[1]=new \instantiate\Template_Argument($f->catalog->integer_literal_type,'9');$results[1]=new \instantiate\Application_Result($tasks[1],$args,[]);}
    if($mode==='stale_task'){$other=\instance_join_test\Fixture::prepare($entry,'typename T,int N',true,'');$tasks[1]=$other->tasks[1];$results[1]=$other->results[1];}
    $frozen=serialize([$tasks,$results,$f->initial,$f->names,$f->catalog]);$candidate=serialize([$f->store,$f->types]);
    $join=new \instantiate\Instance_Join($f->store,$tasks,$f->types,$f->names,$f->definitions,$f->catalog);
    $tasks=[];$rejected=false;try{$join->join($results);}catch(LogicException $error){$rejected=true;}
    verify($rejected===in_array($mode,['incomplete','wrong_value','stale_task'],true));
    // Constructor retains its own selected membership; input results and semantic facts stay fixed.
    $original_tasks=$f->tasks;if($mode==='stale_task'){$original_tasks[1]=$other->tasks[1];}
    verify(serialize([$original_tasks,$results,$f->initial,$f->names,$f->catalog])===$frozen);
    if($rejected||$mode==='pending'){verify(serialize([$f->store,$f->types])===$candidate);}
    else{verify($f->store->size()===2);verify($f->initial->size()===0);}
}
echo 'Host instance join invariants: ',$count," passed\n";
