<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

/** Parse either fresh syntax or an edit while preserving the existing collection owner. */
function indexing_parse(string $text, ?parsed_file $previous = null): parsed_file
{
	$input = new file();
	$input->path = 'indexing.phs';
	$input->content = $text;
	return (new Parser((new Tokenizer($input))->tokenize(), null, $previous))->parse();
}

/** Exercise the shared semantic pipeline; no successful bracket lowering is claimed. */
function indexing_prepare(string $text): void
{
	Compiler_Lifecycle::reset();
	$syntax = indexing_parse($text);
	$module = new module('.', Source_Registry::normalize('.'), '.');
	Model::$modules->add($module->name, $module);
	$record = Source_Registry::add($module, $syntax->source_file());
	Source_Publication::publish_parsed($record, $syntax);
	$compiler = new Compiler();
	$compiler->prepare();
	$compiler->cpp();
}

foreach (['$base[]', '$base[1 + 2 * 3]', '$base[$x = 2]', '$base[f()]',
	'$base[true]', '$base[null]', '$base["key"]', 'f()[]', '"text"[0]', 'true[]', '[1, 2][]',
	'($a + $b)[]', '$base[][f()][0]'] as $source)
{
	$syntax = indexing_parse('return ' . $source . ';');
	$node = $syntax->root->body->statements[0]->expression;
	if (!($node instanceof index_node)) {
		throw new \LogicException('Bracket suffix was not parsed uniformly: ' . $source);
	}
	$children = iterator_to_array($node->children());
	if (count($children) !== ($node->index === null ? 1 : 2) || $children[0] !== $node->base) {
		throw new \LogicException('Bracket arity or child order was lost');
	}
	Preparation_Cleanup::tree($syntax->root);
}
$syntax = indexing_parse('return $base[1 + 2 * 3];');
$index = $syntax->root->body->statements[0]->expression->index;
if (!($index instanceof binary_expression_node) || !($index->right instanceof binary_expression_node)) {
	throw new \LogicException('Bracket argument lost ordinary expression precedence');
}
foreach (['return $base[;','return $base[1, 2];','return $base[1;'] as $source)
{
	$failed = false;
	try {
		indexing_parse($source);
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	if (!$failed) {
		throw new \LogicException('Malformed bracket arguments were accepted');
	}
}

foreach (['[]' => 0, '[1 + 2]' => 1] as $suffix => $arity)
{
	foreach (['return $s' . $suffix . ';', '$s' . $suffix . ' = "x";',
		'$s' . $suffix . ' += 1;', '$s' . $suffix . '++;', '$out = $s' . $suffix . ';'] as $use)
	{
		$failed = false;
		try {
			indexing_prepare('$s = "abc"; ' . $use);
		}
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), 'string operator[] with ' . $arity . ' argument(s) is deferred');
		}
		if (!$failed || !Model::$cpp_files->is_empty() || !Model::$prepared_files->is_empty()) {
			throw new \LogicException('Bracket use bypassed shared overload preparation: ' . $use);
		}
	}
}
foreach ([
	['return 1[];', 'operator[] with 0 argument(s) overload resolution is deferred'],
	['return 1[true];', 'operator[] with 1 argument(s) overload resolution is deferred'],
	['function f(): string { return "x"; } return f()[];', 'string operator[] with 0 argument(s) is deferred'],
	['return $missing[];', 'established local declaration for missing'],
	['$s = "x"; return $s[$missing];', 'established local declaration for missing'],
	['$s = "x"; $s[$new = 1] = 2;', 'established local declaration for new'],
] as [$source, $diagnostic])
{
	$failed = false;
	try {
		indexing_prepare($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Missing operator[] diagnostic: ' . $source);
	}
}

// Parser-only reuse/compaction proves optional children without inventing overload facts.
$source = 'function f(string $s): string { return $s[][1 + 2]; }';
$parsed = indexing_parse($source);
$function = $parsed->root_scope()->functions_named('f')[0]->syntax();
$body = $function->body;
$node = $body->statements[0]->expression;
$occurrence = $node->base->base->occurrence();
$parsed = indexing_parse('function before(): int { return 0; } ' . $source, $parsed);
Token_Cleanup::file($parsed);
if (($function->body !== $body) || ($node->base->index !== null)
	|| ($node->base->base->occurrence() !== $occurrence)
	|| ($parsed->tokens->text_at($node->start_token()) !== '$s')) {
	throw new \LogicException('Compaction lost bracket arity, provenance or retained identity');
}
$parsed = indexing_parse(str_replace('$s[][1 + 2]', '$s[0][]', $source), $parsed);
$node = $function->body->statements[0]->expression;
if (($node->index !== null) || !($node->base->index instanceof integer_literal_node)) {
	throw new \LogicException('Bracket arity edit retained stale syntax');
}

$directory = sys_get_temp_dir() . '/scpp_indexing_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$valid = '$s = "abc"; return 0;';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$expected = Model::$cpp_files[0]->text;
	foreach (['return $s[];', 'return $s[0];'] as $use)
	{
		file_put_contents($path, '$s = "abc"; ' . $use);
		$failed = false;
		try {
			$compiler->update_cpp([$path]);
		}
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), 'string operator[]');
		}
		if (!$failed || !Model::$cpp_files->is_empty()) {
			throw new \LogicException('Unresolved brackets published stale C++');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		if (Model::$cpp_files[0]->text !== $expected) {
			throw new \LogicException('Bracket failure recovery changed valid output');
		}
	}
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Indexing: both arities, expression arguments, shared rejection, compaction and recovery passed\n";
