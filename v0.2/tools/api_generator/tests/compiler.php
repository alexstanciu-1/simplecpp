<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/../php/input.php';
require_once __DIR__ . '/../php/target.php';
require_once __DIR__ . '/../php/scalars.php';
require_once __DIR__ . '/../php/publication.php';
require_once __DIR__ . '/../php/render_compiler.php';
require_once __DIR__ . '/../php/families.php';
require_once __DIR__ . '/../php/render.php';
require_once __DIR__ . '/../php/render_sequences.php';

/** Locate a normalized test row by canonical identity, independent of family expansion order. */
function conversion_row(object $metadata, string $from, string $to): object {
    foreach ($metadata->compiler->conversions as $row) if ($row->from === $from && $row->to === $to) return $row;
    throw new \LogicException('Missing test conversion');
}

$root = dirname(__DIR__, 3);
$facts = targetFacts(decode(readFile($argv[1]), 'facts'), decode(readFile($argv[2]), 'context'));
$metadata = decode(readFile($root . '/runtime/metadata/runtime.json'), 'metadata');
$originalExportMetadata = serialize($metadata);
$files = renderCompiler($metadata, $facts);
// The target, rather than the PHP host or ordinary LL carrier, selects exact overload literals.
foreach (['long' => 'L', 'long long' => 'LL'] as $native => $suffix) {
    $target = $facts;
    $target['fixed_integers']['int64']['native_name'] = $native;
    $integer = renderCompiler($metadata, $target)['types/int64.php'];
    requireThat(str_contains($integer, "'(-9223372036854775807$suffix - 1$suffix)' : \$value . '$suffix'"),
        'Exact argument literals ignored target native identity');
}
// A runtime function must follow the published source spelling and binding in both outputs.
$functionMetadata = unserialize($originalExportMetadata);
$declaration = $functionMetadata->string->functions->strlen;
unset($functionMetadata->string->functions->strlen);
$functionMetadata->string->functions->count_bytes = $declaration;
$declaration->symbol = 'scpp::str::measured_bytes';
$demand = decode(readFile($root . '/runtime/metadata/publication.json'), 'publication');
$demand->string->functions = ['count_bytes'];
$functionPlan = prepareRuntime($functionMetadata, $demand, $facts);
$registry = renderCompiler($functionMetadata, $facts, $functionPlan)['types/registry.php'];
requireThat(str_contains($registry, "'func:count_bytes'") && str_contains($registry, "'scpp::str::measured_bytes'")
    && !str_contains($registry, "'func:strlen'"), 'Source function registration ignored runtime metadata');
requireThat(str_contains(renderString($functionPlan)[0], 'measured_bytes(const string&'), 'String binding ignored runtime metadata');
$demand->string->functions = ['string_byte_from_int'];
$bytePlan = prepareRuntime($functionMetadata, $demand, $facts);
$registry = renderCompiler($functionMetadata, $facts, $bytePlan)['types/registry.php'];
requireThat(str_contains($registry, "'func:string_byte_from_int'") && !str_contains($registry, "'func:count_bytes'")
    && str_contains(renderString($bytePlan)[0], 'string byte_from_int(int64 value)'), 'Checked byte function publication mismatch');
$demand->string->functions = ['str_contains'];
$functionMetadata->string->functions->str_contains->members->js = 'hasBytes';
$predicatePlan = prepareRuntime($functionMetadata, $demand, $facts);
$registry = renderCompiler($functionMetadata, $facts, $predicatePlan)['types/registry.php'];
requireThat(str_contains($registry, "'js:string:hasBytes'") && str_contains($registry, "'php:func:str_contains'")
    && !str_contains($registry, "'js:string:includes'") && !str_contains($registry, "'cpp:string:starts_with'"),
    'String member lookup ignored flavor metadata or publication selection');
// Whole-string comparison adapters can each be published independently of the windowed comparison.
foreach (['strcmp' => 'false', 'strcasecmp' => 'true'] as $name => $fold) {
    $demand->string->functions = [$name];
    $comparison = prepareRuntime($functionMetadata, $demand, $facts);
    $comparisonFiles = $groups = [];
    $definitions = '';
    renderSequences($comparison, $comparisonFiles, $definitions, $groups);
    $header = $comparisonFiles['public/scpp/php/string.hpp'];
    requireThat(str_contains($header, " $name(") && str_contains($header, "compare_bytes(arg0, arg1, $fold)")
        && !str_contains($header, 'substr_compare(') && substr_count($definitions, 'static int64 compare_byte_views(') === 1,
        'Isolated byte comparison publication mismatch');
}
// A flavor result is part of the public signature and must be available in this publication.
foreach (['strpos' => 'int64', 'hex2bin' => 'string'] as $name => $payload) {
    $restricted = unserialize(serialize($demand));
    $restricted->string->functions = [$name];
    $restricted->carriers->specializations = array_values(array_filter($restricted->carriers->specializations,
        static fn($row) => $row->family !== 'result_or_false' || $row->payload !== $payload));
    try {
        prepareRuntime($functionMetadata, $restricted, $facts);
        throw new \LogicException('Missing result carrier was accepted for ' . $name);
    } catch (\RuntimeException $error) {
        requireThat(str_contains($error->getMessage(), "$name requires published result_or_false<$payload>"), $error->getMessage());
    }
}
$demand->string->functions = [];
requireThat(!isset(prepareRuntime($functionMetadata, $demand, $facts)['string_functions']), 'Deselected string function remains published');
// A published formatter supplies the runtime and compiler binding together.
$functionMetadata->string->explicit_formatters->int64->symbol = 'format_integer_probe';
$formatPlan = prepareRuntime($functionMetadata, $demand, $facts);
requireThat(str_contains(renderString($formatPlan)[0], 'format_integer_probe(int64 value)')
    && str_contains(renderCompiler($functionMetadata, $facts, $formatPlan)['types/string.php'], 'scpp::str::format_integer_probe'),
    'Text cast binding ignored formatter metadata');
$demand->string->explicit_formatters = [];
$formatPlan = prepareRuntime($functionMetadata, $demand, $facts);
requireThat(!isset($formatPlan['explicit_formatters'])
    && !str_contains(renderCompiler($functionMetadata, $facts, $formatPlan)['types/string.php'], 'value_conversion::format_int64'),
    'Deselected formatter remains available to the compiler');
// A publication may rename or omit the checked string read independently of other string operations.
$functionMetadata->string->index_read->method = 'read_byte_probe';
$indexPlan = prepareRuntime($functionMetadata, $demand, $facts);
requireThat(str_contains(renderString($indexPlan)[0], 'uint8 read_byte_probe(int64 index) const')
    && str_contains(renderCompiler($functionMetadata, $facts, $indexPlan)['types/string.php'], '.read_byte_probe('),
    'String index lowering ignored its published binding');
$demand->string->index_read = false;
$indexPlan = prepareRuntime($functionMetadata, $demand, $facts);
requireThat(!str_contains(renderString($indexPlan)[0], 'read_byte_probe')
    && !str_contains(renderCompiler($functionMetadata, $facts, $indexPlan)['types/string.php'], 'implements index_read_type'),
    'Deselected string read is still published');
// Byte replacement can be published without a read and has its own runtime binding.
$functionMetadata->string->index_write->method = 'replace_byte_probe';
$writePlan = prepareRuntime($functionMetadata, $demand, $facts);
requireThat(str_contains(renderString($writePlan)[0], 'void replace_byte_probe(int64 index, uint8 value)')
    && str_contains(renderCompiler($functionMetadata, $facts, $writePlan)['types/string.php'], '.replace_byte_probe('),
    'String store ignored its published binding');
$demand->string->index_write = false;
$writePlan = prepareRuntime($functionMetadata, $demand, $facts);
requireThat(!str_contains(renderString($writePlan)[0], 'replace_byte_probe')
    && !str_contains(renderCompiler($functionMetadata, $facts, $writePlan)['types/string.php'], 'index_write_type'),
    'Disabled string store is still published');
