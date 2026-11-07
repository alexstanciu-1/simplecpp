<?php
declare(strict_types=1);
namespace Scpp\Api;
function lifecycle(string $n): array {
    return [template('lifecycle_declaration', ['n' => $n]), template('lifecycle_definition', ['n' => $n])];
}

function renderSequences(array $plan, array &$files, string &$definitions, array &$groups): void {
    foreach(['string' => 'renderString', 'vectors' => 'renderVectors', 'carriers' => 'renderCarriers'] as $family => $renderer) {
        $path = $family === 'string' ? ($plan['string']['header'] ?? null):($plan[$family . '_header'] ?? null);
        if($path === null) {
            continue;
        }
        [$h, $d] = (__NAMESPACE__ . '\\' . $renderer)($plan);
        $provider = match ($family) {
            'string' => $plan['string']['provider'],
            'vectors' => $plan['vector_publication']['provider'],
            'carriers' => $plan['carrier_publication']['provider'],
        };
        $groups[] = ['provider' => $provider, 'declarations' => $h, 'system' => []];
        $definitions .= $d;
    }
}

function renderString(array $plan): array {
    $s = $plan['string'];
    $n = $s['name'];
    $count = $s['count_representation'];
    [$lh, $ld] = lifecycle($n);
    $vars = compact('n', 'count', 'lh', 'ld');
    $h = template('string_declaration', $vars);
    $inputs = [];
    foreach ($plan['conversions'] as $conversion) {
        if ($conversion['target'] === $n) $inputs[] = scalarNativeIdentity($plan, $conversion['source']);
    }
    foreach (NATIVES as $native) {
        if (!in_array($native, $inputs, true)) $h .= "    explicit $n($native) = delete;\n";
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
    foreach ($plan['string_functions'] ?? [] as $function) {
        $name = substr($function['symbol'], strlen('scpp::str::'));
        $h .= "namespace str { inline {$function['result']} $name(const string& value) { return value.{$function['method']}(); } }\n";
    }
    return [$h . "}\n", $d];
}

function renderVectors(array $plan): array {
    $h = "namespace scpp {\nnamespace detail { struct vector_access; }\n";
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
            $sourceElement = substr($src, strlen('vector_'));
            $expression = "static_cast<$sourceElement>(item)";
            if ($e !== 'string') {
                $binding = null;
                foreach ($plan['checked_conversions']['functions'] ?? [] as $function) {
                    if ($function['source'] === $sourceElement && $function['target'] === $e) $binding = $function['symbol'];
                }
                requireThat($binding !== null, 'Vector conversion lacks named runtime binding');
                $expression = "$binding($expression)";
            }
            if ($e === 'string') $expression = "string{static_cast<$sourceElement>(item)}";
            $d .= template('vector_cast', $vars + ['src' => $src, 'conversion' => $expression]);
        }
        $h .= template('vector_methods_declaration', $vars);
        $d .= template('vector_methods_definition', $vars);
        $indices = [$count];
        $values = [$e];
        if ($v['native_index'] && scalarNativeIdentity($plan, $count) !== 'int') {
            $indices[] = 'int';
            $h .= "    $e at(int index) const;\n";
            $d .= "$e $n::at(int index) const { return at(detail::checked_integer_cast<$count>(index)); }\n";
        }
        $native = $v['native_value'];
        if ($native !== '' && scalarNativeIdentity($plan, $e) !== $native) {
            $values[] = $native;
            $h .= "    void append($native value);\n";
            $conversion = nativeScalarConversion($plan, $e, $native, 'value');
            $d .= "void $n::append($native value) { append($conversion); }\n";
        }
        $sets = [];
        foreach ($indices as $index) foreach ($values as $value) {
            $sets[] = [$index, $value];
            if ($index === $count && $value === $e) continue;
            $h .= "    void set($index index, $value value);\n";
            $a = $index === $count ? 'index' : "detail::checked_integer_cast<$count>(index)";
            $b = $value === $e ? 'std::move(value)' : nativeScalarConversion($plan, $e, $value, 'value');
            $d .= "void $n::set($index index, $value value) { set($a, $b); }\n";
        }
        $h .= scalarInputFences($plan, 'at', array_map(static fn(string $type): array => [$type], $indices), false, ' const');
        $h .= scalarInputFences($plan, 'append', array_map(static fn(string $type): array => [$type], $values));
        $h .= scalarInputFences($plan, 'set', $sets);
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
    $h = '';
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
        $h .= template('error_declaration', ['fences' => scalarInputFences($plan, 'error_t', [['string', 'int64'], ['string', 'int64', 'string']], true)]);
        $d .= template('error_definition');
    }
    foreach($plan['carriers'] as $c) {
        $n = $c['name'];
        $p = $c['payload'];
        $result = $c['family'] === 'result';
        $nullable = $c['family'] === 'nullable';
        [$lh, $ld] = lifecycle($n);
        $vars = compact('n', 'p', 'result', 'lh', 'ld') + ['explicit' => str_starts_with($c['extraction'], 'implicit') ? '':'explicit ', 'error_type' => $result ? ', error_t':''];
        $h .= template('carrier_declaration', $vars) . scalarInputFences($plan, $n, [[$p]], true);
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
