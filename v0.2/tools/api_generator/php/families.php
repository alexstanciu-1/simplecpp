<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/boxed.php';

function checkHeader(array $plan, string $header): void {
    $paths = array_values($plan['headers']);
    foreach (['string', 'hashes', 'datetime', 'boxed'] as $key) {
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
    fields($publication, array_merge(SCALAR_FIELDS, ['string', 'vectors', 'carriers', 'ownership', 'hashes', 'datetime', 'boxed']));
    $scalarRequest = (object)array_intersect_key(object($publication), array_flip(SCALAR_FIELDS));
    $plan = prepareScalars($metadata, $scalarRequest, $facts);
    $registry = registry($metadata, $facts);
    prepareString($metadata, $publication, $registry, $plan);
    prepareVectors($metadata, $publication, $registry, $plan);
    prepareBoxed($metadata, $publication, $facts, $plan);
    prepareCarriers($metadata, $publication, $plan);
    prepareHashes($metadata, $publication, $plan);
    prepareDatetime($metadata, $publication, $plan);
    prepareOwnership($metadata, $publication, $facts, $plan);
    sortConversions($plan['conversions']);
    return $plan;
}

function prepareString(mixed $metadata, mixed $publication, array $registry, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'string', 'string', 'String');
    if ($m === null) {
        return;
    }
    fields($m, ['type', 'source_type', 'storage', 'count_type', 'construction', 'null_pointer', 'text_casts', 'operations']);
    literals($m, ['type' => 'string', 'source_type' => 'string', 'storage' => 'private_std_string', 'construction' => 'explicit', 'null_pointer' => 'reject']);
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
    fields($p, ['header', 'operations', 'casts']);
    $header = headerPath(at($p, 'header'));
    checkHeader($plan, $header);
    requireThat(hasType($plan, $count), 'String requires published count type');
    requireThat(hasValue($plan, 'bool_t'), 'String requires published boolean type');
    requireThat(hasNative($plan, 'long long'), 'String count requires explicit native long long construction');
    $operations = [];
    foreach (names(at($p, 'operations'), 'Duplicate published string operation') as $op) {
        requireThat(in_array($op, $caps, true), 'String operation is not enabled by metadata');
        $append = in_array($op, ['add', 'add_assign'], true);
        $mutates = $op === 'add_assign';
        $operations[] = ['operation' => $op, 'symbol' => $mutates ? '+=':SYMBOLS[$op], 'form' => $mutates ? 'compound':'binary', 'implementation' => $append ? 'string_append':'string_compare', 'execution' => 'string', 'result' => $append ? 'string':'bool_t', 'left' => 'string', 'right' => 'string', 'result_category' => $mutates ? 'left_reference':'value'];
    }
    foreach (names(at($p, 'casts'), 'Duplicate published text cast') as $target) {
        $policy = hasType($plan, $target) ? 'decimal_integer':($target === 'float64' && hasValue($plan, $target) ? 'decimal_float':($target === 'bool_t' && hasValue($plan, $target) ? 'boolean_words':''));
        requireThat($policy !== '', 'Text cast requires a published numeric or boolean type');
        requireThat(in_array($policy, $casts, true), 'Text cast is not enabled by metadata');
        $plan['conversions'][] = conversion($target, 'string', false, false, $target === 'bool_t' ? 'parse_boolean_text':'parse_decimal', true);
        $plan['conversions'][] = conversion('string', $target, false, $target !== 'float64', $target === 'bool_t' ? 'format_boolean_text':'format_decimal', true);
    }
    $methods = [];
    foreach (['size' => $count, 'empty' => 'bool_t', 'data' => 'const char*', 'append' => 'void', 'clear' => 'void'] as $name => $result) {
        $methods[] = ['name' => $name, 'result' => $result, 'receiver' => in_array($name, ['append', 'clear'], true) ? 'mutable_reference':'const_reference', 'arguments' => $name === 'append' ? ['const string&']:[], 'result_category' => $name === 'data' ? 'borrowed_bytes_until_mutation_or_destruction':'value', 'can_fail' => in_array($name, ['size', 'append'], true)];
    }
    $plan['source_types']->string = 'string';
    $plan['string'] = ['name' => 'string', 'count_type' => $count, 'header' => $header, 'storage' => 'private_std_string', 'lifecycle' => 'owned_copy_noexcept_move', 'empty_state' => 'null_storage', 'operations' => $operations, 'methods' => $methods, 'construction' => [['arguments' => ['const char*'], 'explicit' => true, 'null_pointer' => 'reject', 'can_fail' => true], ['arguments' => ['const char*', $count], 'explicit' => true, 'null_pointer' => 'reject', 'can_fail' => true]]];
}

