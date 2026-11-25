<?php
declare(strict_types=1);
namespace source_export_contracts_test;
final class Probe {
    private static function check(bool $value): void { echo $value ? "true\n" : "false\n"; }
    public static function run(string $text): void {
        $cases = json_read($text); $members /** vector<\type_model\Lifecycle_Member> */ = [];
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $role = $fixture->member('role')->integer();
            if ($fixture->member('mode')->text() === 'semantics') {
                $semantics = \prepare_backend\Source_Export_Roles::semantics($role);
                Probe::check(($semantics->destination_before === $fixture->member('before')->text()) && ($semantics->destination_after === $fixture->member('after')->text())
                    && ($semantics->source_access === $fixture->member('access')->text()) && ($semantics->source_after === $fixture->member('source_after')->text())
                    && ($semantics->aliasing === $fixture->member('aliasing')->text()) && ($semantics->payload_escape === 'call_scoped') && ($semantics->failure === 'terminate')
                    && ($semantics->unwind === 'none') && ($semantics->resources === 'selected_field_contracts')
                    && (\prepare_backend\Source_Export_Roles::name($role) === $fixture->member('name')->text()) && (\prepare_backend\Source_Export_Roles::implemented_kind($role) === $fixture->member('kind')->integer()));
                continue;
            }
            $state = $fixture->member('state')->integer(); $kind = $fixture->member('kind')->integer(); $accepted = true;
            try {
                if ($kind === 0) { $capability = new \prepare_backend\Source_Export_Capability($role,$state,'reason'); }
                else {
                    $operation = \type_model\Lifecycle_Operation::source(1,'impl',$kind,$members,0,'ccc',0);
                    if ($fixture->member('imported')->boolean()) { $operation = \type_model\Lifecycle_Operation::runtime('p','op','impl','ccc',$kind); }
                    $capability_with_operation = new \prepare_backend\Source_Export_Capability($role,$state,'reason',$operation);
                }
            } catch (\InvalidArgumentException $error) { $accepted = false; }
            Probe::check($accepted === $fixture->member('accept')->boolean());
        }
        Probe::records();
    }
    private static function records(): void {
        $members /** vector<\type_model\Lifecycle_Member> */ = [];
        $operation = \type_model\Lifecycle_Operation::source(1,'copy_impl',3,$members,0,'ccc',0);
        $capability = new \prepare_backend\Source_Export_Capability(3,0,'',$operation);
        $unsupported = new \prepare_backend\Source_Export_Capability(4,2,'deferred');
        $parameter = new \prepare_backend\Abi_Parameter('i32',1);
        $parameters /** vector<\prepare_backend\Abi_Parameter> */ = [$parameter];
        $implementation = new \prepare_backend\Abi_Target('copy_impl','ccc','void',$parameters,0,$operation);
        $import = new \prepare_backend\Abi_Target('bridge_copy','ccc','void',$parameters);
        $parameters[] = $parameter;
        Probe::check((q_count($implementation->parameters) === 1) && ($implementation->parameters[0] === $parameter) && ($implementation->lifecycle_operation === $operation));
        Probe::check(($import->lifecycle_operation === null) && ($import->link_name === 'bridge_copy'));
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract($policy,$operations);
        $definition = new \type_model\Named_Definition('R','',\type_model\Representation::structure(0,0),$life,null,'',false,false,true);
        $config = new \prepare_backend\Backend_Configuration('b','t','d','','','a','r'); $lineage = new \type_model\Type_Lineage();
        $fields /** vector<\type_model\Type_Member> */ = []; $children /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
        $dependency = new \prepare_backend\Layout_Dependency(1,$definition,$fields,$children);
        $offsets /** vector<int> */ = [];
        $layout = new \prepare_backend\Storage_Layout($definition,$config,$fields,'{}',1,1,$offsets,$lineage,$dependency);
        $project = new \compile\Native_Project('p','/src','/out'); $arguments /** vector<\resolve_types\Export_Argument> */ = [];
        $identity = \resolve_types\Export_Type_Identity::source('p','r.phs','','R',$arguments);
        $identities /** hash<\resolve_types\Export_Type_Identity,int> */ = []; $identities[1] = $identity;
        $capabilities /** hash<\prepare_backend\Source_Export_Capability> */ = []; $capabilities['copy_construct'] = $capability; $capabilities['move_construct'] = $unsupported;
        $task = new \prepare_backend\Source_Export_Task($project,$identity,$layout,$identities,$capabilities);
        $identities[2] = $identity; $capabilities['destroy'] = $unsupported;
        Probe::check(($task->project === $project) && ($task->identity === $identity) && ($task->layout === $layout) && (q_count($task->identities) === 1) && (q_count($task->capabilities) === 2));
        $operation_export = new \prepare_backend\Source_Operation_Export($capability,$implementation,$import);
        $exports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        $exports['copy_construct'] = $operation_export; $exports['move_construct'] = new \prepare_backend\Source_Operation_Export($unsupported,null,null);
        $type_export = new \prepare_backend\Source_Type_Export($task,$exports); $exports['destroy'] = $operation_export;
        Probe::check(($type_export->task === $task) && ($type_export->require_operation(3) === $operation_export) && (q_count($type_export->operations) === 2));
        Probe::check(($operation_export->implementation === $implementation) && ($operation_export->import === $import));
        $rejected = false;
        try { $bad_move = $type_export->require_operation(4); } catch (\RuntimeException $error) { $rejected = true; }
        Probe::check($rejected);
        $missing = false;
        try { $bad_missing = $type_export->require_operation(2); } catch (\LogicException $error) { $missing = true; }
        Probe::check($missing);
        $bad_extension = false;
        try { $bad_parameter = new \prepare_backend\Abi_Parameter('i32',3); } catch (\InvalidArgumentException $error) { $bad_extension = true; }
        Probe::check($bad_extension);
        $bad_return = false;
        try { $bad_target = new \prepare_backend\Abi_Target('f','ccc','i32',$parameters,-1); } catch (\InvalidArgumentException $error) { $bad_return = true; }
        Probe::check($bad_return);
        $names /** vector<string> */ = ['identity','trunc','sext','zext'];
        for ($index = 0; $index < 4; $index++) { Probe::check(\prepare_backend\Integer_Adaptations::name($index) === $names[$index]); }
        Probe::check(\prepare_backend\Source_Type_Export::profile() === 'inline_source_payload_v1');
        $roles = \prepare_backend\Source_Export_Roles::all();
        Probe::check(($roles[0] === 1) && ($roles[1] === 3) && ($roles[2] === 4) && ($roles[3] === 5) && ($roles[4] === 6) && ($roles[5] === 2));
    }
}
