<?php
declare(strict_types=1);

namespace Scpp\S2S\Support;

/**
 * Parses standalone PHP expressions used in annotation metadata into php-ast nodes.
 *
 * Current implementation requires ext-ast because annotation expressions do not have
 * sidecar JSON fixtures. This keeps annotation lowering aligned with the normal
 * expression pipeline instead of maintaining a second ad-hoc parser.
 */
final class AnnotationExpressionParser
{
	/** @var array<string, mixed> */
	private array $cache = [];

	public function parse(string $expression): mixed
	{
		$expression = trim($expression);
		if ($expression === '') {
			throw new InputException('Empty annotation expression.');
		}
		if (array_key_exists($expression, $this->cache)) {
			return $this->cache[$expression];
		}
		if (!extension_loaded('ast')) {
			throw new InputException('Annotation expressions require the php-ast extension because they are parsed as real PHP code. Install ext-ast or provide an environment where ext-ast is enabled.');
		}

		$wrapped = "<?php\nfunction __scpp_annotation_expr__() {\n\treturn " . $expression . ";\n}\n";
		try {
			$version = max(\ast\get_supported_versions());
			$ast = \ast\parse_code($wrapped, $version);
		} catch (\Throwable $e) {
			throw new InputException('Failed to parse annotation expression as PHP: ' . $expression . '. ' . $e->getMessage(), 0, $e);
		}

		$exprAst = $this->extractReturnedExpression($ast, $expression);
		$this->cache[$expression] = $exprAst;
		return $exprAst;
	}

	private function extractReturnedExpression(mixed $ast, string $expression): mixed
	{
		if (!is_object($ast) || ($ast->kind ?? null) !== AstKind::STMT_LIST) {
			throw new InputException('Unexpected AST root while parsing annotation expression: ' . $expression);
		}
		$children = is_array($ast->children ?? null) ? $ast->children : [];
		$func = $children[0] ?? null;
		if (!is_object($func) || ($func->kind ?? null) !== AstKind::FUNC_DECL) {
			throw new InputException('Could not locate synthetic function wrapper for annotation expression: ' . $expression);
		}
		$stmts = $func->children['stmts'] ?? null;
		$stmtList = is_object($stmts) && is_array($stmts->children ?? null) ? $stmts->children : [];
		$returnStmt = $stmtList[0] ?? null;
		if (!is_object($returnStmt) || ($returnStmt->kind ?? null) !== AstKind::RETURN) {
			throw new InputException('Could not locate synthetic return wrapper for annotation expression: ' . $expression);
		}
		return $returnStmt->children['expr'] ?? null;
	}
}
