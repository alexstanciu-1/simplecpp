<?php
namespace scpp\compiler;

/** @scpp-no-export */
final class Host_Report
{
	/** PHP browser/CLI presentation and optional sample execution stay outside the compiler. */
	public function show(): void
	{
		foreach (Model::$tokens as $tokens)
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
		foreach (Model::$syntax_files as $syntax) {
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
		foreach (Model::$collected_files as $file)
		{
			if ($file->source->file->changes === SYNC_DELETED) {
				continue;
			}
			echo '  ' . htmlspecialchars($file->source->file->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
			foreach (['defined_elements', 'type_references', 'variable_references', 'function_references', 'field_references', 'pending_bindings'] as $group)
			{
echo "    $group\n";
				foreach ($file->$group as $index)
				{
					$entry = $file->entries[$index];
					if ($entry->changes === SYNC_DELETED) {
						continue;
					}
					$scope_label = $entry->scope->function_boundary ? 'function-local' : 'global';
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
		$label = $node->kind->name;
		$payload = $node->structure;
		if ($payload instanceof binding_structure) {
			$label .= ' (' . $payload->classification->name . ')';
		}
		$words /** vector<string> */ = [];
		for ($index = $node->token_index; $index < $node->end_token_index; $index++) {
			$words[] = $tokens->tokens[$index]->text();
		}
echo str_repeat('  ', $depth) . $label . " [{$node->token_index}, {$node->end_token_index}) " . htmlspecialchars(implode(' ', $words), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";

		if ($payload instanceof block_structure) {
			foreach ($payload->children as $child) {
				$this->dump_node($child, $depth + 1, $tokens);
			}
		}
		elseif ($payload instanceof expression_statement_structure) {
			$this->dump_node($payload->expression, $depth + 1, $tokens);
		}
		elseif ($payload instanceof function_structure) {
			foreach ($payload->parameters as $parameter) {
				$this->dump_node($parameter, $depth + 1, $tokens);
			}
			$this->dump_node($payload->return_type, $depth + 1, $tokens);
			$this->dump_node($payload->body, $depth + 1, $tokens);
		}
		elseif ($payload instanceof parameter_structure) {
			$this->dump_node($payload->type_syntax, $depth + 1, $tokens);
		}
		elseif ($payload instanceof call_structure) {
			foreach ($payload->arguments as $argument) {
				$this->dump_node($argument, $depth + 1, $tokens);
			}
		}
		elseif ($payload instanceof struct_structure) {
			foreach ($payload->fields as $field) {
				$this->dump_node($field, $depth + 1, $tokens);
			}
		}
		elseif ($payload instanceof field_structure) {
			$this->dump_node($payload->type_syntax, $depth + 1, $tokens);
		}
		elseif ($payload instanceof field_access_structure) {
			$this->dump_node($payload->base, $depth + 1, $tokens);
		}
		elseif ($payload instanceof array_type_structure) {
			$this->dump_node($payload->element_type, $depth + 1, $tokens);
			$this->dump_node($payload->count, $depth + 1, $tokens);
		}
		elseif ($payload instanceof array_literal_structure) {
			foreach ($payload->elements as $element) {
				$this->dump_node($element, $depth + 1, $tokens);
			}
		}
		elseif ($payload instanceof index_structure) {
			$this->dump_node($payload->base, $depth + 1, $tokens);
			$this->dump_node($payload->index, $depth + 1, $tokens);
		}
		elseif ($payload instanceof binding_structure)
		{
			if ($payload->target !== null) {
				$this->dump_node($payload->target, $depth + 1, $tokens);
			}
			if ($payload->type_syntax !== null) {
				$this->dump_node($payload->type_syntax, $depth + 1, $tokens);
			}
			if ($payload->value !== null) {
				$this->dump_node($payload->value, $depth + 1, $tokens);
			}
		}
		elseif (($payload instanceof return_structure) && ($payload->expression !== null)) {
			$this->dump_node($payload->expression, $depth + 1, $tokens);
		}
	}
}
