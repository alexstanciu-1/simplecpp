<?php
declare(strict_types=1);
namespace package_context_test;
final class Probe {
    public static function catalog(): \type_model\Type_Catalog {
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $definition = new \type_model\Named_Definition('Int','',\type_model\Representation::integer(64),new \type_model\Lifetime_Contract($policy,$operations),true,'',false,false,false);
        $definitions /** vector<\type_model\Named_Definition> */ = [$definition];
        return new \type_model\Type_Catalog('p','key','language_values',$definitions,$definition,$definition,null);
    }
    private static function source_definition(\type_model\Lifetime_Contract $life): \prepare_backend\Source_Type_Export {
        $definition = new \type_model\Named_Definition('R','',\type_model\Representation::structure(0,0),$life,null,'',false,false,true);
        $config = new \prepare_backend\Backend_Configuration('b','t','d','','','a','r');
        $fields /** vector<\type_model\Type_Member> */ = []; $children /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
        $dependency = new \prepare_backend\Layout_Dependency(1,$definition,$fields,$children); $offsets /** vector<int> */ = [];
        $layout = new \prepare_backend\Storage_Layout($definition,$config,$fields,'{}',8,8,$offsets,new \type_model\Type_Lineage(),$dependency);
        $arguments /** vector<\resolve_types\Export_Argument> */ = [];
        $identity = \resolve_types\Export_Type_Identity::source('p','r.phs','','R',$arguments);
        $identities /** hash<\resolve_types\Export_Type_Identity,int> */ = []; $identities[1] = $identity;
        $capabilities /** hash<\prepare_backend\Source_Export_Capability> */ = [];
        $task = new \prepare_backend\Source_Export_Task(new \compile\Native_Project('p','/src','/out'),$identity,$layout,$identities,$capabilities);
        $exports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        return new \prepare_backend\Source_Type_Export($task,$exports);
    }
    public static function run(string $text): void {
        $catalog = Probe::catalog(); $integer = $catalog->find_type('Int','');
        if ($integer === null) { throw new \LogicException('Fixture catalog missing'); }
        $life = $integer->lifetime; if ($life === null) { throw new \LogicException('Fixture lifetime missing'); }
        $source_export = Probe::source_definition($life);
        $native = new \load_runtime\Runtime_Type('foreign',new \load_runtime\Runtime_Storage(0,8,8),64,true,$integer);
        $old_names /** hash<\type_model\Type_Reference> */ = []; $old_names['T'] = \type_model\Type_Reference::named('T',''); $old_names['U'] = \type_model\Type_Reference::named('U','');
        $old_calls /** hash<\type_model\Type_Reference> */ = []; $old_calls['f'] = \type_model\Type_Reference::named('F','');
        $old_imports /** hash<\load_runtime\Runtime_Type_Import> */ = []; $old_imports['N'] = new \load_runtime\Runtime_Type_Import('owner','foreign',$native,'t','d');
        $old_sources /** hash<\prepare_backend\Source_Type_Export> */ = []; $old_sources['S'] = $source_export;
        $old_bindings = new \load_runtime\Package_Bindings($old_names,$old_calls,$old_imports,$old_sources);
        $old_exports /** hash<\prepare_backend\Source_Type_Export> */ = []; $old_exports[$source_export->task->identity->key()] = $source_export;
        $old_project = new \load_runtime\Project_Binding('receipt',$old_exports);
        $types /** hash<\load_runtime\Runtime_Type> */ = []; $callables /** vector<\type_model\Runtime_Callable> */ = []; $strings /** vector<string> */ = []; $modules /** hash<string> */ = [];
        $families /** hash<\type_model\Storage_Family> */ = []; $source_imports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $mode = $fixture->member('mode')->integer();
            $directory = '/provider'; $manifest = 'manifest'; $current_catalog = $catalog;
            if ($mode === 1) { $directory = '/other'; } if ($mode === 2) { $manifest = 'manifest '; } if ($mode === 3) { $current_catalog = Probe::catalog(); }
            $names /** hash<\type_model\Type_Reference> */ = []; $calls /** hash<\type_model\Type_Reference> */ = [];
            if ($mode === 27) { $names['U'] = \type_model\Type_Reference::named('U',''); }
            $type_key = 'T'; $name = 'T'; if ($mode === 4) { $name = 'Changed'; } if ($mode === 5) { $type_key = 'Changed'; }
            $names[$type_key] = \type_model\Type_Reference::named($name,''); $names['U'] = \type_model\Type_Reference::named('U','');
            if ($mode === 6) { $names['extra'] = \type_model\Type_Reference::named('Extra',''); }
            $call_key = 'f'; $call_name = 'F'; if ($mode === 7) { $call_name = 'Changed'; } if ($mode === 8) { $call_key = 'Changed'; }
            $calls[$call_key] = \type_model\Type_Reference::named($call_name,''); if ($mode === 9) { $calls['extra'] = \type_model\Type_Reference::named('Extra',''); }
            $provider = 'owner'; $id = 'foreign'; $target = 't'; $layout = 'd'; $owner = $native;
            if ($mode === 10) { $provider = 'other'; } if ($mode === 11) { $id = 'other'; } if ($mode === 12) { $target = 'other'; } if ($mode === 13) { $layout = 'other'; }
            if ($mode === 14) { $owner = new \load_runtime\Runtime_Type('foreign',new \load_runtime\Runtime_Storage(0,8,8),64,true,$integer); }
            $imports /** hash<\load_runtime\Runtime_Type_Import> */ = []; $import_key = 'N'; if ($mode === 15) { $import_key = 'Other'; }
            if ($mode !== 28) { $imports[$import_key] = new \load_runtime\Runtime_Type_Import($provider,$id,$owner,$target,$layout); }
            $sources /** hash<\prepare_backend\Source_Type_Export> */ = []; $selected = $source_export;
            if ($mode === 16) { $selected = new \prepare_backend\Source_Type_Export($source_export->task,$source_export->operations); }
            $source_key = 'S'; if ($mode === 17) { $source_key = 'Other'; } if ($mode !== 29) { $sources[$source_key] = $selected; }
            $bindings = new \load_runtime\Package_Bindings($names,$calls,$imports,$sources); if ($mode === 31) { $bindings = $old_bindings; }
            $exports /** hash<\prepare_backend\Source_Type_Export> */ = []; $project_export = $source_export; $export_key = $source_export->task->identity->key();
            if ($mode === 19) { $project_export = new \prepare_backend\Source_Type_Export($source_export->task,$source_export->operations); }
            if ($mode === 20) { $export_key = 'other'; } if ($mode !== 30) { $exports[$export_key] = $project_export; }
            $receipt = 'receipt'; if ($mode === 18) { $receipt = 'receipt '; }
            $project = new \load_runtime\Project_Binding($receipt,$exports); if ($mode === 32) { $project = $old_project; }
            $previous = new \load_runtime\Runtime_Package('p','/provider','t','d','clang',$strings,$types,$callables,$modules,$strings,'manifest',$catalog,$catalog,$families,$old_bindings,$old_project,$source_imports);
            if (($mode === 22) || ($mode === 23)) { $previous = new \load_runtime\Runtime_Package('p','/provider','t','d','clang',$strings,$types,$callables,$modules,$strings,'manifest',$catalog,$catalog,$families,null,$old_project,$source_imports); }
            if (($mode === 25) || ($mode === 26)) { $previous = new \load_runtime\Runtime_Package('p','/provider','t','d','clang',$strings,$types,$callables,$modules,$strings,'manifest',$catalog,$catalog,$families,$old_bindings,null,$source_imports); }
            $context = new \load_runtime\Package_Context($directory,$manifest,$current_catalog,$bindings,$project);
            if (($mode === 21) || ($mode === 23)) { $context = new \load_runtime\Package_Context($directory,$manifest,$current_catalog,null,$project); }
            if (($mode === 24) || ($mode === 26)) { $context = new \load_runtime\Package_Context($directory,$manifest,$current_catalog,$bindings,null); }
            $matches = $previous->matches($context) === $fixture->member('accept')->boolean();
            if (($old_imports['N']->type !== $native) || ($old_sources['S'] !== $source_export) || ($old_project->receipt !== 'receipt')) { $matches = false; }
            echo $matches ? "true\n" : "false\n";
        }
    }
}
