<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

$artifacts = new Storage();
$source = new cpp_artifact('source', cpp_artifact_kind::header, 'source.hpp', '');
$source->header_dependencies = ['first.hpp' => true];
$artifacts->append($source);
// Unreached source-header cycles must not participate in layout ordering.
$unused = new cpp_artifact('unused', cpp_artifact_kind::header, 'unused.hpp', '');
$unused->header_dependencies = ['unused.hpp' => true];
$artifacts->append($unused);
$entries = new Storage();
$entries->append(new cpp_specialization_entry('second', 'second.hpp', '', '', ['source.hpp' => true], '', 'probe'));
$entries->append(new cpp_specialization_entry('first', 'first.hpp', '', '', ['native.hpp' => true], '', 'probe'));
$worker = new CPP_Layout_Depths();
if ($worker->resolve($artifacts, $entries) !== ['second' => 1, 'first' => 0]) {
	throw new \RuntimeException('Dependency depths changed');
}
$source->header_dependencies = ['second.hpp' => true];
$rejected = false;
try { $worker->resolve($artifacts, $entries); }
catch (\RuntimeException $error) { $rejected = $error->getMessage() === 'Concrete layout dependencies contain a generated header cycle'; }
if (!$rejected) { throw new \RuntimeException('Layout cycle was accepted'); }
$source->header_dependencies = ['first.hpp' => true];
if ($worker->resolve($artifacts, $entries) !== ['second' => 1, 'first' => 0]) {
	throw new \RuntimeException('Failed resolution left stale depths');
}
echo "Layout dependencies, irrelevant headers, cycle rejection and retry: pass\n";
