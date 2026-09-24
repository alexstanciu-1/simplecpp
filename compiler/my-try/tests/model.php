<?php

namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
\define('dbg', false);

final class Model_Test
{
	/** Verify publication and shared graph links across numeric Storage boundaries. */
	public static function run(): void
	{
		$compiler = new Compiler();
		$compiler->init([dirname(__DIR__) . '/samples/01_base']);
		foreach ([Model::$modules, Model::$tokens, Model::$syntax_files, Model::$collected_files, Model::$llvm_files] as $storage) {
			if (!$storage instanceof Storage) {
				throw new \RuntimeException('Model collection is not Storage');
			}
		}
		$empty_output = Model::$llvm_files;
		$compiler->exec();
		if ((count(Model::$modules) !== 1) || (count(Model::$tokens) !== 2) || (count(Model::$syntax_files) !== 2) || (count(Model::$collected_files) !== 2) || (count(Model::$llvm_files) !== 2)) {
			throw new \RuntimeException('Unexpected model collection counts');
		}
		foreach (Model::$tokens as $position => $tokens)
		{
			if (!is_int($position) || ($tokens->file !== Model::$modules[0]->files[$position])) {
				throw new \RuntimeException('Source identity or numeric order changed');
			}
			if (($tokens->file->tokens !== $tokens) || (Model::$collected_files[$position]->source !== $tokens) || (Model::$syntax_files[$position]->tokens !== $tokens) || (Model::$syntax_files[$position]->collection !== Model::$collected_files[$position])) {
				throw new \RuntimeException('Parser/collection sharing changed');
			}
		}
		$seen = new \SplObjectStorage();
		foreach ([Model::$modules, Model::$tokens, Model::$syntax_files, Model::$global_scope, Model::$collected_files, Model::$llvm_files] as $root) {
			self::check_graph($root, $seen);
		}
		if ((Model::$llvm_files === $empty_output) || !$empty_output->is_empty()) {
			throw new \RuntimeException('Generation did not publish a separate result');
		}

		$old_output = Model::$llvm_files;
		$compiler->init([]);
		foreach ([Model::$modules, Model::$tokens, Model::$syntax_files, Model::$collected_files, Model::$llvm_files] as $storage) {
			if (!$storage->is_empty()) {
				throw new \RuntimeException('Reset retained previous rows');
			}
		}
		if ((Model::$llvm_files === $old_output) || (count($old_output) !== 2)) {
			throw new \RuntimeException('Reset altered the previously retained result');
		}
		self::check_failure($compiler, '$', true);
		self::check_failure($compiler, 'function unfinished(', false);
		self::check_assigned_position();
		self::check_restarts($compiler);
		echo "Model: data-only graph, Storage boundaries, sharing, failure publication and reset passed\n";
	}

	/** Stage failure must not leave output or backlinks from the previous run. */
	private static function check_restarts(Compiler $compiler): void
	{
		$compiler->init([dirname(__DIR__) . '/samples/01_base']);
		$compiler->exec();
		$preparation = new LLVM_Preparation();
		$prepared = $preparation->prepare_program(Model::$collected_files, new llvm_policy());
		$output = (new LLVM_Generator())->generate($prepared, new llvm_policy());
		$incoming = new \SplObjectStorage();
		foreach ($prepared as $prepared_file) {
			foreach ($prepared_file->functions as $function) {
				foreach ($function->parameters as $parameter) { $incoming->attach($parameter->incoming); }
			}
		}
		foreach ($output as $output_file) {
			foreach ($output_file->functions as $function) {
				foreach ($function->parameters as $parameter) {
					if ($incoming->contains($parameter)) { throw new \LogicException('Output borrowed preparation operand'); }
				}
			}
		}
		// Reusing a worker must not reuse mutable collections or damage the old graph.
		$again = $preparation->prepare_program(Model::$collected_files, new llvm_policy());
		if (($again === $prepared) || ($again[0] === $prepared[0]) || ($again[0]->functions === $prepared[0]->functions)) {
			throw new \LogicException('Preparation reused a previous result');
		}
		$seen = new \SplObjectStorage();
		self::check_graph($prepared, $seen);
		foreach ($prepared as $file) {
			foreach ($file->external_functions as $name => $target) {
				if (($name !== $target->name) || ($target->file === $file)) {
					throw new \LogicException('External target key or provenance changed');
				}
				$found = false;
				foreach ($target->file->functions as $owned) {
					if ($owned === $target) { $found = true; }
				}
				if (!$found) { throw new \LogicException('External target lost shared identity'); }
			}
		}
		$regenerated = (new LLVM_Generator())->generate($prepared, new llvm_policy());
		foreach ($output as $index => $file) {
			if ($file->text !== $regenerated[$index]->text) {
				throw new \LogicException('Preparation reuse altered retained output');
			}
		}
		$old_output = Model::$llvm_files;
		$old_scope = Model::$global_scope;
		$old_tokens = Model::$tokens[0];
		$old_content = $old_tokens->content;
		$old_tokens->file->content = '$';
		try {
			$compiler->tokenize();
			throw new \LogicException('Expected lexical failure');
		} catch (\RuntimeException $expected) { }
		foreach ([Model::$tokens, Model::$syntax_files, Model::$collected_files, Model::$llvm_files] as $store) {
			if (!$store->is_empty()) { throw new \LogicException('Stale downstream result'); }
		}
		foreach (Model::$modules[0]->files as $file) {
			if (($file->tokens !== null)) { throw new \LogicException('Stale token backlink'); }
		}
		if (($old_tokens->content !== $old_content) || ($old_scope === Model::$global_scope) || count($old_output) !== 2) {
			throw new \LogicException('Restart damaged retained results');
		}
		$old_tokens->file->content = $old_content;
		$compiler->exec();
		// Corrupt the first token only to exercise parse failure after a successful run.
		Model::$tokens[0]->tokens[0]->text = ')';
		try {
			$compiler->parse();
			throw new \LogicException('Expected parse failure');
		} catch (\RuntimeException $expected) { }
		if (!Model::$syntax_files->is_empty() || !Model::$collected_files->is_empty() || !Model::$llvm_files->is_empty()) {
			throw new \LogicException('Parse failure retained stale output');
		}
		$compiler->exec();
		$compiler->parse();
		if (!Model::$llvm_files->is_empty() || count(Model::$syntax_files) !== 2) {
			throw new \LogicException('Successful parse retained stale LLVM');
		}
	}

