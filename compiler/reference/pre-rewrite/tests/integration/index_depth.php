<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';
use Body_Test_Stages as Check;

$root = getcwd() . '/deep-indices';
mkdir($root);
$manifest = $root . '/project.json';
file_put_contents($manifest, json_encode(['source_folders' => ['.'], 'entry' => 'main.phs']));
file_put_contents($root . '/definitions.phs', 'const ONE: int32 = 1; struct row { public int32 $data[2]; }');
$index = '0';
for ($i = 0; $i < 256; ++$i) {
    $index = '$a->data[' . $index . ']';
}
$main = '$a row; $a->data[' . $index . '] = ONE; return ' . $index . ';';
$source = $root . '/main.phs';
file_put_contents($source, $main);
$session = new \compile\Compiler_Session();
$output = $root . '-program';
$first = $session->compile($manifest, $output);
exec(escapeshellarg($output), $stdout, $status);
Check::check($status === 0, 'Deep read/write indices compile and execute with the default PHP stack limit');
$before = serialize($first);
Check::edit($source, str_replace('return ' . $index, 'return ' . $index . ' + ONE', $main));
$next = $session->compile($manifest, $output);
exec(escapeshellarg($output), $stdout, $status);
Check::check(($status === 1) && !$next->inputs->context->full_rebuild, 'One deep-expression body replacement executes incrementally');
Check::check(serialize($first) === $before, 'Deep expression work keeps the retained program unchanged');
echo "index depth ok: 256 nested read/write operands, native execution, default stack settings and one body replacement\n";
