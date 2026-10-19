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

	/** The same family recipe consumes every admitted argument; no concrete-type switch. */
	public function request(canonical_type_use $type): cpp_specialization_ref
	{
		$canonical = Type_Queries::canonical($type);
		$definition = $canonical->definition();
		if ($definition instanceof integer_type_definition)
		{
			$integer = object_cast($definition, integer_type_definition::class);
			$name = ($integer->signed() ? 'int' : 'uint') . $integer->bit_width();
			if (!isset($this->names[$name])) {
				$this->install(CPP_Concrete_Recipes::integer($name, $integer->signed(), $integer->bit_width()));
			}
			return $this->reference($this->names[$name]);
		}
		if ($canonical instanceof applied_template_type)
		{
			$application = object_cast($canonical, applied_template_type::class);
			if ($definition !== Model::$type_catalog->definition('vector')) {
				throw new \RuntimeException('prelower_templates: unsupported template family ' . $definition->name());
			}
			$element = $application->arguments()[0];
			if (!(new Capability_Facts())->has_capability($element, generic_contract::copyable_value)) {
				throw new \RuntimeException('prelower_templates: vector recipe currently requires copyable elements');
			}
			$context = new cpp_generation_context($this);
			$mapping = CPP_Types::representation($element, $context);
			$key = $this->entry($this->request(Model::$type_catalog->registry()->use(Model::$type_catalog->canonical('int')->type_id())));
			$suffix = '';
			if ($mapping->specialization !== null) {
				$suffix = $this->entry($this->request($element))->name;
			}
			else {
				$suffix = CPP_Artifacts::component($mapping->spelling);
			}
			$name = 'vector_' . $suffix;
			if (!isset($this->names[$name])) {
				$this->install(CPP_Concrete_Recipes::vector($name, $mapping, $key));
			}
			return $this->reference($this->names[$name]);
		}
		throw new \RuntimeException('prelower_templates: no recipe for ' . $definition->name());
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

	/** Ordinary nontemplate representations retain the existing binding, without another semantic pass. */
	public function representation(canonical_type_use $type, cpp_generation_context $context): cpp_type
	{
		$canonical = Type_Queries::canonical($type);
		if (($canonical->family() !== type_family::integer) && !($canonical instanceof applied_template_type)) {
			return CPP_Types::representation($type);
		}
		$context->specializations[$type->type_id()] = $type;
		$entry = $this->entry($this->request($type));
		$result = new cpp_type();
		$result->spelling = 'scpp::internal::' . $entry->name;
		$result->headers[$entry->header] = true;
		$result->literal = $canonical->family() === type_family::integer ? cpp_literal_kind::signed_integer : cpp_literal_kind::none;
		$result->specialization = $type;
		return $result;
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
		$entries /** Storage<cpp_specialization_entry> */ = $this->entries;
		foreach ($this->emission_order() as $id) {
			$entry = $entries[$id];
			$artifacts->append(new cpp_artifact('specialization:' . $entry->name, cpp_artifact_kind::header,
			$entry->header, $entry->declaration));
			$implementation .= '#include "' . $entry->header . '"' . "\n" . $entry->implementation . "\n";
		}
		if ($implementation !== '') {
			$artifacts->append(new cpp_artifact('specializations:implementation', cpp_artifact_kind::translation_unit,
			'specializations/containers.cpp', $implementation));
		}
	}
}
