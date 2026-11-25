<?php
declare(strict_types=1);
namespace Scpp\Api;

function cliHelperHeader(string $name): string {
    requireThat(in_array($name, ['argument_count', 'arguments', 'mixed_arguments', 'environment'], true), 'Unknown CLI operation');
    return "scpp/cli/$name.hpp";
}

function prepareCliHelpers(mixed $publication, array &$plan): void {
    $published = [];
    foreach (object($publication->cli_helpers ?? (object)[]) as $name => $op) {
        $header = cliHelperHeader($name);
        fields($op, ['result', 'parameters', 'general', 'php']);
        $result = textValue(at($op, 'result'));
        $parameters = listValue($op->parameters ?? []);
        $signature = match ($name) {
            'argument_count' => ['int64'], 'arguments' => ['vector_string'],
            'mixed_arguments' => ['mixed'], 'environment' => ['result_or_false_string', 'string'],
        };
        requireThat([$result, ...$parameters] === $signature, "Invalid native signature for $name");
        checkHeader($plan, $header);
        $includes = [];
        foreach ([$result, ...$parameters] as $type) {
            $export = moduleFunctionTypeExport($plan, $type);
            $includes[] = providerHeader($plan['submodules'], $export['provider']);
        }
        $keys = [];
        foreach (['general', 'php'] as $surface) {
            foreach (names($op->$surface ?? []) as $alias) {
                requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $alias) === 1, 'Invalid CLI source name');
                $key = "$surface:func:$alias";
                requireThat(!isset($published[$key]), "Duplicate CLI binding $key");
                $keys[] = $key;
                $published[$key] = true;
            }
        }
        requireThat($keys !== [], "Missing CLI binding for $name");
        $plan['cli_helpers'][$name] = compact('header', 'result', 'parameters', 'includes', 'keys');
    }
}

function compilerCliCases(array $runtime, array $types): array {
    $cases = [];
    foreach ($runtime['cli_helpers'] ?? [] as $name => $op) {
        $inputs = array_map(static fn($t) => "type_{$types[$t]}::instance()", $op['parameters']);
        foreach ($op['keys'] as $key) {
            $alias = substr($key, strrpos($key, ':') + 1);
            $cases[] = "\t\t\t'$key' => \$bindings[\$key] ??= new runtime_function('$alias', [" . implode(', ', $inputs)
                . "], type_{$types[$op['result']]}::instance(), '::scpp::cli::$name', '{$op['header']}', const_reference_mask: "
                . ((1 << count($inputs)) - 1) . ', exact_arguments: true),';
        }
    }
    return $cases;
}

function cliStartup(array $plan): bool {
    return array_diff(array_keys($plan['cli_helpers'] ?? []), ['environment']) !== [];
}

function renderCliHelpers(array $plan, array &$files): void {
    $ops = $plan['cli_helpers'] ?? [];
    if (cliStartup($plan)) {
        $files['private/cli_context.hpp'] = BANNER . template('cli/context');
        $files['src/runtime.cpp'] .= includeHeader('cli_context.hpp') . template('cli/startup');
    }
    foreach ($ops as $name => $op) {
        $args = array_map(static fn($t) => "const $t& name", $op['parameters']);
        $signature = $op['result'] . " $name(" . implode(', ', $args) . ')';
        $files['public/' . $op['header']] = moduleHeaderBody($op['includes'], 'scpp::cli', "$signature;\n");
        $unit = $name === 'mixed_arguments' ? 'src/boxed.cpp' : 'src/runtime.cpp';
        $files[$unit] .= includeHeader($op['header']);
        if ($name === 'mixed_arguments') $files[$unit] .= includeHeader('cli_context.hpp');
        if ($name === 'environment') $files[$unit] .= "#include <cstdlib>\n#include <cstring>\n#include <string>\n";
        $reserve = $name === 'arguments' && array_column($plan['vectors'], 'enable_reserve', 'name')['vector_string'] === '1'
            ? '    values.reserve(static_cast<int64>(detail::invocation.count));' . "\n" : '';
        $files[$unit] .= "namespace scpp::cli {\n$signature {\n" . template("cli/$name", ['reserve' => $reserve]) . "}\n}\n";
    }
}
