<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Validate the small exposure vocabulary before generating any C++ or artifacts. */
final class Definitions
{
    /** @var array<string, array<string, mixed>> */
    public readonly array $types;
    /** @var array<string, array<string, mixed>> */
    public readonly array $operations;
    /** @var list<string> */
    public readonly array $files;
    /** @var array<string, string> Hash the bytes actually parsed, not a later reread. */
    public readonly array $source_hashes;

    /** Load exact identities, validate supported definitions and join lifecycle references before publication. */
    public function __construct(string $directory)
    {
        $files = glob($directory . '/*.json');
        if (($files === false) || ($files === [])) {
            throw new \RuntimeException('No definition JSON files in ' . $directory);
        }
        sort($files);
        $types = [];
        $operations = [];
        $source_hashes = [];
        foreach ($files as $file)
        {
            $contents = Files::read($file);
            $source_hashes[realpath($file)] = hash('sha256', $contents);
            $document = Files::object($contents, $file);
            self::fields($document, ['schema_version', 'types', 'operations'], $file);
            if ($document['schema_version'] !== 1) {
                throw new \RuntimeException('Unsupported definitions schema in ' . $file);
            }
            foreach (['types', 'operations'] as $key)
            {
                if ((!is_array($document[$key])) || (!array_is_list($document[$key]))) {
                    throw new \RuntimeException('Expected list: ' . $key);
                }
                foreach ($document[$key] as $row)
                {
                    if (!is_array($row)) {
                        throw new \RuntimeException('Expected definition object');
                    }
                    self::identifier($row['id'] ?? null, '/^[a-z][a-z0-9_.]*$/D', 'definition id');
                    if ($key === 'types') {
                        self::add($types, $row);
                    }
                    else {
                        self::add($operations, $row);
                    }
                }
            }
        }
        foreach ($types as $type) {
            self::validate_type($type);
            Resource_Contracts::type($type);
            if ($type['kind'] === 'value_record') {
                Record_Exposure::validate($type, $types);
            }
        }
        foreach ($operations as $operation) {
            self::validate_operation($operation, $types);
            Resource_Contracts::operation($operation, $types);
        }
        foreach ($types as $type)
        {
            if ($type['kind'] !== 'runtime_value') {
                continue;
            }
            $lifecycle = $type['lifecycle'];
            // A type may be produced only by returning functions, without exposing a constructor.
            if (isset($lifecycle['construct']))
            {
                $constructor = $operations[$lifecycle['construct']] ?? null;
                if (($constructor === null) || !in_array($constructor['kind'], ['construct_from_bytes', 'construct'], true)
                    || ($constructor['type'] !== $type['id'])) {
                    throw new \RuntimeException('Invalid lifecycle binding for ' . $type['id'] . ':construct');
                }
            }
            if (isset($lifecycle['default_construct']))
            {
                $operation = $operations[$lifecycle['default_construct']] ?? null;
                if (($operation === null) || ($operation['kind'] !== 'construct') || ($operation['type'] !== $type['id'])
                    || ($operation['parameters'] !== []) || isset($operation['expose_as'])) {
                    throw new \RuntimeException('Default construction requires an implicit zero-argument constructor');
                }
            }
            foreach (['destroy', 'copy_construct', 'move_construct', 'copy_assign'] as $role)
            {
                if (!isset($lifecycle[$role])) {
                    continue;
                }
                $operation = $operations[$lifecycle[$role]] ?? null;
                if (($operation === null) || ($operation['kind'] !== $role) || ($operation['type'] !== $type['id'])) {
                    throw new \RuntimeException('Invalid lifecycle binding for ' . $type['id'] . ':' . $role);
                }
            }
        }
        Storage_Contracts::validate($types, $operations);
        ksort($types);
        ksort($operations);
        $this->types = $types;
        $this->operations = $operations;
        $this->files = $files;
        ksort($source_hashes);
        $this->source_hashes = $source_hashes;
    }

    /** @param array<string, mixed> $value @param list<string> $required @param list<string> $optional */
    public static function fields(array $value, array $required, string $context, array $optional = []): void
    {
        $missing = array_diff($required, array_keys($value));
        $unknown = array_diff(array_keys($value), [...$required, ...$optional]);
        if (($missing !== []) || ($unknown !== [])) {
            throw new \RuntimeException($context . ': missing [' . implode(', ', $missing) . '], unknown [' . implode(', ', $unknown) . ']');
        }
    }

    public static function identifier(mixed $value, string $pattern, string $context): void
    {
        if ((!is_string($value)) || (!preg_match($pattern, $value))) {
            throw new \RuntimeException('Invalid ' . $context);
        }
    }

