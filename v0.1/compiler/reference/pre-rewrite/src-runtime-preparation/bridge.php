<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Generate concrete C++ adaptations by operation role, independent of type names. */
final class Bridge
{
    public readonly string $source;
    public readonly string $header;
    /** @var array<string, array<string, mixed>> */
    public readonly array $types;
    /** @var array<string, array<string, mixed>> */
    public readonly array $contracts;

    /** Generate the complete bridge, declarations and semantic contracts from validated definitions. */
    public function __construct(Definitions $definitions, public readonly string $provider, public readonly ?project\module_contract $project = null)
    {
        foreach ($definitions->types as $type)
        {
            if (isset($type['source_payload']))
            {
                $source = $project?->sources[$type['source_payload']] ?? null;
                if (($source === null) || ($type['cpp_name'] !== project\Adapter::name($source))) {
                    throw new \RuntimeException('Source payload requires its exact authorized project contract');
                }
            }
        }
        $headers = ['cstddef', 'cstdint', 'cstdio', 'cstdlib', 'exception', 'new', 'string_view', 'type_traits', 'utility', 'limits'];
        foreach ($definitions->types as $type) {
            $headers[] = $type['header'];
        }
        foreach ($definitions->operations as $operation) {
            if (isset($operation['header'])) {
                $headers[] = $operation['header'];
            }
        }
        $headers = array_unique($headers);
        sort($headers);
        $source = '';
        foreach ($headers as $header) {
            $source .= '#include <' . $header . ">\n";
        }
        $source .= <<<'CPP'

namespace {
[[noreturn]] void rp_failure(const char *operation, const char *message) noexcept {
    std::fprintf(stderr, "runtime operation %s failed: %s\n", operation, message);
    std::exit(EXIT_FAILURE);
}
}

CPP;
        $types = [];
        foreach ($definitions->types as $type)
        {
            $type['fact_prefix'] = Symbols::name(['type', $provider, $type['id']]);
            $source .= self::layout_source($type);
            if ($type['kind'] === 'value_record') {
                $source .= Record_Exposure::source($type, $definitions->types);
            }
            $types[$type['id']] = $type;
        }
        $header = "#pragma once\n#include <cstddef>\n#include <cstdint>\n";
        // Integer aliases may need provider headers. Runtime object declarations
        // do not: their ABI uses addresses of caller-owned storage.
        foreach ($definitions->types as $type) {
            if (in_array($type['kind'], ['integer', 'void'], true)) {
                $header .= '#include <' . $type['header'] . ">\n";
            }
        }
        $contracts = [];
        foreach ($definitions->operations as $operation)
        {
            [$declaration, $body, $contract] = self::operation($operation, $definitions, $provider, $project);
            $header .= $declaration . ";\n";
            $source .= $declaration . " {\n    try {\n" . $body . "\n    }\n";
            $source .= '    catch (const std::exception &error) { rp_failure("' . $operation['id'] . '", error.what()); }' . "\n";
            $source .= '    catch (...) { rp_failure("' . $operation['id'] . '", "unknown C++ exception"); }' . "\n}\n";
            $contracts[$operation['id']] = $contract;
        }
        $this->source = $source;
        $this->header = $header;
        $this->types = $types;
        $this->contracts = $contracts;
    }

