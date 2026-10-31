<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';

use scpp\compiler\{Source_Frontend, Syntax_Inspection, ast_node, ast_file_node, ast_function_declaration,
	ast_function_body, ast_name_expression, diagnostic, entry_target, flavor, source_file};

function verify(bool $condition, string $message): void
{
	if (!$condition) { throw new RuntimeException($message); }
}

/** Follow owning syntax fields only, excluding semantic references and collection indexes. */
function check_tree(ast_node $node, ast_file_node $root): void
{
	verify($node->root() === $root && $node->source() === $root->tokens->source, 'Root/source identity lost');
	verify($node->start <= $node->end && $node->end <= count($root->tokens->tokens), 'Invalid token bounds');
	verify(!property_exists($node, 'span') && !property_exists($node, 'source'), 'Duplicated source location');
	$children = [];
	if ($node instanceof ast_file_node) { $children = array_values($node->declarations); }
	elseif ($node instanceof ast_function_declaration) { $children = [$node->body()]; }
	elseif ($node instanceof ast_function_body) { $children = $node->statements; }
	else {
		foreach (get_object_vars($node) as $name => $value) {
			if ($name !== 'parent' && $value instanceof ast_node) { $children[] = $value; }
		}
	}
	foreach ($children as $child) {
		verify($child->parent === $node, 'Parent is not the syntax owner');
		check_tree($child, $root);
	}
}

$frontend = new Source_Frontend();
foreach ([
	[flavor::cpp, 'void ping() { int x = 8; echo x; return; }'],
	[flavor::php, 'function ping(): void { $x int = 8; echo $x; return; }'],
	[flavor::js, 'function ping(): void { let x: int = 8; echo x; return; }'],
] as [$flavor, $definition])
{
	$text = "/* leading é */\r\n  " . $definition . "\r\n  ping /* interior */ (); echo \"é\\0\"; return 8; // trailing\r\n";
	$source = new source_file('locations', $text, $flavor);
	$file = $frontend->parse($source);
	$root = $file;
	check_tree($root, $root);
	verify($root->parent === null, 'Root must have null parent');
	$function = $root->declarations['func:ping'];
	verify($function->start === 0 && $function->byte_start() === strpos($text, $definition), 'Token position confused with byte position');
	verify($function->source_text() === $definition, 'Function range does not include the complete body');
	$call = $root->main()->body()->statements[0];
	verify($call->source_text() === 'ping /* interior */ ();', 'Interior trivia lost');
	verify($root->main()->is_implicit_entry && $root->main()->name === 'main', 'Implicit entry identity');
	verify($root->main()->return_type_name === 'int', 'Implicit entry type spelling');
	$view = Syntax_Inspection::file($file);
	verify($view['statements'][0]['span']['start'] === strpos($text, 'ping /*'), 'CLI byte location changed');
	$eof = new ast_name_expression('synthetic', $root->end, $root->end);
	$eof->attach($root);
	verify($eof->byte_start() === strlen($text) && $eof->byte_end() === strlen($text), 'EOF anchor ignores trailing trivia');
	try { $call->attach($root); throw new RuntimeException('Parent reassignment accepted'); }
	catch (LogicException $expected) {}
	// A new source generation cannot change source access through a retained old tree.
	$frontend->parse(new source_file('locations', 'return 0;', $flavor));
	verify($call->source() === $source && $call->source_text() === 'ping /* interior */ ();', 'Source generation replaced');
}
$a = new ast_name_expression('a', 0, 0);
$b = new ast_name_expression('b', 0, 0);
$a->attach($b);
try { $b->attach($a); throw new RuntimeException('Ownership cycle accepted'); }
catch (LogicException $expected) {}
$empty = $frontend->parse(new source_file('empty', " // only trivia\r\n", flavor::cpp));
verify($empty->start === 0 && $empty->end === 0 && $empty->byte_start() === strlen($empty->tokens->source->content), 'Empty source anchor');
foreach ([
	["/* é */\r\n echo missing; return 8;", 'missing'],
	["/* é */\r\n echo \"bad\\q\"; return 8;", '\\q'],
	["/* é */\r\n void ping() {} void ping() {} return 8;", 'ping() {} return'],
	["/* é */\r\n int x = 8; // no return\r\n", null],
	["/* é */\r\n return", null],
] as [$text, $needle]) {
	try {
		$frontend->prepare(new source_file('diagnostic', $text, flavor::cpp), new entry_target(31));
		throw new RuntimeException('Expected diagnostic');
	}
	catch (diagnostic $error) {
		$offset = $needle === null ? strlen($text) : strpos($text, $needle);
		verify($error->offset === $offset, 'Diagnostic no longer points to original byte: ' . $error->getMessage());
		$location = $error->source->location($offset);
		verify(str_starts_with($error->getMessage(), 'diagnostic:' . $location['line'] . ':' . $location['column'] . ':'), 'Line/column changed');
	}
}
echo "AST token ranges, ownership, trivia, EOF, source generations and byte diagnostics passed\n";
