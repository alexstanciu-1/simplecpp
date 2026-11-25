<?php
declare(strict_types=1);
namespace runtime_package_test;
final class Probe {
    private static function source_definition(\type_model\Lifetime_Contract $life): \prepare_backend\Source_Type_Export {
        $definition = new \type_model\Named_Definition('R','',\type_model\Representation::structure(0,0),$life,null,'',false,false,true);
        $config = new \prepare_backend\Backend_Configuration('b','t','d','','','a','r');
        $fields /** vector<\type_model\Type_Member> */ = []; $children /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
        $dependency = new \prepare_backend\Layout_Dependency(1,$definition,$fields,$children);
        $offsets /** vector<int> */ = [];
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
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $mask = $fixture->member('mask')->integer(); $binding = $fixture->member('binding')->integer();
            $policy = new \type_model\Lifetime_Policy(); $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
            $expected /** vector<\type_model\Lifecycle_Operation> */ = []; $roles /** vector<int> */ = [2,3,4,5,1]; $remaining = $mask;
            foreach ($roles as $role) {
                $enabled = ($remaining % 2) === 1; $remaining = (int)($remaining / 2);
                if (!$enabled) { continue; }
                $operation = \type_model\Lifecycle_Operation::runtime('p','op' . $role,'link' . $role,'ccc',$role);
                $operations[] = $operation; $expected[] = $operation;
                if ($role === 2) { $policy->cleanup = 1; }
                elseif ($role === 3) { $policy->copy = 2; }
                elseif ($role === 4) { $policy->expiring = 3; }
                elseif ($role === 5) { $policy->assignment = 2; }
                else { $policy->construction = 2; }
            }
            $life = new \type_model\Lifetime_Contract($policy,$operations);
            $definition = new \type_model\Named_Definition('I','',\type_model\Representation::integer(64),$life,true,'',false,false,false);
            $definitions /** vector<\type_model\Named_Definition> */ = [$definition];
            $catalog = new \type_model\Type_Catalog('p','key','language_values',$definitions,$definition,$definition,null);
            $storage = new \load_runtime\Runtime_Storage(0,8,8);
            $first = new \load_runtime\Runtime_Type('first',$storage,64,true,$definition);
            $second_operations /** vector<\type_model\Lifecycle_Operation> */ = [];
            foreach ($operations as $operation) { $second_operations[] = \type_model\Lifecycle_Operation::runtime('p','second' . $operation->kind,'second_link' . $operation->kind,'ccc',$operation->kind); }
            $second_definition = new \type_model\Named_Definition('J','',\type_model\Representation::integer(64),new \type_model\Lifetime_Contract($policy,$second_operations),true,'',false,false,false);
            $second = new \load_runtime\Runtime_Type('second',$storage,64,true,$second_definition);
            $unexposed = new \load_runtime\Runtime_Type('hidden',$storage,64,true,null);
            $types /** hash<\load_runtime\Runtime_Type> */ = []; $types['second'] = $second; $types['hidden'] = $unexposed; $types['first'] = $first;
            $references /** hash<\type_model\Type_Reference> */ = []; $imports /** hash<\load_runtime\Runtime_Type_Import> */ = [];
            if ($binding === 1) { $imports['second'] = new \load_runtime\Runtime_Type_Import('p','second',$second,'t','d'); }
            if ($binding === 2) { $references['second'] = \type_model\Type_Reference::named('I',''); }
            $sources /** hash<\prepare_backend\Source_Type_Export> */ = [];
            if ($binding === 3) {
                $source_export = Probe::source_definition($life); $sources['second'] = $source_export;
                $second = new \load_runtime\Runtime_Type('second',new \load_runtime\Runtime_Storage(5,8,8),null,null,$source_export->task->layout->definition);
                $types['second'] = $second;
            }
            $bindings = new \load_runtime\Package_Bindings($references,$references,$imports,$sources);
            $semantic /** vector<\type_model\Semantic_Parameter> */ = []; $physical /** vector<\type_model\Runtime_Abi_Position> */ = [];
            $signature = new \type_model\Semantic_Signature($semantic,new \type_model\Semantic_Result(\type_model\Type_Reference::named('Void',''),0));
            $callable = new \type_model\Runtime_Callable('p','f','f','',$signature,new \type_model\Runtime_Callable_Abi('f','ccc',null,$physical));
            $callables /** vector<\type_model\Runtime_Callable> */ = [$callable]; $modules /** hash<string> */ = []; $modules['ordinary'] = 'runtime.bc'; $modules['thin_lto'] = 'runtime.thin.bc';
            $paths /** vector<string> */ = ['/protected']; $arguments /** vector<string> */ = ['-lm'];
            $families /** hash<\type_model\Storage_Family> */ = []; $source_imports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
            $project = new \load_runtime\Project_Binding('receipt',$sources);
            $package = new \load_runtime\Runtime_Package('p','/provider','t','d','clang',$arguments,$types,$callables,$modules,$paths,'manifest',$catalog,$catalog,$families,$bindings,$project,$source_imports);
            if ($binding === 4) { $package = new \load_runtime\Runtime_Package('p','/provider','t','d','clang',$arguments,$types,$callables,$modules,$paths,'manifest',$catalog,$catalog,$families,null,null,$source_imports); }
            $types['third'] = $first; $callables[] = $callable; $paths[] = '/later'; $arguments[] = '-lother'; $modules['ordinary'] = 'changed';
            $actual = $package->lifecycle_operations(); $copies = 2; if (($binding === 1) || ($binding === 3)) { $copies = 1; }
            $matches = q_count($actual) === q_count($expected) * $copies;
            if ($matches) {
                for ($i = 0; $i < q_count($actual); $i++) {
                    $wanted = $expected[$i % q_count($expected)];
                    if ($copies === 2) { if ($i < q_count($expected)) { $wanted = $second_operations[$i]; } }
                    if ($actual[$i] !== $wanted) { $matches = false; }
                }
            }
            $returned_types = $package->types(); $returned_types['extra'] = $first;
            $returned_calls = $package->callables(); $returned_calls[] = $callable;
            $returned_paths = $package->protected_paths(); $returned_paths[] = '/extra';
            if ((q_count($package->types()) !== 3) || (q_count($package->callables()) !== 1) || (q_count($package->protected_paths()) !== 1) || (q_count($package->link_arguments) !== 1)) { $matches = false; }
            if (($package->type_for('first') !== $first) || ($package->storage_for('second') !== $second->storage) || ($package->module_for(0) !== 'runtime.bc') || ($package->module_for(2) !== 'runtime.thin.bc') || ($package->manifest_text() !== 'manifest')) { $matches = false; }
            if (($package->base_catalog !== $catalog) || ($package->catalog !== $catalog)) { $matches = false; }
            if ($binding === 4) {
                if (($package->bindings !== null) || ($package->project !== null)) { $matches = false; }
            } else {
                if (($package->bindings !== $bindings) || ($package->project !== $project) || ($project->receipt !== 'receipt') || (q_count($project->exports) !== q_count($sources))) { $matches = false; }
            }
            $missing_type = false; $missing_module = false;
            try { $missing = $package->type_for('absent'); } catch (\OutOfBoundsException $error) { $missing_type = true; }
            try { $missing_path = $package->module_for(1); } catch (\RuntimeException $error) { $missing_module = true; }
            echo ($matches && $missing_type && $missing_module) ? "true\n" : "false\n";
        }
    }
}