    /** Emit Clang-evaluated storage and capability facts for one declared type.
     * @param array<string, mixed> $type */
    private static function layout_source(array $type): string
    {
        $cpp = $type['cpp_name'];
        if ($type['kind'] === 'void') {
            return 'static_assert(std::is_void_v<' . $cpp . '>);' . "\n";
        }
        $prefix = $type['fact_prefix'];
        $facts = ['size' => 'sizeof(' . $cpp . ')', 'alignment' => 'alignof(' . $cpp . ')',
            'trivially_copyable' => 'std::is_trivially_copyable_v<' . $cpp . '>',
            'trivially_destructible' => 'std::is_trivially_destructible_v<' . $cpp . '>',
            'copy_constructible' => 'std::is_copy_constructible_v<' . $cpp . '>',
            'copy_assignable' => 'std::is_copy_assignable_v<' . $cpp . '>',
            'move_constructible' => 'std::is_move_constructible_v<' . $cpp . '>'];
        $source = '';
        if ($type['kind'] === 'integer') {
            $source .= 'static_assert(std::is_integral_v<' . $cpp . '> && !std::is_same_v<' . $cpp . ', bool>);' . "\n";
            $facts['signed'] = 'std::is_signed_v<' . $cpp . '>';
            $facts['value_bits'] = 'std::numeric_limits<' . $cpp . '>::digits + std::is_signed_v<' . $cpp . '>';
        }
        elseif ($type['kind'] === 'address') {
            $source .= 'static_assert(std::is_same_v<' . $cpp . ', void *>);' . "\n";
        }
        else {
            $source .= 'static_assert(std::is_class_v<' . $cpp . '> && std::is_destructible_v<' . $cpp . '>);' . "\n";
        }
        if ($type['kind'] === 'byte_span') {
            $source .= 'static_assert(std::is_same_v<' . $cpp . ', std::string_view>);' . "\n";
        }
        if (($type['lifecycle']['cleanup'] ?? null) === 'none') {
            $source .= 'static_assert(std::is_trivially_destructible_v<' . $cpp . '>, "cleanup none requires trivial destruction");' . "\n";
        }
        foreach ($facts as $name => $expression) {
            $source .= 'extern "C" const std::uint64_t ' . Symbols::append($prefix, $name) . ' = ' . $expression . ";\n";
        }
        return $source;
    }

    /** Dispatch implemented bridge adaptations by their declared semantic role.
     * @param array<string, mixed> $operation @return array{string, string, array<string, mixed>} */
    private static function operation(array $operation, Definitions $definitions, string $provider, ?project\module_contract $project): array
    {
        $symbol = Symbols::name(['op', $provider, $operation['id']]);
        $contract = ['id' => $operation['id'], 'kind' => $operation['kind'],
            'symbol' => $symbol, 'error_policy' => 'terminate', 'exception_boundary' => 'caught_in_bridge',
            'calling_convention' => 'ccc'];
        foreach (['language_binding', 'default_literal', 'conversion_purpose', 'allocation_effect'] as $field) {
            if (isset($operation[$field])) {
                $contract[$field] = $operation[$field];
            }
        }
        if (isset($operation['expose_as'])) {
            $contract['expose_as'] = $operation['expose_as'];
        }
        if ($operation['kind'] === 'free_function') {
            return self::free_function($operation, $definitions, $contract, $project);
        }
        $type = $definitions->types[$operation['type']];
        $cpp = $type['cpp_name'];
        $contract['type'] = $type['id'];
        if (in_array($operation['kind'], ['copy_construct', 'move_construct'], true)) {
            return self::source_construct($operation, $cpp, $contract);
        }
        if ($operation['kind'] === 'copy_assign') {
            return self::copy_assign($operation, $cpp, $contract);
        }
        if ($operation['kind'] === 'construct') {
            return self::construct($operation, $definitions, $contract);
        }
        if ($operation['kind'] === 'construct_from_bytes')
        {
            // This adapter means construction from a borrowed byte span via
            // std::string_view. Its availability is proved by compiling the call.
            $declaration = 'extern "C" void ' . $symbol . '(void *result, const char *bytes, std::size_t length) noexcept';
            $body = '        if ((result == nullptr) || ((bytes == nullptr) && (length != 0))) { rp_failure("'
                . $operation['id'] . '", "invalid storage or byte span"); }' . "\n";
            $body .= '        ::new (result) ' . $cpp . '(std::string_view(bytes == nullptr ? "" : bytes, length));';
            $contract['parameters'] = [['name' => 'bytes', 'kind' => 'byte_span', 'ownership' => 'borrowed', 'abi_indices' => [1, 2]]];
            if (isset($operation['parameter_type'])) {
                $contract['parameters'][0] += ['type' => $operation['parameter_type'], 'passing' => 'byte_span', 'borrow_scope' => 'call'];
            }
            $contract['result'] = ['type' => $type['id'], 'ownership' => 'owned', 'passing' => 'caller_storage', 'abi_index' => 0];
            $contract['storage_precondition'] = 'aligned_uninitialized_storage';
            $contract['cpp_parameters'] = ['void *', 'const char *', 'std::size_t'];
            $contract['cpp_return'] = 'void';
        }
        elseif ($operation['kind'] === 'const_method')
        {
            $result = $definitions->types[$operation['result_type']];
            $declaration = 'extern "C" ' . $result['cpp_name'] . ' ' . $symbol . '(const void *value) noexcept';
            $body = '        static_assert(std::is_same_v<decltype(std::declval<const ' . $cpp . '&>().'
                . $operation['member'] . '()), ' . $result['cpp_name'] . '>);' . "\n";
            $body .= '        if (value == nullptr) { rp_failure("' . $operation['id'] . '", "null object"); }' . "\n";
            $body .= '        return std::launder(static_cast<const ' . $cpp . ' *>(value))->' . $operation['member'] . '();';
            $contract['member'] = $operation['member'];
            $contract['parameters'] = [['name' => 'value', 'type' => $type['id'], 'ownership' => 'borrowed', 'passing' => 'const_address', 'abi_indices' => [0]]];
            if (isset($operation['borrow_scope'])) {
                $contract['parameters'][0]['borrow_scope'] = $operation['borrow_scope'];
            }
            $contract['result'] = ['type' => $result['id'], 'ownership' => 'value', 'passing' => 'direct'];
            $contract['storage_precondition'] = 'live_object';
            $contract['cpp_parameters'] = ['const void *'];
            $contract['cpp_return'] = $result['cpp_name'];
        }
        else
        {
            $declaration = 'extern "C" void ' . $symbol . '(void *value) noexcept';
            $body = '        if (value == nullptr) { rp_failure("' . $operation['id'] . '", "null object"); }' . "\n";
            $body .= '        using object_type = ' . $cpp . ";\n";
            $body .= '        std::launder(static_cast<object_type *>(value))->~object_type();';
            $contract['parameters'] = [['name' => 'value', 'type' => $type['id'], 'ownership' => 'consumed', 'passing' => 'address', 'abi_indices' => [0]]];
            $contract['result'] = null;
            $contract['storage_precondition'] = 'live_owned_object';
            $contract['storage_after'] = 'uninitialized_caller_storage';
            $contract['cpp_parameters'] = ['void *'];
            $contract['cpp_return'] = 'void';
        }
        return [$declaration, $body, $contract];
    }

