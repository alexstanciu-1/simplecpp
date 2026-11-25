<?php
declare(strict_types=1);
namespace load_runtime;

/** Validates provider lifecycle evidence before granting independent language permissions. */
final class Lifecycle_Import {
    private static function present(\scpp\Json_View $row, string $key): bool {
        if (!$row->has($key)) { return false; }
        return $row->member($key)->kind() !== 'null';
    }
    private static function text(\scpp\Json_View $row, string $key, string $expected): void {
        if ($row->member($key)->text() !== $expected) { throw new \RuntimeException('Unsupported runtime lifecycle ' . $key); }
    }
    private static function truth(\scpp\Json_View $row, string $key): void {
        if (!$row->member($key)->boolean()) { throw new \RuntimeException('Unverified runtime lifecycle trait: ' . $key); }
    }
    private static function indices(\scpp\Json_View $parameter, int $position): void {
        $indices = $parameter->member('abi_indices');
        if ($indices->kind() !== 'array') { throw new \RuntimeException('Invalid lifecycle ABI indices'); }
        if ($indices->size() !== 1) { throw new \RuntimeException('Invalid lifecycle ABI indices'); }
        if ($indices->at(0)->integer() !== $position) { throw new \RuntimeException('Invalid lifecycle ABI index'); }
    }
    private static function result(\scpp\Json_View $row, string $id): void {
        $result = $row->member('result');
        Lifecycle_Import::text($result, 'type', $id);
        Lifecycle_Import::text($result, 'ownership', 'owned');
        Lifecycle_Import::text($result, 'passing', 'caller_storage');
        if ($result->member('abi_index')->integer() !== 0) { throw new \RuntimeException('Invalid lifecycle result slot'); }
    }
    private static function no_result(\scpp\Json_View $row): void {
        if ($row->member('result')->kind() !== 'null') { throw new \RuntimeException('Lifecycle operation must have no result'); }
    }
    private static function borrow(\scpp\Json_View $parameter, string $id, string $passing, int $slot): void {
        Lifecycle_Import::text($parameter, 'type', $id);
        Lifecycle_Import::text($parameter, 'ownership', 'borrowed');
        Lifecycle_Import::text($parameter, 'passing', $passing);
        Lifecycle_Import::text($parameter, 'borrow_scope', 'call');
        Lifecycle_Import::indices($parameter, $slot);
    }
    private static function symbol(string $name): void {
        if (string_byte_len($name) === 0) { throw new \RuntimeException('Invalid runtime lifecycle symbol'); }
        for ($index = 0; $index < string_byte_len($name); $index++) {
            $byte = string_byte_at($name, $index);
            $letter = (($byte >= 65) && ($byte < 91)) || (($byte >= 97) && ($byte < 123)) || ($byte === 95);
            if (!$letter) {
                if (($index === 0) || ($byte < 48) || ($byte > 57)) { throw new \RuntimeException('Invalid runtime lifecycle symbol'); }
            }
        }
    }

