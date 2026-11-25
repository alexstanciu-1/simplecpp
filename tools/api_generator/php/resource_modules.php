<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Resolve a whole optional module's type, carrier and operation declarations before either backend renders them. */
function prepareResourceModules(object $publication, array &$plan): void {
    $catalogs = object($publication->resource_modules ?? (object)[]);
    requireThat(!isset($catalogs['filesystem']), 'Filesystem resources belong to the filesystem publication');
    if (isset($publication->filesystem->resources)) $catalogs['filesystem'] = $publication->filesystem->resources;
    foreach ($catalogs as $module => $catalog) {
        requireThat(in_array($module, ['io', 'process', 'curl', 'mysqli', 'ui', 'webview', 'fcgi', 'filesystem'], true), 'Unknown resource module');
        fields($catalog, ['types', 'operations', 'constants']);
        $base = $module === 'filesystem' ? substr($plan['filesystem']['header'], 0, -4) . '/' : "scpp/$module/";
        $types = [];
        foreach (object(at($catalog, 'types')) as $name => $type) {
            requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $name) === 1, 'Invalid module type');
            fields($type, ['fields', 'carriers']);
            $header = $base . $name . '.hpp';
            $record = ['header' => $header, 'module' => "runtime-$module", 'strategy' => 'module_value'];
            if (isset($type->fields)) {
                $record['fields'] = object($type->fields);
                foreach ($record['fields'] as $field => $identity) {
                    requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $field) === 1, 'Invalid record field');
                    moduleFunctionTypeExport($plan, $identity);
                }
            }
            $plan['resource_types'][$name] = $record;
            $types[$name] = $header;
            foreach (names(at($type, 'carriers')) as $family) {
                requireThat(in_array($family, ['result','result_or_false','result_or_bool','nullable'], true), 'Unsupported module carrier');
                $prototype = array_column($plan['carriers'], null, 'name')[$family . '_string'] ?? null;
                requireThat($prototype !== null, 'Missing carrier prototype');
                $carrier = $family . '_' . $name;
                $types[$carrier] = $base . $carrier . '.hpp';
                $plan['resource_carriers'][$carrier] = ['name' => $carrier, 'payload' => $name, 'header' => $types[$carrier]] + $prototype;
            }
        }
        $ops = [];
        foreach (listValue(at($catalog, 'operations')) as $row) {
            fields($row, ['native','general','php','parameters','result','required','dispatch','method','constructor','property','slot_pack','mutable_parameters']);
            $native = textValue(at($row, 'native'));
            requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $native) === 1 && !isset($ops[$native]), 'Invalid or duplicate module operation');
            $params = listValue(at($row, 'parameters'));
            $required = $row->required ?? count($params);
            requireThat(is_int($required) && $required >= 0 && $required <= count($params), 'Invalid module arity');
            $includes = [];
            $mask = 0;
            $result = textValue(at($row, 'result'));
            foreach ([...$params, $result] as $index => $id) {
                if ($id === 'void') {
                    requireThat($index === count($params), 'Void cannot be a module parameter');
                    continue;
                }
                if (isset($plan['resource_types'][$id])) $includes[] = $plan['resource_types'][$id]['header'];
                elseif (isset($types[$id])) $includes[] = $types[$id];
                else $includes[] = providerHeader($plan['submodules'], moduleFunctionTypeExport($plan, $id)['provider']);
                if ($index < count($params) && !in_array($id, ['int64','bool_t'], true)) $mask |= 1 << $index;
            }
            $mutable = 0;
            foreach (listValue($row->mutable_parameters ?? []) as $index) {
                requireThat(is_int($index) && $index >= 0 && $index < $required && $index < 32
                    && !($mutable & (1 << $index)), 'Invalid mutable module parameter');
                $mutable |= 1 << $index;
            }
            $mask &= ~$mutable;
            $header = $base . $native . '.hpp';
            $surfaces = [];
            foreach (['general','php'] as $surface) {
                foreach (names($row->{$surface} ?? []) as $name) {
                    requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $name) === 1, 'Invalid module source name');
                    $surfaces[] = "$surface:func:$name";
                }
            }
            $slotPack = $row->slot_pack ?? false;
            requireThat(is_bool($slotPack) && (!$slotPack || ($module === 'mysqli' && $native === 'bind_param'
                && $params === ['mysqli_stmt','string'] && $result === 'bool_t')), 'Unknown borrowed-slot signature');
            if (isset($row->method) || isset($row->property)) {
                requireThat(isset($types[$params[0] ?? '']), 'Receiver must belong to the module');
            }
            if (isset($row->method)) {
                requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $row->method) === 1, 'Invalid module method');
                $surfaces[] = "php:{$params[0]}:{$row->method}";
            }
            if (isset($row->constructor)) {
                requireThat(isset($types[$result]) && $row->constructor === $result, 'Invalid module constructor');
                $surfaces[] = "php:new:$result";
            }
            if (isset($row->property)) {
                requireThat(count($params) === 1, 'Property requires exactly one receiver');
                foreach (names(is_string($row->property) ? [$row->property] : $row->property) as $field) {
                    requireThat(preg_match('/^[a-z][a-z_0-9]*$/D', $field) === 1, 'Invalid module property');
                    $surfaces[] = "php:property:{$params[0]}:$field";
                    $plan['resource_types'][$params[0]]['properties'][$field] = $result;
                }
            }
            requireThat($surfaces !== [], 'Missing module operation surface');
            $dispatch = $row->dispatch ?? null;
            requireThat($dispatch === null || (is_int($dispatch) && $dispatch >= 0 && $dispatch < $required), 'Invalid module overload index');
            $ops[$native] = compact('params','result','mutable','mask','header','includes','required','surfaces','dispatch','slotPack');
        }
        $constants = object($catalog->constants ?? (object)[]);
        foreach ($constants as $name => $value) requireThat(preg_match('/^[A-Z][A-Z0-9_]*$/D', $name) && is_int($value), 'Invalid module constant');
        $plan['resource_modules'][$module] = compact('ops','types','constants');
    }
}