    /** Construct from the declared source category; Clang selects the native constructor, including legal copy fallback. */
    private static function source_construct(array $operation, string $cpp, array $contract): array
    {
        $moving = $operation['kind'] === 'move_construct';
        $qualifier = $moving ? '' : 'const ';
        $trait = $moving ? 'move_constructible' : 'copy_constructible';
        $declaration = 'extern "C" void ' . $contract['symbol'] . '(void *result, ' . $qualifier . 'void *source) noexcept';
        $body = '        static_assert(std::is_' . $trait . '_v<' . $cpp . '>);' . "\n";
        $body .= '        if ((result == nullptr) || (source == nullptr) || (result == source)) { rp_failure("'
            . $operation['id'] . '", "invalid construction storage"); }' . "\n";
        $source = '*std::launder(static_cast<' . $qualifier . $cpp . ' *>(source))';
        $body .= '        ::new (result) ' . $cpp . '(' . ($moving ? 'std::move(' . $source . ')' : $source) . ');';

        // The source remains live and destructible; movement does not promise an empty source.
        $contract['parameters'] = [['name' => 'source', 'type' => $operation['type'], 'ownership' => 'borrowed',
            'passing' => $moving ? 'mutable_address' : 'const_address', 'borrow_scope' => 'call', 'abi_indices' => [1]]];
        $contract['result'] = ['type' => $operation['type'], 'ownership' => 'owned', 'passing' => 'caller_storage', 'abi_index' => 0];
        $contract['storage_precondition'] = 'aligned_uninitialized_storage';
        $contract['storage_after'] = 'live_owned_object';
        $contract['source_precondition'] = 'live_object';
        $contract['source_after'] = 'live_object';
        $contract['cpp_parameters'] = ['void *', $qualifier . 'void *'];
        $contract['cpp_return'] = 'void';
        return [$declaration, $body, $contract];
    }

