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
		foreach ($this->collection->candidates() as $candidates)
		{
			foreach ($candidates as $candidate)
			{
				$id = spl_object_id($candidate);
				if (!isset($complete[$id])) {
					throw new \LogicException('Discovery is not part of a completed source result');
				}
				unset($complete[$id]);
			}
		}
		if ($complete !== []) {
			throw new \LogicException('Completed source has unreported declarations');
		}
		$this->published = true;
		$pending = $this->collection->candidates();
		$changed = [];
		if (($previous !== null) && ($dependencies !== null)) {
			foreach (array_unique(array_merge(array_keys($previous->declarations), array_keys($pending))) as $key) {
				if (function_dependencies::contract_changed($previous->declarations[$key] ?? [], $pending[$key] ?? [])) {
					$changed[] = $key;
				}
			}
		}
		// All declarations are complete before the shared scope publisher touches live entries.
		$scope = $this->collection->publish($previous);
		ksort($scope->declarations, SORT_STRING);
		foreach ($files as $file) { $file->scope->parent = $scope; }
		if ($dependencies !== null) {
			$dependencies->retain($files);
			foreach ($changed as $key) { $dependencies->changed($scope->entry($key)); }
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
