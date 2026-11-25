<?php
namespace scpp\compiler;

/** Invocation-local layout calculation; shared maps stay with their reader instead of copying per edge set. */
final class CPP_Layout_Depths
{
	private array $known /** hash<bool> */ = [];
	private array $depths /** hash<int> */ = [];

	/** Native headers need no generated layout; generated headers use the emitter's dependency facts. */
	private function required_depth(array $dependencies /** hash<bool> */): int
	{
		$depth = 0;
		foreach ($dependencies as $header => $used) {
			if (!isset($this->known[$header])) { continue; }
			if (!isset($this->depths[$header])) { return -1; }
			if ($this->depths[$header] > $depth) { $depth = $this->depths[$header]; }
		}
		return $depth;
	}

	/** Topological layout batches include shared source headers, not source method bodies. */
	public function resolve(Storage $artifacts /** Storage<cpp_artifact> */, Storage $entries /** Storage<cpp_specialization_entry> */): array /** hash<int> */
	{
		$empty_known /** hash<bool> */ = [];
		$empty_depths /** hash<int> */ = [];
		$this->known = $empty_known;
		$this->depths = $empty_depths;
		$required /** hash<bool> */ = [];
		foreach ($entries as $entry) {
			$this->known[$entry->header] = true;
			foreach ($entry->layout_dependencies as $header => $used) { $required[$header] = true; }
		}
		// Only headers reached by layout requirements participate; unrelated includes are not layout work.
		$required_before = 0;
		do {
			$required_before = q_count($required);
			foreach ($artifacts as $artifact) {
				if (($artifact->kind() !== cpp_artifact_kind::header) || !isset($required[$artifact->file_name])) { continue; }
				$this->known[$artifact->file_name] = true;
				foreach ($artifact->header_dependencies as $header => $used) { $required[$header] = true; }
			}
		} while (q_count($required) !== $required_before);
		while (q_count($this->depths) < q_count($this->known)) {
			$before = q_count($this->depths);
			foreach ($artifacts as $artifact) {
				if (!isset($this->known[$artifact->file_name]) || isset($this->depths[$artifact->file_name])) { continue; }
				$depth = $this->required_depth($artifact->header_dependencies);
				if ($depth >= 0) { $this->depths[$artifact->file_name] = $depth; }
			}
			foreach ($entries as $entry) {
				// Member fragments share the owning type header; only its layout entry determines readiness.
				if ($entry->member_owner !== '') { continue; }
				if (isset($this->depths[$entry->header])) { continue; }
				$depth = $this->required_depth($entry->layout_dependencies);
				if ($depth >= 0) { $this->depths[$entry->header] = $depth + ($entry->layout_statements === '' ? 0 : 1); }
			}
			if (q_count($this->depths) === $before) { throw new \RuntimeException('Concrete layout dependencies contain a generated header cycle'); }
		}
		$result /** hash<int> */ = [];
		foreach ($entries as $entry) {
			$result[$entry->name] = $this->depths[$entry->header] - ($entry->layout_statements === '' ? 0 : 1);
		}
		return $result;
	}
}