function prepareVectors(mixed $metadata, mixed $publication, array $registry, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'vector', 'vectors', 'Vector');
    if ($m === null) {
        return;
    }
    fields($m, ['source_type', 'storage', 'count_type', 'read', 'mutation', 'element_families', 'native_index', 'native_value', 'operations']);
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
    $header = headerPath(at($p, 'header'));
    checkHeader($plan, $header);
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
    fields($m, ['extraction', 'construction', 'families', 'error']);
    $extraction = textValue(at($m, 'extraction'));
    requireThat(in_array($extraction, ['implicit_checked_one_layer_value_copy', 'explicit_checked_one_layer_value_copy'], true), 'Unsupported carrier extraction policy');
    literals($m, ['construction' => 'explicit']);
    $error = at($m, 'error');
    fields($error, ['type', 'message', 'line', 'file', 'state']);
    literals($error, ['type' => 'error_t', 'message' => 'string', 'line' => 'int64', 'file' => 'string', 'state' => 'explicit_present']);
    $families = at($m, 'families');
    fields($families, array_keys(CARRIER_DEFAULTS));
    foreach(object($families) as $f => $state) {
        requireThat(textValue($state) === CARRIER_DEFAULTS[$f], 'Unsupported carrier default state');
    }
    if(!property_exists($publication, 'carriers')) {
        return;
    }
    $p = $publication->carriers;
    fields($p, ['header', 'specializations']);
    $header = headerPath(at($p, 'header'));
    checkHeader($plan, $header);
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
        $carriers[$name] = compact('name', 'family', 'payload', 'extraction') + ['default_state' => CARRIER_DEFAULTS[$family], 'boxing' => 'explicit', 'extraction_can_fail' => true, 'non_payload_extraction' => 'throw_runtime_error', 'condition' => 'query_state_explicitly', 'lifecycle' => 'owned_copy_move_resets_source_to_default', 'lifted_operators' => false, 'result_category' => 'value', 'explicit_lowering' => 'value_then_payload_operation'];
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
}

function prepareHashes(mixed $metadata, mixed $publication, array &$plan): void {
    $m = familyMetadata($metadata, $publication, 'hash', 'hashes', 'Hash');
    if($m === null) {
        return;
    }
    fields($m, ['binding', 'key_types', 'value_types', 'count_type', 'key_policy', 'read', 'optional_lookup', 'append']);
    literals($m, ['binding' => 'ordered_typed_swiss', 'count_type' => 'int64', 'key_policy' => 'strict', 'read' => 'value_copy', 'optional_lookup' => 'nullable', 'append' => 'max_live_nonnegative_plus_one']);
    foreach(['key_types', 'value_types'] as $key) {
        requireThat(names(at($m, $key), 'Duplicate hash type policy') === ['int64', 'string'], 'Unsupported hash type policy');
    }
    if(!property_exists($publication, 'hashes')) {
        return;
    }
    $p = $publication->hashes;
    fields($p, ['header', 'specializations']);
    $header = headerPath(at($p, 'header'));
    checkHeader($plan, $header);
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
        requireThat(in_array($nullable, array_column($plan['carriers'], 'name'), true), 'Hash find requires published nullable payload');
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
        $hashes[$name] = compact('name', 'key', 'value', 'methods') + ['source_family' => 'hash', 'key_policy' => 'strict', 'read' => 'value_copy', 'copy' => 'independent', 'move' => 'transfer_source_empty', 'storage' => 'private_ordered_typed_swiss', 'mutation_failure' => 'logical_contents_unchanged', 'traversal' => 'deferred_RT-DEBT-01'];
    }
    requireThat($hashes !== [], 'Empty hash publication');
    ksort($hashes, SORT_STRING);
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
