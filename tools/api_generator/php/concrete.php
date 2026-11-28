<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/concrete_type.php';

/** Instantiate a generator-owned recipe from already resolved native types, without interpreting C++ templates. */
function concreteTemplate(string $name, array $types, array $values, string $namespace = ''): array {
    $recipe = concreteRecipe($name);
    $parameters = object(at($recipe, 'parameters'));
    requireThat(array_diff_key($types, $parameters) === [] && array_diff_key($parameters, $types) === [], "Concrete type arguments differ from $name parameters");
    $contracts = '';
    $aliases = [];
    foreach ($parameters as $parameter => $contract) {
        fields($contract, ['alias', 'requires']);
        $alias = textValue(at($contract, 'alias'));
        requireThat(!isset($aliases[$alias]), 'Duplicate concrete type alias');
        $aliases[$alias] = true;
        requireThat((bool)preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/D', $alias), 'Invalid concrete type alias');
        $type = $types[$parameter];
        requireThat($type instanceof ConcreteType, 'Concrete type must implement ConcreteType');
        $spelling = $type->cppType($namespace);
        requireThat($type->identity() !== '' && $spelling !== '', 'Concrete type must have an identity and resolved C++ spelling');
        requireThat(!array_key_exists($parameter, $values), 'Concrete type argument shadows a value argument');
        $values[$parameter] = $spelling;
        $contracts .= "using $alias = $spelling;\n";
        foreach (names(at($contract, 'requires')) as $requirement) {
            requireThat($type->supports($requirement) !== false, "Concrete type {$type->identity()} does not support $requirement");
            if ($requirement === 'nothrow_destructible') $contracts .= "static $alias& {$alias}_contract_value() noexcept;\n";
            $expression = match ($requirement) {
                'copy_constructible' => "__is_constructible($alias, const $alias&)",
                'nothrow_move_constructible' => "__is_nothrow_constructible($alias, $alias&&)",
                'nothrow_move_assignable' => "__is_nothrow_assignable($alias&, $alias&&)",
                'nothrow_destructible' => "noexcept({$alias}_contract_value().~$alias())",
                default => throw new \RuntimeException("Unknown concrete requirement: $requirement"),
            };
            $contracts .= "static_assert($expression, \"$name requires $parameter.$requirement\");\n";
        }
    }
    if (isset($recipe->hash_key)) {
        $key = $types[textValue($recipe->hash_key)] ?? null;
        requireThat($key instanceof ConcreteHashKey, 'Concrete hash requires a key contract');
        $hash = $key->hashExpression('key');
        $equal = $key->equalityExpression('left', 'right');
        requireThat($hash !== null && $equal !== null, 'Concrete type is not hashable');
        requireThat(!array_key_exists('hash', $values) && !array_key_exists('equal', $values), 'Key operations belong to the type contract');
        $values += compact('hash', 'equal');
    }
    requireThat(!array_key_exists('type_contracts', $values), 'Concrete type contracts are generated');
    if (isset($recipe->checked_slot)) $values['checked_slot'] = checkedSlotMethod($recipe);
    $values['type_contracts'] = $contracts;
    $result = [];
    foreach (object(at($recipe, 'fragments')) as $section => $fragments) {
        $result[$section] = '';
        foreach (listValue($fragments) as $fragment) {
            if (is_object($fragment)) {
                fields($fragment, ['template', 'when']);
                $condition = textValue(at($fragment, 'when'));
                requireThat(array_key_exists($condition, $values), 'Unknown concrete fragment condition');
                requireThat(in_array($values[$condition], ['', '1'], true), 'Concrete fragment condition must be empty or 1');
                if ($values[$condition] === '') continue;
                $fragment = at($fragment, 'template');
            }
            $result[$section] .= template(textValue($fragment), $values);
        }
    }
    return $result;
}

/** Read immutable generator-owned recipe data once; callers supply only settled type contracts. */
function concreteRecipe(string $name): mixed {
    static $recipes = [];
    $recipe = $recipes[$name] ??= decode(readFile(__DIR__ . '/../templates/' . $name . '.concrete.json'), 'concrete template');
    fields($recipe, ['parameters', 'fragments', 'application', 'support', 'includes', 'hash_key', 'destination_append', 'checked_slot']);
    return $recipe;
}

/** Checked slots borrow an existing element from a mutable lvalue for one immediate access. */
function checkedSlotMethod(object $recipe): string {
    $slot = at($recipe, 'checked_slot');
    fields($slot, ['method', 'key', 'value', 'missing']);
    $method = textValue(at($slot, 'method'));
    requireThat(recordIdentifier($method), 'Invalid checked slot member');
    $key = $recipe->hash_key ?? 'index';
    $valueParameters = array_diff(array_keys(object($recipe->parameters)), [$key]);
    requireThat(at($slot, 'key') === $key && array_values($valueParameters) === [at($slot, 'value')],
        'Invalid checked slot associated types');
    requireThat(at($slot, 'missing') === ($key === 'index' ? 'sequence_index_out_of_range' : 'hash_missing_key'),
        'Invalid checked slot failure');
    return $method;
}

/** Package a concrete application definition without family-specific compiler assembly. */
function concreteApplication(string $name, array $types, array $values, string $namespace): string {
    $recipe = concreteRecipe($name);
    $parts = concreteTemplate($name, $types, $values, $namespace);
    foreach ($parts as $section => $text) $values['section_' . $section] = $text;
    $values['destination_friend'] = isset($recipe->destination_append) ? 'friend struct destination_' . $values['n'] . ';' : '';
    return template(textValue(at($recipe, 'application')), $values);
}

/** Reuse runtime helper declarations/source without exposing private include paths to applications. */
function concreteSupport(string $name): string {
    $text = '';
    foreach (listValue(concreteRecipe($name)->includes ?? []) as $header) $text .= includeHeader(concreteSupportPath($header));
    foreach (listValue(at(concreteRecipe($name), 'support')) as $path) {
        $path = concreteSupportPath($path);
        $guard = 'SCPP_CONCRETE_SUPPORT_' . strtoupper(hash('sha256', $path));
        $source = readFile(__DIR__ . '/../../../runtime/src/' . $path);
        $text .= "#ifndef $guard\n#define $guard\n" . str_replace('#pragma once', '', $source) . "\n#endif\n";
    }
    return $text;
}

/** Generator-owned support paths are relative runtime sources or standard headers, not public provider paths. */
function concreteSupportPath(mixed $value): string {
    $path = textValue($value);
    requireThat(preg_match('~^[a-z][a-z0-9_/]*(?:\.hpp)?$~D', $path) === 1, 'Invalid concrete support path');
    return $path;
}
