<?php
declare(strict_types=1);
namespace concrete_bindings_test;
final class Probe {
    private static function file(string $text): \parse\Parse_Result {
        $source=new \read_sources\Source_Buffer();$source->path='/bindings.phs';$source->content=$text;
        $file=\parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));if(!$file->valid){throw new \LogicException($file->error_reason);}return $file;
    }
    private static function collect(\parse\Parse_Result $file): \collect_symbols\Symbol_Store {
        $files=new \parse\Frontend_Set();$files->add($file);$files->entry_index=0;
        return \collect_symbols\Declaration_Collector::collect($files,new \collect_symbols\Symbol_Store(1),false)->current;
    }
    public static function run(string $text): void {
        $cases=json_read($text);$base=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        for($ci=0;$ci<$cases->size();$ci++) {
            $mode=$cases->at($ci)->text();$ok=true;$annotation='int';
            if(($mode==='source_pending')||($mode==='source_ready')||($mode==='required_pending')){$annotation='Source';}
            if(($mode==='record_pending')||($mode==='record_ready')){$annotation='Record';}
            if($mode==='byte_span'){$annotation='bytes';}
            $rows /** vector<\type_model\Named_Definition> */ = [];for($i=0;$i<$base->size();$i++){$rows[]=$base->definition_at($i);}
            $bytes=new \type_model\Named_Definition('bytes','',\type_model\Representation::byte_span(),$base->integer_literal_type->lifetime,null,'',false,false,false);$rows[]=$bytes;
            $field_type=$base->find_type('int32','');if($field_type===null){throw new \LogicException('Missing fixture field type');}
            $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('value',new \type_model\Field_Type($field_type,0),true)];
            $record=new \type_model\Record_Declaration('Record','',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0);
            $records /** vector<\type_model\Record_Declaration> */ = [$record];
            $catalog=new \type_model\Type_Catalog($base->provider,$base->content_key,$base->representation_scope,$rows,$base->integer_literal_type,$base->entry_return_type,$base->boolean_type,$records);
            $literal='7';if($mode==='leading_zero'){$literal='0007';}if($mode==='range_error'){$literal='999999999999999999999999999999999999';}
            $source='struct Source { public int $value; } const N: int = '.$literal.'; template<typename T,int K> struct Box { public T $value; } template<typename T,int K> function generic($x T): Box<T,K> { return new Box<T,K>(); } function plain($x '.$annotation.'): int { return N + 1; }';
            $file=Probe::file($source);$symbols=Probe::collect($file);
            $names=\resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false)->result();
            $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));$view=new \resolve_types\Definition_View($catalog,$types);
            $plain=$symbols->symbol_by_id($symbols->find_symbol('plain',\collect_symbols\SYMBOL_FUNCTION,0,''));
            $generic=$symbols->symbol_by_id($symbols->find_symbol('generic',\collect_symbols\SYMBOL_TEMPLATE_FUNCTION,0,''));
            $box=$symbols->symbol_by_id($symbols->find_symbol('Box',\collect_symbols\SYMBOL_TEMPLATE_STRUCT,0,''));
            $constant=$symbols->symbol_by_id($symbols->find_symbol('N',\collect_symbols\SYMBOL_CONSTANT,0,''));
            $parts=\parse\Syntax_Access::function_parts($file->tree,(int)$plain->source_fact()->declaration_node_id);
            $parameter=\parse\Syntax_Access::first_parameter($file->tree,(int)$parts->parameters_id);
            $node=(int)\parse\Syntax_Access::parameter_parts($file->tree,$parameter)->type_syntax_id;
            $generic_parts=\parse\Syntax_Access::function_parts($file->tree,\parse\Syntax_Access::underlying_declaration($file->tree,(int)$generic->source_fact()->declaration_node_id));
            $application=(int)$generic_parts->return_type_id;$app_parts=\parse\Syntax_Access::template_application_parts($file->tree,$application);
            $type_node=(int)$app_parts->first_argument_id;$integer_node=(int)$file->tree->row($type_node)->next_sibling;
            $constant_parts=\parse\Syntax_Access::constant_parts($file->tree,(int)$constant->source_fact()->declaration_node_id);
            $literal_node=(int)$constant_parts->initializer_id;
            $global_node=0;$expression=0;
            for($i=1;$i<$file->tree->size()+1;$i++){
                $row=$file->tree->row($i);
                if((int)$row->kind===\parse\SYNTAX_ADDITION_EXPRESSION){$expression=$i;$global_node=(int)$row->first_child;}
            }
            $arguments /** vector<\instantiate\Template_Argument> */ = [new \instantiate\Template_Argument($base->integer_literal_type),new \instantiate\Template_Argument($base->integer_literal_type,'7')];
            if($mode==='value_as_type'){$arguments[0]=new \instantiate\Template_Argument($base->integer_literal_type,'1');}
            if($mode==='type_as_value'){$arguments[1]=new \instantiate\Template_Argument($base->integer_literal_type);}
            if(($mode==='missing_parameter')||($mode==='missing_integer_parameter')){$empty_args /** vector<\instantiate\Template_Argument> */ = [];$arguments=$empty_args;}
            $state=new \instantiate\Instance_State(new \instantiate\Instance_Identities($types->lineage));
            $value=new \instantiate\Template_Argument($base->integer_literal_type,'7');
            if($mode!=='missing_constant'){$state->constants[$constant->symbol_id]=$value;}
            $empty_checks /** vector<\check_templates\Definition_Result> */ = [];
            $store=new \instantiate\Instance_Store($state,new \check_templates\Template_Set($empty_checks,0));
            $instance=new \instantiate\Instance_Context($generic,1,$arguments);$plain_context=\instantiate\Instance_Context::ordinary($plain);
            if($mode==='catalog_only'){$view=new \resolve_types\Definition_View($catalog);}
            if($mode==='stale_provided'){$view=new \resolve_types\Definition_View(\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json')));}
            $prepared=$base->integer_literal_type;
            if($mode==='source_ready'){$source_record=new \type_model\Record_Declaration('Source','',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0);$source_id=\resolve_types\Record_Definitions::materialize($types,$source_record);$prepared=$types->definition_for_type($source_id);}
            if($mode==='record_ready'){$record_id=\resolve_types\Record_Definitions::materialize($types,$record);$prepared=$types->definition_for_type($record_id);}
            if($mode==='application_ready'){$target=new \instantiate\Instance_Context($box,2,$arguments);$store->accept($target);$store->accept_type($target,$prepared);$store->bind($instance,$application,$target);}
            if($mode==='missing_bindings'){$names=new \resolve_symbols\Resolution_Set(null);}
            if($mode==='stale_bindings'){$other=Probe::collect(Probe::file($source));$plain=$other->symbol_by_id($other->find_symbol('plain',\collect_symbols\SYMBOL_FUNCTION,0,''));$plain_context=\instantiate\Instance_Context::ordinary($plain);}
            $annotations=new \resolve_types\Annotation_Types($names,$view);$reader=new \instantiate\Bindings($annotations,$catalog,$store->view());
            $expected='';$internal=false;
            if(($mode==='stale_provided')||($mode==='missing_parameter')||($mode==='value_as_type')||($mode==='missing_bindings')||($mode==='stale_bindings')||($mode==='dependent_direct')||($mode==='wrong_role')){$internal=true;}
            if(($mode==='type_as_value')||($mode==='missing_integer_parameter')||($mode==='missing_constant')||($mode==='expression')){$expected='constant evaluation';}
            if(($mode==='required_pending')||($mode==='required_application')){$expected='requires unsupported concrete preparation';}
            if($mode==='range_error'){$expected='outside the range';}
            if($mode==='nonliteral'){$expected='initializer must be an integer literal';}
            if($mode==='direct_application'){$expected='requires instance preparation';}
            if($mode==='byte_span'){$expected='Byte spans';}
            $failure_node=$node;
            if(($mode==='type_as_value')||($mode==='missing_integer_parameter')){$failure_node=$integer_node;}
            if($mode==='missing_constant'){$failure_node=$global_node;}
            if($mode==='expression'){$failure_node=$expression;}
            if($mode==='range_error'){$failure_node=$literal_node;}
            if(($mode==='direct_application')||($mode==='required_application')){$failure_node=$application;}
            $rejected=false;
            try {
                if(($mode==='type_parameter')||($mode==='missing_parameter')||($mode==='value_as_type')){$ok=$reader->type($instance,$type_node)===$prepared;}
                elseif(($mode==='integer_parameter')||($mode==='type_as_value')||($mode==='missing_integer_parameter')){$read=$reader->value($instance,$integer_node);$ok=($read===$arguments[1])&&($read->value==='7');}
                elseif(($mode==='literal')||($mode==='leading_zero')||($mode==='range_error')){$read=$reader->value(\instantiate\Instance_Context::ordinary($constant),$literal_node);$ok=($read->type===$base->integer_literal_type)&&($read->value==='7');}
                elseif($mode==='nonliteral'){$reader->literal($plain,$node,$base->integer_literal_type);}
                elseif(($mode==='global_constant')||($mode==='missing_constant')){$ok=$reader->value($plain_context,$global_node)===$value;}
                elseif($mode==='expression'){$reader->value($plain_context,$expression);}
                elseif(($mode==='application_pending')||($mode==='application_ready')){$read=$reader->type($instance,$application);$ok=$mode==='application_pending' ? $read===null : $read===$prepared;}
                elseif($mode==='direct_application'){$annotations->bound_definition($generic,$application);}
                elseif($mode==='required_application'){\resolve_types\Annotation_Types::definition($instance,$application,'return',$reader);}
                elseif($mode==='dependent_direct'){$annotations->bound_definition($generic,$type_node);}
                elseif($mode==='wrong_role'){$annotations->bound_definition($plain,$global_node);}
                elseif(($mode==='required_pending')||($mode==='byte_span')){\resolve_types\Annotation_Types::definition($plain_context,$node,'parameter',$reader);}
                else {$read=$reader->type($plain_context,$node);$ok=(($mode==='source_pending')||($mode==='record_pending')) ? $read===null : $read===$prepared;}
            } catch(\LogicException $error){$rejected=true;$ok=$internal&&($annotations->diagnostic()===null);}
            catch(\RuntimeException $error){$rejected=true;$diagnostic=$annotations->diagnostic();if($diagnostic===null){throw $error;}$ok=($expected!=='')&&(q_strpos($diagnostic->reason,$expected)!==false)&&($diagnostic->path==='/bindings.phs')&&($diagnostic->length===(int)$file->tree->row($failure_node)->length)&&($diagnostic->start===(int)$file->tree->row($failure_node)->start);}
            if($internal||($expected!=='')){$ok=$ok&&$rejected;}else{$ok=$ok&&!$rejected;}
            if(!$ok){throw new \LogicException('Concrete binding case failed: '.$mode);}echo "true\n";
        }
    }
}
