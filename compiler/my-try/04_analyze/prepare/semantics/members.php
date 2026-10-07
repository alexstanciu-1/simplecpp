<?php

/* Shared modifier and qualified-member rules; member kinds retain their own semantic owners. */
namespace scpp\compiler;

final class Member_Preparation
{
	public static function validate_modifiers(array $modifiers /** vector<declaration_modifier> */, bool $allow_static): void
	{
		$visibility = false;
		$has_static = false;
		foreach ($modifiers as $modifier) {
			if (($modifier === declaration_modifier::public_access) || ($modifier === declaration_modifier::private_access)) {
				if ($visibility) { throw new \RuntimeException('Duplicate or conflicting member visibility'); }
				$visibility = true;
			}
			elseif (($modifier === declaration_modifier::static_member) && $allow_static) {
				if ($has_static) { throw new \RuntimeException('Duplicate static modifier'); }
				$has_static = true;
			}
			else { throw new \RuntimeException('Member requires public or private visibility and supported modifiers'); }
		}
	}

	public static function is_public(array $modifiers /** vector<declaration_modifier> */): bool
	{
		foreach ($modifiers as $modifier) { if ($modifier === declaration_modifier::private_access) { return false; } }
		return true;
	}

	public static function is_static(array $modifiers /** vector<declaration_modifier> */): bool
	{
		foreach ($modifiers as $modifier) { if ($modifier === declaration_modifier::static_member) { return true; } }
		return false;
	}

	/** Qualified lookup needs nominal identity, not completion of the record's instance layout. */
	public static function owner(named_type_node $syntax, preparation_context $context): canonical_type_use
	{
		if (($syntax->name === 'parent') || ($syntax->name === 'static')) {
			throw new \RuntimeException('Inheritance-dependent qualification is deferred to CH11');
		}
		if ($syntax->name === 'self') {
			$record = $context->receiver_record;
			if ($record === null) { throw new \RuntimeException('self requires a declaring type context'); }
			$type = Model::$type_catalog->registry()->canonical(Source_Types::definition($record));
			$syntax->set_preparation(Model::$type_catalog->registry()->use($type->type_id()));
		}
		else { Type_Preparation::prepare_named_type($syntax, $context, false); }
		return $syntax->require_preparation();
	}

	public static function require_access(bool $public_access, collected_record $record, preparation_context $context): void
	{
		if (!$public_access && ($context->receiver_record !== $record)) { throw new \RuntimeException('Private member is not accessible here'); }
	}

	public static function reference(static_member_reference_node $syntax, preparation_context $context): prepared_expression
	{
		$type /** canonical_type_use */;
		if ($syntax->qualifier !== null) {
			$value = Method_Preparation::base($syntax->qualifier, $context);
			$type = Type_Queries::record_payload($value->type);
			if (Type_Queries::source_record($type) === null) {
				throw new \RuntimeException('Expression qualification requires a concrete class or struct');
			}
		}
		else { $type = self::owner($syntax->require_type_owner(), $context); }
		$record = Type_Queries::source_record($type);
		$property = string_byte_starts_with($syntax->member_name, '$');
		if ($record === null) {
			if (!$property) { return Enum_Preparation::reference($syntax, $context); }
			// Installed readonly constants such as error::$no_error keep their registered contract.
			$owner = $syntax->require_type_owner();
			$lookup_scope = object_cast(weakref_get($owner->occurrence()->scope), scope::class);
			$installed = Observed_Lookup::constants($lookup_scope, $owner->name . '::' . $syntax->member_name, $context);
			if (q_count($installed) !== 1) { throw new \RuntimeException('Unknown installed static member'); }
			return self::constant_reference($installed[0]);
		}
		if (!$property) {
			$targets = Observed_Lookup::constants($record->syntax()->member_scope(), $syntax->member_name, $context);
			if (q_count($targets) !== 1) { throw new \RuntimeException('Unknown or duplicate member constant'); }
			$target = $targets[0];
			if (!($target instanceof collected_constant)) { throw new \RuntimeException('Unknown member constant'); }
			$constant = object_cast($target, collected_constant::class);
			if (weakref_get($constant->record_owner) !== $record) { throw new \RuntimeException('Unknown member constant'); }
			$context->worker->require_declaration($context->owner, $constant);
			self::require_access($constant->syntax()->require_preparation()->public_access, $record, $context);
			return self::constant_reference($constant);
		}
		$context->worker->require_declaration($context->owner, $record);
		$fields /** Key_Storage_List<prepared_field> */ = $record->syntax()->require_preparation()->static_fields;
		$name = string_byte_slice($syntax->member_name, 1, string_byte_len($syntax->member_name) - 1);
		$matches /** vector<prepared_field> */ = $fields->named($name);
		if (q_count($matches) !== 1) { throw new \RuntimeException('Qualified property access requires one static property'); }
		$field = $matches[0];
		self::require_access($field->public_access, $record, $context);
		$facts = new prepared_static_property();
		$facts->field = $field;
		$facts->record_type = $type;
		$facts->type = $field->type;
		// Static storage is stable, but native-reference escape is outside this bounded slice.
		$facts->place = new prepared_place($field, null, true);
		return $facts;
	}

	private static function constant_reference(constant_target_i $target): prepared_constant_reference
	{
		$facts = new prepared_constant_reference();
		$facts->target = $target;
		$facts->value = $target->value();
		$facts->type = $facts->value->type;
		return $facts;
	}
}
