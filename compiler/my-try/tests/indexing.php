<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Parse either fresh syntax or an edit while preserving the existing collection owner. */
function indexing_parse(string $text, ?parsed_file $previous = null): parsed_file
{
	$input = new file();
	$input->path = 'indexing.phs';
	$input->content = $text;
	return (new Parser((new Tokenizer($input))->tokenize(), null, $previous))->parse();
}

/** Exercise shared preparation and emission; invalid reads must never publish output. */
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
	$node = $syntax->root->single_body()->body->statements[0]->expression;
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
$index = $syntax->root->single_body()->body->statements[0]->expression->index;
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
	['$v vector<int> = [1]; $x = $v[true];', 'indexed access keys require'],
	['$v vector<int> = [1]; $x = $v[null];', 'indexed access keys require'],
	['$v vector<int> = [1]; $x = $v["0"];', 'requires matching types'],
	['$v dynamic = {}; $x = $v[1.5];', 'indexed access keys require'],
	['function read(hash<int,mixed> $v): int { return $v[0]; }', 'required capability hashable'],
	['$v vector<int> = [1]; $x = $v[];', 'operator[] with 0 argument(s)'],
	['$v hash<int> = {}; $v[] = 2;', 'does not support append'],
	['$v vector<vector<int>> = [[1]]; $v[][0] = 2;', 'intermediate append'],
	['$v vector<int> = [1]; $v[0][0] = 2;', 'operator[] with 1 argument(s)'],
	['$v vector<vector<int>> = [[1]]; $v[0][0] = "bad";', 'requires matching types'],
	['$v vector<vector<int>> = [[1]]; $v[true][0] = 2;', 'indexed access keys require'],
	['$v vector<vector<int>> = [[1]]; $v[0][$new = 0] = 2;', 'established local declaration for new'],
	['$v mixed = {}; $d dynamic = {}; $v["a"]["b"] = $d;', 'family conversion requires'],
	['function make(): dynamic { return {}; } make()["a"] = 1;', 'direct local or parameter'],
	['$v vector<int> = []; $v[] = "bad";', 'requires matching types'],
	['$v dynamic = {}; $v[true] = 1;', 'indexed access keys require'],
	['$v dynamic = {}; $v["a"] = $new = 1;', 'established local declaration for new'],
	['$v vector<int> = [1]; $v[0]++;', 'mutation requires'],
	['$v vector<int> = [1]; $v[0] += 2;', 'mutation requires'],
	['function accept(int &$v): void {} $v vector<int> = [1]; accept($v[0]);', 'stable storage'],
	['$v vector<int> = [1]; $x = isset($v[]);', 'explicit keys'],
	['$v vector<int> = [1]; unset($v[0]);', 'keyed removal'],
	['$v mixed = {}; unset($v);', 'requires nullable storage'],
	['function make(): dynamic { return {}; } unset(make()["a"]);', 'local or parameter root'],
	['$v vector<int> = [1]; $x = $v[$new = 0];', 'established local declaration for new'],
] as [$source, $diagnostic])
{
	$failed = false;
	$reported = 'No diagnostic';
	try {
		indexing_prepare($source);
	}
	catch (\RuntimeException $error) {
		$reported = $error->getMessage();
		$failed = str_contains($error->getMessage(), $diagnostic);
	}
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Missing operator[] diagnostic: ' . $source . ' — ' . $reported);
	}
}

indexing_prepare('$v vector<int> = [1]; $x = $v[0]; return 0;');
$read = Model::syntax_files()[0]->root->single_body()->body->statements[1]->expression->value;
if (!($read instanceof index_node) || $read->require_preparation()->addressable()
	|| !$read->require_preparation()->type->matches(Language_Types::integer(Model::$language_scope))) {
	throw new \LogicException('Read did not retain its exact value-only result type');
}
Preparation_Cleanup::tree($read);
if ($read->preparation() !== null || $read->base->preparation() !== null || $read->index->preparation() !== null) {
	throw new \LogicException('Read facts survived cleanup');
}

