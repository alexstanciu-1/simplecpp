<?php
declare(strict_types=1);
use constant_batch_test\Probe;
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Constant purity assertion '.$count);}$count++;}
function rejected(callable $call): void {try{$call();}catch(LogicException $error){verify(true);return;}throw new LogicException('Expected rejection');}
$catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
$file=Probe::file('const A=0007; const B: uint8=255; return 0;');$symbols=Probe::collect($file);$reader=Probe::reader($symbols,$catalog);
$a=$symbols->symbol_by_id($symbols->find_symbol('A',\collect_symbols\SYMBOL_CONSTANT,0,''));$b=$symbols->symbol_by_id($symbols->find_symbol('B',\collect_symbols\SYMBOL_CONSTANT,0,''));
$before=serialize([$symbols,$reader]);$tasks=[$a,$b];
$results=[$b->symbol_id=>\instantiate\Constant_Worker::resolve($b,$reader),$a->symbol_id=>\instantiate\Constant_Worker::resolve($a,$reader)];verify(serialize([$symbols,$reader])===$before);
$frozen=serialize([$symbols,$reader,$tasks,$results]);$join=new \instantiate\Constant_Join($tasks,$reader);$accepted=$join->join($results);
verify(array_keys($accepted)===[$a->symbol_id,$b->symbol_id]);verify($accepted[$a->symbol_id]===$results[$a->symbol_id]);verify(serialize([$symbols,$reader,$tasks,$results])===$frozen);
$bad=$results;$bad[$b->symbol_id]=new \instantiate\Template_Argument($results[$b->symbol_id]->type,'254');rejected(fn()=>$join->join($bad));verify(serialize([$symbols,$reader,$tasks,$results])===$frozen);
rejected(fn()=>$join->join([$a->symbol_id=>$results[$a->symbol_id]]));verify(serialize([$symbols,$reader,$tasks,$results])===$frozen);
$again=$join->join($results);verify($again[$b->symbol_id]===$accepted[$b->symbol_id]);$again=[];verify(count($accepted)===2);
echo 'Host constant invariants: ',$count," passed\n";
