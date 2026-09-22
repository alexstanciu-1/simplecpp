<?php
declare(strict_types=1);
namespace record_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function run(): void {
        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
        $p=new \type_model\Lifetime_Policy(); $p->copy=1; $p->construction=1; $p->assignment=1; $p->expiring=1;
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        $word=new \type_model\Named_Definition('word','',\type_model\Representation::integer(32),new \type_model\Lifetime_Contract($p,$ops),true,'',false,false,true);
        $array=\type_model\Field_Type::fixed_array($word,4);
        $array_id=\resolve_types\Type_Cache::materialize_field($types,$array);
        Probe::check($types->representation_for_type($array_id)->member_count()===4);
        Probe::check(\resolve_types\Type_Cache::materialize_field($types,$array)===$array_id);
        $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('first',\type_model\Field_Type::named($word),true),new \type_model\Field_Declaration('values',$array,false)];
        $record=new \type_model\Record_Declaration('Record','fixture',$fields,true,\type_model\RECORD_LAYOUT_TARGET,null,0,0,0,0);
        $id=\resolve_types\Record_Definitions::materialize($types,$record);
        Probe::check($types->field_index($id,'values')===1); Probe::check($types->field_for($id,1)->type_id===$array_id); Probe::check(!$types->field_for($id,1)->writable);
        $definition=$types->definition_for_type($id); Probe::check($definition->struct_field);
        Probe::check((int)$definition->lifetime->policy()->copy===1);
        $nested_fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('record',\type_model\Field_Type::named($definition),true)];
        $outer=new \type_model\Record_Declaration('Outer','fixture',$nested_fields,true,0,null,0,0,0,0);
        $outer_id=\resolve_types\Record_Definitions::materialize($types,$outer);
        Probe::check($types->field_for($outer_id,0)->type_id===$id);
        $owner_policy=new \type_model\Lifetime_Policy(); $owner_policy->construction=1;
        $paths /** vector<vector<int>> */ = []; $ownership=new \type_model\Resource_Obligations(1,$paths);
        $owner=new \type_model\Named_Definition('owner','',\type_model\Representation::opaque(8,8),new \type_model\Lifetime_Contract($owner_policy,$ops),null,'',false,false,true,$ownership);
        $owning_fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('value',\type_model\Field_Type::named($owner),true)];
        $owned=new \type_model\Record_Declaration('Owned','',$owning_fields,true,0,null,0,0,0,0);
        $owned_id=\resolve_types\Record_Definitions::materialize($types,$owned); $owned_definition=$types->definition_for_type($owned_id);
        Probe::check($owned_definition->ownership->path_count()===1); Probe::check($owned_definition->ownership->path_at(0)[0]===0);
        Probe::check((int)$owned_definition->lifetime->policy()->copy===0);
        $rejected=false; try { $bad=\type_model\Field_Type::fixed_array($owner,2); } catch (\InvalidArgumentException $error) { $rejected=true; } Probe::check($rejected);
        $rejected=false; try { $zero=\type_model\Field_Type::fixed_array($word,0); } catch (\InvalidArgumentException $error) { $rejected=true; } Probe::check($rejected);
        $offsets /** vector<int> */ = [0,4]; $layout=new \type_model\Native_Record_Layout('t','d',20,4,$offsets);
        $native=new \type_model\Record_Declaration('Native','',$fields,true,1,$layout,0,0,0,0);
        $native_id=\resolve_types\Record_Definitions::materialize($types,$native);
        Probe::check($types->definition_for_type($native_id)->native_layout===$layout);
        Probe::check($types->definition_for_type($native_id)->native_layout->field_offset(1)===4);
        $nested_owners /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('inner',\type_model\Field_Type::named($owned_definition),true)];
        $nested_record=new \type_model\Record_Declaration('NestedOwner','',$nested_owners,true,0,null,0,0,0,0);
        $nested_id=\resolve_types\Record_Definitions::materialize($types,$nested_record);
        $nested_path=$types->definition_for_type($nested_id)->ownership->path_at(0);
        Probe::check(q_count($nested_path)===2); Probe::check($nested_path[0]===0); Probe::check($nested_path[1]===0);
        $rejected=false; try { $bad_nested=\type_model\Field_Type::fixed_array($owned_definition,2); } catch (\InvalidArgumentException $error) { $rejected=true; } Probe::check($rejected);
        for ($case=0;$case<8;$case++) {
            $name='Bad'; $automatic=true; $policy=0; $candidate_fields=$fields;
            if ($case===0) { $name=''; } elseif ($case===1) { $name='0bad'; } elseif ($case===2) { $name='é'; }
            elseif ($case===3) { $automatic=false; } elseif ($case===4) { $candidate_fields=[]; }
            elseif ($case===5) { $candidate_fields[1]=$candidate_fields[0]; }
            elseif ($case===6) { $policy=1; }
            else { $candidate_fields[0]=new \type_model\Field_Declaration('bad-name',\type_model\Field_Type::named($word),true); }
            $candidate=new \type_model\Record_Declaration($name,'',$candidate_fields,$automatic,$policy,null,0,0,0,0);
            $rejected=false;
            try { \resolve_types\Record_Definitions::materialize($types->fork(),$candidate); } catch (\LogicException $error) { $rejected=true; }
            Probe::check($rejected);
        }
    }
}
