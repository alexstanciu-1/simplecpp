<?php
declare(strict_types=1);
namespace load_runtime;

/** Fixed physical protocol role; source element types are not inferred here. */
final class Storage_Primitive_Schema {
    public function __construct(public readonly int $result_kind, public readonly bool $read_only,
        public readonly int $counter_parameters, public readonly bool $second_descriptor) {}
}
final class Storage_Import {
    public static function schema(string $role): Storage_Primitive_Schema {
        $result = \load_runtime\RUNTIME_STORAGE_VOID; $read_only = false; $counters = 0; $second = false;
        if ($role === 'allocate') { $counters = 3; }
        elseif ($role === 'next') { $result = \load_runtime\RUNTIME_STORAGE_ADDRESS; }
        elseif ($role === 'at') { $result = \load_runtime\RUNTIME_STORAGE_ADDRESS; $read_only = true; $counters = 1; }
        elseif ($role === 'count') { $result = \load_runtime\RUNTIME_STORAGE_INTEGER; $read_only = true; }
        elseif ($role === 'transfer') { $second = true; }
        elseif (($role !== 'commit') && ($role !== 'pop') && ($role !== 'release')) { throw new \RuntimeException('Unknown storage primitive role'); }
        return new Storage_Primitive_Schema($result, $read_only, $counters, $second);
    }
    private static function text(\scpp\Json_View $row, string $key, string $expected): void {
        if ($row->member($key)->text() !== $expected) { throw new \RuntimeException('Unsupported storage primitive ' . $key); }
    }
    private static function present(\scpp\Json_View $row, string $key): bool {
        if (!$row->has($key)) { return false; }
        return $row->member($key)->kind() !== 'null';
    }
    private static function type(array $types /** hash<Runtime_Type> */, string $id): Runtime_Type {
        if (!isset($types[$id])) { throw new \RuntimeException('Unknown storage protocol type'); }
        return $types[$id];
    }
    public static function storage_primitive(\scpp\Json_View $row, string $owner, string $counter,
        array $types /** hash<Runtime_Type> */, string $role): \type_model\Storage_Primitive {
        $schema = Storage_Import::schema($role);
        Storage_Import::text($row, 'kind', 'free_function');
        if (Storage_Import::present($row,'expose_as') || Storage_Import::present($row,'allocation_effect') || Storage_Import::present($row,'conversion_purpose')) { throw new \RuntimeException('Storage primitive cannot have source roles'); }
        Storage_Import::text($row, 'calling_convention', 'ccc');
        Storage_Import::text($row, 'error_policy', 'terminate');
        Storage_Import::text($row, 'exception_boundary', 'caught_in_bridge');
        $result = $row->member('result');
        Storage_Import::text($result, 'passing', 'direct'); Storage_Import::text($result, 'ownership', 'value');
        $abi = $row->member('abi');
        $semantic = Package_Syntax::rows($row->member('parameters'), 'storage parameter');
        $physical = Package_Syntax::rows($abi->member('parameters'), 'storage ABI parameter');
        $expected /** vector<string> */ = [$owner];
        if ($schema->second_descriptor) { $expected[] = $owner; }
        else { for ($index = 0; $index < $schema->counter_parameters; $index++) { $expected[] = $counter; } }
        if ((q_count($semantic) !== q_count($expected)) || (q_count($physical) !== q_count($expected))) { throw new \RuntimeException('Invalid storage primitive arity'); }
        $parameters /** vector<\type_model\Runtime_Abi_Position> */ = [];
        foreach ($expected as $index => $id) {
            $parameter = $semantic[$index]; Storage_Import::text($parameter, 'type', $id);
            $indices = $parameter->member('abi_indices');
            if ($indices->kind() !== 'array') { throw new \RuntimeException('Invalid storage ABI indices'); }
            if ($indices->size() !== 1) { throw new \RuntimeException('Invalid storage ABI indices'); }
            if ($indices->at(0)->integer() !== $index) { throw new \RuntimeException('Invalid storage ABI index'); }
            if ($id === $owner) {
                $passing = $schema->read_only ? 'const_address' : 'mutable_address';
                Storage_Import::text($parameter,'passing',$passing); Storage_Import::text($parameter,'ownership','borrowed'); Storage_Import::text($parameter,'borrow_scope','call');
                Package_Syntax::address_abi($physical[$index]);
                $parameters[] = \type_model\Runtime_Abi_Position::borrow(!$schema->read_only);
            } else {
                Storage_Import::text($parameter,'passing','direct'); Storage_Import::text($parameter,'ownership','value');
                $parameters[] = Package_Syntax::integer_abi($physical[$index]->member('type'),$physical[$index]->member('attributes'),Storage_Import::type($types,$counter));
            }
        }
        $result_type = Storage_Import::type($types,Package_Syntax::identifier($result->member('type')));
        if ($result_type->storage->kind !== $schema->result_kind) { throw new \RuntimeException('Storage primitive result kind mismatch'); }
        $link = $row->member('symbol')->text(); Package_Syntax::require_identifier_spelling($link);
        if ($schema->result_kind === \load_runtime\RUNTIME_STORAGE_ADDRESS) {
            Package_Syntax::address_parts($abi->member('return_type'),$abi->member('return_attributes'));
            return new \type_model\Storage_Primitive($link,\type_model\Runtime_Abi_Position::borrow(false),$parameters);
        }
        if ($schema->result_kind === \load_runtime\RUNTIME_STORAGE_INTEGER) {
            if ($result_type !== Storage_Import::type($types,$counter)) { throw new \RuntimeException('Storage count result differs from counter'); }
            $result_abi = Package_Syntax::integer_abi($abi->member('return_type'),$abi->member('return_attributes'),$result_type);
            return new \type_model\Storage_Primitive($link,$result_abi,$parameters);
        }
        Storage_Import::text($abi,'return_type','void'); Storage_Import::text($abi,'return_attributes','');
        return new \type_model\Storage_Primitive($link,null,$parameters);
    }
    private static function void_type(array $types /** hash<Runtime_Type> */): ?\type_model\Named_Definition {
        foreach ($types as $type) {
            if ($type->storage->kind === \load_runtime\RUNTIME_STORAGE_VOID) {
                if ($type->language_type !== null) { return $type->language_type; }
            }
        }
        return null;
    }
    public static function storage_families(array $rows /** vector<\scpp\Json_View> */, array $types /** hash<Runtime_Type> */,
        array $operations /** vector<\scpp\Json_View> */, string $provider): array /** vector<\type_model\Storage_Family> */ {
        $by_id /** hash<\scpp\Json_View> */ = [];
        foreach ($operations as $operation) { $id=Package_Syntax::identifier($operation->member('id')); $by_id[$id]=$operation; }
        $families /** vector<\type_model\Storage_Family> */ = [];
        $roles /** vector<string> */ = ['allocate','next','commit','at','pop','count','release','transfer'];
        foreach ($rows as $row) {
            if (!$row->has('storage_family')) { continue; }
            $raw=$row->member('storage_family');
            if ($raw->kind() !== 'object') { throw new \RuntimeException('Invalid storage family record'); }
            if ($raw->size() !== 3) { throw new \RuntimeException('Invalid storage family fields'); }
            Storage_Import::text($row,'kind','runtime_value');
            if (Storage_Import::present($row,'resource')) { throw new \RuntimeException('Storage descriptor cannot own an allocation'); }
            $id=Package_Syntax::identifier($row->member('id')); $descriptor=Storage_Import::type($types,$id)->language_type;
            $counter_id=Package_Syntax::identifier($raw->member('counter_type')); $counter=Storage_Import::type($types,$counter_id);
            $counter_definition=$counter->language_type; $void_definition=Storage_Import::void_type($types);
            if (($descriptor===null) || ($counter_definition===null) || ($void_definition===null)) { throw new \RuntimeException('Storage family requires exposed descriptor/counter/void'); }
            $lifetime=$descriptor->lifetime;
            if ($lifetime===null) { throw new \RuntimeException('Storage descriptor requires lifetime'); }
            $policy=$lifetime->policy();
            if (($descriptor->representation->kind()!==\type_model\REPRESENTATION_OPAQUE) || ((int)$policy->copy!==\type_model\COPY_UNAVAILABLE) || ((int)$policy->cleanup!==\type_model\CLEANUP_NONE) || (!$lifetime->has_operation(\type_model\LIFECYCLE_DEFAULT)) || ($counter->signed!==true)) { throw new \RuntimeException('Unsupported storage family contract'); }
            $raw_primitives=$raw->member('primitives');
            if ($raw_primitives->kind()!=='object') { throw new \RuntimeException('Invalid storage primitive map'); }
            if ($raw_primitives->size()!==8) { throw new \RuntimeException('Storage family requires complete primitives'); }
            $primitives /** hash<\type_model\Storage_Primitive> */ = [];
            foreach ($roles as $role) {
                $operation_id=Package_Syntax::identifier($raw_primitives->member($role));
                if (!isset($by_id[$operation_id])) { throw new \RuntimeException('Missing storage primitive'); }
                $primitives[$role]=Storage_Import::storage_primitive($by_id[$operation_id],$id,$counter_id,$types,$role);
            }
            $raw_names=$raw->member('operations');
            if ($raw_names->kind()!=='object') { throw new \RuntimeException('Invalid storage operation names'); }
            if (($raw_names->size()!==6) || ($descriptor->namespace_name!=='')) { throw new \RuntimeException('Storage family requires complete root operation names'); }
            $names /** hash<string> */ = []; $seen /** hash<bool> */ = [];
            for ($role=0;$role<6;$role++) {
                $key=\type_model\Storage_Roles::name($role); $name=$raw_names->member($key)->text(); Package_Syntax::require_identifier_spelling($name);
                if (isset($seen[$name])) { throw new \RuntimeException('Duplicate storage operation name'); }
                $seen[$name]=true; $names[$key]=$name;
            }
            $families[]=new \type_model\Storage_Family($provider,$id,$descriptor,$counter_definition,$void_definition,$primitives,$names,$descriptor->name,$descriptor->namespace_name);
        }
        return $families;
    }
}
