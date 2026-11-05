<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/../php/input.php';
require_once __DIR__ . '/../php/target.php';
require_once __DIR__ . '/../php/scalars.php';
require_once __DIR__ . '/../php/publication.php';
require_once __DIR__ . '/../php/render_compiler.php';

$root = dirname(__DIR__, 3);
$facts = targetFacts(decode(readFile($argv[1]), 'facts'), decode(readFile($argv[2]), 'context'));
$metadata = decode(readFile($root . '/runtime/metadata/runtime.json'), 'metadata');
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
requireThat(str_contains($files['types/int64.php'], "return '__INT64_TYPE__'"), 'Missing metadata-derived representation');
$bad = clone $metadata;
$bad->types = clone $metadata->types;
unset($bad->types->int64);
try {
    renderCompiler($bad, $facts);
    throw new \LogicException('Missing int64 was accepted');
} catch (\RuntimeException $error) {
    requireThat(str_contains($error->getMessage(), 'requires int64'), 'Unexpected rejection');
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
$aliasPlan = compilerTypePlan($aliasMetadata, $facts, registry($aliasMetadata, $facts));
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
        case 'missing_definition': unset($bad->compiler->value_types->byte); break;
        case 'missing_member':
            $bad->compiler->modules->{'default-runtime'}->types = array_values(array_diff($bad->compiler->modules->{'default-runtime'}->types, ['byte']));
            break;
        case 'inactive_definition': $bad->compiler->value_types->extra = clone $bad->compiler->value_types->byte; break;
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
        'byte_width' => $bad->byte->bits = 16,
        'byte_implicit' => $bad->compiler->conversions[2]->contexts = ['assignment'],
        'conversion_kind' => $bad->compiler->conversions[0]->operation = 'truncate',
        'duplicate_conversion' => $bad->compiler->conversions[] = clone $bad->compiler->conversions[0],
        'literal' => $bad->compiler->value_types->bool_t->literals->true = 1,
        'inactive_target' => $bad->compiler->conversions[0]->to = 'vector',
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
$without->compiler->conversions = [];
$without->compiler->promotions = [];
$withoutFiles = renderCompiler($without, $facts);
requireThat(!str_contains($withoutFiles['types/byte.php'], 'scpp::byte{'), 'Conversion ignored metadata removal');
requireThat(str_contains($files['types/byte.php'], 'MAXIMUM /** uint8 */ = 255'), 'Missing generated byte limit');
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
    require $work . '/types/byte.php';
    require $work . '/types/float64.php';
    require $work . '/types/string.php';
    require $work . '/types/registry.php';
    require $root . '/compiler/03_parse/structures/abstract.php';
    require $root . '/compiler/03_parse/structures/common.php';
    $type = \scpp\compiler\type_int64::instance();
    requireThat(\scpp\compiler\type_registry::resolve('int') === $type && \scpp\compiler\type_registry::resolve('int64') === $type, 'Registration identity differs');
    requireThat(\scpp\compiler\type_registry::resolve('vector') === null, 'Out-of-scope type registered');
    requireThat($type::MINIMUM === PHP_INT_MIN && $type::MAXIMUM === PHP_INT_MAX, 'Generated bounds differ');
    requireThat($type->cpp_type() === '__INT64_TYPE__', 'Wrong native type');
    requireThat($type->accepts_name('int') && $type->accepts_name('int64'), 'Missing canonical source aliases');
    foreach ([0, 8, PHP_INT_MAX, PHP_INT_MIN] as $value) {
        $expected = $value === PHP_INT_MIN ? '(-9223372036854775807LL - 1LL)' : $value . 'LL';
        $literal = new \scpp\compiler\ast_integer_expression(0, 1);
        $literal->settle_integer($value);
        requireThat($type->lower_cpp_literal($literal) === $expected, 'Incorrect literal lowering');
    }
} finally {
    foreach (['types/int64.php', 'types/null.php', 'types/bool.php', 'types/byte.php', 'types/float64.php', 'types/string.php', 'types/registry.php', 'inventory.json', 'retired.php', 'keep.txt'] as $path) {
        if (file_exists($work . '/' . $path)) unlink($work . '/' . $path);
    }
    rmdir($work . '/types');
    rmdir($work);
}
echo "Generated native lowering, metadata admission, freshness and cleanup passed\n";

