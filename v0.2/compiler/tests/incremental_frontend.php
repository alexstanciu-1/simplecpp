<?php

declare(strict_types=1);
namespace scpp\compiler {
	/** Count actual source reads independently of retained AST/token identity checks. */
	function file_get_contents(string $path): string|false
	{
		$GLOBALS['reads'][$path] = ($GLOBALS['reads'][$path] ?? 0) + 1;
		return \file_get_contents($path);
	}
}
namespace {
	require_once __DIR__ . '/../boot.php';
	use scpp\compiler\{Project_Compiler, Program_Frontend, entry_target, change_status};

	function verify(bool $ok, string $message): void
	{
		if (!$ok) {
			throw new RuntimeException($message);
		}
	}

	$root = sys_get_temp_dir() . '/scpp-incremental-frontend-' . bin2hex(random_bytes(8));
	mkdir($root);
	try
	{
		mkdir($root . '/runtime');
		file_put_contents($root . '/runtime/module.json', '{"type":"c++"}');
		$compiler = new Project_Compiler($root . '/runtime');
		foreach (['cpp', 'php', 'js'] as $flavor)
		{
			$app = $root . '/' . $flavor;
			mkdir($app);
			file_put_contents($app . '/module.json', '{}');
			$main_path = $app . '/main.' . $flavor;
			$helper_path = $app . '/helper.' . $flavor;
			$main_name = basename($main_path);
			$helper_name = basename($helper_path);
			$declaration = $flavor === 'cpp' ? 'void helper() {}' : 'function helper(): void {}';
			file_put_contents($main_path, 'helper(); return 8;');
			file_put_contents($helper_path, $declaration);
			$first = $compiler->frontend($app);
			verify($first->previous === null && $first->entry->sources[$main_name]->status === change_status::added, 'Cold path');
			$main = $first->entry->sources[$main_name]->ast;
			$call = $main->main()->body()->statements[0];
			$key = $call->symbol_key();
			verify($key === 'func:helper', 'Call handle is not canonical');
			$initial_helper = $first->symbols->resolve($key);
			verify($initial_helper->symbol_key() === $key, 'Declaration handle mismatch');
			verify($first->symbols->resolve('helper') === null && $first->symbols->resolve('func:main') === null, 'Index leaked local/private keys');
			try {
				$initial_helper->return_type_def();
				throw new RuntimeException('Frontend performed preparation');
			}
			catch (LogicException $expected) {
			}
			$second = $compiler->frontend($app, $first);
			verify($second->previous === $first, 'Previous generation not retained');
			verify($second->entry->sources[$main_name] !== $first->entry->sources[$main_name], 'Source records shared across generations');
			verify($second->entry->sources[$main_name]->ast === $main, 'Unchanged AST copied or reparsed');
			verify($second->entry->sources[$main_name]->ast->tokens === $main->tokens, 'Unchanged tokens copied');
			verify($GLOBALS['reads'][$main_path] === 1 && $GLOBALS['reads'][$helper_path] === 1, 'Unchanged source read again');
			verify($second->symbols->resolve($key) === $initial_helper, 'Unchanged declaration copied');
			verify($first->entry->sources[$main_name]->status === change_status::added, 'Previous status mutated');
			file_put_contents($helper_path, $declaration . ' // changed');
			$third = $compiler->frontend($app, $second);
			verify($third->entry->sources[$helper_name]->status === change_status::changed, 'Changed status');
			verify($third->symbols->resolve($key) !== $initial_helper, 'Changed declaration retained old destination');
			verify($third->entry->sources[$main_name]->ast === $main && $GLOBALS['reads'][$main_path] === 1, 'Dependent caller reparsed');
			verify($second->symbols->resolve($key) === $initial_helper, 'Previous index changed');
			verify($second->entry->sources[$helper_name]->ast->tokens->source->content === $declaration, 'Previous content overwritten');
			$moved_path = $app . '/moved.' . $flavor;
			rename($helper_path, $moved_path);
			$moved = $compiler->frontend($app, $third);
			verify(count($moved->removed_sources) === 1 && $moved->removed_sources[0]->status === change_status::removed, 'Removal not retained');
			verify($moved->removed_sources[0]->ast === $third->entry->sources[$helper_name]->ast, 'Removed AST copied/lost');
			verify($moved->symbols->resolve($key)->root() === $moved->entry->sources[basename($moved_path)]->ast, 'Move did not redirect key');
			$duplicate_path = $app . '/duplicate.' . $flavor;
			file_put_contents($duplicate_path, $declaration . ' ' . $declaration);
			$ambiguous = $compiler->frontend($app, $moved);
			verify(count($ambiguous->symbols->declarations[$key]) === 3, 'Global duplicate candidates lost');
			$head = $ambiguous->entry->sources[basename($duplicate_path)]->ast->declarations[$key];
			verify($head->duplicate_element() !== null, 'File-local duplicate chain lost');
			verify($moved->symbols->resolve($key)->duplicate_element() === null, 'Global duplicate chain mutated old AST');
			try {
				$ambiguous->symbols->resolve($key);
				throw new LogicException('Ambiguous key resolved');
			}
			catch (RuntimeException $expected) {
				verify(str_contains($expected->getMessage(), 'Ambiguous symbol'), 'Unexpected ambiguity error');
			}
			$again = $compiler->frontend($app, $ambiguous);
			verify($again->entry->sources[basename($duplicate_path)]->ast === $head->root(), 'Duplicate AST not reused');
			verify(count($head->root()->diagnostics) === 1, 'Duplicate diagnostic lost');
			unlink($duplicate_path);
			unlink($moved_path);
			$removed = $compiler->frontend($app, $again);
			verify($removed->symbols->resolve($key) === null && count($removed->removed_sources) === 2, 'Removed symbol still resolves');
			verify($removed->entry->sources[$main_name]->ast === $main, 'Unresolved call caused frontend mutation');
			$failure_path = $app . '/zbad.' . $flavor;
			file_put_contents($failure_path, 'this is invalid syntax');
			try {
				$compiler->frontend($app, $removed);
				throw new LogicException('Invalid candidate published');
			}
			catch (RuntimeException $expected) {
			}
			verify($removed->symbols->resolve($key) === null && $removed->entry->sources[$main_name]->ast === $main, 'Failed candidate damaged prior publication');
			unlink($failure_path);
			file_put_contents($helper_path, $declaration);
			$retry = $compiler->frontend($app, $removed);
			verify($retry->symbols->resolve($key) !== null && $retry->entry->sources[$main_name]->ast === $main, 'Retry/reappearance failed');
			try {
				(new Program_Frontend())->prepare($retry, new entry_target(31));
				throw new RuntimeException('Shared frontend generation entered preparation');
			}
			catch (LogicException $expected) {
				verify(str_contains($expected->getMessage(), 'cannot be prepared'), 'Wrong phase-boundary rejection');
			}
			$cold = $compiler->frontend($app);
			verify($cold->entry->sources[$main_name]->status === change_status::added && $cold->entry->sources[$main_name]->ast !== $main, 'Full frontend path did not start fresh');
			verify(array_keys($cold->symbols->declarations) === array_keys($retry->symbols->declarations), 'Incremental symbols differ from clean collection');
		}
	}
	finally
	{
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($iterator as $file) {
			if ($file->isDir()) {
				rmdir($file->getPathname());
			}
			else {
				unlink($file->getPathname());
			}
		}
		rmdir($root);
	}
	echo "Incremental frontend identity reuse, key resolution, moves, duplicates, removal and failure retry passed for all flavors\n";
}
