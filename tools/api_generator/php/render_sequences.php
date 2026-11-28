<?php
declare(strict_types=1);
namespace Scpp\Api;
function lifecycle(string $n): array {
    return [template('core/lifecycle_declaration', ['n' => $n]), template('core/lifecycle_definition', ['n' => $n])];
}

function stringAdapterOverload(string $flavor, array $adapter, string $result, array $parameters, string $symbol, array $arguments): string {
    $args = implode(', ', $arguments);
    $body = isset($adapter['body'])
        ? rtrim(template('strings/' . $adapter['body'], ['symbol' => $symbol, 'result' => $result, 'args' => $args]))
        : "{ return ::$symbol($args); }";
    return "namespace scpp::$flavor {\ninline ::scpp::$result {$adapter['name']}(" . implode(', ', $parameters) . ") $body\n}\n";
}

function renderSequences(array $plan, array &$files, string &$definitions, array &$groups): void {
    foreach (['string', 'vectors', 'carriers'] as $family) {
        $path = $family === 'string' ? ($plan['string']['header'] ?? null):($plan[$family . '_header'] ?? null);
        if($path === null) {
            continue;
        }
        [$h, $d] = match ($family) {
            'string' => renderString($plan, $groups),
            'vectors' => renderVectors($plan),
            'carriers' => renderCarriers($plan, $groups),
        };
        $provider = match ($family) {
            'string' => $plan['string']['provider'],
            'vectors' => $plan['vector_publication']['provider'],
            'carriers' => $plan['carrier_publication']['provider'],
        };
        if ($family === 'string') {
            $h .= "namespace scpp::types { using ::scpp::string; }\n";
            if (hasType($plan, 'int64')) {
                $files['public/scpp/php/parse_decimal_int64.hpp'] = BANNER . "#pragma once\n" . scalarProviderIncludes($plan)
                    . "namespace scpp { class string; namespace php { int64 parse_decimal_int64(const string& value); } }\n";
                $d .= template('strings/php_decimal_int64');
            }
            $carrierIncludes = [];
            foreach ($plan['string_functions'] ?? [] as $binding) {
                foreach ($binding['adapters'] ?? [] as $flavor => $adapter) {
                    $path = 'public/' . $adapter['header'];
                    $aggregate = 'public/' . stringAdapterHeader($flavor, $binding['implementation']);
                    if ($path !== $aggregate) {
                        $files[$aggregate] ??= BANNER . "#pragma once\n" . includeHeader($binding['header']);
                        $files[$aggregate] .= includeHeader($adapter['header']);
                    }
                    $inputHeader = $path !== $aggregate ? ($binding['direct_header'] ?? $binding['header']) : $binding['header'];
                    $files[$path] ??= BANNER . "#pragma once\n" . includeHeader($inputHeader);
                    $result = $adapter['result'] ?? $binding['result'];
                    $result = $plan['source_types']->{$result} ?? $result;
                    $resultHeader = $binding['result_header'] ?? (isset($adapter['body']) ? providerHeader($plan['submodules'], typeExport($plan['submodules'], $result)['provider']) : null);
                    foreach ([$resultHeader, $binding['input_header'] ?? null] as $header) {
                        if ($header === null || isset($carrierIncludes[$path][$header])) continue;
                        $files[$path] .= includeHeader($header);
                        $carrierIncludes[$path][$header] = true;
                    }
                    if (isset($binding['overloads'])) {
                        foreach ($binding['overloads'] as $arity => $variants) {
                            foreach ($variants as $types) {
                                $parameters = $arguments = [];
                                $constRefs = stringAdapterConstReferenceMask($types);
                                foreach ($types as $i => $type) {
                                    $parameters[] = (($constRefs & (1 << $i)) ? "const ::scpp::$type&" : "::scpp::$type") . " arg$i";
                                    $arguments[] = "arg$i";
                                }
                                if ($arity === 1) $arguments[] = '0';
                                if ($arity < 4) array_push($arguments, '::scpp::string{".", 1}', '::scpp::string{",", 1}');
                                $files[$path] .= stringAdapterOverload($flavor, $adapter, $result, $parameters, $binding['symbol'], $arguments);
                            }
                        }
                        continue;
                    }
                    $parameters = [];
                    $arguments = [];
                    $types = stringAdapterOrder($adapter['parameters'] ?? $binding['parameters'], $adapter);
                    $constRefs = stringAdapterConstReferenceMask($types);
                    foreach ($types as $i => $type) {
                        $arguments[] = $arg = "arg$i";
                        $cppType = ($constRefs & (1 << $i))
                            ? 'const ::scpp::' . $plan['source_types']->{$type} . '&' : "::scpp::$type";
                        if (in_array($i, $adapter['reference_parameters'] ?? [], true)) $cppType .= '&';
                        $parameters[] = "$cppType $arg";
                    }
                    $arguments = stringAdapterOrder($arguments, $adapter);
                    if (in_array($binding['implementation'], ['count_matches', 'compare_window'], true) || isset($adapter['search_offset'])) {
                        for ($arity = $binding['required_parameters']; $arity < count($parameters); ++$arity) {
                            $files[$path] .= stringAdapterOverload($flavor, $adapter, $result, array_slice($parameters, 0, $arity),
                                $binding['symbol'], array_slice($arguments, 0, $arity));
                        }
                    }
                    if (isset($adapter['complement'])) {
                        $mode = $adapter['complement'] ? 'true' : 'false';
                        $short = array_slice($parameters, 0, 3);
                        $short[2] .= ' = 0';
                        $files[$path] .= stringAdapterOverload($flavor, $adapter, $result, $short, $binding['symbol'],
                            ['arg0', 'arg1', 'arg2', 'arg0.size()', $mode]);
                        $arguments[3] = 'arg3.has_value() ? arg3.value() : arg0.size()';
                        $arguments[] = $mode;
                    }
                    if ($binding['implementation'] === 'chunk_bytes') {
                        $parameters[1] .= ' = 76';
                        $parameters[2] .= ' = ::scpp::string{"\r\n", 2}';
                    }
                    if ($binding['implementation'] === 'wrap_words') {
                        $parameters[1] .= ' = 75';
                        $parameters[2] .= ' = ::scpp::string{"\n", 1}';
                        $parameters[3] .= ' = false';
                    }
                    if ($binding['implementation'] === 'ascii_words') $parameters[1] .= ' = ::scpp::string{" \t\r\n\f\v", 6}';
                    if ($binding['implementation'] === 'newline_breaks') $parameters[1] .= ' = true';
                    if (isset($adapter['before_needle'])) $parameters[2] .= ' = false';
                    if ($binding['implementation'] === 'replace_window') $parameters[3] .= ' = 0';
                    if (isset($adapter['ascii_fold'])) $arguments[] = $adapter['ascii_fold'] ? 'true' : 'false';
                    if (isset($adapter['search_offset'])) $arguments[] = '::scpp::str::search_policy::' . $adapter['search_offset'];
                    if (isset($adapter['limit_policy'])) {
                        $short = implode(', ', array_slice($parameters, 0, 2));
                        $args = implode(', ', array_slice($arguments, 0, 2));
                        $files[$path] .= "namespace scpp::$flavor {\ninline ::scpp::$result {$adapter['name']}($short)"
                            . " { return ::{$binding['symbol']}($args); }\n}\n";
                        $policy = $adapter['limit_policy'];
                        if ($policy === 'uint32_cap') {
                            $arguments[2] = '(arg2 & 0xffffffffLL)';
                            $policy = 'cap';
                        }
                        $arguments[] = "::scpp::str::split_policy::$policy";
                    }
                    if (isset($adapter['pad_side'])) {
                        $parameters[2] .= ' = ::scpp::string{" ", 1}';
                        if ($adapter['pad_side'] === 'argument') {
                            foreach ($plan['php_integer_constants'] as $constant => $number) {
                                $files[$path] .= "namespace scpp::php { inline constexpr ::scpp::int64 $constant = $number; }\n";
                            }
                            $parameters[3] .= ' = STR_PAD_RIGHT';
                            $arguments[] = 'true';
                        } else {
                            $arguments[] = 'static_cast<::scpp::int64>(::scpp::str::pad_side::' . $adapter['pad_side'] . ')';
                            $arguments[] = 'false';
                        }
                    }
                    if (isset($adapter['replace_mode'])) {
                        if ($flavor === 'php') $arguments = ['arg2', 'arg0', 'arg1'];
                        $arguments[] = '::scpp::str::replace_mode::' . $adapter['replace_mode'];
                        if (isset($adapter['reference_parameters'])) {
                            $files[$path] .= stringAdapterOverload($flavor, $adapter, $result,
                                array_slice($parameters, 0, $adapter['required_parameters']), $binding['symbol'], $arguments);
                            $arguments[] = 'arg3';
                        }
                    }
                    if (isset($adapter['bound'])) {
                        $short = implode(', ', array_slice($parameters, 0, $binding['required_parameters']));
                        $files[$path] .= "namespace scpp::$flavor {\ninline ::scpp::$result {$adapter['name']}($short)"
                            . " { return ::{$binding['symbol']}(arg0, arg1, 0, ::scpp::str::slice_bound::to_end); }\n}\n";
                        $arguments[] = '::scpp::str::slice_bound::' . $adapter['bound'];
                    }
                    if (isset($adapter['trim_side'])) {
                        $side = '::scpp::str::trim_side::' . $adapter['trim_side'];
                        $default = [$arguments[0], $side, '::scpp::str::trim_set::' . $adapter['trim_set']];
                        if (isset($adapter['parameters'])) {
                            $files[$path] .= stringAdapterOverload($flavor, $adapter, $result, [$parameters[0]], $binding['symbol'], $default);
                            $arguments[] = $side;
                        } else {
                            $arguments = $default;
                        }
                    }
                    $files[$path] .= stringAdapterOverload($flavor, $adapter, $result, $parameters, $binding['symbol'], $arguments);
                    foreach ($binding['php_aliases'] ?? [] as $alias) {
                        $files[$path] .= stringAdapterOverload('php', ['name' => $alias], $result, $parameters, $binding['symbol'], $arguments);
                    }
                }
            }
        }
        $groups[] = ['provider' => $provider, 'declarations' => $h, 'system' => []];
        $definitions .= $d;
    }
}