    /** Update a live object through its native assignment; self-assignment executes the same operation. */
    private static function copy_assign(array $operation, string $cpp, array $contract): array
    {
        $declaration = 'extern "C" void ' . $contract['symbol'] . '(void *destination, const void *source) noexcept';
        $body = '        static_assert(std::is_copy_assignable_v<' . $cpp . '>);' . "\n";
        $body .= '        if ((destination == nullptr) || (source == nullptr)) { rp_failure("'
            . $operation['id'] . '", "invalid assignment storage"); }' . "\n";
        $body .= '        *std::launder(static_cast<' . $cpp . ' *>(destination)) = *std::launder(static_cast<const ' . $cpp . ' *>(source));';

        // Both operands stay live. The native implementation owns replacement of the old contents.
        $contract['parameters'] = [
            ['name' => 'destination', 'type' => $operation['type'], 'ownership' => 'borrowed',
                'passing' => 'mutable_address', 'borrow_scope' => 'call', 'abi_indices' => [0]],
            ['name' => 'source', 'type' => $operation['type'], 'ownership' => 'borrowed',
                'passing' => 'const_address', 'borrow_scope' => 'call', 'abi_indices' => [1]],
        ];
        $contract['result'] = null;
        $contract['storage_precondition'] = 'live_object';
        $contract['storage_after'] = 'live_object';
        $contract['source_precondition'] = 'live_object';
        $contract['source_after'] = 'live_object';
        $contract['self_assignment'] = 'native_call';
        $contract['cpp_parameters'] = ['void *', 'const void *'];
        $contract['cpp_return'] = 'void';
        return [$declaration, $body, $contract];
    }

    /** Construct directly into the destination; arguments use their declared scalar types. */
    private static function construct(array $operation, Definitions $definitions, array $contract): array
    {
        $cpp = $definitions->types[$operation['type']]['cpp_name'];
        $declarations = ['void *result'];
        $cpp_types = ['void *'];
        $arguments = [];
        $parameters = [];
        foreach ($operation['parameters'] as $index => $id)
        {
            $type = $definitions->types[$id]['cpp_name'];
            $name = 'arg' . $index;
            $declarations[] = $type . ' ' . $name;
            $cpp_types[] = $type;
            $arguments[] = $name;
            $parameters[] = ['name' => $name, 'type' => $id, 'ownership' => 'value', 'passing' => 'direct', 'abi_indices' => [$index + 1]];
        }
        $declaration = 'extern "C" void ' . $contract['symbol'] . '(' . implode(', ', $declarations) . ') noexcept';
        $body = '        if (result == nullptr) { rp_failure("' . $operation['id'] . '", "null destination"); }' . "\n";
        $body .= '        ::new (result) ' . $cpp . '(' . implode(', ', $arguments) . ');';
        $contract['parameters'] = $parameters;
        $contract['result'] = ['type' => $operation['type'], 'ownership' => 'owned', 'passing' => 'caller_storage', 'abi_index' => 0];
        $contract['storage_precondition'] = 'aligned_uninitialized_storage';
        $contract['cpp_parameters'] = $cpp_types;
        $contract['cpp_return'] = 'void';
        return [$declaration, $body, $contract];
    }

