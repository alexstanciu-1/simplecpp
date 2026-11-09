<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/boxed.php';
require_once __DIR__ . '/json.php';

function checkHeader(array $plan, string $header): void {
    $paths = array_values($plan['headers']);
    foreach (['scalar_core', 'scalar_numeric'] as $group) {
        $paths[] = providerHeader($plan['submodules'], $plan['submodules']['groups'][$group]['provider']);
    }
    if (isset($plan['checked_conversions'])) $paths[] = $plan['checked_conversions']['header'];
    foreach (['string', 'hashes', 'datetime', 'boxed', 'json'] as $key) {
        if (isset($plan[$key])) {
            $paths[] = $plan[$key]['header'];
        }
    }
    foreach (['vectors_header', 'carriers_header'] as $key) {
        if (isset($plan[$key])) {
            $paths[] = $plan[$key];
        }
    }
    requireThat(!in_array($header, $paths, true), 'Conflicting public headers');
}

function familyMetadata(mixed $metadata, mixed $publication, string $key, string $requestKey, string $label): mixed {
    if (!property_exists($metadata, $key)) {
        requireThat(!property_exists($publication, $requestKey), "$label publication requires metadata");
        return null;
    }
    return $metadata->{$key};
}

function prepareRuntime(mixed $metadata, mixed $publication, array $facts): array {
    fields($publication, array_merge(SCALAR_FIELDS, ['string', 'vectors', 'carriers', 'ownership', 'hashes', 'datetime', 'boxed', 'json']));
    $scalarRequest = (object)array_intersect_key(object($publication), array_flip(SCALAR_FIELDS));
    $plan = prepareScalarPlan($metadata, $scalarRequest, $facts);
    $registry = registry($metadata, $facts);
    prepareString($metadata, $publication, $registry, $plan);
    prepareCheckedConversions($metadata, $facts, $plan);
    prepareVectors($metadata, $publication, $registry, $plan);
    prepareBoxed($metadata, $publication, $facts, $plan);
    prepareCarriers($metadata, $publication, $plan);
    foreach ($plan['string_functions'] ?? [] as $name => $binding) {
        if (in_array($binding['implementation'], STRING_VECTOR_IMPLEMENTATIONS, true)) {
            $vector = $plan['source_types']->{'vector<string>'} ?? null;
            requireThat($vector !== null, "$name requires a published vector<string>");
            $export = typeExport($plan['submodules'], $vector);
            $plan['string_functions'][$name]['header'] = providerHeader($plan['submodules'], $export['provider']);
        }
        foreach ($binding['adapters'] ?? [] as $flavor => $adapter) {
            if (!isset($adapter['body'])) continue;
            $export = $plan['source_types']->{$adapter['result']} ?? null;
            requireThat($export !== null, "$name requires published " . $adapter['result']);
            $plan['string_functions'][$name]['adapters'][$flavor]['result'] = $export;
        }
    }
    prepareHashes($metadata, $publication, $plan);
    prepareDatetime($metadata, $publication, $plan);
    prepareJson($metadata, $publication, $plan);
    prepareOwnership($metadata, $publication, $facts, $plan);
    sortConversions($plan['conversions']);
    finalizePublicationProviders($plan);
    return $plan;
}

