<?php

/*
 * Role: coordinate the currently imported compiler stages.
 * Call map: main.php -> init -> exec -> tokenize -> parse -> llvm.
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
			$input_module = new module();
			Module_Loader::init($input_module, $path);
			Model::$modules[] = $input_module;
		}
	}
	/** Run the source pipeline through collection and the initial LLVM pass. */
	public function exec(): void
	{
		$this->tokenize();
		$this->parse();
		$this->llvm();
	}

	/** Tokenize each module's files and retain the completed collections. */
	public function tokenize(): void
	{
		Model::reset_tokens();

		foreach (Model::$modules as $m)
		{
			foreach ($m->files as $file)
			{
				$tok = new Tokenizer($file);
				$tokens = $tok->tokenize();
				$file->tokens = $tokens;
				Model::$tokens[] = $tokens;
			}
		}
	}

	/** Parse into the selected global scope, collecting file-local occurrences during parsing. */
	public function parse(): void
	{
		Model::reset_syntax();

		foreach (Model::$tokens as $tokens) {
			$parser = new Parser($tokens, Model::$global_scope);
			$parsed = $parser->parse();
			Model::$syntax_files[] = $parsed;
			Model::$collected_files[] = $parsed->collection;
		}
	}

	/** Prepare all sources and emit one LLVM module per source file. */
	public function llvm(): void
	{
		Model::reset_llvm();
		$policy = new llvm_policy();
		$prepared_files = (new LLVM_Preparation())->prepare_program(Model::$collected_files, $policy);
		Model::$llvm_files = (new LLVM_Generator())->generate($prepared_files, $policy);
	}

}
