<?php
declare(strict_types=1);
namespace Scpp\Api;

function prepareQueryBuilder(object $publication, array &$plan): void {
    $policy = $publication->boxed->query_builder ?? null;
    if ($policy === null) return;
    fields($policy, ['name', 'vector_elements', 'hash_keys', 'hash_values', 'constants']);
    $name = textValue(at($policy, 'name'));
    requireThat($name === 'http_build_query', 'Unknown query builder');
    requireThat(isset($plan['string_functions']['urlencode'], $plan['string_functions']['rawurlencode']), 'Query builder requires both URL encoders');
    $elements = names(at($policy, 'vector_elements'));
    $keys = names(at($policy, 'hash_keys'));
    $values = names(at($policy, 'hash_values'));
    $inputs = ['mixed' => []];
    foreach ($plan['vectors'] ?? [] as $v) {
        if (in_array($v['element'], $elements, true)) $inputs[$v['name']] = ['int64', $v['element']];
    }
    foreach ($plan['hashes']['specializations'] ?? [] as $h) {
        if (in_array($h['key'], $keys, true) && in_array($h['value'], $values, true)) {
            $inputs[$h['name']] = [$h['key'], $h['value']];
        }
    }
    $includes = [];
    foreach ([...array_keys($inputs), 'nullable_string'] as $type) {
        $includes[] = providerHeader($plan['submodules'], moduleFunctionTypeExport($plan, $type)['provider']);
    }
    $constants = object(at($policy, 'constants'));
    requireThat(($constants['PHP_QUERY_RFC1738'] ?? null) === 1 && ($constants['PHP_QUERY_RFC3986'] ?? null) === 2,
        'Invalid PHP query encoding constants');
    $plan['php_integer_constants'] = ($plan['php_integer_constants'] ?? []) + $constants;
    $plan['query_builder'] = compact('name', 'inputs', 'includes') + ['header' => "scpp/php/$name.hpp"];
}

function compilerQueryBuilder(array $builder, array $types): string {
    $name = $builder['name'];
    $variants = [];
    foreach ($builder['inputs'] as $input => $_) {
        $type = 'type_' . $types[$input];
        $variants[] = "$type::class => new runtime_function('$name', [$type::instance(), type_string::instance(), type_nullable_string::instance(), type_int64::instance()], type_string::instance(), '::scpp::php::$name', '{$builder['header']}', required_parameters: 1, const_reference_mask: 7)";
    }
    return "\t\t\t'php:func:$name' => \$bindings[\$key] ??= new runtime_overloads(array_fill_keys([1, 2, 3, 4], [" . implode(', ', $variants) . "]), list_context: type_mixed::instance()),";
}

function renderQueryBuilder(array $plan, array &$files): void {
    $builder = $plan['query_builder'] ?? null;
    if ($builder === null) return;
    $name = $builder['name'];
    $declarations = $definitions = '';
    $forward = "class nullable_string;\n";
    foreach ($builder['inputs'] as $input => $shape) {
        $forward .= "class $input;\n";
        $args = ["const $input& data", 'const string& prefix', 'const nullable_string& separator', 'int64 mode'];
        for ($arity = 1; $arity <= count($args); ++$arity) {
            $signature = "string $name(" . implode(', ', array_slice($args, 0, $arity)) . ')';
            $declarations .= "[[nodiscard]] $signature;\n";
            if ($arity < 4) {
                $call = implode(', ', array_slice(['data', 'prefix', 'separator'], 0, $arity));
                $defaults = implode(', ', array_slice(['{}', '{}', '1'], $arity - 1));
                $definitions .= "$signature { return $name($call, $defaults); }\n";
            } else {
                $emit = $shape ? 'typed<' . implode(', ', $shape) . '>' : 'table';
                $definitions .= "$signature { query_writer writer(prefix, separator, mode); writer.$emit(data); return writer.finish(); }\n";
            }
        }
    }
    $files['public/' . $builder['header']] = BANNER . "#pragma once\n" . includeHeader(providerHeader($plan['submodules'], typeExport($plan['submodules'], 'string')['provider']))
        . "namespace scpp {\n$forward" . "namespace php {\n$declarations}\n}\n";
    $includes = array_unique([$builder['header'], ...$builder['includes'], $plan['string_functions']['urlencode']['header'], 'query_writer.hpp']);
    $files["src/$name.cpp"] = BANNER . implode('', array_map(__NAMESPACE__ . '\\includeHeader', $includes)) . "namespace scpp::php {\n$definitions}\n";
}