function renderString(array $plan, array &$groups): array {
    $s = $plan['string'];
    $n = $s['name'];
    $count = $s['count_representation'];
    $lh = template('core/lifecycle_declaration', ['n' => $n]);
    $vars = compact('n', 'count', 'lh') + ['inline_capacity' => $s['inline_capacity']];
    $read = $plan['string_index_read'] ?? null;
    $h = template('strings/string_declaration', $vars + ['view_forward' => $read ? ' class checked_byte_view;' : '']);
    $inputs = [];
    foreach ($plan['conversions'] as $conversion) {
        if ($conversion['target'] === $n) $inputs[] = scalarNativeIdentity($plan, $conversion['source']);
    }
    foreach (NATIVES as $native) {
        if (!in_array($native, $inputs, true)) $h .= "    explicit $n($native) = delete;\n";
    }
    $h .= template('scalars/int128_fences', ['name' => $n]);
    foreach($plan['conversions'] as $c) {
        if($c['target'] === $n) {
            $h .= "    explicit $n(" . scalarInput($c['source']) . " value);\n";
        }
    }
    if ($read) $h .= "    {$read['result']} {$read['name']}({$read['key']} index) const;\n";
    if ($read) $h .= "    ::scpp::detail::checked_byte_view checked_bytes() const &;\n"
        . "    ::scpp::detail::checked_byte_view checked_bytes() const && = delete;\n";
    $write = $plan['string_index_write'] ?? null;
    if ($write) $h .= "    void {$write['name']}({$write['key']} index, {$write['value']} value);\n";
    $h .= template('strings/string_methods_declaration', $vars);
    $classic = array_column($plan['string_functions'] ?? [], 'implementation');
    $usesClassicStorage = array_intersect($classic, ['reverse_bytes', 'ascii_rot13', 'ascii_words', 'newline_breaks', 'add_slashes', 'strip_slashes', 'chunk_bytes', 'wrap_words', 'format_number', 'hex_encode', 'hex_decode', 'repeat', 'ascii_lower', 'ascii_upper', 'replace_bytes', 'pad_bytes', 'join_bytes']);
    $vars['classic_storage'] = ($usesClassicStorage || isset($plan['json'])) ? template('strings/string_own', ['n' => $n]) : '';
    if ($usesClassicStorage) $vars['classic_storage'] .= template('strings/string_classic_storage', ['n' => $n]);
    $d = template('strings/string_storage', $vars) . template('strings/string_methods_definition', $vars);
    if (isset($plan['json'])) {
        $d .= "$n detail::own_string(std::string&& bytes) {\n"
            . "    static_cast<void>(detail::checked_integer_cast<$count>(bytes.size()));\n"
            . "    return string_access::own(std::move(bytes));\n}\n";
    }
    if ($read) {
        $view = template('strings/checked_byte_view', ['n' => $n, 'key' => $read['key'], 'result' => $read['result']]);
        if ($plan['string_view']['provider'] === $s['provider']) $h .= $view;
        else $groups[] = ['provider' => $plan['string_view']['provider'], 'declarations' => "namespace scpp {\n$view}\n", 'system' => []];
        $d .= "void detail::checked_byte_view::invalid_index() { throw std::out_of_range(\"sequence_index_out_of_range\"); }\n";
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
    $typeDeclarations = $h . "}\n";
    $h = "namespace scpp {\n";
    foreach ($plan['explicit_formatters'] ?? [] as $source => $formatter) {
        $signature = "string {$formatter['name']}($source value)";
        $h .= "namespace str { $signature; }\n";
        $body = $formatter['policy'] === 'boolean_words'
            ? 'return value ? string{"true", 4} : string{"false", 5};'
            : "char buffer[128]; const auto size = detail::format_decimal_into(value, buffer); return string{buffer, static_cast<$count>(size)};";
        $d .= "namespace str { $signature { $body } }\n";
    }
    if (array_intersect($classic, ['compare_bytes', 'compare_prefix', 'compare_window'])) $d .= template('strings/string_compare_views');
    $comparisonSymbols = [];
    $windowPositionEmitted = false;
    $sliceMatcher = array_intersect($classic, ['substring_tail', 'ascii_substring_tail']);
    if ($sliceMatcher) $d .= "namespace str {\n" . template('strings/byte_match') . "}\n";
    $spanSymbols = [];
    $searchPolicyEmitted = false;
    $padSymbols = [];
    $trimSymbols = [];
    $replaceSymbols = [];
    foreach ($plan['string_functions'] ?? [] as $function) {
        $name = substr($function['symbol'], strlen('scpp::str::'));
        if (in_array($function['implementation'], STRING_VECTOR_IMPLEMENTATIONS, true)) continue;
        if (in_array($function['implementation'], ['find', 'rfind'], true)) {
            if (!$searchPolicyEmitted) {
                $groups[] = ['provider' => $plan['search_policy']['provider'], 'system' => [],
                    'declarations' => "namespace scpp::str { enum class search_policy { signed_offset, clamp, nonnegative }; }\n"];
                $d .= template('strings/string_search_position', ['byte_match' => $sliceMatcher ? '' : template('strings/byte_match')]);
                $searchPolicyEmitted = true;
            }
            $args = 'const string& subject, const string& needle';
            $method = $function['implementation'];
            $decl = "namespace str { int64 $name($args); int64 $name($args, int64 offset, search_policy policy); }";
            if (isset($function['direct_provider'])) {
                $groups[] = ['provider' => $function['direct_provider'], 'system' => [],
                    'declarations' => "namespace scpp { class string; $decl }\n"];
            } else $h .= "$decl\n";
            $d .= template('strings/string_search', ['name' => $name, 'args' => $args, 'fold' => isset($function['ascii_fold']) ? 'true' : 'false',
                'reverse' => $method === 'rfind' ? 'true' : 'false']);
            continue;
        }
        if ($function['implementation'] === 'pad_bytes') {
            if (!$padSymbols) $h .= "namespace str { enum class pad_side : int64 { left = 0, right = 1, both = 2 }; }\n";
            if (!isset($padSymbols[$name])) {
                $signature = "string $name(const string& subject, int64 width, const string& fill, int64 side, bool reject_empty)";
                $h .= "namespace str { $signature; }\n";
                $d .= template('strings/string_pad', ['signature' => $signature]);
                $padSymbols[$name] = true;
            }
            continue;
        }
        if ($function['implementation'] === 'replace_bytes') {
            if (!$replaceSymbols) $h .= "namespace str { enum class replace_mode { first, all }; }\n";
            $args = 'const string& subject, const string& search, const string& replacement, replace_mode mode';
            if (!isset($replaceSymbols[$name])) {
                $h .= "namespace str { string $name($args); }\n";
                $d .= template('strings/string_replace', ['name' => $name, 'args' => $args]);
                $replaceSymbols[$name] = false;
            }
            if (isset($function['adapters']['php']['reference_parameters']) && !$replaceSymbols[$name]) {
                $h .= "namespace str { string $name($args, int64& count); }\n";
                $d .= template('strings/string_replace_count', ['name' => $name, 'args' => $args]);
                $replaceSymbols[$name] = true;
            }
            continue;
        }
        if (!$windowPositionEmitted && in_array($function['implementation'], ['slice_bytes', 'replace_window'], true)) {
            $d .= template('strings/string_byte_position');
            $windowPositionEmitted = true;
        }
        if ($function['implementation'] === 'format_number') {
            foreach ($function['overloads'][1] as $input => $_) {
                $h .= "namespace str { string $name($input value, int64 decimals, const string& point, const string& group); }\n";
            }
            $d .= template('strings/string_number_format', ['name' => $name]);
            continue;
        }
        if (in_array($function['implementation'], ['compare_bytes', 'compare_prefix'], true)) {
            if (!isset($comparisonSymbols[$name])) {
                $bounded = $function['implementation'] === 'compare_prefix';
                $limit = $bounded ? 'int64 limit, ' : '';
                $signature = "int64 $name(const string& left, const string& right, {$limit}bool_t insensitive)";
                $h .= "namespace str { $signature; }\n";
                $d .= $bounded ? template('strings/string_compare_prefix', ['signature' => $signature])
                    : "namespace str { $signature { return detail::compare_byte_views(detail::string_access::read(left),"
                    . " detail::string_access::read(right), insensitive); } }\n";
                $comparisonSymbols[$name] = true;
            }
            continue;
        }
        if ($function['implementation'] === 'compare_window') {
            $args = 'const string& subject, const string& other, int64 offset';
            $h .= "namespace str { int64 $name($args); int64 $name($args, int64 length, bool_t insensitive = false); }\n";
            $d .= template('strings/string_compare_window', ['name' => $name, 'args' => $args]);
            continue;
        }
        if ($function['implementation'] === 'replace_window') {
            $signature = "string $name(const string& subject, const string& replacement, int64 offset, int64 length)";
            $h .= "namespace str { $signature; }\n";
            $d .= template('strings/string_replace_window', ['signature' => $signature]);
            continue;
        }
        if ($function['implementation'] === 'slice_bytes') {
            $signature = "string $name(const string& value, int64 start, int64 bound, slice_bound mode)";
            $h .= "namespace str { enum class slice_bound { to_end, length, exclusive_end }; $signature; }\n";
            $d .= template('strings/string_slice', ['signature' => $signature]);
            continue;
        }
        if ($function['implementation'] === 'byte_span') {
            if (!isset($spanSymbols[$name])) {
                $signature = "int64 $name(const string& value, const string& mask, int64 offset, int64 length, bool complement)";
                $h .= "namespace str { $signature; }\n";
                $d .= template('strings/byte_span', ['signature' => $signature]);
                $spanSymbols[$name] = true;
            }
            continue;
        }
        if ($function['implementation'] === 'trim_bytes') {
            if (!$trimSymbols) {
                $h .= "namespace str { enum class trim_side { both, start, end }; enum class trim_set { php_space, ascii_space }; }\n";
            }
            if (!isset($trimSymbols[$name])) {
                $signature = "string $name(const string& value, trim_side side, trim_set set)";
                $masked = "string $name(const string& value, const string& mask, trim_side side)";
                $h .= "namespace str { $signature; $masked; }\n";
                $d .= template('strings/string_trim', ['signature' => $signature, 'masked' => $masked, 'count' => $count]);
                $trimSymbols[$name] = true;
            }
            continue;
        }
        $parameters = [];
        foreach ($function['parameters'] as $index => $type) {
            $parameters[] = scalarInput($type) . ' ' . (['value', 'other', 'third', 'fourth'][$index]);
        }
        $signature = "{$function['result']} $name(" . implode(', ', $parameters) . ')';
        if ($function['implementation'] === 'hex_encode' || isset($function['result_header'])) {
            $result = $plan['source_types']->{$function['result']};
            if (isset($function['result_header'])) $h .= "class $result;\n";
            $signature = "$result $name(" . implode(', ', $parameters) . ')';
            $h .= "namespace str { $signature; }\n";
            $match = match ($function['implementation']) {
                'substring_tail' => 'byte_match<false, false>(subject, needle, 0)',
                'ascii_substring_tail' => 'byte_match<false, true>(subject, needle, 0)',
                'last_byte_tail' => "subject.rfind(needle.empty() ? '\\0' : needle.front())",
                'byte_set_tail' => 'subject.find_first_of(needle)',
                default => null,
            };
            $d .= $match === null ? template('strings/' . $function['implementation'], ['signature' => $signature, 'result' => $result])
                : template('strings/search_slice', ['signature' => $signature, 'result' => $result, 'match' => $match,
                    'slice' => isset($function['required_parameters']) ? 'third ? subject.substr(0, match) : subject.substr(match)' : 'subject.substr(match)',
                    'validation' => $function['implementation'] === 'byte_set_tail' ? '    if (needle.empty()) throw ValueError("strpbrk(): characters must be non-empty");' . "\n" : '']);
        } elseif ($function['implementation'] === 'byte_length') {
            $h .= "namespace str { inline $signature { return value.size(); } }\n";
        } elseif ($function['implementation'] === 'count_matches') {
            $args = "const string& value, const string& other";
            $h .= "namespace str { int64 $name($args); int64 $name($args, int64 offset); int64 $name($args, int64 offset, int64 length); }\n";
            $d .= template('strings/string_count_matches', ['name' => $name, 'args' => $args]);
        } elseif ($function['implementation'] === 'checked_single_byte') {
            $h .= "namespace str { $signature; }\n";
            $d .= "namespace str { $signature {\n"
                . "    if (value < 0 || value > 255) throw std::invalid_argument(\"Byte value must be between 0 and 255\");\n"
                . "    const char byte = static_cast<char>(value);\n    return string{&byte, 1};\n} }\n";
        } else {
            if (isset($function['direct_provider'])) {
                $groups[] = ['provider' => $function['direct_provider'], 'system' => [],
                    'declarations' => "namespace scpp { class string; namespace str { $signature; } }\n"];
            } else {
                $h .= "namespace str { $signature; }\n";
            }
            $method = $function['implementation'];
            $bytes = 'detail::string_access::read';
            $body = match ($method) {
                'ascii_lower', 'ascii_upper' => 'return detail::string_access::ascii_case(value, ' . ($method === 'ascii_upper' ? 'true' : 'false')
                    . ', ' . (isset($function['first_byte_only']) ? 'true' : 'false') . ');',
                'first_byte' => 'return value.size() == 0 ? 0 : static_cast<unsigned char>(value.data()[0]);',
                'low_byte' => 'const char byte = static_cast<char>(static_cast<unsigned char>(value)); return string{&byte, 1};',
                'reverse_bytes' => "const auto bytes = $bytes(value); return detail::string_access::own(std::string(bytes.rbegin(), bytes.rend()));",
                'ascii_rot13', 'ascii_words', 'newline_breaks', 'add_slashes', 'strip_slashes', 'chunk_bytes', 'wrap_words' => trim(template('strings/' . $method, [])),
                'repeat' => "return detail::string_access::own(detail::repeat_bytes($bytes(value), other));",
                default => "return $bytes(value).$method($bytes(other));",
            };
            $d .= "namespace str { $signature { $body } }\n";
        }
    }
    if ($plan['string_library']['provider'] === $s['provider']) $typeDeclarations .= $h . "}\n";
    else $groups[] = ['provider' => $plan['string_library']['provider'], 'declarations' => $h . "}\n", 'system' => []];
    return [$typeDeclarations, $d];
}

function renderVectors(array $plan, string $access = 'vector_access'): array {
    $h = !empty($plan['mt_segments']) ? "namespace scpp::mt { struct job; }\n" : '';
    $h .= "namespace scpp {\nnamespace detail { struct $access; }\n";
    $d = '';
    $instances = [];
    foreach($plan['vectors'] as $v) {
        $h .= "class {$v['name']};\n";
        $element = new RuntimeConcreteType($plan, $v['element']);
        $instances[$v['name']] = concreteTemplate(vectorConcreteRecipe($v), ['e' => $element],
            ['mt_segments' => !empty($plan['mt_segments']) ? '1' : '', 'capacity_helpers' => !empty($v['capacity_helpers']) ? '1' : '', 'qual' => ($v['element'] === 'string' || $access !== 'vector_access') ? 'const ' : '', 'ref' => ($v['element'] === 'string' || $access !== 'vector_access') ? '&' : '', 'enable_reserve' => $v['enable_reserve'], 'n' => $v['name'], 'count' => $v['count_type'], 'inline' => '', 'buffer' => 'detail', 'access' => 'detail::' . $access] + $v['traversal'], 'scpp');
        $d .= $instances[$v['name']]['storage'];
    }
    $d .= "namespace detail { struct $access {\n";
    foreach($plan['vectors'] as $v) {
        $d .= $instances[$v['name']]['access'];
    }
    $d .= "}; }\n";
    foreach($plan['vectors'] as $v) {
        $n = $v['name'];
        $e = $v['element'];
        $count = $v['count_type'];
        $vars = compact('n', 'e', 'count') + ['access' => 'detail::' . $access] + $v['traversal'];
        $h .= "class $n final {\npublic:\n" . $instances[$n]['declaration'];
        $d .= $instances[$n]['definition'];
        if (($v['initialization']['bulk'] ?? null) !== null) {
            $init = $v['initialization']['bulk'];
            $bindings = $vars + ['input' => $init['input'], 'read_only' => $init['read_only'], 'inline_limit' => $init['inline_limit'], 'stack_bytes' => $init['stack_bytes']];
            $h .= template('collections/initialization_input_declaration', $bindings + ['guard' => template('collections/initialization_input_guard', $bindings)]);
            $d .= template('collections/initialization_input_definition', $bindings + ['inline' => '']);
            if (isset($init['consume'])) {
                $h .= "    static $n {$init['consume']}($e* values, $count count);\n";
                $d .= template('collections/vector_input_factory', $bindings + ['factory' => $init['consume'], 'qualifier' => '', 'transfer' => 'static_cast<' . $e . '&&>(values[i])']);
            }
            $d .= template('collections/vector_input_factory', $bindings + ['factory' => $init['read_only'], 'qualifier' => 'const ', 'transfer' => 'values[i]']);
        }
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
            $d .= template('collections/vector_cast', $vars + ['src' => $src, 'conversion' => $expression]);
        }
        $indices = [$count];
        $values = [$e];
        if ($v['native_index'] && scalarNativeIdentity($plan, $count) !== 'int') {
            $indices[] = 'int';
            $h .= "    $e at(int index) const;\n";
            $d .= "$e $n::at(int index) const { return at(static_cast<$count>(index)); }\n";
        }
        $native = $v['native_value'];
        if ($native !== '' && scalarNativeIdentity($plan, $e) !== $native) {
            $values[] = $native;
            $h .= "    void append($native value);\n";
            $conversion = concreteNativeScalarConversion($plan, $e, $native, 'value');
            $d .= "void $n::append($native value) { append($conversion); }\n";
        }
        $sets = [];
        foreach ($indices as $index) foreach ($values as $value) {
            $sets[] = [$index, $value];
            if ($index === $count && $value === $e) continue;
            $h .= "    void set($index index, $value value);\n";
            $a = $index === $count ? 'index' : "static_cast<$count>(index)";
            $b = $value === $e ? "static_cast<$e&&>(value)" : concreteNativeScalarConversion($plan, $e, $value, 'value');
            $d .= "void $n::set($index index, $value value) { set($a, $b); }\n";
        }
        $h .= scalarInputFences($plan, 'at', array_map(static fn(string $type): array => [$type], $indices), false, ' const');
        if (!empty($v['capacity_helpers'])) {
            $fill = [[$count, scalarInput($e)]];
            $h .= scalarInputFences($plan, 'filled', $fill, static: true);
            $h .= scalarInputFences($plan, 'resize', $fill);
            $h .= scalarInputFences($plan, 'compact', [[$count]]);
        }
        if ($v['enable_reserve'] === '1') $h .= scalarInputFences($plan, 'reserve', [[$count]]);
        $h .= scalarInputFences($plan, 'remove', [[$count]]);
        $h .= scalarInputFences($plan, 'has', [[$count]], false, ' const');
        $h .= scalarInputFences($plan, 'append', array_map(static fn(string $type): array => [$type], $values));
        $h .= scalarInputFences($plan, 'set', $sets);
        $h .= "private:\n" . $instances[$n]['private_declaration'] . "    struct storage;\n    storage* storage_ = nullptr;\n    friend struct detail::$access;\n};\n";
        foreach($v['operations'] as $op) {
            $sig = "bool_t operator{$op['symbol']}(const $n& left, const $n& right)";
            $h .= "$sig;\n";
            $d .= template('collections/vector_comparison', $vars + ['sig' => $sig, 'equal' => $op['operation'] === 'equal', 'different' => $op['operation'] !== 'equal']);
        }
    }
    foreach ($plan['string_functions'] ?? [] as $binding) {
        if (!in_array($binding['implementation'], STRING_VECTOR_IMPLEMENTATIONS, true)) continue;
        $vector = $plan['source_types']->{'vector<string>'};
        $name = substr($binding['symbol'], strlen('scpp::str::'));
        $signature = $binding['implementation'] === 'split_bytes'
            ? "$vector $name(const string& subject, const string& separator)"
            : "string $name(const $vector& pieces, const string& separator)";
        $h .= "namespace str { $signature; }\n";
        $vars = ['signature' => $signature, 'vector' => $vector];
        if ($binding['implementation'] === 'split_bytes') {
            $vars['limited'] = "$vector $name(const string& subject, const string& separator, int64 limit, split_policy policy)";
            $vars['name'] = $name;
            $h .= "namespace str { enum class split_policy { remainder, cap }; {$vars['limited']}; }\n";
        }
        $d .= template('collections/' . $binding['implementation'], $vars);
    }
    return [$h . "}\n", $d];
}

