<?php

// Host composition root for the imported PHP pipeline.
namespace scpp\compiler;

// Host loads the shared portable helpers; converted source uses their fixed bindings.
require_once dirname(__DIR__, 2) . '/tools/php_portability/runtime/bootstrap.php';

require_once __DIR__ . '/helpers/storage_abstract.php';
require_once __DIR__ . '/helpers/storage.php';
require_once __DIR__ . '/helpers/keyed_storage.php';
require_once __DIR__ . '/01_prepare_inputs/structures.php';
require_once __DIR__ . '/01_prepare_inputs/file.php';
require_once __DIR__ . '/01_prepare_inputs/module.php';
require_once __DIR__ . '/02_tokenize/structures.php';
require_once __DIR__ . '/02_tokenize/text.php';
require_once __DIR__ . '/02_tokenize/tokens.php';
require_once __DIR__ . '/03_parse/structures.php';
require_once __DIR__ . '/03_parse/structures_specialization.php';
require_once __DIR__ . '/03_parse/syntax.php';
require_once __DIR__ . '/03_parse/parser.php';
require_once __DIR__ . '/04_analyze/collect/structures.php';
require_once __DIR__ . '/04_analyze/collect/collect.php';
require_once __DIR__ . '/04_analyze/structures.php';
require_once __DIR__ . '/04_analyze/prepare.php';
require_once __DIR__ . '/04_analyze/templates.php';
require_once __DIR__ . '/05_llvm/structures.php';
require_once __DIR__ . '/05_llvm/text.php';
require_once __DIR__ . '/05_llvm/names.php';
require_once __DIR__ . '/05_llvm/prepare.php';
require_once __DIR__ . '/05_llvm/structs.php';
require_once __DIR__ . '/05_llvm/expressions.php';
require_once __DIR__ . '/05_llvm/statements.php';
require_once __DIR__ . '/05_llvm/functions.php';
require_once __DIR__ . '/05_llvm/write.php';
require_once __DIR__ . '/05_llvm/generate.php';
require_once __DIR__ . '/06_native/structures.php';
require_once __DIR__ . '/06_native/run.php';
require_once __DIR__ . '/compile/model.php';
require_once __DIR__ . '/compile/structures.php';
require_once __DIR__ . '/compile/work_queue.php';
require_once __DIR__ . '/compile/compile.php';
require_once __DIR__ . '/compile/host_report.php';