    /** @param array<string, array<string, mixed>> $rows @param array<string, mixed> $row */
    private static function add(array &$rows, array $row): void
    {
        if (isset($rows[$row['id']])) {
            throw new \RuntimeException('Duplicate definition: ' . $row['id']);
        }
        $rows[$row['id']] = $row;
    }

    /** Validate storage policy and explicit lifecycle bindings without inferring language capabilities.
     * @param array<string, mixed> $type */
    public static function validate_type(array $type): void
    {
        $kind = $type['kind'] ?? '';
        if (!in_array($kind, ['runtime_value', 'value_record', 'integer', 'byte_span', 'void', 'address'], true)) {
            throw new \RuntimeException('Unsupported type kind');
        }
        if (($kind === 'address') && isset($type['language_type'])) {
            throw new \RuntimeException('Native addresses cannot be exposed as source values');
        }
        $extra = match ($kind) {
            'runtime_value' => ['storage', 'lifecycle'],
            'value_record' => ['storage', 'construction', 'copy', 'cleanup', 'fields'],
            default => [],
        };
        self::fields($type, ['id', 'cpp_name', 'header', 'kind', ...$extra], 'type ' . $type['id'], ['language_type', 'struct_field', 'resource', 'storage_family', 'native_import', 'native_preparation', 'source_payload']);
        if (isset($type['native_preparation'])) {
            if (($kind !== 'runtime_value') || isset($type['native_import']) || isset($type['resource']) || isset($type['storage_family'])) {
                throw new \RuntimeException('Native preparation requires an owned opaque type');
            }
            Native_Types::preparation($type['native_preparation']);
        }
        if (isset($type['source_payload']))
        {
            if (!is_string($type['source_payload']) || ($type['source_payload'] === '') || ($kind !== 'runtime_value')
                || ($type['lifecycle'] !== []) || isset($type['native_import']) || isset($type['language_type'])
                || isset($type['native_preparation']) || isset($type['resource']) || isset($type['storage_family']) || isset($type['struct_field'])) {
                throw new \RuntimeException('Source payload rows require a distinct project import contract');
            }
        }
        if (isset($type['native_import']))
        {
            self::fields($type['native_import'], ['provider', 'id'], 'native type import');
            foreach ($type['native_import'] as $identity) {
                if (!is_string($identity) || ($identity === '')) {
                    throw new \RuntimeException('Native type import requires exact identities');
                }
            }
            if (($kind !== 'runtime_value') || ($type['lifecycle'] !== []) || isset($type['language_type'])
                || isset($type['resource']) || isset($type['storage_family']) || isset($type['struct_field'])) {
                throw new \RuntimeException('Native type imports reference existing opaque contracts');
            }
        }
        if (array_key_exists('struct_field', $type) && (($kind !== 'runtime_value') || !is_bool($type['struct_field']))) {
            throw new \RuntimeException('Runtime field eligibility requires an explicit boolean contract');
        }
        if (isset($type['language_type'])) {
            self::language_name($type['language_type']);
        }
        self::identifier($type['cpp_name'], '/^[A-Za-z_][A-Za-z0-9_]*(?:::[A-Za-z_][A-Za-z0-9_]*)*$/D', 'qualified C++ type name');
        self::identifier($type['header'], '/^[A-Za-z0-9_][A-Za-z0-9_\/.+-]*$/D', 'header');
        if (in_array('..', explode('/', $type['header']), true)) {
            throw new \RuntimeException('Header must use an include-root-relative path');
        }
        if ($kind === 'runtime_value')
        {
            if (($type['storage'] !== 'inline') || (!is_array($type['lifecycle']))) {
                throw new \RuntimeException('Runtime values require inline storage and lifecycle bindings');
            }
            self::fields($type['lifecycle'], [], 'lifecycle', ['construct', 'default_construct', 'destroy', 'cleanup', 'copy_construct', 'move_construct', 'copy_assign']);
            if (!isset($type['native_import']) && !isset($type['source_payload']) && (($type['lifecycle']['cleanup'] ?? null) !== 'none') && !isset($type['lifecycle']['destroy'])) {
                throw new \RuntimeException('Lifecycle requires a destroy operation or explicit cleanup none');
            }
            if (isset($type['lifecycle']['cleanup']) && ($type['lifecycle']['cleanup'] !== 'none')) {
                throw new \RuntimeException('Unsupported lifecycle cleanup policy');
            }
            foreach (array_intersect_key($type['lifecycle'], array_flip(['construct', 'default_construct', 'destroy', 'copy_construct', 'move_construct', 'copy_assign'])) as $id) {
                self::identifier($id, '/^[a-z][a-z0-9_.]*$/D', 'lifecycle operation id');
            }
        }
    }

