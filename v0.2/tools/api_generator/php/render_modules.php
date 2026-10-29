<?php
declare(strict_types=1);
namespace Scpp\Api;
function renderHashes(array $plan, array &$files): void {
    if(!isset($plan['hashes'])) {
        return;
    }
    $hashes = $plan['hashes'];
    $h = BANNER . "#pragma once\n" . includeHeader($plan['headers']['operators']) . includeHeader($plan['carriers_header']);
    if(isset($plan['string'])) {
        $h .= includeHeader($plan['string']['header']);
    }
    $h .= "namespace scpp {\n";
    $d = BANNER . includeHeader($hashes['header']) . includeHeader('typed_hash.hpp') . includeHeader('scalar_semantics.hpp') . "namespace scpp {\n";
    foreach($hashes['specializations'] as $hash) {
        $n = $hash['name'];
        $k = $hash['key'];
        $v = $hash['value'];
        $count = $hashes['count_type'];
        $nullable = 'nullable_' . $v;
        [$lh, $ld] = lifecycle($n);
        $vars = compact('n', 'k', 'v', 'count', 'nullable', 'lh', 'ld') + ['policy' => $k === 'int64' ? 'integer_hash_key':'string_hash_key'];
        $h .= template('hash_declaration', $vars);
        $d .= template('hash_storage', $vars) . template('hash_methods_definition', $vars);
        if($k === 'int64') {
            $h .= "    $k append($v value);\n";
            $d .= "$k $n::append($v value) { if (!storage_) storage_ = new storage; return storage_->table.append(std::move(value)); }\n";
        }
        $h .= "private:\n    struct storage;\n    storage* storage_ = nullptr;\n};\n";
    }
    $files['public/' . $hashes['header']] = $h . "}\n";
    $files['src/hash.cpp'] = $d . "}\n";
}

function renderDatetime(array $plan, array &$files): void {
    if(!isset($plan['datetime'])) {
        return;
    }
    $module = $plan['datetime'];
    $includes = [$plan['headers']['types']];
    foreach($module['operations'] as $op) {
        if($op['result'] === 'string' || in_array('const string&', $op['arguments'], true)) {
            $includes[] = $plan['string']['header'];
        }
        if($op['result'] === 'result_int64') {
            $includes[] = $plan['carriers_header'];
        }
    }
    $includes = array_unique($includes);
    sort($includes, SORT_STRING);
    $h = BANNER . "#pragma once\n";
    foreach($includes as $include) {
        $h .= includeHeader($include);
    }
    $h .= "namespace scpp::dt {\n";
    $d = BANNER . includeHeader($module['header']) . includeHeader('datetime_native.hpp') . includeHeader('scalar_semantics.hpp') . "namespace scpp::dt {\n";
    foreach($module['operations'] as $op) {
        $args = [];
        foreach($op['arguments'] as $i => $type) {
            $args[] = "$type input$i";
        }
        $name = $op['name'];
        $result = $op['result'];
        $signature = "$result $name(" . implode(', ', $args) . ')';
        $h .= "$signature;\n";
        if($op['arguments'] === []) {
            $native = $result === 'uint64' ? 'unsigned long long':'long long';
            $body = "return $result{static_cast<$native>(detail::datetime::$name())};";
        }
        elseif($name === 'sleep_millis') {
            $body = 'detail::datetime::sleep_millis(input0.native_value());';
        }
        elseif($name === 'format_iso_utc') {
            $body = 'const auto bytes = detail::datetime::format_iso_utc(input0.native_value()); return string{bytes.data(), int64{20LL}};';
        }
        elseif($result === 'result_int64') {
            $body = "const auto parsed = detail::datetime::$name({input0.data(), static_cast<std::size_t>(input0.size().native_value())}); if (parsed.error) return result_int64{error_t{string{parsed.error}}}; return result_int64{int64{static_cast<long long>(parsed.value)}};";
        }
        else {
            $seconds = $name === 'format_local' ? ', input1.native_value()':'';
            $body = "const auto bytes = detail::datetime::$name({input0.data(), static_cast<std::size_t>(input0.size().native_value())}$seconds); return string{bytes.data(), int64{detail::checked_integer_cast<long long>(bytes.size())}};";
        }
        $d .= "$signature { $body }\n";
    }
    $files['public/' . $module['header']] = $h . "}\n";
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
    $h = "// Generated ownership API; edit metadata/publication.\n#pragma once\n" . includeHeader($plan['headers']['types']) . includeHeader($plan['headers']['values']);
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
            $h .= template('owner_declaration', $vars);
            $d .= template('owner_lifetime_definition', $vars);
            if($family !== 'unique') {
                $d .= template('owner_copy_definition', $vars);
            }
            $thisAccess = $get('*this');
            $other = $get('other');
            $d .= "void $n::reset() noexcept { $thisAccess.reset(); }\nvoid $n::swap($n& other) noexcept { $thisAccess.swap($other); }\n";
            if($family === 'weak') {
                $h .= "    explicit $n(const $shared& owner) noexcept;\n    $shared lock() const noexcept;\n";
                $d .= template('owner_weak_definition', $vars + ['strong' => "detail::ownership_access::get<std::shared_ptr<$t>>"]);
            }
            else {
                $h .= "    bool_t has_value() const noexcept;\n    explicit operator bool() const noexcept;\n    $t& operator*() const;\n    $t* operator->() const;\n";
                $d .= template('owner_access_definition', $vars);
                foreach($r['constructors'] as $args) {
                    $params = constructorArguments($args, false);
                    $values = constructorArguments($args, true);
                    $result = $get('result');
                    $h .= "    static $n make($params);\n";
                    $d .= "$n $n::make($params) { $n result; $result = std::make_$family<$t>($values); return result; }\n";
                }
            }
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
