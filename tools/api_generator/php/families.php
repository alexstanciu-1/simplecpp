<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/boxed.php';
require_once __DIR__ . '/json.php';
require_once __DIR__ . '/resource_modules.php';
require_once __DIR__ . '/module_functions.php';
require_once __DIR__ . '/text_helpers.php';
require_once __DIR__ . '/cli_helpers.php';
require_once __DIR__ . '/text_builders.php';
require_once __DIR__ . '/stringable.php';

const STRUCTURAL_READERS = ['int64' => 'read_integer', 'bool_t' => 'read_boolean', 'string' => 'read_string'];

/** Internal native traversal ABI shared by the supported concrete collection families. */
function collectionTraversalPlan(): array {
    return ['limit_method' => 'iteration_limit', 'next_method' => 'iteration_next',
        'mode' => 'borrow_live_wrapper_physical', 'visibility' => 'compiler_internal',
        'value_copy' => 'ordinary_copy', 'mutation' => 'reload_without_preservation'];
}

function checkHeader(array $plan, string $header): void {
    $paths = array_values($plan['headers']);
    if (isset($plan['structural_decoder'])) $paths[] = 'scpp/decoder.hpp';
    if (isset($plan['structural_encoder'])) $paths[] = 'scpp/encoder.hpp';
    foreach (['scalar_core', 'scalar_numeric'] as $group) {
        $paths[] = providerHeader($plan['submodules'], $plan['submodules']['groups'][$group]['provider']);
    }
    if (isset($plan['string_library'])) $paths[] = providerHeader($plan['submodules'], $plan['string_library']['provider']);
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
    fields($publication, array_merge(SCALAR_FIELDS, ['string', 'vectors', 'carriers', 'ownership', 'hashes', 'datetime', 'boxed', 'json', 'filesystem', 'regex', 'async_frames', 'mt_segments', 'collection_transforms', 'cli_helpers', 'text_helpers', 'text_builders', 'resource_modules', 'structural_encoder', 'structural_decoder']));
    $scalarRequest = (object)array_intersect_key(object($publication), array_flip(SCALAR_FIELDS));
    $plan = prepareScalarPlan($metadata, $scalarRequest, $facts);
    $registry = registry($metadata, $facts);
    prepareString($metadata, $publication, $registry, $plan);
    $exception = coreExceptionBinding($metadata);
    if ($exception !== null && isset($plan['string'])) $plan['exception'] = $exception;
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
        if (in_array('nullable<int64>', $binding['parameters'], true)) {
            requireThat(isset($plan['source_types']->{'nullable<int64>'}), "$name requires published nullable<int64>");
            $plan['string_functions'][$name]['input_header'] = $plan['carriers_header'];
        }
        if ($binding['result'] === 'result_or_false<string>') {
            $export = $plan['source_types']->{$binding['result']} ?? null;
            requireThat($export !== null, "$name requires published " . $binding['result']);
            $plan['string_functions'][$name]['result_header'] = $plan['carriers_header'];
        }
        foreach ($binding['adapters'] ?? [] as $flavor => $adapter) {
            if (!isset($adapter['body'])) continue;
            $export = $plan['source_types']->{$adapter['result']} ?? null;
            requireThat($export !== null, "$name requires published " . $adapter['result']);
            $plan['string_functions'][$name]['adapters'][$flavor]['result'] = $export;
        }
    }
    prepareHashes($metadata, $publication, $plan);
    $maps = publishedMaps($metadata, $publication);
    foreach (names(property_exists($publication, 'collection_transforms') ? $publication->collection_transforms : [], 'Duplicate collection transform') as $name) {
        if (in_array($name, ['in_array', 'array_filter'], true) || in_array($name, $maps, true)) {
            $plan['collection_transforms'][] = $name;
            continue;
        }
        requireThat(preg_match('/^(collection|sequence|keyed)_(map|filter)$/D', $name, $match) === 1, 'Unknown collection transform');
        if ($match[2] === 'map') continue;
        if (($match[1] !== 'keyed' && $plan['vectors'] !== []) || ($match[1] !== 'sequence' && isset($plan['hashes']))
            || ($match[1] === 'collection' && isset($plan['boxed_dispatch']))) {
            $plan['collection_transforms'][] = $name;
        }
    }
    prepareTextBuilders($metadata, $publication, $plan);
    prepareStringable($metadata, $plan);
    prepareCliHelpers($publication, $plan);
    prepareTextHelpers($metadata, $publication, $plan);
    prepareDatetime($metadata, $publication, $plan);
    if (needsStructuralEncoder($publication)) {
        checkHeader($plan, 'scpp/encoder.hpp');
        $plan['structural_encoder'] = structuralEncoderInputs($plan);
    }
    if (needsStructuralDecoder($publication)) {
        checkHeader($plan, 'scpp/decoder.hpp');
        requireThat(isset($plan['string']), 'Structural Decoder requires published string');
        $plan['structural_decoder'] = array_filter(STRUCTURAL_READERS,
            fn(string $id): bool => $id === 'string' || hasType($plan, $id) || hasValue($plan, $id), ARRAY_FILTER_USE_KEY);
    }
    prepareJson($metadata, $publication, $plan);
    foreach (FUNCTION_MODULES as $family) prepareModuleFunctions($family, $metadata, $publication, $plan);
    prepareResourceModules($publication, $plan);
    prepareOwnership($metadata, $publication, $facts, $plan);
    sortConversions($plan['conversions']);
    finalizePublicationProviders($plan);
    if (property_exists($publication, 'mt_segments')) {
        requireThat(is_bool($publication->mt_segments), 'Invalid MT segment selection');
        if ($publication->mt_segments) {
            requireThat(in_array('int64', array_column($plan['vectors'], 'element'), true), 'MT segments require vector<int64> results');
            $plan['mt_segments'] = true;
        }
    }
    $plan['async_frames'] = $publication->async_frames ?? false;
    requireThat(is_bool($plan['async_frames']), 'Invalid async frame selection');
    return $plan;
}

