<?php
namespace scpp\compiler;

/** Restricted untagged storage; eligibility observes completed declaration facts. */
final class Union_Preparation
{
	public static function shape(type_declaration_node $syntax): void
	{
		$methods /** Storage<method_node> */ = $syntax->methods;
		$constants /** Storage<member_constant_node> */ = $syntax->constants;
		$traits /** Storage<trait_use_node> */ = $syntax->use_traits;
		$interfaces /** Storage<named_type_node> */ = $syntax->implemented_interfaces;
		$extended /** Storage<named_type_node> */ = $syntax->extended_interfaces;
		foreach ($syntax->modifiers as $modifier) {
			if (($syntax->declaration_kind === type_declaration_kind::struct_type)
				&& ($modifier === declaration_modifier::final_declaration)) { continue; }
			throw new \RuntimeException('Restricted unions and their struct payloads cannot introduce construction or abstract modifiers');
		}
		if (!$methods->is_empty() || !$constants->is_empty() || !$traits->is_empty()
			|| !$interfaces->is_empty() || !$extended->is_empty()
			|| ($syntax->base_class !== null)) {
			throw new \RuntimeException('Restricted unions permit only public instance payload fields');
		}
	}

	public static function prepare(type_declaration_node $syntax, preparation_context $context): void
	{
		self::shape($syntax);
		$context->member_owner = object_cast($syntax->occurrence(), collected_record::class);
		$context->worker->require_relationships($context->member_owner, $context);
		$facts = new prepared_record();
		$fields /** Key_Storage_List<prepared_field> */ = $facts->fields;
		$nodes /** Storage<property_node> */ = $syntax->properties;
		foreach ($nodes as $node) {
			Member_Preparation::validate_modifiers($node->modifiers, true);
			if (($node->initializer !== null) || ($node->type_syntax === null)) {
				throw new \RuntimeException('Union payload fields require explicit types and no default initializers');
			}
			foreach ($node->modifiers as $modifier) {
				if ($modifier !== declaration_modifier::public_access) {
					throw new \RuntimeException('Union payload fields must be public instance fields');
				}
			}
			if (q_count($fields->named($node->name)) !== 0) {
				throw new \RuntimeException('Duplicate union payload field: ' . $node->name);
			}
			$type = Type_Preparation::type($node->require_type_syntax(), $context);
			self::require_payload($type, $context);
			$field = new prepared_field();
			$field->type = $type;
			$field->declaration = $node->occurrence();
			$node->set_preparation($field);
			$fields->add($node->name, $field);
		}
		$facts->capabilities = [generic_contract::value_storable, generic_contract::copyable_value];
		$syntax->set_preparation($facts);
	}

	/** Managed storage and nested unions are excluded even through a struct field. */
	private static function require_payload(canonical_type_use $type, preparation_context $context): void
	{
		if ($type->by_value()) { throw new \RuntimeException('Union payload cannot use storage modifiers'); }
		$definition = Type_Queries::canonical($type)->definition();
		if ($type->matches($context->boolean)) { return; }
		if (($definition instanceof integer_type_definition) && !$type->matches($context->integer)) { return; }
		$declaration = Type_Queries::source_declaration($type);
		if ($declaration instanceof collected_enum) {
			$enumeration = object_cast($declaration, collected_enum::class);
			$context->worker->require_declaration($context->owner, $enumeration);
			$backing = $enumeration->enum_syntax()->require_preparation()->backing_type;
			if ($backing !== null) {
				if (!$backing->matches($context->integer)) { return; }
			}
			throw new \RuntimeException('Union enum payload requires fixed integer backing');
		}
		if ($declaration instanceof collected_record) {
			$record = object_cast($declaration, collected_record::class);
			$syntax = $record->syntax();
			if ($syntax->declaration_kind === type_declaration_kind::struct_type) {
				// First-slice payload records have no authored lifecycle or inheritance.
				self::shape($syntax);
				$context->worker->require_declaration($context->owner, $record);
				$fields /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->fields;
				foreach ($fields->items() as $field) {
					if ($field->visibility !== member_visibility::public_access) {
						throw new \RuntimeException('Union struct payload fields must be public');
					}
					self::require_payload($field->type, $context);
				}
				$statics /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->static_fields;
				if (!$statics->is_empty()) { throw new \RuntimeException('Union struct payload cannot contain static fields'); }
				return;
			}
		}
		throw new \RuntimeException('Restricted union payload requires bool, fixed integer, fixed-backed enum or eligible struct');
	}
}