function prepareString(mixed $metadata, mixed $publication, array $registry, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'string', 'string', 'String');
    if ($m === null) {
        return;
    }
    fields($m, ['type', 'source_type', 'storage', 'count_type', 'construction', 'null_pointer', 'text_casts', 'operations', 'provider', 'functions', 'trim_defaults', 'search_offsets', 'explicit_formatters', 'index_read', 'index_write']);
    literals($m, ['type' => 'string', 'source_type' => 'string', 'storage' => 'inline_22_private_std_string', 'construction' => 'explicit', 'null_pointer' => 'reject']);
    $count = textValue(at($m, 'count_type'));
    requireThat($registry[$count]['signed'] ?? false, 'String count requires a signed integer type');
    $caps = names(at($m, 'operations'), 'Duplicate string operation');
    foreach ($caps as $op) {
        requireThat(in_array($op, array_merge(['add', 'add_assign'], COMPARISONS), true), 'Unsupported string operation');
    }
    $casts = names(at($m, 'text_casts'), 'Duplicate text cast policy');
    foreach ($casts as $cast) {
        requireThat(in_array($cast, ['decimal_integer', 'decimal_float', 'boolean_words'], true), 'Unsupported text cast policy');
    }
    if (!property_exists($publication, 'string')) {
        return;
    }
    $p = $publication->string;
    fields($p, ['header', 'operations', 'casts', 'functions', 'explicit_formatters', 'index_read', 'index_write']);
    $provider = textValue(at($m, 'provider'));
    $header = providerHeader($plan['submodules'], $provider);
    if (isset($p->header)) requireThat(headerPath($p->header) === $header, 'String publication header conflicts with provider');
    checkHeader($plan, $header);
    requireThat(hasValue($plan, 'bool_t'), 'String requires published boolean type');
    $operations = [];
    foreach (names(at($p, 'operations'), 'Duplicate published string operation') as $op) {
        requireThat(in_array($op, $caps, true), 'String operation is not enabled by metadata');
        $append = in_array($op, ['add', 'add_assign'], true);
        $mutates = $op === 'add_assign';
        $operations[] = ['operation' => $op, 'symbol' => $mutates ? '+=':SYMBOLS[$op], 'form' => $mutates ? 'compound':'binary', 'implementation' => $append ? 'string_append':'string_compare', 'execution' => 'string', 'result' => $append ? 'string':'bool_t', 'left' => 'string', 'right' => 'string', 'result_category' => $mutates ? 'left_reference':'value'];
    }
    foreach (names(at($p, 'casts'), 'Duplicate published text cast') as $target) {
        requireThat(!in_array($target, names(at($metadata, 'deferred_text_casts')), true), 'Text conversion for this integer is deferred');
        $policy = hasType($plan, $target) ? 'decimal_integer':($target === 'float64' && hasValue($plan, $target) ? 'decimal_float':($target === 'bool_t' && hasValue($plan, $target) ? 'boolean_words':''));
        requireThat($policy !== '', 'Text cast requires a published numeric or boolean type');
        requireThat(in_array($policy, $casts, true), 'Text cast is not enabled by metadata');
        $plan['conversions'][] = conversion($target, 'string', false, false, $target === 'bool_t' ? 'parse_boolean_text':'parse_decimal', true);
        $plan['conversions'][] = conversion('string', $target, false, $target !== 'float64', $target === 'bool_t' ? 'format_boolean_text':'format_decimal', true);
    }
    $methods = [];
    $countRepresentation = $registry[$count]['representation'];
    foreach (['size' => $countRepresentation, 'empty' => 'bool_t', 'is_inline' => 'bool_t', 'data' => 'const char*', 'append' => 'void', 'clear' => 'void'] as $name => $result) {
        $methods[] = ['name' => $name, 'result' => $result, 'receiver' => in_array($name, ['append', 'clear'], true) ? 'mutable_reference':'const_reference', 'arguments' => $name === 'append' ? ['const string&']:[], 'result_category' => $name === 'data' ? 'borrowed_bytes_until_mutation_or_destruction':'value', 'can_fail' => in_array($name, ['size', 'append'], true)];
    }
    $requires = array_merge(['bool_t'], names(at($p, 'casts')));
    $symbols = [];
    $memberNames = [];
    foreach (names($p->functions ?? [], 'Duplicate string function') as $name) {
        $function = at(at($m, 'functions'), $name);
        fields($function, ['parameters', 'result', 'symbol', 'implementation', 'members', 'flavor_adapters', 'signatures', 'side', 'required_parameters', 'limit_policy', 'bound', 'replace_mode', 'pad_side', 'first_byte_only', 'flavors', 'constants', 'free_names']);
        $implementation = textValue(at($function, 'implementation'));
        $signature = match ($implementation) {
            'split_bytes' => ['parameters' => ['string', 'string', 'int64'], 'result' => 'vector<string>', 'required_parameters' => 2],
            'join_bytes' => ['parameters' => ['vector<string>', 'string'], 'result' => 'string'],
            'byte_length' => ['parameters' => ['string'], 'result' => $count],
            'checked_single_byte' => ['parameters' => ['int64'], 'result' => 'string'],
            'count_matches' => ['parameters' => ['string', 'string', 'int64', 'int64'], 'result' => 'int64', 'required_parameters' => 2],
            'find', 'rfind' => ['parameters' => ['string', 'string', 'int64'], 'result' => 'int64', 'required_parameters' => 2],
            'ascii_lower', 'ascii_upper', 'trim_bytes' => ['parameters' => ['string'], 'result' => 'string'],
            'pad_bytes' => ['parameters' => ($function->pad_side ?? null) === 'argument'
                ? ['string', 'int64', 'string', 'int64'] : ['string', 'int64', 'string'], 'result' => 'string', 'required_parameters' => 2],
            'replace_window' => ['parameters' => ['string', 'string', 'int64', 'int64'], 'required_parameters' => 3, 'result' => 'string'],
            'replace_bytes' => ['parameters' => ['string', 'string', 'string'], 'result' => 'string'],
            'slice_bytes' => ['parameters' => ['string', 'int64', 'int64'], 'result' => 'string', 'required_parameters' => 2],
            'repeat' => ['parameters' => ['string', 'int64'], 'result' => 'string'],
            'starts_with', 'ends_with', 'contains' => ['parameters' => ['string', 'string'], 'result' => 'bool_t'],
            default => null,
        };
        requireThat($signature !== null, 'Unknown string function implementation');
        literals($function, $signature);
        $symbol = textValue(at($function, 'symbol'));
        requireThat(preg_match('/^[a-z_][a-z0-9_]*$/D', $name) === 1
            && preg_match('/^scpp::str::[a-z_][a-z0-9_]*$/D', $symbol) === 1, 'Invalid string function name');
        foreach ([...$signature['parameters'], $signature['result']] as $type) {
            if ($type === 'string' || $type === 'vector<string>') continue;
            requireThat(hasType($plan, $type) || hasValue($plan, $type), 'String function requires a published parameter/result type');
            $requires[] = $type;
        }
        $members = isset($function->members) ? fields($function->members, ['cpp', 'js']) : [];
        $freeNames = isset($function->free_names) ? fields($function->free_names, ['cpp', 'js']) : [];
        requireThat(!array_intersect_key($members, $freeNames), 'Duplicate flavor spelling');
        requireThat(!$members || in_array($signature['parameters'][0], ['string', 'vector<string>'], true),
            'Invalid string member receiver');
        foreach ($members + $freeNames as $flavor => $member) {
            requireThat(preg_match('/^[a-zA-Z_][a-zA-Z_0-9]*$/D', textValue($member)) === 1
                && !isset($memberNames[$flavor][$member]), 'Invalid or duplicate string member binding');
            $memberNames[$flavor][$member] = true;
        }
        $padding = $implementation === 'pad_bytes';
        requireThat($padding === isset($function->pad_side), 'Padding requires a side');
        $spellings = ['php' => $name] + $members + $freeNames;
        $firstByte = boolean($function->first_byte_only ?? false);
        requireThat(!isset($function->first_byte_only) || in_array($implementation, ['ascii_lower', 'ascii_upper'], true),
            'First-byte case requires an ASCII case operation');
        $phpOnly = $implementation === 'replace_window' || $firstByte;
        requireThat(($padding || $phpOnly) === isset($function->flavors), 'Invalid string adapter flavor selection');
        if (in_array($implementation, STRING_VALUE_ERROR_IMPLEMENTATIONS, true)) checkHeader($plan, 'scpp/value_error.hpp');
        if ($padding) {
            $side = $function->pad_side;
            requireThat(in_array($side, ['argument', 'left', 'right'], true), 'Invalid padding side');
        }
        if ($padding || $phpOnly) {
            $expected = ($phpOnly || $side === 'argument') ? ['php'] : ['cpp', 'js'];
            literals($function, ['flavors' => $expected, 'flavor_adapters' => true]);
            $spellings = array_intersect_key($spellings, array_flip($expected));
            requireThat(count($spellings) === count($expected), 'Missing flavor spelling');
        }
        requireThat(($padding && $side === 'argument') === isset($function->constants), 'PHP padding publishes side constants');
        if (isset($function->constants)) {
            $values = fields($function->constants, ['STR_PAD_LEFT', 'STR_PAD_RIGHT', 'STR_PAD_BOTH']);
            literals($function->constants, ['STR_PAD_LEFT' => 0, 'STR_PAD_RIGHT' => 1, 'STR_PAD_BOTH' => 2]);
            $plan['php_integer_constants'] = $values;
        }
        $search = in_array($implementation, ['find', 'rfind'], true);
        if ($search) {
            $offsets = fields(at($m, 'search_offsets'), ['php', 'js', 'cpp']);
            requireThat(($function->flavor_adapters ?? false) === true, 'Search requires flavor adapters');
        }
        $slice = $implementation === 'slice_bytes';
        $split = $implementation === 'split_bytes';
        requireThat($split === isset($function->limit_policy), 'Split bindings require a limit policy');
        if ($split) $limits = fields($function->limit_policy, ['php', 'cpp', 'js']);
        requireThat(isset($signature['required_parameters']) === isset($function->required_parameters) && $slice === isset($function->bound), 'Invalid optional string function parameters');
        if ($slice) {
            $bounds = fields($function->bound, ['php', 'cpp', 'js']);
            requireThat(($function->flavor_adapters ?? false) === true, 'Slice requires flavor adapters');
        }
        $replace = $implementation === 'replace_bytes';
        requireThat($replace === property_exists($function, 'replace_mode'), 'Replacement bindings require a mode');
        if ($replace) {
            requireThat(in_array($function->replace_mode, ['first', 'all'], true), 'Invalid replacement mode');
            requireThat(($function->flavor_adapters ?? false) === true, 'Replacement requires flavor adapters');
        }
        $trim = $implementation === 'trim_bytes';
        requireThat($trim === property_exists($function, 'side'), 'Trim direction required only for trim bindings');
        if ($trim) {
            $side = textValue($function->side);
            requireThat(in_array($side, ['both', 'start', 'end'], true), 'Unknown trim direction');
            $defaults = fields(at($m, 'trim_defaults'), ['php', 'js', 'cpp']);
            requireThat(($function->flavor_adapters ?? false) === true, 'Default trim requires flavor adapters');
        }
        requireThat(!isset($symbols[$symbol]) || (($trim || $replace || $padding) && $symbols[$symbol] === $implementation), 'Duplicate string function binding');
        $symbols[$symbol] = $implementation;
        $plan['string_functions'][$name] = $signature + [
            'symbol' => $symbol, 'implementation' => $implementation, 'header' => $header, 'members' => $members];
        if ($firstByte) $plan['string_functions'][$name]['first_byte_only'] = true;
        $enabled = boolean($function->flavor_adapters ?? false);
        requireThat($enabled || !in_array($implementation, STRING_VECTOR_IMPLEMENTATIONS, true),
            'String/vector operations require flavor adapters');
        $signatures = isset($function->signatures) ? fields($function->signatures, array_keys(['php' => $name] + $members)) : [];
        requireThat(!$signatures || $enabled, 'Flavor signatures require generated adapters');
        if ($enabled) {
            requireThat($members !== [] || isset($function->flavors), 'Flavor adapters require flavor-specific string spellings');
            foreach ($spellings as $flavor => $spelling) {
                $options = [];
                if ($split) {
                    $policy = $limits[$flavor] ?? null;
                    requireThat(in_array($policy, ['remainder', 'uint32_cap', 'cap'], true), 'Unknown split limit policy');
                    $options['limit_policy'] = $policy;
                }
                if ($padding) $options['pad_side'] = $side;
                if ($replace) {
                    $options['replace_mode'] = $function->replace_mode;
                }
                if ($slice) {
                    $bound = $bounds[$flavor] ?? null;
                    requireThat(in_array($bound, ['length', 'exclusive_end'], true), 'Unknown slice bound');
                    $options['bound'] = $bound;
                }
                if ($trim) {
                    $set = $defaults[$flavor] ?? null;
                    requireThat(in_array($set, ['php_space', 'ascii_space'], true), 'Unknown default trim byte set');
                    $options = ['trim_side' => $side, 'trim_set' => $set];
                }
                if (array_key_exists($flavor, $signatures)) {
                    if ($trim) {
                        requireThat($flavor === 'php', 'Explicit trim masks require the PHP flavor');
                        $options += fields($signatures[$flavor], ['parameters', 'required_parameters']);
                        literals($signatures[$flavor], ['parameters' => ['string', 'string'], 'required_parameters' => 1]);
                    } elseif ($replace) {
                        requireThat($flavor === 'php' && $function->replace_mode === 'all', 'Count output requires PHP replace-all');
                        $options += fields($signatures[$flavor], ['parameters', 'required_parameters', 'reference_parameters']);
                        literals($signatures[$flavor], ['parameters' => ['string', 'string', 'string', 'int64'],
                            'required_parameters' => 3, 'reference_parameters' => [3]]);
                        requireThat(hasType($plan, 'int64'), 'Replacement count requires int64');
                        $requires[] = 'int64';
                    } else {
                        $options += fields($signatures[$flavor], ['result', 'body']);
                        literals($signatures[$flavor], ['result' => 'result_or_false<int64>', 'body' => 'search_offset_or_false']);
                        requireThat(in_array($implementation, ['find', 'rfind'], true), 'Offset body requires byte search');
                    }
                }
                if ($search) {
                    $policy = $offsets[$flavor] ?? null;
                    requireThat(in_array($policy, ['signed_offset', 'clamp', 'nonnegative'], true), 'Unknown search offset policy');
                    $options['search_offset'] = $policy;
                }
                if (isset($freeNames[$flavor])) $options['free'] = true;
                if ($flavor === 'php' && in_array($implementation, STRING_VECTOR_IMPLEMENTATIONS, true)) $options['swap_first_two'] = true;
                checkHeader($plan, stringAdapterHeader($flavor, $implementation));
                $plan['string_functions'][$name]['adapters'][$flavor] = [
                    'name' => $spelling, 'symbol' => "scpp::$flavor::$spelling", 'header' => stringAdapterHeader($flavor, $implementation)] + $options;
            }
        }
    }
    foreach (['index_read' => 'result', 'index_write' => 'value'] as $operation => $element) {
        if (!boolean($p->$operation ?? false)) continue;
        $row = at($m, $operation);
        fields($row, ['method', 'key', $element, 'policy']);
        literals($row, ['policy' => 'checked_byte_offset', 'key' => $count, $element => 'uint8']);
        $method = textValue(at($row, 'method'));
        requireThat(preg_match('/^[a-z][a-z0-9_]*$/D', $method) === 1 && !in_array($method, array_column($methods, 'name'), true), 'Invalid string index method');
        requireThat(hasType($plan, $row->key) && hasType($plan, $row->$element), 'String indexing requires published key and byte types');
        $plan['string_' . $operation] = ['name' => $method, 'key' => $row->key, $element => $row->$element];
        $writes = $operation === 'index_write';
        $methods[] = ['name' => $method, 'receiver' => $writes ? 'mutable_reference' : 'const_reference',
            'arguments' => $writes ? [$row->key, $row->$element] : [$row->key],
            'result' => $writes ? 'void' : $row->$element, 'result_category' => 'value', 'can_fail' => true];
        array_push($requires, $row->key, $row->$element);
    }
    foreach (names($p->explicit_formatters ?? [], 'Duplicate explicit formatter') as $source) {
        $row = at(at($m, 'explicit_formatters'), $source);
        fields($row, ['policy', 'symbol']);
        $policy = hasType($plan, $source) ? 'decimal_integer' : match ($source) {
            'bool_t' => 'boolean_words', 'float64' => 'decimal_float', default => null,
        };
        requireThat((hasType($plan, $source) || hasValue($plan, $source)) && in_array($policy, $casts, true),
            'Explicit formatter requires a published scalar and enabled text policy');
        literals($row, ['policy' => $policy]);
        $name = textValue(at($row, 'symbol'));
        requireThat(preg_match('/^[a-z_][a-z0-9_]*$/D', $name) === 1, 'Invalid formatter symbol');
        $symbol = "scpp::str::$name";
        requireThat(!isset($symbols[$symbol]), 'Duplicate string binding');
        $symbols[$symbol] = true;
        $plan['explicit_formatters'][$source] = ['policy' => $policy, 'name' => $name, 'symbol' => $symbol, 'header' => $header];
        $requires[] = $source;
    }
    $requirements = exportProviders($plan['submodules'], $requires);
    $plan['source_types']->string = 'string';
    $plan['string'] = ['provider' => $provider, 'requires' => $requirements, 'name' => 'string', 'count_type' => $count, 'count_representation' => $countRepresentation, 'header' => $header, 'storage' => 'inline_22_private_std_string', 'lifecycle' => 'owned_copy_noexcept_move', 'inline_capacity' => 22, 'empty_state' => 'inline', 'operations' => $operations, 'methods' => $methods, 'construction' => [['arguments' => ['const char*'], 'explicit' => true, 'null_pointer' => 'reject', 'can_fail' => true], ['arguments' => ['const char*', $countRepresentation], 'explicit' => true, 'null_pointer' => 'reject', 'can_fail' => true]]];
}

