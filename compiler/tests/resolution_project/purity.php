<?php
declare(strict_types=1);
// Host-only snapshot checks and full semantic export comparison.
$count=0;
function check(bool $ok): void { global $count; if (!$ok) { throw new RuntimeException('Project invariant failed at ' . $count); } $count++; }
function rejects(callable $operation): void {
    try { $operation(); } catch (LogicException $error) { check(true); return; }
    throw new RuntimeException('Expected project rejection');
}
function resolve_set($symbols,$previous,$catalog,bool $full=false): \resolve_symbols\Resolution_Set {
    $attempt=\resolve_symbols\Symbol_Resolver::resolve($symbols,$previous,$catalog,$full);
    if (!$attempt->valid()) { throw new RuntimeException($attempt->error_reason); } return $attempt->result();
}
function semantic_export(\resolve_symbols\Resolution_Set $set): string {
    $out=[];
    for($i=0;$i<$set->size();$i++) {
        $r=$set->at($i);$row=['symbol'=>$r->owner->symbol_id,'path'=>$r->owner->frontend->tokens->source->path];
        foreach(['calls','scopes','locals','uses','parameters','constants','members'] as $name) {
            $row[$name]=[];
            for($j=0;$j<$r->{$name.'_count'}();$j++) { $row[$name][]=get_object_vars($r->{$name.'_at'}($j)); }
        }
        $row['names']=[];
        for($j=0;$j<$r->names_count();$j++) {
            $n=$r->names_at($j);$row['names'][]=[$n->use_node_id,$n->role,$n->kind,$n->target_id,$n->provided_type?->namespace_name,$n->provided_type?->name];
        }
        $row['applications']=[];
        for($j=0;$j<$r->applications_count();$j++) { $a=$r->applications_at($j);$row['applications'][]=[$a->use_node_id,$a->definition->symbol_id]; }
        $out[]=$row;
    }
    return json_encode($out,JSON_THROW_ON_ERROR);
}
$a=\resolution_test\Probe::file('/a.phs','$x int = answer(1); { $y int = $x; $x = $y; } return $x;');
$b=\resolution_test\Probe::file('/b.phs','function answer($n int): int { return $n; }');
$symbols=\resolution_test\Probe::collect([$a,$b],new \collect_symbols\Symbol_Store(1));
$catalog=\resolution_test\Probe::catalog();$empty=new \resolve_symbols\Resolution_Set(null);
$before=serialize([$symbols,$catalog,$empty]);$cold=resolve_set($symbols,$empty,$catalog);check(serialize([$symbols,$catalog,$empty])===$before);
$frozen=serialize([$symbols,$catalog,$cold]);$warm=resolve_set($symbols,$cold,$catalog);$full=resolve_set($symbols,$cold,$catalog,true);
check(serialize([$symbols,$catalog,$cold])===$frozen);check(semantic_export($warm)===semantic_export($cold));check(semantic_export($full)===semantic_export($cold));
for($i=0;$i<$cold->size();$i++) { check($warm->at($i)===$cold->at($i));check($full->at($i)!==$cold->at($i)); }
$rebound=\resolution_test\Probe::file('/b.phs',$b->tokens->source->content);$rebound->tree=$b->tree;$rebound->tokens->source->mtime=1234;
$changed=\resolution_test\Probe::collect([$a,$rebound],$symbols);$plan=new \resolve_symbols\Resolution_Plan($changed,$cold,$catalog,false);check($plan->task_count()===2);
$changed_names=resolve_set($changed,$cold,$catalog);$answer=$changed->find_symbol('answer',\collect_symbols\SYMBOL_FUNCTION,0);
check($changed_names->for_symbol($answer)->owner->frontend===$rebound);check($changed_names->for_symbol($answer)->owner->frontend->tokens->source->mtime===1234);
check(serialize([$symbols,$catalog,$cold])===$frozen);
$removed=\resolution_test\Probe::collect([$a],$symbols);$failure=\resolve_symbols\Symbol_Resolver::resolve($removed,$cold,$catalog,false);
check(!$failure->valid());check($failure->error_reason==="Unknown function 'answer'");rejects(fn()=>$failure->result());check(serialize([$symbols,$catalog,$cold])===$frozen);
check(resolve_set($symbols,$cold,$catalog)->for_symbol($symbols->entry_symbol_id('/a.phs'))===$cold->for_symbol($symbols->entry_symbol_id('/a.phs')));
$rows=[];for($i=0;$i<$cold->size();$i++){$rows[]=$cold->at($i);}
rejects(fn()=>\resolve_symbols\Resolution_Set::publish($symbols,$catalog,$cold,[]));
$duplicate=$rows;$duplicate[1]=$duplicate[0];rejects(fn()=>\resolve_symbols\Resolution_Set::publish($symbols,$catalog,$cold,$duplicate));
rejects(fn()=>\resolve_symbols\Resolution_Set::publish($changed,$catalog,$cold,$rows));
$bad=$rows;$bad[0]=\resolution_test\Probe::changed($rows[0],2);rejects(fn()=>\resolve_symbols\Resolution_Set::publish($symbols,$catalog,$cold,$bad));
check(serialize([$symbols,$catalog,$cold])===$frozen);
$repaired=resolve_set($symbols,$cold,$catalog,true);check(semantic_export($repaired)===semantic_export($cold));
echo 'Host project invariants: ',$count," passed\n";
