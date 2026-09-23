<?php
declare(strict_types=1);
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Application purity assertion '.$count);}$count++;}
$catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
foreach(['Target<int,7>','Target<Source,7>','Target<void,7>'] as $application){
    $source=new \read_sources\Source_Buffer();$source->path='/purity.phs';
    $source->content='struct Source { public int32 $value; } template<typename T,int N> struct Target { public T $value; } $value '.$application.'; return 0;';
    $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
    if(!$file->valid){throw new LogicException($file->error_reason);}
    $files=new \parse\Frontend_Set();$files->add($file);$files->entry_index=0;
    $symbols=\collect_symbols\Declaration_Collector::with_providers($files,new \collect_symbols\Symbol_Store(1),false,[])->current;
    $names=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false)->result();
    $checks=\check_templates\Template_Checker::check($symbols,$names,$catalog,new \check_templates\Template_Set([],0),false)->result();
    $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
    $instances=new \instantiate\Instance_Set(new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage)),$checks);
    $reader=new \instantiate\Bindings(new \resolve_types\Annotation_Types($names,new \resolve_types\Definition_View($catalog,$types)),$catalog,$instances->view());
    $owner=$symbols->symbol_by_id($symbols->entry_symbol_id('/purity.phs'));
    $task=new \instantiate\Application_Task(\instantiate\Instance_Context::ordinary($owner),$names->for_symbol($owner->symbol_id)->applications_at(0));
    $before=serialize([$files,$symbols,$names,$checks,$types,$instances,$task]);$rejected=false;
    try{$result=\instantiate\Application_Worker::run($task,$reader);}catch(RuntimeException $error){$rejected=true;}
    verify(serialize([$files,$symbols,$names,$checks,$types,$instances,$task])===$before);
    verify($rejected===($application==='Target<void,7>'));
    if($rejected){verify($reader->annotations->diagnostic()!==null);continue;}
    verify($reader->annotations->diagnostic()===null);
    $args=$result->arguments();$pending=$result->ready()?[]:[$result->prerequisite_at(0)];
    $copy=new \instantiate\Application_Result($task,$args,$pending);$args=[];$pending=[];
    verify($copy->argument_count()===$result->argument_count());
    verify($copy->prerequisite_count()===$result->prerequisite_count());
    $export=$copy->arguments();$export=[];verify($copy->argument_count()===$result->argument_count());
    foreach([-1,$copy->argument_count()] as $index){$bad=false;try{$copy->argument_at($index);}catch(OutOfBoundsException $error){$bad=true;}verify($bad);}
    foreach([-1,$copy->prerequisite_count()] as $index){$bad=false;try{$copy->prerequisite_at($index);}catch(OutOfBoundsException $error){$bad=true;}verify($bad);}
}
echo 'Host application invariants: ',$count," passed\n";
