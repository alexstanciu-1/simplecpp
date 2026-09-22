<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Extract checked facts from Clang outputs; never infer layout from a type name. */
final class Metadata
{
    /** Read the actual module target and data layout emitted by Clang.
     * @return array{triple: string, data_layout: string} */
    public static function target(string $llvm): array
    {
        if ((!preg_match('/^target triple = "([^"]+)"$/m', $llvm, $triple))
            || (!preg_match('/^target datalayout = "([^"]+)"$/m', $llvm, $layout))) {
            throw new \RuntimeException('Missing LLVM target facts');
        }
        return ['triple' => $triple[1], 'data_layout' => $layout[1]];
    }

    /** Join measured layout and C++ traits with the declared semantic contract.
     * @param array<string, mixed> $type @return array<string, mixed> */
    public static function type(array $type, string $llvm, ?string $ast): array
    {
        if ($type['kind'] === 'void') {
            return $type + ['size_bytes' => 0, 'alignment_bytes' => 1];
        }
        $prefix = $type['fact_prefix'];
        $record = $type;
        $record['size_bytes'] = self::constant($llvm, Symbols::append($prefix, 'size'));
        $record['alignment_bytes'] = self::constant($llvm, Symbols::append($prefix, 'alignment'));
        if (($record['size_bytes'] < 1) || ($record['alignment_bytes'] < 1)) {
            throw new \RuntimeException('Invalid storage layout for ' . $type['id']);
        }
        foreach (['trivially_copyable', 'trivially_destructible', 'copy_constructible', 'move_constructible', 'copy_assignable'] as $trait) {
            $record['cpp_traits'][$trait] = self::constant($llvm, Symbols::append($prefix, $trait)) === 1;
        }
        if ($type['kind'] === 'integer') {
            $record['signed'] = self::constant($llvm, Symbols::append($prefix, 'signed')) === 1;
            $record['value_bits'] = self::constant($llvm, Symbols::append($prefix, 'value_bits'));
        }
        elseif (in_array($type['kind'], ['runtime_value', 'value_record'], true)) {
            $record['declaration'] = self::record_declaration($ast, $type['cpp_name']);
        }
        if ($type['kind'] === 'value_record') {
            $record = Record_Exposure::measure($record, $llvm);
        }
        return $record;
    }

    public static function constant(string $llvm, string $symbol): int
    {
        if (!preg_match('/^@' . preg_quote($symbol, '/') . ' = [^\n]*\bconstant i[0-9]+ ([0-9]+)\b/m', $llvm, $match)) {
            throw new \RuntimeException('Missing Clang layout constant: ' . $symbol);
        }
        return (int)$match[1];
    }

    /** Locate the exact qualified declaration in Clang AST output.
     * @return array<string, mixed> */
    private static function record_declaration(string $json, string $qualified): array
    {
        $name = substr($qualified, (int)strrpos('::' . $qualified, '::'));
        $matches = [];
        $aliases = [];
        foreach (self::json_roots($json) as $root)
        {
            if ((($root['kind'] ?? '') === 'TypeAliasDecl') && (($root['name'] ?? '') === $name)) {
                $aliases[] = $root;
            }
            if ((($root['kind'] ?? '') === 'CXXRecordDecl') && (($root['name'] ?? '') === $name)
                && ($root['completeDefinition'] ?? false)) {
                $matches[] = $root;
            }
        }
        // Opaque native specializations may have a named alias rather than a new record.
        // Layout/traits are still measured on the actual aliased C++ type.
        if (($matches === []) && (count($aliases) === 1)) {
            return ['name' => $qualified, 'kind' => 'alias', 'cpp_type' => $aliases[0]['type']['qualType'],
                'members' => [], 'has_bases' => false, 'field_offsets_exported' => false];
        }
        if (count($matches) !== 1) {
            throw new \RuntimeException('Expected one complete C++ record declaration for ' . $qualified);
        }
        $root = $matches[0];
        $access = ($root['tagUsed'] === 'struct') ? 'public' : 'private';
        $members = [];
        foreach ($root['inner'] ?? [] as $member)
        {
            if ($member['kind'] === 'AccessSpecDecl') {
                $access = $member['access'];
            }
            elseif (in_array($member['kind'], ['FieldDecl', 'CXXMethodDecl', 'CXXConstructorDecl', 'CXXDestructorDecl'], true)) {
                $members[] = ['kind' => $member['kind'], 'name' => $member['name'] ?? '',
                    'cpp_type' => $member['type']['qualType'], 'access' => $access,
                    'implicit' => $member['isImplicit'] ?? false,
                    'bit_field' => $member['isBitfield'] ?? false, 'initializer' => $member['hasInClassInitializer'] ?? false];
            }
        }
        return ['name' => $qualified, 'kind' => $root['tagUsed'], 'members' => $members,
            'has_bases' => ($root['bases'] ?? []) !== [], 'field_offsets_exported' => false];
    }

