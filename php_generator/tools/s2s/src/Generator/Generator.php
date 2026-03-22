<?php
declare(strict_types=1);

namespace Scpp\S2S\Generator;

use Scpp\S2S\Emit\CppFile;
use Scpp\S2S\IR\ClassDecl;
use Scpp\S2S\IR\FunctionDecl;
use Scpp\S2S\IR\MethodDecl;
use Scpp\S2S\IR\PhpFile;
use Scpp\S2S\IR\Statement;
use Scpp\S2S\Lowering\TypeMapper;
use Scpp\S2S\Support\AstKind;

final class Generator
{
	/** @var array<string, bool> */
	private array $declaredLocals = [];
	/** @var list<string> */
	private array $errors = [];
	/** @var array<string, string> */
	private array $localTypeComments = [];
	/** @var array<string, bool> */
	private array $predefinedConstants = [];
	private NameRegistry $nameRegistry;

	public function __construct(
		private readonly TypeMapper $typeMapper = new TypeMapper(),
	) {
		$this->predefinedConstants = $this->loadPredefinedConstants();
		$this->nameRegistry = new NameRegistry();
	}

	public function generate(PhpFile $file): CppFile
	{
		$this->declaredLocals = [];
		$this->errors = [];
		$this->localTypeComments = $file->localTypeCommentsByKey;
		$this->nameRegistry = NameRegistry::fromPhpFile($file);

		$baseName = pathinfo($file->path, PATHINFO_FILENAME);
		$header = ['#pragma once', '', '#include <scpp/runtime.hpp>', ''];
		$source = ['#include "' . $baseName . '.hpp"', ''];

		$hasRootNamespaceContent = ($file->classes !== [] || $file->functions !== [] || $file->rootStatements !== []);
		$rootMainName = $file->rootStatements !== [] ? 'main' : null;
		if ($hasRootNamespaceContent) {
			$this->emitNamespaceBlock($header, $source, 'scpp', null, $file->classes, $file->functions, $file->rootStatements, $rootMainName);
		}

		$namespaceMainTargets = [];
		foreach ($file->namespaces as $namespace) {
			$mainName = $namespace->statements !== [] ? '__scpp_main' : null;
			$this->emitNamespaceBlock(
				$header,
				$source,
				'scpp::' . str_replace('\\', '::', $namespace->name),
				$namespace->name,
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
			$source[] = $this->indent(1) . 'return scpp::main();';
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

	/** @param list<ClassDecl> $classes @param list<FunctionDecl> $functions @param list<Statement> $statements */
	private function emitNamespaceBlock(array &$header, array &$source, string $namespaceCpp, ?string $namespacePhp, array $classes, array $functions, array $statements, ?string $syntheticMainName = null): void
	{
		$header[] = 'namespace ' . $namespaceCpp . ' {';
		$header[] = '';
		$source[] = 'namespace ' . $namespaceCpp . ' {';
		$source[] = $this->indent(1) . 'using namespace ::scpp::php;';
		$source[] = '';

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

	private function emitFunction(array &$header, array &$source, FunctionDecl $function, ?string $namespacePhp): void
	{
		$header[] = $this->renderFunctionDeclaration($function, $namespacePhp) . ';';
		$header[] = '';
		$source[] = $this->renderFunctionDefinition($function, $namespacePhp);
		$source[] = '';
	}

	private function emitNamespaceMain(array &$header, array &$source, string $name, array $statements, ?string $namespacePhp): void
	{
		$header[] = 'int ' . $name . '();';
		$header[] = '';
		$source[] = 'int ' . $name . '() {';
		$this->declaredLocals = [];
		foreach ($statements as $statement) {
			foreach ($this->renderStatement($statement, $namespacePhp) as $line) {
				$source[] = $this->indent(1) . $line;
			}
		}
		$source[] = $this->indent(1) . 'return 0;';
		$source[] = '}';
	}


	private function renderMethodDeclaration(MethodDecl $method, ?string $className = null, ?string $namespacePhp = null): string
	{
		if ($method->name === '__construct' && $className !== null) {
			return $className . '(' . $this->renderParams($method->params, true, $namespacePhp) . ')';
		}
		$prefix = $method->isStatic ? 'static ' : '';
		$returnType = $this->typeMapper->mapReturnType($method->returnType, $method->returnsByReference);
		return $prefix . $returnType . ' ' . $method->name . '(' . $this->renderParams($method->params, true, $namespacePhp) . ')';
	}

	private function renderMethodDefinition(string $className, MethodDecl $method, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
		}
		if ($method->name === '__construct') {
			$signature = $className . '::' . $className . '(' . $this->renderParams($method->params, false, $namespacePhp) . ')';
		} else {
			$returnType = $this->typeMapper->mapReturnType($method->returnType, $method->returnsByReference);
			$signature = $returnType . ' ' . $className . '::' . $method->name . '(' . $this->renderParams($method->params, false, $namespacePhp) . ')';
		}
		return $signature . " {\n" . $this->renderBody($method->statements, $namespacePhp) . "\n}";
	}

	private function renderFunctionDeclaration(FunctionDecl $function, ?string $namespacePhp = null): string
	{
		$returnType = $this->typeMapper->mapReturnType($function->returnType, $function->returnsByReference);
		return $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, true, $namespacePhp) . ')';
	}

	private function renderFunctionDefinition(FunctionDecl $function, ?string $namespacePhp): string
	{
		$this->declaredLocals = [];
		foreach ($function->params as $param) {
			$this->declaredLocals[$param->name] = true;
		}
		$returnType = $this->typeMapper->mapReturnType($function->returnType, $function->returnsByReference);
		$signature = $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params, false, $namespacePhp) . ')';
		return $signature . " {\n" . $this->renderBody($function->statements, $namespacePhp) . "\n}";
	}

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

			$expr = $this->renderExpr($exprNode, $namespacePhp);
			if ($exprNode !== null && $this->isNullExpr($exprNode) && $typed === null && $name !== null && !isset($this->declaredLocals[$name])) {
				$this->errors[] = 'Untyped null assignment is rejected at line ' . $statement->line . '.';
				return ['// ERROR: untyped null assignment rejected'];
			}

			if ($statement->kind === 'assign_ref') {
				if ($name !== null && !isset($this->declaredLocals[$name])) {
					$this->declaredLocals[$name] = true;
					return ['auto& ' . $name . ' = ' . $expr . ';'];
				}
				return [$target . ' = ' . $expr . ';'];
			}

			if ($name !== null && !isset($this->declaredLocals[$name])) {
				$this->declaredLocals[$name] = true;
				if ($typed !== null) {
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

		if ($statement->kind === 'expr') {
			return [$this->renderExpr($statement->payload, $namespacePhp) . ';'];
		}

		return ['// Unsupported statement'];
	}


	private function extractSimpleVarName(mixed $expr): ?string
	{
		if (!is_array($expr) || (($expr['kind'] ?? null) !== AstKind::VAR)) {
			return null;
		}
		$name = (string) ($expr['children']['name'] ?? '');
		return $name !== '' ? $name : null;
	}

	private function renderAssignmentTarget(mixed $expr, ?string $namespacePhp): string
	{
		return $this->renderExpr($expr, $namespacePhp);
	}

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
			return match (strtolower(ltrim($name, '\\'))) {
				'true' => 'static_cast<bool_t>(true)',
				'false' => 'static_cast<bool_t>(false)',
				'null' => 'null',
				default => $this->renderConstantName($name, $namespacePhp),
			};
		}
		if ($kind === AstKind::BINARY_OP) {
			$left = $this->renderExpr($expr['children']['left'] ?? null, $namespacePhp);
			$right = $this->renderExpr($expr['children']['right'] ?? null, $namespacePhp);
			return $left . ' + ' . $right;
		}
		if ($kind === AstKind::CAST) {
			$inner = $this->renderExpr($expr['children']['expr'] ?? null, $namespacePhp);
			$flags = (int) ($expr['flags'] ?? 0);
			return match ($flags) {
				AstKind::TYPE_STRING => 'string_t(' . $inner . ')',
				AstKind::TYPE_LONG => 'static_cast<int_t>(' . $inner . ')',
				AstKind::TYPE_DOUBLE => 'static_cast<float_t>(' . $inner . ')',
				AstKind::TYPE_BOOL => 'static_cast<bool_t>(' . $inner . ')',
				default => '/* unsupported-cast */',
			};
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

		return '/* unsupported-expr-kind-' . $kind . ' */';
	}


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
	private function renderArgs(array $args, ?string $namespacePhp): string
	{
		$out = [];
		foreach ($args as $arg) {
			$out[] = $this->renderExpr($arg, $namespacePhp);
		}
		return implode(', ', $out);
	}

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


	private function renderConstantName(string $name, ?string $namespacePhp): string
	{
		$trimmed = ltrim($name, '\\');
		if ($trimmed === '') {
			return '/* unsupported-const */';
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

	private function isNullExpr(mixed $expr): bool
	{
		return is_array($expr)
			&& ($expr['kind'] ?? null) === AstKind::CONST
			&& (($expr['children']['name']['children']['name'] ?? null) === 'null');
	}

	private function indent(int $level): string
	{
		return str_repeat("\t", $level);
	}
}
