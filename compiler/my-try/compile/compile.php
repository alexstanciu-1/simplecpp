<?php

/*
 * Role: coordinate the currently imported compiler stages.
 * Call map: main.php -> init -> exec -> tokenize -> parse -> llvm -> run_native.
 * Output: retained sources, syntax, collection and per-source LLVM modules.
 */
namespace scpp\compiler;

final class Compiler
{
	/** Load one module per input folder, retaining the requested order. */
	public function init(array $paths /** vector<string> */): void
	{
		Model::reset();

		foreach ($paths as $path) {
			$module = new module();
			Module_Loader::init($module, $path);
			Model::$modules[] = $module;
		}
	}
	/** Run the source pipeline through collection and the initial LLVM pass. */
	public function exec(): void
	{
		if (dbg) {
			echo __CLASS__ . " _ " . __METHOD__ . "\n";
		}

		$this->tokenize();
		$this->parse();
		$this->llvm();
		if (dbg) {
			$this->run_native();
		}
	}

	/** Tokenize each module's files and retain the completed collections. */
	public function tokenize(): void
	{
		Model::reset_tokens();

		foreach (Model::$modules as $m)
		{
			foreach ($m->files as $file)
			{
				$tok = new Tokenizer();
				$tok->init($file);
				$tokens = $tok->tokenize();
				$file->tokens = $tokens;
				Model::$tokens[] = $tokens;

				if (dbg) {
					echo "\nTokens: " . htmlspecialchars($file->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
					echo "offset\tlength\ttext\n";
					foreach ($tokens->tokens as $token) {
						echo $token->offset . "\t" . $token->length . "\t" . htmlspecialchars($token->text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
					}
				}
			}
		}
	}

	/** Parse into the selected global scope, collecting file-local occurrences during parsing. */
	public function parse(): void
	{
		Model::reset_syntax();

		foreach (Model::$tokens as $tokens) {
			$parser = new Parser();
			$parser->init($tokens, Model::$global_scope);
			$parsed = $parser->parse();
			Model::$syntax_files[] = $parsed;
			Model::$collected_files[] = $parsed->collection;
		}

		if (dbg) {
			$this->dump_collection();
		}
	}

	/** Show declarations and pending uses with their source locations. */
	private function dump_collection(): void
	{
		echo "\nCollected names (global and function-local scopes)\n";
		foreach (Model::$collected_files as $file)
		{
			echo '  ' . htmlspecialchars($file->source->file->path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
			foreach (['defined_elements', 'type_references', 'variable_references', 'function_references', 'field_references', 'pending_bindings'] as $group)
			{
				echo "    $group\n";
				foreach ($file->$group as $index) {
					$entry = $file->entries[$index];
					$scope_label = $entry->scope->function_boundary ? 'function-local' : 'global';
					echo "      entry {$entry->local_index} " . $entry->kind->name . ': ' . htmlspecialchars($entry->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . " at token {$entry->token_index} ($scope_label)\n";
				}
			}
		}
	}

	/** Prepare all sources and emit one LLVM module per source file. */
	public function llvm(): void
	{
		Model::reset_llvm();
		$policy = new llvm_policy();
		$prepared_files = (new LLVM_Preparation())->prepare_program(Model::$collected_files, $policy);
		Model::$llvm_files = (new LLVM_Generator())->generate($prepared_files, $policy);

		if (dbg) {
			foreach (Model::$llvm_files as $output) {
				echo "\nLLVM: " . htmlspecialchars($output->file_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
				echo htmlspecialchars($output->text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			}
		}
	}

	/** Debug execution uses the generated IR and reports build/run results on the page. */
	private function run_native(): void
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
}
