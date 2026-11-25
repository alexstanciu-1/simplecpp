<?php
declare(strict_types=1);
namespace package_composition_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index);
            $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
            $life_ops /** vector<\type_model\Lifecycle_Operation> */ = [];
            $word = new \type_model\Named_Definition('Int','',\type_model\Representation::integer(64),new \type_model\Lifetime_Contract($policy,$life_ops),true,'',false,false,true);
            $void_definition = new \type_model\Named_Definition('Void','',\type_model\Representation::void_type(),null,null,'',false,false,false);
            $definitions /** vector<\type_model\Named_Definition> */ = [$word,$void_definition];
            $catalog = new \type_model\Type_Catalog('language','base','language_values',$definitions,$word,$word,null);
            $references /** hash<\type_model\Type_Reference> */ = []; $call_bindings /** hash<\type_model\Type_Reference> */ = [];
            $imports /** hash<\load_runtime\Runtime_Type_Import> */ = []; $sources /** hash<\prepare_backend\Source_Type_Export> */ = [];
            if ($fixture->member('bound')->boolean()) { $references['word'] = \type_model\Type_Reference::named('Int',''); }
            $bindings = new \load_runtime\Package_Bindings($references,$call_bindings,$imports,$sources);
            $context = new \load_runtime\Package_Context('/provider','manifest',$catalog,$bindings,null);
            $artifacts /** hash<string> */ = []; $modules /** hash<string> */ = []; $arguments /** vector<string> */ = [];
            $manifest = new \load_runtime\Package_Manifest('p','input',new \load_runtime\Package_Target('t','d'),$fixture->member('project')->boolean(),$artifacts,'metadata',$modules,'clang',$arguments,$artifacts);
            $metadata = new \load_runtime\Package_Metadata(\load_runtime\Package_Syntax::rows($fixture->member('rows'),'types'),\load_runtime\Package_Syntax::rows($fixture->member('operations'),'operations'));
            $accepted = true; $matches = true;
            try {
                $result = \load_runtime\Package_Composition::build(new \load_runtime\Package_Composition_Input($context,$manifest,$metadata,'composed'));
                if (!$fixture->member('accept')->boolean()) { $matches = false; }
                else {
                    $matches = (q_count($result->types) === q_count($metadata->types)) && ($result->catalog->size() === $fixture->member('definitions')->integer())
                        && ($result->catalog->record_count() === $fixture->member('records')->integer()) && (q_count($result->families) === $fixture->member('families')->integer())
                        && (q_count($result->callables) === $fixture->member('calls')->integer()) && (($result->catalog !== $catalog) === $fixture->member('composed')->boolean())
                        && ($result->catalog->integer_literal_type === $word) && ($result->catalog->entry_return_type === $word) && ($catalog->size() === 2) && ($catalog->record_count() === 0);
                    if ($fixture->member('composed')->boolean()) { if ($result->catalog->content_key !== 'composed') { $matches = false; } }
                    if (isset($result->types['row'])) { if ($result->types['row']->record !== $result->catalog->record_at(0)) { $matches = false; } }
                    if (isset($result->families['descriptor'])) {
                        if (($result->families['descriptor']->descriptor !== $result->types['descriptor']->language_type) || ($result->catalog->find_type('Storage','') !== null)) { $matches = false; }
                    }
                    $paths /** vector<string> */ = []; $source_imports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
                    $directory = $fixture->member('foreign')->boolean() ? '/elsewhere' : '/provider';
                    $previous = new \load_runtime\Runtime_Package('p',$directory,'t','d','clang',$arguments,$result->types,$result->callables,$modules,$paths,'old',$catalog,$result->catalog,$result->families,$bindings,null,$source_imports);
                    $next = \load_runtime\Package_Composition::build(new \load_runtime\Package_Composition_Input($context,$manifest,$metadata,'next',$previous));
                    if ($fixture->member('bound')->boolean()) {
                        $same = $next->types['word'] === $result->types['word'];
                        if ($same === $fixture->member('foreign')->boolean()) { $matches = false; }
                    }
                    for ($slot = 0; $slot < q_count($result->callables); $slot++) { if ($next->callables[$slot] !== $result->callables[$slot]) { $matches = false; } }
                }
            } catch (\RuntimeException $error) { $accepted = false; }
            catch (\InvalidArgumentException $error) { $accepted = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
}