function renderCarriers(array $plan, array &$groups): array {
    $h = '';
    $payloadHeaders = [];
    foreach ($plan['carriers'] as $carrier) {
        if (isset($carrier['payload_header'])) $payloadHeaders[] = $carrier['payload_header'];
    }
    $payloadHeaders = array_unique($payloadHeaders);
    sort($payloadHeaders, SORT_STRING);
    foreach ($payloadHeaders as $header) $h .= includeHeader($header);
    if (isset($plan['error'])) $h .= includeHeader('scpp/exception.hpp');
    $h .= "namespace scpp {\n";
    if (isset($plan['error'])) $h .= "using error_t = exception;\n";
    $d = '';
    foreach($plan['carriers'] as $c) {
        $n = $c['name'];
        [$class, $implementation] = renderCarrier($plan, $c);
        $d .= $implementation;
        $provider = typeExport($plan['submodules'], $n)['provider'];
        if ($provider === $plan['carrier_publication']['provider']) $h .= $class;
        else $groups[] = ['provider' => $provider, 'system' => [], 'declarations' => "namespace scpp {\n$class}\n"];
    }
    // Define cross-type comparisons after both private storage definitions are complete.
    foreach ($plan['carriers'] as $c) {
        foreach ($c['error_identity_peers'] ?? [] as $peer) {
            $d .= template('carriers/carrier_error_identity', ['n' => $c['name'], 'peer' => $peer]);
        }
    }
    return [$h . "}\n", $d];
}

