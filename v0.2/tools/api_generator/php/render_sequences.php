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
        if ($family === 'string') {
            $h .= "namespace scpp::types { using ::scpp::string; }\n";
            foreach ($plan['string_functions'] ?? [] as $binding) {
                foreach ($binding['adapters'] ?? [] as $flavor => $adapter) {
                    $path = 'public/' . $adapter['header'];
                    $files[$path] ??= BANNER . "#pragma once\n" . includeHeader($binding['header']);
                    $parameters = [];
                    $arguments = [];
                    foreach ($binding['parameters'] as $i => $type) {
                        $arguments[] = $arg = "arg$i";
                        $parameters[] = ($type === 'string' ? 'const ::scpp::string&' : "::scpp::$type") . " $arg";
                    }
                    $files[$path] .= "namespace scpp::$flavor {\ninline ::scpp::{$binding['result']} {$adapter['name']}("
                        . implode(', ', $parameters) . ") { return ::{$binding['symbol']}(" . implode(', ', $arguments) . "); }\n}\n";
                }
            }
        }
        $groups[] = ['provider' => $provider, 'declarations' => $h, 'system' => []];
        $definitions .= $d;
    }
}

function renderString(array $plan): array {
    $s = $plan['string'];
    $n = $s['name'];
    $count = $s['count_representation'];
    $lh = template('lifecycle_declaration', ['n' => $n]);
    $vars = compact('n', 'count', 'lh') + ['inline_capacity' => $s['inline_capacity']];
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
    $read = $plan['string_index_read'] ?? null;
    if ($read) $h .= "    {$read['result']} {$read['name']}({$read['key']} index) const;\n";
    $write = $plan['string_index_write'] ?? null;
    if ($write) $h .= "    void {$write['name']}({$write['key']} index, {$write['value']} value);\n";
    $h .= template('string_methods_declaration', $vars);
    $classic = array_column($plan['string_functions'] ?? [], 'implementation');
    $vars['classic_storage'] = array_intersect($classic, ['repeat', 'ascii_lower', 'ascii_upper'])
        ? template('string_classic_storage', ['n' => $n]) : '';
    $d = template('string_storage', $vars) . template('string_methods_definition', $vars);
    if ($read) {
        $d .= "{$read['result']} $n::{$read['name']}({$read['key']} index) const {\n"
            . "    const auto bytes = detail::string_access::read(*this);\n"
            . "    return static_cast<{$read['result']}>(bytes[detail::sequence_index(index, bytes.size())]);\n}\n";
    }
    if ($write) {
        $d .= "void $n::{$write['name']}({$write['key']} index, {$write['value']} value) {\n"
            . "    const auto offset = detail::sequence_index(index, detail::string_access::read(*this).size());\n"
            . "    char* bytes = is_inline() ? rep_.small.bytes : rep_.large.ptr->bytes.data();\n"
            . "    bytes[offset] = static_cast<char>(value);\n}\n";
    }
    foreach($s['operations'] as $op) {
        $append = $op['implementation'] === 'string_append';
        $symbol = $op['symbol'];
        $signature = $op['form'] === 'compound' ? "$n& operator+=($n& left, const $n& right)":($append ? "$n operator+($n left, const $n& right)":"bool_t operator$symbol(const $n& left, const $n& right) noexcept");
        $h .= "$signature;\n";
        $d .= "$signature { ";
        $d .= $append ? 'left.append(right); return left;':"return bool_t{detail::string_access::read(left) $symbol detail::string_access::read(right)};";
        $d .= " }\n";
    }
    foreach ($plan['explicit_formatters'] ?? [] as $source => $formatter) {
        $signature = "string {$formatter['name']}($source value)";
        $h .= "namespace str { $signature; }\n";
        $body = $formatter['policy'] === 'boolean_words'
            ? 'return value ? string{"true", 4} : string{"false", 5};'
            : "char buffer[128]; const auto size = detail::format_decimal_into(value, buffer); return string{buffer, static_cast<$count>(size)};";
        $d .= "namespace str { $signature { $body } }\n";
    }
    foreach ($plan['string_functions'] ?? [] as $function) {
        $name = substr($function['symbol'], strlen('scpp::str::'));
        $parameters = [];
        foreach ($function['parameters'] as $index => $type) {
            $parameters[] = scalarInput($type) . ' ' . (['value', 'other', 'third', 'fourth'][$index]);
        }
        $signature = "{$function['result']} $name(" . implode(', ', $parameters) . ')';
        if ($function['implementation'] === 'byte_length') {
            $h .= "namespace str { inline $signature { return value.size(); } }\n";
        } elseif ($function['implementation'] === 'checked_single_byte') {
            $h .= "namespace str { $signature; }\n";
            $d .= "namespace str { $signature {\n"
                . "    if (value < 0 || value > 255) throw std::invalid_argument(\"Byte value must be between 0 and 255\");\n"
                . "    const char byte = static_cast<char>(value);\n    return string{&byte, 1};\n} }\n";
        } else {
            $h .= "namespace str { $signature; }\n";
            $method = $function['implementation'];
            $bytes = 'detail::string_access::read';
            $body = match ($method) {
                'find', 'rfind' => "const auto pos = $bytes(value).$method($bytes(other)); return pos == std::string_view::npos ? -1 : detail::checked_integer_cast<int64>(pos);",
                'ascii_lower', 'ascii_upper' => 'return detail::string_access::ascii_case(value, ' . ($method === 'ascii_upper' ? 'true' : 'false') . ');',
                'repeat' => "return detail::string_access::own(detail::repeat_bytes($bytes(value), other));",
                default => "return $bytes(value).$method($bytes(other));",
            };
            $d .= "namespace str { $signature { $body } }\n";
        }
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
