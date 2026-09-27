<?php
namespace scpp\compiler;

/** @scpp-no-export */
final class Host_Report
{
	/** PHP browser/CLI presentation and optional sample execution stay outside the compiler. */
	public function show(): void
	{
		foreach (Model::tokens() as $tokens)
		{
			if ($tokens->file->changes === SYNC_DELETED) {
				continue;
			}
			echo "\nTokens: " . htmlspecialchars($tokens->file->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
			echo "offset\tlength\ttext\n";
			foreach ($tokens->tokens as $token) {
				echo $token->offset . "\t" . $token->length . "\t" . htmlspecialchars($token->text(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
			}
		}
		foreach (Model::syntax_files() as $syntax) {
			if ($syntax->tokens->file->changes === SYNC_DELETED) {
				continue;
			}
			echo "\nAST: " . htmlspecialchars($syntax->tokens->file->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
			$this->dump_node($syntax->root, 0, $syntax->tokens);
		}
		$this->dump_collection();
		foreach (Model::$llvm_files as $output) {
			echo "\nLLVM: " . htmlspecialchars($output->file_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
			echo htmlspecialchars($output->text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		}
	}

	/** Show declarations and pending uses with their source locations. */
	private function dump_collection(): void
	{
		echo "\nCollected names (global and function-local scopes)\n";
		foreach (Model::collected_files() as $file)
		{
			if ($file->source_file()->changes === SYNC_DELETED) {
				continue;
			}
			echo '  ' . htmlspecialchars($file->source_file()->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
			foreach (['defined_elements', 'type_references', 'variable_references', 'function_references', 'field_references', 'pending_bindings'] as $group)
			{
				echo "    $group\n";
				foreach ($file->$group as $index)
				{
					$entry = $file->entries[$index];
					if ($entry->changes === SYNC_DELETED) {
						continue;
					}
					$scope_label = $entry->scope->is_function() ? 'function-local' : 'global';
					echo "      entry {$entry->local_index} " . $entry->kind->name . ': ' . htmlspecialchars($entry->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . " at token {$entry->token_index} ($scope_label)\n";
				}
			}
		}
	}

	/** Debug execution uses the generated IR and reports build/run results on the page. */
	public function run_native(): void
	{
		$runner = new Native_Runner();
		try {
			$result = $runner->run(Model::$llvm_files);
			$runner->dump($result);
		}
		catch (\Throwable $error) {
			echo "\nNative execution failed: " . htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
		}
	}

	/** Display parsed nodes with their token spans and original spelling. */
	private function dump_node(ast_node $node, int $depth, token_list $tokens): void
	{
		$label = $node->kind()->name;
		$payload = $node->payload();
		if ($payload instanceof binding_structure) {
			$label .= ' (' . $payload->syntax_kind->name . ')';
		}
		$words /** vector<string> */ = [];
		for ($index = $node->token_index; $index < $node->end_token_index; $index++) {
			$words[] = $tokens->tokens[$index]->text();
		}
		echo str_repeat('  ', $depth) . $label . " [{$node->token_index}, {$node->end_token_index}) " . htmlspecialchars(implode(' ', $words), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";

		$child = $node->first_child();
		while ($child !== null) {
			$child_node /** ast_node */ = $child;
			$this->dump_node($child_node, $depth + 1, $tokens);
			$child = $child_node->next();
		}
	}
}
