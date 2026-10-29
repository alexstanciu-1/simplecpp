<?php
declare(strict_types=1);
namespace Scpp\Api;
function lifecycle(string $n): array {
    return [template('lifecycle_declaration', ['n' => $n]), template('lifecycle_definition', ['n' => $n])];
}

function renderSequences(array $plan, array &$files, string &$definitions): void {
    foreach(['string' => 'renderString', 'vectors' => 'renderVectors', 'carriers' => 'renderCarriers'] as $family => $renderer) {
        $path = $family === 'string' ? ($plan['string']['header'] ?? null):($plan[$family . '_header'] ?? null);
        if($path === null) {
            continue;
        }
        [$h, $d] = (__NAMESPACE__ . '\\' . $renderer)($plan);
        $files['public/' . $path] = BANNER . $h;
        $definitions .= $d;
    }
}

function renderString(array $plan): array {
    $s = $plan['string'];
    $n = $s['name'];
    $count = $s['count_type'];
    $types = $plan['headers']['types'];
    $vals = $plan['headers']['values'];
    [$lh, $ld] = lifecycle($n);
    $vars = compact('n', 'count', 'types', 'vals', 'lh', 'ld');
    $h = template('string_declaration', $vars);
    foreach(NATIVES as $native) {
        $h .= "    explicit $n($native) = delete;\n";
    }
    $h .= template('int128_fences', ['name' => $n]);
    foreach($plan['conversions'] as $c) {
        if($c['target'] === $n) {
            $h .= "    explicit $n(" . scalarInput($c['source']) . " value);\n";
        }
    }
    $h .= template('string_methods_declaration', $vars);
    $d = template('string_storage', $vars) . template('string_methods_definition', $vars);
    foreach($s['operations'] as $op) {
        $append = $op['implementation'] === 'string_append';
        $symbol = $op['symbol'];
        $signature = $op['form'] === 'compound' ? "$n& operator+=($n& left, const $n& right)":($append ? "$n operator+($n left, const $n& right)":"bool_t operator$symbol(const $n& left, const $n& right) noexcept");
        $h .= "$signature;\n";
        $d .= "$signature { ";
        $d .= $append ? 'left.append(right); return left;':"return bool_t{detail::string_access::read(left) $symbol detail::string_access::read(right)};";
        $d .= " }\n";
    }
    return [$h . "}\n", $d];
}

function renderVectors(array $plan): array {
    $h = "#pragma once\n" . includeHeader($plan['headers']['operators']);
    if(isset($plan['string'])) {
        $h .= includeHeader($plan['string']['header']);
    }
    $h .= "namespace scpp {\nnamespace detail { struct vector_access; }\n";
    $d = '';
    foreach($plan['vectors'] as $v) {
        $h .= "class {$v['name']};\n";
        $d .= template('vector_storage', ['n' => $v['name'], 'e' => $v['element']]);
    }
    $d .= "namespace detail { struct vector_access {\n";
    foreach($plan['vectors'] as $v) {
        $d .= template('vector_access', ['n' => $v['name'], 'e' => $v['element']]);
    }
    $d .= "}; }\n";
    foreach($plan['vectors'] as $v) {
        $n = $v['name'];
        $e = $v['element'];
        $count = $v['count_type'];
        [$lh, $ld] = lifecycle($n);
        $vars = compact('n', 'e', 'count');
        $h .= "class $n final {\npublic:\n$lh";
        $d .= $ld;
        foreach($v['casts_from'] as $c) {
            $src = $c['source'];
            $h .= "    explicit $n(const $src&);\n";
            $d .= template('vector_cast', $vars + ['src' => $src]);
        }
        $h .= template('vector_methods_declaration', $vars);
        $d .= template('vector_methods_definition', $vars);
        $indices = [$count];
        $values = [$e];
        if($v['native_index']) {
            $indices[] = 'int';
            $h .= "    $e at(int index) const;\n";
            $d .= "$e $n::at(int index) const { return at($count{index}); }\n";
        }
        if($v['native_value'] !== '') {
            $native = $v['native_value'];
            $values[] = $native;
            $h .= "    void append($native value);\n";
            $d .= "void $n::append($native value) { append($e{value}); }\n";
        }
        foreach($indices as $index) {
            foreach($values as $value) {
                if($index === $count && $value === $e) {
                    continue;
                }
                $h .= "    void set($index index, $value value);\n";
                $a = $index === $count ? 'index':"$count{index}";
                $b = $value === $e ? 'std::move(value)':"$e{value}";
                $d .= "void $n::set($index index, $value value) { set($a, $b); }\n";
            }
        }
        foreach([NATIVES, ['__int128', 'unsigned __int128']] as $group => $natives) {
            if($group === 1 && !$v['native_index'] && $v['native_value'] === '') {
                continue;
            }
            if($group === 1) {
                $h .= "#ifdef __SIZEOF_INT128__\n";
            }
            $deleted = [];
            foreach($natives as $native) {
                if($v['native_index'] && $native !== 'int') {
                    $deleted[] = "    void at($native) const = delete;\n";
                    foreach($values as $value) {
                        $deleted[] = "    void set($native, $value) = delete;\n";
                    }
                }
                if($v['native_value'] !== '' && $v['native_value'] !== $native) {
                    $deleted[] = "    void append($native) = delete;\n";
                    foreach($indices as $index) {
                        $deleted[] = "    void set($index, $native) = delete;\n";
                    }
                }
            }
            $deleted = array_unique($deleted);
            sort($deleted, SORT_STRING);
            $h .= implode('', $deleted);
            if($group === 1) {
                $h .= "#endif\n";
            }
        }
        $h .= "private:\n    struct storage;\n    storage* storage_ = nullptr;\n    friend struct detail::vector_access;\n};\n";
        foreach($v['operations'] as $op) {
            $sig = "bool_t operator{$op['symbol']}(const $n& left, const $n& right)";
            $h .= "$sig;\n";
            $d .= template('vector_comparison', $vars + ['sig' => $sig, 'negate' => $op['operation'] === 'equal' ? '':'!']);
        }
    }
    return [$h . "}\n", $d];
}

