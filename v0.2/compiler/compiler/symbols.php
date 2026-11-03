<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Invocation-local coordinator: discoveries are pending until every frontend result succeeds. */
final class Symbol_Publication implements declaration_sink
{
	private readonly Scope_Collection $collection;
	public function __construct() { $this->collection = new Scope_Collection(new scope()); }
	private bool $published = false;

	/** Read only the initialized header; discovery must never inspect the still-unfinished body. */
	public function discovered(ast_declaration_interface $function): void
	{
		if ($this->published) {
			throw new \LogicException('Cannot notify a completed symbol publication');
		}
		if (!($function instanceof ast_function_declaration)) { throw new \LogicException('Only public functions are exported by the current file grammar'); }
		if (!$function->is_private) {
			$this->collection->import($function);
		}
	}

	/** The coordinator supplies the complete successful inventory; failed parses cannot publish candidates. */
	public function publish(array $files /** array<string,ast_file_node> */, ?scope $previous = null, ?function_dependencies $dependencies = null): scope
	{
		if ($this->published) {
			throw new \LogicException('Symbols already published');
		}
		$complete = [];
		foreach ($files as $file) {
			foreach ($file->functions() as $function) {
				$function->body();
				$complete[spl_object_id($function)] = true;
			}
		}
		foreach ($this->collection->inventory() as $candidate)
		{
			$id = spl_object_id($candidate);
			if (!isset($complete[$id])) { throw new \LogicException('Discovery is not part of a completed source result'); }
			unset($complete[$id]);
		}
		if ($complete !== []) {
			throw new \LogicException('Completed source has unreported declarations');
		}
		$this->published = true;
		$this->collection->order(static fn(ast_function_declaration $a, ast_function_declaration $b): int /** int32 */ =>
			strcmp($a->source()->path, $b->source()->path) ?: ($a->start <=> $b->start));
		// Capture the dependency role before publication rewrites links on reused AST declarations.
		$old_contracts = $dependencies !== null && $previous !== null ? function_dependencies::contracts($previous) : [];
		$scope = $this->collection->publish($previous);
		ksort($scope->declarations, SORT_STRING);
		foreach ($files as $file) { $file->scope->parent = $scope; }
		if ($dependencies !== null) {
			$dependencies->retain($files);
			$contracts = function_dependencies::contracts($scope);
			foreach (array_unique(array_merge(array_keys($old_contracts), array_keys($contracts))) as $key) {
				$old = $old_contracts[$key] ?? null;
				$current = $contracts[$key] ?? null;
				$entry = $scope->entry($key);
				if (($old !== $current) || (array_key_exists($key, $old_contracts) !== array_key_exists($key, $contracts))
					|| (array_key_exists($key, $contracts) && $current === null && $entry->status !== change_status::none)) {
					$dependencies->changed($entry);
				}
			}
		}
		return $scope;
	}

	/** In-memory analysis clients publish their already complete collections through the same owner. */
	public static function completed(array $files /** array<string,ast_file_node> */, ?scope $previous = null, ?function_dependencies $dependencies = null): scope
	{
		$publication = new self();
		foreach ($files as $file) {
			foreach ($file->functions() as $function) {
				$publication->discovered($function);
			}
		}
		return $publication->publish($files, $previous, $dependencies);
	}
}
