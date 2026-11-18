<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/concrete_type.php';

/** Instantiate a generator-owned recipe from already resolved native types, without interpreting C++ templates. */
function concreteTemplate(string $name, array $types, array $values, string $namespace = ''): array {
    static $recipes = [];
    $recipe = $recipes[$name] ??= decode(readFile(__DIR__ . '/../templates/' . $name . '.concrete.json'), 'concrete template');
    fields($recipe, ['parameters', 'fragments']);
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
    requireThat(!array_key_exists('type_contracts', $values), 'Concrete type contracts are generated');
    $values['type_contracts'] = $contracts;
    $result = [];
    foreach (object(at($recipe, 'fragments')) as $section => $fragments) {
        $result[$section] = '';
        foreach (listValue($fragments) as $fragment) $result[$section] .= template(textValue($fragment), $values);
    }
    return $result;
}
