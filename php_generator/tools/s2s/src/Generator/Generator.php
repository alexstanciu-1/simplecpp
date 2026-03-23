<?php
declare(strict_types=1);

namespace Scpp\S2S\Generator;

use Scpp\S2S\Emit\CppFile;
use Scpp\S2S\IR\ClassDecl;
use Scpp\S2S\IR\ConstantDecl;
use Scpp\S2S\IR\FunctionDecl;
use Scpp\S2S\IR\MethodDecl;
use Scpp\S2S\IR\PhpFile;
use Scpp\S2S\IR\Statement;
use Scpp\S2S\IR\UseDecl;
use Scpp\S2S\Lowering\TypeMapper;
use Scpp\S2S\Support\AstKind;

/**
 * Emits Simple C++ declarations and statements from the IR. This file is where the catalog rules are turned into concrete header/source text.
 *
 * Relationship to specs:
 * - this type exists to keep the implementation aligned with php_generator/specs/rules.md and rules_catalog.md
 * - the implementation favors explicit normalized data over ad-hoc AST access during emission
 */
final class Generator
{
	/** @var array<string, bool> */
	private array $declaredLocals = [];
	/** @var list<string> */
	private array $errors = [];
	/** @var array<string, string> */
	private array $localTypeComments = [];
	/** @var array<string, string> */
	private array $declaredLocalTypes = [];
	/** @var array<string, bool> */
	private array $predefinedConstants = [];
	private NameRegistry $nameRegistry;

