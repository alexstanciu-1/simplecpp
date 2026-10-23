<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
$map = require __DIR__ . '/../../tools/php_portability/function_map.php';
$prefix = '<?php class Payload {} class Owner { public Payload $item /** _uninitialized_ */; ';
$cases = [
    'compound' => [$prefix . 'public function f(Payload $v): void { $this->item += $v; } }', 'mutation, projection or reference'],
    'projection' => [$prefix . 'public function f(): void { $this->item->value = 1; } }', 'mutation, projection or reference'],
    'expression-write' => [$prefix . 'public function f(Payload $v): Payload { return $this->item = $v; } }', 'standalone whole-field'],
    'probe' => [$prefix . 'public function f(): bool { return isset($this->item[0]); } }', 'probes, removal or nested callables'],
    'removal' => [$prefix . 'public function f(): void { unset($this->item[0]); } }', 'probes, removal or nested callables'],
    'closure' => [$prefix . 'public function f(): void { $f = function (): Payload { return $this->item; }; } }', 'probes, removal or nested callables'],
    'unknown' => ['<?php class Owner { public External $item /** _uninitialized_ */; }', 'class declared in this file'],
    'nullable' => ['<?php class Payload {} class Owner { public ?Payload $item /** _uninitialized_ */; }', 'required instance field'],
    'static' => ['<?php class Payload {} class Owner { public static Payload $item /** _uninitialized_ */; }', 'required instance field'],
    'inherited' => ['<?php class Payload {} class Base {} class Owner extends Base { public Payload $item /** _uninitialized_ */; }', 'inherited or trait-owned'],
    'trait' => ['<?php class Payload {} trait Fields { public Payload $item /** _uninitialized_ */; } class Owner { use Fields; }', 'trait-owned staged fields'],
    'multiply' => [$prefix . 'public function f(Payload $v): void { $this->item *= $v; } }', 'unsupported syntax: *='],
    'coalesce-write' => [$prefix . 'public function f(Payload $v): void { $this->item ??= $v; } }', 'unsupported syntax: ??='],
    'collision' => [$prefix . 'private function scpp_portability_staged_read_item(): Payload { return new Payload(); } }', 'reserved staged-field accessor'],
    'collision-case' => [$prefix . 'private function SCPP_PORTABILITY_STAGED_READ_ITEM(): Payload { return new Payload(); } }', 'reserved staged-field accessor'],
];
foreach (['v0.1', 'v0.2'] as $target) {
    foreach ($cases as $label => [$source, $diagnostic]) {
        try { (new scpp\portability\Converter($map, $target))->convert($source, $label . '.php'); }
        catch (RuntimeException $error) {
            if (!str_contains($error->getMessage(), $diagnostic)) { throw $error; }
            continue;
        }
        throw new LogicException('Accepted unsupported staged use: ' . $label);
    }
}
echo "Staged field structural rejections passed for both targets\n";
