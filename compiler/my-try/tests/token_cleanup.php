<?php

namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function token_cleanup_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

$directory = sys_get_temp_dir() . '/scpp_token_cleanup_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$initial = 'function value(): int { return 17; } return value();';
try
{
	file_put_contents($path, $initial);
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$record = Model::$modules[$directory]->sources['main.phs'];
	$function = object_cast(Model::$global_scope->functions_named('value')[0]->node, function_node::class);
	$body = $function->body;
	$first = $body->start_token();
	$entry = $record->parsed->collection->entries[$record->parsed->collection->function_references[0]];
	$entry_index = $entry->token_index;
	$facts = $body->work();
	$version = $facts->version;
	$initial_count = $record->tokens->end_token;

	// A preceding declaration moves current source positions while the unchanged body stays put.
	$changed = 'function extra(): int { return 9; } ' . $initial;
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	$tokens = $record->tokens;
	token_cleanup_check($tokens->first_token === $initial_count, 'New input did not start at the end of retained tokens');
	token_cleanup_check(($tokens->content === $initial . $changed) && ($tokens->content_offset === strlen($initial)), 'Source prefix or append offset lost');
	token_cleanup_check(($function->body === $body) && ($body->start_token() === $first) && ($entry->token_index === $entry_index), 'Compilation relocated reused syntax or occurrences');
	token_cleanup_check(($body->work() === $facts) && ($facts->version === $version), 'Unchanged body was prepared again');
	$output = Model::$cpp_files[0]->text;
	$first_row = $tokens->tokens[$tokens->first_token];
	token_cleanup_check(substr($tokens->content, $first_row->offset, $first_row->length) === 'function', 'Appended token bytes are not addressable');

	// The host has the output before normalization; compaction must not invalidate cached facts/text.
	$compiler->cleanup_tokens();
	token_cleanup_check(($tokens->first_token === 0) && ($tokens->content_offset === 0) && ($tokens->content === $changed), 'Cleanup did not reclaim the old prefix');
	token_cleanup_check(($body->start_token() > $first) && ($tokens->text_at($body->start_token()) === '{'), 'Cleanup did not remap retained body bounds');
	token_cleanup_check($tokens->text_at($entry->token_index) === 'value', 'Cleanup did not remap a collected occurrence');
	token_cleanup_check(($facts->version === $version) && (Model::$cpp_files[0]->text === $output), 'Cleanup altered facts or delivered output');
	$compiler->prepare();
	$compiler->cpp();
	token_cleanup_check(Model::$cpp_files[0]->text === $output, 'Generating after cleanup changed output');

	// Repeated updates without host cleanup use the synchronous fallback before the next scan.
	for ($iteration = 0; $iteration < 3; $iteration++)
	{
		$changed = str_replace('return 9;', 'return 99;', $changed) . "\n";
		file_put_contents($path, $changed);
		$compiler->update_cpp([$path]);
		token_cleanup_check(($function->body === $body) && ($facts->version === $version), 'Immediate increment lost retained work');
		token_cleanup_check(count($record->tokens->tokens) === 2 * $record->tokens->first_token, 'Immediate increments accumulated obsolete generations');
	}
	$incremental = Model::$cpp_files[0]->text;
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	token_cleanup_check(Model::$cpp_files[0]->text === $incremental, 'Appended incremental output differs from a fresh build');

	// Parser diagnostics use file-relative bytes despite the retained prefix.
	file_put_contents($path, 'function bad(');
	try {
		$compiler->update_cpp([$path]);
		throw new \LogicException('Malformed input was accepted');
	}
	catch (\RuntimeException $error) {
		token_cleanup_check(str_contains($error->getMessage(), ': byte 13'), 'Diagnostic included the appended source prefix');
	}
	$compiler->cleanup_tokens();
	file_put_contents($path, $initial);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	token_cleanup_check(Model::tokens()[0]->content === $initial, 'Recovery retained obsolete source bytes');
	file_put_contents($path, '');
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	token_cleanup_check((Model::tokens()[0]->content === '') && (count(Model::tokens()[0]->tokens) === 0), 'Empty input retained obsolete tokens');
	file_put_contents($path, "  \n");
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	file_put_contents($path, $initial);
	$compiler->update_cpp([$path]);
	$compiler->cleanup_tokens();
	token_cleanup_check(Model::tokens()[0]->content === $initial, 'Whitespace-only prefix was not reclaimed');
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Token cleanup: stable compile-time indexes, retained bytes/facts, deferred compaction, immediate increments, diagnostics and retry passed\n";