function prepareVectors(mixed $metadata, mixed $publication, array $registry, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'vector', 'vectors', 'Vector');
    if ($m === null) {
        return;
    }
    fields($m, ['provider', 'source_type', 'storage', 'count_type', 'read', 'mutation', 'element_families', 'native_index', 'native_value', 'operations']);
    literals($m, ['source_type' => 'vector', 'storage' => 'private_std_vector', 'read' => 'value_copy', 'mutation' => 'explicit_set', 'native_index' => 'int']);
    $count = textValue(at($m, 'count_type'));
    requireThat($registry[$count]['signed'] ?? false, 'Vector count requires signed integer type');
    $families = names(at($m, 'element_families'), 'Duplicate vector element family');
    foreach($families as $f) {
        requireThat(in_array($f, ['integer', 'floating', 'boolean', 'null', 'string'], true), 'Unsupported vector element family');
    }
    $ops = names(at($m, 'operations'), 'Duplicate vector operation');
    foreach($ops as $op) {
        requireThat(in_array($op, ['equal', 'not_equal'], true), 'Unsupported vector operation');
    }
    $natives = names(at($m, 'native_value'), 'Duplicate vector native value');
    foreach($natives as $n) {
        requireThat(in_array($n, ['int', 'const char*'], true), 'Unsupported vector native value');
    }
    if(!property_exists($publication, 'vectors')) {
        return;
    }
    $p = $publication->vectors;
    fields($p, ['header', 'specializations']);
    $provider = $plan['submodules']['groups']['vectors']['provider'];
    $header = providerHeader($plan['submodules'], $provider);
    if (isset($p->header)) requireThat(headerPath($p->header) === $header, 'Vector publication header conflicts with provider');
    requireThat(hasType($plan, $count) && hasNative($plan, 'long long'), 'Vector requires published count and native long long construction');
    requireThat(hasValue($plan, 'bool_t'), 'Vector requires published boolean type');
    $vectors = [];
    $demands = [];
    foreach(listValue(at($p, 'specializations')) as $item) {
        fields($item, ['element', 'native_index', 'native_value', 'operations', 'casts_from']);
        $element = textValue(at($item, 'element'));
        $family = hasType($plan, $element) ? 'integer':($element === 'string' && isset($plan['string']) ? 'string':'');
        foreach($plan['values'] as $v) {
            if($v['name'] === $element) {
                $family = $v['kind'];
            }
        }
        requireThat($family !== '' && in_array($family, $families, true), 'Vector requires supported published element type');
        $name = 'vector_' . $element;
        requireThat(!isset($vectors[$name]), 'Duplicate vector specialization');
        $nativeIndex = boolean(at($item, 'native_index'));
        requireThat(!$nativeIndex || hasNative($plan, 'int'), 'Vector native index requires int construction');
        $native = textValue(at($item, 'native_value'));
        if($native !== 'none') {
            requireThat(in_array($native, $natives, true), 'Vector native value is not enabled');
            requireThat($native === 'const char*' ? $family === 'string':hasCast($plan, $element, 'int', true), $native === 'const char*' ? 'C-string adapter requires string element':'Vector int adapter requires checked element construction');
        }
        $selected = names(at($item, 'operations'), 'Duplicate published vector operation');
        foreach($selected as $op) {
            requireThat(in_array($op, $ops, true), 'Vector operation is not enabled');
        }
        if($selected !== []) {
            $equality = false;
            if($family === 'string') {
                $equality = in_array('equal', array_column($plan['string']['operations'], 'operation'), true);
            }
            elseif(in_array($family, ['integer', 'floating'], true)) {
                foreach($plan['operations'] as $op) {
                    if($op['operation'] === 'equal' && $op['left']['identity'] === $element && !$op['left']['native'] && ($op['right']['identity'] ?? '') === $element && !$op['right']['native']) {
                        $equality = true;
                    }
                }
            }
            else {
                foreach($plan['values'] as $v) {
                    if($v['name'] === $element) {
                        $equality = in_array('equal', $v['operations'], true);
                    }
                }
            }
            requireThat($equality, 'Vector equality requires published element equality');
        }
        $demands[$name] = names(at($item, 'casts_from'), 'Duplicate vector cast');
        requireThat(!in_array($name, $demands[$name], true), 'Vector identity uses copy construction');
        $operations = array_map(fn(string $op):array => ['operation' => $op, 'symbol' => $op === 'equal' ? '==':'!=', 'implementation' => 'elementwise_equality', 'element_dependency' => 'operator==', 'result' => 'bool_t'], $selected);
        $methods = [];
        $add = function(string $name, string $result, array $arguments, bool $mutates, string $adaptation) use (&$methods):void {
            $methods[] = compact('name', 'result', 'arguments', 'adaptation') + ['receiver' => $mutates ? 'mutable_reference':'const_reference', 'result_category' => 'value'];
        };
        $add('size', $count, [], false, 'checked_count');
        $add('empty', 'bool_t', [], false, 'none');
        $add('clear', 'void', [], true, 'none');
        $indices = $nativeIndex ? [$count, 'int']:[$count];
        $values = $native === 'none' ? [$element]:[$element, $native];
        foreach($indices as $index) {
            $add('at', $element, [$index], false, $index === $count ? 'checked_index_copy':'checked_native_index_copy');
            foreach($values as $value) {
                $add('set', 'void', [$index, $value], true, $index === $count && $value === $element ? 'checked_index_move_value':'explicit_arguments_then_checked_set');
            }
        }
        foreach($values as $value) {
            $add('append', 'void', [$value], true, $value === $element ? 'move_value':'explicit_element_then_append');
        }
        $vectors[$name] = ['name' => $name, 'element' => $element, 'source_type' => "vector<$element>", 'count_type' => $count, 'storage' => 'private_std_vector', 'lifecycle' => 'owned_copy_noexcept_move', 'read' => 'checked_index_value_copy', 'set' => 'checked_index_move_value', 'native_index' => $nativeIndex, 'native_value' => $native === 'none' ? '':$native, 'operations' => $operations, 'methods' => $methods, 'casts_from' => []];
    }
    requireThat($vectors !== [], 'Empty vector publication');
    ksort($vectors, SORT_STRING);
    foreach($vectors as $name => &$v) {
        foreach($demands[$name] as $source) {
            requireThat(isset($vectors[$source]), 'Vector cast requires published source specialization');
            $elementSource = $vectors[$source]['element'];
            requireThat(hasCast($plan, $v['element'], $elementSource), 'Vector cast requires published explicit element conversion');
            $v['casts_from'][] = ['source' => $source, 'explicit' => true, 'element_source' => $elementSource, 'element_target' => $v['element'], 'implementation' => 'elementwise_explicit_conversion', 'failure' => 'discard_partial_target_keep_source'];
        }
        $plan['source_types']->{$v['source_type']} = $name;
    }
    unset($v);
    $plan['vectors'] = array_values($vectors);
    $plan['vectors_header'] = $header;
    publishTypeExports($plan['submodules'], 'vectors', array_keys($vectors));
    $plan['vector_publication'] = ['provider' => $provider,
        'requires' => exportProviders($plan['submodules'], array_merge([$count, 'bool_t'], array_column($vectors, 'element')))];
}
// Resolve published payload identities before carrier admission. This does not
// recursively synthesize dependencies or infer payloads from requested names.
function carrierPayload(array $plan, string $name): ?array {
    if (hasType($plan, $name) || ($name !== 'null_t' && hasValue($plan, $name))) return ['kind' => 'scalar'];
    if ($name === 'string' && isset($plan['string'])) return ['kind' => 'string'];
    if (in_array($name, array_column($plan['vectors'], 'name'), true)) return ['kind' => 'vector'];
    foreach ($plan['boxed']['families'] ?? [] as $family) {
        if ($family['name'] === $name) return ['kind' => 'boxed', 'header' => $plan['boxed']['header'], 'copy' => $family['copy']];
    }
    return null;
}

