<?php
declare(strict_types=1);

namespace Scpp\S2S\Generator;

use Scpp\S2S\Emit\CodeBlock;
use Scpp\S2S\Emit\CppFile;
use Scpp\S2S\IR\ArgNormalizationRule;
use Scpp\S2S\IR\ClassDecl;
use Scpp\S2S\IR\ConstantDecl;
use Scpp\S2S\IR\FunctionDecl;
use Scpp\S2S\IR\MethodDecl;
use Scpp\S2S\IR\ParamDecl;
use Scpp\S2S\IR\PhpFile;
use Scpp\S2S\IR\PropertyDecl;
use Scpp\S2S\IR\Statement;
use Scpp\S2S\IR\UseDecl;
use Scpp\S2S\Lowering\TypeMapper;
use Scpp\S2S\Support\AnnotationExpressionParser;
use Scpp\S2S\Support\AstKind;
use Scpp\S2S\Support\GenerationException;

/**
 * Emits Prism++ declarations and statements from the IR. This file is where the catalog rules are turned into concrete header/source text.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with generators/php/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class Generator
{
	/** @var array<string, bool> */
	private array $declaredLocals = [];
	/** @var list<string> */
	private array $errors = [];
	/** @var list<string> */
	private array $warnings = [];
	/** @var list<int> */
	private array $headerLineMap = [];
	/** @var list<int> */
	private array $sourceLineMap = [];
	private string $currentSourcePath = '';
	private int $currentSourceLine = 0;
	private int $currentSourceColumn = 0;
	/** @var array<string, string> */
	private array $localTypeComments = [];
	/** @var array<int, string> */
	private array $scannerReturnAnnotationsByLine = [];
	/** @var array<string,string> */
	private array $declaredTypeKinds = [];
	/** @var array<string, string> */
	private array $declaredLocalTypes = [];
	/** @var array<string, bool> */
	private array $predefinedReferenceLocals = [];
	/** @var array<string, bool> */
	private array $predefinedConstants = [];
	private NameRegistry $nameRegistry;
	/** @var array<string, FunctionDecl> */
	private array $functionDecls = [];
	/** @var array<string, MethodDecl> */
	private array $methodDecls = [];
	/** @var array<string, ClassDecl> */
	private array $classDecls = [];
	/** @var array<string, string> */
	private array $currentParamPassModes = [];
	/** @var list<string> */
	private array $currentScalarRefParamAliasLines = [];
	/** @var list<string> */
	private array $currentParamEntryAliasLines = [];
	/** @var array<string, ArgNormalizationRule> */
	private array $currentArgNormalizationRulesByKey = [];
	private ?string $currentNormalizationCallableName = null;
	private ?string $currentReturnType = null;
	private bool $currentFunctionIsAsync = false;
	/** @var null|array{flag:string,value:?string,type:?string} */
	private ?array $currentFinallyReturnContext = null;
	/** @var null|array{returnType:?string,paramTypes:list<string>} */
	private ?array $currentExpectedClosureSignature = null;
	private ?string $currentNamespacePhp = null;
	private ?string $currentClassName = null;
	private ?string $currentParentClass = null;
	/** @var array<string, MethodDecl> */
	private array $currentLateStaticDispatchMethods = [];
	private int $tempCounter = 0;
	private AnnotationExpressionParser $annotationExpressionParser;
	/** @var array<string, string> */
	private array $phpRuntimeRelativeSymbols = [];
	/** @var array<string, string> */
	private array $currentPhpVarToCpp = [];
	/** @var array<string, bool> */
	private array $currentUsedCppVarNames = [];
	/** @var array<string, string> */
	private array $currentLocalArrayShapes = [];
	/** @var array<string, array{line:int}> */
	private array $activeConditionVisibilityHints = [];
	/** @var list<array<string, string>> */
	private array $foreachReferenceSlotStack = [];
	/** @var list<array<string, bool>> */
	private array $foreachReferenceSuppressedNamesStack = [];
	private string $phpProfile;

	/**

	 * Stores collaborators and default state for this phase object.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function __construct(
		private readonly TypeMapper $typeMapper = new TypeMapper(),
		string $phpProfile = 'legacy',
	) {
		$normalizedPhpProfile = strtolower(trim($phpProfile));
		$this->phpProfile = in_array($normalizedPhpProfile, ['legacy', 'strict'], true) ? $normalizedPhpProfile : 'legacy';
		$this->predefinedConstants = $this->loadPredefinedConstants();
		$this->phpRuntimeRelativeSymbols = $this->loadPhpRuntimeRelativeSymbols();
		$this->nameRegistry = new NameRegistry();
		$this->annotationExpressionParser = new AnnotationExpressionParser();
	}

	/**

	 * Generates the header/source pair for one lowered PHP file and accumulates generator diagnostics.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function generate(PhpFile $file, bool $emitProgramEntry = true): CppFile
	{
		$this->declaredLocals = [];
		$this->activeConditionVisibilityHints = [];
		$this->errors = $file->buildErrors;
		$this->warnings = [];
		$this->localTypeComments = $file->localTypeCommentsByKey;
		$this->scannerReturnAnnotationsByLine = $this->indexScannerReturnAnnotations($file);
		$this->declaredLocalTypes = [];
		$this->predefinedReferenceLocals = [];
		$this->currentFinallyReturnContext = null;
		$this->currentPhpVarToCpp = [];
		$this->currentUsedCppVarNames = [];
		$this->currentLocalArrayShapes = [];
		$this->foreachReferenceSlotStack = [];
		$this->foreachReferenceSuppressedNamesStack = [];
		$this->tempCounter = 0;
		$this->nameRegistry = NameRegistry::fromPhpFile($file);
		$this->functionDecls = $this->collectFunctionDecls($file);
		$this->methodDecls = $this->collectMethodDecls($file);
		$this->classDecls = $this->collectClassDecls($file);
		$this->typeMapper->setEnumNames($this->collectEnumTypeNames());
		$this->typeMapper->setDeclaredTypeKinds(array_replace($this->declaredTypeKinds, $this->collectLocalDeclaredTypeKinds()));
		$this->validatePhpFile($file);
		$this->throwIfErrors();

		$baseName = pathinfo($file->path, PATHINFO_FILENAME);
		$header = [];
		$this->headerLineMap = [];
		$this->currentSourcePath = $file->path;
		$this->currentSourceLine = 0;
		$this->currentSourceColumn = 0;
		$this->appendHeaderLines($header, $this->code('#pragma once', 0));
		$this->appendHeaderLines($header, $this->code('', 0));
		$this->appendHeaderLines($header, $this->code('#include <scpp/lang/php.hpp>', 0));
		$this->appendHeaderLines($header, $this->code('#include <cstddef>', 0));
		$this->appendHeaderLines($header, $this->code('#include <type_traits>', 0));
		$this->appendHeaderLines($header, $this->code('#include <utility>', 0));
		foreach ($file->prologueIncludes as $includePath) {
			$this->appendHeaderLines($header, $this->code('#include "' . $includePath . '"', 0));
		}
		$this->appendHeaderLines($header, $this->code('', 0));
		$source = [];
		$this->sourceLineMap = [];
		$this->appendSourceLines($source, $this->code('#include "' . $baseName . '.hpp"', 0));
		$this->appendSourceLines($source, $this->code('', 0));

		$hasRootNamespaceContent = ($file->rootUses !== [] || $file->constants !== [] || $file->classes !== [] || $file->functions !== [] || $file->rootStatements !== []);
		$unitMainName = $this->buildUnitMainName($file, $emitProgramEntry);
		$rootMainName = $file->rootStatements !== [] ? $unitMainName : null;
		if ($hasRootNamespaceContent) {
			$this->emitNamespaceBlock($header, $source, 'scpp', null, $file->rootUses, $file->constants, $file->classes, $file->functions, $file->rootStatements, $rootMainName);
		}

		$namespaceMainTargets = [];
		foreach ($file->namespaces as $namespace) {
			$mainName = $namespace->statements !== [] ? $unitMainName : null;
			$namespaceCpp = $this->buildNamespaceCppName($namespace->name);
			$this->emitNamespaceBlock(
				$header,
				$source,
				$namespaceCpp,
				$namespace->name,
				$namespace->uses,
				$namespace->constants,
				$namespace->classes,
				$namespace->functions,
				$namespace->statements,
				$mainName,
			);
			if ($mainName !== null) {
				$namespaceMainTargets[] = $this->qualifyNamespaceSymbol($namespace->name, $mainName) . '()';
			}
		}

		if ($file->rootStatements !== [] && $namespaceMainTargets !== []) {
			$this->fail('Root executable statements and namespace executable statements are not mixed in the current pass.');
		}

		if ($emitProgramEntry && $file->rootStatements !== []) {
			$this->appendSourceLines($source, ...$this->codeLinesFromStrings([
				'int main(int __scpp_argc, char** __scpp_argv) {',
				$this->indent(1) . 'try {',
				$this->indent(2) . '::scpp::php::set_cli_args(__scpp_argc, __scpp_argv);',
				$this->indent(2) . 'return scpp::' . $unitMainName . '();',
				$this->indent(1) . '} catch (const std::exception &exception) {',
				$this->indent(2) . '::scpp::print_runtime_exception(exception);',
				$this->indent(2) . 'return 1;',
				$this->indent(1) . '}',
				'}',
				'',
			], 0));
		} elseif ($emitProgramEntry && $namespaceMainTargets !== []) {
			$this->appendSourceLines($source, ...$this->codeLinesFromStrings([
				'int main(int __scpp_argc, char** __scpp_argv) {',
				$this->indent(1) . 'try {',
				$this->indent(2) . '::scpp::php::set_cli_args(__scpp_argc, __scpp_argv);',
				$this->indent(2) . 'return ' . $namespaceMainTargets[0] . ';',
				$this->indent(1) . '} catch (const std::exception &exception) {',
				$this->indent(2) . '::scpp::print_runtime_exception(exception);',
				$this->indent(2) . 'return 1;',
				$this->indent(1) . '}',
				'}',
				'',
			], 0));
		}

		$this->throwIfErrors();

		return new CppFile($baseName, $this->flattenCodeText($header), $this->flattenCodeLineMap($header), $this->buildExportManifest($file), $this->flattenCodeText($source), $this->flattenCodeLineMap($source), $this->errors, $this->warnings);
	}

	/** @param array<string,string> $declaredTypeKinds */
	public function setDeclaredTypeKinds(array $declaredTypeKinds): void
	{
		$this->declaredTypeKinds = $declaredTypeKinds;
	}

	private function code(string $text, int $srcLine = -1, int $srcColumn = -1, string $srcRelation = 'exact'): CodeBlock
	{
		return new CodeBlock($text, $srcLine, $srcColumn, $srcRelation);
	}

	private function codeWithCurrentOrigin(string $text): CodeBlock
	{
		return $this->code($text, $this->currentSourceLine, $this->currentSourceColumn);
	}

	private function cppStringLiteral(string $value): string
	{
		$escaped = str_replace(
			["\\", "\"", "\n", "\r", "\t", "\v", "\f"],
			["\\\\", "\\\"", "\\n", "\\r", "\\t", "\\v", "\\f"],
			$value,
		);
		return '"' . $escaped . '"';
	}

	private function renderCallDepthGuardLine(string $callableName, int $line): string
	{
		return 'SCPP_CALL_DEPTH_GUARD(' . $this->cppStringLiteral($callableName) . ', ' . $this->cppStringLiteral($this->currentSourcePath) . ', ' . $line . ');';
	}

	private function renderGeneratedCast(string $type, string $expr): string
	{
		return 'cast<' . $type . '>(' . $expr . ')';
	}

	private function renderRequiredTypedBoundaryCast(string $type, string $expr): string
	{
		if ($type === 'mixed_t') {
			return $expr;
		}
		if ($this->isDirectInitializerBoundaryType($type)) {
			return $expr;
		}
		if (($type === 'dynamic_t<>' || str_starts_with($type, 'shared_p<')) && $expr === 'null') {
			return $expr;
		}

		return 'required_cast<' . $type . '>(' . $expr . ')';
	}

	private function isDirectInitializerBoundaryType(string $type): bool
	{
		if (preg_match('/^(?:nullable|result|result_or_false|result_or_bool|shared_p|unique_p|weak_p|value_p|fixed_array_t)<.+>$/', $type) === 1) {
			return true;
		}
		if ($this->resolveStructDeclByMappedType($type) instanceof ClassDecl) {
			return true;
		}
		return $this->typeMapper->declaredTypeKind(str_replace('::', '\\', trim($type))) === 'struct';
	}

	/** @param list<string> $lines @return list<CodeBlock> */
	private function codeLinesFromStrings(array $lines, int $srcLine = -1, int $srcColumn = -1, string $srcRelation = 'exact'): array
	{
		return array_map(fn (string $line): CodeBlock => $this->code($line, $srcLine, $srcColumn, $srcRelation), $lines);
	}

	/** @return list<CodeBlock> */
	private function codeLinesFromTextBlock(string $block, int $srcLine = -1, int $srcColumn = -1, string $srcRelation = 'exact'): array
	{
		return $this->codeLinesFromStrings(explode("\n", $block), $srcLine, $srcColumn, $srcRelation);
	}

	/** @param list<CodeBlock> $lines @return list<CodeBlock> */
	private function indentCodeLines(array $lines, int $level = 1): array
	{
		return array_map(fn (CodeBlock $line): CodeBlock => $this->code($this->indent($level) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation), $lines);
	}

	private function appendHeaderLines(array &$header, CodeBlock ...$lines): void
	{
		foreach ($lines as $line) {
			$header[] = $this->normalizeCodeBlock($line);
		}
	}

	private function appendSourceLines(array &$source, CodeBlock ...$lines): void
	{
		foreach ($lines as $line) {
			$source[] = $this->normalizeCodeBlock($line);
		}
	}

	private function normalizeCodeBlock(CodeBlock $line): CodeBlock
	{
		$srcLine = $line->srcLine < 0 ? $this->currentSourceLine : $line->srcLine;
		$srcColumn = $line->srcColumn < 0 ? $this->currentSourceColumn : $line->srcColumn;
		$srcRelation = $line->srcRelation;
		if ($srcLine <= 0) {
			$srcLine = max(1, $this->currentSourceLine);
			if ($srcRelation === 'exact') {
				$srcRelation = 'around';
			}
		}
		return $this->code($line->text, $srcLine, $srcColumn, $srcRelation);
	}

	/** @param list<CodeBlock> $lines @return list<string> */
	private function flattenCodeText(array $lines): array
	{
		return array_map(static fn (CodeBlock $line): string => $line->text, $lines);
	}

	/** @param list<CodeBlock> $lines @return list<array{line:int,relation:string}> */
	private function flattenCodeLineMap(array $lines): array
	{
		return array_map(
			static fn (CodeBlock $line): array => [
				'line' => max(1, $line->srcLine),
				'relation' => in_array($line->srcRelation, ['exact', 'above', 'below', 'around'], true) ? $line->srcRelation : 'around',
			],
			$lines
		);
	}

	/** @param list<CodeBlock> $lines @return list<string> */
	private function flattenExportCodeText(array $lines): array
	{
		return $this->flattenCodeText($lines);
	}

	/** @param list<string> $lines @return list<CodeBlock> */
	private function statementCodeLines(Statement $statement, array $lines): array
	{
		return $this->codeLinesFromStrings($lines, $statement->line);
	}

	/** @return array<int, string> */
	private function indexScannerReturnAnnotations(PhpFile $file): array
	{
		$out = [];
		foreach ($file->scannerAnnotations as $annotation) {
			if (!in_array(($annotation['kind'] ?? null), ['function_return', 'method_return', 'closure_return'], true)) {
				continue;
			}
			$line = (int) ($annotation['line'] ?? 0);
			$type = $annotation['type'] ?? null;
			if ($line > 0 && is_string($type) && $type !== '') {
				$out[$line] = $type;
			}
		}
		return $out;
	}

	/** @return array<string,mixed> */
	private function buildExportManifest(PhpFile $file): array
	{
		$manifest = [
			'source' => basename($file->path),
			'prologue_includes' => array_values($file->prologueIncludes),
			'namespaces' => [],
		];

		$rootConstants = array_values(array_filter($file->constants, static fn (ConstantDecl $constant): bool => $constant->isLibExport));
		$rootClasses = array_values(array_filter($file->classes, static fn (ClassDecl $class): bool => $class->isLibExport));
		$rootFunctions = array_values(array_filter($file->functions, static fn (FunctionDecl $function): bool => $function->isLibExport));
		if ($rootConstants !== [] || $rootClasses !== [] || $rootFunctions !== []) {
			$manifest['namespaces'][] = $this->buildExportNamespaceManifest('scpp', null, $rootConstants, $rootClasses, $rootFunctions);
		}

		foreach ($file->namespaces as $namespace) {
			$constants = array_values(array_filter($namespace->constants, static fn (ConstantDecl $constant): bool => $constant->isLibExport));
			$classes = array_values(array_filter($namespace->classes, static fn (ClassDecl $class): bool => $class->isLibExport));
			$functions = array_values(array_filter($namespace->functions, static fn (FunctionDecl $function): bool => $function->isLibExport));
			if ($constants === [] && $classes === [] && $functions === []) {
				continue;
			}
			$manifest['namespaces'][] = $this->buildExportNamespaceManifest($this->buildNamespaceCppName($namespace->name), $namespace->name, $constants, $classes, $functions);
		}

		return $manifest;
	}

	/** @param list<ConstantDecl> $constants @param list<ClassDecl> $classes @param list<FunctionDecl> $functions @return array<string,mixed> */
	private function buildExportNamespaceManifest(string $namespaceCpp, ?string $namespacePhp, array $constants, array $classes, array $functions): array
	{
		$headerLines = [
			$this->code('namespace ' . $namespaceCpp . ' {', 0),
			$this->code('', 0),
		];
		$constantEntries = [];
		foreach ($constants as $constant) {
			$start = count($headerLines);
			$this->emitConstant($headerLines, $constant, $namespacePhp);
			$constantEntries[] = [
				'kind' => 'constant',
				'name' => $constant->name,
				'declaration_lines' => $this->flattenExportCodeText(array_values(array_slice($headerLines, $start))),
			];
		}
		if ($constants !== []) {
			$headerLines[] = $this->code('', 0);
		}
		foreach ($this->collectNamespaceForwardClassNames($classes, $functions, $namespacePhp) as $className => $classKind) {
			$headerLines[] = $this->code($classKind . ' ' . $className . ';', 0);
		}
		if ($classes !== []) {
			$headerLines[] = $this->code('', 0);
		}
		$discardSource = [];
		$classEntries = [];
		foreach ($classes as $class) {
			$start = count($headerLines);
			$this->emitClass($headerLines, $discardSource, $class, $namespacePhp);
			$classEntries[] = [
				'kind' => 'class',
				'name' => $class->name,
				'declaration_lines' => $this->flattenExportCodeText(array_values(array_slice($headerLines, $start))),
			];
		}
		$functionEntries = [];
		foreach ($functions as $function) {
			$start = count($headerLines);
			$this->emitFunction($headerLines, $discardSource, $function, $namespacePhp);
			$functionEntries[] = [
				'kind' => 'function',
				'name' => $function->name,
				'declaration_lines' => $this->flattenExportCodeText(array_values(array_slice($headerLines, $start))),
			];
		}
		$headerLines[] = $this->code('}', 0);
		$headerLines[] = $this->code('', 0);
		return [
			'namespace_cpp' => $namespaceCpp,
			'namespace_php' => $namespacePhp,
			'constants' => $constantEntries,
			'classes' => $classEntries,
			'functions' => $functionEntries,
			'header_lines' => $this->flattenExportCodeText($headerLines),
		];
	}



	private function buildUnitMainName(PhpFile $file, bool $emitProgramEntry): string
	{
		if ($emitProgramEntry) {
			return '__scpp_main';
		}

		$hash = substr($this->buildStableUnitHash($file), 0, 12);
		return '__scpp_unit_' . $hash;
	}

	private function buildStableUnitHash(PhpFile $file): string
	{
		$contents = @file_get_contents($file->path);
		if (is_string($contents)) {
			return hash('sha256', $contents);
		}

		return hash('sha256', str_replace('\\', '/', $file->path));
	}

	private function addError(string $message): void
	{
		$this->errors[] = $message;
	}

	private function fail(string $message): never
	{
		$this->addError($message);
		throw new GenerationException($message);
	}

	private function unsupportedExprKindMessage(mixed $expr, mixed $kind): string
	{
		$line = (int) ($expr->lineno ?? 0);
		return 'Unsupported expression lowering for AST kind ' . (string) $kind . ' at line ' . $line . '. '
			. 'Category: generator lowering gap. '
			. 'Requirement: add an explicit lowering rule for this expression shape or rewrite the source to a supported form.';
	}

	private function throwIfErrors(): void
	{
		if ($this->errors === []) {
			return;
		}

		$messages = array_values(array_unique($this->errors));
		throw new GenerationException(implode(PHP_EOL, $messages));
	}

	/** @return array<string, FunctionDecl> */
	private function buildNamespaceCppName(?string $namespacePhp): string
	{
		if ($namespacePhp === null || $namespacePhp === '') {
			return 'scpp';
		}

		return 'scpp::' . str_replace('\\', '::', $namespacePhp);
	}

	private function qualifyNamespaceSymbol(?string $namespacePhp, string $symbol): string
	{
		if ($namespacePhp === null || $namespacePhp === '') {
			return 'scpp::' . $symbol;
		}

		return 'scpp::' . str_replace('\\', '::', $namespacePhp) . '::' . $symbol;
	}

	private function collectFunctionDecls(PhpFile $file): array
	{
		$out = [];

		foreach ($file->functions as $function) {
			$out[$function->name] = $function;
		}

		foreach ($file->namespaces as $namespace) {
			foreach ($namespace->functions as $function) {
				$out[$namespace->name . '\\' . $function->name] = $function;
			}
		}

		return $out;
	}

	/** @return array<string, ClassDecl> */
	private function collectClassDecls(PhpFile $file): array
	{
		$out = [];

		foreach ($file->classes as $class) {
			$out[$class->name] = $class;
		}

		foreach ($file->namespaces as $namespace) {
			foreach ($namespace->classes as $class) {
				$out[$namespace->name . '\\' . $class->name] = $class;
				$out[$class->name] ??= $class;
			}
		}

		return $out;
	}

	/** @return array<string, MethodDecl> */
	private function collectMethodDecls(PhpFile $file): array
	{
		$out = [];

		foreach ($file->classes as $class) {
			foreach ($class->methods as $method) {
				$out[$class->name . '::' . $method->name] = $method;
			}
		}

		foreach ($file->namespaces as $namespace) {
			foreach ($namespace->classes as $class) {
				$qualifiedClass = $namespace->name . '\\' . $class->name;
				foreach ($class->methods as $method) {
					$out[$qualifiedClass . '::' . $method->name] = $method;
				}
			}
		}

		return $out;
	}

	/** @return array<string, bool> */
	private function collectEnumTypeNames(): array
	{
		$out = [];
		foreach ($this->classDecls as $name => $class) {
			if (!$class->isEnum) {
				continue;
			}
			$out[ltrim($name, '\\')] = true;
			$out[$class->name] = true;
		}

		return $out;
	}

	/** @return array<string,string> */
	private function collectLocalDeclaredTypeKinds(): array
	{
		$out = [];
		foreach ($this->classDecls as $name => $class) {
			$kind = $class->isUnion ? 'union' : ($class->isStruct ? 'struct' : ($class->isEnum ? 'enum' : 'class'));
			$out[ltrim($name, '\\')] = $kind;
			$out[$class->name] = $kind;
		}
		return $out;
	}

	private function isKnownEnumTypeName(string $name): bool
	{
		$trimmed = ltrim(trim($name), '\\');
		if ($trimmed === '') {
			return false;
		}
		$class = $this->classDecls[$trimmed] ?? $this->classDecls[basename(str_replace('\\', '/', $trimmed))] ?? null;
		return ($class instanceof ClassDecl && $class->isEnum) || $this->typeMapper->declaredTypeKind($trimmed) === 'enum';
	}

	private function lookupEnumDeclByTypeName(string $name): ?ClassDecl
	{
		$trimmed = ltrim(trim($name), '\\');
		if ($trimmed === '') {
			return null;
		}
		$class = $this->classDecls[$trimmed] ?? $this->classDecls[basename(str_replace('\\', '/', $trimmed))] ?? null;
		return $class instanceof ClassDecl && $class->isEnum ? $class : null;
	}

	private function lookupFunctionDeclByCall(mixed $nameExpr, ?string $namespacePhp): ?FunctionDecl
	{
		if (!is_object($nameExpr) || ($nameExpr->kind ?? null) !== AstKind::NAME) {
			return null;
		}

		$phpName = (string) ($nameExpr->children['name'] ?? '');
		$flags = (int) ($nameExpr->flags ?? 0);
		$resolved = $this->nameRegistry->resolveFunction($phpName, $flags, $namespacePhp);
		if ($resolved !== null && isset($this->functionDecls[$resolved])) {
			return $this->functionDecls[$resolved];
		}

		$trimmed = ltrim($phpName, '\\');
		return $this->functionDecls[$trimmed] ?? null;
	}

	private function lookupMethodDeclByStaticCall(mixed $classNode, string $methodName, ?string $namespacePhp): ?MethodDecl
	{
		if (!is_object($classNode) || ($classNode->kind ?? null) !== AstKind::NAME) {
			return null;
		}

		$phpClass = (string) ($classNode->children['name'] ?? '');
		$flags = (int) ($classNode->flags ?? 0);
		$resolvedClass = $this->nameRegistry->resolveClass($phpClass, $flags, $namespacePhp) ?? ltrim($phpClass, '\\');
		return $this->methodDecls[$resolvedClass . '::' . $methodName] ?? null;
	}

	private function lookupMethodDeclByCurrentClass(string $methodName, ?string $namespacePhp): ?MethodDecl
	{
		if ($this->currentClassName === null) {
			return null;
		}

		$qualifiedClass = $namespacePhp !== null && $namespacePhp !== ''
			? $namespacePhp . '\\' . $this->currentClassName
			: $this->currentClassName;

		return $this->methodDecls[$qualifiedClass . '::' . $methodName] ?? $this->methodDecls[$this->currentClassName . '::' . $methodName] ?? null;
	}

	private function qualifyClassNameForLookup(string $className, ?string $namespacePhp): string
	{
		$trimmed = ltrim($className, '\\');
		if ($trimmed === '') {
			return $className;
		}
		if (str_contains($trimmed, '\\')) {
			return $trimmed;
		}
		return $namespacePhp !== null && $namespacePhp !== ''
			? $namespacePhp . '\\' . $trimmed
			: $trimmed;
	}

	private function resolveClassDeclKey(string $phpClass, ?string $namespacePhp): ?string
	{
		$resolved = $this->nameRegistry->resolveClass($phpClass, 0, $namespacePhp);
		if ($resolved !== null && isset($this->classDecls[$resolved])) {
			return $resolved;
		}

		$candidates = [];
		$trimmed = ltrim($phpClass, '\\');
		if ($trimmed !== '') {
			$candidates[] = $trimmed;
		}
		if ($namespacePhp !== null && $namespacePhp !== '' && $trimmed !== '' && !str_contains($trimmed, '\\')) {
			$candidates[] = $namespacePhp . '\\' . $trimmed;
		}

		foreach ($candidates as $candidate) {
			if (isset($this->classDecls[$candidate])) {
				return $candidate;
			}
		}

		return null;
	}

	private function namespaceFromQualifiedClassKey(string $qualifiedClass): ?string
	{
		$pos = strrpos($qualifiedClass, '\\');
		if ($pos === false) {
			return null;
		}
		return substr($qualifiedClass, 0, $pos);
	}

	private function resolveMethodDeclInClassHierarchy(string $classKey, string $methodName): ?MethodDecl
	{
		$seen = [];
		$currentKey = $classKey;
		while ($currentKey !== '' && !isset($seen[$currentKey])) {
			$seen[$currentKey] = true;
			if (isset($this->methodDecls[$currentKey . '::' . $methodName])) {
				return $this->methodDecls[$currentKey . '::' . $methodName];
			}
			$classDecl = $this->classDecls[$currentKey] ?? null;
			if (!$classDecl instanceof ClassDecl || $classDecl->parentClass === null) {
				break;
			}
			$currentKey = $this->resolveClassDeclKey($classDecl->parentClass, $this->namespaceFromQualifiedClassKey($currentKey)) ?? '';
		}
		return null;
	}

	private function isClassSameOrDescendantOf(string $candidateKey, string $ancestorKey): bool
	{
		if ($candidateKey === $ancestorKey) {
			return true;
		}

		$seen = [];
		$currentKey = $candidateKey;
		while ($currentKey !== '' && !isset($seen[$currentKey])) {
			$seen[$currentKey] = true;
			$classDecl = $this->classDecls[$currentKey] ?? null;
			if (!$classDecl instanceof ClassDecl || $classDecl->parentClass === null) {
				return false;
			}
			$currentKey = $this->resolveClassDeclKey($classDecl->parentClass, $this->namespaceFromQualifiedClassKey($currentKey)) ?? '';
			if ($currentKey === $ancestorKey) {
				return true;
			}
		}

		return false;
	}

	private function lookupMethodDeclByMappedBaseType(string $baseType, string $methodName): ?MethodDecl
	{
		$classType = $this->extractMappedClassCarrierType($baseType);
		if ($classType === null) {
			return null;
		}

		$normalized = ltrim($classType, ':');
		if (str_starts_with($normalized, 'scpp::')) {
			$normalized = substr($normalized, strlen('scpp::'));
		}

		$phpQualified = str_replace('::', '\\', $normalized);
		$phpShort = basename(str_replace('\\', '/', $phpQualified));
		$candidates = array_values(array_unique(array_filter([$phpQualified, $phpShort], static fn ($v) => $v !== '')));

		foreach ($candidates as $candidate) {
			$methodDecl = $this->methodDecls[$candidate . '::' . $methodName] ?? null;
			if ($methodDecl instanceof MethodDecl) {
				return $methodDecl;
			}
		}

		return null;
	}

	private function renderCallArgsForParams(array $params, array $args, ?string $namespacePhp): string
	{
		$lastParam = $params === [] ? null : $params[array_key_last($params)];
		if (!$lastParam instanceof ParamDecl || !$lastParam->isVariadic) {
			$out = [];
			foreach ($args as $index => $arg) {
				$param = $params[$index] ?? null;
				$out[] = $param instanceof ParamDecl
					? $this->renderArgForParam($param, $arg, $namespacePhp)
					: $this->renderCallArgExpr($arg, $namespacePhp);
			}
			return implode(', ', $out);
		}

		$fixedCount = count($params) - 1;
		$out = [];
		for ($i = 0; $i < $fixedCount; ++$i) {
			if (array_key_exists($i, $args)) {
				$out[] = $this->renderArgForParam($params[$i], $args[$i], $namespacePhp);
			}
		}

		$variadicType = $lastParam->type !== null
			? $this->typeMapper->mapDeclaredType($lastParam->type)
			: '/* ERROR missing-variadic-element-type */';

		$packedValues = [];
		for ($i = $fixedCount; $i < count($args); ++$i) {
			$packedValues[] = $this->renderCallArgExpr($args[$i], $namespacePhp);
		}

		$out[] = 'vector_t<' . $variadicType . '>{' . implode(', ', $packedValues) . '}';
		return implode(', ', $out);
	}



	private function wrapExprForExpectedType(string $renderedExpr, string $exprType, ?string $expectedType): string
	{
		if ($expectedType === null || $expectedType === '' || $expectedType === 'mixed_t') {
			return $renderedExpr;
		}

		// Keep nullable value-parameter normalization runtime-owned when the
		// argument is already a concrete T. The generated C++ can bind through
		// nullable<T>'s value constructor without inventing a synthetic
		// cast<nullable<T>>(...) bridge in the S2S layer.
		if (
			preg_match('/^nullable<(.+)>$/', $expectedType, $nullableMatches) === 1
			&& $exprType === $nullableMatches[1]
		) {
			return $renderedExpr;
		}

		if (
			str_starts_with($expectedType, 'result_or_false<')
			&& $expectedType !== 'result_or_false<bool_t>'
			&& $renderedExpr === 'static_cast<bool_t>(false)'
		) {
			return 'false_sentinel';
		}

		if ($exprType === 'mixed_t') {
			return $this->renderGeneratedCast($expectedType, $renderedExpr);
		}

		if (preg_match('/^int_t<.+>$/', $expectedType) === 1 && str_starts_with($exprType, 'int_t')) {
			return $this->renderGeneratedCast($expectedType, $renderedExpr);
		}

		if ($this->isKnownEnumTypeName($expectedType) && $this->isIntegerRuntimeType($exprType)) {
			throw new \RuntimeException('Implicit assignment from raw integer to enum `' . $expectedType . '` is not supported; use an explicit enum conversion helper.');
		}

		if ($expectedType === 'bool_t' && $exprType === 'bool_t') {
			return 'bool_t(' . $renderedExpr . ')';
		}

		if ($exprType === 'dynamic_t<>') {
			return $expectedType === 'mixed_t' ? ('mixed_t{dynamic_box(' . $renderedExpr . ')}') : $renderedExpr;
		}

		if ($exprType === 'null_t' && $expectedType === 'dynamic_t<>') {
			return $renderedExpr;
		}
		if ($renderedExpr === 'null' && $expectedType === 'dynamic_t<>') {
			return $renderedExpr;
		}

		if ($exprType === 'nullable<' . $expectedType . '>' || $exprType === 'result_or_false<' . $expectedType . '>' || $exprType === 'result_or_bool<' . $expectedType . '>' || $exprType === 'result<' . $expectedType . '>') {
			return $this->renderGeneratedCast($expectedType, $renderedExpr);
		}

		return $renderedExpr;
	}

	private function isFixedWidthIntegerRuntimeType(string $type): bool
	{
		return preg_match('/^int_t<std::(?:u?int(?:8|16|32|64)_t)>$/', $type) === 1;
	}

	private function isIntegerRuntimeType(string $type): bool
	{
		return $type === 'int_t<>' || $this->isFixedWidthIntegerRuntimeType($type);
	}

	private function renderCallArgExpr(mixed $arg, ?string $namespacePhp): string
	{
		if (is_object($arg) && (($arg->kind ?? null) === AstKind::DIM)) {
			return $this->renderLvalueExpr($arg, $namespacePhp);
		}

		return $this->renderExpr($arg, $namespacePhp);
	}
	private function renderArgForParam(ParamDecl $param, mixed $arg, ?string $namespacePhp): string
	{
		// The S2S generator no longer synthesizes typed scalar reference proxy
		// arguments (int_ref/float_ref/bool_ref/string_ref) at call sites.
		// Keep argument rendering direct. Scalar typed by-reference cases are
		// handled by the normalized template path instead of proxy wrappers or
		// sibling mixed_t& bridge overloads.
		if ($param->isReference) {
			$rendered = $this->renderCallArgExpr($arg, $namespacePhp);
			if (!$this->isLvalueCapableExpr($arg, $namespacePhp)) {
				$this->errors[] = 'By-reference argument requires directly stable native-reference-bindable storage in the current safe subset.';
				return '/* unsupported-by-ref-arg */';
			}
			return $this->renderReferenceBindingExpr($arg, $namespacePhp);
		}
		if ($param->type === null) {
			$rendered = $this->renderCallArgExpr($arg, $namespacePhp);
			return $rendered;
		}

		$rendered = is_object($arg) && (($arg->kind ?? null) === AstKind::ARRAY)
			? $this->renderInitializerExpr($arg, $param->type, $namespacePhp)
			: $this->renderCallArgExpr($arg, $namespacePhp);

		if ($this->paramNeedsTemplateNormalization($param)) {
			return $rendered;
		}

		return $this->wrapExprForExpectedType($rendered, $this->inferExprType($arg), $this->typeMapper->mapDeclaredType($param->type));
	}

	private function paramNeedsTemplateNormalization(ParamDecl $param): bool
	{
		if ($this->isSupportedScalarTemplateRefParam($param)) {
			return true;
		}

		if ($param->isReference || $param->isVariadic || count($param->unionTypes) < 2) {
			return false;
		}

		foreach ($param->unionTypes as $unionType) {
			if (!$this->isScalarLikeUnionType($unionType)) {
				return false;
			}
		}

		return true;
	}

	private function isSupportedScalarTemplateRefParam(ParamDecl $param): bool
	{
		if (!$param->isReference || $param->type === null || $param->isVariadic) {
			return false;
		}

		return in_array($this->typeMapper->mapDeclaredType($param->type), ['int_t<>', 'float_t', 'bool_t', 'string_t'], true);
	}

	private function isScalarLikeUnionType(string $phpType): bool
	{
		$normalized = trim($phpType);
		if ($normalized === '') {
			return false;
		}

		if (str_starts_with($normalized, '?')) {
			$normalized = substr($normalized, 1);
		}

		return in_array($normalized, ['null', 'bool', 'int', 'float', 'string'], true);
	}


	private function validatePhpFile(PhpFile $file): void
	{
		foreach ($file->namespaces as $namespace) {
			foreach ($namespace->statements as $statement) {
				if ($statement->kind === 'static_var') {
					$this->errors[] = 'Namespace-scope static variable is rejected in namespace ' . $namespace->name . ' at line ' . $statement->line . '.';
				}
			}
		}

		$executingNamespaces = [];
		foreach ($file->namespaces as $namespace) {
			if ($namespace->statements !== []) {
				$executingNamespaces[] = $namespace->name;
			}
		}
		$executingNamespaces = array_values(array_unique($executingNamespaces));
		$execCount = count($executingNamespaces);
		for ($i = 0; $i < $execCount; $i++) {
			for ($j = $i + 1; $j < $execCount; $j++) {
				$left = $executingNamespaces[$i];
				$right = $executingNamespaces[$j];
				if (str_starts_with($right, $left . '\\') || str_starts_with($left, $right . '\\')) {
					$this->errors[] = 'Nested parent/child execution conflict is rejected between namespaces ' . $left . ' and ' . $right . '.';
				}
			}
		}

		$previousDeclaredLocals = $this->declaredLocals;
		$previousDeclaredLocalTypes = $this->declaredLocalTypes;
		$this->seedSyntheticMainCliLocals();
		$this->validateStatementList($file->rootStatements, null);
		$this->declaredLocals = $previousDeclaredLocals;
		$this->declaredLocalTypes = $previousDeclaredLocalTypes;
		foreach ($file->functions as $function) {
			$this->validateFunctionLikeParameters($function->params, 'function ' . $function->name);
			$this->validateReferenceRulesForFunctionLike($function->params, $function->statements, $function->returnsByReference, 'function ' . $function->name, null);
			$this->validateStatementList($function->statements, null);
		}
		foreach ($file->classes as $class) {
			$this->validateClassDeclaration($class);
			foreach ($class->properties as $property) {
				$this->validatePropertyDeclaration($class, $property);
			}
			$prevClassName = $this->currentClassName;
			$this->currentClassName = $class->name;
			foreach ($class->methods as $method) {
				$this->validateFunctionLikeParameters($method->params, 'method ' . $class->name . '::' . $method->name);
				$this->validateReferenceRulesForFunctionLike($method->params, $method->statements, $method->returnsByReference, 'method ' . $class->name . '::' . $method->name, null);
				$this->validateStatementList($method->statements, null);
			}
			$this->currentClassName = $prevClassName;
		}
		foreach ($file->namespaces as $namespace) {
			$previousDeclaredLocals = $this->declaredLocals;
			$previousDeclaredLocalTypes = $this->declaredLocalTypes;
			$this->seedSyntheticMainCliLocals();
			$this->validateStatementList($namespace->statements, $namespace->name);
			$this->declaredLocals = $previousDeclaredLocals;
			$this->declaredLocalTypes = $previousDeclaredLocalTypes;
			foreach ($namespace->functions as $function) {
				$this->validateFunctionLikeParameters($function->params, 'function ' . $function->name);
				$this->validateReferenceRulesForFunctionLike($function->params, $function->statements, $function->returnsByReference, 'function ' . $function->name, $namespace->name);
				$this->validateStatementList($function->statements, $namespace->name);
			}
			foreach ($namespace->classes as $class) {
				$this->validateClassDeclaration($class);
				foreach ($class->properties as $property) {
					$this->validatePropertyDeclaration($class, $property);
				}
				$prevClassName = $this->currentClassName;
				$this->currentClassName = $class->name;
				foreach ($class->methods as $method) {
					$this->validateFunctionLikeParameters($method->params, 'method ' . $class->name . '::' . $method->name);
					$this->validateReferenceRulesForFunctionLike($method->params, $method->statements, $method->returnsByReference, 'method ' . $class->name . '::' . $method->name, $namespace->name);
					$this->validateStatementList($method->statements, $namespace->name);
				}
				$this->currentClassName = $prevClassName;
			}
		}
	}

	private function validateClassDeclaration(ClassDecl $class): void
	{
		if ($class->isStruct) {
			$this->validateStructDeclaration($class);
			return;
		}
		if ($class->isUnion) {
			$this->validateUnionDeclaration($class);
			return;
		}
		if ($class->isEnum) {
			$this->validateEnumDeclaration($class);
		}
	}

	private function validateStructDeclaration(ClassDecl $class): void
	{
		if ($class->parentClass !== null) {
			$this->errors[] = 'Struct ' . $class->name . ' cannot extend another class at line ' . $class->line . '.';
		}
		if ($class->interfaces !== []) {
			$this->errors[] = 'Struct ' . $class->name . ' cannot implement interfaces at line ' . $class->line . '.';
		}
		if ($class->constants !== []) {
			$this->errors[] = 'Struct ' . $class->name . ' cannot declare constants in the first struct slice at line ' . $class->line . '.';
		}
		if ($class->methods !== []) {
			$this->errors[] = 'Struct ' . $class->name . ' cannot declare methods in the first struct slice at line ' . $class->line . '.';
		}
		if ($class->isAbstract || $class->isInterface) {
			$this->errors[] = 'Struct ' . $class->name . ' must be a concrete value declaration at line ' . $class->line . '.';
		}
		foreach ($class->properties as $property) {
			if ($property->visibility !== 'public') {
				$this->errors[] = 'Struct field ' . $class->name . '::$' . $property->name . ' must be public at line ' . $property->line . '.';
			}
			if ($property->isStatic) {
				$this->errors[] = 'Struct field ' . $class->name . '::$' . $property->name . ' cannot be static at line ' . $property->line . '.';
			}
			if ($property->type === null) {
				$this->errors[] = 'Struct field ' . $class->name . '::$' . $property->name . ' requires an explicit first-slice field type at line ' . $property->line . '.';
				continue;
			}
			if (!$this->isFirstSliceStructFieldType($property->type)) {
				$this->errors[] = 'Struct field ' . $class->name . '::$' . $property->name . ' uses unsupported first-slice field type ' . $property->type . ' at line ' . $property->line . '.';
			}
		}
	}

	private function validateEnumDeclaration(ClassDecl $class): void
	{
		if ($class->enumBackingType === null) {
			return;
		}
		$range = $this->enumBackingRange($class->enumBackingType);
		if ($range === null) {
			return;
		}
		foreach ($class->enumCases as $case) {
			try {
				$value = $this->enumCaseIntValue($case);
			} catch (\RuntimeException $e) {
				$this->errors[] = $e->getMessage() . ' at line ' . $case->line . '.';
				continue;
			}
			if ($value < $range[0] || $value > $range[1]) {
				$this->errors[] = 'Enum case ' . $class->name . '::' . $case->name . ' value ' . $value . ' is outside backing type ' . $class->enumBackingType . ' at line ' . $case->line . '.';
			}
		}
	}

	private function isFirstSliceStructFieldType(string $type): bool
	{
		$normalized = trim($type);
		$lower = strtolower($normalized);
		if (in_array($lower, ['bool', 'int8', 'int16', 'int32', 'int64', 'uint8', 'byte', 'uint16', 'uint32', 'uint64'], true)) {
			return true;
		}
		$kind = $this->typeMapper->declaredTypeKind($normalized);
		if (in_array($kind, ['enum', 'struct', 'union'], true)) {
			return true;
		}
		if (preg_match('/^(vector|vector_t|hash|hash_t|fixed_array|fixed_array_t)\s*<(.+)>$/', $normalized, $matches) === 1) {
			$args = $this->splitTopLevelTypeArgs($matches[2]);
			if ($args === []) {
				return false;
			}
			if (in_array(strtolower($matches[1]), ['fixed_array', 'fixed_array_t'], true) && count($args) !== 2) {
				return false;
			}
			if (in_array(strtolower($matches[1]), ['vector', 'vector_t'], true) && count($args) !== 1) {
				return false;
			}
			if (in_array(strtolower($matches[1]), ['hash', 'hash_t'], true) && (count($args) < 1 || count($args) > 2)) {
				return false;
			}
			return $this->isFirstSliceStructFieldType($args[0]);
		}
		return false;
	}

	private function validateUnionDeclaration(ClassDecl $class): void
	{
		if ($class->parentClass !== null) {
			$this->errors[] = 'Union ' . $class->name . ' cannot extend another class at line ' . $class->line . '.';
		}
		if ($class->interfaces !== []) {
			$this->errors[] = 'Union ' . $class->name . ' cannot implement interfaces at line ' . $class->line . '.';
		}
		if ($class->constants !== []) {
			$this->errors[] = 'Union ' . $class->name . ' cannot declare constants in the first union slice at line ' . $class->line . '.';
		}
		if ($class->methods !== []) {
			$this->errors[] = 'Union ' . $class->name . ' cannot declare methods in the first union slice at line ' . $class->line . '.';
		}
		if ($class->isAbstract || $class->isInterface) {
			$this->errors[] = 'Union ' . $class->name . ' must be a concrete value declaration at line ' . $class->line . '.';
		}
		foreach ($class->properties as $property) {
			if ($property->visibility !== 'public') {
				$this->errors[] = 'Union field ' . $class->name . '::$' . $property->name . ' must be public at line ' . $property->line . '.';
			}
			if ($property->isStatic) {
				$this->errors[] = 'Union field ' . $class->name . '::$' . $property->name . ' cannot be static at line ' . $property->line . '.';
			}
			if ($property->hasDefault) {
				$this->errors[] = 'Union field ' . $class->name . '::$' . $property->name . ' cannot declare a default initializer in the first union slice at line ' . $property->line . '.';
			}
			if ($property->type === null) {
				$this->errors[] = 'Union field ' . $class->name . '::$' . $property->name . ' requires an explicit first-slice payload type at line ' . $property->line . '.';
				continue;
			}
			if (!$this->isFirstSliceUnionFieldType($property->type)) {
				$this->errors[] = 'Union field ' . $class->name . '::$' . $property->name . ' uses unsupported first-slice payload type ' . $property->type . ' at line ' . $property->line . '.';
			}
		}
	}

	private function isFirstSliceUnionFieldType(string $type): bool
	{
		return $this->isFirstSliceUnionFieldTypeInner($type, []);
	}

	/** @param array<string,bool> $seenStructs */
	private function isFirstSliceUnionFieldTypeInner(string $type, array $seenStructs): bool
	{
		$normalized = trim($type);
		$lower = strtolower($normalized);
		if (in_array($lower, ['bool', 'int8', 'int16', 'int32', 'int64', 'uint8', 'byte', 'uint16', 'uint32', 'uint64'], true)) {
			return true;
		}
		$kind = $this->typeMapper->declaredTypeKind($normalized);
		if ($kind === 'enum') {
			return true;
		}
		if ($kind !== 'struct') {
			return false;
		}
		$struct = $this->resolveClassDeclByTypeName($normalized);
		if (!$struct instanceof ClassDecl) {
			return true;
		}
		$key = spl_object_id($struct);
		if (isset($seenStructs[(string) $key])) {
			return true;
		}
		$seenStructs[(string) $key] = true;
		if (!$struct->isStruct || $struct->parentClass !== null || $struct->interfaces !== [] || $struct->constants !== [] || $struct->methods !== [] || $struct->isAbstract || $struct->isInterface) {
			return false;
		}
		foreach ($struct->properties as $property) {
			if ($property->visibility !== 'public' || $property->isStatic || $property->type === null) {
				return false;
			}
			if (!$this->isFirstSliceUnionFieldTypeInner($property->type, $seenStructs)) {
				return false;
			}
		}
		return true;
	}

	private function resolveClassDeclByTypeName(string $type): ?ClassDecl
	{
		$trimmed = ltrim(trim($type), '\\');
		if (isset($this->classDecls[$trimmed])) {
			return $this->classDecls[$trimmed];
		}
		$short = basename(str_replace('\\', '/', $trimmed));
		return $this->classDecls[$short] ?? null;
	}

	/** @return list<string> */
	private function splitTopLevelTypeArgs(string $args): array
	{
		$out = [];
		$current = '';
		$depth = 0;
		$length = strlen($args);
		for ($i = 0; $i < $length; $i++) {
			$ch = $args[$i];
			if ($ch === '<') {
				$depth++;
				$current .= $ch;
				continue;
			}
			if ($ch === '>') {
				$depth--;
				$current .= $ch;
				continue;
			}
			if ($ch === ',' && $depth === 0) {
				$trimmed = trim($current);
				if ($trimmed !== '') {
					$out[] = $trimmed;
				}
				$current = '';
				continue;
			}
			$current .= $ch;
		}
		$trimmed = trim($current);
		if ($trimmed !== '') {
			$out[] = $trimmed;
		}
		return $out;
	}

	/** @param list<ParamDecl> $params */
	private function validateFunctionLikeParameters(array $params, string $owner): void
	{
		foreach ($params as $param) {
			if ($param->nativeType !== null && $param->docType !== null) {
				$this->errors[] = 'Conflicting parameter type sources for ' . $owner . '::$' . $param->name . ' at line ' . $param->line . ': use either a native PHP type or a doc-comment type, not both.';
				continue;
			}
			if ($param->type === null) {
				$this->errors[] = 'Missing explicit parameter type for ' . $owner . '::$' . $param->name . ' at line ' . $param->line . '.';
			}
		}
	}


	/** @param list<ParamDecl> $params */
	/** @param list<Statement> $statements */
	private function validateReferenceRulesForFunctionLike(array $params, array $statements, bool $returnsByReference, string $owner, ?string $namespacePhp): void
	{
		$refBindings = [];
		$refReturnAliasOwners = [];
		$stableRoots = $this->buildStableReferenceRootsForFunctionLike($params);
		if ($returnsByReference) {
			$this->warnings[] = ucfirst($owner) . ' returns by reference. Return-by-reference is not recommended and only partially supported in Prism++.';
		}
		$this->validateReferenceRulesInStatements($statements, $returnsByReference, $owner, $namespacePhp, false, $refBindings, $refReturnAliasOwners, $stableRoots);
		if ($returnsByReference) {
			$returnCount = $this->countReturnStatements($statements);
			if ($returnCount > 1) {
				$this->errors[] = ucfirst($owner) . ' returning by reference must use a single return statement in the current subset.';
			}
		}
	}

	/**
	 * @param list<Statement> $statements
	 * @param array<string, string> $refBindings
	 */
	private function validateReferenceRulesInStatements(array $statements, bool $returnsByReference, string $owner, ?string $namespacePhp, bool $insideControlFlow, array &$refBindings, array &$refReturnAliasOwners, array $stableRoots): void
	{
		foreach ($statements as $statement) {
			if ($statement->kind === 'assign_ref') {
				$name = $this->extractSimpleVarName($statement->payload['var'] ?? null);
				if ($insideControlFlow) {
					$this->errors[] = 'Conditional or loop-scoped reference binding is not supported for ' . $owner . ' at line ' . $statement->line . '.';
				}
				$expr = $statement->payload['expr'] ?? null;
				if ($name !== null) {
					$refBindings[$name] = $this->classifyReferenceBindingSource($expr);
					if ($this->isStableAliasableReferenceExpr($expr, $stableRoots, $namespacePhp)) {
						$stableRoots[$name] = true;
					}
					$rootVars = $this->extractSimpleVarArgsFromByRefCall($expr, $namespacePhp);
					if ($rootVars !== []) {
						$refReturnAliasOwners[$name] = $rootVars;
					}
				}
			}
			if ($statement->kind === 'assign') {
				$copiedFrom = $this->extractSimpleVarName($statement->payload['expr'] ?? null);
				if ($copiedFrom !== null) {
					foreach ($refReturnAliasOwners as $aliasName => $ownerVars) {
						if (in_array($copiedFrom, $ownerVars, true)) {
							$this->warnings[] = 'Copy-after-alias warning in ' . $owner . ' at line ' . $statement->line . ': $' . $aliasName . ' is bound from a by-reference return rooted in $' . $copiedFrom . ', and copying $' . $copiedFrom . ' may not preserve PHP alias semantics in Prism++.';
						}
					}
				}
			}
			if ($statement->kind === 'return' && $returnsByReference) {
				$expr = $statement->payload;
				if (!$this->isStableAliasableReferenceExpr($expr, $stableRoots, $namespacePhp)) {
					$this->errors[] = ucfirst($owner) . ' returning by reference requires a stable aliasable expression rooted in a by-reference parameter, $this, or another reference derived from stable storage at line ' . $statement->line . '.';
				}
			}
			if ($statement->kind === 'if') {
				foreach ($statement->payload as $branch) {
					$branchBindings = $refBindings;
					$this->validateReferenceRulesInStatements($branch['stmts'] ?? [], $returnsByReference, $owner, $namespacePhp, true, $branchBindings, $refReturnAliasOwners, $stableRoots);
				}
				continue;
			}
			if ($statement->kind === 'while' || $statement->kind === 'do_while' || $statement->kind === 'for' || $statement->kind === 'foreach') {
				$nested = $refBindings;
				$this->validateReferenceRulesInStatements($statement->payload['stmts'] ?? [], $returnsByReference, $owner, $namespacePhp, true, $nested, $refReturnAliasOwners, $stableRoots);
				continue;
			}
			if ($statement->kind === 'switch') {
				foreach (($statement->payload['cases'] ?? []) as $case) {
					$caseBindings = $refBindings;
					$this->validateReferenceRulesInStatements($case['stmts'] ?? [], $returnsByReference, $owner, $namespacePhp, true, $caseBindings, $refReturnAliasOwners, $stableRoots);
				}
			}
		}
	}

	/** @param list<Statement> $statements */
	private function countReturnStatements(array $statements): int
	{
		$count = 0;
		foreach ($statements as $statement) {
			if ($statement->kind === 'return') {
				++$count;
				continue;
			}
			if ($statement->kind === 'if') {
				foreach ($statement->payload as $branch) {
					$count += $this->countReturnStatements($branch['stmts'] ?? []);
				}
				continue;
			}
			if ($statement->kind === 'while' || $statement->kind === 'do_while' || $statement->kind === 'for' || $statement->kind === 'foreach') {
				$count += $this->countReturnStatements($statement->payload['stmts'] ?? []);
				continue;
			}
			if ($statement->kind === 'switch') {
				foreach (($statement->payload['cases'] ?? []) as $case) {
					$count += $this->countReturnStatements($case['stmts'] ?? []);
				}
			}
		}
		return $count;
	}


	/** @param list<ParamDecl> $params
	 *  @return array<string, bool>
	 */
	private function buildStableReferenceRootsForFunctionLike(array $params): array
	{
		$roots = [];
		foreach ($params as $param) {
			if ($param->isReference) {
				$roots[$param->name] = true;
			}
		}
		if ($this->currentClassName !== null) {
			$roots['this'] = true;
		}
		return $roots;
	}

	/** @param array<string, bool> $stableRoots */
	private function isStableAliasableReferenceExpr(mixed $expr, array $stableRoots, ?string $namespacePhp): bool
	{
		if (!is_object($expr)) {
			return false;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::VAR) {
			$name = (string) ($expr->children['name'] ?? '');
			return isset($stableRoots[$name]);
		}

		if ($kind === AstKind::PROP) {
			$baseExpr = $expr->children['expr'] ?? null;
			$baseName = $this->extractSimpleVarName($baseExpr);
			if ($baseName === 'this') {
				return true;
			}
		}

		if ($kind === AstKind::CALL || $kind === AstKind::STATIC_CALL || $kind === AstKind::METHOD_CALL || $kind === AstKind::NULLSAFE_PROP || $kind === AstKind::STATIC_PROP) {
			return false;
		}

		return false;
	}

	private function classifyReferenceBindingSource(mixed $expr): string
	{
		if ($this->containsReferenceSlotExpr($expr)) {
			return 'slot';
		}
		return 'other';
	}

	/** @return list<string> */
	private function extractSimpleVarArgsFromByRefCall(mixed $expr, ?string $namespacePhp): array
	{
		if (!is_object($expr)) {
			return [];
		}

		$kind = $expr->kind ?? null;
		if ($kind !== AstKind::CALL) {
			return [];
		}

		$decl = $this->lookupFunctionDeclByCall($expr->children['expr'] ?? null, $namespacePhp);
		if ($decl === null || !$decl->returnsByReference) {
			return [];
		}

		$args = $expr->children['args']->children ?? [];
		$out = [];
		foreach ($args as $arg) {
			$name = $this->extractSimpleVarName($arg);
			if ($name !== null) {
				$out[] = $name;
			}
		}

		return array_values(array_unique($out));
	}

	private function isUnsupportedDirectReferenceReturnExpr(mixed $expr): bool
	{
		return $this->containsReferenceSlotExpr($expr) && !$this->isSimpleDirectReferenceReturnExpr($expr);
	}

	private function containsReferenceSlotExpr(mixed $expr): bool
	{
		if (!is_object($expr)) {
			return false;
		}
		$kind = $expr->kind ?? null;
		if (in_array($kind, [AstKind::DIM, AstKind::PROP, AstKind::NULLSAFE_PROP, AstKind::STATIC_PROP], true)) {
			return true;
		}
		foreach ($this->childNodesOf($expr) as $child) {
			if ($this->containsReferenceSlotExpr($child)) {
				return true;
			}
		}
		return false;
	}

	private function isSimpleDirectReferenceReturnExpr(mixed $expr): bool
	{
		if (!is_object($expr)) {
			return false;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::DIM || $kind === AstKind::PROP) {
			$baseExpr = $expr->children['expr'] ?? null;
			if ($this->extractSimpleVarName($baseExpr) !== null) {
				return true;
			}
			return $this->isSimpleDirectReferenceReturnExpr($baseExpr);
		}

		return false;
	}

	private function validatePropertyDeclaration(ClassDecl $class, PropertyDecl $property): void
	{
		if ($property->nativeType !== null && $property->docType !== null) {
			$this->errors[] = 'Conflicting property type sources for ' . $class->name . '::$' . $property->name . ' at line ' . $property->line . ': use either a native PHP type or a doc-comment type, not both.';
			return;
		}
		if ($property->type === null && !$property->hasDefault) {
			$this->errors[] = 'Missing explicit property type for ' . $class->name . '::$' . $property->name . ' at line ' . $property->line . '. Add a type or a default value so the generator can infer one.';
		}
	}

	/** @param list<Statement> $statements */
	private function validateStatementList(mixed $statements, ?string $namespacePhp): void
	{
		if (!is_array($statements)) {
			$statements = [];
		}
		$localKinds = [];
		foreach ($this->declaredLocalTypes as $name => $storedType) {
			$localKinds[$name] = $this->validationKindForStoredLocalType($storedType);
		}
		foreach ($statements as $statement) {
			if ($statement->kind === 'assign') {
				$name = $this->extractSimpleVarName($statement->payload['var'] ?? null);
				if ($name !== null) {
					$key = $statement->line . ':' . $name;
					$typed = $this->localTypeComments[$key] ?? null;
					if (is_string($typed) && $typed !== '') {
						$storedType = $this->normalizeStoredLocalType($typed);
						$this->declaredLocalTypes[$name] = $storedType;
						$localKinds[$name] = $this->validationKindForStoredLocalType($storedType);
					} elseif (isset($this->declaredLocalTypes[$name])) {
						$localKinds[$name] = $this->validationKindForStoredLocalType($this->declaredLocalTypes[$name]);
					} else {
						$localKinds[$name] = $this->inferValidationKind($statement->payload['expr'] ?? null, $localKinds);
					}
				}
				$this->validateExprTree($statement->payload['expr'] ?? null, $namespacePhp, $localKinds, $statement->line);
				continue;
			}
			if ($statement->kind === 'assign_op') {
				$this->validateExprTree($statement->payload['expr'] ?? null, $namespacePhp, $localKinds, $statement->line);
				continue;
			}
			if ($statement->kind === 'assign_ref') {
				$name = $this->extractSimpleVarName($statement->payload['var'] ?? null);
				if ($name !== null) {
					$localKinds[$name] = 'unknown';
				}
				$this->validateExprTree($statement->payload['expr'] ?? null, $namespacePhp, $localKinds, $statement->line);
				continue;
			}
			if ($statement->kind === 'expr' || $statement->kind === 'return' || $statement->kind === 'echo' || $statement->kind === 'unset') {
				$this->validateExprTree($statement->payload, $namespacePhp, $localKinds, $statement->line);
				continue;
			}
			if ($statement->kind === 'include_or_eval') {
				$this->errors[] = 'Prism++ supports require_once only as a static compile-time include with a literal path in the file prologue at line ' . $statement->line . '.';
				continue;
			}
			if ($statement->kind === 'if') {
				foreach ($statement->payload as $branch) {
					$this->validateExprTree($branch['cond'] ?? null, $namespacePhp, $localKinds, (int) ($branch['line'] ?? $statement->line));
					$this->validateStatementList($branch['stmts'] ?? [], $namespacePhp);
				}
				continue;
			}
			if ($statement->kind === 'while' || $statement->kind === 'do_while') {
				$this->validateExprTree($statement->payload['cond'] ?? null, $namespacePhp, $localKinds, $statement->line);
				$this->validateStatementList($statement->payload['stmts'] ?? [], $namespacePhp);
				continue;
			}
			if ($statement->kind === 'for') {
				foreach (($statement->payload['init'] ?? []) as $expr) {
					$this->validateExprTree($expr, $namespacePhp, $localKinds, $statement->line);
				}
				foreach (($statement->payload['cond'] ?? []) as $expr) {
					$this->validateExprTree($expr, $namespacePhp, $localKinds, $statement->line);
				}
				foreach (($statement->payload['loop'] ?? []) as $expr) {
					$this->validateExprTree($expr, $namespacePhp, $localKinds, $statement->line);
				}
				$this->validateStatementList($statement->payload['stmts'] ?? [], $namespacePhp);
				continue;
			}
			if ($statement->kind === 'foreach') {
				$this->validateExprTree($statement->payload['expr'] ?? null, $namespacePhp, $localKinds, $statement->line);
				$this->validateStatementList($statement->payload['stmts'] ?? [], $namespacePhp);
				continue;
			}
			if ($statement->kind === 'switch') {
				$this->validateExprTree($statement->payload['cond'] ?? null, $namespacePhp, $localKinds, $statement->line);
				foreach (($statement->payload['cases'] ?? []) as $case) {
					$this->validateExprTree($case['cond'] ?? null, $namespacePhp, $localKinds, $statement->line);
					$this->validateStatementList($case['stmts'] ?? [], $namespacePhp);
				}
			}
		}
	}

	private function validationKindForStoredLocalType(string $storedType): string
	{
		$mapped = trim($this->typeMapper->mapDeclaredType($storedType));
		return match (true) {
			$mapped === 'mixed_t' => 'mixed',
			$mapped === 'string_t' => 'string',
			$mapped === 'bool_t' => 'bool',
			str_starts_with($mapped, 'int_t<'), $mapped === 'float_t' => 'number',
			str_starts_with($mapped, 'nullable<'),
			str_starts_with($mapped, 'result<'),
			str_starts_with($mapped, 'result_or_false<'),
			str_starts_with($mapped, 'result_or_bool<') => 'wrapper',
			default => 'unknown',
		};
	}

	/** @param array<string, string> $localKinds */
	private function validateExprTree(mixed $expr, ?string $namespacePhp, array $localKinds, int $line): void
	{
		if (!is_object($expr)) {
			return;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::NEW) {
			$classNode = $expr->children['class'] ?? null;
			if (is_object($classNode) && (($classNode->kind ?? null) === AstKind::NAME)) {
				$rawName = (string) ($classNode->children['name'] ?? '');
				if ($namespacePhp !== null && $namespacePhp !== '' && $rawName !== '' && !str_starts_with($rawName, '\\') && str_starts_with($rawName, $namespacePhp . '\\')) {
					$this->errors[] = 'Qualified self-reference construction is rejected at line ' . $line . ': use ' . substr($rawName, strlen($namespacePhp) + 1) . ' or \\' . $rawName . '.';
				}
			}
		}

		if ($kind === AstKind::BINARY_OP) {
			$flags = (int) ($expr->flags ?? 0);
			if (in_array($flags, [AstKind::PLUS, AstKind::MINUS, AstKind::MUL, 4, 5], true)) {
				$leftKind = $this->inferValidationKind($expr->children['left'] ?? null, $localKinds);
				$rightKind = $this->inferValidationKind($expr->children['right'] ?? null, $localKinds);
				$hasMixedOperand = ($leftKind === 'mixed' || $rightKind === 'mixed');
				if (($leftKind === 'string' || $rightKind === 'string') && !$hasMixedOperand) {
					$this->errors[] = 'String used in arithmetic is rejected at line ' . $line . '.';
				}
			}
		}

		foreach ($this->childNodesOf($expr) as $child) {
			$this->validateExprTree($child, $namespacePhp, $localKinds, $line);
		}
	}

	/** @param array<string, string> $localKinds */
	private function inferValidationKind(mixed $expr, array $localKinds): string
	{
		if (is_string($expr)) {
			return 'string';
		}
		if (is_int($expr) || is_float($expr)) {
			return 'number';
		}
		if (!is_object($expr)) {
			return 'unknown';
		}
		$kind = $expr->kind ?? null;
		if ($kind === AstKind::VAR) {
			$name = (string) ($expr->children['name'] ?? '');
			return $localKinds[$name] ?? 'unknown';
		}
		if ($kind === AstKind::CONST) {
			$name = strtolower((string) ($expr->children['name']->children['name'] ?? ''));
			if ($name === 'true' || $name === 'false') {
				return 'bool';
			}
			if ($name === 'null') {
				return 'null';
			}
		}
		if ($kind === AstKind::CAST) {
			$flags = (int) ($expr->flags ?? 0);
			if ($flags === AstKind::TYPE_STRING) {
				return 'string';
			}
			if ($flags === AstKind::TYPE_LONG || $flags === AstKind::TYPE_DOUBLE) {
				return 'number';
			}
		}
		if ($kind === AstKind::ENCAPS_LIST) {
			return 'string';
		}
		if ($kind === AstKind::BINARY_OP) {
			$flags = (int) ($expr->flags ?? 0);
			if ($flags === AstKind::BINARY_CONCAT) {
				return 'string';
			}
			if (in_array($flags, [AstKind::PLUS, AstKind::MINUS, AstKind::MUL, 4, 5], true)) {
				return 'number';
			}
		}
		return 'unknown';
	}

	/** @return list<mixed> */
	private function childNodesOf(mixed $node): array
	{
		if (!is_object($node) || !isset($node->children) || !is_array($node->children)) {
			return [];
		}
		$out = [];
		foreach ($node->children as $child) {
			if (is_object($child)) {
				$out[] = $child;
				continue;
			}
			if (is_array($child)) {
				foreach ($child as $nested) {
					if (is_object($nested)) {
						$out[] = $nested;
					}
				}
			}
		}
		return $out;
	}

	/** @param list<UseDecl> $uses @param list<ConstantDecl> $constants @param list<ClassDecl> $classes @param list<FunctionDecl> $functions @param list<Statement> $statements */
	private function emitNamespaceBlock(array &$header, array &$source, string $namespaceCpp, ?string $namespacePhp, array $uses, array $constants, array $classes, array $functions, array $statements, ?string $syntheticMainName = null): void
	{
		$previousNamespacePhp = $this->currentNamespacePhp;
		$this->currentNamespacePhp = $namespacePhp;
		$this->appendHeaderLines($header, $this->code('namespace ' . $namespaceCpp . ' {', 0));
		$this->appendHeaderLines($header, $this->code('', 0));
		$this->appendSourceLines($source, $this->code('namespace ' . $namespaceCpp . ' {', 0));
		$this->appendSourceLines($source, $this->code($this->indent(1) . 'using namespace ::scpp;', 0));
		$this->appendSourceLines($source, $this->code('', 0));

		foreach ($uses as $use) {
			$useLine = $this->renderUseDeclaration($use);

			if ($useLine === null) {
				continue;
			}
			foreach (explode("\n", $useLine) as $line) {
				if ($line === '') {
					continue;
				}
				$this->appendSourceLines($source, $this->code($this->indent(1) . $line, $use->line));
			}
		}
		if ($uses !== []) {
			$this->appendSourceLines($source, $this->code('', 0));
		}
		
		foreach ($constants as $constant) {
			$this->emitConstant($header, $constant, $namespacePhp);
		}
		if ($constants !== []) {
			$this->appendHeaderLines($header, $this->code('', 0));
		}
		foreach ($this->collectNamespaceForwardClassNames($classes, $functions, $namespacePhp) as $className => $classKind) {
			$this->appendHeaderLines($header, $this->code($classKind . ' ' . $className . ';', 0));
		}
		if ($classes !== []) {
			$this->appendHeaderLines($header, $this->code('', 0));
		}

		foreach ($classes as $class) {
			$this->emitClass($header, $source, $class, $namespacePhp);
		}
		foreach ($functions as $function) {
			$this->emitFunction($header, $source, $function, $namespacePhp);
		}
		if ($syntheticMainName !== null) {
			$this->emitNamespaceMain($header, $source, $syntheticMainName, $statements, $namespacePhp);
		}

		$this->appendHeaderLines($header, $this->code('}', 0));
		$this->appendHeaderLines($header, $this->code('', 0));
		$this->appendSourceLines($source, $this->code('}', 0));
		$this->appendSourceLines($source, $this->code('', 0));
		$this->currentNamespacePhp = $previousNamespacePhp;
	}

	/** @param list<ClassDecl> $classes @param list<FunctionDecl> $functions @return array<string,string> */
	private function collectNamespaceForwardClassNames(array $classes, array $functions, ?string $namespacePhp): array
	{
		$out = [];
		foreach ($classes as $class) {
			if (!$class->isEnum) {
				$out[$class->name] = $class->isUnion ? 'union' : ($class->isStruct ? 'struct' : 'class');
			}
			if ($class->parentClass !== null) {
				$this->collectForwardClassNamesFromType($class->parentClass, $out, $namespacePhp);
			}
			foreach ($class->interfaces as $interface) {
				$this->collectForwardClassNamesFromType($interface, $out, $namespacePhp);
			}
			foreach ($class->properties as $property) {
				if ($property->type !== null) {
					$this->collectForwardClassNamesFromType($property->type, $out, $namespacePhp);
				}
			}
			foreach ($class->methods as $method) {
				if ($method->returnType !== null) {
					$this->collectForwardClassNamesFromType($method->returnType, $out, $namespacePhp);
				}
				foreach ($method->params as $param) {
					if ($param->type !== null) {
						$this->collectForwardClassNamesFromType($param->type, $out, $namespacePhp);
					}
				}
			}
		}
		foreach ($functions as $function) {
			if ($function->returnType !== null) {
				$this->collectForwardClassNamesFromType($function->returnType, $out, $namespacePhp);
			}
			foreach ($function->params as $param) {
				if ($param->type !== null) {
					$this->collectForwardClassNamesFromType($param->type, $out, $namespacePhp);
				}
			}
		}

		ksort($out);
		return $out;
	}

	/** @param array<string, string> $out */
	private function collectForwardClassNamesFromType(string $type, array &$out, ?string $namespacePhp): void
	{
		$normalized = trim($this->qualifyDeclaredPhpType($type, $namespacePhp) ?? $type);
		if ($normalized === '') {
			return;
		}
		if (preg_match('/^\d+$/', $normalized) === 1) {
			return;
		}
		if (str_starts_with($normalized, '?')) {
			$this->collectForwardClassNamesFromType(substr($normalized, 1), $out, $namespacePhp);
			return;
		}
		if (preg_match('/^(?:vector|vector_t|fixed_array|fixed_array_t|hash|hash_t|nullable|value|shared|unique|weak|weakref|shared_p|unique_p|weak_p|result_or_false|result_or_bool|result)\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			foreach ($this->typeMapper->splitTopLevelGenericArgs($matches[1]) as $arg) {
				$this->collectForwardClassNamesFromType(trim($arg), $out, $namespacePhp);
			}
			return;
		}
		if (str_starts_with($normalized, 'value ')) {
			$this->collectForwardClassNamesFromType(trim(substr($normalized, strlen('value '))), $out, $namespacePhp);
			return;
		}
		if (str_starts_with($normalized, 'ref ')) {
			$this->collectForwardClassNamesFromType(trim(substr($normalized, strlen('ref '))), $out, $namespacePhp);
			return;
		}
		if (str_contains($normalized, '\\') || str_contains($normalized, '::')) {
			return;
		}
		if (in_array($normalized, ['int', 'int8', 'int16', 'int32', 'int64', 'uint8', 'byte', 'uint16', 'uint32', 'uint64', 'float', 'bool', 'string', 'array', 'mixed', 'dynamic', 'void', 'false', 'null', 'vector', 'vector_t', 'fixed_array', 'fixed_array_t', 'hash', 'hash_t', 'error', 'resource_handle', 'nullable_resource_handle', 'falseable_resource_handle', 'token_buffer', 'string_parts_builder', 'source_buffer', 'byte_span', 'source_line_index', 'source_location', 'int_t', 'int_t<>', 'float_t', 'bool_t', 'string_t', 'mixed_t', 'dynamic_t<>', 'error_t', 'resource_handle_t', 'nullable_resource_handle_t', 'falseable_resource_handle_t', 'token_buffer_t', 'tokenizer::token_buffer_t', 'str::string_parts_builder', 'source::source_buffer', 'source::byte_span', 'source::source_line_index', 'source::source_location'], true)) {
			return;
		}
		if (in_array($this->typeMapper->declaredTypeKind($normalized), ['enum', 'struct', 'union'], true)) {
			return;
		}
		$declaredKind = $this->typeMapper->declaredTypeKind($normalized);
		$out[$normalized] = $declaredKind === 'union' ? 'union' : ($declaredKind === 'struct' ? 'struct' : 'class');
	}

	/** @param list<UseDecl> $uses @return list<string> */
	
	private function renderUseDeclaration(UseDecl $use): ?string
	{
		$name = $use->name;

		if ($name === '') {
			$this->errors[] = 'Empty use import is not supported at line ' . $use->line . '.';
			return null;
		}

		$fq = '::scpp::' . str_replace('\\', '::', ltrim($name, '\\'));

		if ($use->alias === null) {
			return 'using ' . $fq . ';';
		}

		return match ($use->kind) {
			'function' => 'inline constexpr auto ' . $use->alias . ' = ' . $fq . ';',
			'const' => 'inline constexpr auto& ' . $use->alias . ' = ' . $fq . ';',
			default => 'using ' . $use->alias . ' = ' . $fq . ';',
		};
	}

	/**

	 * Emits one lowered constant as an inline namespace-scoped declaration in the header.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function emitConstant(array &$header, ConstantDecl $constant, ?string $namespacePhp): void
	{
		$this->appendHeaderLines($header, $this->code('inline const auto ' . $constant->name . ' = ' . $this->renderExpr($constant->value, $namespacePhp) . ';', $constant->line));
	}

	/**

	 * Emits a class declaration to the header and its method definitions to the source file.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */


	private function enumStorageType(ClassDecl $class): string
	{
		$backingType = $class->enumBackingType !== null ? strtolower($class->enumBackingType) : null;
		$explicitFixedStorage = match ($backingType) {
			'int8' => 'std::int8_t',
			'int16' => 'std::int16_t',
			'int32' => 'std::int32_t',
			'int64' => 'std::int64_t',
			'uint8', 'byte' => 'std::uint8_t',
			'uint16' => 'std::uint16_t',
			'uint32' => 'std::uint32_t',
			'uint64' => 'std::uint64_t',
			default => null,
		};
		if ($explicitFixedStorage !== null) {
			return $explicitFixedStorage;
		}
		if ($backingType !== null && $backingType !== 'int') {
			throw new \RuntimeException('Only unit enums and int/fixed-width int-backed enums are supported in the current enum lowering');
		}

		$minValue = 0;
		$maxValue = max(0, count($class->enumCases) - 1);
		if ($backingType === 'int') {
			$minValue = 0;
			$maxValue = 0;
			foreach ($class->enumCases as $case) {
				$value = $this->enumCaseIntValue($case);
				$minValue = min($minValue, $value);
				$maxValue = max($maxValue, $value);
			}
		}

		if ($minValue >= 0) {
			if ($maxValue <= 0xFF) {
				return 'std::uint8_t';
			}
			if ($maxValue <= 0xFFFF) {
				return 'std::uint16_t';
			}
			if ($maxValue <= 0xFFFFFFFF) {
				return 'std::uint32_t';
			}
			return 'std::uint64_t';
		}

		if ($minValue >= -0x80 && $maxValue <= 0x7F) {
			return 'std::int8_t';
		}
		if ($minValue >= -0x8000 && $maxValue <= 0x7FFF) {
			return 'std::int16_t';
		}
		if ($minValue >= -0x80000000 && $maxValue <= 0x7FFFFFFF) {
			return 'std::int32_t';
		}
		return 'std::int64_t';
	}

	private function enumBackingRange(string $backingType): ?array
	{
		return match (strtolower($backingType)) {
			'int8' => [-128, 127],
			'int16' => [-32768, 32767],
			'int32' => [-2147483648, 2147483647],
			'int64' => [PHP_INT_MIN, PHP_INT_MAX],
			'uint8', 'byte' => [0, 255],
			'uint16' => [0, 65535],
			'uint32' => [0, 4294967295],
			'uint64' => [0, PHP_INT_MAX],
			default => null,
		};
	}

	private function enumBackingRuntimeType(ClassDecl $class): string
	{
		$backingType = $class->enumBackingType !== null ? strtolower($class->enumBackingType) : null;
		return match ($backingType) {
			'int8' => 'int_t<std::int8_t>',
			'int16' => 'int_t<std::int16_t>',
			'int32' => 'int_t<std::int32_t>',
			'int64' => 'int_t<std::int64_t>',
			'uint8', 'byte' => 'int_t<std::uint8_t>',
			'uint16' => 'int_t<std::uint16_t>',
			'uint32' => 'int_t<std::uint32_t>',
			'uint64' => 'int_t<std::uint64_t>',
			default => 'int_t<>',
		};
	}

	private function enumBackingNativeType(ClassDecl $class): string
	{
		$storage = $this->enumStorageType($class);
		return $class->enumBackingType !== null && strtolower($class->enumBackingType) === 'int'
			? 'std::int64_t'
			: $storage;
	}

	private function enumCaseIntValue(ConstantDecl $case): int
	{
		$value = $case->value;
		if (is_int($value)) {
			return $value;
		}
		if (is_object($value) && ($value->kind ?? null) === AstKind::UNARY_OP && (($value->flags ?? null) === AstKind::UNARY_MINUS)) {
			$inner = $value->children['expr'] ?? null;
			if (is_int($inner)) {
				return -$inner;
			}
		}
		throw new \RuntimeException('Only literal int-backed enum case values are supported in the current enum lowering');
	}

	private function renderEnumCaseValue(ConstantDecl $case): string
	{
		return (string) $this->enumCaseIntValue($case);
	}


	/** @param list<ParamDecl> $params @param list<Statement> $statements */
	private function beginFunctionLikeVariableMapping(array $params, array $statements): void
	{
		$this->currentPhpVarToCpp = [];
		$this->currentUsedCppVarNames = [];
		$this->foreachReferenceSlotStack = [];

		$orderedNames = [];
		$originalNames = [];
		foreach ($params as $param) {
			if (!isset($originalNames[$param->name])) {
				$orderedNames[] = $param->name;
				$originalNames[$param->name] = true;
			}
		}
		foreach ($this->collectSimpleVarNamesFromStatements($statements) as $name) {
			if (!isset($originalNames[$name])) {
				$orderedNames[] = $name;
				$originalNames[$name] = true;
			}
		}

		foreach ($orderedNames as $name) {
			if ($name === 'this') {
				$this->currentPhpVarToCpp[$name] = 'this';
				continue;
			}
			$candidate = $name;
			if ($this->isCppReservedIdentifier($name)) {
				$base = $name . '__';
				$candidate = $base;
				$suffix = 1;
				while ((isset($originalNames[$candidate]) && $candidate !== $name) || isset($this->currentUsedCppVarNames[$candidate])) {
					$candidate = $base . $suffix;
					++$suffix;
				}
			}
			$this->currentPhpVarToCpp[$name] = $candidate;
			$this->currentUsedCppVarNames[$candidate] = true;
		}
	}

	private function endFunctionLikeVariableMapping(): void
	{
		$this->currentPhpVarToCpp = [];
		$this->currentUsedCppVarNames = [];
		$this->currentLocalArrayShapes = [];
		$this->foreachReferenceSlotStack = [];
		$this->foreachReferenceSuppressedNamesStack = [];
	}

	private function localCppName(string $phpName): string
	{
		if ($phpName === 'this') {
			return 'this';
		}

		return $this->currentPhpVarToCpp[$phpName] ?? $phpName;
	}

	private function hasForeachReferenceSlotAlias(string $name): bool
	{
		for ($i = count($this->foreachReferenceSlotStack) - 1; $i >= 0; --$i) {
			if (isset($this->foreachReferenceSlotStack[$i][$name])) {
				return true;
			}
		}

		return false;
	}

	private function isForeachReferenceSlotSuppressed(string $name): bool
	{
		for ($i = count($this->foreachReferenceSuppressedNamesStack) - 1; $i >= 0; --$i) {
			if (isset($this->foreachReferenceSuppressedNamesStack[$i][$name])) {
				return true;
			}
		}

		return false;
	}

	private function renderClosureCaptureItem(string $name, bool $byReference): string
	{
		for ($i = count($this->foreachReferenceSlotStack) - 1; $i >= 0; --$i) {
			if (!isset($this->foreachReferenceSlotStack[$i][$name])) {
				continue;
			}

			$slotExpr = $this->foreachReferenceSlotStack[$i][$name];
			if ($byReference) {
				return '&' . $this->localCppName($name) . ' = ' . $slotExpr;
			}

			return $this->localCppName($name) . ' = ' . $slotExpr;
		}

		return $byReference ? '&' . $this->localCppName($name) : $this->localCppName($name);
	}

	private function inferForeachByRefSourceShape(mixed $expr): string
	{
		if (!is_object($expr)) {
			return 'unknown';
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::VAR) {
			$name = $this->extractSimpleVarName($expr);
			if ($name === null) {
				return 'unknown';
			}
			return $this->currentLocalArrayShapes[$name] ?? 'unknown';
		}

		if ($kind !== AstKind::ARRAY) {
			if ($kind === AstKind::CAST && ((int) ($expr->flags ?? 0) === AstKind::TYPE_OBJECT)) {
				return 'non_vector';
			}
			return 'unknown';
		}

		$hasExplicitKey = false;
		foreach (($expr->children ?? []) as $element) {
			if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
				return 'unknown';
			}
			if (($element->children['key'] ?? null) !== null) {
				$hasExplicitKey = true;
				break;
			}
		}

		return $hasExplicitKey ? 'non_vector' : 'vector';
	}

	private function trackAssignedArrayShape(?string $name, mixed $exprNode, ?string $effectiveTyped): void
	{
		if ($name === null) {
			return;
		}

		if ($effectiveTyped !== null) {
			if ($this->mapTypedVectorLocalType($effectiveTyped) !== null) {
				$this->currentLocalArrayShapes[$name] = 'vector';
				return;
			}
			if ($this->mapTypedHashLocalType($effectiveTyped) !== null) {
				$this->currentLocalArrayShapes[$name] = 'non_vector';
				return;
			}
		}

		$shape = $this->inferForeachByRefSourceShape($exprNode);
		if ($shape !== 'unknown') {
			$this->currentLocalArrayShapes[$name] = $shape;
		}
	}

	private function parameterCppName(ParamDecl $param): string
	{
		return $this->localCppName($param->name);
	}

	private function allocateGeneratedLocalName(string $preferredName): string
	{
		$candidate = $preferredName;
		$suffix = 1;
		while (isset($this->currentUsedCppVarNames[$candidate])) {
			$candidate = $preferredName . $suffix;
			++$suffix;
		}
		$this->currentUsedCppVarNames[$candidate] = true;
		return $candidate;
	}

	/** @param list<Statement> $statements @return list<string> */
	private function collectSimpleVarNamesFromStatements(array $statements): array
	{
		$names = [];
		foreach ($statements as $statement) {
			foreach ($this->collectSimpleVarNamesFromExpr($statement->payload) as $name) {
				if (!isset($names[$name])) {
					$names[$name] = true;
				}
			}
		}
		return array_keys($names);
	}

	/** @return list<string> */
	private function collectSimpleVarNamesFromExpr(mixed $expr): array
	{
		$names = [];
		if (is_array($expr)) {
			foreach ($expr as $value) {
				foreach ($this->collectSimpleVarNamesFromExpr($value) as $name) {
					if (!isset($names[$name])) {
						$names[$name] = true;
					}
				}
			}
			return array_keys($names);
		}
		if (!is_object($expr)) {
			return [];
		}
		if (($expr->kind ?? null) === AstKind::VAR) {
			$name = (string) ($expr->children['name'] ?? '');
			if ($name !== '' && $name !== 'this') {
				$names[$name] = true;
			}
		}
		foreach ($this->childNodesOf($expr) as $child) {
			foreach ($this->collectSimpleVarNamesFromExpr($child) as $name) {
				if (!isset($names[$name])) {
					$names[$name] = true;
				}
			}
		}
		return array_keys($names);
	}

	private function isCppReservedIdentifier(string $name): bool
	{
		static $reserved = [
			'alignas' => true, 'alignof' => true, 'and' => true, 'and_eq' => true, 'asm' => true, 'auto' => true,
			'bitand' => true, 'bitor' => true, 'bool' => true, 'break' => true, 'case' => true, 'catch' => true,
			'char' => true, 'char8_t' => true, 'char16_t' => true, 'char32_t' => true, 'class' => true,
			'compl' => true, 'concept' => true, 'const' => true, 'consteval' => true, 'constexpr' => true,
			'constinit' => true, 'const_cast' => true, 'continue' => true, 'co_await' => true, 'co_return' => true,
			'co_yield' => true, 'decltype' => true, 'default' => true, 'delete' => true, 'do' => true,
			'double' => true, 'dynamic_cast' => true, 'else' => true, 'enum' => true, 'explicit' => true,
			'export' => true, 'extern' => true, 'false' => true, 'float' => true, 'for' => true, 'friend' => true,
			'goto' => true, 'if' => true, 'inline' => true, 'int' => true, 'long' => true, 'mutable' => true,
			'namespace' => true, 'new' => true, 'noexcept' => true, 'not' => true, 'not_eq' => true,
			'nullptr' => true, 'operator' => true, 'or' => true, 'or_eq' => true, 'private' => true,
			'protected' => true, 'public' => true, 'register' => true, 'reinterpret_cast' => true,
			'requires' => true, 'return' => true, 'short' => true, 'signed' => true, 'sizeof' => true,
			'static' => true, 'static_assert' => true, 'static_cast' => true, 'struct' => true, 'switch' => true,
			'template' => true, 'this' => true, 'thread_local' => true, 'throw' => true, 'true' => true,
			'try' => true, 'typedef' => true, 'typeid' => true, 'typename' => true, 'union' => true,
			'unsigned' => true, 'using' => true, 'virtual' => true, 'void' => true, 'volatile' => true,
			'wchar_t' => true, 'while' => true, 'xor' => true, 'xor_eq' => true,
		];

		return isset($reserved[$name]);
	}

	private function cppIdentifier(string $name): string
	{
		return $this->isCppReservedIdentifier($name) ? $name . '_' : $name;
	}

	private function emitEnumClass(array &$header, ClassDecl $class): void
	{
		if ($class->parentClass !== null || $class->interfaces !== [] || $class->properties !== [] || $class->constants !== [] || $class->methods !== []) {
			throw new \RuntimeException('Only simple enums with cases only are supported in the current enum lowering');
		}
		if ($class->enumBackingType === null) {
			foreach ($class->enumCases as $case) {
				if ($case->value !== null) {
					throw new \RuntimeException('Unit enums must not declare backed values in the current enum lowering');
				}
			}
		}
		if ($class->enumCases === []) {
			throw new \RuntimeException('Enums must declare at least one case in the current enum lowering');
		}
		$this->validateEnumCases($class);
		$storage = $this->enumStorageType($class);
		$this->appendHeaderLines($header, $this->code('enum class ' . $class->name . ' : ' . $storage . ' {', $class->line));
		foreach ($class->enumCases as $index => $case) {
			$suffix = $index + 1 < count($class->enumCases) ? ',' : '';
			$line = $this->indent(1) . $this->cppIdentifier($case->name);
			if ($class->enumBackingType !== null) {
				$line .= ' = ' . $this->renderEnumCaseValue($case);
			}
			$this->appendHeaderLines($header, $this->code($line . $suffix, $case->line));
		}
		$this->appendHeaderLines($header, $this->code('};', $class->line));
		$this->appendHeaderLines($header, $this->code('', 0));
	}

	private function validateEnumCases(ClassDecl $class): void
	{
		$seenNames = [];
		$seenValues = [];
		foreach ($class->enumCases as $index => $case) {
			$name = $case->name;
			if (isset($seenNames[$name])) {
				throw new \RuntimeException('Duplicate enum case name `' . $name . '` in enum `' . $class->name . '`');
			}
			$seenNames[$name] = true;

			if ($class->enumBackingType === null) {
				$value = $index;
			} else {
				$value = $this->enumCaseIntValue($case);
			}
			if (isset($seenValues[$value])) {
				throw new \RuntimeException('Duplicate enum case value `' . (string) $value . '` in enum `' . $class->name . '`');
			}
			$seenValues[$value] = true;
		}
	}

	private function emitStructClass(array &$header, ClassDecl $class, ?string $namespacePhp): void
	{
		if ($class->parentClass !== null || $class->interfaces !== [] || $class->constants !== [] || $class->methods !== [] || $class->isAbstract || $class->isInterface) {
			throw new \RuntimeException('Only simple structs with public instance fields are supported in the current struct lowering');
		}

		$this->appendHeaderLines($header, $this->code('struct ' . $class->name . ' {', $class->line));
		$this->appendHeaderLines($header, $this->code($this->indent(1) . $class->name . '* operator->() { return this; }', $class->line));
		$this->appendHeaderLines($header, $this->code($this->indent(1) . 'const ' . $class->name . '* operator->() const { return this; }', $class->line));
		foreach ($class->properties as $property) {
			if ($property->visibility !== 'public' || $property->isStatic) {
				throw new \RuntimeException('Only public instance fields are supported in the current struct lowering');
			}
			$initializer = $property->hasDefault
				? $this->renderInitializerExpr($property->default, $property->type, $namespacePhp)
				: null;
			if ($property->type !== null) {
				$type = $this->typeMapper->mapDeclaredType($property->type);
			} elseif ($initializer !== null) {
				$type = 'decltype(' . $initializer . ')';
			} else {
				$type = '/* ERROR missing-struct-field-type */';
			}
			$line = $type . ' ' . $this->cppIdentifier($property->name);
			if ($initializer !== null) {
				$line .= ' = ' . $initializer;
			}
			$this->appendHeaderLines($header, $this->code($this->indent(1) . $line . ';', $property->line));
		}
		$this->appendHeaderLines($header, $this->code('};', $class->line));
		$this->appendHeaderLines($header, $this->code('', 0));
	}

	private function emitUnionClass(array &$header, ClassDecl $class): void
	{
		if ($class->parentClass !== null || $class->interfaces !== [] || $class->constants !== [] || $class->methods !== [] || $class->isAbstract || $class->isInterface) {
			throw new \RuntimeException('Only simple unions with public instance payload fields are supported in the current union lowering');
		}

		$this->appendHeaderLines($header, $this->code('union ' . $class->name . ' {', $class->line));
		$this->appendHeaderLines($header, $this->code($this->indent(1) . $class->name . '() {}', $class->line));
		$this->appendHeaderLines($header, $this->code($this->indent(1) . '~' . $class->name . '() {}', $class->line));
		foreach ($class->properties as $property) {
			if ($property->visibility !== 'public' || $property->isStatic || $property->hasDefault) {
				throw new \RuntimeException('Only public instance payload fields without defaults are supported in the current union lowering');
			}
			$type = $property->type !== null
				? $this->typeMapper->mapDeclaredType($property->type)
				: '/* ERROR missing-union-field-type */';
			$this->appendHeaderLines($header, $this->code($this->indent(1) . $type . ' ' . $this->cppIdentifier($property->name) . ';', $property->line));
		}
		$this->appendHeaderLines($header, $this->code('};', $class->line));
		$this->appendHeaderLines($header, $this->code('', 0));
	}

	private function emitClass(array &$header, array &$source, ClassDecl $class, ?string $namespacePhp): void
	{
		if ($class->isEnum) {
			$this->emitEnumClass($header, $class);
			return;
		}
		if ($class->isStruct) {
			$this->emitStructClass($header, $class, $namespacePhp);
			return;
		}
		if ($class->isUnion) {
			$this->emitUnionClass($header, $class);
			return;
		}
		$extends = [];
		if ($class->parentClass !== null) {
			$extends[] = 'public ' . $this->typeMapper->mapClassName($class->parentClass);
		}
		foreach ($class->interfaces as $interface) {
			$extends[] = 'public ' . $this->typeMapper->mapClassName($interface);
		}
		$this->appendHeaderLines($header, $this->code('class ' . $class->name . ($extends !== [] ? ' : ' . implode(', ', $extends) : '') . ' {', $class->line));
		$this->appendHeaderLines($header, $this->code('public:', $class->line));
		$lateStaticDispatchMethods = $this->collectLateStaticDispatchMethods($class, $namespacePhp);
		$this->appendHeaderLines($header, $this->code($this->indent(1) . 'static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }', $class->line));
		$this->appendHeaderLines($header, $this->code($this->indent(1) . 'static bool_t __scpp_static_accepts(const void* __scpp_token);', $class->line));
		foreach ($lateStaticDispatchMethods as $dispatchMethod) {
			$this->appendHeaderLines($header, $this->code($this->indent(1) . $this->renderLateStaticDispatchDeclaration($dispatchMethod, $namespacePhp) . ';', $dispatchMethod->line));
		}
		if ($lateStaticDispatchMethods !== []) {
			$this->appendHeaderLines($header, $this->code('', 0));
		}
		$currentAccessSection = 'public';
		foreach ($class->properties as $property) {
			$this->emitClassAccessSection($header, $currentAccessSection, $property->visibility, $property->line);
			$initializer = $property->hasDefault
				? $this->renderInitializerExpr($property->default, $property->type, $namespacePhp)
				: null;
			if ($property->type !== null) {
				$type = $this->typeMapper->mapDeclaredType($property->type);
			} elseif ($initializer !== null) {
				$type = 'decltype(' . $initializer . ')';
			} else {
				$type = '/* ERROR missing-property-type */';
			}
			$line = $type . ' ' . $this->cppIdentifier($property->name);
			if ($property->isStatic) {
				$line = 'static ' . $line;
				if ($initializer !== null) {
					$line .= ';';
				}
			} elseif ($initializer !== null) {
				$line .= ' = ' . $initializer;
			}
			$this->appendHeaderLines($header, $this->code($this->indent(1) . rtrim($line, ';') . ';', $property->line));
		}
		foreach ($class->constants as $constant) {
			$this->emitClassAccessSection($header, $currentAccessSection, $constant->visibility, $constant->line);
			$this->appendHeaderLines($header, $this->code($this->indent(1) . 'static inline const auto ' . $this->cppIdentifier($constant->name) . ' = ' . $this->renderExpr($constant->value, $namespacePhp) . ';', $constant->line));
		}
		foreach ($class->methods as $method) {
			$this->emitClassAccessSection($header, $currentAccessSection, $method->visibility, $method->line);
			if ($this->methodNeedsNormalizedTemplate($method)) {
				$artifacts = $this->functionLikeUsesExecBodySplit($method->params, $method->statements)
					? $this->renderInlineTemplateMethodArtifactsWithExecSplit($class, $method, $namespacePhp)
					: $this->renderInlineTemplateMethodArtifacts($class, $method, $namespacePhp);
				foreach ($artifacts as $line) {
					$this->appendHeaderLines($header, $this->code($this->indent(1) . $line, $method->line));
				}
				continue;
			}
			$this->appendHeaderLines($header, $this->code($this->indent(1) . $this->renderMethodDeclaration($method, $class, $namespacePhp) . ';', $method->line));
			if ($this->methodNeedsValueRefOverload($method, $class)) {
				$overload = $this->methodRequiresInlineValueRefOverload($method, $class)
					? $this->renderInlineMethodValueRefOverloadDefinition($class, $method, $namespacePhp)
					: $this->renderMethodValueRefOverloadDeclaration($method, $class, $namespacePhp) . ';';
				foreach (explode("\n", $overload) as $line) {
					$this->appendHeaderLines($header, $this->code($this->indent(1) . $line, $method->line));
				}
			}
		}
		$this->appendHeaderLines($header, $this->code('};', $class->line));
		$this->appendHeaderLines($header, $this->code('', 0));

		foreach ($class->properties as $property) {
			if (!$property->isStatic) {
				continue;
			}
			$default = $property->hasDefault
				? $this->renderInitializerExpr($property->default, $property->type, $namespacePhp)
				: null;
			if ($property->type !== null) {
				$type = $this->typeMapper->mapDeclaredType($property->type);
			} elseif ($default !== null) {
				$type = 'decltype(' . $default . ')';
			} else {
				$type = '/* ERROR missing-property-type */';
			}
			$this->appendSourceLines($source, $this->code($type . ' ' . $class->name . '::' . $this->cppIdentifier($property->name) . ' = ' . ($default ?? ($type . '{}')) . ';', $property->line));
		}
		if (!$class->isInterface && array_filter($class->properties, static fn ($property): bool => $property->isStatic) !== []) {
			$this->appendSourceLines($source, $this->code('', 0));
		}

		if (!$class->isInterface) {
			$prevClassName = $this->currentClassName;
			$prevParentClass = $this->currentParentClass;
			$prevLateStaticDispatchMethods = $this->currentLateStaticDispatchMethods;
			$this->currentClassName = $class->name;
			$this->currentParentClass = $class->parentClass;
			$this->currentLateStaticDispatchMethods = $lateStaticDispatchMethods;
			$this->appendSourceLines($source, ...$this->codeLinesFromTextBlock($this->renderLateStaticAcceptsDefinition($class, $namespacePhp), $class->line));
			$this->appendSourceLines($source, $this->code('', 0));
			foreach ($lateStaticDispatchMethods as $dispatchMethod) {
				$this->appendSourceLines($source, ...$this->codeLinesFromTextBlock($this->renderLateStaticDispatchDefinition($class, $dispatchMethod, $namespacePhp), $dispatchMethod->line));
				$this->appendSourceLines($source, $this->code('', 0));
			}
			foreach ($class->methods as $method) {
				if ($this->methodIsAbstract($method, $class)) {
					continue;
				}
				if ($this->methodNeedsNormalizedTemplate($method)) {
					if ($this->functionLikeUsesExecBodySplit($method->params, $method->statements)) {
						$this->appendSourceLines($source, ...$this->codeLinesFromTextBlock($this->renderMethodExecDefinition($class, $method, $namespacePhp), $method->line));
						$this->appendSourceLines($source, $this->code('', 0));
					}
					continue;
				}
				$this->appendSourceLines($source, ...$this->renderMethodDefinition($class, $method, $namespacePhp));
				$this->appendSourceLines($source, $this->code('', 0));
				if ($this->methodNeedsValueRefOverload($method, $class) && !$this->methodRequiresInlineValueRefOverload($method, $class)) {
					$this->appendSourceLines($source, ...$this->codeLinesFromTextBlock($this->renderMethodValueRefOverloadDefinition($class, $method, $namespacePhp), $method->line));
					$this->appendSourceLines($source, $this->code('', 0));
				}
			}
			$this->currentClassName = $prevClassName;
			$this->currentParentClass = $prevParentClass;
			$this->currentLateStaticDispatchMethods = $prevLateStaticDispatchMethods;
		}
	}

	private function emitClassAccessSection(array &$header, string &$currentAccessSection, string $visibility, int $line): void
	{
		if (!in_array($visibility, ['public', 'protected', 'private'], true)) {
			$visibility = 'public';
		}
		if ($visibility === $currentAccessSection) {
			return;
		}
		$currentAccessSection = $visibility;
		$this->appendHeaderLines($header, $this->code($visibility . ':', $line));
	}

	/**

	 * Emits one top-level function declaration/definition pair.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	/**
	 * @param list<ParamDecl> $params
	 * @param list<Statement> $statements
	 * @return array<string, string>
	 */
	private function analyzeParamPassModes(array $params, array $statements): array
	{
		$modes = [];
		$tracked = [];
		foreach ($params as $param) {
			if ($param->isReference || $param->isVariadic || $param->type === null) {
				continue;
			}

			$mapped = $this->typeMapper->mapDeclaredType($param->type);
			if ($mapped !== 'string_t' && $mapped !== 'mixed_t' && !str_starts_with($mapped, 'vector_t<') && !str_starts_with($mapped, 'fixed_array_t<') && !str_starts_with($mapped, 'hash_t<')) {
				continue;
			}

			$modes[$param->name] = 'readonly';
			$tracked[$param->name] = true;
		}

		if ($tracked === []) {
			return $modes;
		}

		$this->markOwnedLocalParamsFromStatements($tracked, $modes, $statements);
		return $modes;
	}

	/**
	 * @param array<string, bool> $tracked
	 * @param array<string, string> $modes
	 * @param list<Statement> $statements
	 */
	private function markOwnedLocalParamsFromStatements(array $tracked, array &$modes, array $statements): void
	{
		foreach ($statements as $statement) {
			if (!$statement instanceof Statement) {
				continue;
			}

			if ($statement->kind === 'assign' || $statement->kind === 'assign_ref' || $statement->kind === 'assign_op') {
				$targetRoot = $this->extractAssignmentRootVarName($statement->payload['var'] ?? null);
				if ($targetRoot !== null && isset($tracked[$targetRoot])) {
					$modes[$targetRoot] = 'owned_local';
				}
				continue;
			}

			if ($statement->kind === 'unset') {
				$targetRoot = $this->extractAssignmentRootVarName($statement->payload);
				if ($targetRoot !== null && isset($tracked[$targetRoot])) {
					$modes[$targetRoot] = 'owned_local';
				}
				continue;
			}

			if ($statement->kind === 'expr') {
				$targetRoot = $this->extractMutationExprRootVarName($statement->payload);
				if ($targetRoot !== null && isset($tracked[$targetRoot])) {
					$modes[$targetRoot] = 'owned_local';
				}
				continue;
			}

			if ($statement->kind === 'foreach') {
				if ((bool) ($statement->payload['by_ref'] ?? false)) {
					$sourceRoot = $this->extractSimpleVarName($statement->payload['expr'] ?? null);
					if ($sourceRoot !== null && isset($tracked[$sourceRoot])) {
						$modes[$sourceRoot] = 'owned_local';
					}
				}
				$this->markOwnedLocalParamsFromStatements($tracked, $modes, $statement->payload['stmts'] ?? []);
				continue;
			}

			if ($statement->kind === 'if') {
				foreach ($statement->payload as $branch) {
					$this->markOwnedLocalParamsFromStatements($tracked, $modes, $branch['stmts'] ?? []);
				}
				continue;
			}

			if ($statement->kind === 'while' || $statement->kind === 'do_while' || $statement->kind === 'for') {
				$this->markOwnedLocalParamsFromStatements($tracked, $modes, $statement->payload['stmts'] ?? []);
				continue;
			}

			if ($statement->kind === 'switch') {
				foreach (($statement->payload['cases'] ?? []) as $case) {
					$this->markOwnedLocalParamsFromStatements($tracked, $modes, $case['stmts'] ?? []);
				}
			}
		}
	}

	private function extractAssignmentRootVarName(mixed $target): ?string
	{
		if (!is_object($target)) {
			return null;
		}

		$kind = $target->kind ?? null;
		if ($kind === AstKind::VAR) {
			return $this->extractSimpleVarName($target);
		}

		if ($kind === AstKind::DIM) {
			return $this->extractAssignmentRootVarName($target->children['expr'] ?? null);
		}

		return null;
	}

	private function extractMutationExprRootVarName(mixed $expr): ?string
	{
		if (!is_object($expr)) {
			return null;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::PRE_INC || $kind === AstKind::PRE_DEC || $kind === AstKind::POST_INC || $kind === AstKind::POST_DEC) {
			return $this->extractAssignmentRootVarName($expr->children['var'] ?? null);
		}

		return null;
	}

	private function emitFunction(array &$header, array &$source, FunctionDecl $function, ?string $namespacePhp): void
	{
		if ($this->functionLikeNeedsNormalizedTemplate($function->params)) {
			$artifacts = $this->functionLikeUsesExecBodySplit($function->params, $function->statements)
				? $this->renderFunctionTemplateArtifactsWithExecSplit($function, $namespacePhp)
				: $this->renderFunctionTemplateArtifacts($function, $namespacePhp);
			foreach ($artifacts as $line) {
				$this->appendHeaderLines($header, $this->code($line, $function->line));
			}
			$this->appendHeaderLines($header, $this->code('', 0));
			if ($this->functionLikeUsesExecBodySplit($function->params, $function->statements)) {
				$this->appendSourceLines($source, ...$this->codeLinesFromTextBlock($this->renderFunctionExecDefinition($function, $namespacePhp), $function->line));
				$this->appendSourceLines($source, $this->code('', 0));
			}
			return;
		}
		$this->appendHeaderLines($header, $this->code($this->renderFunctionDeclaration($function, $namespacePhp) . ';', $function->line));
		if ($this->functionNeedsValueRefOverload($function->params)) {
			$this->appendHeaderLines($header, $this->code($this->renderFunctionValueRefOverloadDeclaration($function, $namespacePhp) . ';', $function->line));
		}
		$this->appendHeaderLines($header, $this->code('', 0));
		$this->appendSourceLines($source, ...$this->renderFunctionDefinition($function, $namespacePhp));
		$this->appendSourceLines($source, $this->code('', 0));
		if ($this->functionNeedsValueRefOverload($function->params)) {
			$this->appendSourceLines($source, ...$this->codeLinesFromTextBlock($this->renderFunctionValueRefOverloadDefinition($function, $namespacePhp), $function->line));
			$this->appendSourceLines($source, $this->code('', 0));
		}
	}

	/**

	 * Emits the synthetic entry point used for executable namespace/root statements.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function emitNamespaceMain(array &$header, array &$source, string $name, array $statements, ?string $namespacePhp): void
	{
		$this->appendHeaderLines($header, $this->code('int ' . $name . '();', 0));
		$this->appendHeaderLines($header, $this->code('', 0));
		$this->appendSourceLines($source, $this->code('int ' . $name . '() {', 0));
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->predefinedReferenceLocals = [];
		$this->currentReturnType = 'int';
		$this->seedSyntheticMainCliLocals();
		foreach ($this->renderSyntheticMainCliPreamble() as $line) {
			$this->appendSourceLines($source, $this->codeWithCurrentOrigin($this->indent(1) . $line));
		}
		$this->appendSourceLines($source, $this->codeWithCurrentOrigin($this->indent(1) . $this->renderCallDepthGuardLine($name, 0)));
		foreach ($statements as $statement) {
			$this->currentSourceLine = $statement->line;
			$this->currentSourceColumn = 0;
			$this->appendSourceLines($source, ...$this->indentCodeLines($this->renderStatement($statement, $namespacePhp), 1));
		}
		$this->appendSourceLines($source, $this->codeWithCurrentOrigin($this->indent(1) . 'return 0;'));
		$this->appendSourceLines($source, $this->codeWithCurrentOrigin('}'));
		$this->currentReturnType = null;
	}

	/**

	 * Renders a method signature using the current type and constructor mapping rules.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderMethodDeclaration(MethodDecl $method, ClassDecl|string|null $classDecl = null, ?string $namespacePhp = null): string
	{
		$className = is_string($classDecl) ? $classDecl : ($classDecl?->name);
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$this->beginFunctionLikeVariableMapping($method->params, $method->statements);
		try {
			if ($method->name === '__construct' && $className !== null) {
				return $className . '(' . $this->renderParams($method->params, true, $namespacePhp, $paramPassModes) . ')';
			}
			if ($method->name === '__destruct' && $className !== null) {
				return '~' . $className . '()';
			}
			$prefix = $method->isStatic ? 'static ' : '';
			if (
				!$method->isStatic
				&& $classDecl instanceof ClassDecl
				&& $method->name !== '__construct'
				&& $method->name !== '__destruct'
			) {
				$prefix .= 'virtual ';
			}
			$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
			$declaration = $prefix . $returnType . ' ' . $this->cppIdentifier($method->name) . '(' . $this->renderParams($method->params, true, $namespacePhp, $paramPassModes) . ')';
			if ($classDecl instanceof ClassDecl && $this->methodIsAbstract($method, $classDecl)) {
				$declaration .= ' = 0';
			}
			return $declaration;
		} finally {
			$this->endFunctionLikeVariableMapping();
		}
	}

	/**

	 * Renders the out-of-class method definition body into the source file.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	
	private function methodIsAbstract(MethodDecl $method, ClassDecl $class): bool
	{
		return $class->isInterface || ($method->statements === [] && $method->name !== '__construct' && $method->name !== '__destruct');
	}

	private function extractParentConstructorArgs(array $statements): ?array
	{
		$first = $statements[0] ?? null;
		if (!$first instanceof Statement || $first->kind !== 'expr' || !is_array($first->payload)) {
			return null;
		}
		$expr = $first->payload;
		if (($expr->kind ?? null) !== AstKind::STATIC_CALL) {
			return null;
		}
		$classNode = $expr->children['class'] ?? null;
		$method = (string) ($expr->children['method'] ?? '');
		if (!is_object($classNode) || ($classNode->kind ?? null) !== AstKind::NAME) {
			return null;
		}
		$name = strtolower((string) ($classNode->children['name'] ?? ''));
		if ($name !== 'parent' || $method !== '__construct') {
			return null;
		}
		return $expr->children['args']->children ?? [];
	}

	private function functionNeedsValueRefOverload(array $params): bool
	{
		return false;
	}

	private function methodNeedsValueRefOverload(MethodDecl $method, ClassDecl $class): bool
	{
		if ($method->name === '__construct' || $method->name === '__destruct') {
			return false;
		}

		return $this->functionNeedsValueRefOverload($method->params);
	}

	private function methodRequiresInlineValueRefOverload(MethodDecl $method, ClassDecl $class): bool
	{
		return $this->methodIsAbstract($method, $class);
	}

	private function isSupportedScalarValueRefOverloadParam(ParamDecl $param): bool
	{
		return false;
	}

	private function mapValueRefAccessorForParam(ParamDecl $param): ?string
	{
		if (!$this->isSupportedScalarTemplateRefParam($param) && !$this->isSupportedScalarValueRefOverloadParam($param)) {
			return null;
		}

		return match ($this->typeMapper->mapDeclaredType($param->type ?? '')) {
			'int_t' => 'as_int_ref',
			'float_t' => 'as_float_ref',
			'bool_t' => 'as_bool_ref',
			'string_t' => 'as_string_ref',
			default => null,
		};
	}

	private function renderValueRefOverloadParams(array $params, bool $includeDefaults, ?string $namespacePhp, array $paramPassModes = []): string
	{
		$out = [];
		foreach ($params as $param) {
			if ($param->isVariadic) {
				$elementType = $param->type !== null ? $this->typeMapper->mapDeclaredType($param->type) : '/* ERROR missing-variadic-element-type */';
				$type = 'const vector_t<' . $elementType . '>&';
			} elseif ($this->isSupportedScalarValueRefOverloadParam($param)) {
				$type = 'mixed_t&';
			} else {
				$type = $param->type !== null ? $this->renderParamTypeForMode($param, $paramPassModes[$param->name] ?? 'readonly') : '/* ERROR missing-parameter-type */';
			}
			$rendered = $type . ' ' . $this->renderParamName($param, false);
			if (!$param->isVariadic && $includeDefaults && $param->default !== null) {
				$rendered .= ' = ' . $this->renderExpr($param->default, $namespacePhp);
			}
			$out[] = $rendered;
		}
		return implode(', ', $out);
	}

	private function renderValueRefOverloadForwardArgs(array $params): string
	{
		$out = [];
		foreach ($params as $param) {
			$accessor = $this->mapValueRefAccessorForParam($param);
			$out[] = $accessor !== null
				? $this->parameterCppName($param) . '.' . $accessor . '()'
				: $this->parameterCppName($param);
		}
		return implode(', ', $out);
	}

	private function renderFunctionValueRefOverloadDeclaration(FunctionDecl $function, ?string $namespacePhp = null): string
	{
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		$paramPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		$this->beginFunctionLikeVariableMapping($function->params, $function->statements);
		try {
			return $returnType . ' ' . $function->name . '(' . $this->renderValueRefOverloadParams($function->params, true, $namespacePhp, $paramPassModes) . ')';
		} finally {
			$this->endFunctionLikeVariableMapping();
		}
	}

	private function renderFunctionValueRefOverloadDefinition(FunctionDecl $function, ?string $namespacePhp): string
	{
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		$paramPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		$signature = $returnType . ' ' . $function->name . '(' . $this->renderValueRefOverloadParams($function->params, false, $namespacePhp, $paramPassModes) . ')';
		$forward = $this->renderValueRefOverloadForwardArgs($function->params);
		$body = $returnType === 'void'
			? $this->indent(1) . $function->name . '(' . $forward . ');'
			: $this->indent(1) . 'return ' . $function->name . '(' . $forward . ');';
		return $signature . " {
" . $body . "
}";
	}

	private function renderMethodValueRefOverloadDeclaration(MethodDecl $method, ClassDecl|string|null $classDecl = null, ?string $namespacePhp = null): string
	{
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$prefix = $method->isStatic ? 'static ' : '';
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		return $prefix . $returnType . ' ' . $this->cppIdentifier($method->name) . '(' . $this->renderValueRefOverloadParams($method->params, true, $namespacePhp, $paramPassModes) . ')';
	}

	private function renderMethodValueRefOverloadDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$signature = $returnType . ' ' . $class->name . '::' . $this->cppIdentifier($method->name) . '(' . $this->renderValueRefOverloadParams($method->params, false, $namespacePhp, $paramPassModes) . ')';
		$forward = $this->renderValueRefOverloadForwardArgs($method->params);
		$body = $returnType === 'void'
			? $this->indent(1) . $this->cppIdentifier($method->name) . '(' . $forward . ');'
			: $this->indent(1) . 'return ' . $this->cppIdentifier($method->name) . '(' . $forward . ');';
		return $signature . " {
" . $body . "
}";
	}

	/** @return array<string, MethodDecl> */
	private function collectLateStaticDispatchMethods(ClassDecl $class, ?string $namespacePhp): array
	{
		$out = [];
		$ownerKey = $this->qualifyClassNameForLookup($class->name, $namespacePhp);
		foreach ($class->methods as $method) {
			$this->walkForLateStaticCalls($method->statements, $out, $ownerKey);
		}
		return $out;
	}

	private function walkForLateStaticCalls(mixed $node, array &$out, string $ownerKey): void
	{
		if ($node instanceof Statement) {
			$this->walkForLateStaticCalls($node->payload, $out, $ownerKey);
			return;
		}

		if (is_array($node)) {
			foreach ($node as $value) {
				$this->walkForLateStaticCalls($value, $out, $ownerKey);
			}
			return;
		}

		if (!is_object($node)) {
			return;
		}

		$kind = $node->kind ?? null;
		if ($kind === AstKind::STATIC_CALL) {
			$classNode = $node->children['class'] ?? null;
			if (
				is_object($classNode)
				&& ($classNode->kind ?? null) === AstKind::NAME
				&& strtolower(ltrim((string) ($classNode->children['name'] ?? ''), '\\')) === 'static'
			) {
				$methodName = (string) ($node->children['method'] ?? '');
				$methodDecl = $this->resolveMethodDeclInClassHierarchy($ownerKey, $methodName);
				if ($methodDecl !== null) {
					$out[$methodName] = $methodDecl;
				}
			}
		}

		foreach (get_object_vars($node) as $value) {
			$this->walkForLateStaticCalls($value, $out, $ownerKey);
		}
	}

	private function renderLateStaticDispatchDeclaration(MethodDecl $method, ?string $namespacePhp): string
	{
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$this->beginFunctionLikeVariableMapping($method->params, $method->statements);
		try {
			$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
			return 'static ' . $returnType . ' ' . $this->renderLateStaticDispatchHelperName($method->name) . '(' . $this->renderParams($method->params, false, $namespacePhp, $paramPassModes) . ')';
		} finally {
			$this->endFunctionLikeVariableMapping();
		}
	}

	private function renderLateStaticDispatchHelperName(string $methodName): string
	{
		return '__scpp_static_call_' . $this->cppIdentifier($methodName);
	}

	private function renderLateStaticPropHelperName(string $propName): string
	{
		return '__scpp_static_prop_' . $this->cppIdentifier($propName);
	}

	private function renderLateStaticConstHelperName(string $constName): string
	{
		return '__scpp_static_const_' . $this->cppIdentifier($constName);
	}

	private function renderLateStaticNewHelperName(): string
	{
		return '__scpp_static_new';
	}

	/** @return list<string> */
	private function collectLateStaticHierarchyTargetKeys(ClassDecl $owner, ?string $namespacePhp): array
	{
		$ownerKey = $this->qualifyClassNameForLookup($owner->name, $namespacePhp);
		$targets = [];
		foreach ($this->classDecls as $candidateKey => $candidateDecl) {
			if (!$candidateDecl instanceof ClassDecl || !$this->isClassSameOrDescendantOf($candidateKey, $ownerKey)) {
				continue;
			}
			$targets[] = $candidateKey;
		}
		if ($targets === []) {
			$targets[] = $ownerKey;
		}
		return array_values(array_unique($targets));
	}

	/** @return list<string> */
	private function collectLateStaticDispatchTargets(ClassDecl $owner, MethodDecl $method, ?string $namespacePhp): array
	{
		$targets = [];
		foreach ($this->collectLateStaticHierarchyTargetKeys($owner, $namespacePhp) as $candidateKey) {
			if ($this->resolveMethodDeclInClassHierarchy($candidateKey, $method->name) === null) {
				continue;
			}
			$targets[] = $this->typeMapper->mapClassName($candidateKey);
		}
		if ($targets === []) {
			$targets[] = $owner->name;
		}
		return array_values(array_unique($targets));
	}

	private function renderLateStaticDispatchDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$this->beginFunctionLikeVariableMapping($method->params, $method->statements);
		try {
			$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
			$signature = $returnType . ' ' . $class->name . '::' . $this->renderLateStaticDispatchHelperName($method->name) . '(' . $this->renderParams($method->params, false, $namespacePhp, $paramPassModes, true) . ')';
			$forwardArgs = implode(', ', array_map(fn (ParamDecl $param): string => $this->parameterCppName($param), $method->params));
			$tokenExpr = '::scpp::php::current_static_token_for<' . $class->name . '>()';
			$lines = [];
			foreach ($this->collectLateStaticDispatchTargets($class, $method, $namespacePhp) as $targetClass) {
				$prefix = $lines === [] ? 'if' : 'else if';
				$lines[] = $this->indent(1) . $prefix . ' (' . $tokenExpr . ' == ' . $targetClass . '::__scpp_static_token()) {';
				$call = $targetClass . '::' . $this->cppIdentifier($method->name) . '(' . $forwardArgs . ')';
				if ($returnType === 'void') {
					$lines[] = $this->indent(2) . $call . ';';
					$lines[] = $this->indent(2) . 'return;';
				} else {
					$lines[] = $this->indent(2) . 'return ' . $call . ';';
				}
				$lines[] = $this->indent(1) . '}';
			}
			$fallbackCall = $class->name . '::' . $this->cppIdentifier($method->name) . '(' . $forwardArgs . ')';
			if ($returnType === 'void') {
				$lines[] = $this->indent(1) . $fallbackCall . ';';
				$lines[] = $this->indent(1) . 'return;';
			} else {
				$lines[] = $this->indent(1) . 'return ' . $fallbackCall . ';';
			}
			return $signature . " {\n" . implode("\n", $lines) . "\n}";
		} finally {
			$this->endFunctionLikeVariableMapping();
		}
	}

	private function renderLateStaticAcceptsDefinition(ClassDecl $class, ?string $namespacePhp): string
	{
		$targets = array_map(fn (string $key): string => $this->typeMapper->mapClassName($key), $this->collectLateStaticHierarchyTargetKeys($class, $namespacePhp));
		$lines = ['bool_t ' . $class->name . '::__scpp_static_accepts(const void* __scpp_token) {'];
		foreach ($targets as $index => $targetClass) {
			$prefix = $index === 0 ? 'if' : 'else if';
			$lines[] = $this->indent(1) . $prefix . ' (__scpp_token == ' . $targetClass . '::__scpp_static_token()) {';
			$lines[] = $this->indent(2) . 'return static_cast<bool_t>(true);';
			$lines[] = $this->indent(1) . '}';
		}
		$lines[] = $this->indent(1) . 'return static_cast<bool_t>(false);';
		$lines[] = '}';
		return implode("\n", $lines);
	}

	private function renderInlineMethodValueRefOverloadDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$prefix = $method->isStatic ? 'static ' : '';
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		$signature = $prefix . $returnType . ' ' . $this->cppIdentifier($method->name) . '(' . $this->renderValueRefOverloadParams($method->params, false, $namespacePhp, $paramPassModes) . ')';
		$forward = $this->renderValueRefOverloadForwardArgs($method->params);
		$body = $returnType === 'void'
			? $this->indent(1) . $this->cppIdentifier($method->name) . '(' . $forward . ');'
			: $this->indent(1) . 'return ' . $this->cppIdentifier($method->name) . '(' . $forward . ');';
		return $signature . " {\n" . $body . "\n}";
	}

	/** @return list<CodeBlock> */
	private function renderMethodDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): array
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->predefinedReferenceLocals = [];
		$this->currentParamPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$this->beginFunctionLikeVariableMapping($method->params, $method->statements);
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->isReference) {
				$this->predefinedReferenceLocals[$param->name] = true;
			}
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($method->argNormalizationRules);
		$this->currentNormalizationCallableName = $this->methodNeedsNormalizedTemplate($method) ? $method->name : null;
		$this->currentParamEntryAliasLines = $this->buildParamEntryAliasLines($method->params);
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($method->params);
		$className = $class->name;
		$statements = $method->statements;
		$initializer = '';
		if ($method->name === '__construct') {
			$this->currentReturnType = null;
			if ($class->parentClass !== null) {
				$parentArgs = $this->extractParentConstructorArgs($statements);
				if ($parentArgs !== null) {
					$initializer = ' : ' . $this->typeMapper->mapClassName($class->parentClass) . '(' . $this->renderArgs($parentArgs, $namespacePhp) . ')';
					array_shift($statements);
				}
			}
			$signature = $className . '::' . $className . '(' . $this->renderParams($method->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')' . $initializer;
		} elseif ($method->name === '__destruct') {
			$this->currentReturnType = null;
			$signature = $className . '::~' . $className . '()';
		} else {
			$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
			$this->currentReturnType = $returnType;
			$signature = $returnType . ' ' . $className . '::' . $this->cppIdentifier($method->name) . '(' . $this->renderParams($method->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')';
		}
		$body = $this->renderBody($statements, $namespacePhp);
		array_unshift($body, $this->codeWithCurrentOrigin($this->indent(1) . $this->renderCallDepthGuardLine($className . '::' . $this->cppIdentifier($method->name), $method->line)));
		$this->currentReturnType = null;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		$this->endFunctionLikeVariableMapping();
		return array_merge(
			[$this->code($signature . ' {', $method->line)],
			$body,
			[$this->code('}', $method->line)],
		);
	}


	private function resolveDeclaredReturnType(?string $phpType, bool $explicitRef, string $ownerLabel): string
	{
		if ($explicitRef && $phpType === null) {
			$this->errors[] = $ownerLabel . ' returning by reference requires an explicit declared return type.';
			return '/* unsupported-ref-return-type */';
		}

		return $this->typeMapper->mapReturnType($this->qualifyDeclaredPhpType($phpType, $this->currentNamespacePhp), $explicitRef);
	}

	/**

	 * Renders a function signature for the generated header.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */


	private function functionLikeNeedsNormalizedTemplate(array $params): bool
	{
		foreach ($params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				return true;
			}
		}
		return false;
	}

	private function methodNeedsNormalizedTemplate(MethodDecl $method): bool
	{
		if ($method->name === '__construct' || $method->name === '__destruct') {
			return false;
		}
		return $this->functionLikeNeedsNormalizedTemplate($method->params);
	}

	private function functionLikeUsesExecBodySplit(array $params, array $statements): bool
	{
		return $this->functionLikeNeedsNormalizedTemplate($params) && count($statements) > 2;
	}

	private function renderExecCallableName(string $callableName): string
	{
		return $this->cppIdentifier($callableName) . '__exec';
	}

	private function renderCanonicalParamTypeForExec(ParamDecl $param, string $mode): string
	{
		if ($param->type === null) {
			return '/* ERROR missing-parameter-type */';
		}

		if ($this->paramNeedsTemplateNormalization($param)) {
			$primaryType = $param->primaryType ?? $param->type;
			$mapped = $this->typeMapper->mapDeclaredType($primaryType);
			if ($param->isReference) {
				return $mapped . '&';
			}
			return $this->typeMapper->mapParamType($primaryType, false);
		}

		return $this->renderParamTypeForMode($param, $mode);
	}

	private function renderCanonicalParamsForExec(array $params, bool $includeDefaults, ?string $namespacePhp, array $paramPassModes = []): string
	{
		$out = [];
		foreach ($params as $param) {
			if ($param->isVariadic) {
				$elementType = $param->type !== null ? $this->typeMapper->mapDeclaredType($param->type) : '/* ERROR missing-variadic-element-type */';
				$type = 'const vector_t<' . $elementType . '>&';
			} else {
				$type = $param->type !== null ? $this->renderCanonicalParamTypeForExec($param, $paramPassModes[$param->name] ?? 'readonly') : '/* ERROR missing-parameter-type */';
			}
			$rendered = $type . ' ' . $this->localCppName($param->name);
			if (!$param->isVariadic && $includeDefaults && $param->default !== null) {
				$rendered .= ' = ' . $this->renderExpr($param->default, $namespacePhp);
			}
			$out[] = $rendered;
		}
		return implode(', ', $out);
	}

	private function renderExecForwardArgs(array $params): string
	{
		return implode(', ', array_map(fn (ParamDecl $param): string => $this->localCppName($param->name), $params));
	}

	private function declaredTypeForExecParam(ParamDecl $param): ?string
	{
		if ($param->type === null) {
			return null;
		}
		if ($this->paramNeedsTemplateNormalization($param)) {
			return $param->primaryType ?? $param->type;
		}
		return $param->type;
	}

	private function renderTemplateLineForParams(array $params): string
	{
		$templateParams = [];
		foreach ($params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				$templateParams[] = 'typename ' . $this->renderTemplateTypeName($param);
			}
		}
		return 'template <' . implode(', ', $templateParams) . '>';
	}

	private function renderTemplateTypeName(ParamDecl $param): string
	{
		return 'T_' . $this->parameterCppName($param);
	}

	private function renderNormalizationHelperName(string $callableName, ParamDecl $param): string
	{
		return '_norm_' . $this->cppIdentifier($callableName) . '__' . $this->parameterCppName($param);
	}

	private function renderNormalizationRuleExpression(ArgNormalizationRule $rule, ParamDecl $param, string $sourceType, ?string $namespacePhp): string
	{
		try {
			$exprAst = $this->annotationExpressionParser->parse($rule->expression);
		} catch (\Throwable $e) {
			$this->errors[] = 'Failed to parse @arg normalization expression for $' . $param->name . ' from ' . $rule->sourceType . ' near doc line ' . $rule->line . ': ' . $e->getMessage();
			return '/* invalid-arg-normalization-expression */';
		}

		$prevDeclaredLocals = $this->declaredLocals;
		$prevDeclaredLocalTypes = $this->declaredLocalTypes;
		$prevReferenceLocals = $this->predefinedReferenceLocals;
		$this->declaredLocals[$param->name] = true;
		$this->declaredLocalTypes[$param->name] = $sourceType;
		$rendered = $this->renderExpr($exprAst, $namespacePhp);
		$this->declaredLocals = $prevDeclaredLocals;
		$this->declaredLocalTypes = $prevDeclaredLocalTypes;
		$this->predefinedReferenceLocals = $prevReferenceLocals;
		return $rendered;
	}

	private function renderNormalizationDirectBranchLines(ParamDecl $param, string $unionType, ?string $namespacePhp, bool $sourceIsMixed = false): array
	{
		$mappedPrimaryType = $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type);
		$mappedSourceType = $this->typeMapper->mapDeclaredType($unionType);
		$sourceExpr = '_' . $this->parameterCppName($param);
		if ($this->isSupportedScalarTemplateRefParam($param)) {
			if ($sourceIsMixed) {
				$accessor = $this->mapValueRefAccessorForParam($param);
				if ($accessor === null) {
					return ['throw std::runtime_error("Unsupported runtime kind for normalized parameter $' . addslashes($param->name) . '");'];
				}
				return ['return ' . $sourceExpr . '.' . $accessor . '();'];
			}

			if ($mappedPrimaryType === $mappedSourceType) {
				return ['return ' . $sourceExpr . ';'];
			}

			return ['throw std::runtime_error("Unsupported type for normalized parameter $' . addslashes($param->name) . '");'];
		}

		$rule = $this->lookupArgNormalizationRule($param, $unionType);
		if ($rule === null) {
			if (($param->primaryType ?? $param->type) === $unionType) {
				if ($sourceIsMixed) {
					return ['return ' . $this->renderGeneratedCast($mappedPrimaryType, $sourceExpr) . ';'];
				}
				return ['return ' . $sourceExpr . ';'];
			}
			return ['return ' . $this->renderGeneratedCast($mappedPrimaryType, $sourceExpr) . ';'];
		}
		$expr = $this->renderNormalizationRuleExpression($rule, $param, $unionType, $namespacePhp);
		return [
			$mappedSourceType . ' ' . $this->parameterCppName($param) . ' = ' . $this->renderGeneratedCast($mappedSourceType, $sourceExpr) . ';',
			'return ' . $expr . ';',
		];
	}

	private function renderNormalizationMixedBranchLines(ParamDecl $param, ?string $namespacePhp): array
	{
		$mappedPrimaryType = $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type);
		$lines = [];
		$first = true;
		foreach ($param->unionTypes as $unionType) {
			$condition = $this->renderUnionSourceKindCheck('_' . $param->name, $unionType);
			if ($condition === null) {
				continue;
			}
			$prefix = $first ? 'if' : 'else if';
			$first = false;
			$lines[] = $prefix . ' (' . $condition . ') {';
			foreach ($this->renderNormalizationDirectBranchLines($param, $unionType, $namespacePhp, true) as $line) {
				$lines[] = $this->indent(1) . $line;
			}
			$lines[] = '}';
		}
		$lines[] = 'throw std::runtime_error("Unsupported runtime kind for normalized parameter $' . addslashes($param->name) . '");';
		return $lines;
	}

	private function renderNormalizationHelperDefinition(string $callableName, ParamDecl $param, ?string $namespacePhp, bool $classInline = false): string
	{
		$primaryType = $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type);
		$templateType = $this->renderTemplateTypeName($param);
		$helperName = $this->renderNormalizationHelperName($callableName, $param);
		$returnType = $this->isSupportedScalarTemplateRefParam($param) ? ($primaryType . '&') : $primaryType;
		$prefix = $classInline ? 'static ' : '';
		$lines = [];
		$lines[] = 'template <typename ' . $templateType . '>';
		$lines[] = $prefix . $returnType . ' ' . $helperName . '(' . $templateType . '&& _' . $this->parameterCppName($param) . ') {';
		$lines[] = $this->indent(1) . 'using _norm_arg_t = std::remove_cv_t<std::remove_reference_t<' . $templateType . '>>;';
		$first = true;
		$directTypes = $this->isSupportedScalarTemplateRefParam($param)
			? [$param->primaryType ?? $param->type]
			: $param->unionTypes;
		foreach ($directTypes as $unionType) {
			$mappedSourceType = $this->typeMapper->mapDeclaredType($unionType);
			$prefixCond = $first ? 'if constexpr' : 'else if constexpr';
			$first = false;
			$lines[] = $this->indent(1) . $prefixCond . ' (std::is_same_v<_norm_arg_t, ' . $mappedSourceType . '>) {';
			foreach ($this->renderNormalizationDirectBranchLines($param, $unionType, $namespacePhp, false) as $line) {
				$lines[] = $this->indent(2) . $line;
			}
			$lines[] = $this->indent(1) . '}';
		}
		$lines[] = $this->indent(1) . 'else if constexpr (std::is_same_v<_norm_arg_t, mixed_t>) {';
		foreach ($this->renderNormalizationMixedBranchLines($param, $namespacePhp) as $line) {
			$lines[] = $this->indent(2) . $line;
		}
		$lines[] = $this->indent(1) . '} else {';
		$lines[] = $this->indent(2) . 'static_assert(!std::is_same_v<_norm_arg_t, _norm_arg_t>, "Unsupported type for normalized parameter");';
		$lines[] = $this->indent(1) . '}';
		$lines[] = '}';
		return implode("\n", $lines);
	}


	private function renderFunctionExecDeclaration(FunctionDecl $function, ?string $namespacePhp = null): string
	{
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		$paramPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		$this->beginFunctionLikeVariableMapping($function->params, $function->statements);
		try {
			return $returnType . ' ' . $this->renderExecCallableName($function->name) . '(' . $this->renderCanonicalParamsForExec($function->params, false, $namespacePhp, $paramPassModes) . ')';
		} finally {
			$this->endFunctionLikeVariableMapping();
		}
	}

	private function renderFunctionExecDefinition(FunctionDecl $function, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->predefinedReferenceLocals = [];
		$this->currentParamPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		$this->beginFunctionLikeVariableMapping($function->params, $function->statements);
		foreach ($function->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->isReference) {
				$this->predefinedReferenceLocals[$param->name] = true;
			}
			$declaredType = $this->declaredTypeForExecParam($param);
			if ($declaredType !== null) {
				$this->declaredLocalTypes[$param->name] = $declaredType;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($function->argNormalizationRules);
		$this->currentNormalizationCallableName = null;
		$this->currentParamEntryAliasLines = [];
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($function->params);
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		$this->currentReturnType = $returnType;
		$signature = $returnType . ' ' . $this->renderExecCallableName($function->name) . '(' . $this->renderCanonicalParamsForExec($function->params, false, $namespacePhp, $this->currentParamPassModes) . ')';
		$bodyLines = $this->renderBody($function->statements, $namespacePhp);
		array_unshift($bodyLines, $this->codeWithCurrentOrigin($this->indent(1) . $this->renderCallDepthGuardLine($this->renderExecCallableName($function->name), $function->line)));
		$body = implode("\n", $this->flattenCodeText($bodyLines));
		$this->currentReturnType = null;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		$this->endFunctionLikeVariableMapping();
		return $signature . " {
" . $body . "
}";
	}

	private function renderFunctionTemplateWrapperDefinition(FunctionDecl $function, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->predefinedReferenceLocals = [];
		$this->currentParamPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		$this->beginFunctionLikeVariableMapping($function->params, $function->statements);
		foreach ($function->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->isReference) {
				$this->predefinedReferenceLocals[$param->name] = true;
			}
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($function->argNormalizationRules);
		$this->currentNormalizationCallableName = $function->name;
		$this->currentParamEntryAliasLines = $this->buildParamEntryAliasLines($function->params);
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($function->params);
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		$this->currentReturnType = $returnType;
		$signature = $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')';
		$lines = array_merge($this->renderCurrentParamEntryAliases(), $this->renderCurrentScalarRefParamAliases());
		$execCall = $this->renderExecCallableName($function->name) . '(' . $this->renderExecForwardArgs($function->params) . ')';
		$lines[] = $this->indent(1) . ($returnType === 'void' ? $execCall . ';' : 'return ' . $execCall . ';');
		$body = implode("
", $lines);
		$this->currentReturnType = null;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		$this->endFunctionLikeVariableMapping();
		return $signature . " {
" . $body . "
}";
	}

	private function renderFunctionTemplateArtifactsWithExecSplit(FunctionDecl $function, ?string $namespacePhp): array
	{
		$prevRules = $this->currentArgNormalizationRulesByKey;
		$prevCallable = $this->currentNormalizationCallableName;
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($function->argNormalizationRules);
		$this->currentNormalizationCallableName = $function->name;

		$lines = [];
		foreach ($function->params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				$lines[] = $this->renderNormalizationHelperDefinition($function->name, $param, $namespacePhp, false);
				$lines[] = '';
			}
		}
		$lines[] = $this->renderFunctionExecDeclaration($function, $namespacePhp) . ';';
		$lines[] = '';
		$lines[] = $this->renderTemplateLineForParams($function->params);
		$lines[] = $this->renderFunctionTemplateWrapperDefinition($function, $namespacePhp);

		$this->currentArgNormalizationRulesByKey = $prevRules;
		$this->currentNormalizationCallableName = $prevCallable;
		return $lines;
	}

	private function renderMethodExecDeclaration(ClassDecl $class, MethodDecl $method, ?string $namespacePhp = null): string
	{
		$paramPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		$prefix = $method->isStatic ? 'static ' : '';
		return $prefix . $returnType . ' ' . $this->renderExecCallableName($method->name) . '(' . $this->renderCanonicalParamsForExec($method->params, false, $namespacePhp, $paramPassModes) . ')';
	}

	private function renderMethodExecDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->predefinedReferenceLocals = [];
		$this->currentParamPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$this->beginFunctionLikeVariableMapping($method->params, $method->statements);
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->isReference) {
				$this->predefinedReferenceLocals[$param->name] = true;
			}
			$declaredType = $this->declaredTypeForExecParam($param);
			if ($declaredType !== null) {
				$this->declaredLocalTypes[$param->name] = $declaredType;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($method->argNormalizationRules);
		$this->currentNormalizationCallableName = null;
		$this->currentParamEntryAliasLines = [];
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($method->params);
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		$this->currentReturnType = $returnType;
		$signature = $returnType . ' ' . $class->name . '::' . $this->renderExecCallableName($method->name) . '(' . $this->renderCanonicalParamsForExec($method->params, false, $namespacePhp, $this->currentParamPassModes) . ')';
		$body = implode("\n", $this->flattenCodeText($this->renderBody($method->statements, $namespacePhp)));
		$this->currentReturnType = null;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		$this->endFunctionLikeVariableMapping();
		return $signature . " {
" . $body . "
}";
	}

	private function renderInlineTemplateMethodArtifactsWithExecSplit(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): array
	{
		$prevRules = $this->currentArgNormalizationRulesByKey;
		$prevCallable = $this->currentNormalizationCallableName;
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($method->argNormalizationRules);
		$this->currentNormalizationCallableName = $method->name;

		$lines = [];
		foreach ($method->params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				foreach (explode("
", $this->renderNormalizationHelperDefinition($method->name, $param, $namespacePhp, true)) as $line) {
					$lines[] = $line;
				}
				$lines[] = '';
			}
		}
		$lines[] = $this->renderMethodExecDeclaration($class, $method, $namespacePhp) . ';';
		$lines[] = '';
		foreach (explode("
", $this->renderTemplateLineForParams($method->params)) as $line) {
			$lines[] = $line;
		}
		foreach (explode("
", $this->renderInlineMethodDefinitionWithExecSplit($class, $method, $namespacePhp)) as $line) {
			$lines[] = $line;
		}

		$this->currentArgNormalizationRulesByKey = $prevRules;
		$this->currentNormalizationCallableName = $prevCallable;
		return $lines;
	}

	private function renderInlineMethodDefinitionWithExecSplit(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->currentParamPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$this->beginFunctionLikeVariableMapping($method->params, $method->statements);
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->isReference) {
				$this->predefinedReferenceLocals[$param->name] = true;
			}
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($method->argNormalizationRules);
		$this->currentNormalizationCallableName = $method->name;
		$this->currentParamEntryAliasLines = $this->buildParamEntryAliasLines($method->params);
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($method->params);
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		$this->currentReturnType = $returnType;
		$prefix = $method->isStatic ? 'static ' : '';
		$signature = $prefix . $returnType . ' ' . $this->cppIdentifier($method->name) . '(' . $this->renderParams($method->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')';
		$lines = array_merge($this->renderCurrentParamEntryAliases(), $this->renderCurrentScalarRefParamAliases());
		$execCall = $this->renderExecCallableName($method->name) . '(' . $this->renderExecForwardArgs($method->params) . ')';
		$lines[] = $this->indent(1) . ($returnType === 'void' ? $execCall . ';' : 'return ' . $execCall . ';');
		$body = implode("
", $lines);
		$this->currentReturnType = null;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		$this->endFunctionLikeVariableMapping();
		return $signature . " {
" . $body . "
}";
	}

	private function renderFunctionTemplateArtifacts(FunctionDecl $function, ?string $namespacePhp): array
	{
		$prevRules = $this->currentArgNormalizationRulesByKey;
		$prevCallable = $this->currentNormalizationCallableName;
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($function->argNormalizationRules);
		$this->currentNormalizationCallableName = $function->name;

		$lines = [];
		foreach ($function->params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				$lines[] = $this->renderNormalizationHelperDefinition($function->name, $param, $namespacePhp, false);
				$lines[] = '';
			}
		}
		$lines[] = $this->renderTemplateLineForParams($function->params);
		foreach ($this->flattenCodeText($this->renderFunctionDefinition($function, $namespacePhp)) as $line) {
			$lines[] = $line;
		}

		$this->currentArgNormalizationRulesByKey = $prevRules;
		$this->currentNormalizationCallableName = $prevCallable;
		return $lines;
	}

	private function renderInlineTemplateMethodArtifacts(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): array
	{
		$prevRules = $this->currentArgNormalizationRulesByKey;
		$prevCallable = $this->currentNormalizationCallableName;
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($method->argNormalizationRules);
		$this->currentNormalizationCallableName = $method->name;

		$lines = [];
		foreach ($method->params as $param) {
			if ($this->paramNeedsTemplateNormalization($param)) {
				foreach (explode("\n", $this->renderNormalizationHelperDefinition($method->name, $param, $namespacePhp, true)) as $line) {
					$lines[] = $line;
				}
				$lines[] = '';
			}
		}
		foreach (explode("\n", $this->renderTemplateLineForParams($method->params)) as $line) {
			$lines[] = $line;
		}
		foreach (explode("\n", $this->renderInlineMethodDefinition($class, $method, $namespacePhp)) as $line) {
			$lines[] = $line;
		}

		$this->currentArgNormalizationRulesByKey = $prevRules;
		$this->currentNormalizationCallableName = $prevCallable;
		return $lines;
	}

	private function renderInlineMethodDefinition(ClassDecl $class, MethodDecl $method, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->currentParamPassModes = $this->analyzeParamPassModes($method->params, $method->statements);
		$this->beginFunctionLikeVariableMapping($method->params, $method->statements);
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->isReference) {
				$this->predefinedReferenceLocals[$param->name] = true;
			}
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($method->argNormalizationRules);
		$this->currentNormalizationCallableName = $method->name;
		$this->currentParamEntryAliasLines = $this->buildParamEntryAliasLines($method->params);
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($method->params);
		$returnType = $this->resolveDeclaredReturnType($method->returnType, $method->returnsByReference, 'Method ' . $this->cppIdentifier($method->name));
		$this->currentReturnType = $returnType;
		$prefix = $method->isStatic ? 'static ' : '';
		$signature = $prefix . $returnType . ' ' . $this->cppIdentifier($method->name) . '(' . $this->renderParams($method->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')';
		$body = implode("\n", $this->flattenCodeText($this->renderBody($method->statements, $namespacePhp)));
		$this->currentReturnType = null;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		$this->endFunctionLikeVariableMapping();
		return $signature . " {\n" . $body . "\n}";
	}

	private function renderFunctionDeclaration(FunctionDecl $function, ?string $namespacePhp = null): string
	{
		$returnType = $this->renderFunctionReturnType($function);
		$paramPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		$this->beginFunctionLikeVariableMapping($function->params, $function->statements);
		try {
			return $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, true, $namespacePhp, $paramPassModes) . ')';
		} finally {
			$this->endFunctionLikeVariableMapping();
		}
	}

	/**

	 * Renders one full function body for the generated source file.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	/** @return list<CodeBlock> */
	private function renderFunctionDefinition(FunctionDecl $function, ?string $namespacePhp): array
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		$this->predefinedReferenceLocals = [];
		$this->currentParamPassModes = $this->analyzeParamPassModes($function->params, $function->statements);
		$this->beginFunctionLikeVariableMapping($function->params, $function->statements);
		foreach ($function->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->isReference) {
				$this->predefinedReferenceLocals[$param->name] = true;
			}
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$this->currentArgNormalizationRulesByKey = $this->indexArgNormalizationRules($function->argNormalizationRules);
		$this->currentNormalizationCallableName = $this->functionLikeNeedsNormalizedTemplate($function->params) ? $function->name : null;
		$this->currentParamEntryAliasLines = $this->buildParamEntryAliasLines($function->params);
		$this->currentScalarRefParamAliasLines = $this->buildScalarRefParamAliasLines($function->params);
		$returnType = $this->renderFunctionReturnType($function);
		$this->currentReturnType = $function->isAsync
			? $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name)
			: $returnType;
		$this->currentFunctionIsAsync = $function->isAsync;
		$signature = $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, false, $namespacePhp, $this->currentParamPassModes, true) . ')';
		$body = $this->renderBody($function->statements, $namespacePhp);
		array_unshift($body, $this->codeWithCurrentOrigin($this->indent(1) . $this->renderCallDepthGuardLine($function->name, $function->line)));
		$this->currentReturnType = null;
		$this->currentFunctionIsAsync = false;
		$this->currentFinallyReturnContext = null;
		$this->currentParamPassModes = [];
		$this->currentScalarRefParamAliasLines = [];
		$this->currentParamEntryAliasLines = [];
		$this->currentArgNormalizationRulesByKey = [];
		$this->currentNormalizationCallableName = null;
		$this->endFunctionLikeVariableMapping();
		return array_merge(
			[$this->code($signature . ' {', $function->line)],
			$body,
			[$this->code('}', $function->line)],
		);
	}

	private function renderFunctionReturnType(FunctionDecl $function): string
	{
		if ($function->isAsync && $function->returnsByReference) {
			$this->fail('Async function ' . $function->name . ' cannot return by reference.');
		}
		$returnType = $this->resolveDeclaredReturnType($function->returnType, $function->returnsByReference, 'Function ' . $function->name);
		if (!$function->isAsync) {
			return $returnType;
		}
		return $returnType === 'void'
			? 'scpp::async_core::task<void>'
			: 'scpp::async_core::task<' . $returnType . '>';
	}

	/**

	 * Renders the lowered parameter list, optionally including default expressions when a declaration requires them.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderParams(array $params, bool $includeDefaults, ?string $namespacePhp, array $paramPassModes = [], bool $useStorageNames = false): string
	{
		$out = [];
		foreach ($params as $param) {
			if ($param->isVariadic) {
				$elementType = $param->type !== null ? $this->typeMapper->mapDeclaredType($param->type) : '/* ERROR missing-variadic-element-type */';
				$type = 'const vector_t<' . $elementType . '>&';
			} else {
				$type = $param->type !== null ? $this->renderParamTypeForMode($param, $paramPassModes[$param->name] ?? 'readonly') : '/* ERROR missing-parameter-type */';
			}
			$name = $this->renderParamName($param, $useStorageNames);
			if ($this->paramNeedsTemplateNormalization($param) && !$useStorageNames) {
				$name = '_' . $this->localCppName($param->name);
			}
			$rendered = $type . ' ' . $name;
			if (!$param->isVariadic && $includeDefaults && $param->default !== null) {
				$rendered .= ' = ' . $this->renderExpr($param->default, $namespacePhp);
			}
			$out[] = $rendered;
		}
		return implode(', ', $out);
	}

	/**

	 * Renders a list of lowered statements as an indented C++ block body.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderParamTypeForMode(ParamDecl $param, string $mode): string
	{
		if ($param->type === null) {
			return '/* ERROR missing-parameter-type */';
		}

		if ($this->paramNeedsTemplateNormalization($param)) {
			return $this->renderTemplateTypeName($param) . '&&';
		}

		$qualifiedType = $this->qualifyDeclaredPhpType($param->type, $this->currentNamespacePhp);
		$mapped = $this->typeMapper->mapDeclaredType($qualifiedType);
		if ($param->isReference) {
			$proxyType = $this->typeMapper->mapReferenceProxyType($qualifiedType);
			if ($proxyType !== null) {
				return $proxyType;
			}
			return $this->typeMapper->mapParamType($qualifiedType, true);
		}

		if ($mode === 'owned_local' && ($mapped === 'string_t' || $mapped === 'mixed_t' || str_starts_with($mapped, 'vector_t<') || str_starts_with($mapped, 'fixed_array_t<') || str_starts_with($mapped, 'hash_t<'))) {
			return $mapped;
		}

		return $this->typeMapper->mapParamType($qualifiedType, false);
	}

	/** @param list<Statement> $statements @return list<CodeBlock> */
	private function renderBody(array $statements, ?string $namespacePhp): array
	{
		$lines = $this->codeLinesFromStrings(array_merge($this->renderCurrentParamEntryAliases(), $this->renderCurrentScalarRefParamAliases(), $this->renderCurrentArrayParamGuards()));
		foreach ($this->renderStatementSequence($statements, $namespacePhp) as $line) {
			$lines[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
		}
		return $lines;
	}

	/** @return list<string> */
	private function renderCurrentParamEntryAliases(): array
	{
		return $this->currentParamEntryAliasLines;
	}

	/**
	 * Emits entry guards for PHP array / ?array parameters lowered to mixed_t.
	 *
	 * @return list<string>
	 */
	private function renderCurrentScalarRefParamAliases(): array
	{
		return $this->currentScalarRefParamAliasLines;
	}

	private function buildScalarRefParamAliasLines(array $params): array
	{
		// Scalar typed by-reference parameters now normalize through the template
		// entry aliases instead of proxy storage or sibling bridge overloads.
		return [];
	}

	/** @param list<ParamDecl> $params @return list<string> */
	private function buildParamEntryAliasLines(array $params): array
	{
		$lines = [];
		foreach ($params as $param) {
			$storageName = $this->renderParamStorageName($param);
			if ($this->paramNeedsTemplateNormalization($param)) {
				$primaryType = $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type);
				$binding = $param->isReference ? ($primaryType . '&') : $primaryType;
				$callableName = $this->currentNormalizationCallableName ?? 'callable';
				$lines[] = $binding . ' ' . $this->localCppName($param->name) . ' = ' . $this->renderNormalizationHelperName($callableName, $param) . '(std::forward<' . $this->renderTemplateTypeName($param) . '>(' . $storageName . '));';
				continue;
			}
			if ($storageName === $this->localCppName($param->name)) {
				continue;
			}
			$binding = $param->isReference ? 'auto&' : 'auto';
			$lines[] = $binding . ' ' . $this->localCppName($param->name) . ' = ' . $storageName . ';';
		}
		return $lines;
	}

	/** @return list<string> */
	private function renderSyntheticMainCliPreamble(): array
	{
		return [
			'int_t<> argc = php::cli_argc();',
			'mixed_t argv = php::cli_argv();',
		];
	}

	private function seedSyntheticMainCliLocals(): void
	{
		$this->declaredLocals['argc'] = true;
		$this->declaredLocals['argv'] = true;
		$this->declaredLocalTypes['argc'] = 'int';
		$this->declaredLocalTypes['argv'] = 'mixed';
	}

	private function renderParamName(ParamDecl $param, bool $useStorageNames): string
	{
		return $useStorageNames ? $this->renderParamStorageName($param) : $this->localCppName($param->name);
	}

	private function renderParamStorageName(ParamDecl $param): string
	{
		if (!$this->paramNeedsGeneratedStorageName($param)) {
			return $this->localCppName($param->name);
		}

		return '_' . $this->localCppName($param->name);
	}


	/** @param list<ArgNormalizationRule> $rules @return array<string, ArgNormalizationRule> */
	private function indexArgNormalizationRules(array $rules): array
	{
		$indexed = [];
		foreach ($rules as $rule) {
			$indexed[$rule->paramName . '|' . $rule->sourceType] = $rule;
		}
		return $indexed;
	}

	private function lookupArgNormalizationRule(ParamDecl $param, string $sourceType): ?ArgNormalizationRule
	{
		return $this->currentArgNormalizationRulesByKey[$param->name . '|' . $sourceType] ?? null;
	}

	private function paramNeedsGeneratedStorageName(ParamDecl $param): bool
	{
		return $this->paramNeedsTemplateNormalization($param);
	}

	private function renderUnionSourceKindCheck(string $storageName, string $sourceType): ?string
	{
		$normalized = ltrim(trim($sourceType), '?');
		return match ($normalized) {
			'null' => $storageName . '.kind() == mixed_t::kind_t::null_v',
			'bool' => 'static_cast<bool>(' . $storageName . '.is_bool())',
			'int' => 'static_cast<bool>(' . $storageName . '.is_int())',
			'float' => 'static_cast<bool>(' . $storageName . '.is_float())',
			'string' => 'static_cast<bool>(' . $storageName . '.is_string())',
			default => null,
		};
	}

	private function renderForwardedNormalizationExpression(ArgNormalizationRule $rule, ParamDecl $param, string $storageName, ?string $namespacePhp): string
	{
		$mappedSourceType = $this->typeMapper->mapDeclaredType($rule->sourceType);
		$expression = $this->renderNormalizationRuleExpression($rule, $param, $rule->sourceType, $namespacePhp);
		return '([&]() -> ' . $this->typeMapper->mapDeclaredType($param->primaryType ?? $param->type) . ' { ' . $mappedSourceType . ' ' . $this->parameterCppName($param) . ' = ' . $this->renderGeneratedCast($mappedSourceType, $storageName) . '; return ' . $expression . '; })()';
	}

	private function buildMixedCarrierNormalizationLines(ParamDecl $param, string $storageName, ?string $namespacePhp): array
	{
		$primaryType = $param->primaryType ?? $param->type;
		$mappedPrimaryType = $primaryType !== null ? $this->typeMapper->mapDeclaredType($primaryType) : 'mixed_t';
		$lines = [$mappedPrimaryType . ' ' . $this->parameterCppName($param) . ';'];
		$firstBranch = true;
		foreach ($param->unionTypes as $unionType) {
			$condition = $this->renderUnionSourceKindCheck($storageName, $unionType);
			if ($condition === null) {
				continue;
			}
			$branchPrefix = $firstBranch ? 'if' : 'else if';
			$firstBranch = false;
			$rule = $this->lookupArgNormalizationRule($param, $unionType);
			$assignmentExpr = $rule !== null
				? $this->renderForwardedNormalizationExpression($rule, $param, $storageName, $namespacePhp)
					: $this->renderGeneratedCast($mappedPrimaryType, $storageName);
			$lines[] = $branchPrefix . ' (' . $condition . ') {';
			$lines[] = $this->indent(1) . $this->parameterCppName($param) . ' = ' . $assignmentExpr . ';';
			$lines[] = '}';
		}
		$lines[] = 'else {';
		$lines[] = $this->indent(1) . 'throw std::runtime_error("Unsupported runtime kind for normalized parameter $' . addslashes($param->name) . '");';
		$lines[] = '}';
		return $lines;
	}

	private function renderCurrentArrayParamGuards(): array
	{
		$lines = [];
		foreach ($this->declaredLocalTypes as $name => $declaredType) {
			if (!$this->isPhpArrayLikeDeclaredType($declaredType)) {
				continue;
			}
			$lines[] = 'php::expect_array_argument(' . $name . ', ' . ($this->isNullablePhpArrayDeclaredType($declaredType) ? 'true' : 'false') . ', "' . addslashes($name) . '");';
		}
		return $lines;
	}

	private function isPhpArrayLikeDeclaredType(string $declaredType): bool
	{
		$normalized = trim($declaredType);
		return $normalized === 'array' || $normalized === '?array';
	}

	private function isNullablePhpArrayDeclaredType(string $declaredType): bool
	{
		return trim($declaredType) === '?array';
	}

	/**

	 * Renders one lowered statement kind into one or more C++ source lines.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderStatement(Statement $statement, ?string $namespacePhp): array
	{
		if ($statement->kind === 'declare_local') {
			$name = (string) ($statement->payload['name'] ?? '');
			$typed = (string) ($statement->payload['type'] ?? '');
			if ($name === '' || $typed === '') {
				$this->fail('Typed local declaration is missing its name or type at line ' . $statement->line . '.');
			}
			if (isset($this->declaredLocals[$name])) {
				$this->fail('Variable $' . $name . ' is already declared in this block at line ' . $statement->line . '.');
			}
			$this->declaredLocals[$name] = true;
			$this->declaredLocalTypes[$name] = $this->normalizeStoredLocalType($typed);
			return $this->statementCodeLines($statement, [$this->typeMapper->mapTypedLocalType($typed) . ' ' . $this->localCppName($name) . ';']);
		}
		if ($statement->kind === 'assign' || $statement->kind === 'assign_ref' || $statement->kind === 'assign_op') {
			$varNode = $statement->payload['var'] ?? null;
			$exprNode = $statement->payload['expr'] ?? null;
			$name = $this->extractSimpleVarName($varNode);
			$key = $statement->line . ':' . ($name ?? '');
			$typed = $name !== null ? ($this->localTypeComments[$key] ?? null) : null;

			if ($statement->kind === 'assign_op') {
				return $this->renderCompoundAssignmentStatement($statement, $varNode, $exprNode, $name, $namespacePhp);
			}

			if ($statement->kind === 'assign' && $name !== null && !isset($this->declaredLocals[$name]) && !$this->hasForeachReferenceSlotAlias($name)) {
				$chainLines = $this->tryRenderDeclarationAssignChain($varNode, $exprNode, $typed, $namespacePhp);
				if ($chainLines !== null) {
					return $this->statementCodeLines($statement, $chainLines);
				}
			}

			$effectiveTyped = $typed;
			if ($typed !== null) {
				[$effectiveTyped, $validationError] = $this->resolveTypedLocalTypeForAssignment($typed, $statement->kind, $exprNode, $statement->line);
				if ($validationError !== null) {
					$this->fail($validationError);
				}
				$validationError = $this->validateTypedLocalAssignment($effectiveTyped, $statement->kind, $exprNode, $statement->line);
				if ($validationError !== null) {
					$this->fail($validationError);
				}
			}

			$storedTyped = ($statement->kind === 'assign' && $name !== null)
				? ($this->declaredLocalTypes[$name] ?? null)
				: null;
			$initializerTyped = $effectiveTyped;
			if (
				$statement->kind === 'assign'
				&& $initializerTyped === null
				&& is_string($storedTyped)
				&& $storedTyped !== ''
				&& is_object($exprNode)
				&& (($exprNode->kind ?? null) === AstKind::ARRAY)
			) {
				$initializerTyped = $storedTyped;
			}

			$expr = $statement->kind === 'assign_ref'
				? $this->renderReferenceBindingExpr($exprNode, $namespacePhp)
				: $this->renderInitializerExpr($exprNode, $initializerTyped, $namespacePhp);
			if ($statement->kind === 'assign' && $effectiveTyped === null && $name !== null) {
				if (is_string($storedTyped) && $storedTyped !== '') {
					$mappedStoredType = $this->mapStoredLocalTypeToMappedType($storedTyped);
					if ($mappedStoredType !== null) {
						$expr = $this->wrapExprForExpectedType($expr, $this->inferExprType($exprNode), $mappedStoredType);
					}
				}
			}
			$typedVectorType = $effectiveTyped !== null ? $this->mapTypedVectorLocalType($effectiveTyped) : null;
			$typedFixedArrayType = $effectiveTyped !== null ? $this->mapTypedFixedArrayLocalType($effectiveTyped) : null;
			$typedHashType = $effectiveTyped !== null ? $this->mapTypedHashLocalType($effectiveTyped) : null;
			$typedArrayContainerType = $typedVectorType ?? $typedFixedArrayType ?? $typedHashType;
			$isTypedEmptyArrayLiteral = $statement->kind === 'assign' && $typedArrayContainerType !== null && $this->isEmptyPositionalArrayLiteral($exprNode);
			if ($statement->kind === 'assign_ref') {
				if ($name === null) {
					$error = 'reference assignment requires a fresh simple local target at line ' . $statement->line . '.';
					$this->fail($error);
				}

				if (isset($this->declaredLocals[$name])) {
					$error = 'reference rebinding is not supported for $' . $name . ' at line ' . $statement->line . '.';
					$this->fail($error);
				}

				$this->declaredLocals[$name] = true;
				$this->predefinedReferenceLocals[$name] = true;
				if ($effectiveTyped !== null) {
					$this->declaredLocalTypes[$name] = $this->normalizeStoredLocalType($effectiveTyped);
					return $this->statementCodeLines($statement, [$this->typeMapper->mapTypedLocalType($effectiveTyped) . ' ' . $this->localCppName($name) . ' = ' . $expr . ';']);
				}

				return $this->statementCodeLines($statement, ['auto& ' . $this->localCppName($name) . ' = ' . $expr . ';']);
			}

			if ($name !== null && !isset($this->declaredLocals[$name]) && !$this->hasForeachReferenceSlotAlias($name)) {
				$this->declaredLocals[$name] = true;
				$this->trackAssignedArrayShape($name, $exprNode, $effectiveTyped);
				$closureFunctionType = $effectiveTyped === null ? $this->tryInferStdFunctionTypeFromClosureExpr($exprNode) : null;
				$inferredType = $effectiveTyped ?? $closureFunctionType ?? $this->inferExprTypeWithNamespace($exprNode, $namespacePhp);
				if ($inferredType !== 'auto') {
					$this->declaredLocalTypes[$name] = $effectiveTyped !== null ? $this->normalizeStoredLocalType($effectiveTyped) : $inferredType;
				}
				if ($effectiveTyped !== null) {
					if ($isTypedEmptyArrayLiteral && $typedFixedArrayType === null) {
						return $this->statementCodeLines($statement, [$typedArrayContainerType . ' ' . $this->localCppName($name) . ' = {};']);
					}
					$mappedLocalType = $this->typeMapper->mapTypedLocalType($effectiveTyped);
					$initializer = is_object($exprNode) && in_array(($exprNode->kind ?? null), [AstKind::CLOSURE, AstKind::ARROW_FUNC], true)
						? $expr
						: $this->renderRequiredTypedBoundaryCast($mappedLocalType, $expr);
					return $this->statementCodeLines($statement, [$mappedLocalType . ' ' . $this->localCppName($name) . ' = ' . $initializer . ';']);
				}
				if ($closureFunctionType !== null) {
					return $this->statementCodeLines($statement, [$closureFunctionType . ' ' . $this->localCppName($name) . ' = ' . $expr . ';']);
				}
				$declarationType = $this->inferFirstAssignmentDeclarationType($exprNode, $inferredType);
				return $this->statementCodeLines($statement, [$declarationType . ' ' . $this->localCppName($name) . ' = ' . $expr . ';']);
			}
			if (is_object($varNode) && (($varNode->kind ?? null) === AstKind::DIM)) {
				if (is_object($exprNode) && in_array(($exprNode->kind ?? null), [AstKind::CLOSURE, AstKind::ARROW_FUNC], true)) {
					$this->errors[] = 'Closures cannot be stored in array or dynamic container slots at line ' . $statement->line . '. Assign the closure to a concrete local callable instead.';
					return $this->statementCodeLines($statement, ['/* unsupported-closure-container-assignment */']);
				}
				if (($varNode->children['dim'] ?? null) === null) {
					$baseExpr = $varNode->children['expr'] ?? null;
					$base = is_object($baseExpr) && (($baseExpr->kind ?? null) === AstKind::DIM)
						? $this->renderDimWriteAccess($baseExpr, $namespacePhp)
						: $this->renderExpr($baseExpr, $namespacePhp);
					$value = $this->renderExpr($exprNode, $namespacePhp);
					$baseType = $this->inferExprType($baseExpr);
					$appendBase = $base;
					if ($this->isUntypedTableHandleType($baseType)) {
						$appendBase = '(' . $base . ')';
					}
					$appendMethod = preg_match('/^vector_t<(.+)>$/', $baseType) === 1 ? '.push_back' : ($this->isUntypedTableHandleType($baseType) ? '->append' : '.append');
					$appendValue = $value;
					if ($this->shouldInlineAssignmentValue($exprNode)) {
						return $this->statementCodeLines($statement, ['(void) ' . $appendBase . $appendMethod . '(' . $appendValue . ');']);
					}

					$tempName = $this->nextTempName('__append_value');
					$storedTemp = $tempName;
					return $this->statementCodeLines($statement, [
						'{',
							'auto ' . $tempName . ' = ' . $value . ';',
							'(void) ' . $appendBase . $appendMethod . '(' . $storedTemp . ');',
						'}',
					]);
				}
				$target = $this->renderDimWriteAccess($varNode, $namespacePhp);
				$value = $this->renderExpr($exprNode, $namespacePhp);
				return $this->statementCodeLines($statement, [$target . ' = ' . $value . ';']);
			}
			if ($isTypedEmptyArrayLiteral && $name !== null) {
				return $this->statementCodeLines($statement, [$this->localCppName($name) . ' = ' . $typedArrayContainerType . '{};']);
			}
			$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
			return $this->statementCodeLines($statement, [$target . ' = ' . $expr . ';']);
		}

		if ($statement->kind === 'static_var') {
			$varNode = $statement->payload['var'] ?? null;
			$name = (string) (($varNode->children['name'] ?? '') ?: 'tmp');
			$default = $this->renderExpr($statement->payload['default'] ?? null, $namespacePhp);
			$this->declaredLocals[$name] = true;
			return $this->statementCodeLines($statement, ['static int_t<> ' . $this->localCppName($name) . ' = ' . $default . ';']);
		}

		if ($statement->kind === 'return') {
			if ($this->currentFinallyReturnContext !== null) {
				return $this->renderFinallyAwareReturnStatement($statement, $namespacePhp);
			}
			if ($statement->payload === null) {
				return $this->statementCodeLines($statement, [$this->currentFunctionIsAsync ? 'co_return;' : 'return;']);
			}
			$returnKeyword = $this->currentFunctionIsAsync ? 'co_return ' : 'return ';
			return $this->statementCodeLines($statement, [$returnKeyword . $this->renderReturnExpr($statement->payload, $namespacePhp) . ';']);
		}

		if ($statement->kind === 'throw') {
			return $this->statementCodeLines($statement, ['throw ::scpp::php::make_thrown(' . $this->renderExpr($statement->payload, $namespacePhp) . ');']);
		}

		if ($statement->kind === 'try') {
			return $this->renderTryStatement($statement, $namespacePhp);
		}

		if ($statement->kind === 'echo') {
			return $this->statementCodeLines($statement, [$this->qualifyKnownPhpRuntimeSymbol('echo_one') . '(' . $this->renderExpr($statement->payload, $namespacePhp) . ');']);
		}

		if ($statement->kind === 'unset') {
			$targetNode = $statement->payload;
			if (is_object($targetNode) && (($targetNode->kind ?? null) === AstKind::DIM) && (($targetNode->children['dim'] ?? null) !== null)) {
				$baseExpr = $targetNode->children['expr'] ?? null;
				$base = $this->renderExpr($baseExpr, $namespacePhp);
				$dim = $this->renderExpr($targetNode->children['dim'] ?? null, $namespacePhp);
				$baseType = $this->inferExprType($baseExpr);
				if (preg_match('/^vector_t<(.+)>$/', $baseType) === 1) {
					return $this->statementCodeLines($statement, ['unset_keyed(' . $base . ', ' . $dim . ');']);
				}
				if ($baseType === 'mixed_t') {
					$shape = $this->inferForeachByRefSourceShape($baseExpr);
					if ($shape !== 'non_vector') {
						return $this->statementCodeLines($statement, ['unset_keyed(' . $base . ', ' . $dim . ');']);
					}
				}
				return $this->statementCodeLines($statement, [$base . '.remove(' . $dim . ');']);
			}
			// Preserve the generic runtime fallback for non-array/table forms.
			return $this->statementCodeLines($statement, ['unset(' . $this->renderExpr($statement->payload, $namespacePhp) . ');']);
		}

		if ($statement->kind === 'if') {
			return $this->renderIfStatement($statement->payload, $namespacePhp);
		}

		if ($statement->kind === 'while') {
			$conditionNode = $statement->payload['cond'] ?? null;
			$conditionHints = $this->conditionVisibilityHintsForExpr($conditionNode);
			$lines = [$this->code('while (' . $this->renderConditionExpr($conditionNode, $namespacePhp) . ') {', $statement->line)];
			foreach ($this->renderNestedStatementsWithConditionHints($statement->payload['stmts'] ?? [], $namespacePhp, $conditionHints) as $line) {
				$lines[] = $line;
			}
			$lines[] = $this->code('}', $statement->line);
			return $lines;
		}

		if ($statement->kind === 'do_while') {
			$lines = [$this->code('do {', $statement->line)];
			$conditionHints = $this->conditionVisibilityHintsForExpr($statement->payload['cond'] ?? null);
			foreach ($this->renderNestedStatementsWithConditionHints($statement->payload['stmts'] ?? [], $namespacePhp, $conditionHints) as $line) {
				$lines[] = $line;
			}
			$lines[] = $this->code('} while (' . $this->renderConditionExpr($statement->payload['cond'] ?? null, $namespacePhp) . ');', $statement->line);
			return $lines;
		}

		if ($statement->kind === 'for') {
			$scopedLocals = $this->declaredLocals;
			$scopedLocalTypes = $this->declaredLocalTypes;
			$scopedReferenceLocals = $this->predefinedReferenceLocals;
			$init = $this->renderForInit($statement->payload['init'] ?? [], $namespacePhp);
			$conditionHints = $this->conditionVisibilityHintsForExpr($statement->payload['cond'] ?? []);
			$cond = $this->renderForConditionClause($statement->payload['cond'] ?? [], $namespacePhp);
			$loop = $this->renderForClause($statement->payload['loop'] ?? [], $namespacePhp, '');
			$lines = [$this->code('for (' . $init . '; ' . $cond . '; ' . $loop . ') {', $statement->line)];
			foreach ($this->renderNestedStatementsWithConditionHints($statement->payload['stmts'] ?? [], $namespacePhp, $conditionHints) as $line) {
				$lines[] = $line;
			}
			$lines[] = $this->code('}', $statement->line);
			$this->declaredLocals = $scopedLocals;
			$this->declaredLocalTypes = $scopedLocalTypes;
			$this->predefinedReferenceLocals = $scopedReferenceLocals;
			return $lines;
		}

		if ($statement->kind === 'foreach') {
			return $this->renderForeachStatement($statement, $namespacePhp);
		}

		if ($statement->kind === 'switch') {
			$lines = [$this->code('switch (' . $this->renderSwitchExpr($statement->payload['cond'] ?? null, $namespacePhp) . ') {', $statement->line)];
			foreach (($statement->payload['cases'] ?? []) as $case) {
				$caseCond = $case['cond'] ?? null;
				// Each lowered switch case is emitted in source order so generated case/default blocks preserve the catalog shape.
				$caseLine = (int) ($case['line'] ?? $statement->line);
				$lines[] = $this->code($caseCond === null
					? $this->indent(1) . 'default:'
					: $this->indent(1) . 'case ' . $this->renderSwitchCaseValue($caseCond) . ':', $caseLine);
				foreach ($this->renderNestedStatements($case['stmts'] ?? [], $namespacePhp) as $line) {
					$lines[] = $line;
				}
			}
			$lines[] = $this->code('}', $statement->line);
			return $lines;
		}

		if ($statement->kind === 'break') {
			$depth = $statement->payload;
			if (!$this->isSimpleUnitLoopDepth($depth)) {
				$this->fail('Only break 1 is supported; break depth expressions and depths greater than 1 are not supported at line ' . $statement->line . '.');
			}
			return $this->statementCodeLines($statement, ['break;']);
		}

		if ($statement->kind === 'continue') {
			$depth = $statement->payload;
			if (!$this->isSimpleUnitLoopDepth($depth)) {
				$this->fail('Only continue 1 is supported; continue depth expressions and depths greater than 1 are not supported at line ' . $statement->line . '.');
			}
			return $this->statementCodeLines($statement, ['continue;']);
		}

		if ($statement->kind === 'expr') {
			if ($this->isAsyncSleepCall($statement->payload)) {
				if (!$this->currentFunctionIsAsync) {
					$this->fail('async_sleep_ms() may only be used inside an async function at line ' . $statement->line . '. Use await on an async task from synchronous code instead.');
				}
				return $this->statementCodeLines($statement, ['co_await ' . $this->renderAsyncSleepCall($statement->payload, $namespacePhp) . ';']);
			}
			return $this->statementCodeLines($statement, [$this->renderExpr($statement->payload, $namespacePhp) . ';']);
		}

		if ($statement->kind === 'include_or_eval') {
			$error = 'Prism++ supports require_once only as a static compile-time include with a literal path in the file prologue at line ' . $statement->line . '.';
			$this->fail($error);
		}

		$this->fail('Unsupported statement kind ' . $statement->kind . ' at line ' . $statement->line . '.');
	}

	private function renderTryStatement(Statement $statement, ?string $namespacePhp): array
	{
		$payload = is_array($statement->payload) ? $statement->payload : [];
		$tryStatements = is_array($payload['try'] ?? null) ? $payload['try'] : [];
		$catches = is_array($payload['catches'] ?? null) ? $payload['catches'] : [];
		$finallyStatements = is_array($payload['finally'] ?? null) ? $payload['finally'] : [];

		if ($finallyStatements !== [] && $this->statementsContainUnsupportedFinallyExit($tryStatements, true)) {
			$error = 'finally lowering does not support break/continue leaving the protected try region yet at line ' . $statement->line . '.';
			$this->fail($error);
		}
		if ($finallyStatements !== [] && $this->catchesContainUnsupportedFinallyExit($catches, true)) {
			$error = 'finally lowering does not support break/continue leaving catch handlers yet at line ' . $statement->line . '.';
			$this->fail($error);
		}
		if ($finallyStatements !== [] && $this->statementsContainUnsupportedFinallyExit($finallyStatements, false)) {
			$error = 'finally lowering does not support return/break/continue inside finally blocks yet at line ' . $statement->line . '.';
			$this->fail($error);
		}

		$lines = [$this->code('{', $statement->line)];
		$pendingName = $this->nextTempName('__scpp_pending_exception');
		$returnContext = $finallyStatements !== [] ? $this->createFinallyReturnContext() : null;
		$lines[] = $this->code($this->indent(1) . 'std::exception_ptr ' . $pendingName . ';', $statement->line);
		if ($returnContext !== null) {
			if ($returnContext['value'] !== null && $returnContext['type'] !== null) {
				$lines[] = $this->code($this->indent(1) . 'std::optional<' . $returnContext['type'] . '> ' . $returnContext['value'] . ';', $statement->line);
			}
			$lines[] = $this->code($this->indent(1) . 'bool ' . $returnContext['flag'] . ' = false;', $statement->line);
		}
		$lines[] = $this->code($this->indent(1) . 'try {', $statement->line);
		$tryBody = $returnContext !== null
			? $this->renderFinallyAwareStatementSequence($tryStatements, $namespacePhp, $returnContext)
			: $this->renderNestedStatements($tryStatements, $namespacePhp);
		foreach ($tryBody as $line) {
			$lines[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
		}
		$lines[] = $this->code($this->indent(1) . '}', $statement->line);

		foreach ($catches as $catchSpec) {
			$lines = array_merge($lines, $this->renderCatchChainArm($catchSpec, $namespacePhp, 1, $returnContext));
		}

		$lines[] = $this->code($this->indent(1) . 'catch (...) {', $statement->line);
		$lines[] = $this->code($this->indent(2) . $pendingName . ' = std::current_exception();', $statement->line);
		$lines[] = $this->code($this->indent(1) . '}', $statement->line);

		if ($finallyStatements !== []) {
			$lines[] = $this->code('', $statement->line);
			$lines[] = $this->code($this->indent(1) . '{', $statement->line);
			foreach ($this->renderNestedStatements($finallyStatements, $namespacePhp) as $line) {
				$lines[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
			}
			$lines[] = $this->code($this->indent(1) . '}', $statement->line);
			$lines[] = $this->code('', $statement->line);
		}

		$lines[] = $this->code($this->indent(1) . 'if (' . $pendingName . ') {', $statement->line);
		$lines[] = $this->code($this->indent(2) . 'std::rethrow_exception(' . $pendingName . ');', $statement->line);
		$lines[] = $this->code($this->indent(1) . '}', $statement->line);
		if ($returnContext !== null) {
			$lines[] = $this->code($this->indent(1) . 'if (' . $returnContext['flag'] . ') {', $statement->line);
			if ($returnContext['value'] === null) {
				$lines[] = $this->code($this->indent(2) . 'return;', $statement->line);
			} else {
				$lines[] = $this->code($this->indent(2) . 'return *' . $returnContext['value'] . ';', $statement->line);
			}
			$lines[] = $this->code($this->indent(1) . '}', $statement->line);
		}
		$lines[] = $this->code('}', $statement->line);
		return $lines;
	}

	/** @param array{classes:list<mixed>,var:mixed,stmts:list<Statement>,line:int} $catchSpec */
	private function renderCatchChainArm(array $catchSpec, ?string $namespacePhp, int $baseIndentLevel, ?array $returnContext = null): array
	{
		$catchLine = (int) ($catchSpec['line'] ?? 0);
		$varName = $this->extractSimpleVarName($catchSpec['var'] ?? null);
		if ($varName === null) {
			$error = 'catch handlers require a simple variable name at line ' . $catchLine . '.';
			$this->errors[] = $error;
			return [$this->code($this->indent($baseIndentLevel) . '// ERROR: ' . $error, $catchLine)];
		}

		$classes = is_array($catchSpec['classes'] ?? null) ? $catchSpec['classes'] : [];
		if ($classes === []) {
			$error = 'catch handlers require an explicit exception class at line ' . $catchLine . '.';
			$this->errors[] = $error;
			return [$this->code($this->indent($baseIndentLevel) . '// ERROR: ' . $error, $catchLine)];
		}

		$throwVar = $this->nextTempName('__scpp_thrown');
		$lines = [$this->code($this->indent($baseIndentLevel) . 'catch (const ::scpp::php::thrown_object& ' . $throwVar . ') {', $catchLine)];
		$matched = false;
		foreach ($classes as $classNode) {
			if (!is_object($classNode) || (($classNode->kind ?? null) !== AstKind::NAME)) {
				$error = 'Only named catch types are supported in v1 at line ' . $catchLine . '.';
				$this->errors[] = $error;
				$lines[] = $this->code($this->indent($baseIndentLevel + 1) . '// ERROR: ' . $error, $catchLine);
				continue;
			}
			$classType = $this->renderClassName($classNode, $namespacePhp);
			$caughtVar = $this->nextTempName('__scpp_caught');
			$prefix = $matched ? 'else if' : 'if';
			$lines[] = $this->code($this->indent($baseIndentLevel + 1) . $prefix . ' (auto ' . $caughtVar . ' = ::scpp::php::catch_as<' . $classType . '>(' . $throwVar . '); static_cast<bool>(' . $caughtVar . ')) {', $catchLine);
			$scopedLocals = $this->declaredLocals;
			$scopedLocalTypes = $this->declaredLocalTypes;
			$scopedReferenceLocals = $this->predefinedReferenceLocals;
			$this->declaredLocals[$varName] = true;
			$this->declaredLocalTypes[$varName] = 'shared_p<' . $classType . '>' ;
			$lines[] = $this->code($this->indent($baseIndentLevel + 1) . 'auto ' . $varName . ' = ' . $caughtVar . ';', $catchLine);
			$bodyLines = $returnContext !== null
				? $this->renderFinallyAwareStatementSequence($catchSpec['stmts'] ?? [], $namespacePhp, $returnContext)
				: $this->renderNestedStatements($catchSpec['stmts'] ?? [], $namespacePhp);
			foreach ($bodyLines as $line) {
				$lines[] = $this->code($this->indent($baseIndentLevel) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
			}
			$this->declaredLocals = $scopedLocals;
			$this->declaredLocalTypes = $scopedLocalTypes;
			$this->predefinedReferenceLocals = $scopedReferenceLocals;
			$lines[] = $this->code($this->indent($baseIndentLevel + 1) . '}', $catchLine);
			$matched = true;
		}

		if ($matched) {
			$lines[] = $this->code($this->indent($baseIndentLevel + 1) . 'else {', $catchLine);
			$lines[] = $this->code($this->indent($baseIndentLevel + 2) . 'throw;', $catchLine);
			$lines[] = $this->code($this->indent($baseIndentLevel + 1) . '}', $catchLine);
		} else {
			$lines[] = $this->code($this->indent($baseIndentLevel + 1) . 'throw;', $catchLine);
		}
		$lines[] = $this->code($this->indent($baseIndentLevel) . '}', $catchLine);
		return $lines;
	}

	/** @param list<Statement> $statements */
	private function statementsContainUnsupportedFinallyExit(array $statements, bool $allowReturn, int $loopDepth = 0, int $switchDepth = 0): bool
	{
		foreach ($statements as $statement) {
			if (!$statement instanceof Statement) {
				continue;
			}
			if ($statement->kind === 'break') {
				if ($loopDepth === 0 && $switchDepth === 0) {
					return true;
				}
				continue;
			}
			if ($statement->kind === 'continue') {
				if ($loopDepth === 0) {
					return true;
				}
				continue;
			}
			if (!$allowReturn && $statement->kind === 'return') {
				return true;
			}
			if ($statement->kind === 'if') {
				foreach (($statement->payload ?? []) as $branch) {
					if ($this->statementsContainUnsupportedFinallyExit($branch['stmts'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
						return true;
					}
				}
				continue;
			}
			if ($statement->kind === 'while' || $statement->kind === 'do_while' || $statement->kind === 'for' || $statement->kind === 'foreach') {
				if ($this->statementsContainUnsupportedFinallyExit($statement->payload['stmts'] ?? [], $allowReturn, $loopDepth + 1, $switchDepth)) {
					return true;
				}
				continue;
			}
			if ($statement->kind === 'switch') {
				foreach (($statement->payload['cases'] ?? []) as $case) {
					if ($this->statementsContainUnsupportedFinallyExit($case['stmts'] ?? [], $allowReturn, $loopDepth, $switchDepth + 1)) {
						return true;
					}
				}
				continue;
			}
			if ($statement->kind === 'try') {
				$payload = is_array($statement->payload) ? $statement->payload : [];
				if ($this->statementsContainUnsupportedFinallyExit($payload['try'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
					return true;
				}
				if ($this->catchesContainUnsupportedFinallyExit($payload['catches'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
					return true;
				}
				if ($this->statementsContainUnsupportedFinallyExit($payload['finally'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
					return true;
				}
			}
		}
		return false;
	}

	/** @param list<array<string,mixed>> $catches */
	private function catchesContainUnsupportedFinallyExit(array $catches, bool $allowReturn, int $loopDepth = 0, int $switchDepth = 0): bool
	{
		foreach ($catches as $catchSpec) {
			if ($this->statementsContainUnsupportedFinallyExit($catchSpec['stmts'] ?? [], $allowReturn, $loopDepth, $switchDepth)) {
				return true;
			}
		}
		return false;
	}

	private function createFinallyReturnContext(): ?array
	{
		if ($this->currentReturnType === null) {
			return null;
		}

		return [
			'flag' => $this->nextTempName('__scpp_pending_return'),
			'value' => $this->currentReturnType === 'void' ? null : $this->nextTempName('__scpp_return_value'),
			'type' => $this->currentReturnType === 'void' ? null : $this->currentReturnType,
		];
	}

	private function renderFinallyAwareReturnStatement(Statement $statement, ?string $namespacePhp): array
	{
		$context = $this->currentFinallyReturnContext;
		if ($context === null) {
			return $this->statementCodeLines($statement, ['return;']);
		}

		if ($context['value'] === null) {
			return $this->statementCodeLines($statement, [
				$context['flag'] . ' = true;',
			]);
		}

		return $this->statementCodeLines($statement, [
			$context['value'] . ' = ' . $this->renderReturnExpr($statement->payload, $namespacePhp) . ';',
			$context['flag'] . ' = true;',
		]);
	}

	private function renderFinallyAwareStatementSequence(array $statements, ?string $namespacePhp, array $returnContext): array
	{
		$previousContext = $this->currentFinallyReturnContext;
		$this->currentFinallyReturnContext = $returnContext;
		try {
			$lines = [];
			foreach ($statements as $statement) {
				$previousLine = $this->currentSourceLine;
				$previousColumn = $this->currentSourceColumn;
				$this->currentSourceLine = $statement->line;
				$this->currentSourceColumn = 0;
				foreach ($this->wrapWithReturnGuard($this->renderFinallyAwareStatement($statement, $namespacePhp, $returnContext), $returnContext['flag']) as $line) {
					$lines[] = $line;
				}
				$this->currentSourceLine = $previousLine;
				$this->currentSourceColumn = $previousColumn;
			}
			return $lines;
		} finally {
			$this->currentFinallyReturnContext = $previousContext;
		}
	}

	/** @param list<Statement> $statements @param array<string, array{line:int}> $hints */
	private function renderFinallyAwareStatementSequenceWithConditionHints(array $statements, ?string $namespacePhp, array $returnContext, array $hints): array
	{
		$previousHints = $this->activeConditionVisibilityHints;
		$this->activeConditionVisibilityHints = $hints + $previousHints;
		try {
			return $this->renderFinallyAwareStatementSequence($statements, $namespacePhp, $returnContext);
		} finally {
			$this->activeConditionVisibilityHints = $previousHints;
		}
	}

	private function renderFinallyAwareStatement(Statement $statement, ?string $namespacePhp, array $returnContext): array
	{
		if ($statement->kind === 'if') {
			$lines = [];
			$branches = is_array($statement->payload) ? $statement->payload : [];
			$first = true;
			foreach ($branches as $branch) {
				$branchLine = (int) ($branch['line'] ?? $statement->line);
				if (($branch['cond'] ?? null) !== null) {
					$lines[] = $this->code(($first ? 'if' : 'else if') . ' (' . $this->renderConditionExpr($branch['cond'], $namespacePhp) . ') {', $branchLine);
				} else {
					$lines[] = $this->code('else {', $branchLine);
				}
				$conditionHints = $this->conditionVisibilityHintsForExpr($branch['cond'] ?? null);
				foreach ($this->renderFinallyAwareStatementSequenceWithConditionHints($branch['stmts'] ?? [], $namespacePhp, $returnContext, $conditionHints) as $line) {
					$lines[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
				}
				$lines[] = $this->code('}', $branchLine);
				$first = false;
			}
			return $lines;
		}

		if ($statement->kind === 'while') {
			$conditionNode = $statement->payload['cond'] ?? null;
			$conditionHints = $this->conditionVisibilityHintsForExpr($conditionNode);
			$lines = [$this->code('while (!' . $returnContext['flag'] . ' && (' . $this->renderConditionExpr($conditionNode, $namespacePhp) . ')) {', $statement->line)];
			foreach ($this->renderFinallyAwareStatementSequenceWithConditionHints($statement->payload['stmts'] ?? [], $namespacePhp, $returnContext, $conditionHints) as $line) {
				$lines[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
			}
			$lines[] = $this->code('}', $statement->line);
			return $lines;
		}

		if ($statement->kind === 'do_while') {
			$lines = [$this->code('do {', $statement->line)];
			$conditionHints = $this->conditionVisibilityHintsForExpr($statement->payload['cond'] ?? null);
			foreach ($this->renderFinallyAwareStatementSequenceWithConditionHints($statement->payload['stmts'] ?? [], $namespacePhp, $returnContext, $conditionHints) as $line) {
				$lines[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
			}
			$lines[] = $this->code('} while (!' . $returnContext['flag'] . ' && (' . $this->renderConditionExpr($statement->payload['cond'] ?? null, $namespacePhp) . '));', $statement->line);
			return $lines;
		}

		if ($statement->kind === 'for') {
			$scopedLocals = $this->declaredLocals;
			$scopedLocalTypes = $this->declaredLocalTypes;
			$scopedReferenceLocals = $this->predefinedReferenceLocals;
			$init = $this->renderForInit($statement->payload['init'] ?? [], $namespacePhp);
			$conditionHints = $this->conditionVisibilityHintsForExpr($statement->payload['cond'] ?? []);
			$cond = $this->renderForConditionClause($statement->payload['cond'] ?? [], $namespacePhp);
			$loop = $this->renderForClause($statement->payload['loop'] ?? [], $namespacePhp, '');
			$lines = [$this->code('{', $statement->line)];
			if ($init !== '') {
				$lines[] = $this->code($this->indent(1) . $init . ';', $statement->line);
			}
			$lines[] = $this->code($this->indent(1) . 'while (!' . $returnContext['flag'] . ' && (' . $cond . ')) {', $statement->line);
			foreach ($this->renderFinallyAwareStatementSequenceWithConditionHints($statement->payload['stmts'] ?? [], $namespacePhp, $returnContext, $conditionHints) as $line) {
				$lines[] = $this->code($this->indent(2) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
			}
			if ($loop !== '') {
				$lines[] = $this->code($this->indent(2) . 'if (!' . $returnContext['flag'] . ') {', $statement->line);
				$lines[] = $this->code($this->indent(3) . $loop . ';', $statement->line);
				$lines[] = $this->code($this->indent(2) . '}', $statement->line);
			}
			$lines[] = $this->code($this->indent(1) . '}', $statement->line);
			$lines[] = $this->code('}', $statement->line);
			$this->declaredLocals = $scopedLocals;
			$this->declaredLocalTypes = $scopedLocalTypes;
			$this->predefinedReferenceLocals = $scopedReferenceLocals;
			return $lines;
		}

		if ($statement->kind === 'foreach') {
			return $this->renderForeachStatement($statement, $namespacePhp, $returnContext);
		}

		if ($statement->kind === 'switch') {
			$lines = [$this->code('switch (' . $this->renderSwitchExpr($statement->payload['cond'] ?? null, $namespacePhp) . ') {', $statement->line)];
			foreach (($statement->payload['cases'] ?? []) as $case) {
				$caseCond = $case['cond'] ?? null;
				$caseLine = (int) ($case['line'] ?? $statement->line);
				$lines[] = $this->code($caseCond === null
					? $this->indent(1) . 'default:'
					: $this->indent(1) . 'case ' . $this->renderSwitchCaseValue($caseCond) . ':', $caseLine);
				foreach ($this->renderFinallyAwareStatementSequence($case['stmts'] ?? [], $namespacePhp, $returnContext) as $line) {
					$lines[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
				}
			}
			$lines[] = $this->code('}', $statement->line);
			return $lines;
		}

		if ($statement->kind === 'try') {
			return $this->renderTryStatement($statement, $namespacePhp);
		}

		return $this->renderStatement($statement, $namespacePhp);
	}

	/** @param list<CodeBlock> $lines @return list<CodeBlock> */
	private function wrapWithReturnGuard(array $lines, string $returnFlag): array
	{
		if ($lines === []) {
			return [];
		}
		$originLine = $lines[0]->srcLine;
		$originColumn = $lines[0]->srcColumn;
		$out = [$this->code('if (!' . $returnFlag . ') {', $originLine, $originColumn)];
		foreach ($lines as $line) {
			$out[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
		}
		$out[] = $this->code('}', $originLine, $originColumn);
		return $out;
	}

	private function isSimpleUnitLoopDepth(mixed $depth): bool
	{
		return $depth === null || $depth === 1;
	}

	private function renderForeachStatement(Statement $statement, ?string $namespacePhp, ?array $returnContext = null): array
	{
		$payload = is_array($statement->payload) ? $statement->payload : [];
		$sourceExpr = $this->renderExpr($payload['expr'] ?? null, $namespacePhp);
		$valueName = $this->extractSimpleVarName($payload['value'] ?? null);
		$keyName = $this->extractSimpleVarName($payload['key'] ?? null);
		$byRef = (bool) ($payload['by_ref'] ?? false);

		if ($valueName === null) {
			$this->errors[] = 'foreach value target must be a simple variable at line ' . $statement->line . '.';
			return $this->statementCodeLines($statement, ['// ERROR: unsupported foreach value target']);
		}

		if (($payload['key'] ?? null) !== null && $keyName === null) {
			$this->errors[] = 'foreach key target must be a simple variable at line ' . $statement->line . '.';
			return $this->statementCodeLines($statement, ['// ERROR: unsupported foreach key target']);
		}

		$entryName = '__scpp_foreach_entry_' . $statement->line;
		$sourceType = $this->inferExprTypeWithNamespace($payload['expr'] ?? null, $namespacePhp);
		$isVectorLikeForeach = $this->isForeachVectorLikeType($sourceType);
		$hashTypeParts = $this->parseHashTypeParts($sourceType);
		$isExplicitDynamicForeach = $sourceType === 'mixed_t';
		$sourceTempName = $this->allocateGeneratedLocalName('__scpp_foreach_source_' . $statement->line);
		$valueStoredType = null;
		if (preg_match('/^vector_t<(.+)>$/', $sourceType, $matches) === 1) {
			$valueStoredType = $matches[1];
		} elseif (($fixedArrayParts = $this->parseMappedFixedArrayType($sourceType)) !== null) {
			$valueStoredType = $fixedArrayParts['element'];
		} elseif ($hashTypeParts !== null) {
			$valueStoredType = $hashTypeParts['value'];
		} elseif ($isExplicitDynamicForeach) {
			$valueStoredType = 'mixed_t';
		}
		$sourceBinding = $this->isLvalueCapableExpr($payload['expr'] ?? null, $namespacePhp)
			? 'auto& ' . $sourceTempName . ' = ' . $this->renderLvalueExpr($payload['expr'] ?? null, $namespacePhp) . ';'
			: 'auto ' . $sourceTempName . ' = ' . $sourceExpr . ';';
		$lines = $this->statementCodeLines($statement, [
			$sourceBinding,
			'for (auto ' . $entryName . ' : foreach_range(' . $sourceTempName . ')) {',
		]);

		$scopedLocals = $this->declaredLocals;
		$scopedLocalTypes = $this->declaredLocalTypes;
		$scopedReferenceLocals = $this->predefinedReferenceLocals;

		$keyCppName = null;
		if ($keyName !== null) {
			$keyCppName = $this->localCppName($keyName);
			$lines[] = $this->code($this->indent(1) . 'auto&& ' . $keyCppName . ' = ' . $entryName . '.key();', $statement->line);
			$keyStoredType = $isVectorLikeForeach
				? 'int_t<>'
				: ($hashTypeParts !== null ? $hashTypeParts['key'] : ($isExplicitDynamicForeach ? 'mixed_t' : null));
			if ($keyStoredType !== null) {
				$this->declaredLocalTypes[$keyName] = $keyStoredType;
			}
			$this->declaredLocals[$keyName] = true;
		}

		if ($byRef) {
			$this->foreachReferenceSlotStack[] = [
				$valueName => $entryName . '.value_ref()',
			];
			$this->declaredLocals[$valueName] = true;
			if ($valueStoredType !== null) {
				$this->declaredLocalTypes[$valueName] = $valueStoredType;
			}
		} else {
			$valueCppName = $this->localCppName($valueName);
			$hasOuterValueBinding = isset($scopedLocals[$valueName]);
			$currentElementExpr = $entryName . '.value_copy()';
			if ($hasOuterValueBinding) {
				$lines[] = $this->code($this->indent(1) . $valueCppName . ' = ' . $currentElementExpr . ';', $statement->line);
				if ($valueStoredType !== null) {
					$this->declaredLocalTypes[$valueName] = $valueStoredType;
				}
			} else {
				$lines[] = $this->code($this->indent(1) . 'auto ' . $valueCppName . ' = ' . $currentElementExpr . ';', $statement->line);
				$this->declaredLocals[$valueName] = true;
				if ($valueStoredType !== null) {
					$this->declaredLocalTypes[$valueName] = $valueStoredType;
				}
			}
		}

		try {
			if ($returnContext !== null) {
				$bodyLines = $this->renderFinallyAwareStatementSequence($payload['stmts'] ?? [], $namespacePhp, $returnContext);
				$lines[] = $this->code($this->indent(1) . 'if (' . $returnContext['flag'] . ') {', $statement->line);
				$lines[] = $this->code($this->indent(2) . 'break;', $statement->line);
				$lines[] = $this->code($this->indent(1) . '}', $statement->line);
				foreach ($bodyLines as $line) {
					$lines[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
				}
			} else {
				foreach ($this->renderNestedStatements($payload['stmts'] ?? [], $namespacePhp) as $line) {
					$lines[] = $line;
				}
			}
		} finally {
			if ($byRef) {
				array_pop($this->foreachReferenceSlotStack);
			}
			$this->declaredLocals = $scopedLocals;
			$this->declaredLocalTypes = $scopedLocalTypes;
			$this->predefinedReferenceLocals = $scopedReferenceLocals;
		}

		$lines[] = $this->code('}', $statement->line);
		return $lines;
	}

	/** @param list<array{cond:mixed,stmts:list<Statement>,line:int}> $branches @return list<CodeBlock> */
	private function renderIfStatement(array $branches, ?string $namespacePhp): array
	{
		$lines = [];
		$index = 0;
		foreach ($branches as $branch) {
			$branchLine = (int) ($branch['line'] ?? $this->currentSourceLine);
			$prefix = $index === 0 ? 'if' : (($branch['cond'] ?? null) === null ? 'else' : 'else if');
			if ($prefix === 'else') {
				$lines[] = $this->code('else {', $branchLine);
			} else {
				$lines[] = $this->code($prefix . ' (' . $this->renderConditionExpr($branch['cond'] ?? null, $namespacePhp) . ') {', $branchLine);
			}
			$conditionHints = $this->conditionVisibilityHintsForExpr($branch['cond'] ?? null);
			foreach ($this->renderNestedStatementsWithConditionHints($branch['stmts'] ?? [], $namespacePhp, $conditionHints) as $line) {
				$lines[] = $line;
			}
			$lines[] = $this->code('}', $branchLine);
			$index++;
		}
		return $lines;
	}

	/** @param list<Statement> $statements @return list<CodeBlock> */
	private function renderNestedStatements(array $statements, ?string $namespacePhp): array
	{
		$scopedLocals = $this->declaredLocals;
		$scopedLocalTypes = $this->declaredLocalTypes;
		$scopedReferenceLocals = $this->predefinedReferenceLocals;

		$lines = [];
		foreach ($this->renderStatementSequence($statements, $namespacePhp) as $line) {
			$lines[] = $this->code($this->indent(1) . $line->text, $line->srcLine, $line->srcColumn, $line->srcRelation);
		}

		$this->declaredLocals = $scopedLocals;
		$this->declaredLocalTypes = $scopedLocalTypes;
		$this->predefinedReferenceLocals = $scopedReferenceLocals;
		return $lines;
	}

	/** @param list<Statement> $statements @param array<string, array{line:int}> $hints @return list<CodeBlock> */
	private function renderNestedStatementsWithConditionHints(array $statements, ?string $namespacePhp, array $hints): array
	{
		$previousHints = $this->activeConditionVisibilityHints;
		$this->activeConditionVisibilityHints = $hints + $previousHints;
		try {
			return $this->renderNestedStatements($statements, $namespacePhp);
		} finally {
			$this->activeConditionVisibilityHints = $previousHints;
		}
	}

	/** @param list<Statement> $statements */
	private function renderStatementSequence(array $statements, ?string $namespacePhp): array
	{
		$lines = [];
		foreach ($statements as $statement) {
			$previousLine = $this->currentSourceLine;
			$previousColumn = $this->currentSourceColumn;
			$this->currentSourceLine = $statement->line;
			$this->currentSourceColumn = 0;
			foreach ($this->renderStatement($statement, $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$this->currentSourceLine = $previousLine;
			$this->currentSourceColumn = $previousColumn;
		}

		return $lines;
	}

	/** @param list<mixed> $exprs */
	private function renderForInit(array $exprs, ?string $namespacePhp): string
	{
		if ($exprs === []) {
			return '';
		}

		$out = [];
		foreach ($exprs as $expr) {
			if (is_object($expr) && ($expr->kind ?? null) === AstKind::ASSIGN) {
				$varNode = $expr->children['var'] ?? null;
				$name = $this->extractSimpleVarName($varNode);
				if ($name !== null && !isset($this->declaredLocals[$name]) && !$this->hasForeachReferenceSlotAlias($name)) {
					$this->declaredLocals[$name] = true;
					$out[] = 'auto ' . $this->localCppName($name) . ' = ' . $this->renderExpr($expr->children['expr'] ?? null, $namespacePhp);
					continue;
				}
			}
			$out[] = $this->renderExpr($expr, $namespacePhp);
		}
		return implode(', ', $out);
	}

	/** @param list<mixed> $exprs */
	private function renderForClause(array $exprs, ?string $namespacePhp, string $fallback): string
	{
		if ($exprs === []) {
			return $fallback;
		}
		return implode(', ', array_map(fn (mixed $expr): string => $this->renderExpr($expr, $namespacePhp), $exprs));
	}

	/** @param list<mixed> $exprs */
	private function renderForConditionClause(array $exprs, ?string $namespacePhp): string
	{
		if ($exprs === []) {
			return 'true';
		}
		if (count($exprs) === 1) {
			return $this->renderConditionExpr($exprs[0], $namespacePhp);
		}
		$last = array_pop($exprs);
		$prefix = implode(', ', array_map(fn (mixed $expr): string => $this->renderExpr($expr, $namespacePhp), $exprs));
		return '(' . $prefix . ', ' . $this->renderConditionExpr($last, $namespacePhp) . ')';
	}

	/**
	 * Renders any condition expression with the bool conversion required by the current Prism++ runtime contract.
	 *
	 * Relationship to specs:
	 * - preserves the subset and lowering rules documented for the prototype
	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit
	 */

	private function renderConditionExpr(mixed $expr, ?string $namespacePhp): string
	{
		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($this->exprProducesBool($expr)) {
			return 'static_cast<bool>(' . $rendered . ')';
		}
		return 'static_cast<bool>(' . $this->qualifyKnownPhpRuntimeSymbol('condition_truthy') . '(' . $rendered . '))';
	}

	/** @return array<string, array{line:int}> */
	private function conditionVisibilityHintsForExpr(mixed $expr): array
	{
		$names = [];
		if (is_array($expr)) {
			foreach ($expr as $item) {
				$this->collectConditionAssignedUndeclaredLocals($item, $names);
			}
			return $names;
		}
		$this->collectConditionAssignedUndeclaredLocals($expr, $names);
		return $names;
	}

	/** @param array<string, array{line:int}> $names */
	private function collectConditionAssignedUndeclaredLocals(mixed $expr, array &$names): void
	{
		if (!is_object($expr)) {
			return;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::ASSIGN) {
			$leftName = $this->extractSimpleVarName($expr->children['var'] ?? null);
			if ($leftName !== null && !isset($this->declaredLocals[$leftName]) && !$this->hasForeachReferenceSlotAlias($leftName)) {
				$names[$leftName] = ['line' => (int) ($expr->lineno ?? 0)];
			}
			$this->collectConditionAssignedUndeclaredLocals($expr->children['expr'] ?? null, $names);
			return;
		}

		$children = $expr->children ?? null;
		if (!is_array($children)) {
			return;
		}
		foreach ($children as $child) {
			if (is_array($child)) {
				foreach ($child as $nested) {
					$this->collectConditionAssignedUndeclaredLocals($nested, $names);
				}
				continue;
			}
			$this->collectConditionAssignedUndeclaredLocals($child, $names);
		}
	}

	private function buildUndeclaredVariableVisibilityError(string $name, int $line): string
	{
		$message = 'Variable $' . $name . ' is not visible in this block at line ' . $line . '. Safe v1 uses block-local variable visibility; declare $' . $name . ' in the current block or an enclosing block before use.';
		if (!isset($this->activeConditionVisibilityHints[$name])) {
			return $message;
		}

		return $message . ' When a condition first assigns $' . $name . ', use the canonical strict rewrite: predeclare $' . $name . ' before the loop or if, assign it inside the body, then branch on the null/false check explicitly.';
	}

	/**

	 * Best-effort classifier used to avoid redundant bool casts around expressions already known to produce bool_t.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function exprProducesBool(mixed $expr): bool
	{
		if (!is_object($expr)) {
			return false;
		}
		$kind = $expr->kind ?? null;
		if ($kind === AstKind::UNARY_OP) {
			return true;
		}
		if ($kind === AstKind::BINARY_OP) {
			return in_array((int) ($expr->flags ?? 0), [
				AstKind::BINARY_BOOL_AND,
				AstKind::BINARY_BOOL_OR,
				AstKind::BINARY_IS_SMALLER,
				AstKind::BINARY_IS_SMALLER_OR_EQUAL,
				AstKind::BINARY_IS_GREATER,
				AstKind::BINARY_IS_EQUAL,
				AstKind::BINARY_IS_IDENTICAL,
			], true);
		}
		return false;
	}

	/**

	 * Renders the controlling expression of a `switch`, bridging bool-like wrappers to native switch-compatible values when required.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderSwitchExpr(mixed $expr, ?string $namespacePhp): string
	{
		$rendered = $this->renderExpr($expr, $namespacePhp);
		return is_object($expr) ? '(' . $rendered . ').native_value()' : $rendered;
	}

	/**

	 * Renders a switch-case label and rejects unsupported non-literal case forms in the current prototype.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderSwitchCaseValue(mixed $expr): string
	{
		if (is_int($expr) || is_float($expr)) {
			return (string) $expr;
		}
		return '/* unsupported-switch-case */';
	}

	/**

	 * Extracts a plain variable name when an expression is simple enough to become a declaration target.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function extractSimpleVarName(mixed $expr): ?string
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::VAR)) {
			return null;
		}
		$name = (string) ($expr->children['name'] ?? '');
		return $name !== '' ? $name : null;
	}

	/**

	 * Renders the left-hand side of an assignment for the currently supported assignment targets.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderDimAccess(mixed $expr, ?string $namespacePhp): string
	{
		$baseExpr = $expr->children['expr'] ?? null;
		$base = $this->renderExpr($baseExpr, $namespacePhp);
		$dimNode = $expr->children['dim'] ?? null;
		if ($dimNode === null) {
			$this->errors[] = 'Append syntax cannot be used as a read expression.';
			return '/* unsupported-append-read */';
		}
		$dim = $this->renderExpr($dimNode, $namespacePhp);
		$baseType = $this->inferExprType($baseExpr);
		if (preg_match('/^vector_t<(.+)>$/', $baseType) === 1) {
			return $base . '.at(' . $dim . ')';
		}
		if (preg_match('/^fixed_array_t<(.+)>$/', $baseType) === 1) {
			return $base . '.at(' . $dim . ')';
		}
		$hashTypeParts = $this->parseHashTypeParts($baseType);
		if ($hashTypeParts !== null) {
			return $base . '.at(' . $this->renderHashKeyExpr($dim, $hashTypeParts['key']) . ')';
		}
		if ($baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
			return $base . '.get(' . $dim . ')';
		}
		if ($baseType === 'dynamic_t<>') {
			return '(*' . $base . ').at(' . $dim . ')';
		}
		if (is_object($baseExpr) && (($baseExpr->kind ?? null) === AstKind::DIM)) {
			return $base . '.get(' . $dim . ')';
		}
		if ($this->isUntypedTableType($baseType)) {
			return $this->renderUntypedTableAccessBase($base, $baseType) . '._find_val(' . $dim . ')';
		}
		// Unknown dim-read fallback stays on direct indexing so non-array expressions still surface compile-time errors.
		return $base . '[' . $dim . ']';
	}

	private function renderDimWriteAccess(mixed $expr, ?string $namespacePhp): string
	{
		$baseExpr = $expr->children['expr'] ?? null;
		$base = is_object($baseExpr) && (($baseExpr->kind ?? null) === AstKind::DIM)
			? $this->renderDimWriteAccess($baseExpr, $namespacePhp)
			: $this->renderExpr($baseExpr, $namespacePhp);
		$dimNode = $expr->children['dim'] ?? null;
		if ($dimNode === null) {
			$this->errors[] = 'Append syntax cannot be used as an lvalue target.';
			return '/* unsupported-append-lvalue */';
		}
		$unsupportedKeyMessage = $this->unsupportedPhpArrayKeyMessage($dimNode);
		if ($unsupportedKeyMessage !== null) {
			$this->errors[] = $unsupportedKeyMessage;
			return '/* unsupported-array-key */';
		}
		$dim = $this->renderExpr($dimNode, $namespacePhp);
		$baseType = $this->inferExprType($baseExpr);
		if (preg_match('/^vector_t<(.+)>$/', $baseType) === 1) {
			return $base . '.at(' . $dim . ')';
		}
		if (preg_match('/^fixed_array_t<(.+)>$/', $baseType) === 1) {
			return $base . '.at(' . $dim . ')';
		}
		$hashTypeParts = $this->parseHashTypeParts($baseType);
		if ($hashTypeParts !== null) {
			return $base . '[' . $this->renderHashKeyExpr($dim, $hashTypeParts['key']) . ']';
		}
		if ($baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
			return $base . '[' . $dim . ']';
		}
		if ($baseType === 'dynamic_t<>') {
			return '(*' . $base . ')[' . $dim . ']';
		}
		if ($this->isUntypedTableType($baseType)) {
			return $this->renderUntypedTableAccessBase($base, $baseType) . '[' . $dim . ']';
		}
		return $base . '[' . $dim . ']';
	}

	private function isUntypedTableType(string $type): bool
	{
		return $type === 'hash_t'
			|| $type === '::scpp::hash_t'
			|| $type === 'hash_t<mixed_t>'
			|| $type === '::scpp::hash_t<mixed_t>'
			|| $type === 'unique_p<hash_t<mixed_t>>'
			|| $type === 'unique_p<::scpp::hash_t<mixed_t>>'
			|| $type === '::scpp::unique_p<hash_t<mixed_t>>'
			|| $type === '::scpp::unique_p<::scpp::hash_t<mixed_t>>';
	}

	private function isUntypedTableHandleType(string $type): bool
	{
		return $type === 'unique_p<hash_t<mixed_t>>'
			|| $type === 'unique_p<::scpp::hash_t<mixed_t>>'
			|| $type === '::scpp::unique_p<hash_t<mixed_t>>'
			|| $type === '::scpp::unique_p<::scpp::hash_t<mixed_t>>';
	}

	/** @return array{value: string, key: string}|null */
	private function parseHashTypeParts(string $type): ?array
	{
		$normalized = trim($type);
		if (str_starts_with($normalized, '::scpp::')) {
			$normalized = substr($normalized, strlen('::scpp::'));
		}

		if (preg_match('/^hash_t<(.+)>$/', $normalized, $matches) !== 1) {
			return null;
		}

		$args = $this->typeMapper->splitTopLevelGenericArgs($matches[1]);
		if (count($args) < 1 || count($args) > 2) {
			return null;
		}

		$valueType = trim($args[0]);
		$keyType = count($args) === 2
			? trim($args[1])
			: ($valueType === 'mixed_t' ? 'mixed_t' : 'string_t');

		return ['value' => $valueType, 'key' => $keyType];
	}

	private function renderUntypedTableAccessBase(string $base, string $type): string
	{
		if ($this->isUntypedTableHandleType($type)) {
			return '(*(' . $base . '))';
		}

		return $base;
	}

	private function renderHashKeyExpr(string $expr, string $keyType): string
	{
		$normalized = trim($keyType);
		if ($normalized === 'mixed_t') {
			return $expr;
		}
		return $this->renderGeneratedCast($normalized, $expr);
	}

	private function renderAssignmentExpr(mixed $varNode, mixed $valueNode, ?string $namespacePhp): string
	{
		if (is_object($varNode) && (($varNode->kind ?? null) === AstKind::DIM)) {
			$baseExpr = $varNode->children['expr'] ?? null;
			$base = is_object($baseExpr) && (($baseExpr->kind ?? null) === AstKind::DIM)
				? $this->renderDimWriteAccess($baseExpr, $namespacePhp)
				: $this->renderExpr($baseExpr, $namespacePhp);
			$value = $this->renderExpr($valueNode, $namespacePhp);
			$baseType = $this->inferExprType($baseExpr);
			$dimNode = $varNode->children['dim'] ?? null;
			if ($dimNode === null) {
				$appendBase = $base;
				if ($this->isUntypedTableHandleType($baseType)) {
					$appendBase = '(' . $base . ')';
				}
				$appendMethod = preg_match('/^vector_t<(.+)>$/', $baseType) === 1 ? 'push_back' : ($this->isUntypedTableHandleType($baseType) ? '->append' : '.append');
				$appendValue = $value;
				$returnValue = $value;
				if ($this->shouldInlineAssignmentValue($valueNode)) {
					return '([&]() { (void) ' . $appendBase . $appendMethod . '(' . $appendValue . '); return ' . $returnValue . '; }())';
				}

				// Complex append assignments still spill into a temporary so the right-hand side is named once.
				$tempName = $this->nextTempName('__append_value');
				$storedTemp = $tempName;
				return '([&]() { auto ' . $tempName . ' = ' . $value . '; (void) ' . $appendBase . $appendMethod . '(' . $storedTemp . '); return ' . $storedTemp . '; }())';
			}

			$target = $this->renderDimWriteAccess($varNode, $namespacePhp);
			return '(' . $target . ' = ' . $value . ')';
		}

		$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
		$value = $this->renderExpr($valueNode, $namespacePhp);
		return '(' . $target . ' = ' . $value . ')';
	}

	private function renderCompoundAssignmentExpr(mixed $varNode, mixed $exprNode, int $flags, ?string $namespacePhp): string
	{
		$operator = $this->mapAssignOpFlagToOperator($flags);
		if ($operator === null) {
			return '/* unsupported-assign-op-' . $flags . ' */';
		}

		$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
		if ($flags === AstKind::BINARY_CONCAT) {
			$expr = $this->renderStringOperand($exprNode, $namespacePhp);
			return '(' . $target . ' = (' . $target . ' + ' . $expr . '))';
		}

		$expr = $this->renderExpr($exprNode, $namespacePhp);
		return '(' . $target . ' ' . $operator . ' ' . $expr . ')';
	}


	private function unsupportedPhpArrayKeyMessage(mixed $keyNode): ?string
	{
		if (is_bool($keyNode)) {
			return 'Boolean PHP array keys are not supported in the current subset.';
		}

		if ($keyNode === null) {
			return 'Null PHP array keys are not supported in the current subset.';
		}

		if (is_object($keyNode) && (($keyNode->kind ?? null) === AstKind::CONST)) {
			$nameNode = $keyNode->children['name'] ?? null;
			$constName = null;
			if (is_object($nameNode) && (($nameNode->kind ?? null) === AstKind::NAME)) {
				$constName = strtolower((string) ($nameNode->children['name'] ?? ''));
			}

			if ($constName === 'true' || $constName === 'false') {
				return 'Boolean PHP array keys are not supported in the current subset.';
			}

			if ($constName === 'null') {
				return 'Null PHP array keys are not supported in the current subset.';
			}
		}

		return null;
	}

	private function shouldInlineAssignmentValue(mixed $expr): bool
	{
		if (is_int($expr) || is_float($expr) || is_string($expr) || is_bool($expr) || $expr === null) {
			return true;
		}

		if (!is_object($expr)) {
			return false;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::VAR
			|| $kind === AstKind::CONST
			|| $kind === AstKind::CLASS_CONST
			|| $kind === AstKind::NAME
			|| $kind === AstKind::ARRAY) {
			return true;
		}

		$magicConstKindName = AstKind::class . '::MAGIC_CONST';
		if (defined($magicConstKindName) && $kind === constant($magicConstKindName)) {
			return true;
		}

		return false;
	}

	private function nextTempName(string $prefix = '__tmp'): string
	{
		$this->tempCounter++;
		return $prefix . '_' . (string) $this->tempCounter;
	}


	private function renderCompoundAssignmentStatement(Statement $statement, mixed $varNode, mixed $exprNode, ?string $name, ?string $namespacePhp): array
	{
		$operator = $this->mapAssignOpFlagToOperator((int) ($statement->payload['flags'] ?? 0));
		if ($operator === null) {
			$error = 'Unsupported compound assignment flag ' . (int) ($statement->payload['flags'] ?? 0) . ' at line ' . $statement->line . '.';
			$this->errors[] = $error;
			return $this->statementCodeLines($statement, ['// ERROR: ' . $error]);
		}

		if ($name !== null && !isset($this->declaredLocals[$name]) && !$this->hasForeachReferenceSlotAlias($name)) {
			$error = 'Compound assignment requires a previously declared variable $' . $name . ' at line ' . $statement->line . '.';
			$this->errors[] = $error;
			return $this->statementCodeLines($statement, ['// ERROR: ' . $error]);
		}

		$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
		if ((int) ($statement->payload['flags'] ?? 0) === AstKind::BINARY_CONCAT) {
			$expr = $this->renderStringOperand($exprNode, $namespacePhp);
			return $this->statementCodeLines($statement, [$target . ' = (' . $target . ' + ' . $expr . ');']);
		}

		$expr = $this->renderExpr($exprNode, $namespacePhp);
		return $this->statementCodeLines($statement, [$target . ' ' . $operator . ' ' . $expr . ';']);
	}

	private function mapAssignOpFlagToOperator(int $flag): ?string
	{
		return match ($flag) {
			AstKind::PLUS => '+=',
			AstKind::MINUS => '-=',
			AstKind::MUL => '*=',
			AstKind::DIV => '/=',
			AstKind::MOD => '%=',
			AstKind::SHIFT_LEFT => '<<=',
			AstKind::SHIFT_RIGHT => '>>=',
			AstKind::BITWISE_OR => '|=',
			AstKind::BITWISE_AND => '&=',
			AstKind::BITWISE_XOR => '^=',
			AstKind::BINARY_CONCAT => '+=',
			default => null,
		};
	}

	private function renderAssignmentTarget(mixed $expr, ?string $namespacePhp): string
	{
		if (is_object($expr) && (($expr->kind ?? null) === AstKind::STATIC_PROP)) {
			return $this->renderStaticPropertyAccess($expr, $namespacePhp);
		}

		if (is_object($expr) && (($expr->kind ?? null) === AstKind::DIM)) {
			return $this->renderDimWriteAccess($expr, $namespacePhp);
		}

		if (is_object($expr) && (($expr->kind ?? null) === AstKind::PROP)) {
			$baseExpr = $expr->children['expr'] ?? null;
			$baseType = $this->inferExprType($baseExpr);
			if ($baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
				$base = $this->renderExpr($baseExpr, $namespacePhp);
				$propName = (string) ($expr->children['prop'] ?? 'prop');
				return $base . '[string_t(' . json_encode($propName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ')]';
			}
		}

		return $this->renderExpr($expr, $namespacePhp);
	}

	private function renderMatchExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::MATCH)) {
			return '/* unsupported-match */';
		}

		$subjectNode = $expr->children['cond'] ?? null;
		$subjectExpr = $this->renderExpr($subjectNode, $namespacePhp);
		$subjectName = $this->nextTempName('__match_subject');
		$armsNode = $expr->children['stmts'] ?? null;
		$arms = [];
		if (is_object($armsNode) && isset($armsNode->children) && is_array($armsNode->children)) {
			$arms = array_values($armsNode->children);
		}

		$parts = [];
		$parts[] = '([&]() {';
		$parts[] = 'auto ' . $subjectName . ' = ' . $subjectExpr . ';';

		$hasDefaultArm = false;
		foreach ($arms as $arm) {
			if (!is_object($arm) || (($arm->kind ?? null) !== AstKind::MATCH_ARM)) {
				$parts[] = 'throw std::runtime_error("Unsupported match arm shape");';
				continue;
			}

			$armExpr = $this->renderExpr($arm->children['expr'] ?? null, $namespacePhp);
			$condNode = $arm->children['cond'] ?? null;
			$conditions = $this->extractMatchConditions($condNode);
			if ($conditions === []) {
				$hasDefaultArm = true;
				$parts[] = 'return ' . $armExpr . ';';
				continue;
			}

			$checks = [];
			foreach ($conditions as $condition) {
				$checks[] = 'static_cast<bool>(' . $this->qualifyKnownPhpRuntimeSymbol('identical') . '(' . $subjectName . ', ' . $this->renderExpr($condition, $namespacePhp) . '))';
			}
			$parts[] = 'if (' . implode(' || ', $checks) . ') {';
			$parts[] = $this->indent(1) . 'return ' . $armExpr . ';';
			$parts[] = '}';
		}

		if (!$hasDefaultArm) {
			$parts[] = 'throw std::runtime_error("Unhandled match expression");';
		}

		$parts[] = '}())';
		return implode(' ', $parts);
	}

	/** @return list<mixed> */
	private function extractMatchConditions(mixed $condNode): array
	{
		if ($condNode === null) {
			return [];
		}

		if (is_object($condNode) && isset($condNode->children) && is_array($condNode->children)) {
			if (($condNode->kind ?? null) === AstKind::ARG_LIST || array_is_list($condNode->children)) {
				return array_values($condNode->children);
			}
		}

		return [$condNode];
	}

	/**

	 * Turns first assignment of a local into a declaration+assignment when the rules allow it.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function tryRenderDeclarationAssignChain(mixed $varNode, mixed $exprNode, ?string $typed, ?string $namespacePhp): ?array
	{
		$leftName = $this->extractSimpleVarName($varNode);
		if ($leftName === null || !is_object($exprNode) || (($exprNode->kind ?? null) !== AstKind::ASSIGN)) {
			return null;
		}
		$rightVarNode = $exprNode->children['var'] ?? null;
		$rightExprNode = $exprNode->children['expr'] ?? null;
		$rightName = $this->extractSimpleVarName($rightVarNode);
		if ($rightName === null || isset($this->declaredLocals[$rightName])) {
			return null;
		}
		$rightExpr = $this->renderExpr($rightExprNode, $namespacePhp);
		$this->declaredLocals[$rightName] = true;
		$this->declaredLocals[$leftName] = true;
		$rightDeclarationType = $this->inferFirstAssignmentDeclarationType($rightExprNode, null);
		$leftType = $typed !== null
			? $this->typeMapper->mapTypedLocalType($typed)
			: $this->inferFirstAssignmentDeclarationType($rightExprNode, null);
		return [
			$rightDeclarationType . ' ' . $rightName . ' = ' . $rightExpr . ';',
			$leftType . ' ' . $leftName . ' = ' . $rightName . ';',
		];
	}

	/**
	 * Resolve strict local wrapper shortcuts such as the bare `value` wrapper on `new Box()`.
	 *
	 * @return array{0:string,1:?string}
	 */
	private function resolveTypedLocalTypeForAssignment(string $typedLocalType, string $statementKind, mixed $exprNode, int $line): array
	{
		if ($this->typeMapper->hasInvalidNestedWrapperType($typedLocalType)) {
			return [$typedLocalType, 'Invalid nested wrapper type at line ' . $line . ': ' . $typedLocalType . ' is not allowed.'];
		}

		if (!$this->typeMapper->isBareObjectWrapperShortcut($typedLocalType)) {
			return [$typedLocalType, null];
		}

		if ($statementKind !== 'assign') {
			return [$typedLocalType, 'Bare wrapper local type /** ' . $typedLocalType . ' */ requires a direct assignment from new ClassName(...) at line ' . $line . '.'];
		}

		$className = $this->extractDirectConstructedClassTypeName($exprNode);
		if ($className === null) {
			return [$typedLocalType, 'Bare wrapper local type /** ' . $typedLocalType . ' */ requires a direct assignment from new ClassName(...) at line ' . $line . '.'];
		}

		try {
			return [$this->typeMapper->specializeBareObjectWrapperShortcut($typedLocalType, $className), null];
		} catch (\Throwable $exception) {
			return [$typedLocalType, $exception->getMessage() . ' at line ' . $line . '.'];
		}
	}

	private function extractDirectConstructedClassTypeName(mixed $exprNode): ?string
	{
		if (!is_object($exprNode) || (($exprNode->kind ?? null) !== AstKind::NEW)) {
			return null;
		}

		$classNode = $exprNode->children['class'] ?? null;
		if (!is_object($classNode)) {
			return null;
		}

		$name = trim((string) ($classNode->children['name'] ?? ''));
		if ($name === '') {
			return null;
		}

		$lowered = strtolower(ltrim($name, '\\'));
		if (in_array($lowered, ['self', 'parent', 'static'], true)) {
			return null;
		}

		return ltrim($name, '\\');
	}

	private function validateTypedLocalAssignment(string $typedLocalType, string $statementKind, mixed $exprNode, int $line): ?string
	{
		if (!$this->typeMapper->isRefLocalType($typedLocalType)) {
			return null;
		}

		if ($statementKind !== 'assign_ref') {
			return 'ref typed locals must be initialized via reference assignment at line ' . $line . '.';
		}

		return null;
	}

	private function normalizeStoredLocalType(string $typedLocalType): string
	{
		if ($this->typeMapper->isRefLocalType($typedLocalType)) {
			return $this->typeMapper->mapDeclaredType($this->typeMapper->unwrapRefLocalType($typedLocalType));
		}

		return $typedLocalType;
	}

	private function isEmptyPositionalArrayLiteral(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::ARRAY)) {
			return false;
		}

		$children = $expr->children ?? null;
		return is_array($children) && $children === [];
	}

	private function mapTypedVectorLocalType(string $typedLocalType): ?string
	{
		if (!$this->typeMapper->isVectorType($typedLocalType)) {
			return null;
		}

		return $this->typeMapper->mapTypedLocalType($typedLocalType);
	}

	private function mapTypedFixedArrayLocalType(string $typedLocalType): ?string
	{
		if (!$this->typeMapper->isFixedArrayType($typedLocalType)) {
			return null;
		}

		return $this->typeMapper->mapTypedLocalType($typedLocalType);
	}

	private function parseMappedVectorElementType(string $mappedVectorType): ?string
	{
		$normalized = trim($mappedVectorType);
		if (preg_match('/^vector_t<(.+)>$/', $normalized, $matches) !== 1) {
			return null;
		}

		return trim($matches[1]);
	}

	/** @return array{element:string,size:int}|null */
	private function parseMappedFixedArrayType(string $mappedFixedArrayType): ?array
	{
		$normalized = trim($mappedFixedArrayType);
		if (preg_match('/^fixed_array_t<(.+)>$/', $normalized, $matches) !== 1) {
			return null;
		}
		$args = $this->typeMapper->splitTopLevelGenericArgs($matches[1]);
		if (count($args) !== 2) {
			return null;
		}
		$size = trim($args[1]);
		if (preg_match('/^(?:0|[1-9][0-9]*)$/', $size) !== 1) {
			return null;
		}
		return ['element' => trim($args[0]), 'size' => (int) $size];
	}

	private function mapTypedHashLocalType(string $typedLocalType): ?string
	{
		if (!$this->typeMapper->isHashType($typedLocalType)) {
			return null;
		}

		return $this->typeMapper->mapTypedLocalType($typedLocalType);
	}

	private function mapStoredLocalTypeToMappedType(string $storedLocalType): ?string
	{
		$normalized = trim($storedLocalType);
		if ($normalized === '') {
			return null;
		}

		return $this->typeMapper->mapTypedLocalType($normalized);
	}

	private function renderInitializerExpr(mixed $expr, ?string $typedLocalType, ?string $namespacePhp): string
	{
		if ($typedLocalType !== null && is_object($expr) && (($expr->kind ?? null) === AstKind::NEW)) {
			$wrapperValidationError = $this->validateTypedWrapperInitializerFromNew($typedLocalType, $expr, $namespacePhp);
			if ($wrapperValidationError !== null) {
				$this->errors[] = $wrapperValidationError;
				return '/* error: ' . $wrapperValidationError . ' */';
			}

			$wrapperInit = $this->renderTypedWrapperInitializerFromNew($typedLocalType, $expr, $namespacePhp);
			if ($wrapperInit !== null) {
				return $wrapperInit;
			}
		}

		if (is_object($expr) && (($expr->kind ?? null) === AstKind::ARRAY)) {
			return $this->renderArrayLiteral($expr, $namespacePhp, $typedLocalType);
		}

		if (is_object($expr) && in_array(($expr->kind ?? null), [AstKind::CLOSURE, AstKind::ARROW_FUNC], true)) {
			$savedExpectedClosureSignature = $this->currentExpectedClosureSignature;
			try {
				$this->currentExpectedClosureSignature = $this->parseExpectedClosureSignature($typedLocalType);
				return $this->renderExpr($expr, $namespacePhp);
			} finally {
				$this->currentExpectedClosureSignature = $savedExpectedClosureSignature;
			}
		}

		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($typedLocalType !== null) {
			if ($this->typeMapper->mapTypedLocalType($typedLocalType) === 'dynamic_t<>' && $this->isNullConstantExpr($expr)) {
				return $rendered;
			}
			return $this->wrapExprForExpectedType($rendered, $this->inferExprType($expr), $this->typeMapper->mapTypedLocalType($typedLocalType));
		}

		return $rendered;
	}

	private function isNullConstantExpr(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::CONST)) {
			return false;
		}
		$nameNode = $expr->children['name'] ?? null;
		$name = is_object($nameNode) ? (string) ($nameNode->children['name'] ?? '') : (string) $nameNode;
		return strtolower(ltrim($name, '\\')) === 'null';
	}



	private function shouldCopyInitializerValue(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::DIM)) {
			return false;
		}

		$baseExpr = $expr->children['expr'] ?? null;
		$baseType = $this->inferExprType($baseExpr);
		return $this->isUntypedTableType($baseType);
	}

	private function inferFirstAssignmentDeclarationType(mixed $exprNode, ?string $inferredType): string
	{
		if (is_object($exprNode) && (($exprNode->kind ?? null) === AstKind::ARRAY)) {
			return 'mixed_t';
		}

		if ($this->isNullExpr($exprNode)) {
			return 'mixed_t';
		}

		if ($inferredType === 'mixed_t') {
			return 'mixed_t';
		}

		return 'auto';
	}

	private function validateTypedWrapperInitializerFromNew(string $typedLocalType, object $expr, ?string $namespacePhp): ?string
	{
		$declaredInnerType = $this->extractWrappedObjectInnerType($typedLocalType);
		if ($declaredInnerType === null) {
			return null;
		}

		$constructedClassName = $this->extractDirectConstructedClassTypeName($expr);
		if ($constructedClassName === null) {
			return null;
		}

		$declaredClass = $this->typeMapper->mapClassName($declaredInnerType);
		$constructedClass = $this->renderClassName($expr->children['class'] ?? null, $namespacePhp);
		if ($declaredClass === $constructedClass) {
			return null;
		}

		return 'Type mismatch for wrapper-typed local at line ' . (int) ($expr->lineno ?? 0) . ': declared ' . $typedLocalType . ' but assigned new ' . $constructedClassName . '().';
	}

	private function extractWrappedObjectInnerType(string $typedLocalType): ?string
	{
		$normalized = trim($typedLocalType);
		if ($this->typeMapper->isInlineValueType($normalized)) {
			return $this->typeMapper->unwrapInlineValueType($normalized);
		}

		if ($this->typeMapper->isNullableInlineValueType($normalized)) {
			return $this->typeMapper->unwrapNullableInlineValueType($normalized);
		}

		if (preg_match('/^(?:shared|unique)\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			return trim($matches[1]);
		}

		return null;
	}
	private function renderTypedWrapperInitializerFromNew(string $typedLocalType, object $expr, ?string $namespacePhp): ?string
	{
		$args = $this->renderArgs($expr->children['args']->children ?? [], $namespacePhp);

		if ($this->typeMapper->isInlineValueType($typedLocalType)) {
			$innerType = $this->typeMapper->unwrapInlineValueType($typedLocalType);
			return 'value<' . $innerType . '>(' . $args . ')';
		}

		if ($this->typeMapper->isNullableInlineValueType($typedLocalType)) {
			$innerType = $this->typeMapper->unwrapNullableInlineValueType($typedLocalType);
			return 'nullable<' . $innerType . '>{' . $innerType . '(' . $args . ')}';
		}

		if (preg_match('/^unique\s*<\s*(.+)\s*>$/', trim($typedLocalType), $matches) === 1) {
			return 'unique<' . trim($matches[1]) . '>(' . $args . ')';
		}

		return null;
	}

	private function renderArrayLiteral(mixed $expr, ?string $namespacePhp, ?string $typedLocalType = null): string
	{
		$elements = is_object($expr) && isset($expr->children) && is_array($expr->children)
			? array_values($expr->children)
			: [];
		foreach ($elements as $element) {
			$valueNode = is_object($element) && (($element->kind ?? null) === AstKind::ARRAY_ELEM)
				? ($element->children['value'] ?? null)
				: null;
			if (is_object($valueNode) && in_array(($valueNode->kind ?? null), [AstKind::CLOSURE, AstKind::ARROW_FUNC], true)) {
				$this->errors[] = 'Closures cannot be stored in array or dynamic container literals at line ' . (int) ($valueNode->lineno ?? $expr->lineno ?? 0) . '. Assign the closure to a concrete local callable instead.';
				return '/* unsupported-closure-container-literal */';
			}
		}

		$mappedVectorType = $typedLocalType !== null ? $this->mapTypedVectorLocalType($typedLocalType) : null;
		if ($mappedVectorType !== null) {
			return $this->renderTypedVectorArrayLiteral($expr, $namespacePhp, $mappedVectorType);
		}

		$mappedFixedArrayType = $typedLocalType !== null ? $this->mapTypedFixedArrayLocalType($typedLocalType) : null;
		if ($mappedFixedArrayType !== null) {
			return $this->renderTypedFixedArrayLiteral($expr, $namespacePhp, $mappedFixedArrayType);
		}

		$mappedHashType = $typedLocalType !== null ? $this->mapTypedHashLocalType($typedLocalType) : null;
			if ($mappedHashType !== null) {
				$hashTypeParts = $this->parseHashTypeParts($mappedHashType);
				if ($hashTypeParts === null) {
					$this->errors[] = 'Unsupported typed hash mapping for ' . $typedLocalType . '.';
					return '/* unsupported-typed-hash */';
				}

				$valueType = $hashTypeParts['value'];
			$lines = [
				'[&]() -> ' . $mappedHashType . ' {',
				$this->indent(1) . $mappedHashType . ' __scpp_hash_value{};',
			];

			foreach ($elements as $element) {
				if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
					$this->errors[] = 'Unsupported array literal element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
					return '/* unsupported-array-literal */';
				}

				$valueNode = $element->children['value'] ?? null;
				if ($valueNode === null) {
					$this->errors[] = 'Array unpack and empty array elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
					return '/* unsupported-array-element */';
				}

				$wrappedValue = $this->wrapExprForExpectedType(
					$this->renderExpr($valueNode, $namespacePhp),
					$this->inferExprType($valueNode),
					$valueType
				);

				$keyNode = $element->children['key'] ?? null;
				if ($keyNode === null) {
					$lines[] = $this->indent(1) . '(void) __scpp_hash_value.append(' . $wrappedValue . ');';
					continue;
				}

				$unsupportedKeyMessage = $this->unsupportedPhpArrayKeyMessage($keyNode);
				if ($unsupportedKeyMessage !== null) {
					$this->errors[] = $unsupportedKeyMessage;
					return '/* unsupported-array-key */';
				}

				$lines[] = $this->indent(1) . '__scpp_hash_value.set(' . $this->renderExpr($keyNode, $namespacePhp) . ', ' . $wrappedValue . ');';
			}

			$lines[] = $this->indent(1) . 'return __scpp_hash_value;';
			$lines[] = '}()';
			return implode("\n", $lines);
		}

		$mappedStructType = $typedLocalType !== null ? $this->mapTypedStructLocalType($typedLocalType) : null;
		if ($mappedStructType !== null) {
			return $this->renderTypedStructArrayLiteral($expr, $namespacePhp, $typedLocalType, $mappedStructType);
		}

		if ($elements === []) {
			return 'mixed_t{shared_table_()}';
		}

		$items = [];
		foreach ($elements as $element) {
			if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
				$this->errors[] = 'Unsupported array literal element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
				return '/* unsupported-array-literal */';
			}

			$valueNode = $element->children['value'] ?? null;
			if ($valueNode === null) {
				$this->errors[] = 'Array unpack and empty array elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-array-element */';
			}

			$keyNode = $element->children['key'] ?? null;
			if ($keyNode === null) {
				$items[] = 'table_item_(' . $this->renderExpr($valueNode, $namespacePhp) . ')';
				continue;
			}

			$unsupportedKeyMessage = $this->unsupportedPhpArrayKeyMessage($keyNode);
			if ($unsupportedKeyMessage !== null) {
				$this->errors[] = $unsupportedKeyMessage;
				return '/* unsupported-array-key */';
			}

			$items[] = 'table_kv_(' . $this->renderExpr($keyNode, $namespacePhp) . ', ' . $this->renderExpr($valueNode, $namespacePhp) . ')';
		}

		return 'mixed_t{shared_table_(' . implode(', ', $items) . ')}';
	}

	private function renderTypedVectorArrayLiteral(mixed $expr, ?string $namespacePhp, string $mappedVectorType): string
	{
		$elements = is_object($expr) && isset($expr->children) && is_array($expr->children)
			? array_values($expr->children)
			: [];
		if ($elements === []) {
			return $mappedVectorType . '{}';
		}

		$elementType = $this->parseMappedVectorElementType($mappedVectorType);
		if ($elementType === null) {
			$this->errors[] = 'Unsupported typed vector mapping for ' . $mappedVectorType . '.';
			return '/* unsupported-typed-vector */';
		}

		$values = [];
		foreach ($elements as $element) {
			if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
				$this->errors[] = 'Unsupported array literal element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
				return '/* unsupported-array-literal */';
			}

			$key = $element->children['key'] ?? null;
			if ($key !== null) {
				$this->errors[] = 'Typed vector literals cannot contain explicit keys at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-keyed-vector-literal */';
			}

			$valueNode = $element->children['value'] ?? null;
			if ($valueNode === null) {
				$this->errors[] = 'Array unpack and empty array elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-array-element */';
			}

			if (is_object($valueNode) && (($valueNode->kind ?? null) === AstKind::ARRAY)) {
				$nestedLiteral = $this->renderArrayLiteralForExpectedMappedType($valueNode, $namespacePhp, $elementType);
				if ($nestedLiteral !== null) {
					$values[] = $nestedLiteral;
					continue;
				}
			}

			$values[] = $this->wrapExprForExpectedType(
				$this->renderExpr($valueNode, $namespacePhp),
				$this->inferExprType($valueNode),
				$elementType
			);
		}

		return $mappedVectorType . '{' . implode(', ', $values) . '}';
	}

	private function renderArrayLiteralForExpectedMappedType(mixed $expr, ?string $namespacePhp, string $expectedType): ?string
	{
		$normalized = trim($expectedType);
		if ($this->parseMappedVectorElementType($normalized) !== null) {
			return $this->renderTypedVectorArrayLiteral($expr, $namespacePhp, $normalized);
		}
		if ($this->parseMappedFixedArrayType($normalized) !== null) {
			return $this->renderTypedFixedArrayLiteral($expr, $namespacePhp, $normalized);
		}
		$struct = $this->resolveStructDeclByMappedType($normalized);
		if ($struct instanceof ClassDecl) {
			return $this->renderTypedStructArrayLiteral($expr, $namespacePhp, $struct->name, $normalized);
		}
		return null;
	}

	private function mapTypedStructLocalType(string $typedLocalType): ?string
	{
		if ($this->typeMapper->declaredTypeKind($typedLocalType) !== 'struct') {
			return null;
		}

		return $this->typeMapper->mapTypedLocalType($typedLocalType);
	}

	private function resolveStructDeclByMappedType(string $mappedType): ?ClassDecl
	{
		$normalized = trim($mappedType);
		foreach ($this->classDecls as $name => $class) {
			if (!$class->isStruct) {
				continue;
			}
			if ($this->typeMapper->mapDeclaredType($name) === $normalized || $this->typeMapper->mapDeclaredType($class->name) === $normalized) {
				return $class;
			}
		}
		return null;
	}

	private function renderTypedStructArrayLiteral(mixed $expr, ?string $namespacePhp, string $typedStructType, string $mappedStructType): string
	{
		$elements = is_object($expr) && isset($expr->children) && is_array($expr->children)
			? array_values($expr->children)
			: [];
		if ($elements === []) {
			return $mappedStructType . '{}';
		}

		$valuesByField = [];
		foreach ($elements as $element) {
			if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
				$this->errors[] = 'Unsupported struct initializer element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
				return '/* unsupported-struct-initializer */';
			}

			$keyNode = $element->children['key'] ?? null;
			$fieldName = $this->extractStructInitializerFieldName($keyNode);
			if ($fieldName === null) {
				$this->errors[] = 'Struct initializers require literal string field keys at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-struct-initializer-key */';
			}
			if (isset($valuesByField[$fieldName])) {
				$this->errors[] = 'Struct initializer field `' . $fieldName . '` is assigned more than once at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* duplicate-struct-initializer-field */';
			}

			$valueNode = $element->children['value'] ?? null;
			if ($valueNode === null) {
				$this->errors[] = 'Array unpack and empty struct initializer elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-struct-initializer-element */';
			}
			$valuesByField[$fieldName] = $valueNode;
		}

		$struct = $this->resolveClassDeclByTypeName($typedStructType);
		if (!$struct instanceof ClassDecl || !$struct->isStruct) {
			$items = [];
			foreach ($valuesByField as $fieldName => $valueNode) {
				$items[] = '.' . $this->cppIdentifier($fieldName) . ' = ' . $this->renderExpr($valueNode, $namespacePhp);
			}
			return $mappedStructType . '{' . implode(', ', $items) . '}';
		}

		$fieldTypes = [];
		foreach ($struct->properties as $property) {
			$fieldTypes[$property->name] = $property->type;
		}
		foreach (array_keys($valuesByField) as $fieldName) {
			if (!array_key_exists($fieldName, $fieldTypes)) {
				$this->errors[] = 'Struct initializer for ' . $struct->name . ' references unknown field `' . $fieldName . '`.';
				return '/* unknown-struct-initializer-field */';
			}
		}

		$items = [];
		foreach ($struct->properties as $property) {
			if (!array_key_exists($property->name, $valuesByField)) {
				continue;
			}
			$valueNode = $valuesByField[$property->name];
			$value = $this->renderStructInitializerFieldValue($valueNode, $namespacePhp, $property->type);
			$items[] = '.' . $this->cppIdentifier($property->name) . ' = ' . $value;
		}

		return $mappedStructType . '{' . implode(', ', $items) . '}';
	}

	private function renderStructInitializerFieldValue(mixed $valueNode, ?string $namespacePhp, ?string $fieldType): string
	{
		if ($fieldType !== null && is_object($valueNode) && (($valueNode->kind ?? null) === AstKind::ARRAY)) {
			$nestedMappedType = $this->mapTypedStructLocalType($fieldType);
			if ($nestedMappedType !== null) {
				return $this->renderTypedStructArrayLiteral($valueNode, $namespacePhp, $fieldType, $nestedMappedType);
			}
			$expectedMappedType = $this->typeMapper->mapDeclaredType($fieldType);
			$nestedLiteral = $this->renderArrayLiteralForExpectedMappedType($valueNode, $namespacePhp, $expectedMappedType);
			if ($nestedLiteral !== null) {
				return $nestedLiteral;
			}
		}

		$rendered = $this->renderExpr($valueNode, $namespacePhp);
		if ($fieldType === null) {
			return $rendered;
		}
		return $this->wrapExprForExpectedType($rendered, $this->inferExprType($valueNode), $this->typeMapper->mapDeclaredType($fieldType));
	}

	private function extractStructInitializerFieldName(mixed $keyNode): ?string
	{
		if (is_string($keyNode) && $keyNode !== '') {
			return $keyNode;
		}
		return null;
	}

	private function renderTypedFixedArrayLiteral(mixed $expr, ?string $namespacePhp, string $mappedFixedArrayType): string
	{
		$elements = is_object($expr) && isset($expr->children) && is_array($expr->children)
			? array_values($expr->children)
			: [];
		$parts = $this->parseMappedFixedArrayType($mappedFixedArrayType);
		if ($parts === null) {
			$this->errors[] = 'Unsupported fixed_array mapping for ' . $mappedFixedArrayType . '.';
			return '/* unsupported-fixed-array */';
		}
		if (count($elements) !== $parts['size']) {
			$this->errors[] = 'fixed_array literal size mismatch: declared ' . $parts['size'] . ' element(s), got ' . count($elements) . '.';
			return '/* fixed-array-size-mismatch */';
		}

		$values = [];
		foreach ($elements as $element) {
			if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
				$this->errors[] = 'Unsupported fixed_array literal element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
				return '/* unsupported-fixed-array-literal */';
			}
			if (($element->children['key'] ?? null) !== null) {
				$this->errors[] = 'fixed_array literals cannot contain explicit keys at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-keyed-fixed-array-literal */';
			}
			$valueNode = $element->children['value'] ?? null;
			if ($valueNode === null) {
				$this->errors[] = 'Array unpack and empty fixed_array elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-fixed-array-element */';
			}
			if (is_object($valueNode) && (($valueNode->kind ?? null) === AstKind::ARRAY)) {
				$nestedLiteral = $this->renderArrayLiteralForExpectedMappedType($valueNode, $namespacePhp, $parts['element']);
				if ($nestedLiteral !== null) {
					$values[] = $nestedLiteral;
					continue;
				}
			}
			$values[] = $this->wrapExprForExpectedType(
				$this->renderExpr($valueNode, $namespacePhp),
				$this->inferExprType($valueNode),
				$parts['element']
			);
		}

		return $mappedFixedArrayType . '{' . implode(', ', $values) . '}';
	}


	private function tryInferStdFunctionTypeFromClosureExpr(mixed $exprNode): ?string
	{
		if (!is_object($exprNode) || !in_array(($exprNode->kind ?? null), [AstKind::CLOSURE, AstKind::ARROW_FUNC], true)) {
			return null;
		}

		$paramsNode = $exprNode->children['params'] ?? null;
		$returnTypeNode = $exprNode->children['returnType'] ?? null;
		$stmtsNode = $exprNode->children['stmts'] ?? null;
		$params = (is_object($paramsNode) && isset($paramsNode->children) && is_array($paramsNode->children))
			? array_values($paramsNode->children)
			: [];
		$statements = $this->buildStatementSequenceFromMixed($stmtsNode);

		$paramTypes = [];
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			if (($param->flags ?? 0) & AstKind::PARAM_VARIADIC) {
				return null;
			}
			if (($param->children['default'] ?? null) !== null) {
				return null;
			}
			$phpType = $this->readAstTypeName($param->children['type'] ?? null);
			if ($phpType === null) {
				return null;
			}
			$isReference = (((int) ($param->flags ?? 0)) & AstKind::PARAM_REF) !== 0;
			$paramTypes[] = $this->typeMapper->mapParamType($phpType, $isReference);
		}

		$returnType = $this->renderClosureReturnType($returnTypeNode, $statements, $exprNode);
		if ($returnType === '/* unsupported-closure-return-type */' || $returnType === null) {
			return null;
		}

		return 'std::function<' . $returnType . '(' . implode(', ', $paramTypes) . ')>';
	}

	/** @return null|array{returnType:?string,paramTypes:list<string>} */
	private function parseExpectedClosureSignature(?string $typedLocalType): ?array
	{
		if ($typedLocalType === null) {
			return null;
		}
		$normalized = trim($typedLocalType);
		if (preg_match('/^function\s*<\s*(.+)\s*>$/', $normalized, $matches) !== 1) {
			return null;
		}
		$inner = trim($matches[1]);
		$signature = $this->splitFunctionTypeSignature($inner);
		if ($signature === null) {
			return null;
		}
		$returnPhpType = $signature['return'];
		$paramsInner = $signature['params'];
		$returnType = $returnPhpType !== '' ? $this->typeMapper->mapReturnType($returnPhpType, false) : null;
		$paramTypes = [];
		foreach ($this->splitTopLevelCommaList($paramsInner) as $param) {
			$param = trim($param);
			if ($param === '') {
				continue;
			}
			$byRef = false;
			if (str_starts_with($param, 'ref ')) {
				$byRef = true;
				$param = trim(substr($param, 4));
			} elseif (str_ends_with($param, '&')) {
				$byRef = true;
				$param = rtrim(substr($param, 0, -1));
			}
			$paramTypes[] = $this->typeMapper->mapParamType($param, $byRef);
		}
		return ['returnType' => $returnType, 'paramTypes' => $paramTypes];
	}

	/** @return array{return:string,params:string}|null */
	private function splitFunctionTypeSignature(string $inner): ?array
	{
		$angleDepth = 0;
		$parenDepth = 0;
		$open = null;
		$length = strlen($inner);
		for ($i = 0; $i < $length; ++$i) {
			$ch = $inner[$i];
			if ($ch === '<') {
				++$angleDepth;
				continue;
			}
			if ($ch === '>') {
				--$angleDepth;
				continue;
			}
			if ($ch === '(') {
				if ($angleDepth === 0 && $parenDepth === 0) {
					$open = $i;
					break;
				}
				++$parenDepth;
				continue;
			}
			if ($ch === ')' && $parenDepth > 0) {
				--$parenDepth;
			}
		}

		if (!is_int($open)) {
			return null;
		}

		$angleDepth = 0;
		$parenDepth = 0;
		$close = null;
		for ($i = $open; $i < $length; ++$i) {
			$ch = $inner[$i];
			if ($ch === '<') {
				++$angleDepth;
			} elseif ($ch === '>') {
				--$angleDepth;
			} elseif ($ch === '(') {
				++$parenDepth;
			} elseif ($ch === ')') {
				--$parenDepth;
				if ($angleDepth === 0 && $parenDepth === 0) {
					$close = $i;
					break;
				}
			}
		}

		if (!is_int($close) || $close < $open) {
			return null;
		}

		return [
			'return' => trim(substr($inner, 0, $open)),
			'params' => trim(substr($inner, $open + 1, $close - $open - 1)),
		];
	}

	/** @return list<string> */
	private function splitTopLevelCommaList(string $value): array
	{
		$value = trim($value);
		if ($value === '') {
			return [];
		}
		$out = [];
		$current = '';
		$angleDepth = 0;
		$parenDepth = 0;
		$length = strlen($value);
		for ($i = 0; $i < $length; ++$i) {
			$ch = $value[$i];
			if ($ch === '<') {
				++$angleDepth;
			} elseif ($ch === '>') {
				--$angleDepth;
			} elseif ($ch === '(') {
				++$parenDepth;
			} elseif ($ch === ')') {
				--$parenDepth;
			} elseif ($ch === ',' && $angleDepth === 0 && $parenDepth === 0) {
				$out[] = trim($current);
				$current = '';
				continue;
			}
			$current .= $ch;
		}
		if (trim($current) !== '') {
			$out[] = trim($current);
		}
		return $out;
	}

	/** @return list<Statement> */
	private function buildStatementSequenceFromMixed(mixed $node): array
	{
		if (is_object($node) && isset($node->children) && is_array($node->children) && (($node->kind ?? null) === AstKind::STMT_LIST)) {
			return $this->buildStatementsFromAstNodes(array_values($node->children));
		}
		$stmt = $this->buildStatementFromAstNode($node);
		return $stmt !== null ? [$stmt] : [];
	}

	/** @param list<string> $paramNames @return list<string> */
	private function collectImplicitCaptureNames(mixed $node, array $paramNames): array
	{
		$paramSet = [];
		foreach ($paramNames as $name) {
			$paramSet[$name] = true;
		}
		$captureSet = [];
		$this->walkArrowCaptureCandidates($node, $paramSet, $captureSet);
		return array_keys($captureSet);
	}

	/** @param array<string,bool> $paramSet @param array<string,bool> $captureSet */
	private function walkArrowCaptureCandidates(mixed $node, array $paramSet, array &$captureSet): void
	{
		if (!is_object($node)) {
			return;
		}

		$kind = $node->kind ?? null;
		if ($kind === AstKind::VAR) {
			$name = $this->extractSimpleVarName($node);
			if ($name !== null && !isset($paramSet[$name]) && isset($this->declaredLocals[$name])) {
				$captureSet[$name] = true;
			}
			return;
		}

		foreach (($node->children ?? []) as $child) {
			if (is_array($child)) {
				foreach ($child as $subChild) {
					$this->walkArrowCaptureCandidates($subChild, $paramSet, $captureSet);
				}
				continue;
			}
			$this->walkArrowCaptureCandidates($child, $paramSet, $captureSet);
		}
	}

	private function resolveAstParamDocType(object $param): ?string
	{
		$name = (string) ($param->children['name'] ?? '');
		$line = (int) ($param->lineno ?? 0);
		if ($name === '' || $line <= 0) {
			return null;
		}
		$key = $line . ':' . $name;
		$fromMap = $this->localTypeComments[$key] ?? null;
		return is_string($fromMap) && $fromMap !== '' ? $fromMap : null;
	}

	private function appendLvalueReferenceType(string $mappedType): string
	{
		return str_ends_with($mappedType, '&') ? $mappedType : ($mappedType . '&');
	}

	private function mapClosureDocParamType(string $docType, bool $isReference): string
	{
		return $this->typeMapper->mapParamType($this->qualifyDeclaredPhpType($docType, $this->currentNamespacePhp), $isReference);
	}

	private function renderArrowFunctionExpr(object $expr, ?string $namespacePhp): string
	{
		$paramsNode = $expr->children['params'] ?? null;
		$stmtsNode = $expr->children['stmts'] ?? null;
		$returnTypeNode = $expr->children['returnType'] ?? null;

		$params = (is_object($paramsNode) && isset($paramsNode->children) && is_array($paramsNode->children))
			? array_values($paramsNode->children)
			: [];
		$statements = $this->buildStatementSequenceFromMixed($stmtsNode);
		$paramNames = [];
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			$name = (string) ($param->children['name'] ?? '');
			if ($name !== '') {
				$paramNames[] = $name;
			}
		}
		$captureItems = $this->collectImplicitCaptureNames($stmtsNode, $paramNames);

		$expectedSignature = $this->currentExpectedClosureSignature;
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			if (($param->flags ?? 0) & AstKind::PARAM_VARIADIC) {
				$this->errors[] = 'Arrow function variadic parameters are not supported yet at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-arrow-variadic */';
			}
			if (($param->children['default'] ?? null) !== null) {
				$this->errors[] = 'Arrow function default parameters are not supported yet when lowering to std::function at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-arrow-default-param */';
			}
			$expectedParamType = $expectedSignature['paramTypes'][$paramIndex] ?? null;
			$phpType = $this->readAstTypeName($param->children['type'] ?? null);
			$docType = $this->resolveAstParamDocType($param);
			if ($phpType !== null && $docType !== null) {
				$this->errors[] = 'Conflicting arrow function parameter type sources for $' . (string) ($param->children['name'] ?? '') . ' at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . ': use either a native PHP type or a doc-comment type, not both.';
				return '/* unsupported-arrow-conflicting-param-type */';
			}
			if ($phpType === null && $docType === null && !is_string($expectedParamType)) {
				$this->errors[] = 'Arrow function parameters require PHP signature types or explicit doc-comment types in std::function lowering at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-arrow-untyped-param */';
			}
			$paramIndex++;
		}

		$captureRenderItems = [];
		foreach ($captureItems as $captureName) {
			$captureRenderItems[] = $this->renderClosureCaptureItem($captureName, false);
		}
		$capture = $captureRenderItems === [] ? '[]' : '[' . implode(', ', $captureRenderItems) . ']';
		$paramList = $this->renderClosureParams($params, $namespacePhp);
		$returnType = $this->renderClosureReturnType($returnTypeNode, $statements, $expr);
		if (is_string($returnType) && str_starts_with($returnType, '/* unsupported-closure-')) {
			return $returnType;
		}

		$savedDeclaredLocals = $this->declaredLocals;
		$savedDeclaredLocalTypes = $this->declaredLocalTypes;
		$savedReferenceLocals = $this->predefinedReferenceLocals;
		$savedReturnType = $this->currentReturnType;

		foreach ($captureItems as $captureName) {
			$this->declaredLocals[$captureName] = true;
			if (isset($savedDeclaredLocalTypes[$captureName])) {
				$this->declaredLocalTypes[$captureName] = $savedDeclaredLocalTypes[$captureName];
			}
		}
		$captureShadowNames = [];
		foreach ($captureItems as $captureName) {
			if ($this->hasForeachReferenceSlotAlias($captureName)) {
				$captureShadowNames[$captureName] = true;
			}
		}
		$this->foreachReferenceSuppressedNamesStack[] = $captureShadowNames;
		$captureShadowNames = [];
		foreach ($captureItems as $captureName) {
			if ($this->hasForeachReferenceSlotAlias($captureName)) {
				$captureShadowNames[$captureName] = true;
			}
		}
		$this->foreachReferenceSuppressedNamesStack[] = $captureShadowNames;
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			$name = (string) ($param->children['name'] ?? '');
			if ($name === '') {
				continue;
			}
			$this->declaredLocals[$name] = true;
			$expectedParamType = $expectedSignature['paramTypes'][$paramIndex] ?? null;
			$storedType = $this->inferClosureParamStoredType($param, $expectedParamType);
			if ($storedType !== null) {
				$this->declaredLocalTypes[$name] = $storedType;
			}
			$paramIndex++;
		}
		$this->currentReturnType = $returnType !== 'void' ? $returnType : null;

		$bodyLines = $this->flattenCodeText($this->renderStatementSequence($statements, $namespacePhp));

		array_pop($this->foreachReferenceSuppressedNamesStack);
		array_pop($this->foreachReferenceSuppressedNamesStack);
		$this->declaredLocals = $savedDeclaredLocals;
		$this->declaredLocalTypes = $savedDeclaredLocalTypes;
		$this->predefinedReferenceLocals = $savedReferenceLocals;
		$this->currentReturnType = $savedReturnType;

		$out = [];
		$signature = $capture . '(' . $paramList . ')';
		if ($captureItems !== []) {
			$signature .= ' mutable';
		}
		if ($returnType !== null) {
			$signature .= ' -> ' . $returnType;
		}
		$signature .= ' {';
		$out[] = $signature;
		foreach ($bodyLines as $line) {
			$out[] = $this->indent(1) . ($line instanceof CodeBlock ? $line->text : (string) $line);
		}
		$out[] = '}';

		return implode("
", $out);
	}

	private function renderClosureExpr(object $expr, ?string $namespacePhp): string
	{
		$paramsNode = $expr->children['params'] ?? null;
		$useNode = $expr->children['uses'] ?? null;
		$stmtsNode = $expr->children['stmts'] ?? null;
		$returnTypeNode = $expr->children['returnType'] ?? null;

		$params = (is_object($paramsNode) && isset($paramsNode->children) && is_array($paramsNode->children))
			? array_values($paramsNode->children)
			: [];
		$uses = (is_object($useNode) && isset($useNode->children) && is_array($useNode->children))
			? array_values($useNode->children)
			: [];
		$statements = (is_object($stmtsNode) && isset($stmtsNode->children) && is_array($stmtsNode->children))
			? $this->buildStatementsFromAstNodes(array_values($stmtsNode->children))
			: [];

		$captureItems = [];
		$captureByReference = [];
		foreach ($uses as $use) {
			if (!is_object($use)) {
				continue;
			}
			$isUseByReference = false;
			if (($use->kind ?? null) === AstKind::REF) {
				$isUseByReference = true;
			} elseif (($use->kind ?? null) === AstKind::CLOSURE_VAR && (((int) ($use->flags ?? 0)) & 1) !== 0) {
				// php-ast represents `use (&$x)` as AST_CLOSURE_VAR with a by-reference flag,
				// not as a nested AST_REF node.
				$isUseByReference = true;
			}
			$name = (string) ($use->children['name'] ?? '');
			if ($name === '') {
				$this->errors[] = 'Closure use-capture requires a simple variable name at line ' . (int) ($use->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-use */';
			}
			$captureItems[] = $name;
			$captureByReference[$name] = $isUseByReference;
		}

		$expectedSignature = $this->currentExpectedClosureSignature;
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			if (($param->flags ?? 0) & AstKind::PARAM_VARIADIC) {
				$this->errors[] = 'Closure variadic parameters are not supported yet at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-variadic */';
			}
			if (($param->children['default'] ?? null) !== null) {
				$this->errors[] = 'Closure default parameters are not supported yet when lowering to std::function at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-default-param */';
			}
			$expectedParamType = $expectedSignature['paramTypes'][$paramIndex] ?? null;
			$phpType = $this->readAstTypeName($param->children['type'] ?? null);
			$docType = $this->resolveAstParamDocType($param);
			if ($phpType !== null && $docType !== null) {
				$this->errors[] = 'Conflicting closure parameter type sources for $' . (string) ($param->children['name'] ?? '') . ' at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . ': use either a native PHP type or a doc-comment type, not both.';
				return '/* unsupported-closure-conflicting-param-type */';
			}
			if ($phpType === null && $docType === null) {
				$this->errors[] = 'Closure parameters require PHP signature types or explicit doc-comment types in std::function lowering at line ' . (int) ($param->lineno ?? $expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-untyped-param */';
			}
			$paramIndex++;
		}

		$captureRenderItems = [];
		foreach ($captureItems as $captureName) {
			$captureRenderItems[] = $this->renderClosureCaptureItem($captureName, $captureByReference[$captureName] ?? false);
		}
		$capture = $captureRenderItems === [] ? '[]' : '[' . implode(', ', $captureRenderItems) . ']';
		$paramList = $this->renderClosureParams($params, $namespacePhp);
		$returnType = $this->renderClosureReturnType($returnTypeNode, $statements, $expr);
		if ($returnType === '/* unsupported-closure-return-type */') {
			return $returnType;
		}

		$savedDeclaredLocals = $this->declaredLocals;
		$savedDeclaredLocalTypes = $this->declaredLocalTypes;
		$savedReferenceLocals = $this->predefinedReferenceLocals;
		$savedReturnType = $this->currentReturnType;

		foreach ($captureItems as $captureName) {
			$this->declaredLocals[$captureName] = true;
			if (isset($savedDeclaredLocalTypes[$captureName])) {
				$this->declaredLocalTypes[$captureName] = $savedDeclaredLocalTypes[$captureName];
			}
		}
		$captureShadowNames = [];
		foreach ($captureItems as $captureName) {
			if ($this->hasForeachReferenceSlotAlias($captureName)) {
				$captureShadowNames[$captureName] = true;
			}
		}
		$this->foreachReferenceSuppressedNamesStack[] = $captureShadowNames;
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			$name = (string) ($param->children['name'] ?? '');
			if ($name === '') {
				continue;
			}
			$this->declaredLocals[$name] = true;
			$expectedParamType = $expectedSignature['paramTypes'][$paramIndex] ?? null;
			$storedType = $this->inferClosureParamStoredType($param, $expectedParamType);
			if ($storedType !== null) {
				$this->declaredLocalTypes[$name] = $storedType;
			}
			$paramIndex++;
		}
		$this->currentReturnType = $returnType !== 'void' ? $returnType : null;

		$bodyLines = $this->flattenCodeText($this->renderStatementSequence($statements, $namespacePhp));

		array_pop($this->foreachReferenceSuppressedNamesStack);
		$this->declaredLocals = $savedDeclaredLocals;
		$this->declaredLocalTypes = $savedDeclaredLocalTypes;
		$this->predefinedReferenceLocals = $savedReferenceLocals;
		$this->currentReturnType = $savedReturnType;

		$out = [];
		$signature = $capture . '(' . $paramList . ')';
		if ($captureItems !== []) {
			// PHP closures captured by value may still assign to the captured name locally.
			// In C++, lambda operator() is const by default, so mark captured closures mutable.
			$signature .= ' mutable';
		}
		if ($returnType !== null) {
			$signature .= ' -> ' . $returnType;
		}
		$signature .= ' {';
		$out[] = $signature;
		foreach ($bodyLines as $line) {
			$out[] = $this->indent(1) . ($line instanceof CodeBlock ? $line->text : (string) $line);
		}
		$out[] = '}';

		return implode("
", $out);
	}

	/** @param list<mixed> $nodes @return list<Statement> */
	private function buildStatementsFromAstNodes(array $nodes): array
	{
		$out = [];
		foreach ($nodes as $node) {
			$stmt = $this->buildStatementFromAstNode($node);
			if ($stmt !== null) {
				$out[] = $stmt;
			}
		}
		return $out;
	}

	private function buildStatementFromAstNode(mixed $node): ?Statement
	{
		$kind = $node->kind ?? null;
		$line = (int) ($node->lineno ?? 0);

		if ($kind === AstKind::ASSIGN) {
			return new Statement('assign', $node->children ?? [], $line);
		}
		if ($kind === AstKind::ASSIGN_REF) {
			return new Statement('assign_ref', $node->children ?? [], $line);
		}
		if ($kind === AstKind::ASSIGN_OP) {
			$payload = $node->children ?? [];
			$payload['flags'] = (int) ($node->flags ?? 0);
			return new Statement('assign_op', $payload, $line);
		}
		if ($kind === AstKind::RETURN) {
			return new Statement('return', $node->children['expr'] ?? null, $line);
		}
		if ($kind === AstKind::THROW) {
			return new Statement('throw', $node->children['expr'] ?? null, $line);
		}
		if ($kind === AstKind::TRY) {
			$catches = [];
			foreach (($node->children['catches']->children ?? []) as $catchNode) {
				if (!is_object($catchNode) || (($catchNode->kind ?? null) !== AstKind::CATCH)) {
					continue;
				}
				$classNode = $catchNode->children['class'] ?? null;
				$classKinds = is_object($classNode) && isset($classNode->children) && is_array($classNode->children)
					? array_values($classNode->children)
					: [$classNode];
				$stmtsNode = $catchNode->children['stmts'] ?? null;
				$catches[] = [
					'classes' => $classKinds,
					'var' => $catchNode->children['var'] ?? null,
					'stmts' => (is_object($stmtsNode) && isset($stmtsNode->children) && is_array($stmtsNode->children))
						? $this->buildStatementsFromAstNodes(array_values($stmtsNode->children))
						: [],
					'line' => (int) ($catchNode->lineno ?? $line),
				];
			}
			$tryNode = $node->children['try'] ?? null;
			$finallyNode = $node->children['finally'] ?? null;
			return new Statement('try', [
				'try' => (is_object($tryNode) && isset($tryNode->children) && is_array($tryNode->children))
					? $this->buildStatementsFromAstNodes(array_values($tryNode->children))
					: [],
				'catches' => $catches,
				'finally' => (is_object($finallyNode) && isset($finallyNode->children) && is_array($finallyNode->children))
					? $this->buildStatementsFromAstNodes(array_values($finallyNode->children))
					: [],
			], $line);
		}
		if ($kind === AstKind::AST_ECHO) {
			return new Statement('echo', $node->children['expr'] ?? null, $line);
		}
		if ($kind === AstKind::AST_UNSET) {
			return new Statement('unset', $node->children['var'] ?? null, $line);
		}
		if ($kind === AstKind::IF) {
			$branches = [];
			$children = (isset($node->children) && is_array($node->children)) ? array_values($node->children) : [];
			foreach ($children as $ifElem) {
				if (!is_object($ifElem) || (($ifElem->kind ?? null) !== AstKind::IF_ELEM)) {
					continue;
				}
				$branchStmtsNode = $ifElem->children['stmts'] ?? null;
				$branchStmts = (is_object($branchStmtsNode) && isset($branchStmtsNode->children) && is_array($branchStmtsNode->children))
					? $this->buildStatementsFromAstNodes(array_values($branchStmtsNode->children))
					: [];
				$branches[] = [
					'cond' => $ifElem->children['cond'] ?? null,
					'stmts' => $branchStmts,
					'line' => (int) ($ifElem->lineno ?? $line),
				];
			}
			return new Statement('if', $branches, $line);
		}
		if ($kind === AstKind::WHILE) {
			$stmtsNode = $node->children['stmts'] ?? null;
			return new Statement('while', [
				'cond' => $node->children['cond'] ?? null,
				'stmts' => (is_object($stmtsNode) && isset($stmtsNode->children) && is_array($stmtsNode->children))
					? $this->buildStatementsFromAstNodes(array_values($stmtsNode->children))
					: [],
			], $line);
		}
		if ($kind === AstKind::DO_WHILE) {
			$stmtsNode = $node->children['stmts'] ?? null;
			return new Statement('do_while', [
				'cond' => $node->children['cond'] ?? null,
				'stmts' => (is_object($stmtsNode) && isset($stmtsNode->children) && is_array($stmtsNode->children))
					? $this->buildStatementsFromAstNodes(array_values($stmtsNode->children))
					: [],
			], $line);
		}
		if ($kind === AstKind::BREAK) {
			return new Statement('break', $node->children['depth'] ?? null, $line);
		}
		if ($kind === AstKind::CONTINUE) {
			return new Statement('continue', $node->children['depth'] ?? null, $line);
		}
		if ($kind === AstKind::CALL || $kind === AstKind::STATIC_CALL || $kind === AstKind::METHOD_CALL || $kind === AstKind::PRE_INC || $kind === AstKind::PRE_DEC || $kind === AstKind::POST_INC || $kind === AstKind::POST_DEC) {
			return new Statement('expr', $node, $line);
		}
		return null;
	}

	/** @param list<mixed> $params */
	private function renderClosureParams(array $params, ?string $namespacePhp): string
	{
		$out = [];
		$paramIndex = 0;
		foreach ($params as $param) {
			if (!is_object($param) || (($param->kind ?? null) !== AstKind::PARAM)) {
				continue;
			}
			$name = (string) ($param->children['name'] ?? '');
			if ($name === '') {
				continue;
			}
			$type = $this->renderClosureParamType($param, $paramIndex);
			$rendered = $type . ' ' . $name;
			$default = $param->children['default'] ?? null;
			if ($default !== null) {
				$rendered .= ' = ' . $this->renderExpr($default, $namespacePhp);
			}
			$out[] = $rendered;
			$paramIndex++;
		}
		return implode(', ', $out);
	}

	private function renderClosureParamType(object $param, int $paramIndex): string
	{
		$expectedParamType = $this->currentExpectedClosureSignature['paramTypes'][$paramIndex] ?? null;
		$phpType = $this->readAstTypeName($param->children['type'] ?? null);
		$docType = $this->resolveAstParamDocType($param);
		$isReference = (((int) ($param->flags ?? 0)) & AstKind::PARAM_REF) !== 0;
		if ($phpType !== null) {
			return $this->typeMapper->mapParamType($this->qualifyDeclaredPhpType($phpType, $this->currentNamespacePhp), $isReference);
		}
		if ($docType !== null) {
			return $this->mapClosureDocParamType($docType, $isReference);
		}
		if (is_string($expectedParamType) && $expectedParamType !== '') {
			return $expectedParamType;
		}
		$default = $param->children['default'] ?? null;
		if ($default !== null) {
			$defaultType = $this->inferExprType($default);
			if ($defaultType !== 'auto') {
				return $defaultType;
			}
		}
		return $isReference ? 'auto&' : 'auto';
	}

	private function inferClosureParamStoredType(object $param, ?string $expectedParamType = null): ?string
	{
		$phpType = $this->readAstTypeName($param->children['type'] ?? null);
		$docType = $this->resolveAstParamDocType($param);
		$isReference = (((int) ($param->flags ?? 0)) & AstKind::PARAM_REF) !== 0;
		if ($phpType !== null) {
			return $this->typeMapper->mapParamType($this->qualifyDeclaredPhpType($phpType, $this->currentNamespacePhp), $isReference);
		}
		if ($docType !== null) {
			return $this->normalizeStoredLocalType($this->qualifyDeclaredPhpType($docType, $this->currentNamespacePhp));
		}
		if (is_string($expectedParamType) && $expectedParamType !== '') {
			return $expectedParamType;
		}
		$default = $param->children['default'] ?? null;
		if ($default !== null) {
			$defaultType = $this->inferExprType($default);
			return $defaultType !== 'auto' ? $defaultType : null;
		}
		return null;
	}

	/** @param list<Statement> $statements */
	private function renderClosureReturnType(mixed $returnTypeNode, array $statements, object $expr): ?string
	{
		$phpType = $this->readAstTypeName($returnTypeNode);
		$docFunctionType = $this->resolveScannerClosureReturnType($expr);
		if ($phpType !== null && $docFunctionType !== null) {
			$this->errors[] = 'Conflicting closure return type sources at line ' . (int) ($expr->lineno ?? 0) . ': use either a native PHP return type or a doc-comment callable return type, not both.';
			return '/* unsupported-closure-conflicting-return-type */';
		}
		if ($phpType !== null) {
			return $this->typeMapper->mapReturnType($this->qualifyDeclaredPhpType($phpType, $this->currentNamespacePhp), false);
		}

		if ($docFunctionType !== null) {
			return $this->typeMapper->mapTypedLocalType($docFunctionType);
		}

		$expectedReturnType = $this->currentExpectedClosureSignature['returnType'] ?? null;
		if (is_string($expectedReturnType) && $expectedReturnType !== '') {
			return $expectedReturnType;
		}

		foreach ($statements as $statement) {
			if ($statement->kind === 'return' && $statement->payload !== null) {
				$this->errors[] = 'Closure return types must be declared explicitly in std::function lowering at line ' . (int) ($expr->lineno ?? 0) . '.';
				return '/* unsupported-closure-return-type */';
			}
		}

		return 'void';
	}

	private function resolveScannerClosureReturnType(object $expr): ?string
	{
		$line = (int) ($expr->lineno ?? 0);
		if ($line <= 0) {
			return null;
		}
		$type = $this->scannerReturnAnnotationsByLine[$line] ?? null;
		if (!is_string($type) || $type === '') {
			return null;
		}
		return $type;
	}

	private function qualifyDeclaredPhpType(?string $phpType, ?string $namespacePhp): ?string
	{
		if ($phpType === null) {
			return null;
		}

		$normalized = $this->typeMapper->normalizeNullableUnionType($phpType);
		$normalized = trim($normalized);
		if ($normalized === '') {
			return $normalized;
		}
		if (preg_match('/^(?:0|[1-9][0-9]*)$/', $normalized) === 1) {
			return $normalized;
		}

		$unionParts = $this->typeMapper->splitUnionTypes($normalized);
		if (count($unionParts) > 1) {
			$qualifiedUnionParts = [];
			foreach ($unionParts as $part) {
				$qualifiedUnionParts[] = $this->qualifyDeclaredPhpType($part, $namespacePhp) ?? $part;
			}
			return implode('|', $qualifiedUnionParts);
		}

		if (str_starts_with($normalized, '?')) {
			$inner = trim(substr($normalized, 1));
			return '?' . ($this->qualifyDeclaredPhpType($inner, $namespacePhp) ?? $inner);
		}

		if (preg_match('/^(nullable|value|shared|unique|weak|weakref|shared_p|unique_p|weak_p|vector|vector_t|fixed_array|fixed_array_t|hash|hash_t|result_or_false|result_or_bool|result)\s*<\s*(.+)\s*>$/', $normalized, $matches) === 1) {
			$wrapper = $matches[1];
			$args = $this->typeMapper->splitTopLevelGenericArgs($matches[2]);
			$qualifiedArgs = [];
			foreach ($args as $arg) {
				$qualifiedArgs[] = $this->qualifyDeclaredPhpType($arg, $namespacePhp) ?? trim($arg);
			}
			return $wrapper . '<' . implode(', ', $qualifiedArgs) . '>';
		}

		return $this->resolveDeclaredClassLikeType($normalized, $namespacePhp);
	}

	private function resolveDeclaredClassLikeType(string $phpType, ?string $namespacePhp): string
	{
		$normalized = trim($phpType);
		if ($normalized === '' || str_contains($normalized, '::')) {
			return $normalized;
		}

		if (preg_match('/^(?:int|int8|int16|int32|int64|uint8|byte|uint16|uint32|uint64|float|bool|string|array|mixed|void|int_t|int_t<>|float_t|bool_t|string_t|mixed_t|hash_t|vector_t|fixed_array_t)$/', $normalized) === 1) {
			return $normalized;
		}

		if (in_array($normalized, ['self', 'parent', 'static'], true)) {
			return $normalized;
		}

		$flags = str_starts_with($normalized, '\\') ? 0 : 1;
		$resolved = $this->nameRegistry->resolveClass($normalized, $flags, $namespacePhp);
		if (is_string($resolved) && $resolved !== '') {
			return $resolved;
		}

		return $normalized;
	}

	private function readAstTypeName(mixed $typeNode): ?string
	{
		if (!is_object($typeNode)) {
			return null;
		}
		$kind = (int) ($typeNode->kind ?? 0);
		$flags = (int) ($typeNode->flags ?? 0);
		if ($kind === AstKind::NULLABLE_TYPE) {
			$inner = $this->readAstTypeName($typeNode->children['type'] ?? null);
			return $inner !== null ? '?' . ltrim($inner, '?') : null;
		}
		if ($kind === AstKind::NAME) {
			$name = (string) ($typeNode->children['name'] ?? '');
			return $name !== '' ? $name : null;
		}
		return match ($flags) {
			AstKind::TYPE_BOOL => 'bool',
			AstKind::TYPE_LONG => 'int',
			AstKind::TYPE_DOUBLE => 'float',
			AstKind::TYPE_STRING => 'string',
			AstKind::TYPE_VOID => 'void',
			AstKind::TYPE_MIXED => 'mixed',
			default => null,
		};
	}

	/**
	 * Renders one expression node from php-ast into the current Prism++ expression subset.
	 *
	 * Relationship to specs:
	 * - preserves the subset and lowering rules documented for the prototype
	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit
	 */
	private function renderExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (is_int($expr)) {
			return 'static_cast<int_t<> >(' . $expr . ')';
		}
		if (is_float($expr)) {
			return 'static_cast<float_t>(' . $expr . ')';
		}
		if (is_string($expr)) {
			return 'string_t(' . json_encode($expr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ')';
		}
		if (!is_object($expr)) {
			$this->errors[] = 'Unsupported expression value in generator input. Category: generator lowering gap. Requirement: pass a scalar literal or AST node supported by the current lowering surface.';
			return '/* unsupported-expr */';
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::CLOSURE) {
			return $this->renderClosureExpr($expr, $namespacePhp);
		}
		if ($kind === AstKind::ARROW_FUNC) {
			return $this->renderArrowFunctionExpr($expr, $namespacePhp);
		}
		if ($kind === AstKind::ARRAY) {
			return $this->renderArrayLiteral($expr, $namespacePhp);
		}
		if ($kind === AstKind::VAR) {
			$name = (string) ($expr->children['name'] ?? 'var');
			if ($name === 'this') {
				return 'this';
			}
			$hasForeachByRefAlias = $this->hasForeachReferenceSlotAlias($name);
			if ($name !== '' && !isset($this->declaredLocals[$name]) && !$hasForeachByRefAlias) {
				$this->errors[] = $this->buildUndeclaredVariableVisibilityError($name, (int) ($expr->lineno ?? 0));
				return '/* undeclared-var-' . $name . ' */';
			}
			return $this->renderVar($expr);
		}
		if ($kind === AstKind::CONST) {
			$name = (string) ($expr->children['name']->children['name'] ?? '');
			$flags = (int) ($expr->children['name']->flags ?? 0);
			return match (strtolower(ltrim($name, '\\'))) {
				'true' => 'static_cast<bool_t>(true)',
				'false' => 'static_cast<bool_t>(false)',
				'null' => 'null',
				default => $this->renderConstantName($name, $flags, $namespacePhp),
			};
		}
		if ($kind === AstKind::BINARY_OP) {
			$leftNode = $expr->children['left'] ?? null;
			$rightNode = $expr->children['right'] ?? null;
			$left = $this->renderExpr($leftNode, $namespacePhp);
			$right = $this->renderExpr($rightNode, $namespacePhp);
			$flags = (int) ($expr->flags ?? 0);
			if ($flags === AstKind::BINARY_IS_EQUAL || $flags === AstKind::BINARY_IS_NOT_EQUAL) {
				$leftType = $this->inferConstantType($leftNode, $namespacePhp);
				$rightType = $this->inferConstantType($rightNode, $namespacePhp);
				if ($leftType === $rightType && $this->isKnownEnumTypeName($leftType)) {
					$comparison = '(' . $left . ' == ' . $right . ')';
					if ($flags === AstKind::BINARY_IS_NOT_EQUAL) {
						$comparison = '(!' . $comparison . ')';
					}
					return 'bool_t(' . $comparison . ')';
				}
			}

			return match ($flags) {
				AstKind::PLUS => '(' . $left . ' + ' . $right . ')',
				AstKind::MINUS => '(' . $left . ' - ' . $right . ')',
				AstKind::MUL => '(' . $left . ' * ' . $right . ')',
				AstKind::DIV => '(' . $left . ' / ' . $right . ')',
				AstKind::MOD => '(' . $left . ' % ' . $right . ')',
				AstKind::BITWISE_OR => '(' . $left . ' | ' . $right . ')',
				AstKind::BITWISE_AND => '(' . $left . ' & ' . $right . ')',
				AstKind::BITWISE_XOR => '(' . $left . ' ^ ' . $right . ')',
				AstKind::SHIFT_LEFT => '(' . $left . ' << ' . $right . ')',
				AstKind::SHIFT_RIGHT => '(' . $left . ' >> ' . $right . ')',
				AstKind::BINARY_CONCAT => $this->renderStringConcat($leftNode, $rightNode, $namespacePhp),
				AstKind::BINARY_BOOL_AND => '(' . $left . ' && ' . $right . ')',
				AstKind::BINARY_BOOL_OR => '(' . $left . ' || ' . $right . ')',
				AstKind::BINARY_IS_SMALLER => '(' . $left . ' < ' . $right . ')',
				AstKind::BINARY_IS_SMALLER_OR_EQUAL => '(' . $left . ' <= ' . $right . ')',
				AstKind::BINARY_IS_GREATER => '(' . $left . ' > ' . $right . ')',
				AstKind::BINARY_IS_NOT_EQUAL => '(!(' . $left . ' == ' . $right . '))',
				AstKind::BINARY_IS_EQUAL => '(' . $left . ' == ' . $right . ')',
				AstKind::BINARY_IS_IDENTICAL => $this->qualifyKnownPhpRuntimeSymbol('identical') . '(' . $left . ', ' . $right . ')',
				AstKind::BINARY_IS_NOT_IDENTICAL => $this->qualifyKnownPhpRuntimeSymbol('not_identical') . '(' . $left . ', ' . $right . ')',
				257 => '(' . $left . ' >= ' . $right . ')',
				AstKind::BINARY_COALESCE => $this->renderCoalesceExpr($leftNode, $rightNode, $namespacePhp),
				default => '/* unsupported-binary-op-' . $flags . ' */',
			};
		}
		if ($kind === AstKind::CAST) {
			$innerNode = $expr->children['expr'] ?? null;
			$inner = $this->renderExpr($innerNode, $namespacePhp);
			$flags = (int) ($expr->flags ?? 0);
			return match ($flags) {
				AstKind::TYPE_STRING => $this->renderGeneratedCast('string_t', $inner),
				AstKind::TYPE_LONG => $this->renderGeneratedCast('int_t<>', $inner),
				AstKind::TYPE_DOUBLE => $this->renderGeneratedCast('float_t', $inner),
				AstKind::TYPE_BOOL => $this->renderGeneratedCast('bool_t', $inner),
				AstKind::TYPE_OBJECT => $this->renderObjectCastExpr($innerNode, $namespacePhp),
				default => '/* unsupported-cast */',
			};
		}
		if ($kind === AstKind::ARRAY) {
			return $this->renderArrayLiteral($expr, $namespacePhp);
		}
		if ($kind === AstKind::ENCAPS_LIST) {
			return $this->renderInterpolatedString($expr, $namespacePhp);
		}
		if ($kind === AstKind::DIM) {
			return $this->renderDimAccess($expr, $namespacePhp);
		}
		if ($kind === AstKind::PROP) {
			$baseExpr = $expr->children['expr'] ?? null;
			$base = $this->renderExpr($baseExpr, $namespacePhp);
			$propName = (string) ($expr->children['prop'] ?? 'prop');
			$baseType = $this->inferExprType($baseExpr);
			if ($baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
				return $base . '.get(string_t(' . json_encode($propName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '))';
			}
			$prop = $this->cppIdentifier($propName);
			return $base === 'this' ? 'this->' . $prop : $base . '->' . $prop;
		}
		if ($kind === AstKind::NULLSAFE_PROP) {
			$baseExpr = $expr->children['expr'] ?? null;
			$base = $this->renderExpr($baseExpr, $namespacePhp);
			$prop = $this->cppIdentifier((string) ($expr->children['prop'] ?? 'prop'));
			return '([&]() -> auto { auto __scpp_tmp = ' . $base . '; return static_cast<bool>(isset(__scpp_tmp)) ? __scpp_tmp->' . $prop . ' : null; }())';
		}
		if ($kind === AstKind::STATIC_PROP) {
			$classNode = $expr->children['class'] ?? null;
			if (
				is_object($classNode)
				&& ($classNode->kind ?? null) === AstKind::NAME
				&& strtolower(ltrim((string) ($classNode->children['name'] ?? ''), '\\')) === 'static'
			) {
				return $this->renderLateStaticPropertyAccess($expr, $namespacePhp);
			}
			return $this->renderStaticPropertyAccess($expr, $namespacePhp);
		}
		if ($kind === AstKind::CLASS_CONST) {
			$classNode = $expr->children['class'] ?? null;
			if (
				is_object($classNode)
				&& ($classNode->kind ?? null) === AstKind::NAME
				&& strtolower(ltrim((string) ($classNode->children['name'] ?? ''), '\\')) === 'static'
			) {
				return $this->renderLateStaticClassConstAccess($expr, $namespacePhp);
			}
			$class = $this->renderClassName($expr->children['class'] ?? null, $namespacePhp);
			$const = $this->cppIdentifier((string) ($expr->children['const'] ?? 'CONST'));
			return $class . '::' . $const;
		}
		if ($kind === AstKind::NEW) {
			if ($this->isStdClassNewExpr($expr)) {
				return 'mixed_t{dynamic_()}';
			}
			$classNode = $expr->children['class'] ?? null;
			if (
				is_object($classNode)
				&& ($classNode->kind ?? null) === AstKind::NAME
				&& strtolower(ltrim((string) ($classNode->children['name'] ?? ''), '\\')) === 'static'
			) {
				return $this->renderLateStaticNewExpr($expr, $namespacePhp);
			}
			$class = $this->renderClassName($expr->children['class'] ?? null, $namespacePhp);
			return 'create<' . $class . '>(' . $this->renderArgs($expr->children['args']->children ?? [], $namespacePhp) . ')';
		}
		if ($kind === AstKind::STATIC_CALL) {
			$classNode = $expr->children['class'] ?? null;
			$method = (string) ($expr->children['method'] ?? '');
			$args = $expr->children['args']->children ?? [];
			if (
				is_object($classNode)
				&& ($classNode->kind ?? null) === AstKind::NAME
				&& strtolower(ltrim((string) ($classNode->children['name'] ?? ''), '\\')) === 'static'
			) {
				return $this->renderLateStaticMethodCall($expr, $namespacePhp);
			}
			$class = is_object($classNode) && ($classNode->kind ?? null) === AstKind::VAR
				? 'class_t<decltype(' . $this->renderExpr($classNode, $namespacePhp) . ')>'
				: $this->renderClassName($classNode, $namespacePhp);
			$methodDecl = $this->lookupMethodDeclByStaticCall($classNode, $method, $namespacePhp);
			$renderedArgs = $methodDecl !== null ? $this->renderCallArgsForParams($methodDecl->params, $args, $namespacePhp) : $this->renderArgs($args, $namespacePhp);
			$callExpr = $class . '::' . $this->cppIdentifier($method) . '(' . $renderedArgs . ')';
			if (is_object($classNode) && ($classNode->kind ?? null) === AstKind::NAME) {
				$phpClass = (string) ($classNode->children['name'] ?? '');
				$resolvedKey = $this->resolveClassDeclKey($phpClass, $namespacePhp);
				$classDecl = $resolvedKey !== null ? ($this->classDecls[$resolvedKey] ?? null) : null;
				if ($classDecl instanceof ClassDecl && $classDecl->parentClass !== null) {
					return $this->renderStaticScopeWrapper($class, $class, $callExpr);
				}
			}
			return $callExpr;
		}
		if ($kind === AstKind::AST_ISSET) {
			// In this exporter, multi-argument isset() is already normalized into boolean-op trees.
			// AST_ISSET itself carries exactly one operand in `children['var']`.
			// Keyed reads must stay on the runtime helper path so missing and existing-null do not collapse into pure key-existence semantics.
			$varNode = $expr->children['var'] ?? null;
			if (
				is_object($varNode)
				&& (($varNode->kind ?? null) === AstKind::DIM)
				&& (($varNode->children['dim'] ?? null) !== null)
			) {
				return $this->qualifyKnownPhpRuntimeSymbol('isset') . '('
					. $this->renderExpr($varNode->children['expr'] ?? null, $namespacePhp) . ', '
					. $this->renderExpr($varNode->children['dim'] ?? null, $namespacePhp) . ')';
			}
			if (is_object($varNode) && (($varNode->kind ?? null) === AstKind::VAR)) {
				return $this->qualifyKnownPhpRuntimeSymbol('isset') . '(' . $this->renderExpr($varNode, $namespacePhp) . ')';
			}
			$issetEvalFn = $this->qualifyKnownPhpRuntimeSymbol('isset_eval');
			return $issetEvalFn . '([&]() -> decltype(auto) { return ' . $this->renderExpr($varNode, $namespacePhp) . '; })';
		}
		if ($kind === AstKind::AST_EMPTY) {
			// empty() must evaluate the operand expression through the runtime helper so missing keyed reads,
			// existing null, empty string, and countable-empty values follow the unified narrowed contract.
			$exprNode = $expr->children['expr'] ?? null;
			if (is_object($exprNode) && (($exprNode->kind ?? null) === AstKind::DIM) && (($exprNode->children['dim'] ?? null) !== null)) {
				return $this->qualifyKnownPhpRuntimeSymbol('empty') . '('
					. $this->renderExpr($exprNode->children['expr'] ?? null, $namespacePhp) . ', '
					. $this->renderExpr($exprNode->children['dim'] ?? null, $namespacePhp) . ')';
			}
			return $this->qualifyKnownPhpRuntimeSymbol('empty') . '(' . $this->renderExpr($exprNode, $namespacePhp) . ')';
		}
		if ($kind === AstKind::CALL) {
			$nameExpr = $expr->children['expr'] ?? null;
			$args = $expr->children['args']->children ?? [];
			if ($this->isAsyncWaitCallName($nameExpr)) {
				return $this->renderAsyncWaitCallExpr($args, $namespacePhp, (int) ($expr->lineno ?? 0));
			}
			if ($this->isTakeCallName($nameExpr)) {
				return $this->renderTakeCallExpr($args, $namespacePhp, (int) ($expr->lineno ?? 0));
			}
			if ($this->isDbgCallName($nameExpr)) {
				return $this->renderDbgCallExpr($args, $namespacePhp, (int) ($expr->lineno ?? 0));
			}
			if ($this->isDbgIfCallName($nameExpr)) {
				return $this->renderDbgIfCallExpr($args, $namespacePhp, (int) ($expr->lineno ?? 0));
			}
			if ($this->isScppDebugDumpCallName($nameExpr)) {
				return $this->renderScppDebugDumpCallExpr($args, $namespacePhp, (int) ($expr->lineno ?? 0));
			}
			if ($this->isScppDebugExitCallName($nameExpr)) {
				return $this->renderScppDebugExitCallExpr($args, (int) ($expr->lineno ?? 0));
			}
			if ($this->isScppDebugBreakCallName($nameExpr)) {
				return $this->renderScppDebugBreakCallExpr($args, (int) ($expr->lineno ?? 0));
			}
			if ($this->isLayoutProbeCallName($nameExpr)) {
				return $this->renderLayoutProbeCallExpr($nameExpr, $args, $namespacePhp, (int) ($expr->lineno ?? 0));
			}
			if ($this->isEnumValueCallName($nameExpr)) {
				return $this->renderEnumValueCallExpr($args, $namespacePhp, (int) ($expr->lineno ?? 0));
			}
			if ($this->isEnumNameCallName($nameExpr)) {
				return $this->renderEnumNameCallExpr($args, $namespacePhp, (int) ($expr->lineno ?? 0));
			}
			if ($this->isEnumFromValueCallName($nameExpr)) {
				return $this->renderEnumFromValueCallExpr($args, $namespacePhp, (int) ($expr->lineno ?? 0));
			}
			$functionDecl = $this->lookupFunctionDeclByCall($nameExpr, $namespacePhp);
			$name = $functionDecl !== null
				? $this->renderNameExpr($nameExpr, $namespacePhp)
				: $this->renderRuntimeAwareCallNameExpr($nameExpr, $namespacePhp);
			$renderedArgs = $functionDecl !== null ? $this->renderCallArgsForParams($functionDecl->params, $args, $namespacePhp) : $this->renderArgs($args, $namespacePhp);
			return $name . '(' . $renderedArgs . ')';
		}
		if ($kind === AstKind::METHOD_CALL) {
			$baseExpr = $expr->children['expr'] ?? null;
			$base = $this->renderExpr($baseExpr, $namespacePhp);
			$method = (string) ($expr->children['method'] ?? 'call');
			$args = $expr->children['args']->children ?? [];
			$baseType = $this->inferExprType($baseExpr);
			if (str_starts_with($baseType, 'result<') && $method === 'error' && count($args) === 0) {
				return $base . '.error()';
			}
			$methodDecl = is_object($baseExpr) && ($baseExpr->kind ?? null) === AstKind::VAR && ($baseExpr->children['name'] ?? null) === 'this'
				? $this->lookupMethodDeclByCurrentClass($method, $namespacePhp)
				: null;
			$renderedArgs = $methodDecl !== null ? $this->renderCallArgsForParams($methodDecl->params, $args, $namespacePhp) : $this->renderArgs($args, $namespacePhp);
			return $base . '->' . $this->cppIdentifier($method) . '(' . $renderedArgs . ')';
		}
		if ($kind === AstKind::ASSIGN) {
			return $this->renderAssignmentExpr($expr->children['var'] ?? null, $expr->children['expr'] ?? null, $namespacePhp);
		}
		if ($kind === AstKind::ASSIGN_OP) {
			return $this->renderCompoundAssignmentExpr(
				$expr->children['var'] ?? null,
				$expr->children['expr'] ?? null,
				(int) ($expr->flags ?? 0),
				$namespacePhp
			);
		}
		if ($kind === AstKind::UNARY_OP) {
			$inner = $this->renderExpr($expr->children['expr'] ?? null, $namespacePhp);
			$flags = (int) ($expr->flags ?? 0);
			return match ($flags) {
				AstKind::UNARY_BOOL_NOT => '(!' . $inner . ')',
				AstKind::UNARY_BITWISE_NOT => '(~' . $inner . ')',
				AstKind::UNARY_PLUS => '(+' . $inner . ')',
				AstKind::UNARY_MINUS => '(-' . $inner . ')',
				default => '/* unsupported-unary-op-' . $flags . ' */',
			};
		}
		if ($kind === AstKind::PRE_INC) {
			$target = $this->renderAssignmentTarget($expr->children['var'] ?? null, $namespacePhp);
			return '(++' . $target . ')';
		}
		if ($kind === AstKind::PRE_DEC) {
			$target = $this->renderAssignmentTarget($expr->children['var'] ?? null, $namespacePhp);
			return '(--' . $target . ')';
		}
		if ($kind === AstKind::POST_INC) {
			$target = $this->renderAssignmentTarget($expr->children['var'] ?? null, $namespacePhp);
			return '(' . $target . '++)';
		}
		if ($kind === AstKind::POST_DEC) {
			$target = $this->renderAssignmentTarget($expr->children['var'] ?? null, $namespacePhp);
			return '(' . $target . '--)';
		}
		if ($kind === AstKind::CONDITIONAL) {
			$condNode = $expr->children['cond'] ?? null;
			$trueNode = $expr->children['true'] ?? null;
			$falseNode = $expr->children['false'] ?? null;
			return $this->renderConditionalExpr($condNode, $trueNode, $falseNode, $namespacePhp);
		}
		if ($kind === AstKind::MATCH) {
			return $this->renderMatchExpr($expr, $namespacePhp);
		}
		if ($kind === AstKind::THROW) {
			$this->fail('throw used as an expression is not supported yet at line ' . (int) ($expr->lineno ?? 0) . '.');
		}

		$this->errors[] = $this->unsupportedExprKindMessage($expr, $kind);
		return '/* unsupported-expr-kind-' . $kind . ' */';
	}

	/** @return list<mixed> */
	private function extractVariadicPayload(mixed $node): array
	{
		$children = $node->children ?? [];

		if (array_key_exists('expr', $children)) {
			$expr = $children['expr'];
			if (is_object($expr) && isset($expr->children) && is_array($expr->children)) {
				return array_values($expr->children);
			}
			return [$expr];
		}

		if (array_key_exists('var', $children)) {
			$var = $children['var'];
			if (is_object($var) && isset($var->children) && is_array($var->children)) {
				return array_values($var->children);
			}
			return [$var];
		}

		if (is_array($children)) {
			return array_values($children);
		}

		return [];
	}

	/**

	 * Renders `AST_ENCAPS_LIST` interpolation by concatenating stringified fragments in source order.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderInterpolatedString(mixed $expr, ?string $namespacePhp): string
	{
		$parts = [];
		foreach (($expr->children ?? []) as $child) {
			// Interpolation fragments must reuse the ordinary expression renderer for any
			// non-literal AST node found inside AST_ENCAPS_LIST. The interpolation layer only
			// adds string normalization around the rendered expression subtree.
			$parts[] = $this->renderStringOperand($child, $namespacePhp);
		}

		if ($parts === []) {
			return 'string_t("")';
		}

		return '(' . implode(' + ', $parts) . ')';
	}

	/**

	 * Renders PHP string concatenation through explicit string conversion helpers.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderStringConcat(mixed $leftNode, mixed $rightNode, ?string $namespacePhp): string
	{
		return '(' . $this->renderStringOperand($leftNode, $namespacePhp) . ' + ' . $this->renderStringOperand($rightNode, $namespacePhp) . ')';
	}

	/**

	 * Renders one operand that must participate in string concatenation or interpolation.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderStringOperand(mixed $expr, ?string $namespacePhp): string
	{
		if (is_string($expr)) {
			return 'string_t(' . json_encode($expr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ')';
		}

		if (is_int($expr) || is_float($expr)) {
			return $this->renderGeneratedCast('string_t', $this->renderExpr($expr, $namespacePhp));
		}

		if (!is_object($expr)) {
			return 'string_t("")';
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::CONST) {
			$name = strtolower((string) ($expr->children['name']->children['name'] ?? ''));
			if ($name === 'null' || $name === 'true' || $name === 'false') {
				return $this->renderGeneratedCast('string_t', $this->renderExpr($expr, $namespacePhp));
			}
		}

		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($kind === AstKind::ENCAPS_LIST) {
			return $rendered;
		}

		return match ($kind) {
			AstKind::VAR,
			AstKind::DIM,
			AstKind::PROP,
			AstKind::METHOD_CALL,
			AstKind::CALL,
			AstKind::STATIC_CALL,
			AstKind::AST_ISSET,
			AstKind::AST_EMPTY,
			AstKind::CAST,
			AstKind::BINARY_OP,
			AstKind::ASSIGN,
			AstKind::CLASS_CONST,
				AstKind::STATIC_PROP => $this->renderGeneratedCast('string_t', $rendered),
				default => $this->renderGeneratedCast('string_t', $rendered),
			};
		}

	/**

	 * Renders a PHP variable or symbol-name expression into the generated C++ identifier form.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderNameExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr)) {
			return 'call';
		}
		if (($expr->kind ?? null) === AstKind::NAME) {
			$name = (string) ($expr->children['name'] ?? 'call');
			$flags = (int) ($expr->flags ?? 0);
			return $this->renderSymbolPath($name, $flags, true);
		}
		return $this->renderExpr($expr, $namespacePhp);
	}

	private function isTakeCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}

		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === 'take';
	}

	private function isLayoutProbeCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}
		return in_array(strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')), [
			'layout_sizeof',
			'layout_alignof',
			'layout_offsetof',
			'layout_field_sizeof',
		], true);
	}

	private function renderLayoutProbeCallExpr(mixed $nameExpr, array $args, ?string $namespacePhp, int $line): string
	{
		$name = strtolower(ltrim((string) ($nameExpr->children['name'] ?? ''), '\\'));
		$typeName = $this->renderLayoutProbeTypeArg($args[0] ?? null, $namespacePhp, $line);
		if ($typeName === null) {
			return 'static_cast<int_t<> >(0)';
		}
		if ($name === 'layout_sizeof') {
			if (count($args) !== 1) {
				$this->errors[] = 'layout_sizeof(TypeName) expects exactly one type argument at line ' . $line . '.';
			}
			return 'static_cast<int_t<> >(sizeof(' . $typeName . '))';
		}
		if ($name === 'layout_alignof') {
			if (count($args) !== 1) {
				$this->errors[] = 'layout_alignof(TypeName) expects exactly one type argument at line ' . $line . '.';
			}
			return 'static_cast<int_t<> >(alignof(' . $typeName . '))';
		}
		$fieldName = $this->renderLayoutProbeFieldArg($args[1] ?? null, $line);
		if ($fieldName === null) {
			return 'static_cast<int_t<> >(0)';
		}
		if ($name === 'layout_offsetof') {
			if (count($args) !== 2) {
				$this->errors[] = 'layout_offsetof(TypeName, field_name) expects exactly two arguments at line ' . $line . '.';
			}
			return 'static_cast<int_t<> >(offsetof(' . $typeName . ', ' . $fieldName . '))';
		}
		if (count($args) !== 2) {
			$this->errors[] = 'layout_field_sizeof(TypeName, field_name) expects exactly two arguments at line ' . $line . '.';
		}
		return 'static_cast<int_t<> >(sizeof(std::declval<' . $typeName . '>().' . $fieldName . '))';
	}

	private function renderLayoutProbeTypeArg(mixed $arg, ?string $namespacePhp, int $line): ?string
	{
		if (is_object($arg) && ($arg->kind ?? null) === AstKind::CONST) {
			$nameNode = $arg->children['name'] ?? null;
			$name = is_object($nameNode) ? (string) ($nameNode->children['name'] ?? '') : (string) $nameNode;
			if ($name !== '') {
				return $this->typeMapper->mapClassName($this->qualifyDeclaredPhpType($name, $namespacePhp) ?? $name);
			}
		}
		if (is_object($arg) && ($arg->kind ?? null) === AstKind::CLASS_CONST) {
			$classNode = $arg->children['class'] ?? null;
			if (strtolower((string) ($arg->children['const'] ?? '')) === 'class') {
				return $this->renderClassName($classNode, $namespacePhp);
			}
		}
		$this->errors[] = 'Layout probes expect a type name argument at line ' . $line . '. Use layout_sizeof(TypeName) or layout_sizeof(TypeName::class).';
		return null;
	}

	private function renderLayoutProbeFieldArg(mixed $arg, int $line): ?string
	{
		if (is_object($arg) && ($arg->kind ?? null) === AstKind::CONST) {
			$nameNode = $arg->children['name'] ?? null;
			$name = is_object($nameNode) ? (string) ($nameNode->children['name'] ?? '') : (string) $nameNode;
			if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) === 1) {
				return $this->cppIdentifier($name);
			}
		}
		if (is_string($arg) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $arg) === 1) {
			return $this->cppIdentifier($arg);
		}
		$this->errors[] = 'Layout field probes expect a bare field name at line ' . $line . '.';
		return null;
	}

	private function isAsyncWaitCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}

		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === 'async_wait';
	}

	private function isAsyncSleepCall(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::CALL)) {
			return false;
		}

		$nameExpr = $expr->children['expr'] ?? null;
		if (!is_object($nameExpr) || (($nameExpr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}

		return strtolower(ltrim((string) ($nameExpr->children['name'] ?? ''), '\\')) === 'async_sleep_ms';
	}

	private function isDbgCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}

		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === 'dbg';
	}

	private function isDbgIfCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}

		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === 'dbg_if';
	}

	private function isEnumValueCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}

		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === 'enum_value';
	}

	private function isEnumNameCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}

		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === 'enum_name';
	}

	private function isEnumFromValueCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}

		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === 'enum_from_value';
	}

	private function isScppDebugDumpCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}
		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === '__scpp_debug_dump';
	}

	private function isScppDebugExitCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}
		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === '__scpp_debug_exit';
	}

	private function isScppDebugBreakCallName(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return false;
		}
		return strtolower(ltrim((string) ($expr->children['name'] ?? ''), '\\')) === '__scpp_debug_break';
	}

	/** @param list<mixed> $args */
	private function renderDbgCallExpr(array $args, ?string $namespacePhp, int $line): string
	{
		if (count($args) < 1) {
			$this->fail('dbg() expects at least one value or label/value pair at line ' . $line . '.');
		}
		if (count($args) > 3) {
			$this->fail('dbg() expects value, label/value, or label/value/flags at line ' . $line . '.');
		}

		$renderedArgs = [];
		foreach ($args as $arg) {
			$renderedArgs[] = $this->renderExpr($arg, $namespacePhp);
		}

		if (count($args) === 2 && $this->isDbgFlagsExpr($args[1])) {
			return 'php::dbg_at('
				. $this->cppStringLiteral($this->currentSourcePath) . ', '
				. (string) $line . ', '
				. 'string_t(""), '
				. $renderedArgs[0] . ', '
				. $renderedArgs[1]
				. ')';
		}

		return 'php::dbg_at('
			. $this->cppStringLiteral($this->currentSourcePath) . ', '
			. (string) $line
			. ($renderedArgs === [] ? '' : ', ' . implode(', ', $renderedArgs))
			. ')';
	}

	private function isDbgFlagsExpr(mixed $expr): bool
	{
		if (!is_object($expr)) {
			return false;
		}
		if (($expr->kind ?? null) === AstKind::CONST) {
			$name = $expr->children['name']->children['name'] ?? null;
			return is_string($name) && str_starts_with($name, 'DBG_');
		}
		if (($expr->kind ?? null) === AstKind::BINARY_OP && ((int) ($expr->flags ?? 0)) === AstKind::BITWISE_OR) {
			return $this->isDbgFlagsExpr($expr->children['left'] ?? null)
				&& $this->isDbgFlagsExpr($expr->children['right'] ?? null);
		}
		return false;
	}

	/** @param list<mixed> $args */
	private function renderDbgIfCallExpr(array $args, ?string $namespacePhp, int $line): string
	{
		if (count($args) < 2) {
			$this->fail('dbg_if() expects a gate name plus dbg() arguments at line ' . $line . '.');
		}

		$renderedArgs = [];
		foreach ($args as $arg) {
			$renderedArgs[] = $this->renderExpr($arg, $namespacePhp);
		}

		$key = array_shift($renderedArgs);
		return 'php::dbg_if_at('
			. $key . ', '
			. $this->cppStringLiteral($this->currentSourcePath) . ', '
			. (string) $line
			. ($renderedArgs === [] ? '' : ', ' . implode(', ', $renderedArgs))
			. ')';
	}

	/** @param list<mixed> $args */
	private function renderAsyncWaitCallExpr(array $args, ?string $namespacePhp, int $line): string
	{
		if (count($args) !== 1) {
			$this->fail('async_wait() expects exactly one async task value at line ' . $line . '.');
		}

		return 'scpp::async_core::sync_wait(' . $this->renderExpr($args[0] ?? null, $namespacePhp) . ')';
	}

	private function renderAsyncSleepCall(mixed $expr, ?string $namespacePhp): string
	{
		$args = is_object($expr) ? ($expr->children['args']->children ?? []) : [];
		$line = is_object($expr) ? (int) ($expr->lineno ?? 0) : 0;
		if (count($args) !== 1) {
			$this->fail('async_sleep_ms() expects exactly one duration argument at line ' . $line . '.');
		}

		return 'scpp::async_core::sleep_ms(' . $this->renderExpr($args[0] ?? null, $namespacePhp) . ')';
	}

	/** @param list<mixed> $args */
	private function renderScppDebugDumpCallExpr(array $args, ?string $namespacePhp, int $line): string
	{
		if (count($args) !== 3) {
			$this->fail('__scpp_debug_dump() expects phase, label, and one expression at line ' . $line . '.');
		}
		return 'php::__scpp_debug_dump_at('
			. $this->cppStringLiteral($this->currentSourcePath) . ', '
			. (string) $line . ', '
			. $this->renderExpr($args[0], $namespacePhp) . ', '
			. $this->renderExpr($args[1], $namespacePhp) . ', '
			. $this->renderExpr($args[2], $namespacePhp)
			. ')';
	}

	/** @param list<mixed> $args */
	private function renderScppDebugExitCallExpr(array $args, int $line): string
	{
		if ($args !== []) {
			$this->fail('__scpp_debug_exit() expects no arguments at line ' . $line . '.');
		}
		return 'php::__scpp_debug_exit_at('
			. $this->cppStringLiteral($this->currentSourcePath) . ', '
			. (string) $line
			. ')';
	}

	/** @param list<mixed> $args */
	private function renderScppDebugBreakCallExpr(array $args, int $line): string
	{
		if ($args !== []) {
			$this->fail('__scpp_debug_break() expects no arguments at line ' . $line . '.');
		}
		return 'php::__scpp_debug_break_at('
			. $this->cppStringLiteral($this->currentSourcePath) . ', '
			. (string) $line
			. ')';
	}

	/** @param list<mixed> $args */
	private function renderEnumValueCallExpr(array $args, ?string $namespacePhp, int $line): string
	{
		if (count($args) !== 1) {
			$this->fail('enum_value() expects exactly one enum argument at line ' . $line . '.');
		}
		$arg = $args[0] ?? null;
		$enumType = $this->inferExprTypeWithNamespace($arg, $namespacePhp);
		$class = $this->lookupEnumDeclByTypeName($enumType);
		if (!$class instanceof ClassDecl) {
			$this->fail('enum_value() expects an enum argument at line ' . $line . '.');
		}
		$runtimeType = $this->enumBackingRuntimeType($class);
		$nativeType = $this->enumBackingNativeType($class);
		return $runtimeType . '(static_cast<' . $nativeType . '>(' . $this->renderExpr($arg, $namespacePhp) . '))';
	}

	/** @param list<mixed> $args */
	private function renderEnumNameCallExpr(array $args, ?string $namespacePhp, int $line): string
	{
		if (count($args) !== 1) {
			$this->fail('enum_name() expects exactly one enum argument at line ' . $line . '.');
		}
		$arg = $args[0] ?? null;
		$enumType = $this->inferExprTypeWithNamespace($arg, $namespacePhp);
		$class = $this->lookupEnumDeclByTypeName($enumType);
		if (!$class instanceof ClassDecl) {
			$this->fail('enum_name() expects an enum argument at line ' . $line . '.');
		}
		$cases = [];
		foreach ($class->enumCases as $case) {
			$cases[] = 'case ' . $class->name . '::' . $this->cppIdentifier($case->name) . ': return string_t(' . $this->cppStringLiteral($case->name) . ');';
		}
		return '([&]() -> string_t { switch (' . $this->renderExpr($arg, $namespacePhp) . ') { ' . implode(' ', $cases) . ' default: throw std::runtime_error("Invalid value for enum ' . $class->name . '"); } }())';
	}

	/** @param list<mixed> $args */
	private function renderEnumFromValueCallExpr(array $args, ?string $namespacePhp, int $line): string
	{
		if (count($args) !== 2) {
			$this->fail('enum_from_value() expects enum_from_value(Enum::class, value) at line ' . $line . '.');
		}
		$className = $this->extractEnumClassNameMarker($args[0] ?? null, $namespacePhp);
		$class = $className !== null ? $this->lookupEnumDeclByTypeName($className) : null;
		if (!$class instanceof ClassDecl) {
			$this->fail('enum_from_value() first argument must be an enum class marker such as token_kind::class at line ' . $line . '.');
		}
		$runtimeType = $this->enumBackingRuntimeType($class);
		$nativeType = $this->enumBackingNativeType($class);
		$valueExpr = $this->renderExpr($args[1] ?? null, $namespacePhp);
		$cases = [];
		foreach ($class->enumCases as $index => $case) {
			$value = $class->enumBackingType === null ? $index : $this->enumCaseIntValue($case);
			$cases[] = 'case static_cast<' . $nativeType . '>(' . (string) $value . '): return ' . $class->name . '::' . $this->cppIdentifier($case->name) . ';';
		}
		return '([&]() -> ' . $class->name . ' { const auto __scpp_enum_value = cast<' . $runtimeType . '>(' . $valueExpr . ').native_value(); switch (__scpp_enum_value) { ' . implode(' ', $cases) . ' default: throw std::runtime_error("Invalid value for enum ' . $class->name . '"); } }())';
	}

	private function extractEnumClassNameMarker(mixed $expr, ?string $namespacePhp): ?string
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::CLASS_NAME)) {
			return null;
		}
		$classNode = $expr->children['class'] ?? null;
		if (!is_object($classNode) || (($classNode->kind ?? null) !== AstKind::NAME)) {
			return null;
		}
		$name = (string) ($classNode->children['name'] ?? '');
		return $this->resolveDeclaredClassLikeType($name, $namespacePhp);
	}

	/** @param list<mixed> $args */
	private function renderTakeCallExpr(array $args, ?string $namespacePhp, int $line): string
	{
		if (count($args) !== 2 && count($args) !== 3) {
			$this->fail('take() expects exactly 2 or 3 arguments at line ' . $line . '.');
		}

		foreach ($args as $index => $arg) {
			if ($index === array_key_last($args)) {
				continue;
			}
			if ($this->extractSimpleVarName($arg) === null) {
				$this->fail('take() output arguments must be simple local variables at line ' . $line . '.');
			}
		}

		$this->validateTakeCall($args, $namespacePhp, $line);
		$renderedArgs = [];
		foreach ($args as $arg) {
			$renderedArgs[] = $this->renderExpr($arg, $namespacePhp);
		}

		return $this->qualifyKnownPhpRuntimeSymbol('take') . '(' . implode(', ', $renderedArgs) . ')';
	}

	/** @param list<mixed> $args */
	private function validateTakeCall(array $args, ?string $namespacePhp, int $line): void
	{
		$sourceNode = $args[array_key_last($args)] ?? null;
		$sourceType = $this->inferExprTypeWithNamespace($sourceNode, $namespacePhp);
		if (preg_match('/^nullable<(.+)>$/', $sourceType, $matches) === 1) {
			if (count($args) !== 2) {
				$this->fail('take(nullable<T>) requires exactly one output variable and one source expression at line ' . $line . '.');
			}
			$this->assertTakeOutputTypeMatches($args[0], $matches[1], $line, 'take(nullable<T>)');
			return;
		}

		if (preg_match('/^result_or_false<(.+)>$/', $sourceType, $matches) === 1) {
			if (count($args) !== 2) {
				$this->fail('take(result_or_false<T>) requires exactly one output variable and one source expression at line ' . $line . '.');
			}
			$this->assertTakeOutputTypeMatches($args[0], $matches[1], $line, 'take(result_or_false<T>)');
			return;
		}

		if (preg_match('/^result<(.+)>$/', $sourceType, $matches) === 1) {
			if (count($args) !== 3) {
				$this->fail('take(result<T>) requires two output variables plus the source expression at line ' . $line . '.');
			}
			$this->assertTakeOutputTypeMatches($args[0], $matches[1], $line, 'take(result<T>)');
			$this->assertTakeOutputTypeMatches($args[1], 'error', $line, 'take(result<T>)');
			return;
		}

		if (preg_match('/^result_or_bool<(.+)>$/', $sourceType, $matches) === 1) {
			if (count($args) !== 3) {
				$this->fail('take(result_or_bool<T>) requires two output variables plus the source expression at line ' . $line . '.');
			}
			$this->assertTakeOutputTypeMatches($args[0], $matches[1], $line, 'take(result_or_bool<T>)');
			$this->assertTakeOutputTypeMatches($args[1], 'bool_t', $line, 'take(result_or_bool<T>)');
			return;
		}

		if ($sourceType !== 'auto') {
			$this->fail('take() requires nullable<T>, result<T>, result_or_false<T>, or result_or_bool<T> as the source expression at line ' . $line . '; got ' . $sourceType . '.');
		}
	}

	private function assertTakeOutputTypeMatches(mixed $expr, string $expectedType, int $line, string $context): void
	{
		$actualType = $this->inferExprType($expr);
		if ($actualType === 'auto') {
			return;
		}

		$normalizedExpectedType = $this->normalizeTakeExpectedOutputType($expectedType);
		if ($actualType !== $normalizedExpectedType) {
			$this->fail($context . ' expects output type ' . $normalizedExpectedType . ' but got ' . $actualType . ' at line ' . $line . '.');
		}
	}

	private function normalizeTakeExpectedOutputType(string $expectedType): string
	{
		$normalized = trim($expectedType);
		if ($normalized === '' || $normalized === 'auto') {
			return $normalized;
		}

		if (preg_match('/^(?:int_t|float_t|bool_t|string_t|mixed_t|error_t|hash_t|::scpp::hash_t)(?:<.*>)?$/', $normalized) === 1) {
			return $normalized;
		}

		if (preg_match('/^(?:nullable|result|result_or_false|result_or_bool|shared_p|unique_p|weak_p|value_p|vector_t)<.+>$/', $normalized) === 1) {
			return $normalized;
		}

		return $this->typeMapper->mapDeclaredType($normalized);
	}

	private function renderRuntimeAwareCallNameExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NAME)) {
			return $this->renderNameExpr($expr, $namespacePhp);
		}

		$name = (string) ($expr->children['name'] ?? 'call');
		$trimmed = ltrim($name, '\\');
		if ($trimmed !== '' && !str_contains($trimmed, '\\') && $this->isKnownPhpRuntimeRelativeSymbol($trimmed)) {
			return $this->qualifyKnownPhpRuntimeSymbol($trimmed);
		}

		return $this->renderNameExpr($expr, $namespacePhp);
	}

	/** @return array<string, string> */
	private function loadPhpRuntimeRelativeSymbols(): array
	{
		$specsRoot = dirname(__DIR__, 2) . '/specs';
		$path = $specsRoot . '/php_runtime_symbols_' . $this->phpProfile . '.json';

		$out = [];
		if (!is_file($path)) {
			throw new \RuntimeException('Missing mandatory PHP runtime symbols registry: ' . $path);
		}

		$content = file_get_contents($path);
		if ($content === false) {
			throw new \RuntimeException('Failed to read mandatory PHP runtime symbols registry: ' . $path);
		}

		try {
			$data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
		} catch (\Throwable $e) {
			throw new \RuntimeException('Invalid JSON in mandatory PHP runtime symbols registry: ' . $path . ' (' . $e->getMessage() . ')', 0, $e);
		}

		if (!is_array($data)) {
			throw new \RuntimeException('Mandatory PHP runtime symbols registry must decode to an object: ' . $path);
		}

		$targets = $data['php_runtime_symbol_targets'] ?? null;
		if (!is_array($targets)) {
			throw new \RuntimeException('Mandatory PHP runtime symbols registry must contain object key php_runtime_symbol_targets: ' . $path);
		}

		foreach ($targets as $symbol => $target) {
			if (!is_string($symbol) || $symbol === '' || !is_string($target) || $target === '') {
				throw new \RuntimeException('Invalid symbol target entry in mandatory PHP runtime symbols registry: ' . $path);
			}
			$out[strtolower($symbol)] = $target;
		}

		return $out;
	}

	private function isKnownPhpRuntimeRelativeSymbol(string $symbol): bool
	{
		return isset($this->phpRuntimeRelativeSymbols[strtolower($symbol)]);
	}

	private function qualifyKnownPhpRuntimeSymbol(string $symbol): string
	{
		return $this->phpRuntimeRelativeSymbols[strtolower($symbol)] ?? $symbol;
	}

	/**

	 * Renders argument lists for exporter-lowered variadic-style payload nodes.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderVariadicArgs(mixed $expr, ?string $namespacePhp): string
	{
		$out = [];
		if (is_object($expr) && isset($expr->children) && is_array($expr->children)) {
			$children = $expr->children;
			$isList = array_is_list($children);
			if ($isList) {
				foreach ($children as $child) {
					$out[] = $this->renderExpr($child, $namespacePhp);
				}
			}
		}
		if ($out === []) {
			$out[] = $this->renderExpr($expr, $namespacePhp);
		}
		return implode(', ', $out);
	}

	/**

	 * Renders a normal call argument list in source order.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderArgs(array $args, ?string $namespacePhp): string
	{
		$out = [];
		foreach ($args as $arg) {
			$out[] = $this->renderCallArgExpr($arg, $namespacePhp);
		}
		return implode(', ', $out);
	}

	/**

	 * Renders a class-name expression with namespace resolution applied.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderClassName(mixed $node, ?string $namespacePhp): string
	{
		if (!is_object($node)) {
			return 'Unknown';
		}
		$name = (string) ($node->children['name'] ?? 'Unknown');
		$lowerName = strtolower(ltrim($name, '\\'));
		if ($lowerName === 'self') {
			return $this->currentClassName ?? '/* unsupported-self */';
		}
		if ($lowerName === 'parent') {
			if ($this->currentParentClass === null) {
				$this->errors[] = 'parent:: is not available without a parent class.';
				return '/* unsupported-parent */';
			}
			return $this->typeMapper->mapClassName($this->currentParentClass);
		}
		if ($lowerName === 'static') {
			$this->errors[] = 'static:: is not supported in the current pass.';
			return '/* unsupported-static */';
		}
		$flags = (int) ($node->flags ?? 0);
		return $this->renderSymbolPath($name, $flags, false);
	}

	private function renderStaticPropertyAccess(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::STATIC_PROP)) {
			return '/* unsupported-static-prop */';
		}

		$classNode = $expr->children['class'] ?? null;
		$prop = (string) ($expr->children['prop'] ?? 'prop');
		if (!is_object($classNode)) {
			return '/* unsupported-static-prop */';
		}

		if (($classNode->kind ?? null) === AstKind::VAR) {
			return 'class_t<decltype(' . $this->renderExpr($classNode, $namespacePhp) . ')>::' . $prop;
		}

		if (($classNode->kind ?? null) !== AstKind::NAME) {
			return '/* unsupported-static-prop */';
		}

		$name = (string) ($classNode->children['name'] ?? '');
		if (strtolower(ltrim($name, '\\')) === 'static') {
			return $this->renderLateStaticPropertyAccess($expr, $namespacePhp);
		}

		return $this->renderClassName($classNode, $namespacePhp) . '::' . $this->cppIdentifier($prop);
	}

	private function renderStaticScopeWrapper(string $ownerClassCpp, string $currentClassCpp, string $callExpr): string
	{
		return '::scpp::php::_static<' . $ownerClassCpp . ', ' . $currentClassCpp . '>([&]() -> decltype(auto) { return ' . $callExpr . '; })';
	}

	private function currentClassDeclForLateStatic(?string $namespacePhp): ?ClassDecl
	{
		if ($this->currentClassName === null) {
			return null;
		}
		$key = $this->qualifyClassNameForLookup($this->currentClassName, $namespacePhp);
		$classDecl = $this->classDecls[$key] ?? $this->classDecls[$this->currentClassName] ?? null;
		return $classDecl instanceof ClassDecl ? $classDecl : null;
	}

	private function currentLateStaticNeedsContext(?string $namespacePhp): bool
	{
		$classDecl = $this->currentClassDeclForLateStatic($namespacePhp);
		if (!$classDecl instanceof ClassDecl) {
			return true;
		}
		return count($this->collectLateStaticHierarchyTargetKeys($classDecl, $namespacePhp)) > 1;
	}

	/** @return list<string> */
	private function currentLateStaticTargetClasses(?string $namespacePhp): array
	{
		$classDecl = $this->currentClassDeclForLateStatic($namespacePhp);
		if (!$classDecl instanceof ClassDecl) {
			return [$this->currentClassName ?? '/* unsupported-static */'];
		}
		return array_map(fn (string $key): string => $this->typeMapper->mapClassName($key), $this->collectLateStaticHierarchyTargetKeys($classDecl, $namespacePhp));
	}

	private function renderLateStaticPropertyAccess(mixed $expr, ?string $namespacePhp): string
	{
		$prop = $this->cppIdentifier((string) ($expr->children['prop'] ?? 'prop'));
		$currentClass = $this->currentClassName ?? '/* unsupported-static */';
		if (!$this->currentLateStaticNeedsContext($namespacePhp)) {
			return $currentClass . '::' . $prop;
		}
		$lines = [];
		$tokenExpr = '::scpp::php::current_static_token_for<' . $currentClass . '>()';
		foreach ($this->currentLateStaticTargetClasses($namespacePhp) as $index => $targetClass) {
			$prefix = $index === 0 ? 'if' : 'else if';
			$lines[] = $prefix . ' (' . $tokenExpr . ' == ' . $targetClass . '::__scpp_static_token()) { return (' . $targetClass . '::' . $prop . '); }';
		}
		$lines[] = 'return (' . $currentClass . '::' . $prop . ');';
		$body = implode(' ', $lines);
		return $this->renderStaticScopeWrapper($currentClass, $currentClass, '([&]() -> decltype(auto) { ' . $body . ' }())');
	}

	private function renderLateStaticClassConstAccess(mixed $expr, ?string $namespacePhp): string
	{
		$const = $this->cppIdentifier((string) ($expr->children['const'] ?? 'CONST'));
		$currentClass = $this->currentClassName ?? '/* unsupported-static */';
		if (!$this->currentLateStaticNeedsContext($namespacePhp)) {
			return $currentClass . '::' . $const;
		}
		$lines = [];
		$tokenExpr = '::scpp::php::current_static_token_for<' . $currentClass . '>()';
		foreach ($this->currentLateStaticTargetClasses($namespacePhp) as $index => $targetClass) {
			$prefix = $index === 0 ? 'if' : 'else if';
			$lines[] = $prefix . ' (' . $tokenExpr . ' == ' . $targetClass . '::__scpp_static_token()) { return ' . $targetClass . '::' . $const . '; }';
		}
		$lines[] = 'return ' . $currentClass . '::' . $const . ';';
		$body = implode(' ', $lines);
		return $this->renderStaticScopeWrapper($currentClass, $currentClass, '([&]() -> decltype(auto) { ' . $body . ' }())');
	}

	private function renderLateStaticNewExpr(mixed $expr, ?string $namespacePhp): string
	{
		$args = $expr->children['args']->children ?? [];
		$renderedArgs = $this->renderArgs($args, $namespacePhp);
		$currentClass = $this->currentClassName ?? '/* unsupported-static */';
		if (!$this->currentLateStaticNeedsContext($namespacePhp)) {
			return 'create<' . $currentClass . '>(' . $renderedArgs . ')';
		}
		$returnType = 'shared_p<' . $currentClass . '>';
		$lines = [];
		$tokenExpr = '::scpp::php::current_static_token_for<' . $currentClass . '>()';
		foreach ($this->currentLateStaticTargetClasses($namespacePhp) as $index => $targetClass) {
			$prefix = $index === 0 ? 'if' : 'else if';
			$createExpr = 'create<' . $targetClass . '>(' . $renderedArgs . ')';
			$lines[] = $prefix . ' (' . $tokenExpr . ' == ' . $targetClass . '::__scpp_static_token()) { return ' . ($targetClass === $currentClass ? $createExpr : ($returnType . '(' . $createExpr . ')')) . '; }';
		}
		$lines[] = 'return create<' . $currentClass . '>(' . $renderedArgs . ');';
		$body = implode(' ', $lines);
		return $this->renderStaticScopeWrapper($currentClass, $currentClass, '([&]() -> ' . $returnType . ' { ' . $body . ' }())');
	}

	private function renderLateStaticMethodCall(mixed $expr, ?string $namespacePhp): string
	{
		$method = (string) ($expr->children['method'] ?? '');
		$args = $expr->children['args']->children ?? [];
		$methodDecl = $this->currentLateStaticDispatchMethods[$method] ?? $this->lookupMethodDeclByCurrentClass($method, $namespacePhp);
		$renderedArgs = $methodDecl !== null ? $this->renderCallArgsForParams($methodDecl->params, $args, $namespacePhp) : $this->renderArgs($args, $namespacePhp);
		$currentClass = $this->currentClassName ?? '/* unsupported-static */';
		if (!$this->currentLateStaticNeedsContext($namespacePhp)) {
			return $currentClass . '::' . $this->cppIdentifier($method) . '(' . $renderedArgs . ')';
		}
		$callExpr = $currentClass . '::' . $this->renderLateStaticDispatchHelperName($method) . '(' . $renderedArgs . ')';
		return $this->renderStaticScopeWrapper($currentClass, $currentClass, $callExpr);
	}

	/**

	 * Renders a constant reference using the constant-resolution rules recorded in the name registry.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderConstantName(string $name, int $flags, ?string $namespacePhp): string
	{
		$trimmed = ltrim($name, '\\');
		if ($trimmed === '') {
			return '/* unsupported-const */';
		}

		if (isset($this->predefinedConstants[$trimmed])) {
			return str_replace('\\', '::', $trimmed);
		}

		return $this->renderSymbolPath($name, $flags, true);
	}



	private function renderSymbolPath(string $name, int $flags, bool $rootBareIdentifiers): string
	{
		$trimmed = ltrim($name, '\\');
		if ($trimmed === '') {
			return 'Unknown';
		}

		$cpp = str_replace('\\', '::', $trimmed);
		if ($flags === 0 || str_starts_with($name, '\\')) {
			return $cpp;
		}

		if ($rootBareIdentifiers && !str_contains($trimmed, '\\')) {
			return $trimmed;
		}

		return $cpp;
	}



	/** @return array<string, bool> */
	private function buildStableReferenceRootsForCurrentScope(): array
	{
		$roots = [];
		foreach ($this->predefinedReferenceLocals as $name => $_enabled) {
			$roots[$name] = true;
		}
		foreach ($this->declaredLocalTypes as $name => $_type) {
			if (str_ends_with($_type, '&')) {
				$roots[$name] = true;
			}
		}
		if ($this->currentClassName !== null) {
			$roots['this'] = true;
		}
		return $roots;
	}

	private function renderReturnExpr(mixed $expr, ?string $namespacePhp): string
	{
		$expected = $this->currentReturnType;
		if ($expected !== null && str_ends_with($expected, '&')) {
			$stableRoots = $this->buildStableReferenceRootsForCurrentScope();
			if (!$this->isStableAliasableReferenceExpr($expr, $stableRoots, $namespacePhp) || !$this->isLvalueCapableExpr($expr, $namespacePhp)) {
				$this->errors[] = 'Reference return requires a stable aliasable expression rooted in a by-reference parameter, $this, or another reference derived from stable storage.';
				return '/* unsupported-ref-return */';
			}
			return $this->renderLvalueExpr($expr, $namespacePhp);
		}

		if ($expected === null) {
			return $this->renderExpr($expr, $namespacePhp);
		}

		if (is_object($expr) && (($expr->kind ?? null) === AstKind::ARRAY) && preg_match('/^vector_t<.+>$/', $expected) === 1) {
			return $this->renderTypedVectorArrayLiteral($expr, $namespacePhp, $expected);
		}
		if (is_object($expr) && (($expr->kind ?? null) === AstKind::ARRAY) && preg_match('/^fixed_array_t<.+>$/', $expected) === 1) {
			return $this->renderTypedFixedArrayLiteral($expr, $namespacePhp, $expected);
		}

		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($expected === 'bool_t' && $this->isBinaryComparisonExpr($expr)) {
			return 'bool_t(' . $rendered . ')';
		}
		$exprType = $this->inferExprType($expr);
		return $this->wrapExprForExpectedType($rendered, $exprType, $expected);
	}

	private function isBinaryComparisonExpr(mixed $expr): bool
	{
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::BINARY_OP)) {
			return false;
		}
		return in_array((int) ($expr->flags ?? 0), [
			AstKind::BINARY_IS_SMALLER,
			AstKind::BINARY_IS_SMALLER_OR_EQUAL,
			AstKind::BINARY_IS_GREATER,
			AstKind::BINARY_IS_NOT_EQUAL,
			AstKind::BINARY_IS_EQUAL,
			AstKind::BINARY_IS_IDENTICAL,
			AstKind::BINARY_IS_NOT_IDENTICAL,
		], true);
	}

	private function isLvalueCapableExpr(mixed $expr, ?string $namespacePhp = null): bool
	{
		if (!is_object($expr)) {
			return false;
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::CALL) {
			$nameExpr = $expr->children['expr'] ?? null;
			$functionDecl = $this->lookupFunctionDeclByCall($nameExpr, $namespacePhp);
			return $functionDecl !== null && $functionDecl->returnsByReference;
		}

		if ($kind === AstKind::STATIC_CALL) {
			$classNode = $expr->children['class'] ?? null;
			$methodName = (string) ($expr->children['method'] ?? '');
			$methodDecl = $this->lookupMethodDeclByStaticCall($classNode, $methodName, $namespacePhp);
			return $methodDecl !== null && $methodDecl->returnsByReference;
		}

		if ($kind === AstKind::METHOD_CALL) {
			$baseExpr = $expr->children['expr'] ?? null;
			$methodName = (string) ($expr->children['method'] ?? '');
			$methodDecl = is_object($baseExpr) && ($baseExpr->kind ?? null) === AstKind::VAR && ($baseExpr->children['name'] ?? null) === 'this'
				? $this->lookupMethodDeclByCurrentClass($methodName, $namespacePhp)
				: null;
			return $methodDecl !== null && $methodDecl->returnsByReference;
		}

		return match ($kind) {
			AstKind::VAR => true,
			AstKind::PROP => $this->extractSimpleVarName($expr->children['expr'] ?? null) === 'this',
			default => false,
		};
	}

	private function renderLvalueExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr)) {
			return $this->renderExpr($expr, $namespacePhp);
		}

		return match ($expr->kind ?? null) {
			AstKind::VAR => $this->renderVar($expr),
			default => $this->renderExpr($expr, $namespacePhp),
		};
	}

	private function renderReferenceBindingExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (is_object($expr)) {
			$kind = $expr->kind ?? null;
			if ($kind === AstKind::DIM || $kind === AstKind::PROP || $kind === AstKind::STATIC_PROP || $kind === AstKind::NULLSAFE_PROP) {
				$this->errors[] = 'Reference binding from dynamic or interior access is not supported in the current safe subset.';
				return '/* unsupported-ref-binding */';
			}
		}

		if ($this->isLvalueCapableExpr($expr, $namespacePhp)) {
			return $this->renderLvalueExpr($expr, $namespacePhp);
		}

		return $this->renderExpr($expr, $namespacePhp);
	}

	private function renderVar(mixed $expr): string
	{
		// Reference-capable variable rendering must stay trivial here: a simple PHP local
		// should lower to the raw C++ identifier so native reference binding and reference
		// returns can attach to the storage location instead of to a copied temporary.
		if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::VAR)) {
			return '/* unsupported-var */';
		}

		$name = (string) ($expr->children['name'] ?? 'var');
		if ($name === 'this') {
			return 'this';
		}
		if (!$this->isForeachReferenceSlotSuppressed($name)) {
			for ($i = count($this->foreachReferenceSlotStack) - 1; $i >= 0; --$i) {
				if (isset($this->foreachReferenceSlotStack[$i][$name])) {
					return $this->foreachReferenceSlotStack[$i][$name];
				}
			}
		}
		return $this->localCppName($name);
	}

	private function inferExprTypeWithNamespace(mixed $expr, ?string $namespacePhp): string
	{
		if (!is_object($expr)) {
			return $this->inferExprType($expr);
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::CALL) {
			$nameExpr = $expr->children['expr'] ?? null;
			if ($this->isTakeCallName($nameExpr)) {
				return 'bool_t';
			}
			if ($this->isLayoutProbeCallName($nameExpr)) {
				return 'int_t<>';
			}
			if ($this->isEnumValueCallName($nameExpr)) {
				$args = $expr->children['args']->children ?? [];
				$class = $this->lookupEnumDeclByTypeName($this->inferExprTypeWithNamespace($args[0] ?? null, $namespacePhp));
				return $class instanceof ClassDecl ? $this->enumBackingRuntimeType($class) : 'auto';
			}
			if ($this->isEnumNameCallName($nameExpr)) {
				return 'string_t';
			}
			if ($this->isEnumFromValueCallName($nameExpr)) {
				$args = $expr->children['args']->children ?? [];
				return $this->extractEnumClassNameMarker($args[0] ?? null, $namespacePhp) ?? 'auto';
			}
			$functionDecl = $this->lookupFunctionDeclByCall($nameExpr, $namespacePhp);
			if ($functionDecl !== null && $functionDecl->returnType !== null) {
				return $this->typeMapper->mapReturnType($functionDecl->returnType, $functionDecl->returnsByReference);
			}
		}

		if ($kind === AstKind::STATIC_CALL) {
			$classNode = $expr->children['class'] ?? null;
			$methodName = (string) ($expr->children['method'] ?? '');
			$methodDecl = $this->lookupMethodDeclByStaticCall($classNode, $methodName, $namespacePhp);
			if ($methodDecl !== null && $methodDecl->returnType !== null) {
				return $this->typeMapper->mapReturnType($methodDecl->returnType, $methodDecl->returnsByReference);
			}
		}

		if ($kind === AstKind::METHOD_CALL) {
			$baseExpr = $expr->children['expr'] ?? null;
			$methodName = (string) ($expr->children['method'] ?? '');
			$baseType = $this->inferExprType($baseExpr);
			$methodDecl = is_object($baseExpr) && ($baseExpr->kind ?? null) === AstKind::VAR && ($baseExpr->children['name'] ?? null) === 'this'
				? $this->lookupMethodDeclByCurrentClass($methodName, $namespacePhp)
				: $this->lookupMethodDeclByMappedBaseType($baseType, $methodName);
			if ($methodDecl !== null && $methodDecl->returnType !== null) {
				return $this->typeMapper->mapReturnType($methodDecl->returnType, $methodDecl->returnsByReference);
			}
		}

		return $this->inferExprType($expr);
	}

	private function inferExprType(mixed $expr): string
	{
		if (is_int($expr)) {
			return 'int_t<>';
		}
		if (is_float($expr)) {
			return 'float_t';
		}
		if (is_string($expr)) {
			return 'string_t';
		}
		if (!is_object($expr)) {
			return 'auto';
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::CONST) {
			$nameNode = $expr->children['name'] ?? null;
			$name = strtolower(ltrim(is_object($nameNode) ? (string) ($nameNode->children['name'] ?? '') : (string) $nameNode, '\\'));
			return match ($name) {
				'true', 'false' => 'bool_t',
				'null' => 'null_t',
				default => 'auto',
			};
		}
		if ($kind === AstKind::VAR) {
			$name = (string) ($expr->children['name'] ?? '');
			$declared = $this->declaredLocalTypes[$name] ?? null;
			if ($declared === null) {
				return 'auto';
			}
			if (str_contains($declared, 'int_t') || str_contains($declared, 'float_t') || str_contains($declared, 'bool_t') || str_contains($declared, 'string_t') || $declared === 'mixed_t' || $declared === 'dynamic_t<>' || str_starts_with($declared, 'nullable<') || str_starts_with($declared, 'result_or_false<') || str_starts_with($declared, 'result_or_bool<') || str_starts_with($declared, 'result<') || str_starts_with($declared, 'shared_p<') || str_starts_with($declared, 'unique_p<') || str_starts_with($declared, 'weak_p<') || str_starts_with($declared, 'value_p<') || str_starts_with($declared, 'vector_t<') || str_starts_with($declared, 'fixed_array_t<') || str_starts_with($declared, 'hash_t<') || $declared === 'hash_t' || $declared === '::scpp::hash_t' || $declared === 'hash_t<mixed_t>' || $declared === '::scpp::hash_t<mixed_t>') {
				return $declared;
			}
			return $this->typeMapper->mapDeclaredType($declared);
		}
		if ($kind === AstKind::CLASS_CONST) {
			$classNode = $expr->children['class'] ?? null;
			if (is_object($classNode) && ($classNode->kind ?? null) === AstKind::NAME) {
				$phpClass = (string) ($classNode->children['name'] ?? '');
				if ($phpClass === 'self' && $this->currentClassName !== null) {
					$phpClass = $this->currentClassName;
				}
				$classDecl = $this->classDecls[$phpClass] ?? $this->classDecls[basename(str_replace('\\', '/', $phpClass))] ?? null;
				if ($classDecl instanceof ClassDecl && $classDecl->isEnum) {
					return $this->typeMapper->mapDeclaredType($classDecl->name);
				}
				if ($this->typeMapper->declaredTypeKind($phpClass) === 'enum') {
					return $this->typeMapper->mapDeclaredType($phpClass);
				}
			}
			return 'auto';
		}
		if ($kind === AstKind::CALL) {
			$nameExpr = $expr->children['expr'] ?? null;
			if ($this->isTakeCallName($nameExpr)) {
				return 'bool_t';
			}
			return 'auto';
		}
		if ($kind === AstKind::STATIC_CALL) {
			return 'auto';
		}
		if ($kind === AstKind::NEW) {
			if ($this->isStdClassNewExpr($expr)) {
				return 'dynamic_t<>';
			}

			$constructedClass = $this->extractDirectConstructedClassTypeName($expr);
			if ($constructedClass !== null) {
				$mappedClass = $this->typeMapper->mapClassName($constructedClass);
				$classDecl = $this->classDecls[$constructedClass] ?? $this->classDecls[basename(str_replace('\\', '/', $constructedClass))] ?? null;
				return $classDecl instanceof ClassDecl && $classDecl->isEnum ? $mappedClass : 'shared_p<' . $mappedClass . '>';
			}
			return 'auto';
		}
		if ($kind === AstKind::ARRAY) {
			return 'mixed_t';
		}
		if ($kind === AstKind::CAST && ((int) ($expr->flags ?? 0) === AstKind::TYPE_OBJECT)) {
			return 'mixed_t';
		}
		if ($kind === AstKind::DIM) {
			$baseType = $this->inferExprType($expr->children['expr'] ?? null);
			if (preg_match('/^vector_t<(.+)>$/', $baseType, $matches) === 1) {
				return $matches[1];
			}
			if (($fixedArrayParts = $this->parseMappedFixedArrayType($baseType)) !== null) {
				return $fixedArrayParts['element'];
			}
				if (($hashTypeParts = $this->parseHashTypeParts($baseType)) !== null) {
					return $hashTypeParts['value'];
				}
			if ($baseType === 'dynamic_t<>') {
				return 'mixed_t';
			}
			if ($this->isUntypedTableType($baseType) || $baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
				return 'mixed_t';
			}
			return 'auto';
		}
		if ($kind === AstKind::PROP) {
			$baseType = $this->inferExprType($expr->children['expr'] ?? null);
			$propName = (string) ($expr->children['prop'] ?? '');
			$propertyDecl = $this->lookupPropertyDeclByMappedBaseType($baseType, $propName);
			if ($propertyDecl instanceof PropertyDecl && $propertyDecl->type !== null) {
				return $this->typeMapper->mapDeclaredType($propertyDecl->type);
			}
			if ($baseType === 'mixed_t' || $baseType === 'maybe_value_t') {
				return 'mixed_t';
			}
			return 'auto';
		}

		return 'auto';
	}

	private function lookupPropertyDeclByMappedBaseType(string $baseType, string $propName): ?PropertyDecl
	{
		$classType = $this->extractMappedClassCarrierType($baseType);
		if ($classType === null) {
			return null;
		}

		$normalized = ltrim($classType, ':');
		if (str_starts_with($normalized, 'scpp::')) {
			$normalized = substr($normalized, strlen('scpp::'));
		}

		$phpQualified = str_replace('::', '\\', $normalized);
		$phpShort = basename(str_replace('\\', '/', $phpQualified));
		$candidates = array_values(array_unique(array_filter([$phpQualified, $phpShort], static fn ($v) => $v !== '')));

		foreach ($candidates as $candidate) {
			$classDecl = $this->classDecls[$candidate] ?? null;
			if (!$classDecl instanceof ClassDecl) {
				continue;
			}
			foreach ($classDecl->properties as $property) {
				if ($property->name === $propName) {
					return $property;
				}
			}
		}

		return null;
	}

	private function extractMappedClassCarrierType(string $type): ?string
	{
		$trimmed = trim($type);
		if ($trimmed === '' || $trimmed === 'mixed_t' || $trimmed === 'auto') {
			return null;
		}

		if (preg_match('/^(?:shared_p|unique_p|weak_p|value_p)<(.+)>$/', $trimmed, $matches) === 1) {
			return trim($matches[1]);
		}

		if (str_contains($trimmed, '<')) {
			return null;
		}

		return $trimmed;
	}

	private function isForeachVectorLikeType(string $sourceType): bool
	{
		return str_contains($sourceType, 'vector_t<') || str_contains($sourceType, 'fixed_array_t<');
	}

private function isStdClassNameNode(mixed $classNode): bool
{
	if (!is_object($classNode) || (($classNode->kind ?? null) !== AstKind::NAME)) {
		return false;
	}

	$name = strtolower(ltrim((string) ($classNode->children['name'] ?? ''), '\\'));
	return $name === 'stdclass';
}

private function isStdClassNewExpr(mixed $expr): bool
{
	if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::NEW)) {
		return false;
	}

	$argsNode = $expr->children['args'] ?? null;
	$args = (is_object($argsNode) && isset($argsNode->children) && is_array($argsNode->children))
		? array_values($argsNode->children)
		: [];

	return $args === [] && $this->isStdClassNameNode($expr->children['class'] ?? null);
}

private function renderObjectCastExpr(mixed $expr, ?string $namespacePhp): string
{
	if (!is_object($expr) || (($expr->kind ?? null) !== AstKind::ARRAY)) {
		$this->errors[] = 'Only (object)[...] is supported in the current pass.';
		return '/* unsupported-object-cast */';
	}

	$elements = isset($expr->children) && is_array($expr->children)
		? array_values($expr->children)
		: [];

	if ($elements === []) {
		return 'mixed_t{dynamic_()}';
	}

	$items = [];
	foreach ($elements as $element) {
		if (!is_object($element) || (($element->kind ?? null) !== AstKind::ARRAY_ELEM)) {
			$this->errors[] = 'Unsupported object-cast array element shape at line ' . (int) ($expr->lineno ?? 0) . '.';
			return '/* unsupported-object-cast */';
		}

		$valueNode = $element->children['value'] ?? null;
		if ($valueNode === null) {
			$this->errors[] = 'Array unpack and empty array elements are not supported yet at line ' . (int) ($element->lineno ?? $expr->lineno ?? 0) . '.';
			return '/* unsupported-object-cast */';
		}

		$keyNode = $element->children['key'] ?? null;
		if ($keyNode === null) {
			$items[] = 'table_item_(' . $this->renderExpr($valueNode, $namespacePhp) . ')';
			continue;
		}

		$items[] = 'table_kv_(' . $this->renderExpr($keyNode, $namespacePhp) . ', ' . $this->renderExpr($valueNode, $namespacePhp) . ')';
	}

	return 'mixed_t{dynamic_(' . implode(', ', $items) . ')}';
}

	private function renderConditionalExpr(mixed $condNode, mixed $trueNode, mixed $falseNode, ?string $namespacePhp): string
	{
		$ternaryFn = $this->qualifyKnownPhpRuntimeSymbol('ternary_eval');
		$falseExpr = $this->renderExpr($falseNode, $namespacePhp);

		if ($trueNode === null) {
			$condExpr = $this->renderExpr($condNode, $namespacePhp);
			return '([&]() -> auto { auto __scpp_cond_value = ' . $condExpr . '; return ' . $ternaryFn
				. '([&]() -> decltype(auto) { return __scpp_cond_value; }, [&]() -> decltype(auto) { return __scpp_cond_value; }, [&]() -> decltype(auto) { return ' . $falseExpr . '; }); }())';
		}

		return $ternaryFn
			. '([&]() -> decltype(auto) { return ' . $this->renderExpr($condNode, $namespacePhp) . '; }, [&]() -> decltype(auto) { return ' . $this->renderExpr($trueNode, $namespacePhp) . '; }, [&]() -> decltype(auto) { return ' . $falseExpr . '; })';
	}

	private function renderCoalesceExpr(mixed $leftNode, mixed $rightNode, ?string $namespacePhp): string
	{
		$coalesceFn = $this->qualifyKnownPhpRuntimeSymbol('coalesce_eval');
		return $coalesceFn
			. '([&]() -> decltype(auto) { return ' . $this->renderExpr($leftNode, $namespacePhp) . '; }, [&]() -> decltype(auto) { return ' . $this->renderExpr($rightNode, $namespacePhp) . '; })';
	}


	private function inferConstantType(mixed $expr, ?string $namespacePhp): string
	{
		if (is_int($expr)) {
			return 'int_t<>';
		}
		if (is_float($expr)) {
			return 'float_t';
		}
		if (is_string($expr)) {
			return 'string_t';
		}
		if (!is_object($expr)) {
			return 'auto';
		}

		$kind = $expr->kind ?? null;
		if ($kind === AstKind::CONST) {
			$nameNode = $expr->children['name'] ?? null;
			$name = strtolower(ltrim(is_object($nameNode) ? (string) ($nameNode->children['name'] ?? '') : (string) $nameNode, '\\'));
			return match ($name) {
				'true', 'false' => 'bool_t',
				'null' => 'null_t',
				default => 'auto',
			};
		}
		if ($kind === AstKind::CAST) {
			$flags = (int) ($expr->flags ?? 0);
			return match ($flags) {
				AstKind::TYPE_STRING => 'string_t',
				AstKind::TYPE_LONG => 'int_t<>',
				AstKind::TYPE_DOUBLE => 'float_t',
				AstKind::TYPE_BOOL => 'bool_t',
				AstKind::TYPE_OBJECT => 'mixed_t',
				default => 'auto',
			};
		}
		if ($kind === AstKind::ENCAPS_LIST) {
			return 'string_t';
		}
		if ($kind === AstKind::BINARY_OP) {
			$flags = (int) ($expr->flags ?? 0);
			return match ($flags) {
				AstKind::BINARY_CONCAT => 'string_t',
				AstKind::BINARY_BOOL_AND,
				AstKind::BINARY_BOOL_OR,
				AstKind::BINARY_IS_SMALLER,
				AstKind::BINARY_IS_SMALLER_OR_EQUAL,
				AstKind::BINARY_IS_GREATER,
				AstKind::BINARY_IS_EQUAL,
				AstKind::BINARY_IS_IDENTICAL => 'bool_t',
				default => $this->inferConstantType($expr->children['left'] ?? null, $namespacePhp),
			};
		}

		return 'auto';
	}

	/** @return array<string, bool> */
	private function loadPredefinedConstants(): array
	{
		$result = [];
		foreach (array_keys(get_defined_constants()) as $constantName) {
			$result[(string) $constantName] = true;
		}
		return $result;
	}

	/**

	 * Recognizes null-like expressions that should map directly to runtime sentinels.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function isNullExpr(mixed $expr): bool
	{
		return is_object($expr)
			&& ($expr->kind ?? null) === AstKind::CONST
			&& (($expr->children['name']->children['name'] ?? null) === 'null');
	}

	/**

	 * Returns one tab-based indentation string, matching the project formatting preference.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function indent(int $level): string
	{
		return str_repeat("\t", $level);
	}
}
