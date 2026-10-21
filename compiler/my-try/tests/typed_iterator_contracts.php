<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

$directory = $argv[1];
mkdir($directory, 0777, true);
$path = $directory . '/main.phs';
$valid = 'class Cursor implements \\Iterator {
 public function current(): int { return 7; }
 public function key(): int { return 0; }
 public function valid(): bool { return false; }
 public function next(): void {}
 public function rewind(): void {}
} foreach (new Cursor() as $value) { echo $value; }';
$compiler = new Compiler();
$compiler->init([$directory]);
$cases = [
 'missing' => [str_replace('public function current(): int { return 7; }', '', $valid), 'Iterator requires one'],
 'visibility' => [str_replace('public function current', 'private function current', $valid), 'Iterator methods require'],
 'static' => [str_replace('public function current', 'public static function current', $valid), 'Iterator methods require'],
 'parameters' => [str_replace('current()', 'current(int $n = 1)', $valid), 'Iterator methods require'],
 'valid result' => [str_replace('valid(): bool { return false;', 'valid(): int { return 0;', $valid), 'Iterator requires valid'],
 'next result' => [str_replace('next(): void {}', 'next(): int { return 0; }', $valid), 'Iterator requires valid'],
 'void value' => [str_replace('current(): int { return 7; }', 'current(): void {}', $valid), 'void is not a storage'],
 'marker storage' => ['$cursor Iterator;', 'protocol marker'],
 'struct' => [str_replace('class Cursor', 'struct Cursor', $valid), 'shared class or interface'],
 'duplicate' => [str_replace('implements \\Iterator', 'implements \\Iterator, \\Iterator', $valid), 'Duplicate direct'],
 'runtime inheritance' => ['class Child extends Exception {}', 'Relationship target must'],
 'unmarked' => [str_replace(' implements \\Iterator', '', $valid), 'registered iterable'],
];
foreach ($cases as $name => [$source, $message]) {
 file_put_contents($path, $source);
 $failed = false;
 try { $compiler->sync([$path]); $compiler->prepare(); }
 catch (\RuntimeException $error) {
  $failed = true;
  if (!str_contains($error->getMessage(), $message)) { throw new \LogicException($name . ': ' . $error->getMessage()); }
 }
 if (!$failed) { throw new \LogicException('Admitted invalid Iterator: ' . $name); }
 file_put_contents($path, $valid);
 $compiler->sync([$path]);$compiler->prepare();$compiler->cpp();
}
// A method signature edit must invalidate dependent foreach bindings, then recover.
file_put_contents($path, str_replace('current(): int { return 7; }', 'current(): string { return "seven"; }', $valid));
$compiler->sync([$path]);$compiler->prepare();$compiler->cpp();
$loop = Model::syntax_files()[0]->root->single_body()->body->statements[0];
if (!($loop instanceof foreach_node)
 || !$loop->require_preparation()->iteration->value_type->matches(Language_Types::string_type(Model::$language_scope))) {
 throw new \LogicException('Signature edit did not invalidate the foreach value type');
}
file_put_contents($path, $valid);
$compiler->sync([$path]);$compiler->prepare();$compiler->cpp();
echo "Iterator admission and incremental recovery passed\n";
