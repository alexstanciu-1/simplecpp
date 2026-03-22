<?php
declare(strict_types=1);

namespace Scpp\S2S\Builder;

use Scpp\S2S\IR\ClassDecl;
use Scpp\S2S\IR\FunctionDecl;
use Scpp\S2S\IR\MethodDecl;
use Scpp\S2S\IR\NamespaceBlock;
use Scpp\S2S\IR\ParamDecl;
use Scpp\S2S\IR\PhpFile;
use Scpp\S2S\IR\Statement;
use Scpp\S2S\Loader\ParsedInput;
use Scpp\S2S\Support\AstKind;

final class IrBuilder
{
	/**
	 * @param array<int, array{name:string,type:string,line:int}> $typeComments
	 */
	public function build(ParsedInput $input, array $typeComments): PhpFile
	{
		$root = $input->ast;
		if (!is_array($root) || ($root['kind'] ?? null) !== AstKind::STMT_LIST) {
			throw new \RuntimeException('Unsupported AST root shape.');
		}

		$typeMap = [];
		foreach ($typeComments as $comment) {
			$typeMap[$comment['line'] . ':' . $comment['name']] = $comment['type'];
		}

		$top = $this->collectBlock($root['children'] ?? [], null);

		return new PhpFile(
			path: $input->path,
			namespaces: $top['namespaces'],
			classes: $top['classes'],
			functions: $top['functions'],
			rootStatements: $top['statements'],
			localTypeCommentsByKey: $typeMap,
		);
	}

	/**
	 * @param array<int, mixed> $nodes
	 * @return array{namespaces:list<NamespaceBlock>,classes:list<ClassDecl>,functions:list<FunctionDecl>,statements:list<Statement>}
	 */
	private function collectBlock(array $nodes, ?string $currentNamespace): array
	{
		$rootNamespaces = [];
		$classes = [];
		$functions = [];
		$statements = [];
		$namespaceBuckets = [];
		$activeNamespace = $currentNamespace;

		foreach ($nodes as $node) {
			if (!is_array($node)) {
				continue;
			}

			$kind = $node['kind'] ?? null;
			if ($kind === AstKind::NAMESPACE) {
				$children = $node['children'] ?? [];
				$name = (string) ($children['name'] ?? '');
				$fullName = $this->combineNamespace($currentNamespace, $name);
				$stmtsNode = $children['stmts'] ?? null;

				if (is_array($stmtsNode) && isset($stmtsNode['children'])) {
					$collected = $this->collectBlock($stmtsNode['children'] ?? [], $fullName);
					if ($collected['classes'] !== [] || $collected['functions'] !== [] || $collected['statements'] !== []) {
						$rootNamespaces[] = new NamespaceBlock(
							name: $fullName,
							classes: $collected['classes'],
							functions: $collected['functions'],
							statements: $collected['statements'],
						);
					}
					foreach ($collected['namespaces'] as $nested) {
						$rootNamespaces[] = $nested;
					}
				} else {
					$activeNamespace = $fullName;
				}
				continue;
			}

			if ($activeNamespace !== null) {
				$bucket = $namespaceBuckets[$activeNamespace] ?? [
					'classes' => [],
					'functions' => [],
					'statements' => [],
				];
				if ($kind === AstKind::CLASS_) {
					$bucket['classes'][] = $this->buildClass($node);
				} elseif ($kind === AstKind::FUNC_DECL) {
					$bucket['functions'][] = $this->buildFunction($node);
				} else {
					$statement = $this->buildStatement($node);
					if ($statement !== null) {
						$bucket['statements'][] = $statement;
					}
				}
				$namespaceBuckets[$activeNamespace] = $bucket;
				continue;
			}

			if ($kind === AstKind::CLASS_) {
				$classes[] = $this->buildClass($node);
				continue;
			}

			if ($kind === AstKind::FUNC_DECL) {
				$functions[] = $this->buildFunction($node);
				continue;
			}

			$statement = $this->buildStatement($node);
			if ($statement !== null) {
				$statements[] = $statement;
			}
		}

		foreach ($namespaceBuckets as $name => $bucket) {
			$rootNamespaces[] = new NamespaceBlock(
				name: $name,
				classes: $bucket['classes'],
				functions: $bucket['functions'],
				statements: $bucket['statements'],
			);
		}

		return [
			'namespaces' => $rootNamespaces,
			'classes' => $classes,
			'functions' => $functions,
			'statements' => $statements,
		];
	}

