<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Function existence/signature role: e.g. second's symbol -> first's calling body; reverse index supports replacement. */
final class function_dependencies
{
	private \SplObjectStorage $observers /** SplObjectStorage<symbol_entry,SplObjectStorage<ast_function_body,null>> */;
	private \SplObjectStorage $targets /** SplObjectStorage<ast_function_body,SplObjectStorage<symbol_entry,null>> */;

	public function __construct()
	{
		$this->observers = new \SplObjectStorage();
		$this->targets = new \SplObjectStorage();
	}

	/** Ephemeral signature snapshot across chain publication; no AST copies or retained candidate lists. */
	public static function contracts(scope $scope): array /** array<string,?string>; null means ambiguous, absent key means missing */
	{
		$contracts = [];
		foreach ($scope->declarations as $key => $head) {
			$contracts[$key] = $head->duplicate_element() === null ? $head->return_type_name : null;
		}
		return $contracts;
	}

	public function observe(symbol_entry $target, ast_function_body $body): void
	{
		if (!$this->observers->contains($target)) { $this->observers[$target] = new \SplObjectStorage(); }
		if (!$this->targets->contains($body)) { $this->targets[$body] = new \SplObjectStorage(); }
		$this->observers[$target]->attach($body);
		$this->targets[$body]->attach($target);
	}

	/** Remove subscriptions of a replaced body or before reconstructing its cache. */
	public function forget(ast_function_body $body): void
	{
		if (!$this->targets->contains($body)) { return; }
		foreach ($this->targets[$body] as $target) {
			$this->observers[$target]->detach($body);
			if (count($this->observers[$target]) === 0) { $this->observers->detach($target); }
		}
		$this->targets->detach($body);
	}

	/** Publication notifications only invalidate; no preparation runs inside this traversal. */
	public function changed(symbol_entry $target): void
	{
		if (!$this->observers->contains($target)) { return; }
		foreach ($this->observers[$target] as $body) { $body->invalidate_preparation(); }
	}

	public function retain(array $files /** array<string,ast_file_node> */): void
	{
		$live = new \SplObjectStorage();
		foreach ($files as $file) {
			foreach ($file->functions() as $function) { $live->attach($function->body()); }
			if ($file->main() !== null) { $live->attach($file->main()->body()); }
		}
		$removed = [];
		foreach ($this->targets as $body) { if (!$live->contains($body)) { $removed[] = $body; } }
		foreach ($removed as $body) {
			$body->invalidate_preparation();
			$this->forget($body);
		}
	}

	public function observer_count(symbol_entry $target): int /** uint32 */
	{
		return $this->observers->contains($target) ? count($this->observers[$target]) : 0;
	}
}