const CARRIER_DEFAULTS = ['nullable' => 'empty', 'result' => 'value', 'result_or_false' => 'false', 'result_or_bool' => 'false'];
function prepareCarriers(mixed $metadata, mixed $publication, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'carriers', 'carriers', 'Carrier');
    if($m === null) {
        return;
    }
    fields($m, ['provider', 'extraction', 'payload_access', 'presence', 'sentinels', 'construction', 'families', 'error']);
    $extraction = textValue(at($m, 'extraction'));
    $payloadAccess = textValue(at($m, 'payload_access'));
    requireThat($payloadAccess === 'value', 'Unsupported carrier payload access binding');
    requireThat(in_array($extraction, ['implicit_checked_one_layer_value_copy', 'explicit_checked_one_layer_value_copy'], true), 'Unsupported carrier extraction policy');
    literals($m, ['construction' => 'explicit']);
    $error = at($m, 'error');
    fields($error, ['type', 'message', 'line', 'file', 'state']);
    literals($error, ['type' => 'error_t', 'message' => 'string', 'line' => 'int64', 'file' => 'string', 'state' => 'explicit_present']);
    $families = at($m, 'families');
    fields($families, array_keys(CARRIER_DEFAULTS));
    $familyPlans = [];
    foreach(object($families) as $f => $policy) {
        fields($policy, ['default_state', 'states']);
        $default = textValue(at($policy, 'default_state'));
        requireThat($default === CARRIER_DEFAULTS[$f], 'Unsupported carrier default state');
        $states = object(at($policy, 'states'));
        $expected = match ($f) {
            'nullable' => ['value' => 'payload', 'empty' => null],
            'result' => ['value' => 'payload', 'error' => 'error_t'],
            'result_or_false' => ['value' => 'payload', 'false' => null],
            'result_or_bool' => ['value' => 'payload', 'false' => null, 'true' => null],
        };
        ksort($states); ksort($expected);
        requireThat($states === $expected, 'Unsupported carrier states');
        $familyPlans[$f] = ['default_state' => $default, 'states' => $states];
    }
    fields(at($m, 'presence'), ['value', 'true']);
    literals($m->presence, ['value' => 'has_value', 'true' => 'is_true']);
    $plan['carrier_families'] = $familyPlans;
    $sentinels = at($m, 'sentinels');
    fields($sentinels, ['false', 'true']);
    $plan['carrier_sentinels'] = [];
    foreach (object($sentinels) as $state => $sentinel) {
        fields($sentinel, ['literal', 'factory']);
        literals($sentinel, ['literal' => $state . '_sentinel', 'factory' => 'from_' . $state]);
        $plan['carrier_sentinels'][$state] = object($sentinel);
    }
    if(!property_exists($publication, 'carriers')) {
        return;
    }
    $p = $publication->carriers;
    fields($p, ['header', 'specializations']);
    $provider = textValue(at($m, 'provider'));
    $header = providerHeader($plan['submodules'], $provider);
    if (isset($p->header)) requireThat(headerPath($p->header) === $header, 'Carrier publication header conflicts with provider');
    requireThat(hasValue($plan, 'bool_t'), 'Carriers require published boolean type');
    $carriers = [];
    foreach(listValue(at($p, 'specializations')) as $item) {
        fields($item, ['family', 'payload']);
        $family = textValue(at($item, 'family'));
        at($families, $family);
        $payload = textValue(at($item, 'payload'));
        requireThat($family !== 'nullable' || hasValue($plan, 'null_t'), 'Nullable requires published null_t');
        $payloadInfo = carrierPayload($plan, $payload);
        requireThat($payloadInfo !== null, 'Carrier payload must be a published scalar, string, vector or supported boxed family');
        requireThat($payloadInfo['kind'] !== 'boxed' || $family === 'result', 'Boxed payloads currently require the result family');
        requireThat($family !== 'result' || (isset($plan['string']) && hasType($plan, 'int64')), 'Result error payload requires string and int64');
        $name = $family . '_' . $payload;
        requireThat(!isset($carriers[$name]), 'Duplicate carrier specialization');
        $carriers[$name] = compact('name', 'family', 'payload', 'extraction') + ['payload_access' => $payloadAccess, 'presence' => object($m->presence)] + $familyPlans[$family] + [ 'boxing' => 'explicit', 'extraction_can_fail' => true, 'non_payload_extraction' => 'throw_runtime_error', 'condition' => 'cpp_payload_conversion_rules', 'lifecycle' => 'owned_copy_move_resets_source_to_default', 'lifted_operators' => false, 'result_category' => 'value', 'explicit_lowering' => 'value_then_payload_operation'];
        if ($payloadInfo['kind'] === 'boxed') {
            $carriers[$name]['payload_header'] = $payloadInfo['header'];
            $carriers[$name]['payload_copy'] = $payloadInfo['copy'];
        }
        $plan['source_types']->{"$family<$payload>"} = $name;
        if($family === 'result') {
            $plan['error'] = ['type' => 'error_t', 'state' => 'explicit_present', 'message' => 'string', 'line' => 'int64', 'file' => 'string', 'inspection' => 'value_copy', 'global_short_name' => false];
        }
    }
    requireThat($carriers !== [], 'Empty carrier publication');
    ksort($carriers, SORT_STRING);
    $plan['carriers'] = array_values($carriers);
    $plan['carriers_header'] = $header;
    $exports = array_keys($carriers);
    if (isset($plan['error'])) $exports[] = 'error_t';
    publishTypeExports($plan['submodules'], 'carriers', $exports);
    $dependencies = ['bool_t'];
    foreach ($carriers as $carrier) {
        if (!isset($carrier['payload_header'])) $dependencies[] = $carrier['payload'];
        if ($carrier['family'] === 'nullable') $dependencies[] = 'null_t';
        if ($carrier['family'] === 'result') $dependencies = array_merge($dependencies, ['string', 'int64']);
    }
    $plan['carrier_publication'] = ['provider' => $provider,
        'requires' => exportProviders($plan['submodules'], array_values(array_unique($dependencies)))];
}

