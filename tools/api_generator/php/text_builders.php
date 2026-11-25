<?php
declare(strict_types=1);
namespace Scpp\Api;

function prepareTextBuilders(mixed $metadata, mixed $publication, array &$plan): void {
    foreach (names($publication->text_builders ?? []) as $name) {
        requireThat(in_array($name, ['string_parts_builder', 'text_builder'], true), 'Unknown text builder');
        $operations = [];
        foreach (object(at(at($metadata, 'text_builders'), $name)) as $method => $signature) {
            $parameters = listValue($signature);
            $mode = array_shift($parameters);
            $result = array_shift($parameters);
            requireThat(in_array($mode, ['create', 'write', 'read'], true), 'Invalid builder passing mode');
            foreach ([$result, ...$parameters] as $type) {
                if ($type !== $name && $type !== 'void') moduleFunctionTypeExport($plan, $type);
            }
            $operations[$method] = compact('mode', 'result', 'parameters');
        }
        $plan['text_builders'][$name] = ['header' => textHelperHeader($name), 'operations' => $operations];
    }
}

function compilerBuilderCases(array $builders, array $types): array {
    $cases = [];
    foreach ($builders as $type => $builder) {
        foreach ($builder['operations'] as $method => $op) {
            $name = $type . '_' . $method;
            $create = $op['mode'] === 'create';
            $parameters = $create ? [] : [$type, ...$op['parameters']];
            $args = array_map(static fn($t) => "type_{$types[$t]}::instance()", $parameters);
            $result = $op['result'] === 'void' ? 'void' : $types[$op['result']];
            $symbol = $create ? "::scpp::$type" : $method;
            $write = (int)($op['mode'] === 'write');
            $read = (int)($op['mode'] === 'read') | (in_array('string', $op['parameters'], true) ? 2 : 0);
            $member = $create ? 'false' : 'true';
            $cases[] = "\t\t\t'general:func:$name' => \$bindings[\$key] ??= new runtime_function('$name', ["
                . implode(', ', $args) . "], type_$result::instance(), '$symbol', '{$builder['header']}', reference_mask: $write, "
                . "const_reference_mask: $read, receiver_method: $member, retains_receiver: false, exact_arguments: true),";
        }
    }
    return $cases;
}

function renderTextBuilders(array $plan, array &$files): void {
    foreach ($plan['text_builders'] ?? [] as $type => $builder) {
        $methods = '';
        foreach ($builder['operations'] as $method => $op) {
            if ($op['mode'] === 'create') continue;
            $args = array_map(static fn($t) => $t === 'string' ? 'const string&' : $t, $op['parameters']);
            $const = $op['mode'] === 'read' ? ' const' : '';
            $methods .= "    {$op['result']} $method(" . implode(', ', $args) . ")$const;\n";
        }
        $files['public/' . $builder['header']] = moduleHeaderBody([$plan['string']['header']], 'scpp',
            template('strings/builder_type', ['type' => $type, 'methods' => $methods]));
        $files['src/runtime.cpp'] .= includeHeader($builder['header']) . "#include <charconv>\n#include <vector>\n#include <utility>\nnamespace scpp {\n"
            . template("strings/$type") . template('strings/builder_value', ['type' => $type]) . "}\n";
    }
}
