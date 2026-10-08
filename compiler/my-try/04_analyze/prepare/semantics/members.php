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
			if (($modifier === declaration_modifier::public_access) || ($modifier === declaration_modifier::private_access)
				|| ($modifier === declaration_modifier::protected_access)) {
				if ($visibility) { throw new \RuntimeException('Duplicate or conflicting member visibility'); }
				$visibility = true;
			}
			elseif (($modifier === declaration_modifier::static_member) && $allow_static) {
				if ($has_static) { throw new \RuntimeException('Duplicate static modifier'); }
				$has_static = true;
			}
			else { throw new \RuntimeException('Member requires public, protected or private visibility and supported modifiers'); }
		}
	}

	public static function visibility(array $modifiers /** vector<declaration_modifier> */): member_visibility
	{
		foreach ($modifiers as $modifier)
		{
			if ($modifier === declaration_modifier::private_access) {
				return member_visibility::private_access;
			}
			if ($modifier === declaration_modifier::protected_access) {
				return member_visibility::protected_access;
			}
		}
		return member_visibility::public_access;
	}

	public static function is_static(array $modifiers /** vector<declaration_modifier> */): bool
	{
		foreach ($modifiers as $modifier) { if ($modifier === declaration_modifier::static_member) { return true; } }
		return false;
	}

	/** Qualified lookup needs nominal identity, not completion of the record's instance layout. */
	public static function owner(named_type_node $syntax, preparation_context $context): canonical_type_use
	{
		if ($syntax->name === 'static') {
			throw new \RuntimeException('Inheritance-dependent qualification is deferred to CH11');
		}
		if (($syntax->name === 'self') || ($syntax->name === 'parent')) {
			$record = $context->member_owner;
			if ($record === null) { throw new \RuntimeException('self requires a declaring type context'); }
			if ($syntax->name === 'parent') {
				$record = weakref_get($context->worker->require_relationships($record, $context)->base);
				if ($record === null) { throw new \RuntimeException('parent requires a concrete base'); }
			}
			$type = Model::$type_catalog->registry()->canonical(Source_Types::definition($record));
			$syntax->set_preparation(Model::$type_catalog->registry()->use($type->type_id()));
		}
		else { Type_Preparation::prepare_named_type($syntax, $context, false); }
		return $syntax->require_preparation();
	}

	/** Observe ancestry without requiring receiver layout or member signatures. */
	public static function derives_from(collected_member_owner $type, collected_member_owner $base, preparation_context $context): bool
	{
		$current /** collected_member_owner */ = $type;
		while (true) {
			if ($current === $base) { return true; }
			$relations = $context->worker->require_relationships($current, $context);
			$parent = weakref_get($relations->base);
			if ($parent === null) { return false; }
			$current = $parent;
		}
	}

	/** Instance receiver restrictions are checked separately from declaring-context access. */
	public static function accessible(member_visibility $visibility, collected_member_owner $owner, preparation_context $context): bool
	{
		if ($visibility === member_visibility::protected_access) {
			if ($context->member_owner === null) { return false; }
			return self::derives_from($context->member_owner, $owner, $context);
		}
		return ($visibility === member_visibility::public_access) || ($context->member_owner === $owner);
	}

	/** Match C++ protected-instance access; a descendant cannot use an arbitrary base receiver. */
	public static function require_instance_access(member_visibility $visibility, collected_member_owner $owner,
		collected_member_owner $receiver, preparation_context $context): void
	{
		self::require_access($visibility, $owner, $context);
		if ($visibility !== member_visibility::protected_access) { return; }
		$accessing /** collected_member_owner */ = $context->member_owner;
		if (!self::derives_from($receiver, $accessing, $context)) {
			throw new \RuntimeException('Protected instance access requires the accessing type or a descendant receiver');
		}
	}

	public static function require_access(member_visibility $visibility, collected_member_owner $owner, preparation_context $context): void
	{
		if (!self::accessible($visibility, $owner, $context)) {
			if ($visibility === member_visibility::protected_access) { throw new \RuntimeException('Protected member is not accessible here'); }
			throw new \RuntimeException('Private member is not accessible here');
		}
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
			$targets = Inherited_Lookup::members($record, $syntax->member_name, preparation_lookup_kind::constant, $context);
			if (q_count($targets) !== 1) { throw new \RuntimeException('Unknown or duplicate member constant'); }
			$selected = $targets[0];
			$target = $selected->member;
			if (!($target instanceof collected_constant)) { throw new \RuntimeException('Unknown member constant'); }
			$constant = object_cast($target, collected_constant::class);
			$context->worker->require_declaration($context->owner, $constant);
			self::require_access($constant->syntax()->require_preparation()->visibility, $selected->declaring_type, $context);
			return self::constant_reference($constant);
		}
		$context->worker->require_declaration($context->owner, $record);
		$name = string_byte_slice($syntax->member_name, 1, string_byte_len($syntax->member_name) - 1);
		$matches = Inherited_Lookup::members($record, $name, preparation_lookup_kind::property_name, $context);
		if (q_count($matches) !== 1) { throw new \RuntimeException('Qualified property access requires one static property'); }
		$selected_field = $matches[0];
		$context->worker->require_declaration($context->owner, $selected_field->declaring_type);
		$field = object_cast($selected_field->member, collected_field::class)->syntax()->require_preparation();
		if (!$field->static_member) { throw new \RuntimeException('Qualified property access requires one static property'); }
		self::require_access($field->visibility, $selected_field->declaring_type, $context);
		$facts = new prepared_static_property();
		$facts->field = $field;
		$declaring_type = Model::$type_catalog->registry()->canonical(Source_Types::definition($selected_field->declaring_type));
		$facts->record_type = Model::$type_catalog->registry()->use($declaring_type->type_id());
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
