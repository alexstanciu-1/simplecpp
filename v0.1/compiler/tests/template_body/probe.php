<?php
declare(strict_types=1);
namespace template_body_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text); $catalog = \load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
        for ($index = 0; $index < $cases->size(); $index++) {
            $input = $cases->at($index); $source = new \read_sources\Source_Buffer();
            $source->path = '/template.phs'; $source->content = $input->member('source')->text();
            $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
            if (!$file->valid) { throw new \LogicException('Case ' . $index . ' parse: ' . $file->error_reason); }
            $files = new \parse\Frontend_Set(); $files->add($file); $files->entry_index = 0;
            $providers /** vector<\collect_symbols\Provider_Declaration> */ = [];
            if ($input->member('providers')->boolean()) { $providers = Fixtures::providers(); }
            $collected = \collect_symbols\Declaration_Collector::with_providers($files,new \collect_symbols\Symbol_Store(1),false,$providers);
            if (!$collected->valid) { throw new \LogicException('Case collection failed'); }
            $symbols = $collected->current;
            $attempt = \resolve_symbols\Symbol_Resolver::resolve($symbols,new \resolve_symbols\Resolution_Set(null),$catalog,false);
            if (!$attempt->valid()) { throw new \LogicException('Case ' . $index . ' names: ' . $attempt->error_reason); }
            $names = $attempt->result(); $count = 0; $reason = ''; $ok = true;
            for ($i = 0; $i < $symbols->size(); $i++) {
                $owner = $symbols->record_at($i);
                if (!$owner->is_source()) { continue; }
                if (!$owner->is_template()) { continue; }
                $bindings = $names->for_symbol($owner->symbol_id);
                if ($bindings === null) { throw new \LogicException('Missing test bindings'); }
                $worker = \check_templates\Template_Worker::create(new \check_templates\Definition_Task($owner,$bindings),$symbols,$names,$catalog);
                try {
                    $result = $worker->check(); $count++;
                    if (!$result->current($owner,$names,$catalog)) { $ok = false; }
                    if ($result->visited_nodes < 1) { $ok = false; }
                    if ($result->dependency_at(0) !== $owner) { $ok = false; }
                    if ($worker->diagnostic() !== null) { $ok = false; }
                    $reused = false;
                    try { $worker->check(); } catch (\LogicException $error) { $reused = true; }
                    $ok = $ok && $reused;
                } catch (\RuntimeException $error) {
                    $diagnostic = $worker->diagnostic();
                    if ($diagnostic === null) { throw $error; }
                    $reason = $diagnostic->reason;
                    $ok = $ok && ($diagnostic->path === $source->path) && ($diagnostic->start >= 0) && ($diagnostic->length > 0)
                        && (($diagnostic->start + $diagnostic->length) < (string_byte_len($source->content)+1));
                    break;
                }
            }
            $wanted = $input->member('reason')->text();
            if ($wanted === '') { $ok = $ok && ($reason === '') && ($count === $input->member('count')->integer()); }
            else { $ok = $ok && (q_strpos($reason,$wanted) !== false); }
            if (!$ok) { throw new \LogicException('Case ' . $index . ' failed: count=' . $count . ' reason=' . $reason); }
            echo "true\n";
        }
    }
}
final class Fixtures {
    public static function providers(): array /** vector<\collect_symbols\Provider_Declaration> */ {
        $parameters /** vector<\type_model\Family_Parameter> */ = [new \type_model\Family_Parameter('T',\type_model\GENERIC_COPYABLE_VALUE)];
        $formal = \type_model\Type_Reference::parameter('["p","family"]',0);
        $arguments /** vector<\type_model\Type_Reference> */ = [$formal]; $self = \type_model\Type_Reference::family('["p","family"]',$arguments);
        $requirements /** vector<\type_model\Capability_Requirement> */ = [new \type_model\Capability_Requirement(0,\type_model\LIFECYCLE_COPY)];
        $effects /** vector<\type_model\Element_Effect> */ = [];
        $result = new \type_model\Semantic_Result($formal,\type_model\RESULT_DEPENDENT_VALUE);
        $get_params /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($formal,\type_model\PASS_BORROW_CONST),new \type_model\Semantic_Parameter($self,\type_model\PASS_BORROW_CONST)];
        $touch_params /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($formal,\type_model\PASS_BORROW_MUTABLE),new \type_model\Semantic_Parameter($self,\type_model\PASS_BORROW_CONST)];
        $get = new \type_model\Family_Operation('get',new \type_model\Semantic_Signature($get_params,$result),$requirements,$effects,1,\type_model\Type_Reference::named('get',''));
        $touch = new \type_model\Family_Operation('touch',new \type_model\Semantic_Signature($touch_params,$result),$requirements,$effects,1,\type_model\Type_Reference::named('touch',''));
        $operations /** hash<\type_model\Family_Operation> */ = []; $operations['get'] = $get; $operations['touch'] = $touch;
        $lifecycle /** hash<string> */ = []; $mapping /** hash<\type_model\Type_Reference> */ = [];
        $definition = new \type_model\Family_Definition('p','family',$parameters,$operations,$lifecycle,\type_model\Type_Reference::named('Family',''));
        $family = new \type_model\Family_Declaration($definition,$mapping);
        $concrete_params /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter(\type_model\Type_Reference::provided('p','int'),\type_model\PASS_VALUE)];
        $concrete_result = new \type_model\Semantic_Result(\type_model\Type_Reference::provided('p','void'),\type_model\RESULT_NONE);
        $abi_params /** vector<\type_model\Runtime_Abi_Position> */ = [\type_model\Runtime_Abi_Position::integer(64,\type_model\ABI_EXTENSION_NONE)];
        $abi = new \type_model\Runtime_Callable_Abi('concrete_link','ccc',null,$abi_params);
        $callable = new \type_model\Runtime_Callable('p','concrete','concrete_provider','',new \type_model\Semantic_Signature($concrete_params,$concrete_result),$abi);
        $out /** vector<\collect_symbols\Provider_Declaration> */ = [
            new \collect_symbols\Provider_Declaration(null,null,null,$family,null),
            new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($family,$get)),
            new \collect_symbols\Provider_Declaration(null,null,null,null,new \type_model\Family_Method($family,$touch)),
            new \collect_symbols\Provider_Declaration($callable,null,null,null,null)];
        return $out;
    }
}
