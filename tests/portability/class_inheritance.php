<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
$converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php');
$source = <<<'SOURCE'
<?php
abstract class Base {
    private int $position /** uint32 */ = 0;
    public function position(): int { return (int) $this->position; }
}
final class Child extends Base {}
SOURCE;
$out = $converter->convert($source, 'inheritance.php');
foreach (['abstract class Base', 'final class Child extends Base', 'private $position uint32 = 0;'] as $expected) {
    if (!str_contains($out, $expected)) { throw new RuntimeException('Missing declaration: ' . $expected); }
}
foreach ([
    'class Child extends {}',
    'class Child extends Base, Other {}',
    'class Bad { private int $value /** uint32 */ = -1; }',
    'class Bad { private int $value /** uint32 */ = 010; }',
    'class Bad { private int $value /** uint32 */ = 4294967296; }',
] as $body) {
    try { $converter->convert('<?php ' . $body, 'bad.php'); }
    catch (RuntimeException $error) { continue; }
    throw new RuntimeException('Accepted malformed declaration: ' . $body);
}
echo "Class conversion: abstract base, literal inheritance, uint32 defaults and rejection passed\n";
