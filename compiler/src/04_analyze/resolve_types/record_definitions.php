<?php
declare(strict_types=1);
namespace resolve_types;
/** Coordinator-only normalized record materialization; discard a failed candidate. */
final class Record_Definitions {
    private static function identifier(string $name): bool {
        if (string_byte_len($name)===0) { return false; }
        for ($i=0;$i<string_byte_len($name);$i++) {
            $c=string_byte_at($name,$i);
            $letter=(($c>=65) && ($c<91)) || (($c>=97) && ($c<123)) || ($c===95);
            if (!$letter) {
                if ($i===0) { return false; }
                if (($c<48) || ($c>57)) { return false; }
            }
        }
        return true;
    }
    public static function materialize(\type_model\Type_Store $types, \type_model\Record_Declaration $record): int {
        if ((!$record->automatic_lifecycle) || ($record->field_count()===0) || (!Record_Definitions::identifier($record->name))) { throw new \LogicException('Unsupported record layout or construction contract'); }
        if (($record->layout_policy===\type_model\RECORD_LAYOUT_NATIVE_VERIFIED) !== ($record->native_layout!==null)) { throw new \LogicException('Record layout policy requires its native measurement'); }
        if ($record->native_layout!==null) {
            if ($record->native_layout->field_count()!==$record->field_count()) { throw new \LogicException('Native measurement must cover every field'); }
        }
        $fields /** vector<\type_model\Type_Member> */ = []; $members /** vector<int> */ = [];
        $resources /** vector<vector<int>> */ = []; $names /** hash<bool> */ = [];
        for ($index=0;$index<$record->field_count();$index++) {
            $field=$record->field_at($index); $name=$field->name; $definition=$field->definition;
            if ((!$definition->element->struct_field) || (!Record_Definitions::identifier($name))) { throw new \LogicException('Unsupported record field contract'); }
            if (isset($names[$name])) { throw new \LogicException('Duplicate record field'); }
            if ($definition->extent===0) {
                $ownership=$definition->element->ownership;
                if ($ownership!==null) {
                    if ($ownership->kind===\type_model\RESOURCE_ALLOCATION) { $direct /** vector<int> */ = [$index]; $resources[]=$direct; }
                    else {
                        for ($path_index=0;$path_index<$ownership->path_count();$path_index++) {
                            $path /** vector<int> */ = [$index];
                            foreach ($ownership->path_at($path_index) as $ordinal) { $path[]=$ordinal; }
                            $resources[]=$path;
                        }
                    }
                }
            }
            $names[$name]=true; $type=Type_Cache::materialize_field($types,$definition);
            $fields[]=new \type_model\Type_Member($type,$name,$field->writable); $members[]=$type;
        }
        $shape=$types->intern_structure($fields); $id=$types->declare_type($record->name,$record->namespace_name);
        $bodies=new Lifecycle_Bodies(); $bodies->constructor=$record->constructor_body; $bodies->destructor=$record->destructor_body;
        $bodies->copy=$record->copy_body; $bodies->assignment=$record->assignment_body;
        $life=Lifecycle_Composition::derive($types,$id,$members,0,$bodies);
        $ownership=new \type_model\Resource_Obligations(\type_model\RESOURCE_NONE,$resources);
        $definition=new \type_model\Named_Definition($record->name,$record->namespace_name,$types->representation_by_id($shape),$life,null,'',false,false,true,$ownership,$record->native_layout);
        $types->bind_definition($id,$definition); $types->set_representation($id,$shape); return $id;
    }
}
