<?php
// Host-only mutation/invalid-result tests complement executable PHP/native behavior.
declare(strict_types=1);
$count=0;
function check(bool $ok): void { global $count; if (!$ok) { throw new RuntimeException('Host purity assertion failed at ' . $count); } $count++; }
function rejects(callable $operation): void {
    try { $operation(); } catch (InvalidArgumentException|LogicException $e) { check(true); return; }
    throw new RuntimeException('Expected invalid result rejection');
}
function rows(\resolve_symbols\Symbol_Resolution $r): array {
    $out=[];
    foreach (['calls','scopes','locals','uses','parameters','constants','members','names','applications'] as $name) {
        $out[$name]=[];
        for($i=0;$i<$r->{$name.'_count'}();$i++) { $out[$name][]=$r->{$name.'_at'}($i); }
    }
    return $out;
}
function rebuild(\resolve_symbols\Symbol_Resolution $r,array $parts): \resolve_symbols\Symbol_Resolution {
    return new \resolve_symbols\Symbol_Resolution($r->owner,...array_values($parts));
}
$store=\lexical_test\Probe::store('function answer($n int): int { $x int = $n; { $y int = $x; } return $x; } return answer(1);');
$catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
$before=serialize([$store,$catalog]);
$id=$store->find_symbol('answer',\collect_symbols\SYMBOL_FUNCTION,0);
$worker=new \resolve_symbols\Resolution_Worker($store,$store->symbol_by_id($id),$catalog);
$r=$worker->run()->result();
check(serialize([$store,$catalog])===$before);
$parts=rows($r);$published=rebuild($r,$parts);$snapshot=serialize($published);
$parts['locals'][0]->scope_id=999;$parts['scopes'][0]->parent_scope_id=999;$parts['uses'][0]->local_id=999;
check(serialize($published)===$snapshot);
$read=$published->local_for(1);$read->scope_id=999;check(serialize($published)===$snapshot);
$bad=rows($r);$bad['locals'][]=$bad['locals'][0];rejects(fn()=>rebuild($r,$bad));
$bad=rows($r);$bad['scopes'][0]->parent_scope_id=1;rejects(fn()=>rebuild($r,$bad));
$bad=rows($r);$bad['scopes'][1]->parent_scope_id=0;rejects(fn()=>rebuild($r,$bad));
$bad=rows($r);$bad['locals'][0]->scope_id=99;rejects(fn()=>rebuild($r,$bad));
$bad=rows($r);$bad['locals'][0]->receiver=true;rejects(fn()=>rebuild($r,$bad));
$bad=rows($r);$bad['uses'][0]->local_id=99;rejects(fn()=>rebuild($r,$bad));
$bad=rows($r);$bad['uses'][0]->access=99;rejects(fn()=>rebuild($r,$bad));
$bad=rows($r);$bad['uses'][]=$bad['uses'][0];rejects(fn()=>rebuild($r,$bad));
$bad=rows($r);$bad['names'][]=$bad['names'][0];rejects(fn()=>rebuild($r,$bad));
$bad=rows($r);$bad['scopes']=[];rejects(fn()=>rebuild($r,$bad));
$entry=\lexical_test\Probe::bind($store,$store->entry_symbol_id('/names.phs'));
$bad=rows($entry);$bad['calls'][]=$bad['calls'][0];rejects(fn()=>rebuild($entry,$bad));
$bad=rows($entry);$bad['calls'][0]->target_symbol_id=0;rejects(fn()=>rebuild($entry,$bad));
$bad=rows($entry);$bad['calls'][0]->target_symbol_id=4294967296;rejects(fn()=>rebuild($entry,$bad));
rejects(fn()=>new \resolve_symbols\Symbol_Resolution($r->owner,...array_values(rows($entry))));
$stale=\lexical_test\Probe::store('function answer($n int): int { return $n; } return answer(1);');
rejects(fn()=>(new \resolve_symbols\Resolution_Worker($stale,$r->owner,$catalog))->run());
rejects(fn()=>(new \resolve_symbols\Resolution_Worker(new \collect_symbols\Symbol_Store(1),$r->owner,$catalog))->run());
$failure=\lexical_test\Probe::store('return $unknown;');$frozen=serialize([$failure,$catalog]);
$attempt=(new \resolve_symbols\Resolution_Worker($failure,$failure->record_at(0),$catalog))->run();
check(!$attempt->valid());check(serialize([$failure,$catalog])===$frozen);rejects(fn()=>$attempt->result());
check(serialize([$store,$catalog])===$before);
echo 'Host resolution invariants: ', $count, " passed\n";
