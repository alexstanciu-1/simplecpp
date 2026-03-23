<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Support/AstKind.php';
require_once __DIR__ . '/../src/Loader/ParsedInput.php';
require_once __DIR__ . '/../src/Loader/InputLoader.php';
require_once __DIR__ . '/../src/Metadata/TypeCommentExtractor.php';
require_once __DIR__ . '/../src/IR/PhpFile.php';
require_once __DIR__ . '/../src/IR/NamespaceBlock.php';
require_once __DIR__ . '/../src/IR/UseDecl.php';
require_once __DIR__ . '/../src/IR/ConstantDecl.php';
require_once __DIR__ . '/../src/IR/ClassDecl.php';
require_once __DIR__ . '/../src/IR/PropertyDecl.php';
require_once __DIR__ . '/../src/IR/FunctionDecl.php';
require_once __DIR__ . '/../src/IR/MethodDecl.php';
require_once __DIR__ . '/../src/IR/ParamDecl.php';
require_once __DIR__ . '/../src/IR/Statement.php';
require_once __DIR__ . '/../src/Builder/IrBuilder.php';
require_once __DIR__ . '/../src/Lowering/TypeMapper.php';
require_once __DIR__ . '/../src/Emit/CppFile.php';
require_once __DIR__ . '/../src/Generator/NameRegistry.php';
require_once __DIR__ . '/../src/Generator/Generator.php';
require_once __DIR__ . '/../src/Transpiler.php';