	/**

	 * Stores collaborators and default state for this phase object.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function __construct(
		private readonly TypeMapper $typeMapper = new TypeMapper(),
	) {
		$this->predefinedConstants = $this->loadPredefinedConstants();
		$this->nameRegistry = new NameRegistry();
	}

	/**

	 * Generates the header/source pair for one lowered PHP file and accumulates generator diagnostics.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	public function generate(PhpFile $file): CppFile
	{
		$this->declaredLocals = [];
		$this->errors = [];
		$this->localTypeComments = $file->localTypeCommentsByKey;
		$this->declaredLocalTypes = [];
		$this->nameRegistry = NameRegistry::fromPhpFile($file);

		$baseName = pathinfo($file->path, PATHINFO_FILENAME);
		$header = ['#pragma once', '', '#include <scpp/runtime.hpp>', ''];
		$source = ['#include "' . $baseName . '.hpp"', ''];

		$hasRootNamespaceContent = ($file->rootUses !== [] || $file->constants !== [] || $file->classes !== [] || $file->functions !== [] || $file->rootStatements !== []);
		$rootMainName = $file->rootStatements !== [] ? '__scpp_main' : null;
		if ($hasRootNamespaceContent) {
			$this->emitNamespaceBlock($header, $source, 'scpp', null, $file->rootUses, $file->constants, $file->classes, $file->functions, $file->rootStatements, $rootMainName);
		}

		$namespaceMainTargets = [];
		foreach ($file->namespaces as $namespace) {
			$mainName = $namespace->statements !== [] ? '__scpp_main' : null;
			$this->emitNamespaceBlock(
				$header,
				$source,
				'scpp::' . str_replace('\\', '::', $namespace->name),
				$namespace->name,
				$namespace->uses,
				$namespace->constants,
				$namespace->classes,
				$namespace->functions,
				$namespace->statements,
				$mainName,
			);
			if ($mainName !== null) {
				$namespaceMainTargets[] = '::scpp::' . str_replace('\\', '::', $namespace->name) . '::' . $mainName . '()';
			}
		}

		if ($file->rootStatements !== [] && $namespaceMainTargets !== []) {
			$this->errors[] = 'Root executable statements and namespace executable statements are not mixed in the current pass.';
		}

		if ($file->rootStatements !== []) {
			$source[] = 'int main() {';
			$source[] = $this->indent(1) . 'return scpp::__scpp_main();';
			$source[] = '}';
			$source[] = '';
		} elseif ($namespaceMainTargets !== []) {
			$source[] = 'int main() {';
			$source[] = $this->indent(1) . 'return ' . $namespaceMainTargets[0] . ';';
			$source[] = '}';
			$source[] = '';
		}

		return new CppFile($baseName, $header, $source, $this->errors);
	}

	/** @param list<UseDecl> $uses @param list<ConstantDecl> $constants @param list<ClassDecl> $classes @param list<FunctionDecl> $functions @param list<Statement> $statements */
	private function emitNamespaceBlock(array &$header, array &$source, string $namespaceCpp, ?string $namespacePhp, array $uses, array $constants, array $classes, array $functions, array $statements, ?string $syntheticMainName = null): void
	{
		$header[] = 'namespace ' . $namespaceCpp . ' {';
		$header[] = '';
		$source[] = 'namespace ' . $namespaceCpp . ' {';
		$source[] = $this->indent(1) . 'using namespace ::scpp::php;';
		$source[] = '';

		$useLines = $this->renderUseDeclarations($uses);
		foreach ($useLines as $useLine) {
			$header[] = $this->indent(1) . $useLine;
		}
		if ($useLines !== []) {
			$header[] = '';
		}

		foreach ($constants as $constant) {
			$this->emitConstant($header, $constant, $namespacePhp);
		}
		if ($constants !== []) {
			$header[] = '';
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

		$header[] = '}';
		$header[] = '';
		$source[] = '}';
		$source[] = '';
	}

	/** @param list<UseDecl> $uses @return list<string> */
	private function renderUseDeclarations(array $uses): array
	{
		$out = [];
		foreach ($uses as $use) {
			$line = $this->renderUseDeclaration($use);
			if ($line !== null) {
				$out[] = $line;
			}
		}
		return $out;
	}

	/**

	 * Renders one lowered use declaration while rejecting PHP forms that do not map cleanly to the current prototype.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderUseDeclaration(UseDecl $use): ?string
	{
		if ($use->isGrouped) {
			$this->errors[] = 'Grouped use imports are not supported at line ' . $use->line . '.';
			return null;
		}
		if ($use->alias !== null) {
			$this->errors[] = 'Aliased use imports are not supported at line ' . $use->line . '.';
			return null;
		}
		if ($use->kind === 'normal') {
			$this->errors[] = 'Plain use imports are not supported at line ' . $use->line . '. Only use function/use const map to C++ using declarations.';
			return null;
		}
		if ($use->name === '') {
			$this->errors[] = 'Empty use import is not supported at line ' . $use->line . '.';
			return null;
		}
		return 'using ::scpp::' . str_replace('\\', '::', ltrim($use->name, '\\')) . ';';
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
		$header[] = 'inline const auto ' . $constant->name . ' = ' . $this->renderExpr($constant->value, $namespacePhp) . ';';
	}

	/**

	 * Emits a class declaration to the header and its method definitions to the source file.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function emitClass(array &$header, array &$source, ClassDecl $class, ?string $namespacePhp): void
	{
		$header[] = 'class ' . $class->name . ' {';
		$header[] = 'public:';
		foreach ($class->properties as $property) {
			$type = $property->type !== null ? $this->typeMapper->mapDeclaredType($property->type) : 'auto';
			$header[] = $this->indent(1) . $type . ' ' . $property->name . ';';
		}
		foreach ($class->methods as $method) {
			$header[] = $this->indent(1) . $this->renderMethodDeclaration($method, $class->name, $namespacePhp) . ';';
		}
		$header[] = '};';
		$header[] = '';

		foreach ($class->methods as $method) {
			$source[] = $this->renderMethodDefinition($class->name, $method, $namespacePhp);
			$source[] = '';
		}
	}

	/**

	 * Emits one top-level function declaration/definition pair.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function emitFunction(array &$header, array &$source, FunctionDecl $function, ?string $namespacePhp): void
	{
		$header[] = $this->renderFunctionDeclaration($function, $namespacePhp) . ';';
		$header[] = '';
		$source[] = $this->renderFunctionDefinition($function, $namespacePhp);
		$source[] = '';
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
		$header[] = 'int ' . $name . '();';
		$header[] = '';
		$source[] = 'int ' . $name . '() {';
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		foreach ($statements as $statement) {
			foreach ($this->renderStatement($statement, $namespacePhp) as $line) {
				$source[] = $this->indent(1) . $line;
			}
		}
		$source[] = $this->indent(1) . 'return 0;';
		$source[] = '}';
	}

	/**

	 * Renders a method signature using the current type and constructor mapping rules.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderMethodDeclaration(MethodDecl $method, ?string $className = null, ?string $namespacePhp = null): string
	{
		if ($method->name === '__construct' && $className !== null) {
			return $className . '(' . $this->renderParams($method->params, true, $namespacePhp) . ')';
		}
		$prefix = $method->isStatic ? 'static ' : '';
		$returnType = $this->typeMapper->mapReturnType($method->returnType, $method->returnsByReference);
		return $prefix . $returnType . ' ' . $method->name . '(' . $this->renderParams($method->params, true, $namespacePhp) . ')';
	}

	/**

	 * Renders the out-of-class method definition body into the source file.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderMethodDefinition(string $className, MethodDecl $method, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		if ($method->name === '__construct') {
			$signature = $className . '::' . $className . '(' . $this->renderParams($method->params, false, $namespacePhp) . ')';
		} else {
			$returnType = $this->typeMapper->mapReturnType($method->returnType, $method->returnsByReference);
			$signature = $returnType . ' ' . $className . '::' . $method->name . '(' . $this->renderParams($method->params, false, $namespacePhp) . ')';
		}
		return $signature . " {\n" . $this->renderBody($method->statements, $namespacePhp) . "\n}";
	}

	/**

	 * Renders a function signature for the generated header.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderFunctionDeclaration(FunctionDecl $function, ?string $namespacePhp = null): string
	{
		$returnType = $this->typeMapper->mapReturnType($function->returnType, $function->returnsByReference);
		return $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, true, $namespacePhp) . ')';
	}

	/**

	 * Renders one full function body for the generated source file.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderFunctionDefinition(FunctionDecl $function, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		$this->declaredLocalTypes = [];
		foreach ($function->params as $param) {
			$this->declaredLocals[$param->name] = true;
			if ($param->type !== null) {
				$this->declaredLocalTypes[$param->name] = $param->type;
			}
		}
		$returnType = $this->typeMapper->mapReturnType($function->returnType, $function->returnsByReference);
		$signature = $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, false, $namespacePhp) . ')';
		return $signature . " {\n" . $this->renderBody($function->statements, $namespacePhp) . "\n}";
	}

	/**

	 * Renders the lowered parameter list, optionally including default expressions when a declaration requires them.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderParams(array $params, bool $includeDefaults, ?string $namespacePhp): string
	{
		$out = [];
		foreach ($params as $param) {
			$rendered = $this->typeMapper->mapParamType($param->type, $param->isReference) . ' ' . $param->name;
			if ($includeDefaults && $param->default !== null) {
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

	private function renderBody(array $statements, ?string $namespacePhp): string
	{
		$lines = [];
		foreach ($statements as $statement) {
			foreach ($this->renderStatement($statement, $namespacePhp) as $line) {
				$lines[] = $this->indent(1) . $line;
			}
		}
		return implode("\n", $lines);
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
		if ($statement->kind === 'assign' || $statement->kind === 'assign_ref') {
			$varNode = $statement->payload['var'] ?? null;
			$exprNode = $statement->payload['expr'] ?? null;
			$target = $this->renderAssignmentTarget($varNode, $namespacePhp);
			$name = $this->extractSimpleVarName($varNode);
			$key = $statement->line . ':' . ($name ?? '');
			$typed = $name !== null ? ($this->localTypeComments[$key] ?? null) : null;

			if ($statement->kind === 'assign' && $name !== null && !isset($this->declaredLocals[$name])) {
				$chainLines = $this->tryRenderDeclarationAssignChain($varNode, $exprNode, $typed, $namespacePhp);
				if ($chainLines !== null) {
					return $chainLines;
				}
			}

			if ($typed !== null) {
				$validationError = $this->validateTypedLocalAssignment($typed, $statement->kind, $exprNode, $statement->line);
				if ($validationError !== null) {
					$this->errors[] = $validationError;
					return ['// ERROR: ' . $validationError];
				}
			}

			$expr = $statement->kind === 'assign_ref'
				? 'ref(' . $this->renderExpr($exprNode, $namespacePhp) . ')'
				: $this->renderInitializerExpr($exprNode, $typed, $namespacePhp);
			if ($exprNode !== null && $this->isNullExpr($exprNode) && $typed === null && $name !== null && !isset($this->declaredLocals[$name])) {
				$this->errors[] = 'Untyped null assignment is rejected at line ' . $statement->line . '.';
				return ['// ERROR: untyped null assignment rejected'];
			}

			if ($statement->kind === 'assign_ref') {
				if ($name !== null && !isset($this->declaredLocals[$name])) {
					$this->declaredLocals[$name] = true;
					if ($typed !== null) {
						$this->declaredLocalTypes[$name] = $typed;
						return [$this->typeMapper->mapTypedLocalType($typed) . ' ' . $name . ' = ' . $expr . ';'];
					}
					return ['auto ' . $name . ' = ' . $expr . ';'];
				}
				return [$target . ' = ' . $expr . ';'];
			}

			if ($name !== null && !isset($this->declaredLocals[$name])) {
				$this->declaredLocals[$name] = true;
				if ($typed !== null) {
					$this->declaredLocalTypes[$name] = $typed;
					return [$this->typeMapper->mapTypedLocalType($typed) . ' ' . $name . ' = ' . $expr . ';'];
				}
				return ['auto ' . $name . ' = ' . $expr . ';'];
			}
			return [$target . ' = ' . $expr . ';'];
		}

		if ($statement->kind === 'static_var') {
			$varNode = $statement->payload['var'] ?? null;
			$name = (string) (($varNode['children']['name'] ?? '') ?: 'tmp');
			$default = $this->renderExpr($statement->payload['default'] ?? null, $namespacePhp);
			$this->declaredLocals[$name] = true;
			return ['static int_t ' . $name . ' = ' . $default . ';'];
		}

		if ($statement->kind === 'return') {
			if ($statement->payload === null) {
				return ['return;'];
			}
			return ['return ' . $this->renderExpr($statement->payload, $namespacePhp) . ';'];
		}

		if ($statement->kind === 'echo') {
			// Preserve the exporter shape: one AST_ECHO node becomes one runtime print call.
			return ['::scpp::php::echo(' . $this->renderExpr($statement->payload, $namespacePhp) . ');'];
		}

		if ($statement->kind === 'unset') {
			// Preserve the exporter shape: one AST_UNSET node becomes one runtime unset call.
			return ['::scpp::php::unset(' . $this->renderExpr($statement->payload, $namespacePhp) . ');'];
		}

		if ($statement->kind === 'if') {
			return $this->renderIfStatement($statement->payload, $namespacePhp);
		}

		if ($statement->kind === 'while') {
			$lines = ['while (' . $this->renderConditionExpr($statement->payload['cond'] ?? null, $namespacePhp) . ') {'];
			foreach ($this->renderNestedStatements($statement->payload['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$lines[] = '}';
			return $lines;
		}

		if ($statement->kind === 'do_while') {
			$lines = ['do {'];
			foreach ($this->renderNestedStatements($statement->payload['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$lines[] = '} while (' . $this->renderConditionExpr($statement->payload['cond'] ?? null, $namespacePhp) . ');';
			return $lines;
		}

		if ($statement->kind === 'for') {
			$init = $this->renderForInit($statement->payload['init'] ?? [], $namespacePhp);
			$cond = $this->renderForConditionClause($statement->payload['cond'] ?? [], $namespacePhp);
			$loop = $this->renderForClause($statement->payload['loop'] ?? [], $namespacePhp, '');
			$lines = ['for (' . $init . '; ' . $cond . '; ' . $loop . ') {'];
			foreach ($this->renderNestedStatements($statement->payload['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$lines[] = '}';
			return $lines;
		}

		if ($statement->kind === 'switch') {
			$lines = ['switch (' . $this->renderSwitchExpr($statement->payload['cond'] ?? null, $namespacePhp) . ') {'];
			foreach (($statement->payload['cases'] ?? []) as $case) {
				$caseCond = $case['cond'] ?? null;
				// Each lowered switch case is emitted in source order so generated case/default blocks preserve the catalog shape.
				$lines[] = $caseCond === null
					? $this->indent(1) . 'default:'
					: $this->indent(1) . 'case ' . $this->renderSwitchCaseValue($caseCond) . ':';
				foreach ($this->renderNestedStatements($case['stmts'] ?? [], $namespacePhp) as $line) {
					$lines[] = $line;
				}
			}
			$lines[] = '}';
			return $lines;
		}

		if ($statement->kind === 'break') {
			$depth = $statement->payload;
			if ($depth !== null && $depth !== 1) {
				$this->errors[] = 'break depth > 1 is not supported at line ' . $statement->line . '.';
				return ['// ERROR: unsupported break depth'];
			}
			return ['break;'];
		}

		if ($statement->kind === 'continue') {
			$depth = $statement->payload;
			if ($depth !== null && $depth !== 1) {
				$this->errors[] = 'continue depth > 1 is not supported at line ' . $statement->line . '.';
				return ['// ERROR: unsupported continue depth'];
			}
			return ['continue;'];
		}

		if ($statement->kind === 'expr') {
			return [$this->renderExpr($statement->payload, $namespacePhp) . ';'];
		}

		return ['// Unsupported statement'];
	}

	/** @param list<array{cond:mixed,stmts:list<Statement>,line:int}> $branches @return list<string> */
	private function renderIfStatement(array $branches, ?string $namespacePhp): array
	{
		$lines = [];
		$index = 0;
		foreach ($branches as $branch) {
			$prefix = $index === 0 ? 'if' : (($branch['cond'] ?? null) === null ? 'else' : 'else if');
			if ($prefix === 'else') {
				$lines[] = 'else {';
			} else {
				$lines[] = $prefix . ' (' . $this->renderConditionExpr($branch['cond'] ?? null, $namespacePhp) . ') {';
			}
			foreach ($this->renderNestedStatements($branch['stmts'] ?? [], $namespacePhp) as $line) {
				$lines[] = $line;
			}
			$lines[] = '}';
			$index++;
		}
		return $lines;
	}

	/** @param list<Statement> $statements @return list<string> */
	private function renderNestedStatements(array $statements, ?string $namespacePhp): array
	{
		$lines = [];
		foreach ($statements as $statement) {
			foreach ($this->renderStatement($statement, $namespacePhp) as $line) {
				$lines[] = $this->indent(1) . $line;
			}
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
			if (is_array($expr) && ($expr['kind'] ?? null) === AstKind::ASSIGN) {
				$varNode = $expr['children']['var'] ?? null;
				$name = $this->extractSimpleVarName($varNode);
				if ($name !== null && !isset($this->declaredLocals[$name])) {
					$this->declaredLocals[$name] = true;
					$out[] = 'auto ' . $name . ' = ' . $this->renderExpr($expr['children']['expr'] ?? null, $namespacePhp);
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

	 * Renders any condition expression with the bool conversion required by the current Simple C++ runtime contract.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderConditionExpr(mixed $expr, ?string $namespacePhp): string
	{
		$rendered = $this->renderExpr($expr, $namespacePhp);
		if ($this->exprProducesBool($expr)) {
			return '(' . $rendered . ').native_value()';
		}
		return 'cast<bool_t>(' . $rendered . ').native_value()';
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
		if (!is_array($expr)) {
			return false;
		}
		$kind = $expr['kind'] ?? null;
		if ($kind === AstKind::UNARY_OP) {
			return true;
		}
		if ($kind === AstKind::BINARY_OP) {
			return in_array((int) ($expr['flags'] ?? 0), [
				AstKind::BINARY_BOOL_AND,
				AstKind::BINARY_BOOL_OR,
				AstKind::BINARY_IS_SMALLER,
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
		return is_array($expr) ? '(' . $rendered . ').native_value()' : $rendered;
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
		if (!is_array($expr) || (($expr['kind'] ?? null) !== AstKind::VAR)) {
			return null;
		}
		$name = (string) ($expr['children']['name'] ?? '');
		return $name !== '' ? $name : null;
	}

	/**

	 * Renders the left-hand side of an assignment for the currently supported assignment targets.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderAssignmentTarget(mixed $expr, ?string $namespacePhp): string
	{
		return $this->renderExpr($expr, $namespacePhp);
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
		if ($leftName === null || !is_array($exprNode) || (($exprNode['kind'] ?? null) !== AstKind::ASSIGN)) {
			return null;
		}
		$rightVarNode = $exprNode['children']['var'] ?? null;
		$rightExprNode = $exprNode['children']['expr'] ?? null;
		$rightName = $this->extractSimpleVarName($rightVarNode);
		if ($rightName === null || isset($this->declaredLocals[$rightName])) {
			return null;
		}
		$rightExpr = $this->renderExpr($rightExprNode, $namespacePhp);
		$this->declaredLocals[$rightName] = true;
		$this->declaredLocals[$leftName] = true;
		$leftType = $typed !== null ? $this->typeMapper->mapTypedLocalType($typed) : 'auto';
		return [
			'auto ' . $rightName . ' = ' . $rightExpr . ';',
			$leftType . ' ' . $leftName . ' = ' . $rightName . ';',
		];
	}

	private function validateTypedLocalAssignment(string $typedLocalType, string $statementKind, mixed $exprNode, int $line): ?string
	{
		if (!$this->typeMapper->isRefLocalType($typedLocalType)) {
			return null;
		}

		if ($statementKind !== 'assign_ref') {
			return 'ref typed locals must be initialized via reference assignment at line ' . $line . '.';
		}

		$innerType = $this->typeMapper->unwrapRefLocalType($typedLocalType);
		if (!$this->typeMapper->isObjectLikeType($innerType)) {
			return null;
		}

		$sourceName = $this->extractSimpleVarName($exprNode);
		if ($sourceName === null) {
			return 'ref typed object locals require a simple value local source at line ' . $line . '.';
		}

		$sourceType = $this->declaredLocalTypes[$sourceName] ?? null;
		if ($sourceType === null || !$this->typeMapper->isInlineValueType($sourceType)) {
			return 'ref typed object locals cannot bind to handle-like or untyped sources at line ' . $line . '.';
		}

		$sourceInnerType = $this->typeMapper->unwrapInlineValueType($sourceType);
		if ($sourceInnerType !== $innerType) {
			return 'ref typed object local type does not match referenced value source at line ' . $line . '.';
		}

		return null;
	}

	private function renderInitializerExpr(mixed $expr, ?string $typedLocalType, ?string $namespacePhp): string
	{
		if ($typedLocalType !== null && $this->typeMapper->isInlineValueType($typedLocalType)) {
			$innerType = $this->typeMapper->unwrapInlineValueType($typedLocalType);
			if (is_array($expr) && (($expr['kind'] ?? null) === AstKind::NEW)) {
				return 'value<' . $innerType . '>(' . $this->renderArgs($expr['children']['args']['children'] ?? [], $namespacePhp) . ')';
			}
		}

		return $this->renderExpr($expr, $namespacePhp);
	}

	/**

	 * Renders one expression node from php-ast into the current Simple C++ expression subset.

	 *

	 * Relationship to specs:

	 * - preserves the subset and lowering rules documented for the prototype

	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit

	 */

	private function renderExpr(mixed $expr, ?string $namespacePhp): string
	{
		if (is_int($expr)) {
			return 'static_cast<int_t>(' . $expr . ')';
		}
		if (is_float($expr)) {
			return 'static_cast<float_t>(' . $expr . ')';
		}
		if (is_string($expr)) {
			return 'string_t(' . json_encode($expr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ')';
		}
		if (!is_array($expr)) {
			return '/* unsupported-expr */';
		}

		$kind = $expr['kind'] ?? null;
		if ($kind === AstKind::VAR) {
			$name = (string) ($expr['children']['name'] ?? 'var');
			return $name === 'this' ? 'this' : $name;
		}
		if ($kind === AstKind::CONST) {
			$name = (string) ($expr['children']['name']['children']['name'] ?? '');
			$flags = (int) ($expr['children']['name']['flags'] ?? 0);
			return match (strtolower(ltrim($name, '\\'))) {
				'true' => 'static_cast<bool_t>(true)',
				'false' => 'static_cast<bool_t>(false)',
				'null' => 'null',
				default => $this->renderConstantName($name, $flags, $namespacePhp),
			};
		}
		if ($kind === AstKind::BINARY_OP) {
			$leftNode = $expr['children']['left'] ?? null;
			$rightNode = $expr['children']['right'] ?? null;
			$left = $this->renderExpr($leftNode, $namespacePhp);
			$right = $this->renderExpr($rightNode, $namespacePhp);
			$flags = (int) ($expr['flags'] ?? 0);

			return match ($flags) {
				AstKind::PLUS => $left . ' + ' . $right,
				AstKind::MINUS => $left . ' - ' . $right,
				AstKind::BINARY_CONCAT => $this->renderStringConcat($leftNode, $rightNode, $namespacePhp),
				AstKind::BINARY_BOOL_AND => '(' . $left . ' && ' . $right . ')',
				AstKind::BINARY_BOOL_OR => '(' . $left . ' || ' . $right . ')',
				AstKind::BINARY_IS_SMALLER => '(' . $left . ' < ' . $right . ')',
				AstKind::BINARY_IS_GREATER => '(' . $left . ' > ' . $right . ')',
				AstKind::BINARY_IS_EQUAL, AstKind::BINARY_IS_IDENTICAL => '(' . $left . ' == ' . $right . ')',
				default => '/* unsupported-binary-op-' . $flags . ' */',
			};
		}
		if ($kind === AstKind::CAST) {
			$inner = $this->renderExpr($expr['children']['expr'] ?? null, $namespacePhp);
			$flags = (int) ($expr['flags'] ?? 0);
			return match ($flags) {
				AstKind::TYPE_STRING => 'cast<string_t>(' . $inner . ')',
				AstKind::TYPE_LONG => 'static_cast<int_t>(' . $inner . ')',
				AstKind::TYPE_DOUBLE => 'static_cast<float_t>(' . $inner . ')',
				AstKind::TYPE_BOOL => 'static_cast<bool_t>(' . $inner . ')',
				default => '/* unsupported-cast */',
			};
		}
		if ($kind === AstKind::ENCAPS_LIST) {
			return $this->renderInterpolatedString($expr, $namespacePhp);
		}
		if ($kind === AstKind::DIM) {
			$base = $this->renderExpr($expr['children']['expr'] ?? null, $namespacePhp);
			$dim = $this->renderExpr($expr['children']['dim'] ?? null, $namespacePhp);
			return $base . '[' . $dim . ']';
		}
		if ($kind === AstKind::PROP) {
			$base = $this->renderExpr($expr['children']['expr'] ?? null, $namespacePhp);
			$prop = (string) ($expr['children']['prop'] ?? 'prop');
			return $base === 'this' ? 'this->' . $prop : $base . '->' . $prop;
		}
		if ($kind === AstKind::NEW) {
			$class = $this->renderClassName($expr['children']['class'] ?? null, $namespacePhp);
			return 'create<' . $class . '>(' . $this->renderArgs($expr['children']['args']['children'] ?? [], $namespacePhp) . ')';
		}
		if ($kind === AstKind::STATIC_CALL) {
			$classNode = $expr['children']['class'] ?? null;
			$method = (string) ($expr['children']['method'] ?? '');
			$class = is_array($classNode) && ($classNode['kind'] ?? null) === AstKind::VAR
				? '::scpp::class_t<decltype(' . $this->renderExpr($classNode, $namespacePhp) . ')>'
				: $this->renderClassName($classNode, $namespacePhp);
			return $class . '::' . $method . '(' . $this->renderArgs($expr['children']['args']['children'] ?? [], $namespacePhp) . ')';
		}
		if ($kind === AstKind::AST_ISSET) {
			// In this exporter, multi-argument isset() is already normalized into boolean-op trees.
			// AST_ISSET itself carries exactly one operand in `children['var']`.
			return '::scpp::php::isset(' . $this->renderExpr($expr['children']['var'] ?? null, $namespacePhp) . ')';
		}
		if ($kind === AstKind::CALL) {
			$nameExpr = $expr['children']['expr'] ?? null;
			$name = $this->renderNameExpr($nameExpr, $namespacePhp);
			return $name . '(' . $this->renderArgs($expr['children']['args']['children'] ?? [], $namespacePhp) . ')';
		}
		if ($kind === AstKind::METHOD_CALL) {
			$base = $this->renderExpr($expr['children']['expr'] ?? null, $namespacePhp);
			$method = (string) ($expr['children']['method'] ?? 'call');
			return $base . '->' . $method . '(' . $this->renderArgs($expr['children']['args']['children'] ?? [], $namespacePhp) . ')';
		}
		if ($kind === AstKind::ASSIGN) {
			$target = $this->renderAssignmentTarget($expr['children']['var'] ?? null, $namespacePhp);
			$value = $this->renderExpr($expr['children']['expr'] ?? null, $namespacePhp);
			return '(' . $target . ' = ' . $value . ')';
		}
		if ($kind === AstKind::UNARY_OP) {
			$inner = $this->renderExpr($expr['children']['expr'] ?? null, $namespacePhp);
			$flags = (int) ($expr['flags'] ?? 0);
			return match ($flags) {
				AstKind::UNARY_BOOL_NOT => '(!' . $inner . ')',
				default => '/* unsupported-unary-op-' . $flags . ' */',
			};
		}
		if ($kind === AstKind::POST_INC) {
			$target = $this->renderAssignmentTarget($expr['children']['var'] ?? null, $namespacePhp);
			return $target . ' = ' . $target . ' + static_cast<int_t>(1)';
		}
		if ($kind === AstKind::CONDITIONAL) {
			$condNode = $expr['children']['cond'] ?? null;
			$trueNode = $expr['children']['true'] ?? null;
			$falseNode = $expr['children']['false'] ?? null;
			$trueExpr = $trueNode === null ? $this->renderExpr($condNode, $namespacePhp) : $this->renderExpr($trueNode, $namespacePhp);
			return '(' . $this->renderConditionExpr($condNode, $namespacePhp) . ' ? ' . $trueExpr . ' : ' . $this->renderExpr($falseNode, $namespacePhp) . ')';
		}

		return '/* unsupported-expr-kind-' . $kind . ' */';
	}

	/** @return list<mixed> */
	private function extractVariadicPayload(array $node): array
	{
		$children = $node['children'] ?? [];

		if (array_key_exists('expr', $children)) {
			$expr = $children['expr'];
			if (is_array($expr) && array_key_exists('children', $expr) && is_array($expr['children'])) {
				return array_values($expr['children']);
			}
			return [$expr];
		}

		if (array_key_exists('var', $children)) {
			$var = $children['var'];
			if (is_array($var) && array_key_exists('children', $var) && is_array($var['children'])) {
				return array_values($var['children']);
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

	private function renderInterpolatedString(array $expr, ?string $namespacePhp): string
	{
		$parts = [];
		foreach (($expr['children'] ?? []) as $child) {
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
			return 'cast<string_t>(' . $this->renderExpr($expr, $namespacePhp) . ')';
		}

		if (!is_array($expr)) {
			return 'string_t("")';
		}

		$kind = $expr['kind'] ?? null;
		if ($kind === AstKind::CONST) {
			$name = strtolower((string) ($expr['children']['name']['children']['name'] ?? ''));
			if ($name === 'null' || $name === 'true' || $name === 'false') {
				return 'cast<string_t>(' . $this->renderExpr($expr, $namespacePhp) . ')';
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
			AstKind::CAST,
			AstKind::BINARY_OP,
			AstKind::ASSIGN,
			AstKind::CLASS_CONST,
			AstKind::STATIC_PROP => 'cast<string_t>(' . $rendered . ')',
			default => 'cast<string_t>(' . $rendered . ')',
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
		if (!is_array($expr)) {
			return 'call';
		}
		if (($expr['kind'] ?? null) === AstKind::NAME) {
			$name = (string) ($expr['children']['name'] ?? 'call');
			$flags = (int) ($expr['flags'] ?? 0);
			$resolved = $this->nameRegistry->resolveFunction($name, $flags, $namespacePhp);
			if ($resolved !== null) {
				return '::scpp::' . str_replace('\\', '::', $resolved);
			}
			return ltrim(str_replace('\\', '::', $name), ':');
		}
		return $this->renderExpr($expr, $namespacePhp);
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
		if (is_array($expr) && array_key_exists('children', $expr) && is_array($expr['children'])) {
			$children = $expr['children'];
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
			$out[] = $this->renderExpr($arg, $namespacePhp);
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
		if (!is_array($node)) {
			return 'Unknown';
		}
		$name = (string) ($node['children']['name'] ?? 'Unknown');
		$flags = (int) ($node['flags'] ?? 0);
		$resolved = $this->nameRegistry->resolveClass($name, $flags, $namespacePhp);
		if ($resolved !== null) {
			return '::scpp::' . str_replace('\\', '::', $resolved);
		}
		$name = ltrim($name, '\\');
		if ($namespacePhp !== null && !str_contains($name, '\\')) {
			return $name;
		}
		return str_replace('\\', '::', $name);
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

		$resolved = $this->nameRegistry->resolveConstant($name, $flags, $namespacePhp);
		if ($resolved !== null) {
			return '::scpp::' . str_replace('\\', '::', $resolved);
		}

		if (isset($this->predefinedConstants[$trimmed])) {
			return '::scpp::php::' . str_replace('\\', '::', $trimmed);
		}

		if (str_contains($trimmed, '\\')) {
			return '::scpp::' . str_replace('\\', '::', $trimmed);
		}

		return $trimmed;
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
		return is_array($expr)
			&& ($expr['kind'] ?? null) === AstKind::CONST
			&& (($expr['children']['name']['children']['name'] ?? null) === 'null');
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