function renderCarriers(array $plan): array {
    $h = "#pragma once\n" . includeHeader($plan['headers']['operators']);
    if(isset($plan['string'])) {
        $h .= includeHeader($plan['string']['header']);
    }
    if(isset($plan['vectors_header'])) {
        $h .= includeHeader($plan['vectors_header']);
    }
    $payloadHeaders = [];
    foreach ($plan['carriers'] as $carrier) {
        if (isset($carrier['payload_header'])) $payloadHeaders[] = $carrier['payload_header'];
    }
    $payloadHeaders = array_unique($payloadHeaders);
    sort($payloadHeaders, SORT_STRING);
    foreach ($payloadHeaders as $header) $h .= includeHeader($header);
    $h .= "namespace scpp {\n";
    $d = '';
    if(isset($plan['error'])) {
        $h .= template('error_declaration');
        $d .= template('error_definition');
    }
    foreach($plan['carriers'] as $c) {
        $n = $c['name'];
        $p = $c['payload'];
        $result = $c['family'] === 'result';
        $nullable = $c['family'] === 'nullable';
        [$lh, $ld] = lifecycle($n);
        $vars = compact('n', 'p', 'result', 'lh', 'ld') + ['explicit' => str_starts_with($c['extraction'], 'implicit') ? '':'explicit ', 'error_type' => $result ? ', error_t':''];
        $h .= template('carrier_declaration', $vars);
        $d .= template('carrier_storage', $vars) . template('carrier_value_definition', $vars) . ' ';
        if($result) {
            $d .= "if (!storage_) return $p{}; ";
        }
        $d .= "if (!storage_) throw std::runtime_error(\"carrier_no_value\"); return storage_->content.value(); }\n$n::operator $p() const { return value(); }\n";
        if($nullable) {
            $h .= "    explicit $n(null_t) noexcept;\n";
            $d .= "$n::$n(null_t) noexcept {}\n";
        }
        if($result) {
            $h .= "    explicit $n(error_t error);\n    bool_t has_error() const noexcept;\n    error_t error() const;\n";
            $d .= template('carrier_error_definition', $vars);
        }
        else {
            $h .= "    void reset() noexcept;\n";
            $d .= "void $n::reset() noexcept { delete storage_; storage_ = nullptr; }\n";
            if(!$nullable) {
                $h .= "    static $n from_false() noexcept;\n    bool_t is_false() const noexcept;\n";
                $d .= template('carrier_false_definition', $vars);
            }
            if($c['family'] === 'result_or_bool') {
                $h .= "    static $n from_true();\n    bool_t is_true() const noexcept;\n";
                $d .= template('carrier_true_definition', $vars);
            }
        }
        $h .= "private:\n    struct storage;\n    storage* storage_ = nullptr;\n};\n";
    }
    return [$h . "}\n", $d];
}
