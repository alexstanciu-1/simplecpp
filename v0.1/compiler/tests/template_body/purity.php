<?php
declare(strict_types=1);
// Host-only snapshot and rejection checks; the converted probe proves shared outcomes.
$count = 0;
function ensure(bool $ok): void { global $count; if (!$ok) { throw new LogicException('Template purity assertion ' . $count); } $count++; }
function rejected(callable $call): void { try { $call(); } catch (LogicException $error) { ensure(true); return; } throw new LogicException('Expected task rejection'); }
$catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
foreach ([false,true] as $bad) {
    $source = new \read_sources\Source_Buffer(); $source->path='/purity.phs';
    $source->content='template<typename T> function identity($x T): T { return '.($bad?'$x + $x':'$x').'; }';
    $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));ensure($file->valid);
    $files=new \parse\Frontend_Set();$files->add($file);$files->entry_index=0;
    $symbols=\collect_symbols\Declaration_Collector::collect($files,new \collect_symbols\Symbol_Store(1),false)->current;
    $names=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false)->result();
    $owner=$symbols->symbol_by_id($symbols->find_symbol('identity',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0,''));
    $task=new \check_templates\Definition_Task($owner,$names->for_symbol($owner->symbol_id));
    $before=serialize([$symbols,$names,$catalog,$task]);
    $worker=\check_templates\Template_Worker::create($task,$symbols,$names,$catalog);
    try { $result=$worker->check();ensure(!$bad);ensure($result->current($owner,$names,$catalog)); }
    catch (RuntimeException $error) { ensure($bad);ensure($worker->diagnostic()!==null); }
    ensure(serialize([$symbols,$names,$catalog,$task])===$before);
    rejected(fn()=>$worker->check());
    $rebuilt=\collect_symbols\Declaration_Collector::collect($files,$symbols,true)->current;
    $stale=\check_templates\Template_Worker::create($task,$rebuilt,$names,$catalog);
    rejected(fn()=>$stale->check());ensure($stale->diagnostic()===null);
    $rebinding=\resolve_symbols\Symbol_Resolver::resolve($symbols,$names,$catalog,true)->result();
    $stale=\check_templates\Template_Worker::create($task,$symbols,$rebinding,$catalog);
    rejected(fn()=>$stale->check());ensure($stale->diagnostic()===null);
    ensure(serialize([$symbols,$names,$catalog,$task])===$before);
}
echo 'Host template invariants: ',$count," passed\n";