    /** Adapt declared scalar, native-object and source-payload arguments with direct or owned results.
     * @return array{string, string, array<string, mixed>} */
    private static function free_function(array $operation, Definitions $definitions, array $contract, ?project\module_contract $project): array
    {
        $result_type = $definitions->types[$operation['result_type']];
        $result = $result_type['cpp_name'];
        $owned = in_array($result_type['kind'], ['runtime_value', 'value_record'], true);
        $offset = $owned ? 1 : 0;
        $cpp_types = [];
        $arguments = [];
        $declarations = $owned ? ['void *result'] : [];
        $physical_types = $owned ? ['void *'] : [];
        $parameters = [];
        $copies = [];
        foreach ($operation['parameters'] as $index => $parameter)
        {
            $id = is_array($parameter) ? $parameter['type'] : $parameter;
            $borrow = is_array($parameter) && in_array($parameter['passing'], ['const_address', 'mutable_address'], true);
            $mutable = ($borrow) && ($parameter['passing'] === 'mutable_address');
            $qualifier = $mutable ? '' : 'const ';
            $reference = ($borrow) || (is_array($parameter) && (($parameter['cpp_passing'] ?? null) === 'const_reference'));
            $cpp = $definitions->types[$id]['cpp_name'];
            $name = 'arg' . $index;
            $cpp_types[] = $reference ? $qualifier . $cpp . ' &' : $cpp;
            $arguments[] = $borrow ? '*std::launder(static_cast<' . $qualifier . $cpp . ' *>(' . $name . '))' : $name;
            if (isset($definitions->types[$id]['source_payload']))
            {
                if ((!$borrow) || ($mutable) || (($parameter['source_payload'] ?? null) !== 'copy_in')) {
                    throw new \RuntimeException('Source payload parameter requires explicit const copy-in');
                }
                $source = $project?->sources[$definitions->types[$id]['source_payload']]
                    ?? throw new \RuntimeException('Missing source argument contract');
                $copies[] = project\Adapter::copy_in($source, $name, 'copied_' . $index);
                $arguments[array_key_last($arguments)] = 'copied_' . $index;
            }
            $physical = $borrow ? $qualifier . 'void *' : $cpp;
            $physical_types[] = $physical;
            $declarations[] = $physical . ' ' . $name;
            $parameters[] = ['name' => $name, 'type' => $id, 'ownership' => $borrow ? 'borrowed' : 'value',
                'passing' => $borrow ? $parameter['passing'] : 'direct', 'abi_indices' => [$index + $offset]];
            if ($borrow) {
                $parameters[$index]['borrow_scope'] = 'call';
                if (isset($parameter['source_payload'])) {
                    $parameters[$index]['payload_crossing'] = $parameter['source_payload'];
                }
            }
        }

        // The exact function pointer proves the native signature; physical value arguments may bind const references.
        $target = $operation['cpp_name'];
        if (($operation['cpp_template_arguments'] ?? []) !== []) {
            $target .= '<' . implode(', ', array_map(static fn($id) => $definitions->types[$id]['cpp_name'], $operation['cpp_template_arguments'])) . '>';
        }
        $physical_result = $owned ? 'void' : ($result_type['kind'] === 'address' ? 'void *' : $result);
        $declaration = 'extern "C" ' . $physical_result . ' ' . $contract['symbol'] . '(' . implode(', ', $declarations) . ') noexcept';
        $body = '        using target_function = ' . $result . ' (*)(' . implode(', ', $cpp_types) . ");\n";
        $body .= '        auto target = static_cast<target_function>(&' . $target . ");\n";
        foreach ($parameters as $index => $parameter) {
            if ($parameter['ownership'] === 'borrowed') {
                $body .= '        if (arg' . $index . ' == nullptr) { rp_failure("' . $operation['id'] . '", "null object"); }' . "\n";
            }
        }
        $body .= implode('', $copies);
        $call = 'target(' . implode(', ', $arguments) . ')';
        if ($owned)
        {
            // Native results initialize their own representation; source payloads use an explicit owned crossing.
            $body .= '        if (result == nullptr) { rp_failure("' . $operation['id'] . '", "null result storage"); }' . "\n";
            if (isset($result_type['source_payload']))
            {
                if (($operation['source_payload_result'] ?? null) !== 'copy_out') {
                    throw new \RuntimeException('Source payload result requires explicit copy-out');
                }
                $source = $project?->sources[$result_type['source_payload']] ?? throw new \RuntimeException('Missing source result contract');
                $body .= project\Adapter::copy_out($source, $call);
            }
            else {
                $body .= '        ::new (result) ' . $result . '(' . $call . ');';
            }
            $contract['storage_precondition'] = 'aligned_uninitialized_storage';
            $contract['storage_after'] = 'live_owned_object';
            $contract['result'] = ['type' => $operation['result_type'], 'ownership' => 'owned', 'passing' => 'caller_storage', 'abi_index' => 0];
            if (isset($result_type['source_payload'])) {
                $contract['result']['payload_crossing'] = 'copy_out';
            }
        }
        else {
            $body .= '        return ' . $call . ';';
            $contract['result'] = ['type' => $operation['result_type'], 'ownership' => 'value', 'passing' => 'direct'];
        }
        $contract['parameters'] = $parameters;
        $contract['cpp_parameters'] = $physical_types;
        $contract['cpp_return'] = $physical_result;
        return [$declaration, $body, $contract];
    }
}