function resourceNamespace(string $module): string {
    if ($module === 'filesystem') return 'fs';
    return in_array($module, ['mysqli','webview'], true) ? $module . '_api' : $module;
}

function renderResourceModules(array $plan, array &$files): void {
    foreach ($plan['resource_modules'] ?? [] as $module => $catalog) {
        $bridge = $defs = BANNER;
        foreach ($catalog['types'] as $name => $header) {
            $h = BANNER . "#pragma once\n";
            if (isset($plan['resource_carriers'][$name])) {
                $carrier = $plan['resource_carriers'][$name];
                [$decl, $impl] = renderCarrier($plan, $carrier);
                $h .= includeHeader($plan['carriers_header']) . includeHeader($catalog['types'][$carrier['payload']]) . "namespace scpp {\n$decl}\n";
                $defs .= "namespace scpp {\n$impl}\n";
            } elseif (isset($plan['resource_types'][$name]['fields'])) {
                $decl = '';
                $includes = [];
                foreach ($plan['resource_types'][$name]['fields'] as $field => $id) {
                    $export = moduleFunctionTypeExport($plan, $id);
                    $includes[providerHeader($plan['submodules'], $export['provider'])] = true;
                    $decl .= "    $id $field{};\n";
                }
                foreach (array_keys($includes) as $include) $h .= includeHeader($include);
                $h .= "namespace scpp { struct $name {\n$decl}; }\n";
            } else {
                $h .= template('resources/handle', ['name' => $name]);
                $bridge .= includeHeader($header) . template('resources/access', ['name' => $name]);
                $defs .= template('resources/lifecycle', ['name' => $name]);
            }
            $files['public/' . $header] = $h;
        }
        foreach ($catalog['ops'] as $native => $op) {
            if ($op['slotPack']) {
                $files['public/' . $op['header']] = BANNER . template('mysqli/bind_param', []);
                $bridge .= includeHeader($op['header']);
                continue;
            }
            $params = [];
            foreach ($op['params'] as $i => $id) {
                $params[] = match (true) {
                    (bool) ($op['mutable'] & (1 << $i)) => "scpp::$id&",
                    (bool) ($op['mask'] & (1 << $i)) => "const scpp::$id&",
                    default => "scpp::$id",
                };
            }
            $decl = '';
            for ($i = $op['required']; $i <= count($params); ++$i) {
                $return = $op['result'] === 'void' ? 'void' : '[[nodiscard]] scpp::' . $op['result'];
                $decl .= "$return $native(" . implode(', ', array_slice($params, 0, $i)) . ");\n";
            }
            $files['public/' . $op['header']] = moduleHeaderBody($op['includes'], 'scpp::' . resourceNamespace($module), $decl);
            $bridge .= includeHeader($op['header']);
        }
        $prefix = $module === 'filesystem' ? 'filesystem_locks' : $module;
        $files["private/{$prefix}_provider.hpp"] = "#pragma once\n" . $bridge;
        $files["private/{$prefix}_values.hpp"] = includeHeader('carrier_semantics.hpp') . $defs;
    }
}

function compilerResourceCases(?array $runtime, array $types): array {
    $cases = [];
    foreach ($runtime['resource_modules'] ?? [] as $module => $catalog) {
        $namespace = resourceNamespace($module);
        $types['void'] = 'void';
        $groups = [];
        foreach ($catalog['ops'] as $native => $op) {
            $args = array_map(fn($t) => "type_{$types[$t]}::instance()", $op['params']);
            foreach ($op['surfaces'] as $key) {
                $name = substr($key, strrpos($key, ':') + 1);
                $member = str_starts_with($key, 'php:') && !str_starts_with($key, 'php:func:') && !str_starts_with($key, 'php:new:') ? ', free_receiver: true' : '';
                if ($op['mutable']) $member .= ", reference_mask: {$op['mutable']}";
                if ($op['slotPack']) $member .= ', borrows_slots: true';
                $call = "new runtime_function('$name', [" . implode(', ', $args) . "], type_{$types[$op['result']]}::instance(), '::scpp::$namespace::$native', '{$op['header']}', required_parameters: {$op['required']}, const_reference_mask: {$op['mask']}, required_module: 'runtime-$module'$member)";
                $groups[$key][] = [$op, $call];
            }
        }
        foreach ($groups as $key => $rows) {
            [[$op,$call]] = $rows;
            if (count($rows) > 1) {
                $variants = [];
                $index = $op['dispatch'];
                $arity = count($op['params']);
                foreach ($rows as [$row,$binding]) {
                    requireThat($index !== null && $index === $row['dispatch'] && $arity === count($row['params']) && $arity === $row['required'], 'Incompatible module overloads');
                    $type = $types[$row['params'][$index]];
                    requireThat(!isset($variants[$type]), 'Duplicate module overload');
                    $variants[$type] = "type_$type::class => $binding";
                }
                $context = isset($variants['vector_string']) ? ", list_context: type_vector_string::instance()" : '';
                $call = "new runtime_overloads([$arity => [" . implode(', ', $variants) . "]], argument_index: $index$context, required_module: 'runtime-$module')";
            }
            $cases[] = "\t\t\t'$key' => \$bindings[\$key] ??= $call,";
        }
    }
    return $cases;
}
