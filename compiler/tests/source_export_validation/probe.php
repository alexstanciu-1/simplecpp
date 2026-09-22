<?php
declare(strict_types=1);
namespace source_export_validation_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($case_index = 0; $case_index < $cases->size(); $case_index++) {
            $fixture = $cases->at($case_index); $change = $fixture->member('change')->text(); $selected = $fixture->member('role')->integer();
            $members /** vector<\type_model\Lifecycle_Member> */ = [];
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract($policy,$operations);
        $definition = new \type_model\Named_Definition('R','',\type_model\Representation::structure(0,0),$life,null,'',false,false,true);
        $config = new \prepare_backend\Backend_Configuration('b','t',$fixture->member('layout')->text(),'','','a','r'); $lineage = new \type_model\Type_Lineage();
        $fields /** vector<\type_model\Type_Member> */ = []; $children /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
        $dependency = new \prepare_backend\Layout_Dependency(1,$definition,$fields,$children);
        $offsets /** vector<int> */ = [];
        $layout = new \prepare_backend\Storage_Layout($definition,$config,$fields,'{}',1,1,$offsets,$lineage,$dependency);
        $project = new \compile\Native_Project('p','/src','/out'); $arguments /** vector<\resolve_types\Export_Argument> */ = [];
        $identity = \resolve_types\Export_Type_Identity::source('p','r.phs','',$fixture->member('text')->text(),$arguments);
        $identities /** hash<\resolve_types\Export_Type_Identity,int> */ = []; $identities[1] = $identity;

            $capabilities /** hash<\prepare_backend\Source_Export_Capability> */ = [];
            $exports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
            $roles = \prepare_backend\Source_Export_Roles::all();
            if ($change === 'reordered') { $reverse_roles /** vector<int> */ = [2,6,5,4,3,1]; $roles = $reverse_roles; }
            foreach ($roles as $role) {
                $name = \prepare_backend\Source_Export_Roles::name($role);
                if ($role !== $selected) {
                    if ($change === 'missing_role') { continue; }
                    $unavailable = new \prepare_backend\Source_Export_Capability($role,2,'unsupported');
                    $capabilities[$name] = $unavailable;
                    $exports[$name] = new \prepare_backend\Source_Operation_Export($unavailable,null,null);
                    continue;
                }
                $operation = \type_model\Lifecycle_Operation::source(1,'impl',$role,$members,0,'ccc',0);
                $capability = new \prepare_backend\Source_Export_Capability($role,0,'',$operation);
                if ($change === 'unavailable_abi') { $capability = new \prepare_backend\Source_Export_Capability($role,1,'forbidden'); }
                $capabilities[$name] = $capability;
                if ($change === 'stale_capability') { $capability = new \prepare_backend\Source_Export_Capability($role,0,'',$operation); }
                if ($change === 'wrong_role') { $capability = new \prepare_backend\Source_Export_Capability(4,2,''); $capabilities[$name] = $capability; }
                $parameters /** vector<\prepare_backend\Abi_Parameter> */ = [];
                $arity = \type_model\Lifecycle_Roles::has_source($role) ? 2 : 1;
                for ($index = 0; $index < $arity; $index++) { $parameters[] = new \prepare_backend\Abi_Parameter('ptr'); }
                $import_parameters = $parameters;
                if ($change === 'impl_parameter') { $parameters[0] = new \prepare_backend\Abi_Parameter('i64'); }
                if ($change === 'impl_param_extension') { $parameters[0] = new \prepare_backend\Abi_Parameter('ptr',1); }
                if ($change === 'impl_count') { $parameters[] = new \prepare_backend\Abi_Parameter('ptr'); }
                if ($change === 'import_parameter') { $import_parameters[0] = new \prepare_backend\Abi_Parameter('i64'); }
                if ($change === 'import_param_extension') { $import_parameters[0] = new \prepare_backend\Abi_Parameter('ptr',1); }
                if ($change === 'import_count') { $import_parameters[] = new \prepare_backend\Abi_Parameter('ptr'); }
                if ($change === 'stale_operation') { $operation = \type_model\Lifecycle_Operation::source(1,'impl',$role,$members,0,'ccc',0); }
                $implementation = new \prepare_backend\Abi_Target($change === 'impl_name' ? 'wrong' : 'impl', $change === 'impl_cc' ? 'fastcc' : 'ccc',
                    $change === 'impl_return' ? 'i32' : 'void',$parameters,$change === 'impl_extension' ? 1 : 0,$operation);
                $symbol = \prepare_backend\Source_Export_Preparation::symbol($identity,$role);
                $import = new \prepare_backend\Abi_Target($change === 'import_name' ? 'wrong' : $symbol,$change === 'import_cc' ? 'fastcc' : 'ccc',
                    $change === 'import_return' ? 'i32' : 'void',$import_parameters,$change === 'import_extension' ? 1 : 0);
                if ($change === 'import_operation') { $import = new \prepare_backend\Abi_Target($symbol,'ccc','void',$import_parameters,0,$operation); }
                if ($change === 'missing_impl') { $exports[$name] = new \prepare_backend\Source_Operation_Export($capability,null,$import); }
                elseif ($change === 'missing_import') { $exports[$name] = new \prepare_backend\Source_Operation_Export($capability,$implementation,null); }
                else { $exports[$name] = new \prepare_backend\Source_Operation_Export($capability,$implementation,$import); }
            }
            if ($change === 'extra_role') { $capabilities['extra'] = new \prepare_backend\Source_Export_Capability(4,2,''); }
            $task = new \prepare_backend\Source_Export_Task($project,$identity,$layout,$identities,$capabilities);
            $result = new \prepare_backend\Source_Type_Export($task,$exports); $accepted = true;
            try { \prepare_backend\Source_Export_Preparation::validate($result); } catch (\LogicException $error) { $accepted = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && (\prepare_backend\Source_Export_Preparation::symbol($identity,$selected) === $fixture->member('symbol')->text())) ? "true\n" : "false\n";
        }
    }
}
