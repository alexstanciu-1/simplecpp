<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

/** Parse without preparation so grammar decisions cannot depend on resolved names. */
function grouping_parse(string $text): parsed_file
{
	Compiler_Lifecycle::reset();
	$input = new file();
	$input->path = 'grouping.phs';
	$input->content = $text;
	return (new Parser((new Tokenizer($input))->tokenize()))->parse();
}

function grouping_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Inspect exact half-open provenance independently of expression values. */
function grouping_span(ast_node $node, parsed_file $syntax): string
{
	$parts = [];
	for ($index = $node->start_token(); $index < $node->end_token(); $index++) {
		$parts[] = $syntax->tokens->text_at($index);
	}
	return implode(' ', $parts);
}

// Equal arithmetic results cannot establish grouping: inspect both tree shapes.
$syntax = grouping_parse('return 1 + (2 + 3);');
$right_group = $syntax->root->body->statements[0]->expression;
grouping_check(($right_group instanceof binary_expression_node)
	&& ($right_group->left instanceof integer_literal_node)
	&& ($right_group->right instanceof binary_expression_node), 'Right grouping lost its tree shape');
grouping_check(grouping_span($right_group->right, $syntax) === '2 + 3', 'Grouping rewrote the inner binary span');
grouping_check(grouping_span($right_group, $syntax) === '1 + ( 2 + 3 )', 'Outer binary lost consumed grouping');

$syntax = grouping_parse('return ((1 + 2)) + 3;');
$left_group = $syntax->root->body->statements[0]->expression;
grouping_check(($left_group->left instanceof binary_expression_node)
	&& ($left_group->right instanceof integer_literal_node), 'Left grouping lost its tree shape');
grouping_check(grouping_span($left_group->left, $syntax) === '1 + 2', 'Nested grouping rewrote the inner span');
grouping_check(grouping_span($left_group, $syntax) === '( ( 1 + 2 ) ) + 3', 'Outer binary lost its opening parentheses');

// Names in grouping collect exactly one value occurrence, never a speculative type.
$syntax = grouping_parse('return ((PHP_INT_MAX));');
$constant = $syntax->root->body->statements[0]->expression;
grouping_check($constant instanceof constant_reference_node, 'Parenthesized constant became a cast');
grouping_check((count($syntax->collection->entries) === 1)
	&& ($syntax->collection->entries[0] === $constant->occurrence()), 'Lookahead collected a speculative occurrence');
grouping_check(grouping_span($constant, $syntax) === 'PHP_INT_MAX', 'Constant lost its own source span');

$syntax = grouping_parse('return (Unknown) + 1;');
grouping_check($syntax->root->body->statements[0]->expression->left instanceof constant_reference_node,
	'Name grouping depended on name resolution');
$syntax = grouping_parse('return (Unknown)$x;');
grouping_check($syntax->root->body->statements[0]->expression instanceof cast_expression_node,
	'Unknown cast target did not remain a semantic lookup');
$syntax = grouping_parse('return (Unknown)(1);');
grouping_check($syntax->root->body->statements[0]->expression instanceof cast_expression_node,
	'Parenthesized cast operand lost cast priority');
$syntax = grouping_parse('return (Unknown)[1];');
grouping_check($syntax->root->body->statements[0]->expression instanceof cast_expression_node,
	'Array cast operand lost cast priority');
$syntax = grouping_parse('return (hash<string, vector<int>>)$x;');
grouping_check($syntax->root->body->statements[0]->expression->target_type instanceof template_application_type_node,
	'Cast lookahead lost nested type applications');
$syntax = grouping_parse('return (value<vector<int>>)$x;');
grouping_check($syntax->root->body->statements[0]->expression->target_type instanceof type_use_modifier_node,
	'Cast lookahead lost type-use modifiers');

$syntax = grouping_parse('return (int)(1 + 2);');
$cast = $syntax->root->body->statements[0]->expression;
grouping_check(($cast instanceof cast_expression_node) && ($cast->operand instanceof binary_expression_node),
	'Cast did not own its grouped operand');
grouping_check(grouping_span($cast, $syntax) === '( int ) ( 1 + 2 )', 'Cast lost grouped operand provenance');
$syntax = grouping_parse('return ((int)$x) + 2;');
grouping_check($syntax->root->body->statements[0]->expression->left instanceof cast_expression_node,
	'Grouping around a cast changed its precedence');

// Postfix access owns the consumed group; its base still describes only the inner value.
$syntax = grouping_parse('return (($box))->value;');
$field = $syntax->root->body->statements[0]->expression;
grouping_check(($field instanceof field_access_node) && ($field->base instanceof variable_reference_node),
	'Grouped member base acquired an extra node');
grouping_check(grouping_span($field, $syntax) === '( ( $box ) ) -> value', 'Member span lost grouped base');
grouping_check(grouping_span($field->base, $syntax) === '$box', 'Member base lost variable provenance');
$syntax = grouping_parse('return ($items)[(0)];');
$index = $syntax->root->body->statements[0]->expression;
grouping_check(($index instanceof index_node) && ($index->index instanceof integer_literal_node),
	'Grouping changed index syntax');
grouping_check(grouping_span($index, $syntax) === '( $items ) [ ( 0 ) ]', 'Index span lost grouped base');

foreach (['return ();', 'return (1 + 2;', 'return (1, 2);', 'return (vector<>)$x;'] as $source)
{
	$failed = false;
	try {
		grouping_parse($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'grouping.phs: byte');
	}
	grouping_check($failed, 'Invalid or out-of-slice grouping accepted: ' . $source);
}

// Retained grouping survives token movement/cleanup, then a regrouping matches fresh output.
$directory = sys_get_temp_dir() . '/scpp_grouping_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$initial = 'function value(): int { return 1 + (2 + 3); } return (value());';
try
{
	file_put_contents($path, $initial);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$function = Model::$global_scope->functions_named('value')[0]->syntax();
	$body = $function->body;
	$expression = $body->statements[0]->expression;
	$changed = 'function before(): int { return 0; } ' . $initial;
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	grouping_check($function->body === $body, 'Token movement replaced an unchanged grouped body');
	$parsed = Model::syntax_files()[0];
	grouping_check(grouping_span($expression, $parsed) === '1 + ( 2 + 3 )', 'Cleanup damaged grouped provenance');
	$changed = str_replace('1 + (2 + 3)', '(1 + 2) + 3', $changed);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	grouping_check($function->body !== $body, 'Regrouping retained stale body syntax');
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	grouping_check(Model::$cpp_files[0]->text === $incremental, 'Regrouping differs between fresh and incremental generation');
}
finally {
	unlink($path);
	rmdir($directory);
}

echo "Grouping: syntax-only disambiguation, AST shape, spans, collection and incremental cleanup passed\n";
