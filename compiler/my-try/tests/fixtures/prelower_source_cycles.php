<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

// Host fixture: a source signature may introduce a physical header/layout cycle.
$source = $argv[1];
if (!is_dir($source)) { mkdir($source, 0777, true); }
$good = 'struct Node { public vector<int> $values; }';
$cycle = 'struct Node { public function rows(): vector<Node> { $v vector<Node>; return $v; } }';
file_put_contents($source . '/main.phs', $good);
$compiler = new Compiler();
$compiler->cpp_lowering->prelower_templates = true;
$compiler->init([$source]);
$compiler->exec_cpp();
$snapshot = static function () { $result=[]; foreach (Model::$cpp_files as $artifact) { $result[$artifact->file_name]=$artifact->text; } return $result; };
$before = $snapshot();
file_put_contents($source . '/main.phs', $cycle);
$rejected = false;
try { $compiler->update_cpp([$source . '/main.phs']); }
catch (\RuntimeException $error) {
    if (!str_contains($error->getMessage(), 'generated header cycle')) { throw $error; }
    $rejected = true;
}
if (!$rejected || ($snapshot() !== [])) { throw new \LogicException('Header cycle published partial output'); }
file_put_contents($source . '/main.phs', $good);
$compiler->update_cpp([$source . '/main.phs']);
if ($snapshot() !== $before) { throw new \LogicException('Header cycle recovery changed output'); }
echo "source header cycle rejection and recovery passed\n";

// Unrelated generated includes are not native layout requests.
$unrelated = new Storage();
$first = new cpp_artifact('unused:first', cpp_artifact_kind::header, 'unused/first.hpp', '');
$first->header_dependencies['unused/second.hpp'] = true;
$second = new cpp_artifact('unused:second', cpp_artifact_kind::header, 'unused/second.hpp', '');
$second->header_dependencies['unused/first.hpp'] = true;
$unrelated->append($first); $unrelated->append($second);
(new CPP_Specializations())->append_artifacts($unrelated);
echo "unrelated headers excluded from layout work\n";