/** Plain numeric leaves cross the native boundary by value; storage-bearing leaves are borrowed. */
function structuralEncoderInputs(array $plan): array {
    $inputs = [];
    foreach (['int64', 'bool_t', 'float64'] as $type) {
        if (hasType($plan, $type) || hasValue($plan, $type)) $inputs[$type] = $type;
    }
    if (isset($plan['string'])) $inputs['string'] = 'const string&';
    foreach ($plan['boxed']['families'] ?? [] as $family) {
        $type = $family['name'];
        $inputs[$type] = "const $type&";
    }
    foreach ($plan['carriers'] ?? [] as $carrier) {
        if ($carrier['family'] !== 'nullable' || !in_array($carrier['payload'], ['int64', 'bool_t', 'float64', 'string'], true)) continue;
        $type = $carrier['name'];
        $inputs[$type] = "const $type&";
    }
    ksort($inputs);
    return $inputs;
}

/** Whole-value transfer may elide the copy only after body preparation proves a private last use. */
function privateValueTransfer(object $family): bool {
    if (!property_exists($family, 'transfer_assignment')) return false;
    literals($family, ['transfer_assignment' => 'private_value_nothrow']);
    return true;
}

/** Normalize the core runtime class binding once for native and compiler publication. */
function coreExceptionBinding(mixed $metadata): ?array {
    $exception = $metadata->compiler->exception ?? null;
    if ($exception === null) return null;
    fields($exception, ['name', 'header', 'handle', 'make', 'get_message']);
    $binding = [];
    foreach (['name', 'header', 'handle', 'make', 'get_message'] as $field) {
        $binding[$field] = textValue(at($exception, $field));
    }
    requireThat($binding['header'] === 'scpp/exception.hpp' && $binding['handle'] === 'scpp::exception'
        && $binding['make'] === '::scpp::make_exception' && $binding['get_message'] === 'get_message'
        && $binding['name'] === 'Exception', 'Unsupported core Exception contract');
    return $binding;
}