// Condition admission follows metadata independently of value conversions.
foreach (['int64', 'float64', 'bool', 'string'] as $type) {
    requireThat(str_contains($files['types/' . $type . '.php'], 'public function prepare_condition'), 'Missing generated condition handler');
}
foreach (['null', 'byte'] as $type) {
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

// Operator capability is generated, rather than inferred from native representation or conversion support.
$without = unserialize(serialize($metadata));
unset($without->compiler->comparisons->int64);
requireThat(!str_contains(renderCompiler($without, $facts)['types/int64.php'], 'binary_operator::equal'), 'Removed comparisons still generated');
$without->compiler->comparisons->float64->operators = ['equal'];
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
        'ordering' => $bad->compiler->comparisons->byte->operators[] = 'less',
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
requireThat(!str_contains($limited, 'binary_operator::add') && !str_contains($limited, 'scpp/arithmetic.hpp'), 'Removed arithmetic still generated');
$without = unserialize(serialize($metadata));
unset($without->compiler->arithmetic->int64->operators->divide);
$limited = renderCompiler($without, $facts)['types/int64.php'];
requireThat(!str_contains($limited, 'binary_operator::divide') && str_contains($limited, 'binary_operator::remainder'), 'Removed division still generated');
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

// Type bindings affect only storage and checked branches; proven conversions remain native casts.
$bound = unserialize(serialize($metadata));
$bound->compiler->runtime_bindings->boxed_byte->symbol = 'scpp::test_byte';
$bound->compiler->runtime_bindings->boxed_byte->extract = 'get_value';
$bound->compiler->runtime_bindings->boxed_byte->header = 'scpp/test_byte.hpp';
$bound->compiler->runtime_bindings->boxed_float64->symbol = 'scpp::test_float';
$bound->compiler->runtime_bindings->string_storage->symbol = 'scpp::test_string';
$bound->compiler->runtime_bindings->string_storage->header = 'scpp/test_string.hpp';
$boundFiles = renderCompiler($bound, $facts);
requireThat(str_contains($boundFiles['types/byte.php'], 'scpp::test_byte{') && str_contains($boundFiles['types/byte.php'], '.get_value()'), 'Checked wrapper binding ignored');
requireThat(str_contains($boundFiles['types/byte.php'], 'scpp/test_byte.hpp'), 'Checked header binding ignored');
requireThat(str_contains($boundFiles['types/int64.php'], 'scpp::test_float{'), 'Source wrapper binding ignored');
requireThat(str_contains($boundFiles['types/string.php'], 'scpp::test_string') && str_contains($boundFiles['types/string.php'], 'scpp/test_string.hpp'), 'Storage binding ignored');
foreach (['missing', 'identity', 'kind', 'extract', 'symbol', 'header'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'missing' => $bad->compiler->value_types->string->storage_binding = 'missing',
        'identity' => $bad->compiler->runtime_bindings->boxed_byte->identity = 'int64',
        'kind' => $bad->compiler->runtime_bindings->string_storage->kind = 'function',
        'extract' => $bad->compiler->runtime_bindings->boxed_int64->extract = 'value()',
        'symbol' => $bad->compiler->runtime_bindings->boxed_float64->symbol = 'scpp::type{}',
        'header' => $bad->compiler->runtime_bindings->string_storage->header = '../private.hpp',
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid type binding accepted: ' . $change);
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
// Float arithmetic shares generated dispatch, but does not implicitly enable compounds or mixed operands.
foreach (['arithmetic', 'unary'] as $section) {
    $without = unserialize(serialize($metadata));
    unset($without->compiler->$section->float64);
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
requireThat(\scpp\compiler\type_int64::instance()->supports_compound(), 'Integer compounds lost');
requireThat(\scpp\compiler\type_float64::instance()->supports_compound(), 'Float compounds unavailable');
$without = unserialize(serialize($metadata));
$without->compiler->arithmetic->float64->compound = false;
$limited = renderCompiler($without, $facts)['types/float64.php'];
requireThat(str_contains($limited, "public function supports_compound(): bool\n\t{\n\t\treturn false;"), 'Disabled compound capability ignored');

// Backend safety facts follow the admitted implementation strategy, never preparation or AST mutation.
require $root . '/compiler/03_parse/structures/expressions.php';
foreach (['type_int64', 'type_bool', 'type_byte', 'type_float64', 'type_null'] as $class) {
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
        'type' => $bad->compiler->updates->byte = clone $bad->compiler->updates->int64,
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
        'type' => $bad->compiler->unary->byte = clone $bad->compiler->unary->int64,
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
$without->compiler->promotions = [];
requireThat(!str_contains(renderCompiler($without, $facts)['types/int64.php'], 'public function prepare_binary'), 'Removed promotion still generated');
foreach (['missing_conversion', 'duplicate', 'operator', 'execution', 'context'] as $change) {
    $bad = unserialize(serialize($metadata));
    match ($change) {
        'missing_conversion' => $bad->compiler->conversions = [],
        'duplicate' => $bad->compiler->promotions[] = clone $bad->compiler->promotions[0],
        'operator' => $bad->compiler->promotions[0]->operators = ['equal'],
        'execution' => $bad->compiler->promotions[0]->execution_type = 'int64',
        'context' => $bad->compiler->conversions[0]->contexts = ['anywhere'],
    };
    try {
        renderCompiler($bad, $facts);
        throw new \LogicException('Invalid promotion/context accepted');
    } catch (\RuntimeException $error) {
        requireThat($error->getMessage() !== '', 'Missing promotion/context diagnostic');
    }
}
$limited = unserialize(serialize($metadata));
$limited->compiler->conversions[4]->contexts = ['explicit_cast'];
$code = renderCompiler($limited, $facts)['types/float64.php'];
requireThat(str_contains($code, '($context === conversion_context::explicit_cast) && ($value->type_def() === type_int64::instance())'), 'Context subset ignored');

// Independent test oracle: strategy guards must preserve the eight existing cross-type admissions.
$expected = ['bool_t:int64' => 'boolean_integer', 'int64:bool_t' => 'integer_boolean',
    'int64:byte' => 'checked_integer', 'byte:int64' => 'integer_widen',
    'int64:float64' => 'numeric_floating', 'bool_t:float64' => 'numeric_floating',
    'float64:bool_t' => 'floating_boolean', 'float64:int64' => 'floating_integer'];
$identities = ['int64', 'float64', 'bool_t', 'byte', 'null_t', 'string'];
$definitions = array_fill_keys($identities, []);
$types = registry($metadata, $facts);
foreach (array_unique(array_values($expected)) as $operation) {
    foreach ($identities as $from) {
        foreach ($identities as $to) {
            $candidate = unserialize(serialize($metadata));
            $candidate->compiler->conversions = [(object)['from' => $from, 'to' => $to,
                'contexts' => ['explicit_cast', 'typed_destination'], 'operation' => $operation, 'promotion_operators' => []]];
            foreach ($metadata->compiler->conversions as $original) {
                if ($original->operation === $operation) {
                    foreach (['source_binding', 'target_binding'] as $field) {
                        if (isset($original->$field)) $candidate->compiler->conversions[0]->$field = $original->$field;
                    }
                    break;
                }
            }
            $accepted = true;
            try {
                compilerConversionPlan($candidate, $definitions, $types);
            } catch (\RuntimeException $error) {
                $accepted = false;
                requireThat($error->getMessage() === 'Unsupported compiler conversion operation', 'Strategy diagnostic changed');
            }
            requireThat($accepted === (($expected[$from . ':' . $to] ?? null) === $operation), 'Strategy changed conversion admission');
        }
    }
}

// Removing the conversion's promotion permission cannot affect explicit casts or silently keep a promotion alive.
$limited = unserialize(serialize($metadata));
$limited->compiler->conversions[4]->promotion_operators = [];
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
$limited->compiler->conversions[4]->promotion_operators = ['less'];
foreach ($limited->compiler->promotions as $promotion) $promotion->operators = ['less'];
$code = renderCompiler($limited, $facts)['types/int64.php'];
$promotionMethod = substr($code, strpos($code, 'public function prepare_binary'));
requireThat(str_contains($promotionMethod, 'binary_operator::less => true') && !str_contains($promotionMethod, 'binary_operator::greater'), 'Promotion subset ignored');
$bad = unserialize(serialize($metadata));
$bad->compiler->conversions[6]->promotion_operators = ['less'];
try {
    renderCompiler($bad, $facts);
    throw new \LogicException('Checked promotion accepted without preparation support');
} catch (\RuntimeException $error) {
    requireThat($error->getMessage() === 'Conversion strategy does not support promotion', 'Wrong checked promotion diagnostic');
}