    /** Clang's filtered AST output can be a stream of JSON objects. @return list<array<string, mixed>> */
    private static function json_roots(string $text): array
    {
        $roots = [];
        $depth = 0;
        $start = 0;
        $quoted = false;
        $escaped = false;
        for ($i = 0, $length = strlen($text); $i < $length; ++$i)
        {
            $character = $text[$i];
            if ($quoted)
            {
                if ($escaped) {
                    $escaped = false;
                }
                elseif ($character === '\\') {
                    $escaped = true;
                }
                elseif ($character === '"') {
                    $quoted = false;
                }
                continue;
            }
            if (($depth === 0) && ctype_space($character)) {
                continue;
            }
            if (($depth === 0) && ($character !== '{')) {
                throw new \RuntimeException('Unexpected Clang AST JSON output');
            }
            if ($character === '"') {
                $quoted = true;
            }
            elseif ($character === '{') {
                if ($depth === 0) {
                    $start = $i;
                }
                ++$depth;
            }
            elseif ($character === '}') {
                --$depth;
                if ($depth === 0) {
                    $roots[] = json_decode(substr($text, $start, $i - $start + 1), true, 512, JSON_THROW_ON_ERROR);
                }
            }
        }
        if (($depth !== 0) || ($quoted)) {
            throw new \RuntimeException('Incomplete Clang AST JSON output');
        }
        return $roots;
    }

    /** Extract the supported LLVM signature and reject unrecognized ABI spellings.
     * @return array{return_type: string, parameters: list<array{type: string, attributes: string}>, return_attributes: string, definition: string} */
    public static function signature(string $llvm, string $symbol): array
    {
        if (preg_match_all('/^define ([^\n]+) @' . preg_quote($symbol, '/') . '\(([^\n]*)$/m', $llvm, $matches, PREG_SET_ORDER) !== 1) {
            throw new \RuntimeException('Missing or duplicate ABI implementation: ' . $symbol);
        }
        $prefix = $matches[0][1];
        if (!preg_match('/^(.*?)(void|i[0-9]+|ptr)$/', $prefix, $result)) {
            throw new \RuntimeException('Unsupported ABI return: ' . $symbol);
        }
        // Only direct scalar/pointer bridges are supported. Preserve attributes
        // rather than silently discarding ABI-relevant signext/zeroext facts.
        $tail = $matches[0][2];
        $depth = 1;
        $end = 0;
        for (; $end < strlen($tail); ++$end) {
            $depth += ($tail[$end] === '(') ? 1 : (($tail[$end] === ')') ? -1 : 0);
            if ($depth === 0) {
                break;
            }
        }
        if ($depth !== 0) {
            throw new \RuntimeException('Incomplete ABI signature');
        }
        $arguments = trim(substr($tail, 0, $end));
        $parameters = [];
        foreach (($arguments === '') ? [] : explode(',', $arguments) as $argument) {
            if (!preg_match('/^\s*(ptr|i[0-9]+)\s*(.*?)\s+%[A-Za-z0-9_.]+$/', $argument, $parameter)) {
                throw new \RuntimeException('Unsupported ABI parameter: ' . $argument);
            }
            $parameters[] = ['type' => $parameter[1], 'attributes' => trim($parameter[2])];
        }
        $return_attributes = trim(preg_replace('/\bdso_local\b/', '', $result[1]));
        if (($return_attributes !== '') && (!preg_match('/^(?:(?:noundef|signext|zeroext)\s*)+$/D', $return_attributes))) {
            throw new \RuntimeException('Unsupported return attributes or calling convention: ' . $prefix);
        }
        return ['return_type' => $result[2], 'parameters' => $parameters,
            'return_attributes' => $return_attributes, 'definition' => 'define ' . $prefix . ' @' . $symbol . '(' . $arguments . ')'];
    }

