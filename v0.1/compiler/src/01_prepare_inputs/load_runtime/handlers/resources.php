<?php
declare(strict_types=1);
namespace load_runtime;

/** Normalize explicit provider permissions; layout never implies resource ownership. */
final class Resource_Import {
    public static function resource_type(\scpp\Json_View $row): ?\type_model\Resource_Obligations {
        if ($row->kind() !== 'object') { throw new \RuntimeException('Expected runtime type record'); }
        if (!$row->has('resource')) { return null; }
        if ($row->member('resource')->text() !== 'allocation') { throw new \RuntimeException('Unsupported allocation resource type'); }
        if ($row->member('kind')->text() !== 'runtime_value') { throw new \RuntimeException('Unsupported allocation resource type'); }
        if ($row->has('lifecycle')) {
            $lifecycle = $row->member('lifecycle');
            if ($lifecycle->kind() === 'object') {
                if ($lifecycle->has('copy_construct')) {
                    if ($lifecycle->member('copy_construct')->kind() !== 'null') { throw new \RuntimeException('Unsupported allocation resource type'); }
                }
                if ($lifecycle->has('copy_assign')) {
                    if ($lifecycle->member('copy_assign')->kind() !== 'null') { throw new \RuntimeException('Unsupported allocation resource type'); }
                }
            }
        }
        $paths /** vector<vector<int>> */ = [];
        return new \type_model\Resource_Obligations(\type_model\RESOURCE_ALLOCATION, $paths);
    }

    private static function is_owner(Runtime_Type $type): bool {
        $language = $type->language_type;
        if ($language === null) { return false; }
        $ownership = $language->ownership;
        if ($ownership === null) { return false; }
        return $ownership->kind === \type_model\RESOURCE_ALLOCATION;
    }
    private static function require_type(string $id, array $types /** hash<Runtime_Type> */): Runtime_Type {
        if (!isset($types[$id])) { throw new \RuntimeException('Unknown runtime type: ' . $id); }
        return $types[$id];
    }

    /** Caller validates physical ABI first; positions here are semantic parameters. */
    public static function call_allocation_effect(\scpp\Json_View $row, array $types /** hash<Runtime_Type> */): ?\type_model\Allocation_Effect {
        $parameters = Package_Syntax::rows($row->member('parameters'), 'parameter');
        $owners /** hash<\scpp\Json_View, int> */ = [];
        foreach ($parameters as $index => $parameter) {
            $type = Resource_Import::require_type(Package_Syntax::identifier($parameter->member('type')), $types);
            if (Resource_Import::is_owner($type)) { $owners[$index] = $parameter; }
        }
        $result = Resource_Import::require_type(Package_Syntax::identifier($row->member('result')->member('type')), $types);
        $call_kind = $row->member('kind')->text();
        if (Resource_Import::is_owner($result)) {
            if (($call_kind !== 'construct') || (q_count($parameters) !== 0)) { throw new \RuntimeException('Allocation result requires empty zero-argument construction'); }
        }
        if (!$row->has('allocation_effect')) {
            if (q_count($owners) !== 0) { throw new \RuntimeException('Resource parameters require an allocation effect'); }
            return null;
        }
        $raw = $row->member('allocation_effect');
        if (($raw->kind() !== 'object') || (($call_kind !== 'free_function') && ($call_kind !== 'const_method'))) { throw new \RuntimeException('Invalid allocation effect'); }
        $spelling = $raw->member('kind')->text();
        if (($spelling !== 'acquire') && ($spelling !== 'release') && ($spelling !== 'transfer') && ($spelling !== 'inspect')) { throw new \RuntimeException('Invalid allocation effect kind'); }
        $kind = \type_model\Allocation_Effects::parse($spelling);
        $transfer = $kind === \type_model\ALLOCATION_TRANSFER;
        $expected_size = 2;
        if ($transfer) { $expected_size = 3; }
        if ($raw->size() !== $expected_size) { throw new \RuntimeException('Invalid allocation effect fields'); }
        $owner = $raw->member('owner')->integer();
        if (!isset($owners[$owner])) { throw new \RuntimeException('Invalid allocation effect owner'); }
        $destination = -1;
        if ($transfer) {
            $position = $raw->member('destination')->integer();
            if (!isset($owners[$position])) { throw new \RuntimeException('Invalid allocation effect destination'); }
            if ($position === $owner) { throw new \RuntimeException('Allocation transfer requires distinct same-type positions'); }
            if ($owners[$position]->member('type')->text() !== $owners[$owner]->member('type')->text()) { throw new \RuntimeException('Allocation transfer requires distinct same-type positions'); }
            $destination = $position;
        }
        $expected_owners = 1;
        if ($transfer) { $expected_owners = 2; }
        if (q_count($owners) !== $expected_owners) { throw new \RuntimeException('Allocation effect must account for every resource parameter'); }
        $passing = 'mutable_address';
        if ($kind === \type_model\ALLOCATION_INSPECT) { $passing = 'const_address'; }
        foreach ($owners as $parameter) {
            if ($parameter->member('passing')->text() !== $passing) { throw new \RuntimeException('Allocation effect requires compatible borrowed access'); }
        }
        if ($transfer) { return new \type_model\Allocation_Effect($kind, $owner, $destination); }
        return new \type_model\Allocation_Effect($kind, $owner, null);
    }
}
