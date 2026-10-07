<?php

/* Concrete base-subobject admission; neither member flattening nor implicit source upcasts. */
namespace scpp\compiler;

final class Inheritance_Preparation
{
	/** Complete the base layout; authored child construction is prepared with its body. */
	public static function base(collected_record $entry, prepared_record $facts, preparation_context $context): void
	{
		$relationships = $context->worker->require_relationships($entry, $context);
		$base = weakref_get($relationships->base);
		if ($base === null) { return; }
		$context->worker->require_declaration($context->owner, $base);
		$canonical = Model::$type_catalog->registry()->canonical(Source_Types::definition($base));
		$base_type = Model::$type_catalog->registry()->use($canonical->type_id());
		if (Type_Queries::is_class_handle($base_type)) {
			$base_type = Model::$type_catalog->registry()->use($canonical->type_id(), true);
		}
		$facts->base_type = $base_type;
		if (weakref_get($base->syntax()->require_preparation()->constructor) === null) {
			if (!Object_Construction_Preparation::supports_default($base_type, $context)) {
				throw new \RuntimeException('Inheritance requires a default-constructible base');
			}
		}
		self::reject_redeclarations($entry->syntax(), $base, $context);
	}

	/** No synthesized callable identity: construction observes the original ancestor signature. */
	public static function inherit_constructor(prepared_record $facts, preparation_context $context): void
	{
		if (($facts->base_type === null) || (weakref_get($facts->constructor) !== null)) { return; }
		$base /** collected_record */ = Type_Queries::source_record($facts->base_type);
		$constructor = weakref_get($base->syntax()->require_preparation()->constructor);
		if ($constructor === null) { return; }
		$fields /** Key_Storage_List<prepared_field> */ = $facts->fields;
		foreach ($fields->items() as $field) {
			if ($field->default_value === null) {
				if (!Object_Construction_Preparation::supports_default($field->type, $context)) {
					throw new \RuntimeException('Inherited construction requires default-constructible child properties');
				}
			}
		}
		$facts->constructor = $constructor;
	}

	/** Lifecycle methods are not inherited ordinary members; their bodies construct/destroy subobjects. */
	private static function reject_redeclarations(type_declaration_node $syntax, collected_record $base,
		preparation_context $context): void
	{
		$properties /** Storage<property_node> */ = $syntax->properties;
		foreach ($properties as $property) {
			self::require_new_name($base, $property->name, preparation_lookup_kind::property_name, $context);
		}
		$methods /** Storage<method_node> */ = $syntax->methods;
		foreach ($methods as $method) {
			if (($method->name === '__construct') || ($method->name === '__destruct')) { continue; }
			self::require_new_name($base, $method->name, preparation_lookup_kind::method_name, $context);
		}
		$constants /** Storage<member_constant_node> */ = $syntax->constants;
		foreach ($constants as $constant) {
			self::require_new_name($base, $constant->name, preparation_lookup_kind::constant, $context);
		}
	}

	private static function require_new_name(collected_record $base, string $name, preparation_lookup_kind $kind,
		preparation_context $context): void
	{
		if (q_count(Inherited_Lookup::members($base, $name, $kind, $context)) !== 0) {
			throw new \RuntimeException('Inherited member redeclaration is deferred: ' . $name);
		}
	}
}