/** Render one concrete carrier; module-owned payloads can use the same transport in their own archive. */
function renderCarrier(array $plan, array $c): array {
    $d = '';
    $n = $c['name'];
    $p = $c['payload'];
    $result = $c['family'] === 'result';
    $nullable = $c['family'] === 'nullable';
    [$lh, $ld] = lifecycle($n);
    $vars = compact('n', 'p', 'result', 'lh', 'ld') + ['explicit' => str_starts_with($c['extraction'], 'implicit') ? '':'explicit ', 'error_type' => $result ? ', exception':''];
    $constructors = [[$p]];
    if ($nullable) $constructors[] = ['null_t'];
    if ($result) $constructors[] = ['exception'];
    $class = template('carriers/carrier_declaration', $vars) . scalarInputFences($plan, $n, $constructors, true);
    $d .= template('carriers/carrier_storage', $vars) . template('carriers/carrier_value_definition', $vars) . ' ';
    if (!$nullable) {
        $success = array_key_exists('true', $c['states']) ? '!is_false()' : 'has_value()';
        $class .= "    bool_t is_success() const noexcept { return $success; }\n";
    }
    if($result) {
        $d .= "if (!storage_) return $p{}; ";
    }
    $d .= "if (!storage_) throw std::runtime_error(\"carrier_no_value\"); return storage_->content.value(); }\n$n::operator $p() const { return value(); }\n";
    $missingRead = $result ? "static const $p empty{}; return empty;" : 'throw std::runtime_error("carrier_no_value");';
    $missingWrite = $result ? "storage_ = new storage{storage::content_type{" . $p . "{}}};" : $missingRead;
    $d .= "const $p& $n::checked_ref() const { if (!storage_) { $missingRead } return storage_->content.checked_ref(); }\n";
    $d .= "$p& $n::checked_ref() { if (!storage_) { $missingWrite } return storage_->content.checked_ref(); }\n";
    if($nullable) {
        $class .= "    explicit $n(null_t) noexcept;\n";
        $d .= "$n::$n(null_t) noexcept {}\n";
    }
    if($result) {
        $class .= "    explicit $n(exception error);\n    bool_t has_error() const noexcept;\n    exception error() const;\n    bool_t same_error_identity(const $n& other) const;\n";
        $d .= template('carriers/carrier_error_definition', $vars)
            . template('carriers/carrier_error_identity', $vars + ['peer' => $n]);
        foreach ($c['error_identity_peers'] ?? [] as $peer) {
            $class .= "    friend class $peer;\n    bool_t same_error_identity(const class $peer& other) const;\n";
        }
    }
    else {
        $class .= "    void reset() noexcept;\n";
        $d .= "void $n::reset() noexcept { if (storage_) storage_->release(); storage_ = nullptr; }\n";
        if(!$nullable) {
            $class .= "    static $n from_false() noexcept;\n    bool_t is_false() const noexcept;\n";
            $d .= template('carriers/carrier_false_definition', $vars);
        }
        if($c['family'] === 'result_or_bool') {
            $class .= "    static $n from_true();\n    bool_t is_true() const noexcept;\n";
            $d .= template('carriers/carrier_true_definition', $vars);
        }
    }
    $class .= "private:\n    storage* storage_ = nullptr;\n};\n";
    return [$class, $d];
}
