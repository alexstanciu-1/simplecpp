<?php
declare(strict_types=1);
namespace Scpp\Api;
function renderHashes(array $plan, array &$files, array &$groups): void {
    if(!isset($plan['hashes'])) {
        return;
    }
    $hashes = $plan['hashes'];
    [$h, $d, $issetHeader] = renderHashTypes($plan, $hashes['specializations'], true);
    $groups[] = ['provider' => $plan['hash_publication']['provider'], 'declarations' => $h, 'system' => []];
    $files['public/scpp/php/hash_isset.hpp'] = BANNER . "#pragma once\n" . includeHeader($hashes['header']) . $issetHeader;
    $files['src/hash.cpp'] = BANNER . includeHeader($hashes['header']) . includeHeader('concrete_index_impl.hpp')
        . includeHeader('new') . includeHeader('cstring') . includeHeader('scalar_semantics.hpp') . $d;
}

function renderHashTypes(array $plan, array $specializations, bool $lookup): array {
    $hashes = $plan['hashes'];
    $issetHeader = '';
    $mt = !empty($plan['mt_segments']);
    $h = ($mt ? "namespace scpp::mt { struct job; }\n" : '') . "namespace scpp {\n";
    $d = "namespace scpp {\n";
    foreach($specializations as $hash) {
        $n = $hash['name'];
        $k = $hash['key'];
        $v = $hash['value'];
        $issetHeader .= template('collections/php_hash_isset', ['hash' => '::scpp::' . $n, 'key' => '::scpp::' . $k, 'method' => 'has']);
        $count = $hashes['count_type'];
        $nullable = 'nullable_' . $v;
        $lh = template('core/lifecycle_declaration', ['n' => $n]);
        $vars = compact('n', 'k', 'v', 'count', 'nullable', 'lh') + $hash['traversal'] + ['inline' => '', 'mt_segments' => $mt ? '1' : ''];
        $vars['lookup'] = $lookup ? "    $nullable find(const $k& key) const;\n" : '';
        $vars['methods'] = template('collections/hash_value_methods_declaration', $vars);
        $h .= template('collections/hash_declaration', $vars) . template('collections/hash_key_iteration_declaration', $vars);
        if ($hash['initialization'] !== null) {
            $init = (array)$hash['initialization'];
            $input = $vars + $init + ['e' => $init['entry'], 'inline_limit' => $init['inline_entries']];
            $h .= template('collections/hash_input_entry', $input);
            $h .= template('collections/initialization_input_declaration', $input + ['guard' => template('collections/initialization_input_guard', $input)]);
            $d .= template('collections/initialization_input_definition', $input);
            if (isset($init['consume'])) $h .= "    static $n {$init['consume']}({$init['entry']}* values, $count count);\n";
        }
        foreach (['has', 'at', 'find', 'remove'] as $method) $h .= scalarInputFences($plan, $method, [["const $k&"]], false, $method === 'remove' ? '' : ' const');
        $h .= scalarInputFences($plan, 'set', [[$k, $v]]);

        $parts = concreteTemplate(hashConcreteRecipe($hash), ['k' => new RuntimeConcreteType($plan, $k), 'v' => new RuntimeConcreteType($plan, $v)], array_diff_key($vars, ['k' => true, 'v' => true]) + hashConcreteArguments($hash, $count), 'scpp');
        $d .= $parts['storage'] . $parts['lifecycle'] . $parts['definition'] . ($lookup ? template('collections/hash_lookup_definition', $vars) : '') . template('collections/hash_iteration', $vars);
        if ($hash['initialization'] !== null) {
            foreach (['read_only', 'consume'] as $mode) {
                if (!isset($init[$mode])) continue;
                $d .= template('collections/hash_input_factory', $input + hashInputFactoryArguments($init, $mode, $k, $v));
            }
        }
        if($k === 'int64') {
            $h .= $parts['append_declaration'] . scalarInputFences($plan, 'append', [[$v]]);
            $d .= $parts['append_definition'];
        }
        $h .= "private:\n" . $parts['private_declaration'] . "    struct storage;\n    storage* storage_ = nullptr;\n};\n";
    }
    return [$h . "}\n", $d . "}\n", $issetHeader];
}