indexing_prepare('function put(vector<int> &$v): void { $v[] = 1; }');
$write = Model::$global_scope->functions_named('put')[0]->syntax()->body->statements[0]->expression;
$facts = $write->require_write_preparation();
if (!($facts instanceof prepared_index_assignment) || $facts->addressable()
	|| !$facts->destination_type()->matches(Language_Types::integer(Model::$language_scope))
	|| $facts->decision->operation !== operator_operation::index_append
	|| str_contains(CPP_Probe::text(Model::$cpp_files), '([&]()')) {
	throw new \LogicException('Statement append lost operation facts or introduced an unused value lambda');
}
Preparation_Cleanup::tree($write);
if ($write->preparation() !== null || $write->target->base->preparation() !== null
	|| $write->value->preparation() !== null) {
	throw new \LogicException('Write facts survived cleanup');
}

indexing_prepare('$value int = 1; $value = 2;');
$ordinary_write = Model::syntax_files()[0]->root->single_body()->body->statements[1]->expression;
if (!$ordinary_write->require_write_preparation()->destination_type()->matches(
	$ordinary_write->require_assignment_preparation()->binding->storage_type())) {
	throw new \LogicException('Assignment family lost its storage destination query');
}

// Write-path brackets own traversal facts through the assignment, not copied reads.
indexing_prepare('function put(vector<vector<int>> &$v): void { $v[0][] = 1; }');
$write = Model::$global_scope->functions_named('put')[0]->syntax()->body->statements[0]->expression;
$facts = $write->require_write_preparation();
if (!($facts instanceof prepared_index_assignment) || $facts->path->count() !== 1
	|| $facts->path[0]->syntax !== $write->target->base
	|| $facts->root !== $write->target->base->base
	|| $facts->path[0]->syntax->preparation() !== null) {
	throw new \LogicException('Nested target lost path facts or was prepared as a copied read');
}
Preparation_Cleanup::tree($write);
if ($write->preparation() !== null || $facts->root->preparation() !== null
	|| $facts->path[0]->syntax->index->preparation() !== null) {
	throw new \LogicException('Nested write facts survived cleanup');
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
	$expected = CPP_Probe::text(Model::$cpp_files);
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
		if (CPP_Probe::text(Model::$cpp_files) !== $expected) {
			throw new \LogicException('Bracket failure recovery changed valid output');
		}
	}
	$source = 'function pick(vector<int> $v): int { return $v[0]; } return 0;';
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	$function = Model::$global_scope->functions_named('pick')[0]->syntax();
	$read = $function->body->statements[0]->expression;
	$old = $read->require_index_preparation();
	file_put_contents($path, str_replace('vector<int>', 'hash<int,int>', $source));
	$compiler->update_cpp([$path]);
	if ($read !== $function->body->statements[0]->expression || $old === $read->require_index_preparation()) {
		throw new \LogicException('Signature edit did not reprepare retained bracket read');
	}
	file_put_contents($path, str_replace('vector<int>', 'hash<int>', $source));
	$failed = false;
	try { $compiler->update_cpp([$path]); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty() || $read->preparation() !== null) {
		throw new \LogicException('Invalid key signature published stale read facts/output');
	}
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	$incremental = CPP_Probe::text(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->exec_cpp();
	if ($incremental !== CPP_Probe::text(Model::$cpp_files)) {
		throw new \LogicException('Recovered read differs from fresh output');
	}
	$source = 'function put(vector<vector<int>> &$v): void { $v[0][] = 1; } return 0;';
	file_put_contents($path, $source);
	$fresh->update_cpp([$path]);
	$function = Model::$global_scope->functions_named('put')[0]->syntax();
	$write = $function->body->statements[0]->expression;
	$old = $write->require_write_preparation();
	file_put_contents($path, str_replace('vector<vector<int>>', 'vector<hash<int,int>>', $source));
	$fresh->update_cpp([$path]);
	if ($write !== $function->body->statements[0]->expression || $old === $write->require_write_preparation()) {
		throw new \LogicException('Signature edit did not reprepare retained append');
	}
	file_put_contents($path, str_replace('vector<vector<int>>', 'vector<hash<int>>', $source));
	$failed = false;
	try { $fresh->update_cpp([$path]); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty() || $write->preparation() !== null) {
		throw new \LogicException('Invalid append signature published stale facts/output');
	}
	file_put_contents($path, $source);
	$fresh->update_cpp([$path]);
	$incremental = CPP_Probe::text(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	$recovered = new Compiler();
	$recovered->init([$directory]);
	$recovered->exec_cpp();
	if ($incremental !== CPP_Probe::text(Model::$cpp_files)) {
		throw new \LogicException('Recovered append differs from fresh output');
	}
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Indexing: arities, read/write facts, deferred operations, compaction and incremental recovery passed\n";