// Export planning is pure and order independent; public names do not select storage strategy.
$reorderedExports = unserialize($originalExportMetadata);
$reorderedExports->type_exports = array_reverse($reorderedExports->type_exports);
$reorderedExports->submodules->{'core.string'}->requires_exports = array_reverse($reorderedExports->submodules->{'core.string'}->requires_exports);
requireThat(renderCompiler($reorderedExports, $facts) === $files, 'Export order changed compiler artifacts');
requireThat(serialize($metadata) === $originalExportMetadata, 'Export resolution mutated metadata');
// Integer domains are selected from representation facts, independently of source-name aliases.
$integerTypes = registry($metadata, $facts);
$integerPolicy = integerPromotionPolicy($metadata, $integerTypes);
foreach ([['uint8', 'uint8', 'uint8'], ['int8', 'int16', 'int64'], ['uint8', 'uint16', 'uint64'],
    ['int64', 'uint8', 'int64'], ['uint32', 'int8', 'int64'], ['int64', 'uint64', null],
    ['uint64', 'int8', null]] as [$left, $right, $execution]) {
    requireThat(integerExecution($integerTypes[$left], $integerTypes[$right], $integerPolicy) === $execution, 'Incorrect integer domain selection');
}
$rawMetadata = unserialize(serialize($metadata));
$limitedGroup = unserialize(serialize($metadata));
$limitedGroup->compiler->integer_operators->arithmetic = ['subtract'];
$limitedGroup->compiler->integer_operators->comparisons = ['equal'];
$limitedGroup->compiler->mixed_numeric_operators->arithmetic = [];
$limitedGroup->compiler->mixed_numeric_operators->comparisons = [];
$limitedGroup->compiler->mixed_numeric_operators->compound_operators = [];
$limitedFiles = renderCompiler($limitedGroup, $facts);
foreach (['int64', 'uint8'] as $identity) {
    requireThat(!str_contains($limitedFiles['types/' . $identity . '.php'], 'binary_operator::add')
        && !str_contains($limitedFiles['types/' . $identity . '.php'], 'binary_operator::less'), 'Integer group removal ignored');
}
foreach (['duplicate_owner', 'unknown_operator', 'bad_policy', 'bad_binding'] as $change) {
    $invalidGroup = unserialize(serialize($rawMetadata));
    match ($change) {
        'duplicate_owner' => $invalidGroup->compiler->arithmetic->uint8 = (object)[],
        'unknown_operator' => $invalidGroup->compiler->integer_operators->arithmetic[] = 'power',
        'bad_policy' => $invalidGroup->promotion_policies->integer_domain_maximum->different_unsigned = 'int64',
        'bad_binding' => $invalidGroup->compiler->integer_operators->bindings->uint8->divide = 'missing',
    };
    try {
        renderCompiler($invalidGroup, $facts);
        throw new \LogicException('Invalid integer group accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Compound admission is independent of binary admission and checked conversion availability.
$disabledCompounds = unserialize(serialize($rawMetadata));
$disabledCompounds->compiler->integer_operators->compounds->types = [];
$disabledCode = renderCompiler($disabledCompounds, $facts)['types/uint8.php'];
requireThat(!str_contains($disabledCode, 'public function prepare_compound')
    && str_contains($disabledCode, 'binary_operator::add'), 'Compound removal changed binary admission');
foreach (['policy', 'permission'] as $change) {
    $invalid = unserialize(serialize($rawMetadata));
    if ($change === 'policy') $invalid->compiler->integer_operators->compounds->write_back = 'unchecked';
    else $invalid->compiler->conversion_families->{'integer:integer'}->contexts = ['explicit_cast', 'typed_destination'];
    try {
        renderCompiler($invalid, $facts);
        throw new \LogicException('Invalid compound write-back accepted');
    } catch (\RuntimeException $expected) {
    }
}
// Family permissions affect every active representation; removing a rule cannot leave pair-specific remnants.
$familySubset = unserialize(serialize($rawMetadata));
unset($familySubset->compiler->conversion_families->{'boolean:integer'});
$familyCode = renderCompiler($familySubset, $facts);
foreach (['int64', 'uint8'] as $identity) requireThat(!str_contains($familyCode['types/' . $identity . '.php'], '$value->value_type() === type_bool::instance()'), 'Removed family retained a conversion');
foreach (['unknown', 'algorithm', 'implicit', 'duplicate_owner', 'binding'] as $change) {
    $invalid = unserialize(serialize($rawMetadata));
    match ($change) {
        'unknown' => $invalid->compiler->conversion_families->{'imaginary:integer'} = (object)['operation' => 'boolean_integer', 'contexts' => []],
        'algorithm' => $invalid->compiler->conversion_families->{'floating:integer'}->operation = 'integer_widen',
        'implicit' => $invalid->compiler->conversion_families->{'boolean:integer'}->contexts = ['assignment'],
        'duplicate_owner' => $invalid->compiler->conversions = [],
        'binding' => $invalid->compiler->value_types->uint8->conversion_binding = 'missing',
    };
    try {
        renderCompiler($invalid, $facts);
        throw new \LogicException('Invalid family conversion metadata accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Mixed operators expand from active families, with independently removable compound permission.
$definitions = compilerTypePlan(compilerProviderMetadata($rawMetadata), $facts, $integerTypes);
$expanded = compilerMixedNumericOperations(clone $rawMetadata->compiler, $definitions);
requireThat(count($expanded->promotions) === 8, 'Expected both integer representations in mixed groups');
$subset = unserialize(serialize($rawMetadata));
$subset->compiler->mixed_numeric_operators->arithmetic = [];
$subset->compiler->mixed_numeric_operators->compound_operators = [];
$rows = compilerMixedNumericOperations($subset->compiler, $definitions)->promotions;
requireThat(count($rows) === 4, 'Arithmetic removal changed mixed comparisons');
foreach ($rows as $row) requireThat($row->result === 'bool_t' && !isset($row->compound_operators), 'Comparison admitted arithmetic writes');
foreach (['duplicate', 'arithmetic', 'comparison', 'compound', 'policy', 'conversion'] as $change) {
    $bad = unserialize(serialize($rawMetadata));
    match ($change) {
        'duplicate' => $bad->compiler->promotions = [],
        'arithmetic' => $bad->compiler->mixed_numeric_operators->arithmetic[] = 'remainder',
        'comparison' => $bad->compiler->mixed_numeric_operators->comparisons[] = 'add',
        'compound' => $bad->compiler->mixed_numeric_operators->compound_operators[] = 'remainder',
        'policy' => $bad->compiler->mixed_numeric_operators->promotion = 'integer_operand',
        'conversion' => $bad->compiler->conversion_families->{'integer:floating'}->operation = 'integer_widen',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid mixed numeric family admitted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Integer single-operand capabilities share one family owner and remain independently removable.
$subset = unserialize(serialize($rawMetadata));
$subset->compiler->integer_operators->unary = [];
$subset->compiler->integer_operators->updates = [];
$generated = renderCompiler($subset, $facts);
foreach (['int64', 'uint8'] as $name) {
    requireThat(!str_contains($generated['types/' . $name . '.php'], 'public function prepare_unary')
        && !str_contains($generated['types/' . $name . '.php'], 'public function supports_update')
        && str_contains($generated['types/' . $name . '.php'], 'public function prepare_condition'), 'Integer capability removal ignored');
}
foreach (['unary', 'updates', 'condition', 'duplicate', 'runtime'] as $change) {
    $bad = unserialize(serialize($rawMetadata));
    match ($change) {
        'unary' => $bad->compiler->integer_operators->unary[] = 'invert',
        'updates' => $bad->compiler->integer_operators->updates[] = 'double',
        'condition' => $bad->compiler->integer_operators->condition = 'identity',
        'duplicate' => $bad->compiler->conditions->uint8 = 'nonzero_integer',
        'runtime' => $bad->operations->negate->promotion = 'integer_domain_maximum',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid integer single-operand family admitted');
    } catch (\RuntimeException $expected) {
    }
}
// Shift counts have their own domain; they never use arithmetic promotion to the left type.
$subset = unserialize(serialize($rawMetadata));
$subset->compiler->integer_operators->bitwise = [];
$subset->compiler->integer_operators->shifts->operators = [];
$generated = renderCompiler($subset, $facts);
foreach (['int64', 'uint8'] as $name) {
    requireThat(!str_contains($generated['types/' . $name . '.php'], 'binary_operator::shift_left')
        && !str_contains($generated['types/' . $name . '.php'], 'binary_operator::bit_and'), 'Removed group remained');
}
foreach (['count', 'operator', 'binding', 'safety'] as $change) {
    $bad = unserialize(serialize($rawMetadata));
    match ($change) {
        'count' => $bad->compiler->integer_operators->shifts->count_type = 'uint8',
        'operator' => $bad->compiler->integer_operators->bitwise[] = 'divide',
        'binding' => $bad->compiler->runtime_bindings->uint8_shift_left->count = 'uint8',
        'safety' => $bad->compiler->lowering_strategies->checked_shift->reorderable = true,
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid bitwise/shift metadata accepted');
    } catch (\RuntimeException $expected) {
    }
}
// Expansion owns a private copy; repeated generation must not consume the caller's family policies.
$originalMetadata = serialize($metadata);
$registry = registry($metadata, $facts);
$definitions = compilerTypePlan(compilerProviderMetadata($metadata), $facts, $registry);
$familyPlan = compilerFamilyPlan($metadata, $definitions, $registry);
requireThat(serialize($metadata) === $originalMetadata, 'Family planning mutated input metadata');
requireThat(serialize(compilerFamilyPlan($metadata, $definitions, $registry)) === serialize($familyPlan), 'Family planning is not repeatable');

// Test grouped metadata expansion separately, then exercise the existing normalized handler validators.
$registry = registry($metadata, $facts);
$definitions = compilerTypePlan(compilerProviderMetadata($metadata), $facts, $registry);
$normalized = compilerConversionFamilies(unserialize(serialize($metadata->compiler)), $definitions, $registry);
$normalized = compilerMixedNumericOperations($normalized, $definitions);
$requirements = array_map(static fn($row) => ['promotion' => $row, 'policy' => 'family'], $normalized->promotions);
$normalized = compilerIntegerOperations($metadata, $normalized, $definitions, $registry, $requirements);
compilerPromotionPermissions($normalized, $requirements);
$metadata->compiler = $normalized;
requireThat(renderCompiler($metadata, $facts) === $files, 'Integer group expansion changed normalized emission');
$metadata->compiler->promotions = array_values(array_filter($metadata->compiler->promotions,
    static fn($row) => $row->left === 'float64' || $row->right === 'float64'));
$files = renderCompiler($metadata, $facts);
requireThat(!isset($files['cpp/native_values.php']), 'Retired emitter trait still generated');
requireThat(str_contains($files['types/string.php'], 'String_Literals::decode'), 'Missing generated string preparation');
requireThat(str_contains($files['types/string.php'], 'scpp/string.hpp'), 'Missing generated string storage header');
$badString = unserialize(serialize($metadata));
$badString->string->count_type = 'int32';
try {
    renderCompiler($badString, $facts);
    throw new \LogicException('Unsupported string count accepted');
} catch (\RuntimeException $error) {
    requireThat(str_contains($error->getMessage(), 'count_type'), 'Unexpected string rejection');
}
requireThat(str_contains($files['types/int64.php'], "return 'scpp::int64'"), 'Missing metadata-derived representation');
$bad = clone $metadata;
$bad->types = clone $metadata->types;
unset($bad->types->int64);
try {
    renderCompiler($bad, $facts);
    throw new \LogicException('Missing int64 was accepted');
} catch (\RuntimeException $error) {
    requireThat(str_contains($error->getMessage(), 'Unknown type export: int64'), $error->getMessage());
}
requireThat(decimalPowerOfTwo(7) === '128' && decimalPredecessor('128') === '127', 'Integer boundary algorithm');
requireThat(decimalPowerOfTwo(63) === '9223372036854775808', 'Exact signed-width magnitude');
foreach ([
    ['inactive', 'default runtime'], ['overflow', 'overflow policy'], ['radix', 'radix'],
    ['suffix', 'suffix'], ['source', 'int64 metadata'], ['alias', 'no source name'],
] as [$change, $message]) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'inactive' => $bad->compiler->modules->{'default-runtime'}->always_available = false,
        'overflow' => $bad->compiler->integer_literals->overflow = 'wrap',
        'radix' => $bad->compiler->integer_literals->radix = 16,
        'suffix' => $bad->compiler->integer_literals->cpp_suffix = 'ULL',
        'source' => $bad->source_types->int = 'int32',
        'alias' => $bad->source_types->bool = 'int64',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Unsupported compiler metadata accepted');
    } catch (\RuntimeException $error) {
        requireThat(str_contains($error->getMessage(), $message), $error->getMessage());
    }
}
// Registration uses shared source spellings plus compiler-only aliases, without an independent whitelist.
$reordered = unserialize(serialize($metadata));
$reordered->compiler->modules->{'default-runtime'}->types = array_reverse($reordered->compiler->modules->{'default-runtime'}->types);
requireThat(renderCompiler($reordered, $facts) === $files, 'Module order changed generated output');
$aliasMetadata = unserialize(serialize($metadata));
$aliasMetadata->compiler->value_types->float64->source_names = ['real'];
$aliasMetadata->compiler->value_types->int64->source_names = [];
$aliasPlan = compilerTypePlan(compilerProviderMetadata($aliasMetadata), $facts, registry($aliasMetadata, $facts));
requireThat($aliasPlan['float64']['aliases'] === ['float', 'real'], 'Shared/extra floating aliases ignored');
requireThat($aliasPlan['int64']['aliases'] === ['int'], 'Removed compiler-only integer alias retained');
$aliasFiles = renderCompiler($aliasMetadata, $facts);
requireThat(str_contains($aliasFiles['types/float64.php'], "(\$name === 'real')"), 'Class ignored added alias');
requireThat(str_contains($aliasFiles['types/registry.php'], "'real'"), 'Registry ignored added alias');
foreach (['collision', 'duplicate_member', 'duplicate_owner', 'missing_definition', 'missing_member', 'inactive_definition', 'strategy'] as $change) {
    $bad = unserialize(serialize($metadata));
    switch ($change) {
        case 'collision': $bad->compiler->value_types->float64->source_names[] = 'int'; break;
        case 'duplicate_member': $bad->compiler->modules->{'default-runtime'}->types[] = 'int64'; break;
        case 'duplicate_owner': $bad->compiler->modules->optional = (object)['always_available' => false, 'types' => ['int64']]; break;
        case 'missing_definition': unset($bad->compiler->value_types->uint8); break;
        case 'missing_member':
            $bad->compiler->modules->{'default-runtime'}->types = array_values(array_diff($bad->compiler->modules->{'default-runtime'}->types, ['uint8']));
            break;
        case 'inactive_definition': $bad->compiler->value_types->extra = clone $bad->compiler->value_types->uint8; break;
        case 'strategy':
            $bad->compiler->modules->{'default-runtime'}->types[] = 'extra';
            $bad->compiler->value_types->extra = (object)['class' => 'extra', 'source_names' => ['extra']];
            break;
    }
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid registration accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Unsupported metadata is rejected before publication; conversion emission follows the metadata rows.
foreach (['byte_width', 'byte_implicit', 'conversion_kind', 'duplicate_conversion', 'literal', 'inactive_target'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'byte_width' => $bad->types->uint8->bits = 16,
        'byte_implicit' => conversion_row($bad, 'int64', 'uint8')->contexts = ['assignment'],
        'conversion_kind' => conversion_row($bad, 'bool_t', 'int64')->operation = 'truncate',
        'duplicate_conversion' => $bad->compiler->conversions[] = clone conversion_row($bad, 'bool_t', 'int64'),
        'literal' => $bad->compiler->value_types->bool_t->literals->true = 1,
        'inactive_target' => conversion_row($bad, 'bool_t', 'int64')->to = 'vector',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid scalar metadata accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
foreach (['overflow' => 'allow', 'underflow' => 'reject', 'rounding' => 'toward_zero', 'radix' => 16] as $field => $value) {
    $bad = unserialize(serialize($metadata));
    $bad->compiler->floating_literals->$field = $value;
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid float policy accepted: ' . $field);
    } catch (\RuntimeException $expected) {
    }
}
$bad = unserialize(serialize($metadata));
$bad->scalar_casts = array_values(array_diff($bad->scalar_casts, ['floating_to_integer_truncate_checked']));
try {
    renderCompiler($bad, $facts);
    throw new \LogicException('Compiler float conversion ignored missing runtime policy');
} catch (\RuntimeException $expected) {
    requireThat(str_contains($expected->getMessage(), 'runtime scalar policy'), $expected->getMessage());
}
$without = unserialize(serialize($metadata));
unset($without->compiler->three_way);
$without->compiler->conversions = [];
unset($without->compiler->three_way);
$without->compiler->promotions = [];
$withoutFiles = renderCompiler($without, $facts);
requireThat(!str_contains($withoutFiles['types/uint8.php'], 'scpp::uint8{'), 'Conversion ignored metadata removal');
requireThat(str_contains($files['types/uint8.php'], 'MAXIMUM /** uint8 */ = 255'), 'Missing generated byte limit');
requireThat(str_contains($files['types/bool.php'], 'value_conversion::integer_boolean'), 'Bool conversion missing');
requireThat(str_contains($files['types/int64.php'], 'value_conversion::boolean_integer'), 'Integer conversion missing');
$work = sys_get_temp_dir() . '/scpp-compiler-generation-' . bin2hex(random_bytes(8));
mkdir($work);
try {
    publish($files, $work, false, ['php', 'json']);
    publish($files, $work, true, ['php', 'json']);
    file_put_contents($work . '/retired.php', '<?php // obsolete');
    file_put_contents($work . '/keep.txt', 'retained');
    try {
        publish($files, $work, true, ['php', 'json']);
        throw new \LogicException('Freshness ignored obsolete PHP');
    } catch (\RuntimeException $error) {
        requireThat(str_contains($error->getMessage(), 'Obsolete generated file'), 'Unexpected freshness rejection');
    }
    publish($files, $work, false, ['php', 'json']);
    requireThat(!file_exists($work . '/retired.php') && file_exists($work . '/keep.txt'), 'Extension-scoped cleanup failed');
    // Exercise freshly generated definitions without depending on compiler build output.
    require $root . '/compiler/types/conversion.php';
    require $root . '/compiler/types/definition.php';
    require $work . '/types/int64.php';
    require $work . '/types/null.php';
    require $work . '/types/bool.php';
    require $work . '/types/uint8.php';
    require $work . '/types/float64.php';
    require $work . '/types/string.php';
    require $work . '/types/registry.php';
    require $root . '/compiler/03_parse/structures/abstract.php';
    require $root . '/compiler/03_parse/structures/common.php';
    $type = \scpp\compiler\type_int64::instance();
    requireThat(\scpp\compiler\type_registry::resolve('int') === $type && \scpp\compiler\type_registry::resolve('int64') === $type, 'Registration identity differs');
    requireThat(\scpp\compiler\type_registry::resolve('vector') === null, 'Out-of-scope type registered');
    requireThat($type::MINIMUM === PHP_INT_MIN && $type::MAXIMUM === PHP_INT_MAX, 'Generated bounds differ');
    requireThat($type->cpp_type() === 'scpp::int64', 'Wrong native type');
    requireThat($type->accepts_name('int') && $type->accepts_name('int64'), 'Missing canonical source aliases');
    foreach ([0, 8, PHP_INT_MAX, PHP_INT_MIN] as $value) {
        $expected = $value === PHP_INT_MIN ? '(-9223372036854775807LL - 1LL)' : $value . 'LL';
        $literal = new \scpp\compiler\ast_integer_expression(0, 1);
        $literal->settle_integer($value);
        requireThat($type->lower_cpp_literal($literal) === $expected, 'Incorrect literal lowering');
    }
} finally {
    foreach (['types/int64.php', 'types/null.php', 'types/bool.php', 'types/uint8.php', 'types/float64.php', 'types/string.php', 'types/registry.php', 'types/vectors.php', 'types/carriers.php', 'types/hashes.php', 'bindings/entry.php', 'inventory.json', 'retired.php', 'keep.txt'] as $path) {
        if (file_exists($work . '/' . $path)) unlink($work . '/' . $path);
    }
    rmdir($work . '/types');
    rmdir($work . '/bindings');
    rmdir($work);
}
echo "Generated native lowering, metadata admission, freshness and cleanup passed\n";

// Condition admission follows metadata independently of value conversions.
foreach (['int64', 'uint8', 'float64', 'bool', 'string'] as $type) {
    requireThat(str_contains($files['types/' . $type . '.php'], 'public function prepare_condition'), 'Missing generated condition handler');
}
foreach (['null'] as $type) {
    requireThat(!str_contains($files['types/' . $type . '.php'], 'public function prepare_condition'), 'Deferred condition was generated');
}
$without = unserialize(serialize($metadata));
unset($without->compiler->conditions->string);
requireThat(!str_contains(renderCompiler($without, $facts)['types/string.php'], 'public function prepare_condition'), 'Removed condition admission still generated');
$bad = unserialize(serialize($metadata));
$bad->compiler->conditions->string = 'native_bool_cast';
try {
    renderCompiler($bad, $facts);
    throw new \LogicException('Unspecified string truthiness accepted');
} catch (\RuntimeException $error) {
    requireThat(str_contains($error->getMessage(), 'condition policy'), 'Unexpected condition diagnostic');
}

// Condition generation consumes the resolved storage binding and category, not a second identity table.
$bound = unserialize(serialize($metadata));
// The runtime declaration owns spelling; the scoped export keeps its canonical name.
$bound->submodules->{'core.string'}->header = 'scpp/test_condition_string.hpp';
$boundCode = renderCompiler($bound, $facts)['types/string.php'];
requireThat(str_contains($boundCode, 'const scpp::string& condition_value'), 'Condition storage spelling ignored');
$start = strpos($boundCode, 'function condition_headers');
requireThat(str_contains(substr($boundCode, $start, strpos($boundCode, "\n\t}", $start) - $start), 'scpp/test_condition_string.hpp'), 'Condition header ignored');
$conditionDefinitions = compilerTypePlan(compilerProviderMetadata($metadata), $facts, registry($metadata, $facts));
$acceptedConditions = ['uint8' => 'nonzero_integer', 'int64' => 'nonzero_integer', 'float64' => 'nonzero_float', 'bool_t' => 'identity', 'string' => 'nonempty_except_zero'];
foreach (array_keys($conditionDefinitions) as $identity) {
    foreach (array_values($acceptedConditions) as $strategy) {
        $candidate = unserialize(serialize($metadata->compiler));
        $candidate->conditions = (object)[$identity => $strategy];
        $accepted = true;
        try {
            compilerConditionPlan($candidate, $conditionDefinitions);
        } catch (\RuntimeException $expected) {
            $accepted = false;
        }
        requireThat($accepted === (($acceptedConditions[$identity] ?? null) === $strategy), 'Condition admission changed');
    }
}
$renamed = $conditionDefinitions;
$renamed['other_integer'] = $renamed['int64'];
$renamedCompiler = (object)['conditions' => (object)['other_integer' => 'nonzero_integer']];
requireThat(isset(compilerConditionPlan($renamedCompiler, $renamed)['other_integer']), 'Condition strategy still depends on identity spelling');

// The normalized comparison plan retains validated metadata and resolves operator spellings only once.
$comparisonDefinitions = compilerTypePlan(compilerProviderMetadata($metadata), $facts, registry($metadata, $facts));
$comparisonPlan = compilerComparisonPlan(compilerProviderMetadata($metadata)->compiler, $comparisonDefinitions, names($metadata->string->operations));
foreach (object($metadata->compiler->comparisons) as $identity => $row) {
    $normalized = $comparisonPlan[$identity];
    foreach (['operand', 'result', 'lowering'] as $field) {
        requireThat($normalized[$field] === at($row, $field), 'Comparison field not retained: ' . $field);
    }
    requireThat(array_keys($normalized['operators']) === names($row->operators), 'Comparison operators changed');
}
$oneComparison = unserialize(serialize($metadata));
$oneComparison->compiler->comparisons->int64->operators = ['equal'];
$onePlan = compilerComparisonPlan(compilerProviderMetadata($oneComparison)->compiler, $comparisonDefinitions, names($metadata->string->operations));
requireThat($onePlan['int64']['operators'] === ['equal' => '=='], 'Comparison operator removal ignored');
$badComparison = unserialize(serialize($metadata));
$badComparison->compiler->comparisons->int64->operators[] = 'equal';
try {
    compilerComparisonPlan(compilerProviderMetadata($badComparison)->compiler, $comparisonDefinitions, names($metadata->string->operations));
    throw new \LogicException('Duplicate comparison operator accepted');
} catch (\RuntimeException $expected) {
    requireThat(str_contains($expected->getMessage(), 'Duplicate comparison'), 'Comparison diagnostic changed');
}

// Operator capability is generated, rather than inferred from native representation or conversion support.
$without = unserialize(serialize($metadata));
unset($without->compiler->comparisons->int64);
requireThat(!str_contains(renderCompiler($without, $facts)['types/int64.php'], 'binary_operator::equal => type_bool::instance()'), 'Removed comparisons still generated');
$without->compiler->comparisons->float64->operators = ['equal'];
unset($without->compiler->three_way);
$without->compiler->promotions = [];
$limited = renderCompiler($without, $facts)['types/float64.php'];
requireThat(str_contains($limited, 'binary_operator::equal') && !str_contains($limited, 'binary_operator::less'), 'Removed operator still generated');
foreach (['type', 'operator', 'operand', 'result', 'lowering', 'ordering'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'type' => $bad->compiler->comparisons->string = clone $bad->compiler->comparisons->int64,
        'operator' => $bad->compiler->comparisons->int64->operators[] = 'spaceship',
        'operand' => $bad->compiler->comparisons->int64->operand = 'numeric',
        'result' => $bad->compiler->comparisons->int64->result = 'int64',
        'lowering' => $bad->compiler->comparisons->int64->lowering = 'coerce',
        'ordering' => $bad->compiler->comparisons->uint8->operators[] = 'less',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Unsupported comparison metadata accepted: ' . $change);
    } catch (\RuntimeException $error) {
        requireThat($error->getMessage() !== '', 'Missing comparison diagnostic');
    }
}
requireThat(str_contains($files['types/int64.php'], 'binary_operator::equal => type_bool::instance()'), 'Wrong generated result identity');

// Arithmetic capabilities disappear with their metadata rows, including required public headers.
$without = unserialize(serialize($metadata));
unset($without->compiler->arithmetic->int64);
$limited = renderCompiler($without, $facts)['types/int64.php'];
requireThat(!str_contains($limited, 'binary_operator::add => type_int64::instance()') && !str_contains($limited, 'scpp::int64_divide('), 'Removed arithmetic still generated');
$without = unserialize(serialize($metadata));
unset($without->compiler->arithmetic->int64->operators->divide);
$limited = renderCompiler($without, $facts)['types/int64.php'];
requireThat(!str_contains($limited, 'binary_operator::divide => type_int64::instance()') && str_contains($limited, 'binary_operator::remainder'), 'Removed division still generated');
foreach (['type', 'operator', 'overflow', 'lowering', 'result'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'type' => $bad->compiler->arithmetic->float64 = clone $bad->compiler->arithmetic->int64,
        'operator' => $bad->compiler->arithmetic->int64->operators->power = 'native',
        'overflow' => $bad->compiler->arithmetic->int64->overflow = 'wrapping',
        'lowering' => $bad->compiler->arithmetic->int64->operators->divide = 'native',
        'result' => $bad->compiler->arithmetic->int64->result = 'float64',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Unsupported arithmetic metadata accepted: ' . $change);
    } catch (\RuntimeException $error) {
        requireThat($error->getMessage() !== '', 'Missing arithmetic diagnostic');
    }
}
requireThat(str_contains($files['types/int64.php'], 'binary_operator::add => type_int64::instance()'), 'Arithmetic result identity');

// Entry binding owns only the ABI symbol/header, with a bounded native result contract.
$boundEntry = unserialize(serialize($metadata));
$boundEntry->compiler->runtime_bindings->entry_exit_checked->symbol = 'scpp::test_entry';
$boundEntry->compiler->runtime_bindings->entry_exit_checked->header = 'scpp/test_entry.hpp';
$entryCode = renderCompiler($boundEntry, $facts)['bindings/entry.php'];
requireThat(str_contains($entryCode, 'scpp::test_entry') && str_contains($entryCode, 'scpp/test_entry.hpp'), 'Entry binding ignored');
foreach (['missing', 'operand', 'result', 'symbol', 'header'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'missing' => $bad->compiler->entry->checked_binding = 'missing',
        'operand' => $bad->compiler->runtime_bindings->entry_exit_checked->operand = 'float64',
        'result' => $bad->compiler->runtime_bindings->entry_exit_checked->result = 'int64',
        'symbol' => $bad->compiler->runtime_bindings->entry_exit_checked->symbol = 'bad()',
        'header' => $bad->compiler->runtime_bindings->entry_exit_checked->header = '../private.hpp',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid entry binding accepted');
    } catch (\RuntimeException $expected) {
    }
}
// Family registration owns direct AST dispatch and canonical accessors; keyword owners share one AST family.
$literalDefinitions = compilerTypePlan(compilerProviderMetadata($metadata), $facts, registry($metadata, $facts));
$familyPlan = compilerLiteralFamilies($literalDefinitions);
requireThat(str_contains($familyPlan['cases'], 'ast_string_expression => self::string()'), 'String family dispatch missing');
$renamedDefinitions = $literalDefinitions;
$renamedDefinitions['float64']['class'] = 'test_floating';
requireThat(str_contains(compilerLiteralFamilies($renamedDefinitions)['accessors'], 'floating(): type_test_floating'), 'Family accessor ignored registered class');
foreach (['missing', 'unknown', 'incompatible', 'unregistered_keywords', 'duplicate_family', 'duplicate_word'] as $change) {
    $bad = unserialize(serialize($metadata));
    try {
        switch ($change) {
            case 'missing': $bad->compiler->value_types->int64->literal_family = null; break;
            case 'unknown': $bad->compiler->value_types->uint8->literal_family = 'hex'; break;
            case 'incompatible': $bad->compiler->value_types->uint8->literal_family = 'keyword'; break;
            case 'unregistered_keywords': $bad->compiler->value_types->bool_t->literal_family = null; break;
            case 'duplicate_family':
                $duplicate = $literalDefinitions;
                $duplicate['extra'] = $duplicate['int64'];
                compilerLiteralFamilies($duplicate);
                throw new \LogicException('Duplicate family accepted');
            case 'duplicate_word':
                $duplicate = $literalDefinitions;
                $duplicate['bool_t']['literals']['null'] = null;
                compilerLiteralFamilies($duplicate);
                throw new \LogicException('Duplicate keyword spelling accepted');
        }
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid literal family accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Declaration flags drive queries; omission and nonboolean policy cannot silently enable a declaration.
$policy = unserialize(serialize($metadata));
$policy->compiler->value_types->int64->declarations->inferred_local = false;
$policy->compiler->value_types->int64->declarations->global_storage = false;
$policyCode = renderCompiler($policy, $facts)['types/int64.php'];
foreach (['inferred_local', 'global_storage'] as $capability) {
    requireThat(str_contains($policyCode, "function supports_$capability(): bool\n\t{\n\t\treturn false;"), 'Declaration metadata ignored');
    foreach (['missing', 'invalid'] as $change) {
        $bad = unserialize(serialize($metadata));
        if ($change === 'missing') unset($bad->compiler->value_types->int64->declarations->$capability);
        else $bad->compiler->value_types->int64->declarations->$capability = 'yes';
        try {
            renderCompiler($bad, $facts);
            throw new \LogicException('Invalid declaration policy accepted');
        } catch (\RuntimeException $expected) {
        }
    }
}
// Output admission and bindings are generated; no implicit to-string conversion is granted.
$noOutput = unserialize(serialize($metadata));
$noOutput->compiler->output = (object)[];
$noOutputFiles = renderCompiler($noOutput, $facts);
requireThat(!str_contains($noOutputFiles['types/int64.php'], 'function supports_output'), 'Removed output admission retained');
$boundOutput = unserialize(serialize($metadata));
$boundOutput->compiler->runtime_bindings->echo_int->symbol = 'scpp::test_echo';
$boundOutput->submodules->{'core.output'}->header = 'scpp/test_output.hpp';
$boundOutputFiles = renderCompiler($boundOutput, $facts);
requireThat(str_contains($boundOutputFiles['types/int64.php'], 'scpp::test_echo'), 'Output symbol ignored');
requireThat(str_contains($boundOutputFiles['types/string.php'], 'scpp/test_output.hpp'), 'Output header ignored');
foreach (['missing', 'operand', 'strategy', 'unsupported', 'symbol', 'header'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'missing' => $bad->compiler->output->int64->binding = 'missing',
        'operand' => $bad->compiler->runtime_bindings->echo_int->operand = 'string',
        'strategy' => $bad->compiler->runtime_bindings->echo_bytes->strategy = 'integer',
        'unsupported' => $bad->compiler->output->bool_t = (object)['strategy' => 'integer', 'binding' => 'echo_int'],
        'symbol' => $bad->compiler->runtime_bindings->echo_int->symbol = 'bad()',
        'header' => $bad->submodules->{'core.output'}->header = '../private.hpp',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid output metadata accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Safety metadata drives generated queries without changing admissions or lowering algorithms.
$conservative = unserialize(serialize($metadata));
$conservative->compiler->lowering_strategies->native->reorderable = false;
$conservative->compiler->value_types->int64->copy_semantics = 'nontrivial';
$code = renderCompiler($conservative, $facts)['types/int64.php'];
requireThat(!str_contains($code, 'function cpp_trivial_value'), 'Copy metadata ignored');
foreach (['cpp_binary_reorderable', 'cpp_unary_reorderable'] as $method) {
    $start = strpos($code, 'function ' . $method);
    $end = strpos($code, "\n\t}", $start);
    requireThat(!str_contains(substr($code, $start, $end - $start), '=> true'), 'Conservative strategy ignored');
}
foreach (['copy_missing', 'copy_invalid', 'strategy_missing', 'strategy_invalid', 'boolean_invalid', 'checked_safe'] as $change) {
    $bad = unserialize(serialize($metadata));
    switch ($change) {
        case 'copy_missing': unset($bad->compiler->value_types->uint8->copy_semantics); break;
        case 'copy_invalid': $bad->compiler->value_types->uint8->copy_semantics = 'unknown'; break;
        case 'strategy_missing': unset($bad->compiler->lowering_strategies->native); break;
        case 'strategy_invalid': $bad->compiler->lowering_strategies->unknown = (object)['reorderable' => false]; break;
        case 'boolean_invalid': $bad->compiler->lowering_strategies->native->reorderable = 'yes'; break;
        case 'checked_safe': $bad->compiler->lowering_strategies->zero_checked_divide->reorderable = true; break;
    }
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid safety metadata accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Shared named bindings affect checked branches; storage bindings and proven casts remain independent.
$bound = unserialize(serialize($metadata));
$bound->checked_conversions->symbol_prefix = 'test_checked_';
$bound->submodules->{'core.conversions'}->header = 'scpp/test_conversions.hpp';
foreach ($bound->type_exports as $export) if ($export->identity === 'uint8') $export->name = 'byte';
$bound->submodules->{'core.scalars'}->header = 'scpp/test_byte.hpp';
$bound->submodules->{'core.string'}->header = 'scpp/test_string.hpp';
$boundFiles = renderCompiler($bound, $facts);
requireThat(str_contains($boundFiles['types/uint8.php'], 'scpp::test_checked_int64_to_uint8('), 'Checked binding ignored');
requireThat(str_contains($boundFiles['types/uint8.php'], 'scpp/test_conversions.hpp'), 'Checked header binding ignored');
requireThat(str_contains($boundFiles['types/int64.php'], 'scpp::test_checked_float64_to_int64('), 'Floating checked binding ignored');
requireThat(str_contains($boundFiles['types/string.php'], 'scpp::string') && str_contains($boundFiles['types/string.php'], 'scpp/test_string.hpp'), 'Storage binding ignored');
requireThat(str_contains($boundFiles['types/uint8.php'], 'scpp::byte') && str_contains($boundFiles['types/uint8.php'], 'scpp/test_byte.hpp'), 'Scalar export binding ignored');
foreach (['missing', 'identity', 'kind', 'extract', 'symbol', 'header'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'missing' => $bad->compiler->value_types->string->storage_binding = 'missing',
        'identity' => $bad->compiler->runtime_bindings->string_storage->identity = 'int64',
        'kind' => $bad->compiler->runtime_bindings->string_storage->kind = 'function',
        'extract' => $bad->compiler->runtime_bindings->scalar_bool->extract = 'value()',
        'symbol' => $bad->compiler->runtime_bindings->string_storage->symbol = 'scpp::type{}',
        'header' => $bad->submodules->{'core.string'}->header = '../private.hpp',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid type binding accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Compiler bindings require named helpers only for the runtime-checked branches.
foreach (['missing', 'prefix', 'header'] as $change) {
    $bad = unserialize(serialize($metadata));
    if ($change === 'missing') unset($bad->checked_conversions);
    elseif ($change === 'prefix') $bad->checked_conversions->symbol_prefix = 'invalid::';
    else $bad->submodules->{'core.conversions'}->header = '../private.hpp';
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid named conversion binding accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Named runtime bindings select helper spelling/header; strategies still own checking and safety.
$renamed = unserialize(serialize($metadata));
$renamed->compiler->runtime_bindings->int64_divide->symbol = 'scpp::test_divide';
$renamed->compiler->runtime_bindings->int64_divide->header = 'scpp/test_arithmetic.hpp';
$renamedFiles = renderCompiler($renamed, $facts);
requireThat(str_contains($renamedFiles['types/int64.php'], 'scpp::test_divide('), 'Binding symbol ignored');
requireThat(str_contains($renamedFiles['types/int64.php'], "['scpp/test_arithmetic.hpp']"), 'Binding header ignored');
requireThat(str_contains($renamedFiles['types/int64.php'], 'scpp::int64_remainder('), 'Unrelated helper changed');
foreach (['missing', 'operand', 'result', 'strategy', 'symbol', 'header'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'missing' => $bad->compiler->arithmetic->int64->bindings->divide = 'missing',
        'operand' => $bad->compiler->runtime_bindings->int64_divide->operand = 'float64',
        'result' => $bad->compiler->runtime_bindings->int64_divide->result = 'bool_t',
        'strategy' => $bad->compiler->runtime_bindings->int64_divide->strategy = 'native',
        'symbol' => $bad->compiler->runtime_bindings->int64_divide->symbol = 'scpp::divide();',
        'header' => $bad->compiler->runtime_bindings->int64_divide->header = 'scpp/../private.hpp',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid arithmetic binding accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
// Removing an execution domain also requires removing promotions that depend on it.
foreach (['arithmetic', 'unary'] as $section) {
    $without = unserialize(serialize($metadata));
    unset($without->compiler->$section->float64);
    if ($section === 'arithmetic') $without->compiler->promotions = array_values(array_filter($without->compiler->promotions, static fn($row) => $row->result === 'bool_t'));
    $limited = renderCompiler($without, $facts)['types/float64.php'];
    requireThat(!str_contains($limited, $section === 'arithmetic' ? 'scpp::float64_divide' : 'public function prepare_unary'), 'Removed float capability still generated');
}
foreach (['remainder', 'unchecked_division', 'compound_policy'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'remainder' => $bad->compiler->arithmetic->float64->operators->remainder = 'zero_checked_remainder',
        'unchecked_division' => $bad->compiler->arithmetic->float64->operators->divide = 'native',
        'compound_policy' => $bad->compiler->arithmetic->float64->compound = 'yes',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid float arithmetic policy accepted');
    } catch (\RuntimeException $error) {
        requireThat($error->getMessage() !== '', 'Missing float policy diagnostic');
    }
}
require_once $root . '/compiler/03_parse/structures/expressions.php';
requireThat(\scpp\compiler\type_int64::instance()->supports_compound(\scpp\compiler\binary_operator::add), 'Integer compounds lost');
requireThat(\scpp\compiler\type_float64::instance()->supports_compound(\scpp\compiler\binary_operator::add), 'Float compounds unavailable');
$without = unserialize(serialize($metadata));
$without->compiler->arithmetic->float64->compound = false;
$limited = renderCompiler($without, $facts)['types/float64.php'];
requireThat(str_contains($limited, "public function supports_compound(binary_operator \$operator): bool\n\t{\n\t\treturn false;"), 'Disabled compound capability ignored');

// Backend safety facts follow the admitted implementation strategy, never preparation or AST mutation.
require_once $root . '/compiler/03_parse/structures/expressions.php';
foreach (['type_int64', 'type_bool', 'type_uint8', 'type_float64', 'type_null'] as $class) {
    $class = 'scpp\\compiler\\' . $class;
    requireThat($class::instance()->cpp_trivial_value(), 'Native scalar copying was not classified');
}
requireThat(!\scpp\compiler\type_string::instance()->cpp_trivial_value(), 'String copy incorrectly classified as trivial');
$type = \scpp\compiler\type_int64::instance();
requireThat($type->cpp_binary_reorderable(\scpp\compiler\binary_operator::add), 'Native addition not classified');
requireThat(!$type->cpp_binary_reorderable(\scpp\compiler\binary_operator::divide)
    && !$type->cpp_binary_reorderable(\scpp\compiler\binary_operator::remainder), 'Checked operations classified as reorderable');

// Expression and compound admission query the same generated decision without constructing syntax.
foreach ([\scpp\compiler\binary_operator::add, \scpp\compiler\binary_operator::subtract,
    \scpp\compiler\binary_operator::multiply, \scpp\compiler\binary_operator::divide,
    \scpp\compiler\binary_operator::remainder] as $operator) {
    requireThat($type->binary_result($operator, $type) === $type, 'Missing shared arithmetic admission');
    requireThat($type->binary_result($operator, \scpp\compiler\type_bool::instance()) === null, 'Mixed arithmetic admitted');
}
requireThat($type->binary_result(\scpp\compiler\binary_operator::equal, $type)
    === \scpp\compiler\type_bool::instance(), 'Comparison result changed');
requireThat(\scpp\compiler\type_null::instance()->binary_result(\scpp\compiler\binary_operator::add,
    \scpp\compiler\type_null::instance()) === null, 'Default operation admission changed');

// Discarded-value updates are explicit metadata capabilities, not inferred from arithmetic availability.
$without = unserialize(serialize($metadata));
unset($without->compiler->updates->int64);
requireThat(!str_contains(renderCompiler($without, $facts)['types/int64.php'], 'public function supports_update'), 'Removed updates still generated');
$limited = unserialize(serialize($metadata));
$limited->compiler->updates->int64->operators = ['decrement'];
$code = renderCompiler($limited, $facts)['types/int64.php'];
requireThat(!str_contains($code, 'update_operator::increment') && str_contains($code, 'update_operator::decrement'), 'Update operator removal ignored');
foreach (['type', 'operator', 'overflow', 'lowering'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'type' => $bad->compiler->updates->bool_t = clone $bad->compiler->updates->int64,
        'operator' => $bad->compiler->updates->int64->operators[] = 'double',
        'overflow' => $bad->compiler->updates->int64->overflow = 'wrap',
        'lowering' => $bad->compiler->updates->int64->lowering = 'helper',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid update policy accepted');
    } catch (\RuntimeException $error) {
        requireThat(str_contains($error->getMessage(), 'update'), 'Wrong update validation failure');
    }
}
requireThat($type->supports_update(\scpp\compiler\update_operator::increment)
    && $type->supports_update(\scpp\compiler\update_operator::decrement), 'Missing generated update admission');
requireThat(\scpp\compiler\type_float64::instance()->supports_update(\scpp\compiler\update_operator::increment)
    && \scpp\compiler\type_float64::instance()->supports_update(\scpp\compiler\update_operator::decrement), 'Missing float updates');
$without = unserialize(serialize($metadata));
unset($without->compiler->updates->float64);
requireThat(!str_contains(renderCompiler($without, $facts)['types/float64.php'], 'public function supports_update'), 'Removed float updates still generated');
$limited = unserialize(serialize($metadata));
$limited->compiler->updates->float64->operators = ['decrement'];
$code = renderCompiler($limited, $facts)['types/float64.php'];
requireThat(!str_contains($code, 'update_operator::increment') && str_contains($code, 'update_operator::decrement'), 'Float operator removal ignored');

// Unary admission and lowering both disappear when their metadata capability is removed.
$without = unserialize(serialize($metadata));
unset($without->compiler->unary->int64);
requireThat(!str_contains(renderCompiler($without, $facts)['types/int64.php'], 'public function prepare_unary'), 'Removed unary capability still generated');
$limited = unserialize(serialize($metadata));
$limited->compiler->unary->int64->operators = ['negative'];
$limitedSource = renderCompiler($limited, $facts)['types/int64.php'];
requireThat(str_contains($limitedSource, 'unary_operator::negative') && !str_contains($limitedSource, 'unary_operator::positive'), 'Unary operator subset ignored');
foreach (['type', 'operator', 'overflow', 'lowering', 'result'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'type' => $bad->compiler->unary->bool_t = clone $bad->compiler->unary->int64,
        'operator' => $bad->compiler->unary->int64->operators[] = 'invert',
        'overflow' => $bad->compiler->unary->int64->overflow = 'wrap',
        'lowering' => $bad->compiler->unary->int64->lowering = 'helper',
        'result' => $bad->compiler->unary->int64->result = 'float64',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid unary metadata accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}

// Promotion permissions are operator-local and depend on existing conversion behavior.
$without = unserialize(serialize($metadata));
unset($without->compiler->three_way);
$without->compiler->promotions = [];
requireThat(!str_contains(renderCompiler($without, $facts)['types/int64.php'], 'public function prepare_binary'), 'Removed promotion still generated');
foreach (['missing_conversion', 'duplicate', 'operator', 'execution', 'context'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'missing_conversion' => $bad->compiler->conversions = [],
        'duplicate' => $bad->compiler->promotions[] = clone $bad->compiler->promotions[0],
        'operator' => $bad->compiler->promotions[0]->operators = ['equal'],
        'execution' => $bad->compiler->promotions[0]->execution_type = 'int64',
        'context' => conversion_row($bad, 'bool_t', 'int64')->contexts = ['anywhere'],
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid promotion/context accepted');
    } catch (\RuntimeException $error) {
        requireThat($error->getMessage() !== '', 'Missing promotion/context diagnostic');
    }
}
$limited = unserialize(serialize($metadata));
unset($limited->compiler->three_way);
conversion_row($limited, 'int64', 'float64')->contexts = ['explicit_cast'];
$code = renderCompiler($limited, $facts)['types/float64.php'];
requireThat(str_contains($code, '($context === conversion_context::explicit_cast) && ($value->value_type() === type_int64::instance())'), 'Context subset ignored');

// Family rules expand to the twelve cross-type numeric conversions among active representations.
$expected = ['bool_t:int64' => 'boolean_integer', 'int64:bool_t' => 'integer_boolean',
    'int64:uint8' => 'checked_integer', 'uint8:int64' => 'integer_widen',
    'int64:float64' => 'numeric_floating', 'bool_t:float64' => 'numeric_floating',
    'float64:bool_t' => 'floating_boolean', 'float64:int64' => 'floating_integer',
    'bool_t:uint8' => 'boolean_integer', 'uint8:bool_t' => 'integer_boolean',
    'uint8:float64' => 'numeric_floating', 'float64:uint8' => 'floating_integer'];
$definitions = compilerTypePlan(compilerProviderMetadata($metadata), $facts, registry($metadata, $facts));
$plan = compilerConversionPlan(compilerProviderMetadata($metadata), $definitions, registry($metadata, $facts), $facts);
$actual = array_map(static fn($row) => $row['operation'], $plan);
ksort($expected); ksort($actual);
requireThat($actual === $expected, 'Family expansion changed conversion admission');
requireThat($definitions['uint8']['strategy'] === 'integer', 'Narrow integer still has a separate family');
requireThat(in_array('byte', $definitions['uint8']['aliases'], true), 'Canonical definition lost byte alias');

// Removing the conversion's promotion permission cannot affect explicit casts or silently keep a promotion alive.
$limited = unserialize(serialize($metadata));
unset($limited->compiler->three_way);
conversion_row($limited, 'int64', 'float64')->promotion_operators = [];
try {
    renderCompiler($limited, $facts);
    throw new \LogicException('Promotion ignored removed conversion permission');
} catch (\RuntimeException $error) {
    requireThat($error->getMessage() === 'Conversion does not authorize promotion', 'Wrong promotion permission diagnostic');
}
$limited->compiler->promotions = [];
$code = renderCompiler($limited, $facts)['types/float64.php'];
requireThat(str_contains($code, 'value_conversion::numeric_floating'), 'Removing promotion removed boundary conversion');
$limited = unserialize(serialize($metadata));
unset($limited->compiler->three_way);
conversion_row($limited, 'int64', 'float64')->promotion_operators = ['less'];
$limited->compiler->promotions = array_values(array_filter($limited->compiler->promotions, static fn($row) => $row->result === 'bool_t'));
foreach ($limited->compiler->promotions as $promotion) $promotion->operators = ['less'];
$code = renderCompiler($limited, $facts)['types/int64.php'];
$promotionMethod = substr($code, strpos($code, 'public function prepare_binary'));
requireThat(str_contains($promotionMethod, 'binary_operator::less => true') && !str_contains($promotionMethod, 'binary_operator::greater'), 'Promotion subset ignored');
$bad = unserialize(serialize($metadata));
conversion_row($bad, 'float64', 'int64')->promotion_operators = ['less'];
try {
    renderCompiler($bad, $facts);
    throw new \LogicException('Checked promotion accepted without preparation support');
} catch (\RuntimeException $error) {
    requireThat($error->getMessage() === 'Conversion strategy does not support promotion', 'Wrong checked promotion diagnostic');
}

// Mixed arithmetic resolves the same validated execution operation used by float lowering.
$without = unserialize(serialize($metadata));
unset($without->compiler->arithmetic->float64->operators->divide);
try {
    renderCompiler($without, $facts);
    throw new \LogicException('Promotion accepted a missing arithmetic execution operation');
} catch (\RuntimeException $error) {
    requireThat($error->getMessage() === 'Promotion execution operation unavailable', 'Wrong missing arithmetic diagnostic');
}
$without->compiler->promotions = array_values(array_filter($without->compiler->promotions,
    static fn($row) => $row->result === 'bool_t'));
requireThat(!str_contains(renderCompiler($without, $facts)['types/float64.php'], 'scpp::float64_divide('), 'Missing division still lowered');

// Compound permission is explicit, directional and independently removable.
$without = unserialize(serialize($metadata));
foreach ($without->compiler->promotions as $row) unset($row->compound_operators);
requireThat(!str_contains(renderCompiler($without, $facts)['types/float64.php'], 'public function prepare_compound'), 'Removed compound permission still generated');
foreach (['direction', 'operator', 'duplicate'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'direction' => $bad->compiler->promotions[0]->compound_operators = ['add'],
        'operator' => $bad->compiler->promotions[1]->compound_operators = ['remainder'],
        'duplicate' => $bad->compiler->promotions[1]->compound_operators = ['add', 'add'],
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid mixed compound metadata accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}

// Equality promotions require the conversion permission and the actual float equality operation.
foreach (['permission', 'execution'] as $change) {
    $bad = unserialize(serialize($metadata));
    if ($change === 'permission') {
        conversion_row($bad, 'int64', 'float64')->promotion_operators = array_values(array_diff(conversion_row($bad, 'int64', 'float64')->promotion_operators, ['equal']));
    } else {
        $bad->compiler->comparisons->float64->operators = array_values(array_diff($bad->compiler->comparisons->float64->operators, ['equal']));
    }
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Missing mixed equality permission/operation accepted');
    } catch (\RuntimeException $error) {
        requireThat($error->getMessage() === ($change === 'permission' ? 'Unsupported promotion operator' : 'Promotion execution operation unavailable'), 'Wrong equality metadata diagnostic');
    }
}

// String comparison needs runtime capability and a typed native-bool extraction binding.
foreach (['runtime', 'result', 'ordering', 'safety'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'runtime' => $bad->string->operations = array_values(array_diff($bad->string->operations, ['equal'])),
        'result' => $bad->compiler->comparisons->string->result_binding = 'string_storage',
        'ordering' => $bad->compiler->comparisons->string->operators[] = 'compare',
        'safety' => $bad->compiler->lowering_strategies->runtime_compare->reorderable = true,
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid string comparison metadata accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
$without = unserialize(serialize($metadata));
unset($without->compiler->comparisons->string);
requireThat(!str_contains(renderCompiler($without, $facts)['types/string.php'], 'binary_operator::equal =>'), 'Removed string comparison still generated');
$renamed = unserialize(serialize($metadata));
$renamed->compiler->runtime_bindings->scalar_bool->extract = 'test_value';
requireThat(str_contains(renderCompiler($renamed, $facts)['types/string.php'], ').test_value()'), 'String result extraction ignores binding');

// Concatenation is independent of comparisons and requires explicit runtime/flavor/safety policy.
foreach (['runtime', 'flavor', 'safety', 'result'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'runtime' => $bad->string->operations = array_values(array_diff($bad->string->operations, ['add'])),
        'flavor' => $bad->compiler->concatenation->operators->add[] = 'php',
        'safety' => $bad->compiler->lowering_strategies->runtime_concat->reorderable = true,
        'result' => $bad->compiler->concatenation->result = 'bool_t',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid concatenation metadata accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}
$without = unserialize(serialize($metadata));
unset($without->compiler->concatenation);
$rendered = renderCompiler($without, $facts)['types/string.php'];
requireThat(!str_contains($rendered, 'binary_operator::concatenate'), 'Removed concatenation still generated');
requireThat(str_contains($rendered, 'binary_operator::equal'), 'Removing concatenation removed comparisons');
$without = unserialize(serialize($metadata));
$without->compiler->concatenation->compound = false;
requireThat(str_contains(renderCompiler($without, $facts)['types/string.php'], "function supports_compound(binary_operator \$operator): bool\n\t{\n\t\treturn false;"), 'Compound metadata ignored');

// Null ordinary equality has independent metadata admission; ordering remains invalid.
$without = unserialize(serialize($metadata));
unset($without->compiler->comparisons->null_t);
requireThat(!str_contains(renderCompiler($without, $facts)['types/null.php'], 'binary_operator::equal =>'), 'Removed null equality still generated');
$bad = unserialize(serialize($metadata));
$bad->compiler->comparisons->null_t->operators[] = 'less';
try {
    renderCompiler($bad, $facts);
    throw new \LogicException('Null ordering accepted');
} catch (\RuntimeException $expected) {
}

// Strict identity is independently admitted and rejects unsafe or unknown policies.
$identityOnly = unserialize(serialize($metadata));
unset($identityOnly->compiler->comparisons->string);
$identityOnly->compiler->runtime_bindings->identity_test_bool = clone $identityOnly->compiler->runtime_bindings->scalar_bool;
$identityOnly->compiler->runtime_bindings->identity_test_bool->extract = 'identity_value';
$identityOnly->compiler->strict_identity->result_binding = 'identity_test_bool';
$identitySource = renderCompiler($identityOnly, $facts)['types/string.php'];
requireThat(str_contains($identitySource, 'binary_operator::identical')
    && str_contains($identitySource, ').identity_value()')
    && !str_contains($identitySource, 'binary_operator::equal =>'), 'Identity depends on ordinary comparison admission/binding');
$identityOnly->string->operations = array_values(array_diff($identityOnly->string->operations, ['equal']));
try {
    renderCompiler($identityOnly, $facts);
    throw new \LogicException('Identity accepted missing runtime string equality');
} catch (\RuntimeException $error) {
    requireThat($error->getMessage() === 'Missing runtime string comparison', 'Identity runtime diagnostic changed');
}

$without = unserialize(serialize($metadata));
unset($without->compiler->strict_identity);
$ordinaryOnly = renderCompiler($without, $facts);
foreach ($ordinaryOnly as $source) {
    requireThat(!str_contains($source, 'binary_operator::identical'), 'Removed identity still generated');
}
requireThat(str_contains($ordinaryOnly['types/string.php'], 'binary_operator::equal =>'), 'Removing identity removed ordinary equality');
foreach (['peer', 'same', 'different', 'safety'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'peer' => $bad->compiler->strict_identity->types[] = 'unknown',
        'same' => $bad->compiler->strict_identity->same_type = 'storage_identity',
        'different' => $bad->compiler->strict_identity->different_type = 'numeric_promote',
        'safety' => $bad->compiler->lowering_strategies->canonical_identity->reorderable = true,
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid strict identity accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}

// Three-way admission and promotion are generated from their family policy.
$without = unserialize(serialize($metadata));
unset($without->compiler->three_way);
foreach (renderCompiler($without, $facts) as $source) {
    requireThat(!str_contains($source, 'binary_operator::compare'), 'Removed spaceship still generated');
}
foreach (['type', 'promotion', 'binding', 'result', 'safety', 'string_runtime'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'type' => $bad->compiler->three_way->types->bool_t = (object)['lowering' => 'native_three_way'],
        'promotion' => $bad->compiler->three_way->integer_promotion = 'native_cpp',
        'binding' => $bad->compiler->three_way->types->float64->binding = 'float64_divide',
        'result' => $bad->compiler->three_way->result = 'bool_t',
        'safety' => $bad->compiler->lowering_strategies->checked_three_way->reorderable = true,
        'string_runtime' => $bad->string->operations = array_values(array_diff($bad->string->operations, ['less'])),
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid spaceship metadata accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}

// Power has its own exact representation and per-operator compound permission.
$without = unserialize(serialize($metadata));
unset($without->compiler->power);
foreach (renderCompiler($without, $facts) as $source) {
    requireThat(!str_contains($source, 'binary_operator::power'), 'Removed power still generated');
}
$without = unserialize(serialize($metadata));
$without->compiler->power->compound = false;
$source = renderCompiler($without, $facts)['types/int64.php'];
$start = strpos($source, 'function supports_compound');
$end = strpos($source, 'function binary_result', $start);
requireThat(!str_contains(substr($source, $start, $end - $start), 'binary_operator::power'), 'Power compound ignored metadata');
requireThat(str_contains($source, 'binary_operator::power =>'), 'Removing compound removed power values');
foreach (['operand', 'result', 'binding', 'safety'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'operand' => $bad->compiler->power->operand = 'uint8',
        'result' => $bad->compiler->power->result = 'float64',
        'binding' => $bad->compiler->power->binding = 'int64_divide',
        'safety' => $bad->compiler->lowering_strategies->checked_power->reorderable = true,
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid power policy accepted: ' . $change);
    } catch (\RuntimeException $expected) {
    }
}

// An operation may need both its storage and extraction headers; no match arm may hide either.
$headers = \scpp\compiler\type_string::instance()->binary_headers(\scpp\compiler\binary_operator::identical);
requireThat(in_array('scpp/string.hpp', $headers, true) && in_array('scpp/core/scalars.hpp', $headers, true), 'Binary header union lost a binding');
