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

	/** Compatibility recipe selection stays with the existing recipe owner, not this engine. */
	public function request(canonical_type_use $type): cpp_specialization_ref
	{
		return $this->intern(CPP_Concrete_Recipes::request($type, $this));
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
		return $entries[$reference->entry_id];
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

	/** Existing expression emission still uses the first-slice recipe protocol. */
	public function representation(canonical_type_use $type, cpp_generation_context $context): cpp_type
	{
		return CPP_Concrete_Recipes::representation($type, $context, $this);
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

	/** Group implementation work, as in the accounting proof; headers remain per representation. */
	public function append_artifacts(Storage $artifacts /** Storage<cpp_artifact> */): void
	{
		$artifacts->append(new cpp_artifact('specializations:base-header', cpp_artifact_kind::header, 'specializations/base.hpp', CPP_Concrete_Recipes::base_header()));
		$artifacts->append(new cpp_artifact('specializations:base', cpp_artifact_kind::translation_unit, 'specializations/base.cpp', CPP_Concrete_Recipes::base_body()));
		$implementation = '';
		$layout_declarations = '';
		$layout_statements = '';
		$entries /** Storage<cpp_specialization_entry> */ = $this->entries;
		foreach ($this->emission_order() as $id) {
			$entry = $entries[$id];
			$layout_declarations .= $entry->layout_declarations;
			$layout_statements .= $entry->layout_statements;
			$artifacts->append(new cpp_artifact('specialization:' . $entry->name, cpp_artifact_kind::header,
			$entry->header, $entry->declaration));
			$implementation .= '#include "' . $entry->header . '"' . "\n" . $entry->implementation . "\n";
		}
		if ($layout_statements !== '') {
			$probe = '#include <cstdio>' . "\n" . '#include <type_traits>' . "\n" . $layout_declarations
				. 'int main() { std::printf("// scpp-native-layout-v1\\n");' . "\n" . $layout_statements . "}\n";
			$artifacts->append(new cpp_artifact('specializations:layout', cpp_artifact_kind::layout_probe,
				'specializations/layout.cpp', $probe));
		}
		if ($implementation !== '') {
			$artifacts->append(new cpp_artifact('specializations:implementation', cpp_artifact_kind::translation_unit,
			'specializations/containers.cpp', $implementation));
		}
	}
}
