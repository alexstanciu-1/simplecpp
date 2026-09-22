<?php
declare(strict_types=1);
namespace project_receipt_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($case_index = 0; $case_index < $cases->size(); $case_index++) {
            $fixture = $cases->at($case_index);
            $members /** vector<\type_model\Lifecycle_Member> */ = [];
            $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
            $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
            $life = new \type_model\Lifetime_Contract($policy,$operations);
            $definition = new \type_model\Named_Definition('R','',\type_model\Representation::structure(0,0),$life,null,'',false,false,true);
            $config = new \prepare_backend\Backend_Configuration('b','t','e','','','a','r'); $lineage = new \type_model\Type_Lineage();
            $fields /** vector<\type_model\Type_Member> */ = []; $children /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
            $dependency = new \prepare_backend\Layout_Dependency(1,$definition,$fields,$children);
            $offsets /** vector<int> */ = [];
            $layout = new \prepare_backend\Storage_Layout($definition,$config,$fields,'{}',1,1,$offsets,$lineage,$dependency);
            $project = new \compile\Native_Project('p','/src','/out'); $arguments /** vector<\resolve_types\Export_Argument> */ = [];
            $identity = \resolve_types\Export_Type_Identity::source('p','r.phs','','R',$arguments);
            $identities /** hash<\resolve_types\Export_Type_Identity,int> */ = []; $identities[1] = $identity;
            $capabilities /** hash<\prepare_backend\Source_Export_Capability> */ = [];
            $exports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
            $roles = \prepare_backend\Source_Export_Roles::all();
            foreach ($roles as $role) {
                $name = \prepare_backend\Source_Export_Roles::name($role);
                if (($role === 4) || ($role === 6)) {
                    $unavailable = new \prepare_backend\Source_Export_Capability($role,2,'unsupported');
                    $capabilities[$name] = $unavailable;
                    $exports[$name] = new \prepare_backend\Source_Operation_Export($unavailable,null,null);
                    continue;
                }
                $operation = \type_model\Lifecycle_Operation::source(1,'impl',$role,$members,0,'ccc',0);
                $capability = new \prepare_backend\Source_Export_Capability($role,0,'',$operation);
                $capabilities[$name] = $capability;
                $parameters /** vector<\prepare_backend\Abi_Parameter> */ = [];
                $arity = \type_model\Lifecycle_Roles::has_source($role) ? 2 : 1;
                for ($index = 0; $index < $arity; $index++) { $parameters[] = new \prepare_backend\Abi_Parameter('ptr'); }
                $implementation = new \prepare_backend\Abi_Target('impl','ccc','void',$parameters,0,$operation);
                $symbol = \prepare_backend\Source_Export_Preparation::symbol($identity,$role);
                $import = new \prepare_backend\Abi_Target($symbol,'ccc','void',$parameters);
                $exports[$name] = new \prepare_backend\Source_Operation_Export($capability,$implementation,$import);
            }
            $task = new \prepare_backend\Source_Export_Task($project,$identity,$layout,$identities,$capabilities);
            $result = new \prepare_backend\Source_Type_Export($task,$exports);
            $source_exports /** hash<\prepare_backend\Source_Type_Export> */ = [];
            $source_exports[$identity->key()] = $result;
            if ($fixture->member('empty')->boolean()) { $no_exports /** hash<\prepare_backend\Source_Type_Export> */ = []; $source_exports = $no_exports; }
            $receipt = $fixture->member('receipt')->text();
            $binding = new \load_runtime\Project_Binding($receipt,$source_exports);
            if ($fixture->member('changed')->boolean()) { $receipt .= ' '; }
            $target = new \load_runtime\Package_Target('t','e');
            $accepted = true; $matches = true;
            try {
                $required = \load_runtime\Project_Receipt::validate($binding,$receipt,$target);
                if (!$fixture->member('accept')->boolean()) { $matches = false; }
                else {
                    $expected = $fixture->member('required');
                    if (q_count($required) !== $expected->size()) { $matches = false; }
                    $index = 0;
                    foreach ($required as $symbol => $operation) {
                        if ($symbol !== $expected->at($index)->text()) { $matches = false; }
                        $name = \prepare_backend\Source_Export_Roles::name($operation->capability->role);
                        if ($operation !== $exports[$name]) { $matches = false; }
                        $index = $index + 1;
                    }
                }
            } catch (\RuntimeException $error) { $accepted = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
}