function prepareString(mixed $metadata, mixed $publication, array $registry, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'string', 'string', 'String');
    if ($m === null) {
        return;
    }
    fields($m, ['access_contracts', 'type', 'source_type', 'storage', 'count_type', 'construction', 'null_pointer', 'text_casts', 'operations', 'provider', 'library_provider', 'functions', 'trim_defaults', 'search_policy_provider', 'search_offsets', 'explicit_formatters', 'index_read', 'index_write', 'transfer_assignment']);
    $transfer = privateValueTransfer($m);
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
    $libraryProvider = textValue($m->library_provider ?? $provider);
    $libraryHeader = providerHeader($plan['submodules'], $libraryProvider);
    if (isset($p->header)) requireThat(in_array(headerPath($p->header), [$header, $libraryHeader], true), 'String publication header conflicts with provider');
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
    $libraryTypes = [];
    $symbols = [];
    $memberNames = [];
    foreach (names($p->functions ?? [], 'Duplicate string function') as $name) {
        $function = at(at($m, 'functions'), $name);
        fields($function, ['parameters', 'result', 'symbol', 'implementation', 'members', 'direct_provider', 'flavor_adapters', 'signatures', 'side', 'required_parameters', 'limit_policy', 'bound', 'replace_mode', 'pad_side', 'first_byte_only', 'flavors', 'constants', 'free_names', 'php_aliases', 'complement', 'numeric_inputs', 'selector', 'arities', 'ascii_fold']);
        $implementation = textValue(at($function, 'implementation'));
        $signature = match ($implementation) {
            'split_bytes' => ['parameters' => ['string', 'string', 'int64'], 'result' => 'vector<string>', 'required_parameters' => 2],
            'join_bytes' => ['parameters' => ['vector<string>', 'string'], 'result' => 'string'],
            'byte_length', 'first_byte' => ['parameters' => ['string'], 'result' => $count],
            'checked_single_byte', 'low_byte' => ['parameters' => ['int64'], 'result' => 'string'],
            'count_matches' => ['parameters' => ['string', 'string', 'int64', 'int64'], 'result' => 'int64', 'required_parameters' => 2],
            'find', 'rfind' => ['parameters' => ['string', 'string', 'int64'], 'result' => 'int64', 'required_parameters' => 2],
            'substring_tail', 'ascii_substring_tail', 'last_byte_tail' => ['parameters' => ['string', 'string', 'bool_t'], 'result' => 'result_or_false<string>', 'required_parameters' => 2],
            'chunk_bytes' => ['parameters' => ['string', 'int64', 'string'], 'result' => 'string', 'required_parameters' => 1],
            'wrap_words' => ['parameters' => ['string', 'int64', 'string', 'bool_t'], 'result' => 'string', 'required_parameters' => 1],
            'ascii_words' => ['parameters' => ['string', 'string'], 'result' => 'string', 'required_parameters' => 1],
            'newline_breaks' => ['parameters' => ['string', 'bool_t'], 'result' => 'string', 'required_parameters' => 1],
            'byte_span' => ['parameters' => ['string', 'string', 'int64', 'nullable<int64>'], 'result' => $count, 'required_parameters' => 2],
            'byte_set_tail' => ['parameters' => ['string', 'string'], 'result' => 'result_or_false<string>'],
            'hex_decode' => ['parameters' => ['string'], 'result' => 'result_or_false<string>'],
            'hex_encode', 'ascii_lower', 'ascii_upper', 'trim_bytes', 'reverse_bytes', 'ascii_rot13', 'increment_alnum', 'decrement_alnum', 'add_slashes', 'strip_slashes', 'quoted_printable_encode', 'quoted_printable_decode' => ['parameters' => ['string'], 'result' => 'string'],
            'pad_bytes' => ['parameters' => ($function->pad_side ?? null) === 'argument'
                ? ['string', 'int64', 'string', 'int64'] : ['string', 'int64', 'string'], 'result' => 'string', 'required_parameters' => 2],
            'format_number' => ['parameters' => ['int64', 'int64', 'string', 'string'], 'result' => 'string'],
            'compare_prefix' => ['parameters' => ['string', 'string', 'int64'], 'result' => 'int64'],
            'compare_bytes', 'compare_natural' => ['parameters' => ['string', 'string'], 'result' => 'int64'],
            'compare_window' => ['parameters' => ['string', 'string', 'int64', 'int64', 'bool_t'], 'required_parameters' => 3, 'result' => 'int64'],
            'replace_window' => ['parameters' => ['string', 'string', 'int64', 'int64'], 'required_parameters' => 3, 'result' => 'string'],
            'translate_bytes', 'ascii_replace_bytes', 'replace_bytes' => ['parameters' => ['string', 'string', 'string'], 'result' => 'string'],
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
            if ($type === 'string' || $type === 'nullable<int64>' || $type === 'vector<string>' || $type === 'result_or_false<string>') continue;
            requireThat(hasType($plan, $type) || hasValue($plan, $type), 'String function requires a published parameter/result type');
            $libraryTypes[] = $type;
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
        $span = $implementation === 'byte_span';
        requireThat($span === isset($function->complement), 'Byte span requires a membership mode');
        $phpOnly = $span || in_array($implementation, ['format_number', 'replace_window', 'compare_natural', 'compare_bytes', 'compare_prefix', 'compare_window', 'hex_encode', 'hex_decode', 'substring_tail', 'ascii_substring_tail', 'last_byte_tail', 'byte_set_tail', 'first_byte', 'low_byte', 'reverse_bytes', 'ascii_rot13', 'ascii_words', 'newline_breaks', 'add_slashes', 'strip_slashes', 'quoted_printable_encode', 'quoted_printable_decode', 'chunk_bytes', 'wrap_words', 'translate_bytes', 'ascii_replace_bytes', 'increment_alnum', 'decrement_alnum'], true) || $firstByte
            || ($implementation === 'trim_bytes' && isset($function->flavors));
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
            $policyProvider = textValue($m->search_policy_provider ?? $libraryProvider);
            $plan['search_policy'] = ['provider' => $policyProvider, 'requires' => []];
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
        $replace = in_array($implementation, ['replace_bytes', 'ascii_replace_bytes'], true);
        requireThat($replace === property_exists($function, 'replace_mode'), 'Replacement bindings require a mode');
        if ($replace) {
            requireThat(in_array($function->replace_mode, ['first', 'all'], true), 'Invalid replacement mode');
            requireThat(($function->flavor_adapters ?? false) === true, 'Replacement requires flavor adapters');
        }
        $compare = in_array($implementation, ['compare_bytes', 'compare_prefix', 'compare_natural'], true);
        requireThat($search || $compare === isset($function->ascii_fold), 'Byte comparisons require an ASCII folding policy');
        $trim = $implementation === 'trim_bytes';
        requireThat($trim === property_exists($function, 'side'), 'Trim direction required only for trim bindings');
        if ($trim) {
            $side = textValue($function->side);
            requireThat(in_array($side, ['both', 'start', 'end'], true), 'Unknown trim direction');
            $defaults = fields(at($m, 'trim_defaults'), ['php', 'js', 'cpp']);
            requireThat(($function->flavor_adapters ?? false) === true, 'Default trim requires flavor adapters');
        }
        requireThat(!isset($symbols[$symbol]) || (($trim || $replace || $padding || $compare || $span) && $symbols[$symbol] === $implementation), 'Duplicate string function binding');
        $symbols[$symbol] = $implementation;
        $plan['string_functions'][$name] = $signature + [
            'symbol' => $symbol, 'implementation' => $implementation, 'header' => $libraryHeader, 'members' => $members];
        if (isset($function->php_aliases)) {
            $aliases = names($function->php_aliases, 'Duplicate PHP string alias');
            foreach ($aliases as $alias) {
                requireThat(preg_match('/^[a-z_][a-z_0-9]*$/D', $alias) && !isset($m->functions->$alias)
                    && !isset($memberNames['php'][$alias]) && $phpOnly, 'Invalid PHP string alias');
                $memberNames['php'][$alias] = true;
            }
            $plan['string_functions'][$name]['php_aliases'] = $aliases;
        }
        if (isset($function->direct_provider)) {
            requireThat(in_array($implementation, ['starts_with', 'find'], true) && ($function->flavor_adapters ?? false) === true, 'Unsupported direct string binding');
            $direct = textValue($function->direct_provider);
            requireExportDependencies($plan['submodules'], $direct, [$signature['result']]);
            if ($search) requireThat(isset($plan['submodules']['closures'][$direct][$policyProvider]), 'Search provider requires offset policy');
            $plan['string_functions'][$name]['direct_provider'] = $direct;
            $plan['string_functions'][$name]['direct_header'] = providerHeader($plan['submodules'], $direct);
        }
        if ($search && boolean($function->ascii_fold ?? false)) $plan['string_functions'][$name]['ascii_fold'] = true;
        $formatted = $implementation === 'format_number';
        requireThat($formatted === isset($function->numeric_inputs) && $formatted === isset($function->arities),
            'Numeric input overloads require explicit arities');
        if (isset($function->selector)) {
            requireThat($formatted && $function->selector === 'payload_or_box_kind', 'Unsupported overload selector');
            $plan['string_functions'][$name]['project_selector'] = true;
        }
        if ($formatted) {
            literals($function, ['numeric_inputs' => ['int64', 'float64'], 'arities' => [1, 2, 4]]);
            foreach ($function->numeric_inputs as $input) {
                requireThat(hasType($plan, $input) || hasValue($plan, $input), "Number formatting requires $input");
                $libraryTypes[] = $input;
                $params = $signature['parameters'];
                $params[0] = $input;
                foreach ($function->arities as $arity) {
                    $plan['string_functions'][$name]['overloads'][$arity][$input] = array_slice($params, 0, $arity);
                }
            }
        }
        if ($firstByte) $plan['string_functions'][$name]['first_byte_only'] = true;
        $enabled = boolean($function->flavor_adapters ?? false);
        requireThat($enabled || !in_array($implementation, STRING_VECTOR_IMPLEMENTATIONS, true),
            'String/vector operations require flavor adapters');
        $signatures = isset($function->signatures) ? fields($function->signatures, array_keys(['php' => $name] + $members)) : [];
        requireThat(!$signatures || $enabled, 'Flavor signatures require generated adapters');
        if ($enabled) {
            requireThat($search || $members !== [] || isset($function->flavors), 'Flavor adapters require flavor-specific string spellings');
            foreach ($spellings as $flavor => $spelling) {
                $options = [];
                if (in_array($implementation, ['substring_tail', 'ascii_substring_tail', 'last_byte_tail'], true)) $options['before_needle'] = true;
                if ($span) $options['complement'] = boolean($function->complement);
                if ($compare) $options['ascii_fold'] = boolean($function->ascii_fold);
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
                    if (isset($signatures[$flavor]->parameter_names)) {
                        $options += fields($signatures[$flavor], ['parameter_names']);
                        $labels = listValue($options['parameter_names']);
                        requireThat(($flavor === 'php' || isset($freeNames[$flavor])) && (!isset($signature['required_parameters']) || $signature['required_parameters'] === count($signature['parameters']) - 1)
                            && !isset($function->arities) && count($labels) === count($signature['parameters']),
                            'Parameter names require a free function with at most one trailing default and one name per parameter');
                        foreach ($labels as $label) requireThat(preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', textValue($label)) === 1, 'Invalid parameter name');
                        requireThat(count(array_unique($labels)) === count($labels), 'Duplicate parameter name');
                    } elseif ($trim) {
                        requireThat($flavor === 'php', 'Explicit trim masks require the PHP flavor');
                        $options += fields($signatures[$flavor], ['parameters', 'required_parameters']);
                        literals($signatures[$flavor], ['parameters' => ['string', 'string'], 'required_parameters' => 1]);
                    } elseif ($replace) {
                        requireThat($flavor === 'php' && $function->replace_mode === 'all', 'Count output requires PHP replace-all');
                        $options += fields($signatures[$flavor], ['parameters', 'required_parameters', 'reference_parameters']);
                        literals($signatures[$flavor], ['parameters' => ['string', 'string', 'string', 'int64'],
                            'required_parameters' => 3, 'reference_parameters' => [3]]);
                        requireThat(hasType($plan, 'int64'), 'Replacement count requires int64');
                        $libraryTypes[] = 'int64';
                    } else {
                        $options += fields($signatures[$flavor], ['result', 'body', 'header']);
                        if (isset($options['header'])) {
                            requireThat($flavor === 'php', 'Dedicated search result adapter requires PHP');
                            $options['header'] = headerPath($options['header']);
                            checkHeader($plan, $options['header']);
                        }
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
                    'name' => $spelling, 'symbol' => "scpp::$flavor::$spelling", 'header' => $options['header'] ?? stringAdapterHeader($flavor, $implementation)] + $options;
            }
        }
    }
    foreach (['index_read' => 'result', 'index_write' => 'value'] as $operation => $element) {
        if (!boolean($p->$operation ?? false)) continue;
        $row = at($m, $operation);
        fields($row, $operation === 'index_read' ? ['method', 'key', $element, 'policy', 'view_provider'] : ['method', 'key', $element, 'policy']);
        literals($row, ['policy' => 'checked_byte_offset', 'key' => $count, $element => 'uint8']);
        $method = textValue(at($row, 'method'));
        requireThat(preg_match('/^[a-z][a-z0-9_]*$/D', $method) === 1 && !in_array($method, array_column($methods, 'name'), true), 'Invalid string index method');
        requireThat(hasType($plan, $row->key) && hasType($plan, $row->$element), 'String indexing requires published key and byte types');
        $plan['string_' . $operation] = ['name' => $method, 'key' => $row->key, $element => $row->$element];
        if ($operation === 'index_read') {
            $view = textValue($row->view_provider ?? $provider);
            $plan['string_view'] = ['provider' => $view, 'requires' => [$provider], 'header' => providerHeader($plan['submodules'], $view)];
        }
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
        $plan['explicit_formatters'][$source] = ['policy' => $policy, 'name' => $name, 'symbol' => $symbol, 'header' => $libraryHeader];
        $libraryTypes[] = $source;
    }
    $requirements = exportProviders($plan['submodules'], $requires);
    $plan['source_types']->string = 'string';
    $libraryRequires = exportProviders($plan['submodules'], $libraryTypes);
    $libraryRequires[] = $provider;
    if (isset($plan['search_policy'])) $libraryRequires[] = $plan['search_policy']['provider'];
    if (isset($plan['string_view'])) $libraryRequires[] = $plan['string_view']['provider'];
    $plan['string_library'] = ['provider' => $libraryProvider, 'requires' => $libraryRequires];
    $plan['string'] = ['provider' => $provider, 'requires' => $requirements, 'name' => 'string', 'count_type' => $count, 'count_representation' => $countRepresentation, 'header' => $header, 'storage' => 'inline_22_private_std_string', 'lifecycle' => 'owned_copy_noexcept_move', 'inline_capacity' => 22, 'empty_state' => 'inline', 'operations' => $operations, 'methods' => $methods, 'construction' => [['arguments' => ['const char*'], 'explicit' => true, 'null_pointer' => 'reject', 'can_fail' => true], ['arguments' => ['const char*', $countRepresentation], 'explicit' => true, 'null_pointer' => 'reject', 'can_fail' => true]]];
    if ($transfer) $plan['string']['transfer_assignment'] = true;
}

/** One storage-to-recipe selection shared by runtime and application compiler generation. */
function vectorConcreteRecipe(array $vector): string {
    requireThat($vector['storage'] === 'concrete_buffer', 'Unsupported vector concrete storage');
    return 'collections/vector';
}

/** Validate the bounded positional transport contract; runtime families supply element/result identities. */
function listInitializationPlan(mixed $init, string $family, string $element, string $result, string $count): ?array {
    if ($init === null) return null;
    fields($init, ['element_families', 'read_only', 'consume', 'input', 'presizing', 'transfer', 'stack_bytes', 'inline_elements']);
    literals($init, ['presizing' => 'entry_count', 'transfer' => 'nothrow']);
    $admitted = names(at($init, 'element_families'));
    requireThat(array_diff($admitted, ['integer', 'string']) === [], 'Unsupported initialization element family');
    foreach (['read_only', 'input'] as $key) requireThat(recordIdentifier(textValue(at($init, $key))), 'Invalid initialization binding name');
    if (isset($init->consume)) requireThat(recordIdentifier(textValue($init->consume)), 'Invalid consuming binding name');
    requireThat(is_int($init->stack_bytes) && $init->stack_bytes >= 1 && $init->stack_bytes <= 65536, 'Invalid initialization stack bound');
    $limits = object(at($init, 'inline_elements'));
    requireThat(array_keys($limits) === $admitted, 'Inline limits must match initialized element families');
    foreach ($limits as $limit) requireThat(is_int($limit) && $limit >= 0 && $limit <= 65536, 'Invalid inline element limit');
    requireThat(count(array_unique([$init->read_only, $init->input, $init->consume ?? ''])) === 3, 'Initialization names must differ');
    return in_array($family, $admitted, true) ? (array)$init + ['element' => $element, 'result' => $result, 'count' => $count, 'inline_limit' => $limits[$family]] : null;
}

function prepareVectors(mixed $metadata, mixed $publication, array $registry, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'vector', 'vectors', 'Vector');
    if ($m === null) {
        return;
    }
    fields($m, ['map_domain', 'access_contracts', 'provider', 'source_type', 'storage', 'count_type', 'read', 'mutation', 'element_families', 'native_index', 'native_value', 'operations', 'initialization', 'reserve', 'transfer_assignment']);
    $transfer = privateValueTransfer($m);
    $reserve = property_exists($m, 'reserve');
    if ($reserve) literals($m, ['reserve' => 'checked_capacity_preserve_size']);
    literals($m, ['source_type' => 'vector', 'storage' => 'concrete_buffer', 'read' => 'value_copy', 'mutation' => 'explicit_set', 'native_index' => 'int']);
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
    fields($p, ['header', 'specializations', 'capacity_helpers']);
    $capacity = property_exists($p, 'capacity_helpers') ? $p->capacity_helpers : false;
    requireThat(is_bool($capacity) && (!$capacity || $reserve), 'Vector capacity helpers require checked reserve');
    $provider = $plan['submodules']['groups']['vectors']['provider'];
    $header = providerHeader($plan['submodules'], $provider);
    if (isset($p->header)) requireThat(headerPath($p->header) === $header, 'Vector publication header conflicts with provider');
    requireThat(hasType($plan, $count) && hasNative($plan, 'long long'), 'Vector requires published count and native long long construction');
    requireThat(hasValue($plan, 'bool_t'), 'Vector requires published boolean type');
    if ($reserve) checkHeader($plan, 'scpp/value_error.hpp');
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
        if ($reserve) $add('reserve', 'void', [$count], true, $m->reserve);
        if ($capacity) {
            $add('capacity', $count, [], false, 'observe_capacity');
            $add('resize', 'void', [$count, scalarInput($element)], true, 'resize_copy_fill');
            $add('compact', 'void', [$count], true, 'optional_capacity');
        }
        $add('remove', 'bool_t', [$count], true, 'erase_shift_missing_false');
        $add('has', 'bool_t', [$count], false, 'index_presence');
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
        $initialization = null;
        if (isset($m->initialization)) {
            fields($m->initialization, ['mode', 'bulk']);
            literals($m->initialization, ['mode' => 'ordered_elements']);
            $initialization = ['bulk' => listInitializationPlan($m->initialization->bulk ?? null, $family, $element, $name, $count)];
        }
        $bulk = $initialization['bulk'] ?? null;
        if ($bulk !== null) requireThat(!in_array('iteration_reference', [$bulk['input'], $bulk['read_only'], $bulk['consume'] ?? null], true), 'Vector initializer name conflicts with iteration reference');
        if ($reserve && $bulk !== null) requireThat(!in_array('reserve', [$bulk['input'], $bulk['read_only'], $bulk['consume'] ?? null], true), 'Vector initializer name conflicts with reserve');
        if ($capacity && $bulk !== null) {
            requireThat(!array_intersect(['capacity', 'compact', 'filled', 'resize'], [$bulk['input'], $bulk['read_only'], $bulk['consume'] ?? null]),
                'Vector initializer name conflicts with capacity helper');
        }
        $vectors[$name] = ($capacity ? ['capacity_helpers' => true] : []) + ['name' => $name, 'element' => $element, 'source_type' => "vector<$element>", 'transfer_assignment' => $transfer, 'enable_reserve' => $reserve ? '1' : '', 'count_type' => $count, 'storage' => 'concrete_buffer', 'lifecycle' => 'owned_copy_noexcept_move', 'read' => 'checked_index_value_copy', 'set' => 'checked_index_move_value', 'native_index' => $nativeIndex, 'native_value' => $native === 'none' ? '':$native, 'operations' => $operations, 'methods' => $methods, 'casts_from' => [], 'traversal' => collectionTraversalPlan(), 'initialization' => $initialization];
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
    fields($m, ['provider', 'specialization_providers', 'extraction', 'payload_access', 'presence', 'operand_participation', 'payload_writes', 'peer_equality', 'sentinels', 'construction', 'families', 'error']);
    $extraction = textValue(at($m, 'extraction'));
    $payloadAccess = textValue(at($m, 'payload_access'));
    requireThat($payloadAccess === 'value', 'Unsupported carrier payload access binding');
    requireThat(in_array($extraction, ['implicit_checked_one_layer_value_copy', 'explicit_checked_one_layer_value_copy'], true), 'Unsupported carrier extraction policy');
    literals($m, ['construction' => 'explicit']);
    $error = at($m, 'error');
    fields($error, ['type', 'message', 'line', 'file', 'state', 'peer_equality']);
    literals($error, ['type' => 'exception', 'message' => 'string', 'line' => 'int64', 'file' => 'string', 'state' => 'explicit_present']);
    $errorEquality = at($error, 'peer_equality');
    fields($errorEquality, ['equal', 'identical']);
    literals($errorEquality, ['equal' => 'same_state']);
    $identity = at($errorEquality, 'identical');
    fields($identity, ['fields']);
    $identityFields = names(at($identity, 'fields'));
    requireThat($identityFields !== [] && array_diff($identityFields, ['message', 'file', 'line']) === [],
        'Invalid error identity fields');
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
            'result' => ['value' => 'payload', 'error' => 'exception'],
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
        fields($item, ['family', 'payload', 'instance_field']);
        $family = textValue(at($item, 'family'));
        at($families, $family);
        $payload = textValue(at($item, 'payload'));
        requireThat($family !== 'nullable' || hasValue($plan, 'null_t'), 'Nullable requires published null_t');
        $payloadInfo = carrierPayload($plan, $payload);
        requireThat($payloadInfo !== null, 'Carrier payload must be a published scalar, string, vector or supported boxed family');
        requireThat($payloadInfo['kind'] !== 'boxed' || $family === 'result' || ($family === 'result_or_false' && $payload === 'mixed'), 'Unsupported carrier family for boxed payload');
        requireThat($family !== 'result' || (isset($plan['string']) && hasType($plan, 'int64')), 'Result error payload requires string and int64');
        $name = $family . '_' . $payload;
        requireThat(!isset($carriers[$name]), 'Duplicate carrier specialization');
        $carriers[$name] = compact('name', 'family', 'payload', 'extraction') + ['payload_access' => $payloadAccess, 'presence' => object($m->presence)] + $familyPlans[$family] + [ 'boxing' => 'explicit', 'extraction_can_fail' => true, 'non_payload_extraction' => 'throw_runtime_error', 'condition' => 'cpp_payload_conversion_rules', 'lifecycle' => 'owned_copy_move_resets_source_to_default', 'lifted_operators' => false, 'result_category' => 'value', 'explicit_lowering' => 'value_then_payload_operation'];
        $carriers[$name]['instance_field'] = property_exists($item, 'instance_field') && boolean($item->instance_field);
        if ($payloadInfo['kind'] === 'boxed') {
            $carriers[$name]['payload_header'] = $payloadInfo['header'];
            $carriers[$name]['payload_copy'] = $payloadInfo['copy'];
        }
        $plan['source_types']->{"$family<$payload>"} = $name;
        if($family === 'result') {
            $plan['error'] = ['type' => 'exception', 'state' => 'explicit_present', 'message' => 'string', 'line' => 'int64', 'file' => 'string', 'identity_fields' => $identityFields, 'inspection' => 'shared_owner', 'global_short_name' => false];
        }
    }
    requireThat($carriers !== [], 'Empty carrier publication');
    ksort($carriers, SORT_STRING);
    // Only concrete result types published together need cross-payload error inspection.
    $identityTypes = $metadata->compiler->strict_identity->types ?? [];
    $results = array_filter($carriers, static fn($c) => $c['family'] === 'result' && in_array($c['payload'], $identityTypes, true));
    if (isset($m->peer_equality->operators->identical)
        && in_array('result', $m->operand_participation->families ?? [], true)) {
        foreach ($results as $name => $c) {
            $peers = array_values(array_diff(array_keys($results), [$name]));
            if ($peers !== []) $carriers[$name]['error_identity_peers'] = $peers;
        }
    }
    $plan['carriers'] = array_values($carriers);
    $plan['carriers_header'] = $header;
    $exports = array_keys($carriers);
    if (isset($plan['error'])) $exports[] = 'exception';
    $placements = fields($m->specialization_providers ?? (object)[], ['result_or_false_int64']);
    $placements = array_intersect_key($placements, $carriers);
    publishTypeExports($plan['submodules'], 'carriers', $exports, $placements);
    foreach ($placements as $identity => $id) {
        requireExportDependencies($plan['submodules'], $id, ['bool_t', $carriers[$identity]['payload']]);
    }
    $dependencies = ['bool_t'];
    foreach ($carriers as $carrier) {
        if (!isset($carrier['payload_header'])) $dependencies[] = $carrier['payload'];
        if ($carrier['family'] === 'nullable') $dependencies[] = 'null_t';
        if ($carrier['family'] === 'result') $dependencies = array_merge($dependencies, ['string', 'int64']);
    }
    $plan['carrier_publication'] = ['provider' => $provider,
        'requires' => array_merge(array_values($placements), exportProviders($plan['submodules'], array_values(array_unique($dependencies))))];
}