function renderDatetime(array $plan, array &$files): void {
    if(!isset($plan['datetime'])) {
        return;
    }
    $module = $plan['datetime'];
    $h = BANNER . "#pragma once\n";
    $d = BANNER . includeHeader($module['header']) . includeHeader('datetime_native.hpp') . includeHeader('scalar_semantics.hpp') . "namespace scpp::dt {\n";
    foreach($module['operations'] as $op) {
        $args = [];
        foreach($op['arguments'] as $i => $type) {
            $args[] = "$type input$i";
        }
        $name = $op['name'];
        $result = $op['result'];
        $signature = "$result $name(" . implode(', ', $args) . ')';
        $declaration = "$signature;\n" . scalarInputFences($plan, $name, [$op['arguments']]);
        $files['public/' . $op['header']] = moduleHeaderBody($op['includes'], $module['namespace'], $declaration);
        $h .= includeHeader($op['header']);
        if($op['arguments'] === []) {
            $native = $result === 'uint64' ? 'unsigned long long':'long long';
            $body = "return $result{static_cast<$native>(detail::datetime::$name())};";
        }
        elseif($name === 'sleep_millis') {
            $body = 'detail::datetime::sleep_millis(input0);';
        }
        elseif($name === 'format_iso_utc') {
            $body = 'const auto bytes = detail::datetime::format_iso_utc(input0); return string{bytes.data(), 20LL};';
        }
        elseif($result === 'result_int64') {
            $body = "const auto parsed = detail::datetime::$name({input0.data(), static_cast<std::size_t>(input0.size())}); if (parsed.error) return result_int64{exception{string{parsed.error}}}; return result_int64{int64{static_cast<long long>(parsed.value)}};";
        }
        else {
            $seconds = $name === 'format_local' ? ', input1':'';
            $body = "const auto bytes = detail::datetime::$name({input0.data(), static_cast<std::size_t>(input0.size())}$seconds); return string{bytes.data(), detail::checked_integer_cast<{$plan['string']['count_representation']}>(bytes.size())};";
        }
        $d .= "$signature { $body }\n";
    }
    $files['public/' . $module['header']] = $h;
    $files['src/datetime.cpp'] = $d . "}\n";
}

function constructorArguments(array $types, bool $namesOnly): string {
    $args = [];
    foreach($types as $i => $type) {
        $args[] = ($namesOnly ? '':$type . ' ') . 'arg' . $i;
    }
    return implode(', ', $args);
}

