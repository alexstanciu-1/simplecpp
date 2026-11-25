<?php
declare(strict_types=1);
namespace definition_contracts_test;
final class Probe {
    private static function life(bool $value): \type_model\Lifetime_Contract {
        $policy = new \type_model\Lifetime_Policy();
        if ($value) { $policy->copy = 1; $policy->assignment = 1; }
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        return new \type_model\Lifetime_Contract($policy,$operations);
    }
    public static function definition(int $mode): \type_model\Named_Definition {
        $name = 'T'; $namespace_name = 'ns'; $bits = 64; $is_signed = true; $integer_family = '';
        if ($mode === 1) { $name = 'Other'; } if ($mode === 2) { $namespace_name = 'other'; }
        if ($mode === 3) { $bits = 32; } if ($mode === 4) { $is_signed = false; } if ($mode === 5) { $integer_family = 'ints'; }
        $paths /** vector<vector<int>> */ = [];
        if ($mode < 11) {
            $life = Probe::life($mode !== 9);
            if ($mode === 10) { return new \type_model\Named_Definition($name,$namespace_name,\type_model\Representation::integer($bits),$life,$is_signed,$integer_family,false,false,false,new \type_model\Resource_Obligations(0,$paths)); }
            return new \type_model\Named_Definition($name,$namespace_name,\type_model\Representation::integer($bits),$life,$is_signed,$integer_family,$mode === 6,$mode === 7,$mode === 8);
        }
        if ($mode === 11) { return new \type_model\Named_Definition($name,$namespace_name,\type_model\Representation::void_type(),null,null,'',false,false,false); }
        if ($mode === 12) { return new \type_model\Named_Definition($name,$namespace_name,\type_model\Representation::opaque(8,8),Probe::life(false),null,'',false,false,false); }
        if ($mode === 13) { return new \type_model\Named_Definition($name,$namespace_name,\type_model\Representation::opaque(8,8),Probe::life(false),null,'',false,false,false,new \type_model\Resource_Obligations(1,$paths)); }
        if ($mode > 25) {
            $family_mode = 0; if ($mode === 29) { $family_mode = 1; }
            $family = Probe::family($family_mode); $element_id = 1; if ($mode === 27) { $element_id = 2; }
            $element_mode = 0; if ($mode === 28) { $element_mode = 3; }
            $storage = new \type_model\Element_Storage($family,$element_id,Probe::definition($element_mode));
            return new \type_model\Named_Definition($name,$namespace_name,$family->descriptor->representation,$family->descriptor->lifetime,null,'',false,false,false,new \type_model\Resource_Obligations(1,$paths),null,$storage);
        }
        if ($mode === 15) { $paths = [[0]]; } if ($mode === 16) { $paths = [[1]]; }
        if ($mode === 17) { $paths = [[0],[1]]; } if ($mode === 18) { $paths = [[1],[0]]; } if ($mode === 19) { $paths = [[0,1]]; }
        $resources = new \type_model\Resource_Obligations(0,$paths);
        if ($mode > 19) {
            $target = 'target'; $data_layout = 'layout'; $size = 8; $alignment = 8; $offsets /** vector<int> */ = [0];
            if ($mode === 21) { $size = 16; } if ($mode === 22) { $alignment = 4; }
            if ($mode === 23) { $target = 'other'; } if ($mode === 24) { $data_layout = 'other'; } if ($mode === 25) { $offsets[0] = 4; }
            $layout = new \type_model\Native_Record_Layout($target,$data_layout,$size,$alignment,$offsets);
            return new \type_model\Named_Definition($name,$namespace_name,\type_model\Representation::structure(0,1),Probe::life(false),null,'',false,false,false,$resources,$layout);
        }
        return new \type_model\Named_Definition($name,$namespace_name,\type_model\Representation::structure(0,1),Probe::life(false),null,'',false,false,false,$resources);
    }
    public static function family(int $mode): \type_model\Storage_Family {
        $provider = 'p'; $id = 'id'; $name = 'Buffer'; $namespace_name = 'ns';
        if ($mode === 1) { $provider = 'q'; } if ($mode === 2) { $id = 'other'; } if ($mode === 3) { $name = 'Other'; } if ($mode === 4) { $namespace_name = 'other'; }
        $descriptor = Probe::definition(13); $counter = Probe::definition(0); $void_type = Probe::definition(11);
        if ($mode === 5) { $descriptor = Probe::definition(12); }
        if ($mode === 6) { $counter = Probe::definition(3); }
        if ($mode === 7) { $void_type = new \type_model\Named_Definition('Other','ns',\type_model\Representation::void_type(),null,null,'',false,false,false); }
        $link = 'allocate'; if ($mode === 8) { $link = 'other'; }
        $bits = 64; if ($mode === 9) { $bits = 32; }
        $parameters /** vector<\type_model\Runtime_Abi_Position> */ = [\type_model\Runtime_Abi_Position::integer($bits,0)];
        if ($mode === 10) { $parameters[] = \type_model\Runtime_Abi_Position::borrow(false); }
        $primitive = new \type_model\Storage_Primitive($link,\type_model\Runtime_Abi_Position::borrow(false),$parameters);
        if ($mode === 11) { $primitive = new \type_model\Storage_Primitive($link,null,$parameters); }
        if ($mode === 12) { $primitive = new \type_model\Storage_Primitive($link,\type_model\Runtime_Abi_Position::borrow(true),$parameters); }
        $primitives /** hash<\type_model\Storage_Primitive> */ = []; $spellings /** hash<string> */ = [];
        if ($mode === 17) { $primitives['release'] = $primitive; $spellings['release'] = 'Release'; }
        $key = 'allocate'; if ($mode === 13) { $key = 'other'; }
        $primitives[$key] = $primitive; $spellings['allocate'] = 'Allocate';
        $primitives['release'] = $primitive; $spellings['release'] = 'Release';
        if ($mode === 14) { $primitives['extra'] = $primitive; }
        if ($mode === 15) { $spellings['allocate'] = 'Other'; }
        if ($mode === 16) { $spellings['push'] = 'Push'; }
        return new \type_model\Storage_Family($provider,$id,$descriptor,$counter,$void_type,$primitives,$spellings,$name,$namespace_name);
    }
    public static function record(int $mode): \type_model\Record_Declaration {
        $name = 'R'; $namespace_name = 'ns'; $automatic = true; $policy = 0;
        if ($mode === 1) { $name = 'Other'; } if ($mode === 2) { $namespace_name = 'other'; }
        if ($mode === 3) { $automatic = false; } if ($mode === 4) { $policy = 1; }
        $ctor = 0; $dtor = 0; $copy = 0; $assign = 0;
        if ($mode === 5) { $ctor = 1; } if ($mode === 6) { $dtor = 1; } if ($mode === 7) { $copy = 1; } if ($mode === 8) { $assign = 1; }
        $field_name = 'field'; $writable = false; $extent = 0; $element_mode = 0;
        if ($mode === 9) { $field_name = 'other'; } if ($mode === 10) { $writable = true; }
        if ($mode === 11) { $extent = 2; } if ($mode === 12) { $element_mode = 3; }
        $field = new \type_model\Field_Declaration($field_name,new \type_model\Field_Type(Probe::definition($element_mode),$extent),$writable);
        $fields /** vector<\type_model\Field_Declaration> */ = [$field];
        if ($mode === 13) { $fields[] = new \type_model\Field_Declaration('second',\type_model\Field_Type::named(Probe::definition(0)),false); }
        if ($mode === 14) { $fields[0] = new \type_model\Field_Declaration('second',\type_model\Field_Type::named(Probe::definition(0)),false); $fields[] = $field; }
        if ($mode > 14) {
            $offsets /** vector<int> */ = [0]; $size = 8; if ($mode === 16) { $size = 16; }
            return new \type_model\Record_Declaration($name,$namespace_name,$fields,$automatic,$policy,new \type_model\Native_Record_Layout('target','layout',$size,8,$offsets),$ctor,$dtor,$copy,$assign);
        }
        return new \type_model\Record_Declaration($name,$namespace_name,$fields,$automatic,$policy,null,$ctor,$dtor,$copy,$assign);
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $a = $fixture->member('left')->integer(); $b = $fixture->member('right')->integer(); $equal = $fixture->member('equal')->boolean(); $matches = false;
            if ($fixture->member('kind')->text() === 'definition') {
                $left = Probe::definition($a); $right = Probe::definition($b);
                $matches = (\type_model\Definition_Contracts::same($left,$right) === $equal) && ($left !== $right);
            } elseif ($fixture->member('kind')->text() === 'family') {
                $left_family = Probe::family($a); $right_family = Probe::family($b);
                $matches = (\type_model\Definition_Contracts::family($left_family,$right_family) === $equal) && ($left_family !== $right_family);
                $snapshot = $left_family->primitive_contracts(); $snapshot['extra_snapshot'] = $left_family->primitive_for('release');
                $names = $left_family->operation_spellings(); $names['release'] = 'Changed';
                if ($left_family->operation_name(4) !== 'Release') { $matches = false; }
                if (q_count($snapshot) !== q_count($left_family->primitive_contracts()) + 1) { $matches = false; }
            } else {
                $left_record = Probe::record($a); $right_record = Probe::record($b);
                $matches = (\type_model\Definition_Contracts::record($left_record,$right_record) === $equal) && ($left_record !== $right_record);
            }
            echo $matches ? "true\n" : "false\n";
        }
    }
}