	/** Inject a historical hole to prove occurrence IDs use Storage's assigned position. */
	private static function check_assigned_position(): void
	{
		$source = new token_list();
		$token = new token();
		$token->text = 'name';
		$source->tokens[] = $token;
		$collector = new Symbol_Collector($source);
		$node = new ast_node();
		$scope = new scope();
		$collector->record($node, 0, collected_name_kind::variable_reference, $scope);
		// Host test access only: the compiler still builds occurrence lists append-only.
		$property = new \ReflectionProperty(Symbol_Collector::class, 'file');
		$file = $property->getValue($collector);
		$file->entries->remove(0);
		$file->variable_references = [];
		$position = $collector->record($node, 0, collected_name_kind::variable_reference, $scope);
		$result = $collector->finish($node);
		if (($position !== 1) || ($result->entries[1]->local_index !== 1) || ($result->variable_references !== [1]) || isset($result->entries[0])) {
			throw new \RuntimeException('Occurrence index did not use the assigned storage position');
		}
	}

	/** Follow cycles and indexes too; only structures, enums and Storage may be retained. */
	private static function check_graph(mixed $value, \SplObjectStorage $seen): void
	{
		if (is_array($value)) {
			foreach ($value as $item) {
				self::check_graph($item, $seen);
			}
			return;
		}
		if (!is_object($value) || $seen->contains($value)) {
			return;
		}
		$seen->attach($value);
		if ($value instanceof Storage_Abstract) {
			foreach ($value as $item) {
				self::check_graph($item, $seen);
			}
			return;
		}
		if ($value instanceof \UnitEnum) {
			return;
		}
		$type = new \ReflectionClass($value);
		if (basename($type->getFileName()) !== 'structures.php') {
			throw new \RuntimeException('Worker retained in model: ' . $type->getName());
		}
		foreach ($type->getProperties() as $property) {
			$comment = $property->getDocComment();
			if (($comment !== false) && str_contains($comment, 'Numeric storage of') && (!$property->isInitialized($value) || !$property->getValue($value) instanceof Storage)) {
				throw new \RuntimeException('Uninitialized record storage: ' . $property->getName());
			}
			if ($property->isInitialized($value)) {
				self::check_graph($property->getValue($value), $seen);
			}
		}
	}

	/** Failed files must not publish partially built token or syntax records. */
	private static function check_failure(Compiler $compiler, string $content, bool $lexical): void
	{
		$compiler->init([]);
		$file = new file();
		if ($file->tokens !== null) { throw new \LogicException('New source has a token backlink'); }
		$file->path = 'invalid.phs';
		$file->content = $content;
		$module = new module();
		$module->files[] = $file;
		Model::$modules[] = $module;
		$failed = false;
		try {
			$compiler->tokenize();
			$compiler->parse();
		}
		catch (\RuntimeException $error) {
			$failed = true;
		}
		if (!$failed || !Model::$syntax_files->is_empty() || !Model::$collected_files->is_empty() || !empty(Model::$global_scope->functions)) {
			throw new \RuntimeException('Failed file published parse output');
		}
		if ($lexical && (!Model::$tokens->is_empty() || ($file->tokens !== null))) {
			throw new \RuntimeException('Failed scan published token output');
		}
		if (!$lexical && ((count(Model::$tokens) !== 1) || ($file->tokens !== Model::$tokens[0]))) {
			throw new \RuntimeException('Parse failure lost completed token output');
		}
	}
}

Model_Test::run();
