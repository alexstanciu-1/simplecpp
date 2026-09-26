<?php

// Host composition root for the imported PHP pipeline.
namespace scpp\compiler;

// Host loads the shared portable helpers; converted source uses their fixed bindings.
require_once dirname(__DIR__, 2) . '/tools/php_portability/runtime/bootstrap.php';

require_once __DIR__ . '/helpers/storage_abstract.php';
require_once __DIR__ . '/helpers/storage.php';
require_once __DIR__ . '/helpers/keyed_storage.php';
require_once __DIR__ . '/compiler/sync/structures.php';
require_once __DIR__ . '/01_prepare_inputs/structures.php';
require_once __DIR__ . '/01_prepare_inputs/file.php';
require_once __DIR__ . '/01_prepare_inputs/module.php';
require_once __DIR__ . '/02_tokenize/structures.php';
require_once __DIR__ . '/02_tokenize/text.php';
require_once __DIR__ . '/02_tokenize/tokens.php';
require_once __DIR__ . '/compiler/types/structures.php';
require_once __DIR__ . '/compiler/types/language.php';
require_once __DIR__ . '/03_parse/scopes/structures.php';
require_once __DIR__ . '/03_parse/scopes/lookup.php';
require_once __DIR__ . '/compiler/types/source.php';
require_once __DIR__ . '/03_parse/structures.php';
require_once __DIR__ . '/03_parse/structures_specialization.php';
require_once __DIR__ . '/03_parse/syntax.php';
require_once __DIR__ . '/03_parse/parser.php';
require_once __DIR__ . '/04_analyze/collect/structures.php';
require_once __DIR__ . '/04_analyze/collect/collect.php';
require_once __DIR__ . '/04_analyze/prepare/structures.php';
require_once __DIR__ . '/04_analyze/prepare/names.php';
require_once __DIR__ . '/04_analyze/prepare/literals.php';
require_once __DIR__ . '/04_analyze/prepare/cleanup.php';
require_once __DIR__ . '/04_analyze/prepare/file.php';
require_once __DIR__ . '/05_backend/cpp/structures.php';
require_once __DIR__ . '/05_backend/cpp/types.php';
require_once __DIR__ . '/05_backend/cpp/generate.php';
require_once __DIR__ . '/04_analyze/prepare/templates.php';
require_once __DIR__ . '/05_backend/llvm/structures.php';
require_once __DIR__ . '/05_backend/llvm/text.php';
require_once __DIR__ . '/05_backend/llvm/names.php';
require_once __DIR__ . '/05_backend/llvm/prepare.php';
require_once __DIR__ . '/05_backend/llvm/structs.php';
require_once __DIR__ . '/05_backend/llvm/expressions.php';
require_once __DIR__ . '/05_backend/llvm/statements.php';
require_once __DIR__ . '/05_backend/llvm/functions.php';
require_once __DIR__ . '/05_backend/llvm/write.php';
require_once __DIR__ . '/05_backend/llvm/generate.php';
require_once __DIR__ . '/06_native/structures.php';
require_once __DIR__ . '/06_native/run.php';
require_once __DIR__ . '/compiler/model.php';
require_once __DIR__ . '/compiler/lifecycle.php';
require_once __DIR__ . '/compiler/scope_publication.php';
require_once __DIR__ . '/compiler/structures.php';
require_once __DIR__ . '/compiler/work_queue.php';
require_once __DIR__ . '/compiler/sync/declarations.php';
require_once __DIR__ . '/compiler/sync/sources.php';
require_once __DIR__ . '/compiler/publication.php';
require_once __DIR__ . '/compiler/frontend.php';
require_once __DIR__ . '/compiler/compile.php';
require_once __DIR__ . '/compiler/host_report.php';
