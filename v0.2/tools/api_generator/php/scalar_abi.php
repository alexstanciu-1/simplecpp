<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Exact overload identity; library-backed types retain their own identity. */
function scalarNativeIdentity(array $plan, string $type): string {
    $type = trim(str_replace(['const ', '&'], '', $type));
    foreach ($plan['types'] as $row) if ($row['name'] === $type) return $row['native_name'];
    return match ($type) { 'bool_t' => 'bool', 'float64' => 'double', default => $type };
}

/** Reject unintended builtin inputs at concrete API boundaries without public templates. */
function scalarInputFences(array $plan, string $name, array $signatures, bool $constructor = false, string $qualifier = ''): string {
    $allowed = [];
    foreach ($signatures as $arguments) {
        $allowed[] = array_map(static fn(string $type): string => scalarNativeIdentity($plan, $type), $arguments);
    }
    $deleted = [];
    foreach ($signatures as $arguments) {
        foreach ($arguments as $position => $type) {
            $identity = scalarNativeIdentity($plan, $type);
            if (!in_array($identity, NATIVES, true)) continue;
            // Preserve already-native API parameters; fence scalar aliases whose wrappers enforced exact input.
            if ($identity === trim(str_replace(['const ', '&'], '', $type))) continue;
            foreach (array_merge(NATIVES, ['decltype(nullptr)', 'const void*', '__int128', 'unsigned __int128']) as $other) {
                $candidate = $arguments;
                $candidate[$position] = $other;
                $key = array_map(static fn(string $type): string => scalarNativeIdentity($plan, $type), $candidate);
                if (in_array($key, $allowed, true)) continue;
                $deleted[implode(',', $key)] = ($constructor ? 'explicit ' : 'void ') . $name . '(' . implode(', ', $candidate) . ')' . $qualifier . ' = delete;';
            }
        }
    }
    ksort($deleted, SORT_STRING);
    $out = '';
    foreach ($deleted as $declaration) {
        $wide = str_contains($declaration, '__int128');
        $out .= ($wide ? "#ifdef __SIZEOF_INT128__\n" : '') . $declaration . "\n" . ($wide ? "#endif\n" : '');
    }
    return $out;
}

/** Native adapters consume the same validated recipes as named canonical conversion functions. */
function nativeScalarConversion(array $plan, string $target, string $source, string $expression): string {
    if ($target === 'string' && $source === 'const char*') return "string{" . $expression . "}";
    foreach ($plan['conversions'] as $conversion) {
        if ($conversion['native_source'] && $conversion['target'] === $target && $conversion['source'] === $source) {
            return "detail::{$conversion['implementation']}<$target>($expression)";
        }
    }
    throw new \RuntimeException('Native adapter lacks a validated conversion');
}
