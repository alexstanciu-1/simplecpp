<?php
// Host-only coverage probe; no production model or semantic traversal is introduced.
namespace scpp\compiler;
require_once dirname(__DIR__, 4) . '/compiler/my-try/boot.php';
$compiler = new Compiler();
$compiler->cpp_lowering->prelower_templates = $argv[2] !== 'direct';
$compiler->cpp_lowering->concrete_type_substitution = $argv[2] === 'concrete';
$stage = 'init';
try {
 $compiler->init([$argv[1]]);
 $compiler->select_entry('main', 'main.phs');
 $stage = 'sync'; $compiler->sync([]);
 $stage = 'prepare'; $compiler->prepare(true);
 $stage = 'cpp';
 $publication = new Output_Publication($argv[3]);
 try { $compiler->cpp($publication); } finally { $publication->close(); }
 echo json_encode(['ok'=>true,'stage'=>$stage]), "\n";
} catch (\Throwable $e) {
 echo json_encode(['ok'=>false,'stage'=>$stage,'error'=>$e->getMessage(),'trace'=>$e->getTraceAsString()], JSON_PRETTY_PRINT), "\n";
}
