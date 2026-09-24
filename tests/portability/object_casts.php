<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
require_once __DIR__ . '/../../tools/php_portability/runtime/bootstrap.php';
require_once __DIR__ . '/../../generators/php/bin/bootstrap.php';
function ensure(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$source = <<<'SOURCE'
<?php
namespace cast_proof;
interface Payload {}
final class Row implements Payload { public int $number = 7; }
final class Other implements Payload {}
final class Access {
    public static function is_row(?Payload $value): bool { return $value instanceof Row; }
    public static function row(?Payload $value): Row { return object_cast($value, Row::class); }
}
$row = new Row();
$payload /** Payload */ = $row;
$alias = Access::row($payload);
$alias->number = 9;
echo $row === $alias ? 'same' : 'bad', ':', $row->number, ':', Access::is_row($payload) ? 'row' : 'bad', "\n";
$other /** Payload */ = new Other();
echo Access::is_row(null) ? 'bad' : 'absent', ':', Access::is_row($other) ? 'bad' : 'other', "\n";
SOURCE;
$converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php');
$out = $converter->convert($source, 'cast.php');
ensure(str_contains($out, 'scpp_portability_object_cast($value, Row::class)'), 'Missing checked cast binding');
ob_start(); eval(substr($source, 5)); $trace = ob_get_clean();
ensure($trace === "same:9:row\nabsent:other\n", 'Unexpected identity/predicate trace: ' . $trace);
foreach ([null, new cast_proof\Other()] as $value) {
    try { cast_proof\Access::row($value); }
    catch (RuntimeException $error) { continue; }
    throw new RuntimeException('Accepted incompatible payload');
}
foreach ([
    '$x = object_cast($value, $target);',
    '$x = object_cast($value, "Row");',
    '$x = $value instanceof $target;',
] as $body) {
    try { $converter->convert('<?php ' . $body, 'bad.php'); }
    catch (RuntimeException $error) { continue; }
    throw new RuntimeException('Accepted dynamic target: ' . $body);
}
// Generate and inspect C++ only. Do not invoke a native compiler or run native code.
$result = (new Scpp\S2S\Transpiler())->transpile('/tmp/object-casts-proof.phs', false, false, $out);
ensure($result->errors === [], 'Generator errors: ' . implode('; ', $result->errors));
$cpp = implode("\n", $result->sourceLines);
$header = implode("\n", $result->headerLines);
ensure(str_contains($cpp, '::scpp::object_is<'), 'Missing native instanceof lowering');
ensure(str_contains($cpp, '::scpp::checked_object_cast<'), 'Missing native checked cast lowering');
ensure(str_contains($header, 'virtual ~Payload() = default;'), 'Interface is not polymorphic');
ensure(!str_contains($header . $cpp, 'shared_p<instanceof'), 'Expression became a type');
ensure(!str_contains($header . $cpp, 'unsupported-'), 'Unsupported native placeholder');
echo "Object casts: PHP identity/null/mismatch, conversion and C++ emission inspection passed (no native compilation)\n";
