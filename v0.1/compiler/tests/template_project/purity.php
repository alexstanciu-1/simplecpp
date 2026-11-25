<?php
declare(strict_types=1);
// Host-only retained-state evidence, including a failure after an earlier worker succeeds.
use template_project_test\Probe;
$count=0;
function verify(bool $ok): void { global $count; if (!$ok) { throw new LogicException('Template project purity assertion '.$count); } $count++; }
function rejected(callable $action): void { try { $action(); } catch (LogicException $error) { verify(true); return; } throw new LogicException('Expected batch rejection'); }
$catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
$main=Probe::file('/main.phs','return 0;');
$first=Probe::file('/a.phs','template<typename T> function first($x T): T { return $x; }');
$second=Probe::file('/b.phs','template<typename T> function second($x T): T { return first<T>($x); }');
$symbols=Probe::collect([$main,$first,$second],new \collect_symbols\Symbol_Store(1));
$names=Probe::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog);$empty=Probe::empty_set();
$before=serialize([$symbols,$names,$catalog,$empty]);
$cold=\check_templates\Template_Checker::check($symbols,$names,$catalog,$empty,false)->result();verify(serialize([$symbols,$names,$catalog,$empty])===$before);
$before=serialize([$symbols,$names,$catalog,$cold]);
$warm=\check_templates\Template_Checker::check($symbols,$names,$catalog,$cold,false)->result();verify($warm->selected_count===0);
$plan=new \check_templates\Template_Plan($symbols,$names,$catalog,$cold,true);$results=[];
for($i=0;$i<$plan->task_count();$i++){$results[]=\check_templates\Template_Worker::create($plan->task_at($i),$symbols,$names,$catalog)->check();}
$join=new \check_templates\Template_Join($plan);$accepted=$join->join(array_reverse($results));verify($accepted->size()===2);
verify(serialize([$symbols,$names,$catalog,$cold])===$before);
rejected(fn()=>$join->join([$results[0]]));rejected(fn()=>$join->join([$results[0],$results[0]]));verify(serialize([$symbols,$names,$catalog,$cold])===$before);
$bad=Probe::file('/b.phs','template<typename T> function bad($x T): int { echo $x; return 0; }');
$changed=Probe::collect([$main,$first,$bad],$symbols);$changed_names=Probe::resolve($changed,$names,$catalog);
$frozen=serialize([$changed,$changed_names,$catalog,$cold]);
$failed=\check_templates\Template_Checker::check($changed,$changed_names,$catalog,$cold,true);verify(!$failed->valid());verify($failed->diagnostic->path==='/b.phs');rejected(fn()=>$failed->result());
verify(serialize([$changed,$changed_names,$catalog,$cold])===$frozen);verify(serialize([$symbols,$names,$catalog,$cold])===$before);
$again=\check_templates\Template_Checker::check($symbols,$names,$catalog,$cold,false)->result();verify($again->selected_count===0);
echo 'Host template project invariants: ',$count," passed\n";
