<?php
namespace scpp\compiler;

/** Application optimization intent; independent of compiler executable optimization. */
final class cpp_lowering_policy
{
	public function __construct(public readonly int $optimization = 2)
	{
		if (($optimization < 0) || ($optimization > 3)) {
			throw new \InvalidArgumentException('C++ optimization must be 0, 1, 2 or 3');
		}
	}

	public function outline_defaults(): bool { return $this->optimization < 2; }
	public function identity(): string
	{
		return $this->outline_defaults() ? 'cpp-lowering-v1:latency' : 'cpp-lowering-v1:execution';
	}
}

/** Backend placement of an existing initialization contract, never a new source constructor. */
final class CPP_Default_Constructors
{
	public static function eligible(collected_record $record, cpp_lowering_policy $policy): bool
	{
		if (!$policy->outline_defaults()) { return false; }
		$syntax = $record->syntax();
		if ($syntax->declaration_kind !== type_declaration_kind::class_type) { return false; }
		$layout = $syntax->require_preparation();
		$constructor = weakref_get($layout->constructor);
		if (($layout->base_type !== null) || ($constructor !== null) || ($layout->initialization !== null)) { return false; }
		$contract = $record->contract_work->require_facts();
		if ($contract->polymorphic || $contract->abstract_type) { return false; }
		$methods /** Key_Storage_List<collected_method> */ = $layout->methods;
		if (q_count($methods->named('__destruct')) !== 0) { return false; }
		$found_container = false;
		$fields /** Key_Storage_List<prepared_field> */ = $layout->fields;
		foreach ($fields->items() as $field) {
			$default_value = $field->default_value;
			// Explicit defaults avoid changing value-initialization of otherwise uninitialized fields.
			if ($default_value === null) { return false; }
			if ($default_value instanceof prepared_constant_property_default) { continue; }
			$construction = object_cast($default_value, prepared_construction_property_default::class)->construction;
			$entries /** Storage<data_entry_node> */ = $construction->entries;
			if (!$entries->is_empty()) { return false; }
			if (!($construction->require_data_preparation() instanceof prepared_sequence_construction)) { return false; }
			$definition = Type_Queries::canonical($field->type)->definition();
			if (($definition !== Model::$type_catalog->definition('hash')) && ($definition !== Model::$type_catalog->definition('vector'))) { return false; }
			$found_container = true;
		}
		return $found_container;
	}

	public static function definition(collected_record $record, cpp_generation_context $context): string
	{
		$name = CPP_Generator::source_name('record', $record->qualified_name());
		$text = $name . '::' . $name . '()';
		$separator = ' : ';
		$fields /** Key_Storage_List<prepared_field> */ = $record->syntax()->require_preparation()->fields;
		foreach ($fields->items() as $field) {
			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$text .= $separator . CPP_Generator::source_name('field', $entry->name) . '('
				. CPP_Declarations::property_default($field->default_value, $context) . ')';
			$separator = ', ';
		}
		return $text . "\n{\n}\n\n";
	}
}