/** Concrete storage selection shared by runtime publication and future application specialization. */
function hashConcreteRecipe(array $hash): string {
    requireThat($hash['storage'] === 'private_ordered_concrete_swiss', 'Unsupported hash concrete storage');
    return 'collections/hash';
}
/** Bind the same copy/consume factory body for runtime and application hashes. */
function hashInputFactoryArguments(array $initialization, string $mode, string $key, string $value): array {
    $consume = $mode === 'consume';
    return [
        'factory' => $initialization[$mode], 'qualifier' => $consume ? '' : 'const ',
        'key_transfer' => $consume ? "static_cast<$key&&>(values[i].key)" : "$key(values[i].key)",
        'value_transfer' => $consume ? "static_cast<$value&&>(values[i].value)" : "$value(values[i].value)",
    ];
}

/** Key operations are selected once from the bounded strict-key policy, independently of the value provider. */
function hashConcreteArguments(array $hash, string $count): array {
    return [
        'index' => '::scpp::detail::concrete_hash_index', 'candidate' => '::scpp::detail::hash_candidate',
        'count' => '::scpp::' . $count,
        'enable_keyed_input' => '', 'enable_shared_lookup' => '', 'enable_owner_presence' => '', 'enable_append' => $hash['key'] === 'int64' ? '1' : '',
        'append' => $hash['key'] === 'int64' ? template('collections/hash_append', []) : '',
    ];
}
function prepareHashes(mixed $metadata, mixed $publication, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'hash', 'hashes', 'Hash');
    if($m === null) {
        return;
    }
    fields($m, ['map_domain', 'access_contracts', 'provider', 'source_type', 'binding', 'construction', 'key_types', 'value_types', 'count_type', 'key_policy', 'key_contracts', 'read', 'optional_lookup', 'append', 'initialization', 'transfer_assignment']);
    $transfer = privateValueTransfer($m);
    literals($m, ['source_type' => 'hash', 'binding' => 'ordered_typed_swiss', 'construction' => 'empty', 'count_type' => 'int64', 'key_policy' => 'strict', 'read' => 'value_copy', 'optional_lookup' => 'nullable', 'append' => 'max_live_nonnegative_plus_one']);
    $keys = names(at($m, 'key_types'), 'Duplicate hash key type');
    $values = names(at($m, 'value_types'), 'Duplicate hash value type');
    requireThat($keys === ['int64', 'string'] && !array_diff($values, ['uint8', 'int64', 'string', 'vector_string']), 'Unsupported hash type policy');
    $initialization = $m->initialization ?? null;
    $callables = [];
    if ($initialization !== null) {
        fields($initialization, ['mode', 'duplicates', 'read_only', 'consume', 'entry', 'input', 'presizing', 'transfer', 'stack_bytes', 'inline_entries']);
        literals($initialization, ['mode' => 'ordered_keyed', 'duplicates' => 'reject', 'presizing' => 'entry_count', 'transfer' => 'nothrow']);
        foreach (['read_only', 'consume', 'entry', 'input'] as $field) {
            if ($field === 'consume' && !isset($initialization->$field)) continue;
            $name = textValue(at($initialization, $field));
            requireThat(recordIdentifier($name), 'Invalid keyed initialization name');
            requireThat(!in_array($name, $callables, true), 'Duplicate keyed initialization name');
            $callables[] = $name;
        }
        // Both the guard name and the entry type are used inside the guard's class scope.
        foreach ([$initialization->input, $initialization->entry] as $guardType) {
            requireThat(!in_array($guardType, ['data', 'data_'], true), 'Keyed initialization type conflicts with an input guard member');
        }
        requireThat(is_int($initialization->stack_bytes) && $initialization->stack_bytes >= 1 && $initialization->stack_bytes <= 65536, 'Invalid keyed input byte budget');
        requireThat(is_int($initialization->inline_entries) && $initialization->inline_entries >= 0 && $initialization->inline_entries <= 65536, 'Invalid keyed inline entry count');
    }
    $keyContracts = [];
    foreach (object(at($m, 'key_contracts')) as $identity => $contract) {
        fields($contract, ['hash', 'equal', 'nonthrowing']);
        requireThat(in_array($identity, names($m->key_types), true), 'Hash contract requires a registered key type');
        requireThat(at($contract, 'nonthrowing') === true, 'Hash key operations must be nonthrowing');
        $hash = textValue(at($contract, 'hash'));
        $equal = textValue(at($contract, 'equal'));
        requireThat(str_contains($hash, '@key@') && str_contains($equal, '@left@') && str_contains($equal, '@right@'), 'Incomplete hash key contract');
        $keyContracts[$identity] = compact('hash', 'equal');
    }
    requireThat(count($keyContracts) === count(names($m->key_types)), 'Missing hash key contract');
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
        requireThat(in_array($key, $keys, true) && in_array($value, $values, true), 'Unsupported hash key or value type');
        foreach([$key, $value] as $type) {
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
        $traversal = collectionTraversalPlan() + ['next_key_method' => 'iteration_next_key', 'current_value_method' => 'iteration_current_value'];
        $occupied = array_merge(array_column($methods, 'name'), [$name, 'storage', 'storage_', 'iteration_output', 'iteration_reference', 'get_or_null', $key, $value, $nullable, 'int64', 'bool_t', $traversal['limit_method'], $traversal['next_method'], $traversal['next_key_method'], $traversal['current_value_method']]);
        requireThat(array_intersect($callables, $occupied) === [], 'Keyed initialization name conflicts with a published member');
        $hashes[$name] = compact('name', 'key', 'value', 'methods', 'initialization') + ['source_family' => textValue($m->source_type), 'construction' => textValue($m->construction), 'key_policy' => 'strict', 'read' => 'value_copy', 'copy' => 'independent', 'move' => 'transfer_source_empty', 'storage' => 'private_ordered_concrete_swiss', 'mutation_failure' => 'logical_contents_unchanged', 'traversal' => $traversal];
    }
    requireThat($hashes !== [], 'Empty hash publication');
    ksort($hashes, SORT_STRING);
    publishTypeExports($plan['submodules'], 'hashes', array_keys($hashes));
    $dependencies = ['bool_t', 'int64'];
    foreach ($hashes as $hash) $dependencies = array_merge($dependencies, [$hash['key'], $hash['value'], 'nullable_' . $hash['value']]);
    $plan['hash_publication'] = ['provider' => $provider, 'requires' => exportProviders($plan['submodules'], array_values(array_unique($dependencies)))];
    $plan['hashes'] = ['transfer_assignment' => $transfer, 'key_contracts' => $keyContracts, 'header' => $header, 'count_type' => 'int64', 'specializations' => array_values($hashes)];
}
const DATETIME_SIGNATURES = [ 'now_unix_seconds' => ['int64', []], 'now_unix_millis' => ['int64', []], 'monotonic_millis' => ['int64', []], 'monotonic_micros' => ['uint64', []], 'monotonic_nanos' => ['uint64', []], 'sleep_millis' => ['void', ['int64']], 'format_iso_utc' => ['string', ['int64']], 'parse_iso_utc' => ['result_int64', ['string']], 'parse_common_local' => ['result_int64', ['string']], 'format_local' => ['string', ['string', 'int64']], 'format_local_now' => ['string', ['string']]];
function prepareDatetime(mixed $metadata, mixed $publication, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'datetime', 'datetime', 'Datetime');
    if($m === null) {
        return;
    }
    fields($m, ['binding', 'utc_years', 'parse_failure', 'format_failure', 'operations', 'local_dst', 'general_names']);
    literals($m, ['binding' => 'native_datetime', 'utc_years' => '0000_9999', 'local_dst' => 'host_normalize_and_select', 'parse_failure' => 'result_error', 'format_failure' => 'throw_runtime_error']);
    $registered = names(at($m, 'operations'), 'Duplicate datetime operation policy');
    requireThat($registered !== [], 'Empty datetime policy');
    $generalNames = object($m->general_names ?? new \stdClass());
    requireThat(array_diff(array_keys($generalNames), $registered) === [], 'Unregistered datetime source mapping');
    names(array_values($generalNames), 'Duplicate datetime source name');
    foreach($registered as $name) {
        if (isset($generalNames[$name])) requireThat(preg_match('/^dt_[a-z_0-9]+$/D', $generalNames[$name]) === 1, 'Invalid datetime source name');
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
        $identities = array_diff(array_unique([...$args, $result]), ['void']);
        $includes = array_map(fn(string $id): string => providerHeader($plan['submodules'], $id),
            exportProviders($plan['submodules'], $identities));
        $operations[] = ['name' => $name, 'source_name' => $generalNames[$name] ?? null, 'parameters' => $args,
            'header' => moduleOperationHeader($header, $name), 'includes' => $includes, 'result' => $result, 'arguments' => array_map(fn(string $a):string => "const $a&", $args)];
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
    $used = array_merge($inputs, array_column($plan['vectors'], 'name'), array_column($plan['carriers'], 'name'), array_column($plan['hashes']['specializations'] ?? [], 'name'), array_keys(object($plan['aliases'])), ['error_t', 'exception', 'null'], isset($plan['boxed']) ? array_merge(['boxed_kind'], array_column($plan['boxed']['families'], 'name')) : []);
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

/** Native const-reference slots in adapter call order. */
function stringAdapterConstReferenceMask(array $types): int {
    $mask = 0;
    foreach ($types as $i => $type) {
        if ($type === 'string' || $type === 'vector<string>' || $type === 'nullable<int64>') $mask |= 1 << $i;
    }
    return $mask;
}

function stringAdapterOrder(array $arguments, array $adapter): array {
    if (isset($adapter['swap_first_two'])) {
        [$arguments[0], $arguments[1]] = [$arguments[1], $arguments[0]];
    }
    return $arguments;
}