function prepareHashes(mixed $metadata, mixed $publication, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'hash', 'hashes', 'Hash');
    if($m === null) {
        return;
    }
    fields($m, ['provider', 'source_type', 'binding', 'construction', 'key_types', 'value_types', 'count_type', 'key_policy', 'read', 'optional_lookup', 'append']);
    literals($m, ['source_type' => 'hash', 'binding' => 'ordered_typed_swiss', 'construction' => 'empty', 'count_type' => 'int64', 'key_policy' => 'strict', 'read' => 'value_copy', 'optional_lookup' => 'nullable', 'append' => 'max_live_nonnegative_plus_one']);
    foreach(['key_types', 'value_types'] as $key) {
        requireThat(names(at($m, $key), 'Duplicate hash type policy') === ['int64', 'string'], 'Unsupported hash type policy');
    }
    if(!property_exists($publication, 'hashes')) {
        return;
    }
    $p = $publication->hashes;
    fields($p, ['header', 'specializations']);
    $provider = textValue(at($m, 'provider'));
    $header = providerHeader($plan['submodules'], $provider);
    if (isset($p->header)) requireThat(headerPath($p->header) === $header, 'Hash publication header conflicts with provider');
    requireThat(hasType($plan, 'int64'), 'Hash requires published int64 count');
    requireThat(hasValue($plan, 'bool_t'), 'Hash requires published bool_t');
    $hashes = [];
    foreach(listValue(at($p, 'specializations')) as $item) {
        fields($item, ['key', 'value']);
        $key = textValue(at($item, 'key'));
        $value = textValue(at($item, 'value'));
        foreach([$key, $value] as $type) {
            requireThat(in_array($type, ['int64', 'string'], true), 'Hash supports only int64/string keys and values');
            requireThat($type !== 'string' || isset($plan['string']), 'Hash requires published string');
        }
        $nullable = 'nullable_' . $value;
        requireThat(in_array($nullable, array_column($plan['carriers'] ?? [], 'name'), true), 'Hash find requires published nullable payload');
        $name = "hash_{$value}_$key";
        requireThat(!property_exists($plan['aliases'], $name), 'Conflicting hash name');
        requireThat(!isset($hashes[$name]), 'Duplicate hash specialization');
        $methods = [];
        foreach([['size', [], 'int64', false], ['empty', [], 'bool_t', false], ['has', ["const $key&"], 'bool_t', false], ['at', ["const $key&"], $value, false], ['find', ["const $key&"], $nullable, false], ['set', [$key, $value], 'void', true], ['remove', ["const $key&"], 'bool_t', true], ['clear', [], 'void', true]] as [$method, $arguments, $result, $mutates]) {
            $row = ['name' => $method] + compact('arguments', 'result', 'mutates');
            if($method === 'at') {
                $row['missing'] = 'throw_out_of_range';
            }
            if($method === 'find') {
                $row['missing'] = 'empty_nullable';
            }
            $methods[] = $row;
        }
        if($key === 'int64') {
            $methods[] = ['name' => 'append', 'arguments' => [$value], 'result' => $key, 'mutates' => true, 'policy' => 'max_live_nonnegative_plus_one', 'overflow' => 'throw_overflow_error'];
        }
        $hashes[$name] = compact('name', 'key', 'value', 'methods') + ['source_family' => textValue($m->source_type), 'construction' => textValue($m->construction), 'key_policy' => 'strict', 'read' => 'value_copy', 'copy' => 'independent', 'move' => 'transfer_source_empty', 'storage' => 'private_ordered_typed_swiss', 'mutation_failure' => 'logical_contents_unchanged', 'traversal' => 'deferred_RT-DEBT-01'];
    }
    requireThat($hashes !== [], 'Empty hash publication');
    ksort($hashes, SORT_STRING);
    publishTypeExports($plan['submodules'], 'hashes', array_keys($hashes));
    $dependencies = ['bool_t', 'int64'];
    foreach ($hashes as $hash) $dependencies = array_merge($dependencies, [$hash['key'], $hash['value'], 'nullable_' . $hash['value']]);
    $plan['hash_publication'] = ['provider' => $provider, 'requires' => exportProviders($plan['submodules'], array_values(array_unique($dependencies)))];
    $plan['hashes'] = ['header' => $header, 'count_type' => 'int64', 'specializations' => array_values($hashes)];
}
const DATETIME_SIGNATURES = [ 'now_unix_seconds' => ['int64', []], 'now_unix_millis' => ['int64', []], 'monotonic_millis' => ['int64', []], 'monotonic_micros' => ['uint64', []], 'monotonic_nanos' => ['uint64', []], 'sleep_millis' => ['void', ['int64']], 'format_iso_utc' => ['string', ['int64']], 'parse_iso_utc' => ['result_int64', ['string']], 'parse_common_local' => ['result_int64', ['string']], 'format_local' => ['string', ['string', 'int64']], 'format_local_now' => ['string', ['string']]];
function prepareDatetime(mixed $metadata, mixed $publication, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'datetime', 'datetime', 'Datetime');
    if($m === null) {
        return;
    }
    fields($m, ['binding', 'utc_years', 'parse_failure', 'format_failure', 'operations', 'local_dst']);
    literals($m, ['binding' => 'native_datetime', 'utc_years' => '0000_9999', 'local_dst' => 'host_normalize_and_select', 'parse_failure' => 'result_error', 'format_failure' => 'throw_runtime_error']);
    $registered = names(at($m, 'operations'), 'Duplicate datetime operation policy');
    requireThat($registered !== [], 'Empty datetime policy');
    foreach($registered as $name) {
        requireThat(isset(DATETIME_SIGNATURES[$name]), "Unsupported datetime operation: $name");
    }
    if(!property_exists($publication, 'datetime')) {
        return;
    }
    $p = $publication->datetime;
    fields($p, ['header', 'operations']);
    $header = headerPath(at($p, 'header'));
    checkHeader($plan, $header);
    requireThat(!property_exists($plan['aliases'], 'dt'), 'Conflicting datetime namespace');
    $operations = [];
    foreach(names(at($p, 'operations'), 'Duplicate datetime operation') as $name) {
        requireThat(in_array($name, $registered, true), 'Unregistered datetime operation');
        [$result, $args] = DATETIME_SIGNATURES[$name];
        $integer = $result === 'uint64' ? 'uint64':'int64';
        $native = $result === 'uint64' ? 'unsigned long long':'long long';
        requireThat(hasType($plan, $integer), 'Datetime requires published integer type');
        requireThat(hasCast($plan, $integer, $native, true), 'Datetime requires explicit native integer transport');
        if($result === 'string' || in_array('string', $args, true)) {
            requireThat(isset($plan['string']), 'Datetime requires published string');
        }
        if($result === 'result_int64') {
            requireThat(in_array('result_int64', array_column($plan['carriers'], 'name'), true), 'Datetime parse requires published result_int64');
        }
        $operations[] = ['name' => $name, 'result' => $result, 'arguments' => array_map(fn(string $a):string => "const $a&", $args)];
    }
    requireThat($operations !== [], 'Empty datetime publication');
    $plan['datetime'] = ['module' => 'datetime', 'namespace' => 'scpp::dt', 'header' => $header, 'utc_years' => [0, 9999], 'local_dst' => 'host_normalize_and_select', 'parse_failure' => 'result_error', 'format_failure' => 'throw_runtime_error', 'operations' => $operations, 'build' => ['target' => 'scpp_datetime', 'requires' => 'scpp_runtime', 'generated_sources' => ['src/datetime.cpp'], 'private_sources' => ['runtime/src/datetime/datetime.cpp'], 'private_includes' => ['runtime/src/datetime', 'runtime/src/numeric']]];
}

