<?php
declare(strict_types=1);

namespace Scpp\S2S\Support;

require_once __DIR__ . '/AstKindPhp84.php';
require_once __DIR__ . '/AstKindPhp85.php';

/**
 * Selects the php-ast numeric kind/flag table that matches the active PHP minor.
 *
 * Prism++ intentionally treats these numeric values as version-specific.
 * Supported runtime families are PHP 8.4.x and PHP 8.5.x only.
 */
if (PHP_VERSION_ID >= 80400 && PHP_VERSION_ID < 80500) {
	final class AstKind extends AstKindPhp84
	{
	}
} elseif (PHP_VERSION_ID >= 80500 && PHP_VERSION_ID < 80600) {
	final class AstKind extends AstKindPhp85
	{
	}
} else {
	throw new \RuntimeException('Unsupported PHP version for AST numeric mapping: ' . PHP_VERSION . ' (expected 8.4.x or 8.5.x)');
}

if (extension_loaded('ast')) {
	$version = max(\ast\get_supported_versions());
	$probe = <<<'PHP'
<?php
function __scpp_ast_probe($a, $b) {
	return $a + $b;
}
echo __scpp_ast_probe(1, 2), "\n";
PHP;
	$root = \ast\parse_code($probe, $version);
	$children = is_object($root) && is_array($root->children ?? null) ? $root->children : [];
	$funcNode = $children[0] ?? null;
	$returnNode = is_object($funcNode) ? (($funcNode->children['stmts']->children[0] ?? null) ?: null) : null;
	$echoNodeA = $children[1] ?? null;
	$echoNodeB = $children[2] ?? null;

	$checks = [
		'STMT_LIST' => [$root->kind ?? null, AstKind::STMT_LIST],
		'FUNC_DECL' => [is_object($funcNode) ? ($funcNode->kind ?? null) : null, AstKind::FUNC_DECL],
		'RETURN' => [is_object($returnNode) ? ($returnNode->kind ?? null) : null, AstKind::RETURN],
		'AST_ECHO#1' => [is_object($echoNodeA) ? ($echoNodeA->kind ?? null) : null, AstKind::AST_ECHO],
		'AST_ECHO#2' => [is_object($echoNodeB) ? ($echoNodeB->kind ?? null) : null, AstKind::AST_ECHO],
	];

	$failures = [];
	foreach ($checks as $label => [$actual, $expected]) {
		if ($actual !== $expected) {
			$failures[] = $label . ': expected ' . (string) $expected . ', got ' . var_export($actual, true);
		}
	}

	if ($failures !== []) {
		throw new \RuntimeException(
			'php-ast numeric mapping mismatch for PHP ' . PHP_VERSION . ' and AST version ' . $version . '. ' .
			'Run Prism++ with an explicit supported PHP binary. Details: ' . implode('; ', $failures)
		);
	}
}
