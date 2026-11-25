<?php
declare(strict_types=1);
namespace type_exposure_test;
final class Probe {
    public static function run(string $text): void {
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
        $none /** vector<\type_model\Lifecycle_Operation> */ = [];
        $lifetime = new \type_model\Lifetime_Contract($policy, $none);
        $integer = new \type_model\Named_Definition('Int', '', \type_model\Representation::integer(64), $lifetime, true, '', false, false, false);
        $unsigned_value = new \type_model\Named_Definition('UInt', '', \type_model\Representation::integer(64), $lifetime, false, '', false, false, false);
        $void_type = new \type_model\Named_Definition('Void', '', \type_model\Representation::void_type(), null, null, '', false, false, false);
        $definitions /** vector<\type_model\Named_Definition> */ = [$integer, $unsigned_value, $void_type];
        $catalog = new \type_model\Type_Catalog('p','key','language_values',$definitions,$integer,$integer,null);
        $operations /** hash<\scpp\Json_View> */ = [];
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $row = $fixture->member('row'); $accepted = true; $matches = true;
            $name = $fixture->member('name')->text(); $namespace_name = $fixture->member('namespace')->text();
            try {
                $measurement = \load_runtime\Package_Type_Import::measure($row);
                $value = \load_runtime\Package_Type_Exposure::definition($row,$measurement,\type_model\Type_Reference::named($name,$namespace_name),$catalog,$operations,'provider');
                if ($fixture->member('accept')->boolean()) {
                    $kind = $measurement->storage->kind;
                    if ($kind === \load_runtime\RUNTIME_STORAGE_RECORD) { $matches = $value === null; }
                    else if ($value === null) { $matches = false; }
                    else {
                        $matches = ($value->name === $name) && ($value->namespace_name === $namespace_name);
                        if ($kind === \load_runtime\RUNTIME_STORAGE_INTEGER) {
                            if ($name === 'Int') { if ($value !== $integer) { $matches = false; } }
                            else { if ($value !== $unsigned_value) { $matches = false; } }
                        } else if ($kind === \load_runtime\RUNTIME_STORAGE_VOID) { if ($value !== $void_type) { $matches = false; } }
                        else {
                            $life = $value->lifetime;
                            if ($life === null) { $matches = false; }
                            else {
                                $actual = $life->policy();
                                if ($kind === \load_runtime\RUNTIME_STORAGE_BYTE_SPAN) {
                                    if (($value->representation->kind() !== \type_model\REPRESENTATION_BYTE_SPAN) || ((int)$actual->copy !== 1) || ((int)$actual->assignment !== 1)) { $matches = false; }
                                } else {
                                    if (($value->representation->opaque_size() !== 8) || ($value->representation->opaque_alignment() !== 8) || ((int)$actual->copy !== 0) || ((int)$actual->cleanup !== 0)) { $matches = false; }
                                    if ($value->struct_field !== ($name === 'Field')) { $matches = false; }
                                    if (($value->ownership !== null) !== ($name === 'Owner')) { $matches = false; }
                                }
                            }
                        }
                    }
                }
            } catch (\RuntimeException $error) { $accepted = false; }
            $ok = $accepted === $fixture->member('accept')->boolean();
            if ($accepted) { if (!$matches) { $ok = false; } }
            echo $ok ? "true\n" : "false\n";
        }
    }
}