    /** Validate the fields and referenced types required by each implemented adaptation.
     * @param array<string, mixed> $operation @param array<string, array<string, mixed>> $types */
    private static function validate_operation(array $operation, array $types): void
    {
        $kind = $operation['kind'] ?? '';
        if (!in_array($kind, ['construct_from_bytes', 'const_method', 'destroy', 'copy_construct', 'move_construct', 'copy_assign', 'free_function', 'construct'], true)) {
            throw new \RuntimeException('Unsupported operation kind: ' . $kind);
        }
        $extra = match ($kind) {
            'const_method' => ['type', 'member', 'result_type'],
            'construct' => ['type', 'parameters'],
            'free_function' => ['cpp_name', 'header', 'parameters', 'result_type'],
            default => ['type'],
        };
        self::fields($operation, ['id', 'kind', 'error_policy', ...$extra], 'operation ' . $operation['id'],
            ['expose_as', 'language_binding', 'default_literal', 'allocation_effect', ...($kind === 'const_method' ? ['borrow_scope'] : []),
                ...($kind === 'construct_from_bytes' ? ['parameter_type'] : []),
                ...($kind === 'free_function' ? ['cpp_template_arguments', 'source_payload_result', 'conversion_purpose'] : [])]);
        self::validate_language_binding($operation, $types);
        if (isset($operation['parameter_type']) && (($types[$operation['parameter_type']]['kind'] ?? null) !== 'byte_span')) {
            throw new \RuntimeException('Byte construction requires a declared byte-span parameter');
        }
        if (isset($operation['borrow_scope']) && ($operation['borrow_scope'] !== 'call')) {
            throw new \RuntimeException('Only call-scoped borrowing is supported');
        }
        if (isset($operation['expose_as'])) {
            if (in_array($kind, ['copy_construct', 'move_construct', 'copy_assign'], true)) {
                throw new \RuntimeException('Source construction and assignment are implicit lifecycle operations');
            }
            self::language_name($operation['expose_as']);
        }
        if ($operation['error_policy'] !== 'terminate') {
            throw new \RuntimeException('Only terminate error policy is implemented');
        }
        if ($kind === 'free_function') {
            self::validate_function($operation, $types);
            return;
        }
        self::identifier($operation['type'], '/^[a-z][a-z0-9_.]*$/D', 'operation type');
        if (($types[$operation['type']]['kind'] ?? '') !== 'runtime_value') {
            throw new \RuntimeException('Operation requires a declared runtime value');
        }
        if ($kind === 'construct')
        {
            if (!is_array($operation['parameters']) || !array_is_list($operation['parameters'])) {
                throw new \RuntimeException('Constructor parameters must be a list');
            }
            foreach ($operation['parameters'] as $id) {
                self::identifier($id, '/^[a-z][a-z0-9_.]*$/D', 'constructor type');
                if (($types[$id]['kind'] ?? '') !== 'integer') {
                    throw new \RuntimeException('Constructor adapter requires declared integer arguments');
                }
            }
        }
        if ($kind === 'const_method') {
            self::identifier($operation['member'], '/^[A-Za-z_][A-Za-z0-9_]*$/D', 'method name');
            self::identifier($operation['result_type'], '/^[a-z][a-z0-9_.]*$/D', 'method result type');
            if (($types[$operation['result_type']]['kind'] ?? '') !== 'integer') {
                throw new \RuntimeException('First method adapter requires a declared integer result');
            }
        }
    }

    /** Require each language binding to select an implemented, correctly shaped operation. */
    private static function validate_language_binding(array $operation, array $types): void
    {
        $role = $operation['language_binding'] ?? null;
        if (($role !== null) && (!isset($operation['expose_as']) || !in_array($role, ['byte_literal', 'echo'], true))) {
            throw new \RuntimeException('Unsupported language operation binding');
        }
        if (isset($operation['default_literal']) && (($role !== 'byte_literal') || !is_bool($operation['default_literal']))) {
            throw new \RuntimeException('A default literal requires a byte-literal binding');
        }
        if (($role === 'byte_literal') && (($operation['kind'] !== 'construct_from_bytes')
                || !isset($operation['parameter_type']))) {
            throw new \RuntimeException('Byte literal binding requires a declared span constructor');
        }
        if (($role === 'echo') && (($operation['kind'] !== 'free_function')
                || (count($operation['parameters'] ?? []) !== 1)
                || (($operation['parameters'][0]['passing'] ?? null) !== 'const_address')
                || (($types[$operation['result_type'] ?? '']['kind'] ?? null) !== 'void'))) {
            throw new \RuntimeException('Echo binding requires one borrowed object and no result');
        }
    }

