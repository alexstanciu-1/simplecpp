<?php
namespace scpp\compiler;

/** Synchronous backend service. Canonical requests never mutate source/preparation facts. */
final class CPP_Specializations
{
	private Storage $entries /** Storage<cpp_specialization_entry> */;
	private array $names /** hash<int> */ = [];

	public function __construct()
	{
		$this->entries = new Storage /** Storage<cpp_specialization_entry> */();
	}

	public function request_operation(cpp_runtime_operation $request): cpp_specialization_ref
	{
		if ($request->kind === cpp_runtime_operation_kind::self_storage) {
			return $this->intern(CPP_Self::storage_entry($request, $this));
		}
		if ($request->kind === cpp_runtime_operation_kind::finally_storage) {
			return $this->intern(CPP_Finally::storage_entry($request, $this));
		}
		if ($request->kind === cpp_runtime_operation_kind::concrete_member) {
			return $this->intern(CPP_Concrete_Members::entry($request, $this));
		}
		if ($request->kind === cpp_runtime_operation_kind::concrete_index) {
			return $this->intern(CPP_Concrete_Index::entry($request, $this));
		}
		if ($request->kind === cpp_runtime_operation_kind::concrete_iteration) {
			return $this->intern(CPP_Concrete_Iteration::entry($request, $this));
		}
		if ($request->kind === cpp_runtime_operation_kind::concrete_mutation) {
			return $this->intern(CPP_Concrete_Mutations::entry($request, $this));
		}
		return $this->intern(CPP_Runtime_Operations::entry($request, $this));
	}

	/** Every public request uses the general concrete representation. */
	public function request(canonical_type_use $type): cpp_specialization_ref
	{
		return $this->request_composed($type);
	}

	private function intern(cpp_specialization_entry $entry): cpp_specialization_ref
	{
		if (!isset($this->names[$entry->name])) { $this->install($entry); }
		return $this->reference($this->names[$entry->name]);
	}

	/** Runtime ABI request from settled types; the representation worker uses existing bindings. */
	public function request_opaque(canonical_type_use $type): cpp_specialization_ref
	{
		return $this->intern(CPP_Opaque_Representation::entry($type, $this));
	}

	/** Integration entry for concrete physical arguments; no additional retained engine or registry. */
	public function request_composed(canonical_type_use $type): cpp_specialization_ref
	{
		return $this->intern(CPP_Opaque_Representation::entry($type, $this, true));
	}

	public function request_observation(prepared_call $call): cpp_specialization_ref
	{
		$parameters /** Storage<callable_parameter> */ = $call->signature->parameters;
		// Validate operation shape before requesting its owned representations.
		$entry = CPP_Opaque_Representation::observation($call, $this);
		$this->request_opaque($parameters[0]->type);
		$this->request_opaque($call->signature->return_type);
		return $this->intern($entry);
	}

	/** Construct a fresh value reference; callers never mutate an acquired entry ID. */
	private function reference(int $index): cpp_specialization_ref
	{
		$reference = new cpp_specialization_ref();
		$reference->entry_id = $index;
		return $reference;
	}

	public function entry(cpp_specialization_ref $reference): cpp_specialization_entry
	{
		$entries /** Storage<cpp_specialization_entry> */ = $this->entries;
		return $entries[(int)$reference->entry_id];
	}

	/** Avoid rendering an already requested entry; this index owns no separate semantic facts. */
	public function find(string $name): ?cpp_specialization_entry
	{
		if (!isset($this->names[$name])) { return null; }
		$entries /** Storage<cpp_specialization_entry> */ = $this->entries;
		return $entries[$this->names[$name]];
	}

	/** Keep index and entry ownership synchronized; IDs fit the compact reference. */
	private function install(cpp_specialization_entry $entry): void
	{
		$entries /** Storage<cpp_specialization_entry> */ = $this->entries;
		if ($entries->count() >= 4294967295) {
			throw new \RuntimeException('prelower_templates: specialization reference capacity exceeded');
		}
		$this->names[$entry->name] = $entries->count();
		$entries->append($entry);
	}

	/** Select the configured physical binding without implementing its storage protocol here. */
	public function representation(canonical_type_use $type, cpp_generation_context $context): cpp_type
	{
		return CPP_Opaque_Representation::representation($type, $this, true);
	}

	/** Order an output-only index so discovery and cached-fragment replay cannot churn support bytes. */
	private function emission_order(): array /** vector<int> */
	{
		$entries /** Storage<cpp_specialization_entry> */ = $this->entries;
		$order /** vector<int> */ = [];
		foreach ($entries as $id => $entry)
		{
			$position = q_count($order);
			$order[] = $id;
			while ($position > 0)
			{
				$previous = $order[$position - 1];
				if ($entries[$previous]->name < $entry->name) {
					break;
				}
				$order[$position] = $previous;
				$position -= 1;
			}
			$order[$position] = $id;
		}
		return $order;
	}

	/** Native headers need no generated layout; generated headers use the emitter's dependency facts. */
	private static function required_depth(array $dependencies /** hash<bool> */, array $known /** hash<bool> */,
		array $depths /** hash<int> */): int
	{
		$depth = 0;
		foreach ($dependencies as $header => $used) {
			if (!isset($known[$header])) { continue; }
			if (!isset($depths[$header])) { return -1; }
			if ($depths[$header] > $depth) { $depth = $depths[$header]; }
		}
		return $depth;
	}

