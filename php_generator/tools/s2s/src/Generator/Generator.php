<?php
declare(strict_types=1);

namespace Scpp\S2S\Generator;

use Scpp\S2S\Emit\CppFile;
use Scpp\S2S\IR\ClassDecl;
use Scpp\S2S\IR\FunctionDecl;
use Scpp\S2S\IR\MethodDecl;
use Scpp\S2S\IR\NamespaceBlock;
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

	public function __construct(
		private readonly TypeMapper $typeMapper = new TypeMapper(),
	) {
	}

	public function generate(PhpFile $file): CppFile
	{
		$this->declaredLocals = [];
		$this->errors = [];
		$this->localTypeComments = $file->localTypeCommentsByKey;

		$baseName = pathinfo($file->path, PATHINFO_FILENAME);
		$header = ['#pragma once', '', '#include <scpp/runtime.hpp>', ''];
		$source = ['#include "' . $baseName . '.hpp"', ''];

		$hasRootNamespaceContent = ($file->classes !== [] || $file->functions !== [] || $file->rootStatements !== []);
		$rootMainName = $file->rootStatements !== [] ? 'main' : null;
		if ($hasRootNamespaceContent) {
			$this->emitNamespaceBlock($header, $source, 'scpp', $file->classes, $file->functions, $file->rootStatements, $rootMainName);
		}

		$namespaceMainTargets = [];
		foreach ($file->namespaces as $namespace) {
			$mainName = $namespace->statements !== [] ? '__scpp_main' : null;
			$this->emitNamespaceBlock(
				$header,
				$source,
				'scpp::' . str_replace('\\', '::', $namespace->name),
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
	private function emitNamespaceBlock(array &$header, array &$source, string $namespaceCpp, array $classes, array $functions, array $statements, ?string $syntheticMainName = null): void
	{
		$header[] = 'namespace ' . $namespaceCpp . ' {';
		$header[] = '';
		$source[] = 'namespace ' . $namespaceCpp . ' {';
		$source[] = '';

		foreach ($classes as $class) {
			$this->emitClass($header, $source, $class, $namespaceCpp);
		}
		foreach ($functions as $function) {
			$this->emitFunction($header, $source, $function, $namespaceCpp);
		}
		if ($syntheticMainName !== null) {
			$this->emitNamespaceMain($header, $source, $syntheticMainName, $statements, $namespaceCpp);
		}

		$header[] = '}';
		$header[] = '';
		$source[] = '}';
		$source[] = '';
	}

	private function emitClass(array &$header, array &$source, ClassDecl $class, ?string $namespaceCpp): void
	{
		$header[] = 'class ' . $class->name . ' {';
		$header[] = 'public:';
		foreach ($class->methods as $method) {
			$header[] = $this->indent(1) . $this->renderMethodDeclaration($method) . ';';
		}
		$header[] = '};';
		$header[] = '';

		foreach ($class->methods as $method) {
			$source[] = $this->renderMethodDefinition($class->name, $method, $namespaceCpp);
			$source[] = '';
		}
	}

	private function emitFunction(array &$header, array &$source, FunctionDecl $function, ?string $namespaceCpp): void
	{
		$header[] = $this->renderFunctionDeclaration($function) . ';';
		$header[] = '';
		$source[] = $this->renderFunctionDefinition($function, $namespaceCpp);
		$source[] = '';
	}

	private function emitNamespaceMain(array &$header, array &$source, string $name, array $statements, ?string $namespaceCpp): void
	{
		$header[] = 'int ' . $name . '();';
		$header[] = '';
		$source[] = 'int ' . $name . '() {';
		$this->declaredLocals = [];
		foreach ($statements as $statement) {
			foreach ($this->renderStatement($statement, $namespaceCpp) as $line) {
				$source[] = $this->indent(1) . $line;
			}
		}
		$source[] = $this->indent(1) . 'return 0;';
		$source[] = '}';
	}


	private function renderMethodDeclaration(MethodDecl $method): string
	{
		$prefix = $method->isStatic ? 'static ' : '';
		$returnType = $this->typeMapper->mapReturnType($method->returnType, $method->returnsByReference);
		return $prefix . $returnType . ' ' . $method->name . '(' . $this->renderParams($method->params) . ')';
	}

	private function renderMethodDefinition(string $className, MethodDecl $method, ?string $namespaceCpp): string
	{
		$this->declaredLocals = [];
		foreach ($method->params as $param) {
			$this->declaredLocals[$param->name] = true;
		}
		$returnType = $this->typeMapper->mapReturnType($method->returnType, $method->returnsByReference);
		$signature = $returnType . ' ' . $className . '::' . $method->name . '(' . $this->renderParams($method->params) . ')';
		return $signature . " {\n" . $this->renderBody($method->statements, $namespaceCpp) . "\n}";
	}

	private function renderFunctionDeclaration(FunctionDecl $function): string
	{
		$returnType = $this->typeMapper->mapReturnType($function->returnType, $function->returnsByReference);
		return $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params) . ')';
	}

	private function renderFunctionDefinition(FunctionDecl $function, ?string $namespaceCpp): string
	{
		$this->declaredLocals = [];
		foreach ($function->params as $param) {
			$this->declaredLocals[$param->name] = true;
		}
		$returnType = $this->typeMapper->mapReturnType($function->returnType, $function->returnsByReference);
		$signature = $returnType . ' ' . $function->name . '(' . $this->renderParams($function->params) . ')';
		return $signature . " {\n" . $this->renderBody($function->statements, $namespaceCpp) . "\n}";
	}

	private function renderParams(array $params): string
	{
		$out = [];
		foreach ($params as $param) {
			$out[] = $this->typeMapper->mapParamType($param->type, $param->isReference) . ' ' . $param->name;
		}
		return implode(', ', $out);
	}

	private function renderBody(array $statements, ?string $namespaceCpp): string
	{
		$lines = [];
		foreach ($statements as $statement) {
			foreach ($this->renderStatement($statement, $namespaceCpp) as $line) {
				$lines[] = $this->indent(1) . $line;
			}
		}
		return implode("\n", $lines);
	}

	private function renderStatement(Statement $statement, ?string $namespaceCpp): array
	{
		if ($statement->kind === 'assign') {
			$varNode = $statement->payload['var'] ?? null;
			$exprNode = $statement->payload['expr'] ?? null;
			$name = (string) (($varNode['children']['name'] ?? '') ?: 'tmp');
			$key = $statement->line . ':' . $name;
			$typed = $this->localTypeComments[$key] ?? null;
			$expr = $this->renderExpr($exprNode, $namespaceCpp);
			if ($exprNode !== null && $this->isNullExpr($exprNode) && $typed === null && !isset($this->declaredLocals[$name])) {
				$this->errors[] = 'Untyped null assignment is rejected at line ' . $statement->line . '.';
				return ['// ERROR: untyped null assignment rejected'];
			}
			if (!isset($this->declaredLocals[$name])) {
				$this->declaredLocals[$name] = true;
				if ($typed !== null) {
					return [$this->typeMapper->mapTypedLocalType($typed) . ' ' . $name . ' = ' . $expr . ';'];
				}
				return ['auto ' . $name . ' = ' . $expr . ';'];
			}
			return [$name . ' = ' . $expr . ';'];
		}

		if ($statement->kind === 'static_var') {
			$varNode = $statement->payload['var'] ?? null;
			$name = (string) (($varNode['children']['name'] ?? '') ?: 'tmp');
			$default = $this->renderExpr($statement->payload['default'] ?? null, $namespaceCpp);
			$this->declaredLocals[$name] = true;
			return ['static int_t ' . $name . ' = ' . $default . ';'];
		}

		if ($statement->kind === 'return') {
			return ['return ' . $this->renderExpr($statement->payload, $namespaceCpp) . ';'];
		}

		if ($statement->kind === 'expr') {
			return [$this->renderExpr($statement->payload, $namespaceCpp) . ';'];
		}

		return ['// Unsupported statement'];
	}

	private function renderExpr(mixed $expr, ?string $namespaceCpp): string
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
			return (string) ($expr['children']['name'] ?? 'var');
		}
		if ($kind === AstKind::CONST) {
			$name = (string) ($expr['children']['name']['children']['name'] ?? '');
			return match ($name) {
				'true' => 'static_cast<bool_t>(true)',
				'false' => 'static_cast<bool_t>(false)',
				'null' => 'null',
				'PHP_INT_MAX' => 'PHP_INT_MAX',
				default => 'php::constant(' . $name . ')',
			};
		}
		if ($kind === AstKind::BINARY_OP) {
			$left = $this->renderExpr($expr['children']['left'] ?? null, $namespaceCpp);
			$right = $this->renderExpr($expr['children']['right'] ?? null, $namespaceCpp);
			return $left . ' + ' . $right;
		}
		if ($kind === AstKind::NEW) {
			$class = $this->renderClassName($expr['children']['class'] ?? null, $namespaceCpp);
			return 'create<' . $class . '>(' . $this->renderArgs($expr['children']['args']['children'] ?? [], $namespaceCpp) . ')';
		}
		if ($kind === AstKind::STATIC_CALL) {
			$classNode = $expr['children']['class'] ?? null;
			$method = (string) ($expr['children']['method'] ?? '');
			$class = is_array($classNode) && ($classNode['kind'] ?? null) === AstKind::VAR
				? 'decltype(' . $this->renderExpr($classNode, $namespaceCpp) . ')'
				: $this->renderClassName($classNode, $namespaceCpp);
			return $class . '::' . $method . '(' . $this->renderArgs($expr['children']['args']['children'] ?? [], $namespaceCpp) . ')';
		}
		if ($kind === AstKind::CALL) {
			$name = (string) (($expr['children']['expr']['children']['name'] ?? '') ?: 'call');
			return $name . '(' . $this->renderArgs($expr['children']['args']['children'] ?? [], $namespaceCpp) . ')';
		}

		return '/* unsupported-expr-kind-' . $kind . ' */';
	}

	private function renderArgs(array $args, ?string $namespaceCpp): string
	{
		$out = [];
		foreach ($args as $arg) {
			$out[] = $this->renderExpr($arg, $namespaceCpp);
		}
		return implode(', ', $out);
	}

	private function renderClassName(mixed $node, ?string $namespaceCpp): string
	{
		if (!is_array($node)) {
			return 'Unknown';
		}
		$name = (string) ($node['children']['name'] ?? 'Unknown');
		$name = ltrim($name, '\\');
		$flags = (int) ($node['flags'] ?? 0);
		if ($flags === 0 && str_contains($name, '\\')) {
			return '::scpp::' . str_replace('\\', '::', $name);
		}
		if ($namespaceCpp !== null && !str_contains($name, '\\')) {
			return $name;
		}
		return str_replace('\\', '::', $name);
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
