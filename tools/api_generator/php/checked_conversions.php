<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Native identity for a published scalar; independent of public export spelling. */
function checkedConversionRepresentation(array $plan, array $facts, string $identity): array {
    if ($identity === 'string' && isset($plan['string'])) return ['cpp' => 'const string&', 'native_name' => null];
    foreach ($plan['types'] as $type) {
        if ($type['name'] === $identity) {
            return ['cpp' => $type['representation'], 'native_name' => $facts['fixed_integers'][$identity]['native_name']];
        }
    }
    foreach ($plan['values'] as $type) {
        if ($type['name'] !== $identity) continue;
        return match ($type['kind']) {
            'boolean' => ['cpp' => 'bool', 'native_name' => 'bool'],
            'floating' => ['cpp' => 'double', 'native_name' => 'double'],
            default => throw new \RuntimeException('Checked conversion requires a numeric scalar'),
        };
    }
    throw new \RuntimeException('Checked conversion references unpublished scalar');
}

/** Publish named native entry points for existing canonical numeric conversions, without new admissions. */
function prepareCheckedConversions(mixed $metadata, array $facts, array &$plan): void {
    if (!isset($metadata->checked_conversions)) return;
    $policy = $metadata->checked_conversions;
    fields($policy, ['provider', 'symbol_prefix']);
    $provider = textValue(at($policy, 'provider'));
    $providers = $plan['submodules'];
    $header = providerHeader($providers, $provider);
    requireThat(!in_array($header, array_values($plan['headers']), true), 'Conflicting checked conversion header');
    $prefix = textValue(at($policy, 'symbol_prefix'));
    requireThat(preg_match('/^[A-Za-z][A-Za-z0-9_]*$/D', $prefix) === 1, 'Invalid checked conversion symbol prefix');
    $representations = [];
    $functions = [];
    foreach ($plan['conversions'] as $conversion) {
        if ($conversion['native_source'] || $conversion['target'] === 'string') continue;
        $source = $conversion['source'];
        $target = $conversion['target'];
        $implementation = $conversion['implementation'];
        requireThat(in_array($implementation, ['checked_integer_cast', 'floating_integer_cast', 'numeric_cast', 'truth_cast', 'parse_decimal', 'parse_boolean_text'], true), 'Unsupported named conversion implementation');
        foreach ([$source, $target] as $identity) {
            $representations[$identity] ??= checkedConversionRepresentation($plan, $facts, $identity);
        }
        $symbol = $prefix . $source . '_to_' . $target;
        requireThat(!isset($functions[$symbol]), 'Duplicate checked conversion symbol');
        $functions[$symbol] = ['source' => $source, 'target' => $target, 'symbol' => $symbol,
            'implementation' => $implementation, 'can_fail' => $conversion['can_fail'], 'lossless' => $conversion['lossless']];
    }
    ksort($representations, SORT_STRING);
    ksort($functions, SORT_STRING);
    $requirements = exportProviders($providers, array_values(array_diff(array_keys($representations), ['string'])));
    $plan['checked_conversions'] = ['provider' => $provider, 'requires' => $requirements, 'header' => $header, 'representations' => $representations, 'functions' => array_values($functions)];
}