function recordIdentifier(string $name): bool {
    $keywords = explode(' ', 'alignas alignof and and_eq asm auto bitand bitor bool break case catch char char8_t char16_t char32_t class compl concept const consteval constexpr constinit const_cast continue co_await co_return co_yield decltype default delete do double dynamic_cast else enum explicit export extern false float for friend goto if inline int long mutable namespace new noexcept not not_eq nullptr operator or or_eq private protected public register reinterpret_cast requires return short signed sizeof static static_assert static_cast struct switch template this thread_local throw true try typedef typeid typename union unsigned using virtual void volatile wchar_t while xor xor_eq std detail');
    return preg_match('/^[a-z][a-z0-9_]*$/D', $name) === 1 && !str_contains($name, '__') && !in_array($name, $keywords, true);
}

function prepareOwnership(mixed $metadata, mixed $publication, array $facts, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'ownership', 'ownership', 'Ownership');
    if($m === null) {
        return;
    }
    fields($m, ['binding', 'families', 'default', 'construction', 'access', 'empty_access', 'weak_access', 'payload', 'value', 'const_pointee']);
    literals($m, ['binding' => 'std_smart_pointer_inline', 'default' => 'empty', 'construction' => 'explicit_factory', 'access' => 'checked_borrow', 'empty_access' => 'throw_runtime_error', 'weak_access' => 'explicit_lock', 'payload' => 'registered_record']);
    requireThat(names(at($m, 'families'), 'Ownership: duplicate family') === ['shared', 'unique', 'weak'], 'Ownership: unsupported families');
    if(property_exists($m, 'value')) {
        fields($m->value, ['binding', 'construction', 'special_members', 'access']);
        literals($m->value, ['binding' => 'inline_record', 'construction' => 'explicit', 'special_members' => 'payload', 'access' => 'borrow']);
    }
    if(property_exists($m, 'const_pointee')) {
        literals($m, ['const_pointee' => 'explicit_add_const']);
    }
    if(!property_exists($publication, 'ownership')) {
        return;
    }
    $p = $publication->ownership;
    fields($p, ['header', 'records']);
    $header = headerPath(at($p, 'header'), true);
    checkHeader($plan, $header);
    requireThat(hasValue($plan, 'bool_t'), 'Ownership: bool_t must be published');
    $inputs = array_merge(array_column($plan['types'], 'name'), array_column($plan['values'], 'name'));
    $used = array_merge($inputs, array_column($plan['vectors'], 'name'), array_column($plan['carriers'], 'name'), array_column($plan['hashes']['specializations'] ?? [], 'name'), array_keys(object($plan['aliases'])), ['error_t', 'null'], isset($plan['boxed']) ? array_merge(['boxed_kind'], array_column($plan['boxed']['families'], 'name')) : []);
    if(isset($plan['string'])) {
        $used[] = 'string';
    }
    if(isset($plan['datetime'])) {
        $used[] = 'dt';
    }
    if (isset($plan['json'])) $used[] = 'json';
    $records = [];
    foreach(listValue(at($p, 'records')) as $r) {
        fields($r, ['name', 'header', 'constructors', 'families', 'const_families']);
        $name = textValue(at($r, 'name'));
        requireThat(recordIdentifier($name) && !in_array($name, $used, true), 'Ownership: invalid or conflicting record name');
        $used[] = $name;
        $rh = headerPath(at($r, 'header'), true);
        checkHeader($plan, $rh);
        requireThat($rh !== $header, 'Ownership: record header conflicts with generated header');
        $families = [];
        foreach(['families' => false, 'const_families' => true] as $field => $const) {
            if(!property_exists($r, $field)) {
                continue;
            }
            $requested = names($r->{$field}, 'Ownership: invalid or duplicate family');
            foreach($requested as $family) {
                requireThat(in_array($family, ['shared', 'weak', 'unique'], true) || (!$const && $family === 'value' && property_exists($m, 'value')), 'Ownership: invalid or duplicate family');
                requireThat(!$const || property_exists($m, 'const_pointee'), 'Ownership: missing const-pointee policy');
                $identity = $family . '_' . ($const ? 'const_':'') . $name;
                requireThat(!in_array($identity, $used, true), 'Ownership: conflicting specialization');
                $used[] = $identity;
                $row = ['name' => $identity, 'family' => $family];
                if($family === 'value') {
                    $row += ['binding' => 'inline_record', 'special_members' => 'native_payload_dependent', 'default' => 'not_published', 'wrapper_allocation' => 'none'];
                }
                else {
                    $row += ['pointee' => $const ? 'const':'mutable', 'qualification_constructors' => [], 'default' => 'empty', 'move' => 'transfer_source_empty', 'copy' => $family === 'unique' ? 'deleted':'retain_identity'] + $facts['ownership'][$family];
                }
                $families[] = $row;
            }
            requireThat(!in_array('weak', $requested, true) || in_array('shared', $requested, true), 'Ownership: weak requires matching shared publication');
        }
        requireThat($families !== [], 'Ownership: empty family request');
        $sources = array_values(array_filter($families, fn(array $s):bool => ($s['pointee'] ?? '') === 'mutable'));
        foreach($families as &$f) {
            if(($f['pointee'] ?? '') !== 'const') {
                continue;
            }
            foreach($sources as $source) {
                $effects = [];
                if($source['family'] === $f['family']) {
                    if($f['family'] !== 'unique') {
                        $effects[] = 'retain_identity';
                    }
                    $effects[] = 'transfer_source_empty';
                }
                elseif($f['family'] === 'weak' && $source['family'] === 'shared') {
                    $effects[] = 'retain_identity';
                }
                foreach($effects as $effect) {
                    $f['qualification_constructors'][] = ['source' => $source['name'], 'parameter' => $effect === 'transfer_source_empty' ? 'rvalue_reference':'const_reference', 'construction' => 'explicit', 'effect' => $effect];
                }
            }
        }
        unset($f);
        $constructors = [];
        foreach(listValue(at($r, 'constructors')) as $args) {
            $args = listValue($args);
            foreach($args as $arg) {
                requireThat(in_array(textValue($arg), $inputs, true), 'Ownership: constructor argument must be a published scalar');
            }
            requireThat(!in_array($args, $constructors, true), 'Ownership: duplicate constructor');
            $constructors[] = $args;
        }
        requireThat($constructors !== [], 'Ownership: record needs constructor signatures');
        usort($constructors, function(array $a, array $b):int {
            for($i = 0; $i < min(count($a), count($b)); ++$i) {
                if($a[$i] !== $b[$i]) {
                    return strcmp($a[$i], $b[$i]);
                }
            }
            return count($a) <=> count($b);
        });
        foreach($families as &$f) {
            if($f['family'] === 'value' && in_array([], $constructors, true)) {
                $f['default'] = 'payload_value_initialization';
            }
        }
        unset($f);
        $records[] = ['name' => $name, 'header' => $rh, 'constructors' => $constructors, 'families' => $families];
    }
    requireThat($records !== [], 'Ownership: empty record publication');
    usort($records, fn(array $a, array $b):int => strcmp($a['name'], $b['name']));
    $plan['ownership'] = ['header' => $header, 'handle_binding' => 'std_smart_pointer_inline', 'handle_empty_access' => 'std::runtime_error:ownership_empty_access', 'weak_lock_failure' => 'empty_shared', 'handle_copy_allocation' => 'none', 'weak_condition' => 'not_published', 'handle_payload_access' => 'checked_borrow_no_implicit_extraction', 'records' => $records];
}

function stringAdapterOrder(array $arguments, array $adapter): array {
    if (isset($adapter['swap_first_two'])) {
        [$arguments[0], $arguments[1]] = [$arguments[1], $arguments[0]];
    }
    return $arguments;
}
