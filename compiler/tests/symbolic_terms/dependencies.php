<?php
$p=new \type_model\Lifetime_Policy(); $p->copy=1; $p->construction=1; $p->assignment=1; $p->expiring=1;
$word=new \type_model\Named_Definition('int','',\type_model\Representation::integer(32),new \type_model\Lifetime_Contract($p,[]),true,'',false,false,true);
$catalog=new \type_model\Type_Catalog('p','key','language_values',[$word],$word,$word,null);
$source=new \read_sources\Source_Buffer(); $source->path='/deps.phs'; $source->content='template<typename T> function identity($x T): T { return $x; } return 0;';
$file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
if (!$file->valid) { throw new RuntimeException($file->error_reason); }
$frontends=new \parse\Frontend_Set(); $frontends->add($file); $frontends->entry_index=0;
$refresh=\collect_symbols\Declaration_Collector::collect($frontends,new \collect_symbols\Symbol_Store(1),false);
if (!$refresh->valid) { throw new RuntimeException($refresh->error_reason); }
$symbols=$refresh->current;
$resolve=fn()=>\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,true)->result();
$names=$resolve(); $fresh=$resolve();
$owner=$symbols->symbol_by_id($symbols->find_symbol('identity',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0));
$task=new \check_templates\Definition_Task($owner,$names->for_symbol($owner->symbol_id));
$other=new \collect_symbols\Symbol_Record($owner->symbol_id,$owner->owner_symbol_id,$owner->name,$owner->frontend,$owner->declaration);
$declaration_stale=new \check_templates\Definition_Result($task,$catalog,[$other],[],1);
$binding_stale=new \check_templates\Definition_Result($task,$catalog,[],[$fresh->for_symbol($owner->symbol_id)],1);
if ($declaration_stale->current($owner,$names,$catalog)) { throw new RuntimeException('Missed changed declaration dependency'); }
if ($binding_stale->current($owner,$names,$catalog)) { throw new RuntimeException('Missed changed binding dependency'); }
echo json_encode(['dependency_checks'=>2]),"\n";
