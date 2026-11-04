<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Compute exact decimal boundaries during generation without host overflow or a big-integer dependency. */
function decimalPowerOfTwo(int $exponent): string {
    $value = '1';
    for ($power = 0; $power < $exponent; ++$power) {
        $carry = 0;
        $next = '';
        for ($i = strlen($value) - 1; $i >= 0; --$i) {
            $digit = ((int)$value[$i]) * 2 + $carry;
            $next = (string)($digit % 10) . $next;
            $carry = intdiv($digit, 10);
        }
        $value = ($carry === 0 ? '' : (string)$carry) . $next;
    }
    return $value;
}

/** Subtract one from a positive decimal magnitude; used for the signed maximum. */
function decimalPredecessor(string $value): string {
    for ($i = strlen($value) - 1; $i >= 0; --$i) {
        if ($value[$i] !== '0') {
            $value[$i] = (string)((int)$value[$i] - 1);
            return ltrim($value, '0') ?: '0';
        }
        $value[$i] = '9';
    }
    throw new \LogicException('Expected positive decimal magnitude');
}

/** Generate only the int/int64 slice; module membership determines availability, never per-type flags. */
function renderCompiler(mixed $metadata, array $facts): array {
    $types = registry($metadata, $facts);
    $compiler = at($metadata, 'compiler');
    fields($compiler, ['modules', 'integer_literals']);
    $active = [];
    foreach (object(at($compiler, 'modules')) as $module => $row) {
        fields($row, ['always_available', 'types']);
        $members = names(at($row, 'types'), 'Duplicate compiler module type');
        if (boolean(at($row, 'always_available'))) {
            requireThat($module === 'default-runtime', 'Only the default runtime is supported in this compiler slice');
            $active = array_merge($active, $members);
        }
    }
    requireThat($active === ['int64'] && isset($types['int64']), 'Compiler lowering requires int64 metadata in the default runtime');
    $name = $active[0];
    $type = $types[$name];
    requireThat($type['signed'] && $type['bits'] === 64, 'Compiler slice requires signed int64');
    $sources = fields(at($metadata, 'source_types'), ['int', 'bool', 'null', 'float']);
    requireThat(($sources['int'] ?? null) === $name, 'Compiler int requires int64 metadata');
    $aliases = [$name];
    foreach ($sources as $alias => $identity) {
        if ($identity === $name) $aliases[] = $alias;
    }
    $aliases = array_values(array_unique($aliases));
    requireThat($aliases === ['int64', 'int'], 'Compiler slice registers only int/int64 aliases');
    $policy = at($compiler, 'integer_literals');
    fields($policy, ['radix', 'overflow', 'cpp_suffix']);
    requireThat(unsigned(at($policy, 'radix')) === 10, 'Unsupported compiler integer radix');
    requireThat(textValue(at($policy, 'overflow')) === 'reject', 'Unsupported compiler integer overflow policy');
    $suffix = textValue(at($policy, 'cpp_suffix'));
    requireThat($suffix === 'LL', 'Unsupported signed integer literal suffix');
    requireThat($facts['native_integers']['long long']['signed'] && $facts['native_integers']['long long']['digits'] >= $type['bits'] - 1,
        'Native literal carrier cannot represent compiler integer');
    $magnitude = decimalPowerOfTwo($type['bits'] - 1);
    $maximum = decimalPredecessor($magnitude);
    $minimumCpp = '(-' . $maximum . $suffix . ' - 1' . $suffix . ')';
    $accept = implode(' || ', array_map(static fn(string $alias): string => '($name === ' . var_export($alias, true) . ')', $aliases));
    $source = strtr(readFile(__DIR__ . '/../templates/signed_integer.php.tpl'), [
        '@name@' => $name, '@maximum@' => $maximum, '@magnitude@' => $magnitude,
        '@accept_names@' => $accept, '@representation@' => var_export($type['representation'], true),
        '@cpp_minimum@' => var_export($minimumCpp, true), '@cpp_suffix@' => var_export($suffix, true),
    ]);
    $cases = implode(', ', array_map(static fn(string $alias): string => var_export($alias, true), $aliases));
    $registration = strtr(readFile(__DIR__ . '/../templates/type_registry.php.tpl'), ['@name@' => $name, '@cases@' => $cases]);
    $files = ['types/' . $name . '.php' => $source, 'types/registry.php' => $registration];
    $files['inventory.json'] = encode(['generator' => 'scpp-compiler-lowering', 'version' => 3,
        'files' => array_keys($files)]) . "\n";
    return $files;
}
