<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function async_publish(Compiler $compiler, string $root, ?array $changed): void
{
 $publication = new Output_Publication($root);
 try {
  if ($changed === null) { $compiler->exec_cpp($publication); }
  else { $compiler->update_cpp($changed, $publication); }
 } finally { $publication->close(); }
}
function async_disk_snapshot(string $root): array
{
 $manifest = json_decode(Output_Publication::require_ready($root), true, 512, JSON_THROW_ON_ERROR);
 if ($manifest['application_templates'] !== 'forbidden') { throw new \LogicException('Lost default prelowering'); }
 $snapshot = [];
 foreach ($manifest['files'] as $file) {
  $text = file_get_contents($root . '/' . $file['path']);
  if (hash('sha256', $text) !== $file['sha256']) { throw new \LogicException('Published artifact hash differs'); }
  $snapshot[$file['path']] = $text;
 }
 ksort($snapshot);
 return $snapshot;
}
$root = $argv[1];
mkdir($root . '/source', 0777, true);
$path = $root . '/source/main.phs';
$valid = 'async function child():int{await async_sleep_ms(0);return 3;}echo await child();';
file_put_contents($path, $valid);
$compiler = new Compiler();
$compiler->init([$root . '/source']);
$compiler->select_entry('main', 'main.phs');
async_publish($compiler, $root . '/published', null);
$initial = async_disk_snapshot($root . '/published');
if (count(array_filter(array_keys($initial), fn($path) => str_starts_with($path, 'specializations/async/'))) !== 1) {
 throw new \LogicException('Missing compiled async implementation unit');
}
file_put_contents($path, str_replace('return 3;', 'return "invalid";', $valid));
$failed = false;
try { async_publish($compiler, $root . '/published', [$path]); }
catch (\RuntimeException $error) { $failed = true; }
if (!$failed) { throw new \LogicException('Invalid async return accepted'); }
$ready = true;
try { Output_Publication::require_ready($root . '/published'); }
catch (\RuntimeException $error) { $ready = false; }
if ($ready) { throw new \LogicException('Invalid revision exposed old ready output'); }
file_put_contents($path, $valid);
async_publish($compiler, $root . '/published', [$path]);
if (async_disk_snapshot($root . '/published') !== $initial) { throw new \LogicException('Disk repair differs'); }
async_publish($compiler, $root . '/published', []);
if (async_disk_snapshot($root . '/published') !== $initial) { throw new \LogicException('Disk replay differs'); }
$previous = $initial;
foreach (['function child():int{return 4;}echo child();', 'async function child():string{await async_sleep_ms(0);return "new";}echo await child();', $valid] as $index => $source) {
 file_put_contents($path, $source);
 async_publish($compiler, $root . '/published', [$path]);
 $current = async_disk_snapshot($root . '/published');
 foreach (array_diff(array_keys($previous), array_keys($current)) as $retired) {
  if (file_exists($root . '/published/' . $retired)) { throw new \LogicException('Retired async artifact remains on disk'); }
 }
 if ($index === 0 && count(array_filter(array_keys($current), fn($path) => str_starts_with($path, 'specializations/async/'))) !== 0) {
  throw new \LogicException('Removed async function retained its implementation unit');
 }
 Compiler_Lifecycle::reset();
 $compiler = new Compiler();
 $compiler->init([$root . '/source']);
 $compiler->select_entry('main', 'main.phs');
 async_publish($compiler, $root . '/fresh-' . $index, null);
 if (async_disk_snapshot($root . '/fresh-' . $index) !== $current) { throw new \LogicException('Published incremental/fresh artifacts differ'); }
 $previous = $current;
}
echo "Async disk rejection, repair, replay, retirement and fresh parity passed\n";
