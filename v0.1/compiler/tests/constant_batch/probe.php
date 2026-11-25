<?php
declare(strict_types=1);
namespace constant_batch_test;
final class Probe {
    public static function file(string $text): \parse\Parse_Result {
        $source=new \read_sources\Source_Buffer();$source->path='/constants.phs';$source->content=$text;
        $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));if(!$file->valid){throw new \LogicException($file->error_reason);}return $file;
    }
    public static function collect(\parse\Parse_Result $file): \collect_symbols\Symbol_Store {
        $files=new \parse\Frontend_Set();$files->add($file);$files->entry_index=0;
        return \collect_symbols\Declaration_Collector::collect($files,new \collect_symbols\Symbol_Store(1),false)->current;
    }
    public static function reader(\collect_symbols\Symbol_Store $symbols, \type_model\Type_Catalog $catalog): \instantiate\Bindings {
        $names=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false)->result();
        $annotations=new \resolve_types\Annotation_Types($names,new \resolve_types\Definition_View($catalog));
        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
        $state=new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage));
        $empty /** vector<\check_templates\Definition_Result> */ = [];
        $set=new \instantiate\Instance_Set($state,new \check_templates\Template_Set($empty,0));
        return new \instantiate\Bindings($annotations,$catalog,$set->view());
    }
    public static function run(string $text): void {
        $cases=json_read($text);$catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        for($ci=0;$ci<$cases->size();$ci++) {
            $mode=$cases->at($ci)->text();$literal='0007';$annotation='';$wanted='7';$semantic='';$internal=false;
            if($mode==='zero'){$literal='000';$wanted='0';}
            if($mode==='unsigned_max'){$annotation=': uint8';$literal='255';$wanted='255';}
            if($mode==='signed_max'){$annotation=': int32';$literal='2147483647';$wanted='2147483647';}
            if($mode==='signed_overflow'){$annotation=': int32';$literal='2147483648';$semantic='outside the range';}
            if($mode==='unsigned_overflow'){$annotation=': uint8';$literal='256';$semantic='outside the range';}
            if($mode==='noninteger'){$annotation=': void';$semantic='Only integer';}
            if($mode==='expression'){$literal='1+2';$semantic='initializer must be';}
            if($mode==='reference'){$literal='B';$semantic='initializer must be';}
            $source='const B: int = 3; const A'.$annotation.' = '.$literal.'; return 0;';
            $file=Probe::file($source);$symbols=Probe::collect($file);$reader=Probe::reader($symbols,$catalog);
            $a=$symbols->symbol_by_id($symbols->find_symbol('A',\collect_symbols\SYMBOL_CONSTANT,0,''));
            $b=$symbols->symbol_by_id($symbols->find_symbol('B',\collect_symbols\SYMBOL_CONSTANT,0,''));
            $tasks /** vector<\collect_symbols\Symbol_Record> */ = [$a,$b];
            if($mode==='duplicate_tasks'){$tasks=[$a,$a];$internal=true;}
            if($mode==='wrong_owner'){$tasks=[$symbols->symbol_by_id($symbols->entry_symbol_id('/constants.phs'))];$internal=true;}
            if($mode==='stale_owner'){$other=Probe::collect(Probe::file($source));$tasks=[$other->symbol_by_id($other->find_symbol('A',\collect_symbols\SYMBOL_CONSTANT,0,''))];$internal=true;}
            if($mode==='empty'){$empty_tasks /** vector<\collect_symbols\Symbol_Record> */ = [];$tasks=$empty_tasks;}
            if(($mode==='missing')||($mode==='extra')||($mode==='wrong_id')||($mode==='wrong_type')||($mode==='wrong_value')||($mode==='unnormalized')||($mode==='absent_value')){$internal=true;}
            $rejected=false;$ok=true;
            try {
                $join=new \instantiate\Constant_Join($tasks,$reader);
                $results /** hash<\instantiate\Template_Argument,int> */ = [];
                if($mode!=='empty') {
                    $first=\instantiate\Constant_Worker::resolve($a,$reader);$second=\instantiate\Constant_Worker::resolve($b,$reader);
                    if($mode==='reverse'){$results[$b->symbol_id]=$second;$results[$a->symbol_id]=$first;}
                    else{$results[$a->symbol_id]=$first;$results[$b->symbol_id]=$second;}
                    if($mode==='missing'){$partial /** hash<\instantiate\Template_Argument,int> */ = [];$partial[$a->symbol_id]=$first;$results=$partial;}
                    if($mode==='extra'){$results[999]=$first;}
                    if($mode==='wrong_id'){$wrong /** hash<\instantiate\Template_Argument,int> */ = [];$wrong[999]=$first;$wrong[$b->symbol_id]=$second;$results=$wrong;}
                    if($mode==='wrong_type'){$other_type=$catalog->find_type('uint8','');if($other_type===null){throw new \LogicException('Missing fixture type');}$results[$a->symbol_id]=new \instantiate\Template_Argument($other_type,'7');}
                    if($mode==='wrong_value'){$results[$a->symbol_id]=new \instantiate\Template_Argument($first->type,'8');}
                    if($mode==='unnormalized'){$results[$a->symbol_id]=new \instantiate\Template_Argument($first->type,'0007');}
                    if($mode==='absent_value'){$results[$a->symbol_id]=new \instantiate\Template_Argument($first->type);}
                }
                $accepted=$join->join($results);
                if($mode==='empty'){$ok=q_count($accepted)===0;}
                else {
                    $ok=(q_count($accepted)===2)&&($accepted[$a->symbol_id]->value===$wanted)&&($accepted[$b->symbol_id]->value==='3');
                    $ids /** vector<int> */ = [];foreach($accepted as $id=>$value){$ids[]=$id;}
                    $ok=$ok&&($ids[0]===$a->symbol_id)&&($ids[1]===$b->symbol_id)&&($accepted[$a->symbol_id]===$results[$a->symbol_id]);
                    if($mode==='registry'){
                        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));$state=new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage));
                        $state->constants=$accepted;foreach($tasks as $task){$state->constant_owners[$task->symbol_id]=$task;}
                        $empty /** vector<\check_templates\Definition_Result> */ = [];$set=new \instantiate\Instance_Set($state,new \check_templates\Template_Set($empty,0));
                        $ok=$ok&&($set->constant_for($a->symbol_id)===$accepted[$a->symbol_id])&&($set->constant_owner($a->symbol_id)===$a);
                    }
                }
            }catch(\LogicException $error){$rejected=true;$ok=$internal&&($reader->annotations->diagnostic()===null);}
            catch(\RuntimeException $error){$rejected=true;$diagnostic=$reader->annotations->diagnostic();if($diagnostic===null){throw $error;}
                $parts=\parse\Syntax_Access::constant_parts($file->tree,(int)$a->source_fact()->declaration_node_id);$anchor=(int)$parts->initializer_id;if($mode==='noninteger'){$anchor=(int)$parts->type_syntax_id;}$row=$file->tree->row($anchor);
                $ok=($semantic!=='')&&(q_strpos($diagnostic->reason,$semantic)!==false)&&($diagnostic->path==='/constants.phs')&&($diagnostic->start===(int)$row->start)&&($diagnostic->length===(int)$row->length);
            }
            if($internal||($semantic!=='')){$ok=$ok&&$rejected;}else{$ok=$ok&&!$rejected;}
            if(!$ok){throw new \LogicException('Constant batch case failed: '.$mode);}echo "true\n";
        }
    }
}
