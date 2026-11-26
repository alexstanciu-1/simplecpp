<?php
declare(strict_types=1);

/** Compare the reachable preparation graph without owning any of its objects between captures. */
final class Preparation_Snapshot
{
	private WeakMap $ids;
	private int $next = 0;

	public function __construct()
	{
		$this->ids = new WeakMap();
	}

	public function capture(object $root): string
	{
		$nodes = [];
		$this->value($root, $nodes);
		return serialize($nodes);
	}

	/**
	* Record each object's state once per capture, preserving shared and cyclic references.
	* @param array<int,array> $nodes
	*/
	private function value(mixed $value, array &$nodes): mixed
	{
		if (is_array($value)) {
			$elements = [];
			foreach ($value as $key => $element) $elements[$key] = $this->value($element, $nodes);
			return ['array' => $elements];
		}
		if (!is_object($value)) return $value;
		if ($value instanceof WeakReference) return ['weak' => $this->value($value->get(), $nodes)];
		$id = $this->ids[$value] ??= ++$this->next;
		if (!isset($nodes[$id])) {
			$nodes[$id] = [$value::class];
			$nodes[$id][] = $this->value((array)$value, $nodes);
			if ($value instanceof WeakMap) {
				foreach ($value as $key => $item) $nodes[$id][] = [$this->value($key, $nodes), $this->value($item, $nodes)];
			} elseif ($value instanceof SplObjectStorage) {
				foreach (clone $value as $key) $nodes[$id][] = [$this->value($key, $nodes), $this->value($value[$key], $nodes)];
			}
		}
		return ['object' => $id];
	}
}
