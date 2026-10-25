<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
$root = $argv[1];
$mode = $argv[2];
$source = $root . '/source';
mkdir($source, 0777, true);
$text = "class LargeBox { public static int \$value = 3;\n";
for ($i = 0; $i < 96; $i++) {
	$text .= 'public function method_' . $i . '(int $x): int {' . str_repeat('$x = $x + 1;', 32) . 'return $x;}' . "\n";
}
$text .= '} $box = new LargeBox(); echo $box->method_0(1) + $box->method_95(2) + LargeBox::$value;';
file_put_contents($source . '/main.phs', $text);
$compiler = new Compiler(new cpp_lowering_options($mode === 'concrete'));
$compiler->jobs = 4;
$compiler->init([$source]);
$compiler->select_entry('main', 'main.phs');
$compiler->exec_cpp();
$before = [];
$split = 0;
foreach (Model::$cpp_files as $file) {
	$before[$file->file_name] = $file->text;
	if (str_starts_with($file->file_name, 'units/')) {
		if (strlen($file->text) > 200000) { throw new \RuntimeException('Implementation partition exceeded size threshold'); }
		$split++;
	}
}
if ($split < 3) { throw new \RuntimeException('Large source was not partitioned'); }
$compiler->update_cpp([]);
foreach (Model::$cpp_files as $file) {
	if ($before[$file->file_name] !== $file->text) { throw new \RuntimeException('Warm partition changed'); }
}
// A same-size body edit preserves membership and unrelated artifact contents.
file_put_contents($source . '/main.phs', str_replace('method_0(int $x): int {$x = $x + 1;', 'method_0(int $x): int {$x = $x + 2;', $text));
$compiler->update_cpp([$source . '/main.phs']);
$changed = 0;
foreach (Model::$cpp_files as $file) {
	if (!isset($before[$file->file_name])) { throw new \RuntimeException('Small body edit changed partition identity'); }
	if ($before[$file->file_name] !== $file->text) { $changed++; }
}
if ($changed !== 1) { throw new \RuntimeException('Small body edit changed unrelated partitions'); }
$publication = new Output_Publication($root . '/published');
try { $compiler->cpp($publication); } finally { $publication->close(); }
echo "Partition size, warm reuse and one-partition edit: pass\n";