    /** Validate exact free-function parameters and result before emitting a callable adaptation. */
    private static function validate_function(array $operation, array $types): void
    {
        self::identifier($operation['cpp_name'], '/^[A-Za-z_][A-Za-z0-9_]*(?:::[A-Za-z_][A-Za-z0-9_]*)*$/D', 'qualified C++ function name');
        self::identifier($operation['header'], '/^[A-Za-z0-9_][A-Za-z0-9_\/.+-]*$/D', 'function header');
        if (in_array('..', explode('/', $operation['header']), true)
            || !is_array($operation['parameters']) || !array_is_list($operation['parameters'])) {
            throw new \RuntimeException('Invalid function header or parameter list');
        }
        // Explicit template arguments select one real C++ function specialization.
        $template_arguments = $operation['cpp_template_arguments'] ?? [];
        if (!is_array($template_arguments) || !array_is_list($template_arguments)) {
            throw new \RuntimeException('Function template arguments must be a list of declared types');
        }
        foreach ($template_arguments as $id) {
            if (!is_string($id) || !isset($types[$id])) {
                throw new \RuntimeException('Function template argument requires a declared type');
            }
        }
        foreach ($operation['parameters'] as $parameter) {
            self::validate_function_parameter($parameter, $types);
        }
        if (!in_array($types[$operation['result_type']]['kind'] ?? null, ['integer', 'void', 'runtime_value', 'value_record', 'address'], true)) {
            throw new \RuntimeException('Free-function result requires an integer, void or inline object');
        }

        $payload_result = isset($types[$operation['result_type']]['source_payload']);
        if (($payload_result !== isset($operation['source_payload_result']))
            || ($payload_result && ($operation['source_payload_result'] !== 'copy_out'))) {
            throw new \RuntimeException('Owned source payload result requires exactly its copy-out crossing');
        }

        if (($types[$operation['result_type']]['kind'] === 'address') && isset($operation['expose_as'])) {
            throw new \RuntimeException('Native address results must remain internal primitives');
        }

        // Conversion entry points are explicit one-input operations, not implicit assignment permission.
        if (isset($operation['conversion_purpose']))
        {
            if (!in_array($operation['conversion_purpose'], ['explicit_cast', 'text'], true)
                || !isset($operation['expose_as']) || isset($operation['language_binding'])
                || (count($operation['parameters']) !== 1) || ($types[$operation['result_type']]['kind'] === 'void')) {
                throw new \RuntimeException('Unsupported conversion operation contract');
            }
            $parameter = $operation['parameters'][0];
            $source = is_string($parameter) ? $parameter : $parameter['type'];
            if ($source === $operation['result_type']) {
                throw new \RuntimeException('Conversion operation cannot replace type identity');
            }
        }
    }

    /** Distinguish semantic value/address passing from the C++ function's parameter spelling. */
    private static function validate_function_parameter(mixed $parameter, array $types): void
    {
        if (is_string($parameter)) {
            if (($types[$parameter]['kind'] ?? null) !== 'integer') {
                throw new \RuntimeException('Direct function parameter requires a declared integer');
            }
            return;
        }
        if (!is_array($parameter)) {
            throw new \RuntimeException('Invalid function parameter');
        }
        $kind = $types[$parameter['type'] ?? '']['kind'] ?? null;
        if (($parameter['passing'] ?? null) === 'direct') {
            self::fields($parameter, ['type', 'passing', 'cpp_passing'], 'function value');
            if (($kind !== 'integer') || ($parameter['cpp_passing'] !== 'const_reference')) {
                throw new \RuntimeException('Unsupported C++ value parameter adaptation');
            }
            return;
        }
        self::fields($parameter, ['type', 'passing', 'borrow_scope'], 'function borrow', ['source_payload']);
        if (isset($parameter['source_payload']) && (($parameter['source_payload'] !== 'copy_in')
            || ($parameter['passing'] !== 'const_address') || !isset($types[$parameter['type']]['source_payload']))) {
            throw new \RuntimeException('Invalid source payload parameter crossing');
        }
        if (!in_array($parameter['passing'], ['const_address', 'mutable_address'], true) || ($parameter['borrow_scope'] !== 'call')
            || (!in_array($kind, ['runtime_value', 'value_record'], true)
                && !(($kind === 'integer') && ($parameter['passing'] === 'const_address')))) {
            throw new \RuntimeException('Function object parameters require call-scoped const or mutable borrowing');
        }
    }

    private static function language_name(array $reference): void
    {
        self::fields($reference, ['name', 'namespace'], 'language name');
        self::identifier($reference['name'], '/^[A-Za-z_][A-Za-z0-9_]*$/D', 'language name');
        if ($reference['namespace'] !== '') {
            throw new \RuntimeException('Only global language names are supported in this slice');
        }
    }
}
