<?php
declare(strict_types=1);
namespace storage_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function run(): void {
        $empty_ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        $descriptor_policy = new \type_model\Lifetime_Policy(); $descriptor_policy->construction = 1;
        $lifetime = new \type_model\Lifetime_Contract($descriptor_policy, $empty_ops);
        $representation = \type_model\Representation::opaque(16, 8);
        $descriptor = new \type_model\Named_Definition('descriptor', '', $representation, $lifetime, null, '', false, false, true);
        $value_policy = new \type_model\Lifetime_Policy(); $value_policy->copy = 1; $value_policy->construction = 1; $value_policy->assignment = 1; $value_policy->expiring = 1;
        $value_lifetime = new \type_model\Lifetime_Contract($value_policy, $empty_ops);
        $counter = new \type_model\Named_Definition('size', '', \type_model\Representation::integer(64), $value_lifetime, false, '', false, false, true);
        $void_type = new \type_model\Named_Definition('void', '', \type_model\Representation::void_type(), null, null, '', false, false, false);
        $address = \type_model\Runtime_Abi_Position::borrow(true);
        $integer = \type_model\Runtime_Abi_Position::integer(64, 0);
        $parameters /** vector<\type_model\Runtime_Abi_Position> */ = [$address, $integer];
        $primitive = new \type_model\Storage_Primitive('native_allocate', $address, $parameters);
        $parameters[0] = $integer;
        Probe::check($primitive->result === $address);
        Probe::check($primitive->parameter_count() === 2);
        Probe::check($primitive->parameter_at(0) === $address);
        Probe::check($primitive->parameter_at(1) === $integer);
        $primitives /** hash<\type_model\Storage_Primitive> */ = [];
        $primitives['allocation'] = $primitive;
        $names /** vector<string> */ = ['allocate', 'push', 'pop', 'count', 'release', 'transfer'];
        $operations /** hash<string> */ = [];
        foreach ($names as $name) { $operations[$name] = 'custom_' . $name; }
        $family = new \type_model\Storage_Family('provider', 'family', $descriptor, $counter, $void_type, $primitives, $operations, 'Buffer', 'collections');
        $operations['allocate'] = 'changed';
        $primitives['allocation'] = new \type_model\Storage_Primitive('changed', null, $parameters);
        Probe::check($family->primitive_for('allocation') === $primitive);
        Probe::check($family->operation_name(0) === 'custom_allocate');
        Probe::check(($family->descriptor === $descriptor) && ($family->counter === $counter) && ($family->void_type === $void_type));
        $effects /** vector<int> */ = [1, 5, 5, 6, 2, 3];
        for ($role = 0; $role < 6; $role++) {
            Probe::check(\type_model\Storage_Roles::name($role) === $names[$role]);
            Probe::check(\type_model\Storage_Roles::parse($names[$role]) === $role);
            $function = new \type_model\Storage_Function($family, $role, $family->operation_name($role), 'collections', 'provider', 'operation_' . $role);
            $effect = $function->allocation_effect();
            Probe::check(($effect->kind === $effects[$role]) && ($effect->owner === 0));
            Probe::check(($effect->destination !== null) === ($role === 5));
            if ($role === 5) { Probe::check($effect->destination === 1); }
            Probe::check($function->family === $family);
        }
        $storage = new \type_model\Element_Storage($family, 7, $counter);
        $paths /** vector<vector<int>> */ = [];
        $ownership = new \type_model\Resource_Obligations(1, $paths);
        $definition = new \type_model\Named_Definition('Buffer_size', '', $representation, $lifetime, null, '', false, false, true, $ownership, null, $storage);
        Probe::check($definition->element_storage === $storage);
        Probe::check(($storage->element_type === 7) && ($storage->element === $counter));
        Probe::check($definition->representation === $descriptor->representation);
        Probe::check($definition->lifetime === $descriptor->lifetime);
        Probe::check($descriptor->element_storage === null);
        for ($case_index = 0; $case_index < 10; $case_index++) {
            $rejected = false;
            try {
                if ($case_index === 0) { $bad = new \type_model\Named_Definition('bad', '', $representation, $lifetime, null, '', false, false, true, null, null, $storage); }
                elseif ($case_index === 1) { $bad = new \type_model\Named_Definition('bad', '', \type_model\Representation::opaque(16, 8), $lifetime, null, '', false, false, true, $ownership, null, $storage); }
                elseif ($case_index === 2) { $bad = new \type_model\Named_Definition('bad', '', $representation, new \type_model\Lifetime_Contract($descriptor_policy, $empty_ops), null, '', false, false, true, $ownership, null, $storage); }
                elseif ($case_index === 3) { $bad = new \type_model\Named_Definition('bad', '', $representation, $lifetime, null, '', false, false, true, new \type_model\Resource_Obligations(0, $paths), null, $storage); }
                elseif ($case_index === 4) { $bad_storage = new \type_model\Element_Storage($family, 0, $counter); }
                elseif ($case_index === 5) { \type_model\Storage_Roles::parse('unknown'); }
                elseif ($case_index === 6) { $family->primitive_for('missing'); }
                elseif ($case_index === 7) { $primitive->parameter_at(2); }
                elseif ($case_index === 8) { $bad_primitive = new \type_model\Storage_Primitive('bad', \type_model\Runtime_Abi_Position::byte_span($integer), $parameters); }
                else {
                    $bad_parameters /** vector<\type_model\Runtime_Abi_Position> */ = [\type_model\Runtime_Abi_Position::byte_span($integer)];
                    $bad_primitive = new \type_model\Storage_Primitive('bad', null, $bad_parameters);
                }
            } catch (\InvalidArgumentException $error) { $rejected = true; }
            catch (\OutOfBoundsException $bounds) { $rejected = true; }
            Probe::check($rejected);
        }
    }
}