	/** Topological layout batches include shared source headers, not source method bodies. */
	private function layout_depths(Storage $artifacts /** Storage<cpp_artifact> */): array /** hash<int> */
	{
		$known /** hash<bool> */ = [];
		$depths /** hash<int> */ = [];
		$entries /** Storage<cpp_specialization_entry> */ = $this->entries;
		$required /** hash<bool> */ = [];
		foreach ($entries as $entry) {
			$known[$entry->header] = true;
			foreach ($entry->layout_dependencies as $header => $used) { $required[$header] = true; }
		}
		// Only headers reached by layout requirements participate; unrelated includes are not layout work.
		$required_before = 0;
		do {
			$required_before = q_count($required);
			foreach ($artifacts as $artifact) {
				if (($artifact->kind() !== cpp_artifact_kind::header) || !isset($required[$artifact->file_name])) { continue; }
				$known[$artifact->file_name] = true;
				foreach ($artifact->header_dependencies as $header => $used) { $required[$header] = true; }
			}
		} while (q_count($required) !== $required_before);
		while (q_count($depths) < q_count($known)) {
			$before = q_count($depths);
			foreach ($artifacts as $artifact) {
				if (!isset($known[$artifact->file_name]) || isset($depths[$artifact->file_name])) { continue; }
				$depth = self::required_depth($artifact->header_dependencies, $known, $depths);
				if ($depth >= 0) { $depths[$artifact->file_name] = $depth; }
			}
			foreach ($entries as $entry) {
				// Member fragments share the owning type header; only its layout entry determines readiness.
				if ($entry->member_owner !== '') { continue; }
				if (isset($depths[$entry->header])) { continue; }
				$depth = self::required_depth($entry->layout_dependencies, $known, $depths);
				if ($depth >= 0) { $depths[$entry->header] = $depth + ($entry->layout_statements === '' ? 0 : 1); }
			}
			if (q_count($depths) === $before) { throw new \RuntimeException('Concrete layout dependencies contain a generated header cycle'); }
		}
		$result /** hash<int> */ = [];
		foreach ($entries as $entry) {
			$result[$entry->name] = $depths[$entry->header] - ($entry->layout_statements === '' ? 0 : 1);
		}
		return $result;
	}

	/** Group implementation work, as in the accounting proof; headers remain per representation. */
	public function append_artifacts(Storage $artifacts /** Storage<cpp_artifact> */): void
	{
		$artifacts->append(new cpp_artifact('specializations:base-header', cpp_artifact_kind::header, 'specializations/base.hpp', CPP_Entry_Support::base_header()));
		$artifacts->append(new cpp_artifact('specializations:base', cpp_artifact_kind::translation_unit, 'specializations/base.cpp', CPP_Entry_Support::base_body()));
		$implementation = '';
		$layout_declarations /** hash<string,int> */ = [];
		$layout_statements /** hash<string,int> */ = [];
		$depths = $this->layout_depths($artifacts);
		$maximum_depth = 0;
		$entries /** Storage<cpp_specialization_entry> */ = $this->entries;
		$order = $this->emission_order();
		$member_prefixes /** hash<string> */ = [];
		$member_declarations /** hash<string> */ = [];
		// Invocation-local assembly index; retained requests stay with existing fragments.
		foreach ($order as $id) {
			$member = $entries[$id];
			if ($member->member_owner === '') { continue; }
			$owner = $member->member_owner;
			if (!isset($member_prefixes[$owner])) {
				$member_prefixes[$owner] = '';
				$member_declarations[$owner] = '';
			}
			$member_prefixes[$owner] .= $member->declaration_prefix;
			$member_declarations[$owner] .= $member->declaration;
		}
		foreach ($order as $id) {
			$entry = $entries[$id];
			$depth = $depths[$entry->name];
			if ($depth > $maximum_depth) { $maximum_depth = $depth; }
			if (!isset($layout_declarations[$depth])) {
				$layout_declarations[$depth] = '';
				$layout_statements[$depth] = '';
			}
			$layout_declarations[$depth] .= $entry->layout_declarations;
			$layout_statements[$depth] .= $entry->layout_statements;
			if ($entry->member_owner === '') {
				$prefix = '';
				$members = '';
				if (isset($member_prefixes[$entry->name])) {
					$prefix = $member_prefixes[$entry->name];
					$members = $member_declarations[$entry->name];
				}
				$declaration = ($prefix === '' ? '' : "#pragma once\n" . $prefix) . $entry->declaration
					. $members . $entry->declaration_tail;
				$artifacts->append(new cpp_artifact('specialization:' . $entry->name, cpp_artifact_kind::header,
					$entry->header, $declaration));
				if ($entry->declaration_tail !== '') {
					$artifacts->append(new cpp_artifact('specialization-declaration:' . $entry->name, cpp_artifact_kind::header,
						CPP_Opaque_Representation::declaration_header('scpp::internal::' . $entry->name),
						"#pragma once\nnamespace scpp::internal { struct " . $entry->name . "; }\n"));
				}
			}
			$implementation .= '#include "' . $entry->header . '"' . "\n" . $entry->implementation . "\n";
		}
		for ($depth = 0; $depth <= $maximum_depth; $depth++) {
			if (!isset($layout_statements[$depth])) {
				continue;
			}
			$statements = $layout_statements[$depth];
			if ($statements === '') { continue; }
			$probe = '#include <cstdio>' . "\n" . '#include <type_traits>' . "\n" . $layout_declarations[$depth]
				. 'int main() { std::printf("// scpp-native-layout-v1\\n");' . "\n" . $statements . "}\n";
			$artifacts->append(new cpp_artifact('specializations:layout' . ($depth === 0 ? '' : ':' . $depth), cpp_artifact_kind::layout_probe,
				'specializations/layout' . ($depth === 0 ? '' : '_' . $depth) . '.cpp', $probe));
		}
		if ($implementation !== '') {
			$artifacts->append(new cpp_artifact('specializations:implementation', cpp_artifact_kind::translation_unit,
			'specializations/containers.cpp', $implementation));
		}
	}
}