function renderOwnership(array $plan, array &$files): void {
    if(!isset($plan['ownership'])) {
        return;
    }
    $o = $plan['ownership'];
    $h = "// Generated ownership API; edit metadata/publication.\n#pragma once\n" . scalarProviderIncludes($plan);
    $headers = array_unique(array_column($o['records'], 'header'));
    sort($headers, SORT_STRING);
    foreach($headers as $header) {
        $h .= includeHeader($header);
    }
    $h .= "namespace scpp {\nnamespace detail { struct ownership_access; }\n";
    $d = "// Generated ownership definitions.\n" . includeHeader($o['header']) . includeHeader('ownership_semantics.hpp') . "namespace scpp {\n";
    foreach($o['records'] as $r) {
        $record = $r['name'];
        $d .= "static_assert(std::is_class_v<$record> && std::is_nothrow_destructible_v<$record>);\n";
        foreach($r['families'] as $s) {
            $h .= "class {$s['name']};\n";
        }
        foreach($r['families'] as $s) {
            $n = $s['name'];
            $family = $s['family'];
            $const = ($s['pointee'] ?? '') === 'const';
            $t = ($const ? 'const ':'') . $record;
            if($family === 'value') {
                $h .= "class $n final {\npublic:\n";
                foreach($r['constructors'] as $args) {
                    $params = constructorArguments($args, false);
                    $values = constructorArguments($args, true);
                    $spec = " noexcept(noexcept($record($values)))";
                    $h .= "    explicit $n($params)$spec;\n";
                    $d .= "$n::$n($params)$spec : payload_($values) {}\n";
                }
                $h .= scalarInputFences($plan, $n, $r['constructors'], true);
                foreach([false, true] as $readOnly) {
                    $ty = ($readOnly ? 'const ':'') . $record;
                    $q = $readOnly ? ' const':'';
                    $h .= "    $ty& operator*()$q noexcept;\n    $ty* operator->()$q noexcept;\n";
                    $d .= "$ty& $n::operator*()$q noexcept { return payload_; }\n$ty* $n::operator->()$q noexcept { return std::addressof(payload_); }\n";
                }
                $h .= "private:\n    $record payload_;\n};\n";
                $d .= "static_assert(sizeof($n) == sizeof($record) && alignof($n) == alignof($record));\n";
                continue;
            }
            $native = "std::{$family}_ptr<$t>";
            $shared = 'shared_' . ($const ? 'const_':'') . $record;
            $size = $s['size'];
            $align = $s['alignment'];
            $get = fn(string $obj):string => "detail::ownership_access::get<$native>($obj)";
            $vars = compact('n', 'native', 'shared', 'size', 'align', 't') + ['this' => $get('*this'), 'other' => $get('other'), 'copy_spec' => $family === 'unique' ? ' = delete':' noexcept'];
            $h .= template('ownership/owner_declaration', $vars);
            $d .= template('ownership/owner_lifetime_definition', $vars);
            if($family !== 'unique') {
                $d .= template('ownership/owner_copy_definition', $vars);
            }
            $thisAccess = $get('*this');
            $other = $get('other');
            $d .= "void $n::reset() noexcept { $thisAccess.reset(); }\nvoid $n::swap($n& other) noexcept { $thisAccess.swap($other); }\n";
            if($family === 'weak') {
                $h .= "    explicit $n(const $shared& owner) noexcept;\n    $shared lock() const noexcept;\n";
                $d .= template('ownership/owner_weak_definition', $vars + ['strong' => "detail::ownership_access::get<std::shared_ptr<$t>>"]);
            }
            else {
                $h .= "    bool_t has_value() const noexcept;\n    explicit operator bool() const noexcept;\n    $t& operator*() const;\n    $t* operator->() const;\n";
                $d .= template('ownership/owner_access_definition', $vars);
                foreach($r['constructors'] as $args) {
                    $params = constructorArguments($args, false);
                    $values = constructorArguments($args, true);
                    $result = $get('result');
                    $h .= "    static $n make($params);\n";
                    $d .= "$n $n::make($params) { $n result; $result = std::make_$family<$t>($values); return result; }\n";
                }
            }
            if ($family !== 'weak') $h .= preg_replace('/^void /m', 'static void ', scalarInputFences($plan, 'make', $r['constructors']));
            foreach($s['qualification_constructors'] as $c) {
                $source = null;
                foreach($r['families'] as $f) {
                    if($f['name'] === $c['source']) {
                        $source = $f;
                    }
                }
                requireThat($source !== null, 'Missing prepared qualification source');
                $transfer = $c['effect'] === 'transfer_source_empty';
                $param = $transfer ? $c['source'] . '&& other':'const ' . $c['source'] . '& other';
                $expression = "detail::ownership_access::get<std::{$source['family']}_ptr<$record>>(other)";
                if($transfer) {
                    $expression = "std::move($expression)";
                }
                $h .= "    explicit $n($param) noexcept;\n";
                $d .= "$n::$n($param) noexcept { detail::ownership_access::construct<$native>(*this, $expression); }\n";
            }
            $h .= "private:\n    friend struct detail::ownership_access;\n    alignas($align) unsigned char storage_[$size];\n};\n";
        }
    }
    $files['public/' . $o['header']] = $h . "}\n";
    $files['src/ownership.cpp'] = $d . "}\n";
}
