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

// Both legal modifier orders have one canonical target spelling.
foreach (['public', 'protected'] as $visibility) {
    $canonical = $converter->convert('<?php abstract class Base { ' . $visibility . ' abstract function value(): int; }', 'canonical.php');
    $reordered = $converter->convert('<?php abstract class Base { abstract /* order */ ' . $visibility . ' function value(): int; }', 'reordered.php');
    if ($canonical !== $reordered || !str_contains($reordered, $visibility . ' abstract function value(): int;')) {
        throw new LogicException('Abstract modifier order changed the declaration');
    }
}
foreach ([
    'abstract class Bad { abstract private function value(): int; }',
    'class Bad { abstract public function value(): int; }',
    'abstract class Bad { abstract function value(): int; }',
    'abstract class Bad { abstract public function value(): int {} }',
] as $body) {
    try { $converter->convert('<?php ' . $body, 'bad_modifiers.php'); }
    catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), 'bad_modifiers.php')) {
            throw new LogicException('Modifier diagnostic lost source location');
        }
        continue;
    }
    throw new LogicException('Accepted invalid modifier declaration: ' . $body);
}
echo "Abstract methods: normalized visibility order and rejected invalid modifiers passed\n";

// Preserve parent dispatch; the target owns ancestry and constructor placement.
$parentSource = file_get_contents(__DIR__ . '/fixtures/parent_calls.php');
$parentOutput = $converter->convert($parentSource, 'parent_calls.php');
foreach (['parent::__construct(', 'parent::value('] as $expected) {
    if (!str_contains($parentOutput, $expected)) {
        throw new LogicException('Lost parent dispatch: ' . $expected);
    }
}
foreach ([
    'parent::run();',
    'class Child extends Base { public function value(): int { return parent::VALUE; } }',
    'class Child extends Base { public function value(): int { return parent::$value; } }',
] as $body) {
    try { $converter->convert('<?php ' . $body, 'bad_parent.php'); }
    catch (RuntimeException $error) { continue; }
    throw new LogicException('Accepted unsupported parent access: ' . $body);
}
echo "Parent calls: literal dispatch preserved and unsupported access rejected\n";