	private function combineNamespace(?string $prefix, string $name): string
	{
		if ($prefix === null || $prefix === '') {
			return $name;
		}
		if ($name === '') {
			return $prefix;
		}
		return $prefix . '\\' . $name;
	}

	private function buildClass(array $node): ClassDecl
	{
		$children = $node['children'] ?? [];
		$methods = [];
		foreach (($children['stmts']['children'] ?? []) as $member) {
			if (is_array($member) && ($member['kind'] ?? null) === AstKind::METHOD) {
				$methods[] = $this->buildMethod($member);
			}
		}

		return new ClassDecl(
			name: (string) ($children['name'] ?? 'Anonymous'),
			methods: $methods,
		);
	}

	private function buildFunction(array $node): FunctionDecl
	{
		$children = $node['children'] ?? [];

		return new FunctionDecl(
			name: (string) ($children['name'] ?? ''),
			params: $this->buildParams($children['params']['children'] ?? []),
			returnType: $this->readTypeName($children['returnType'] ?? null),
			returnsByReference: (($node['flags'] ?? 0) & AstKind::RETURN_REF) !== 0,
			statements: $this->buildStatements($children['stmts']['children'] ?? []),
		);
	}

	private function buildMethod(array $node): MethodDecl
	{
		$children = $node['children'] ?? [];

		return new MethodDecl(
			name: (string) ($children['name'] ?? ''),
			params: $this->buildParams($children['params']['children'] ?? []),
			returnType: $this->readTypeName($children['returnType'] ?? null),
			returnsByReference: (($node['flags'] ?? 0) & AstKind::RETURN_REF) !== 0,
			isStatic: (($node['flags'] ?? 0) & AstKind::STATIC) !== 0,
			statements: $this->buildStatements($children['stmts']['children'] ?? []),
		);
	}

	/** @param array<int, mixed> $nodes @return list<ParamDecl> */
	private function buildParams(array $nodes): array
	{
		$params = [];
		foreach ($nodes as $node) {
			if (!is_array($node) || ($node['kind'] ?? null) !== AstKind::PARAM) {
				continue;
			}
			$children = $node['children'] ?? [];
			$params[] = new ParamDecl(
				name: (string) ($children['name'] ?? ''),
				type: $this->readTypeName($children['type'] ?? null),
				isReference: (($node['flags'] ?? 0) & AstKind::PARAM_REF) !== 0,
			);
		}
		return $params;
	}

	/** @param array<int, mixed> $nodes @return list<Statement> */
	private function buildStatements(array $nodes): array
	{
		$out = [];
		foreach ($nodes as $node) {
			$stmt = $this->buildStatement($node);
			if ($stmt !== null) {
				$out[] = $stmt;
			}
		}
		return $out;
	}

	private function buildStatement(array $node): ?Statement
	{
		$kind = $node['kind'] ?? null;
		$line = (int) ($node['lineno'] ?? 0);

		if ($kind === AstKind::ASSIGN) {
			return new Statement('assign', $node['children'] ?? [], $line);
		}

		if ($kind === AstKind::STATIC_VAR) {
			return new Statement('static_var', $node['children'] ?? [], $line);
		}

		if ($kind === AstKind::RETURN) {
			return new Statement('return', $node['children']['expr'] ?? null, $line);
		}

		if ($kind === AstKind::CALL || $kind === AstKind::STATIC_CALL) {
			return new Statement('expr', $node, $line);
		}

		return null;
	}

	private function readTypeName(mixed $typeNode): ?string
	{
		if (!is_array($typeNode)) {
			return null;
		}

		$flags = (int) ($typeNode['flags'] ?? 0);
		return match ($flags) {
			AstKind::TYPE_LONG => 'int',
			AstKind::TYPE_DOUBLE => 'float',
			AstKind::TYPE_STRING => 'string',
			AstKind::TYPE_VOID => 'void',
			AstKind::TYPE_BOOL => 'bool',
			default => null,
		};
	}
}
