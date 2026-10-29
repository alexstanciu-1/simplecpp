<?php
declare(strict_types=1);
namespace Scpp\Api;

function fingerprint(mixed $value): string {
    $text = textValue($value);
    requireThat(preg_match('/^[0-9a-f]{64}$/D', $text) === 1, 'Invalid context fingerprint');
    return $text;
}

function layout(mixed $record): array {
    $size = unsigned(at($record, 'size'));
    $alignment = unsigned(at($record, 'alignment'));
    requireThat($size > 0 && $size <= 4096 && $alignment > 0 && ($alignment & ($alignment - 1)) === 0 && $alignment <= $size && $size % $alignment === 0, 'Invalid native size/alignment');
    return compact('size', 'alignment');
}

function targetFacts(mixed $facts, mixed $context): array {
    fields($context, ['schema', 'context_id', 'configuration']);
    requireThat(unsigned(at($context, 'schema')) === 1, 'Unsupported target context schema');
    $id = fingerprint(at($context, 'context_id'));
    $config = at($context, 'configuration');
    fields($config, ['target', 'compiler', 'compiler_version', 'compiler_sha256', 'flags', 'probe_sha256', 'preprocessed_sha256', 'driver_sha256']);
    $target = textValue(at($config, 'target'));
    requireThat(str_starts_with($target, 'x86_64-') && str_contains($target, 'linux'), 'Unsupported target: native x86_64 Linux required');
    foreach (['compiler', 'compiler_version'] as $key) {
        requireThat(textValue(at($config, $key)) !== '', 'Missing compiler identity');
    }
    foreach (['compiler_sha256', 'probe_sha256', 'preprocessed_sha256', 'driver_sha256'] as $key) {
        fingerprint(at($config, $key));
    }
    $flags = listValue(at($config, 'flags'));
    requireThat($flags !== [], 'Missing C++ build flags');
    foreach ($flags as $flag) {
        textValue($flag);
    }
    fields($facts, ['schema', 'context_id', 'char_bits', 'pointer_bytes', 'standard_library', 'native_integers', 'fixed_integers', 'double', 'has_int128', 'ownership', 'boxed']);
    requireThat(unsigned(at($facts, 'schema')) === 1, 'Unsupported target facts schema');
    requireThat(fingerprint(at($facts, 'context_id')) === $id, 'Target context mismatch: stale or incompatible facts');
    requireThat(unsigned(at($facts, 'char_bits')) === 8 && unsigned(at($facts, 'pointer_bytes')) === 8, 'Unsupported native byte/pointer representation');
    $standard_library = textValue(at($facts, 'standard_library'));
    requireThat(str_starts_with($standard_library, 'libc++:') || str_starts_with($standard_library, 'libstdc++:'), 'Unsupported C++ standard library');
    $out = compact('id', 'target', 'standard_library');
    foreach (['native_integers' => ['int', 'unsigned int', 'long', 'unsigned long', 'long long', 'unsigned long long'], 'fixed_integers' => ['int8', 'uint8', 'int16', 'uint16', 'int32', 'uint32', 'int64', 'uint64']] as $key => $names) {
        $rows = at($facts, $key);
        fields($rows, $names);
        $out[$key] = [];
        foreach ($names as $name) {
            $row = at($rows, $name);
            fields($row, ['signed', 'digits', 'size', 'alignment']);
            $item = layout($row);
            $item['signed'] = boolean(at($row, 'signed'));
            $item['digits'] = unsigned(at($row, 'digits'));
            requireThat($item['digits'] > 0 && $item['digits'] <= 64 && $item['digits'] + (int)$item['signed'] <= $item['size'] * 8, 'Invalid native integer range');
            if ($key === 'native_integers') {
                requireThat($item['signed'] !== str_starts_with($name, 'unsigned'), 'Native integer signedness mismatch');
            }
            else {
                $bits = (int)preg_replace('/\D/', '', $name);
                $signed = $name[0] === 'i';
                requireThat($item['signed'] === $signed && $item['digits'] === $bits - (int)$signed && $item['size'] * 8 === $bits, 'Fixed integer representation mismatch');
            }
            $out[$key][$name] = $item;
        }
        ksort($out[$key], SORT_STRING);
    }
    $double = at($facts, 'double');
    fields($double, ['iec559', 'radix', 'digits', 'max_exponent', 'size', 'alignment']);
    requireThat(boolean(at($double, 'iec559')) && unsigned(at($double, 'radix')) === 2 && unsigned(at($double, 'digits')) === 53 && unsigned(at($double, 'max_exponent')) === 1024 && layout($double)['size'] === 8, 'float64 requires IEEE binary64 double');
    $owners = at($facts, 'ownership');
    fields($owners, ['shared', 'weak', 'unique']);
    foreach (['shared', 'weak', 'unique'] as $family) {
        $row = at($owners, $family);
        fields($row, ['size', 'alignment']);
        $out['ownership'][$family] = layout($row);
    }
    if (property_exists($facts, 'boxed')) {
        fields($facts->boxed, ['mixed', 'dynamic']);
        foreach (['mixed', 'dynamic'] as $family) {
            $row = at($facts->boxed, $family);
            fields($row, ['size', 'alignment']);
            $out['boxed'][$family] = layout($row);
        }
    }
    $out['has_int128'] = boolean(at($facts, 'has_int128'));
    return $out;
}