    /** Exact selected operation; parameter positions are checked before ABI normalization. */
    public static function operation(\scpp\Json_View $type, array $operations /** hash<\scpp\Json_View> */, string $provider, int $role): \type_model\Lifecycle_Operation {
        $name = \type_model\Lifecycle_Roles::name($role);
        $selected = $type->member('lifecycle')->member($name)->text();
        if (!isset($operations[$selected])) { throw new \RuntimeException('Missing runtime lifecycle operation'); }
        $row = $operations[$selected];
        $id = Package_Syntax::identifier($type->member('id'));
        $kind = $name;
        if ($role === \type_model\LIFECYCLE_DEFAULT) { $kind = 'construct'; }
        Lifecycle_Import::text($row, 'kind', $kind);
        Lifecycle_Import::text($row, 'type', $id);
        if (Lifecycle_Import::present($row, 'expose_as')) { throw new \RuntimeException('Implicit lifecycle operation cannot be exposed'); }
        if ($row->has('allocation_effect')) { throw new \RuntimeException('Lifecycle operation cannot declare allocation effects'); }
        Lifecycle_Import::text($row, 'calling_convention', 'ccc');
        Lifecycle_Import::text($row, 'error_policy', 'terminate');
        Lifecycle_Import::text($row, 'exception_boundary', 'caught_in_bridge');
        $abi = $row->member('abi');
        Lifecycle_Import::text($abi, 'return_type', 'void');
        Lifecycle_Import::text($abi, 'return_attributes', '');
        $parameters = Package_Syntax::rows($row->member('parameters'), 'lifecycle parameter');
        $physical = Package_Syntax::rows($abi->member('parameters'), 'lifecycle ABI position');
        $semantic_count = 1; $physical_count = 2;
        if ($role === \type_model\LIFECYCLE_DEFAULT) { $semantic_count = 0; $physical_count = 1; }
        elseif ($role === \type_model\LIFECYCLE_DESTROY) { $physical_count = 1; }
        elseif ($role === \type_model\LIFECYCLE_ASSIGN) { $semantic_count = 2; }
        if ((q_count($parameters) !== $semantic_count) || (q_count($physical) !== $physical_count)) { throw new \RuntimeException('Invalid runtime lifecycle arity'); }
        if ($role === \type_model\LIFECYCLE_DEFAULT) {
            Lifecycle_Import::text($row, 'storage_precondition', 'aligned_uninitialized_storage');
            Lifecycle_Import::result($row, $id);
        } elseif ($role === \type_model\LIFECYCLE_DESTROY) {
            Lifecycle_Import::text($row, 'storage_precondition', 'live_owned_object');
            Lifecycle_Import::text($row, 'storage_after', 'uninitialized_caller_storage');
            Lifecycle_Import::no_result($row);
            Lifecycle_Import::text($parameters[0], 'type', $id);
            Lifecycle_Import::text($parameters[0], 'ownership', 'consumed');
            Lifecycle_Import::text($parameters[0], 'passing', 'address');
            Lifecycle_Import::indices($parameters[0], 0);
        } elseif ($role === \type_model\LIFECYCLE_ASSIGN) {
            Lifecycle_Import::truth($type->member('cpp_traits'), 'copy_assignable');
            Lifecycle_Import::text($row, 'storage_precondition', 'live_object');
            Lifecycle_Import::text($row, 'storage_after', 'live_object');
            Lifecycle_Import::text($row, 'source_precondition', 'live_object');
            Lifecycle_Import::text($row, 'source_after', 'live_object');
            Lifecycle_Import::text($row, 'self_assignment', 'native_call');
            Lifecycle_Import::no_result($row);
            Lifecycle_Import::borrow($parameters[0], $id, 'mutable_address', 0);
            Lifecycle_Import::borrow($parameters[1], $id, 'const_address', 1);
        } else {
            $trait = 'copy_constructible'; $passing = 'const_address';
            if ($role === \type_model\LIFECYCLE_MOVE) { $trait = 'move_constructible'; $passing = 'mutable_address'; }
            Lifecycle_Import::truth($type->member('cpp_traits'), $trait);
            Lifecycle_Import::text($row, 'storage_precondition', 'aligned_uninitialized_storage');
            Lifecycle_Import::text($row, 'storage_after', 'live_owned_object');
            Lifecycle_Import::text($row, 'source_precondition', 'live_object');
            Lifecycle_Import::text($row, 'source_after', 'live_object');
            Lifecycle_Import::borrow($parameters[0], $id, $passing, 1);
            Lifecycle_Import::result($row, $id);
        }
        foreach ($physical as $position) { Package_Syntax::address_abi($position); }
        $link = $row->member('symbol')->text(); Lifecycle_Import::symbol($link);
        return \type_model\Lifecycle_Operation::runtime($provider, Package_Syntax::identifier($row->member('id')), $link, 'ccc', $role);
    }

    public static function lifetime(\scpp\Json_View $type, array $operations /** hash<\scpp\Json_View> */, string $provider): \type_model\Lifetime_Contract {
        $lifecycle = $type->member('lifecycle');
        $policy = new \type_model\Lifetime_Policy();
        $accepted /** vector<\type_model\Lifecycle_Operation> */ = [];
        if (Lifecycle_Import::present($lifecycle, 'cleanup')) {
            Lifecycle_Import::text($lifecycle, 'cleanup', 'none');
            Lifecycle_Import::truth($type->member('cpp_traits'), 'trivially_destructible');
        } else {
            $accepted[] = Lifecycle_Import::operation($type, $operations, $provider, \type_model\LIFECYCLE_DESTROY);
            $policy->cleanup = 1;
        }
        for ($role = 1; $role < 6; $role++) {
            if ($role === \type_model\LIFECYCLE_DESTROY) { continue; }
            if (!Lifecycle_Import::present($lifecycle, \type_model\Lifecycle_Roles::name($role))) { continue; }
            $accepted[] = Lifecycle_Import::operation($type, $operations, $provider, $role);
            if ($role === \type_model\LIFECYCLE_DEFAULT) { $policy->construction = 2; }
            elseif ($role === \type_model\LIFECYCLE_COPY) { $policy->copy = 2; }
            elseif ($role === \type_model\LIFECYCLE_MOVE) { $policy->expiring = 3; }
            elseif ($role === \type_model\LIFECYCLE_ASSIGN) { $policy->assignment = 2; }
        }
        return new \type_model\Lifetime_Contract($policy, $accepted);
    }
}