    /** Check measured physical arguments against the declared adaptation and attach its ABI.
     * @param array<string, mixed> $contract @param array<string, array<string, mixed>> $types @return array<string, mixed> */
    public static function operation(array $contract, array $types, string $llvm): array
    {
        $abi = self::signature($llvm, $contract['symbol']);
        $parameters = array_column($abi['parameters'], 'type');
        $kind = $contract['kind'];
        $result = $types[$contract['result']['type'] ?? ''] ?? null;
        $expected_return = in_array($kind, ['const_method', 'free_function'], true) && (($result['kind'] ?? null) === 'integer')
            ? 'i' . $result['value_bits'] : ((($result['kind'] ?? null) === 'address') ? 'ptr' : 'void');
        if (($abi['return_type'] !== $expected_return) || (($kind !== 'free_function') && (($parameters[0] ?? '') !== 'ptr'))) {
            throw new \RuntimeException('ABI disagrees with operation contract: ' . $contract['id']);
        }
        if (in_array($kind, ['free_function', 'construct'], true))
        {
            $expected = array_map(static fn(array $parameter): string => in_array($parameter['passing'], ['const_address', 'mutable_address'], true) ? 'ptr' : 'i' . $types[$parameter['type']]['value_bits'], $contract['parameters']);
            if (($contract['result']['passing'] ?? null) === 'caller_storage') {
                array_unshift($expected, 'ptr');
            }
            if ($parameters !== $expected) {
                throw new \RuntimeException('ABI disagrees with function value/address parameters');
            }
        }
        elseif (in_array($kind, ['copy_construct', 'move_construct'], true)) {
            $trait = $kind === 'move_construct' ? 'move_constructible' : 'copy_constructible';
            if (($parameters !== ['ptr', 'ptr']) || (!$types[$contract['type']]['cpp_traits'][$trait])) {
                throw new \RuntimeException('ABI or C++ capability disagrees with source construction');
            }
        }
        elseif ($kind === 'copy_assign') {
            if (($parameters !== ['ptr', 'ptr']) || (!$types[$contract['type']]['cpp_traits']['copy_assignable'])) {
                throw new \RuntimeException('ABI or C++ capability disagrees with copy assignment');
            }
        }
        elseif ($kind === 'construct_from_bytes') {
            if ((count($parameters) !== 3) || ($parameters[1] !== 'ptr') || (!preg_match('/^i[0-9]+$/', $parameters[2]))) {
                throw new \RuntimeException('Unsupported byte span ABI');
            }
            $contract['parameters'][0]['length_abi_type'] = $parameters[2];
            $contract['parameters'][0]['length_signed'] = false; // std::size_t adapter contract.
        }
        elseif (count($parameters) !== 1) {
            throw new \RuntimeException('Unexpected object operation ABI arity');
        }
        if (isset($contract['abi']) && (self::abi_shape($contract['abi']) !== self::abi_shape($abi))) {
            throw new \RuntimeException('ABI variant mismatch for ' . $contract['id']);
        }
        $contract['abi'] = $abi;
        return $contract;
    }

    /** Normalize the supported signature positions for agreement across module variants.
     * @param array<string, mixed> $abi @return list<array{string, list<string>}> */
    private static function abi_shape(array $abi): array
    {
        $parts = [['type' => $abi['return_type'], 'attributes' => $abi['return_attributes']], ...$abi['parameters']];
        $shape = [];
        foreach ($parts as $part)
        {
            if (preg_match('/\b(?:byval|byref|sret|inalloca|preallocated|inreg|nest|swiftself|swifterror)\b/', $part['attributes'])) {
                throw new \RuntimeException('Unsupported non-direct ABI passing attribute');
            }
            preg_match_all('/\b(?:signext|zeroext)\b/', $part['attributes'], $attributes);
            sort($attributes[0]);
            $shape[] = [$part['type'], $attributes[0]];
        }
        return $shape;
    }
}
