<?php

/* Declaration-only lifecycle facts avoid layout/signature dependency cycles. */
namespace scpp\compiler;

final class Type_Lifecycle_Preparation
{
	public static function prepare(collected_member_owner $owner, preparation_context $context): prepared_type_lifecycle
	{
		$facts = new prepared_type_lifecycle();
		$count = 0;
		foreach (Member_Inventory::scopes($owner) as $members) {
			$context->worker->observe($members, '__destruct', preparation_lookup_kind::method_name, $context->owner);
		}
		// Selection is shared with declaration/signature preparation; observe hidden pools too.
		foreach (Member_Inventory::methods($owner) as $syntax) {
			if ($syntax->name !== '__destruct') { continue; }
			$count++;
			$facts->destructor_visibility = Member_Preparation::visibility($syntax->modifiers);
		}
		if ($count > 1) { throw new \RuntimeException('Duplicate destructor'); }
		$base = weakref_get($context->worker->require_relationships($owner, $context)->base);
		if ($base !== null) {
			$parent = $context->worker->require_lifecycle($base, $context);
			if ($parent->destructor_visibility === member_visibility::private_access) {
				throw new \RuntimeException('Private base destructor is not accessible to descendant cleanup');
			}
		}
		return $facts;
	}

	public static function same(?prepared_type_lifecycle $before, prepared_type_lifecycle $after): bool
	{
		if ($before === null) { return false; }
		return $before->destructor_visibility === $after->destructor_visibility;
	}

	/** Shared views retain an existing deleter; unique storage must itself be deletable. */
	public static function require_storage(canonical_type_use $type, preparation_context $context, bool $external = false): void
	{
		$storage = Type_Queries::record_storage($type);
		if (($storage === record_storage_kind::shared) || ($storage === record_storage_kind::weak)) { return; }
		$record = Type_Queries::source_record(Type_Queries::record_payload($type));
		if ($record !== null) {
			$visibility = $context->worker->require_lifecycle($record, $context)->destructor_visibility;
			// unique_p and explicit value<Class> perform cleanup in runtime-owned code.
			$runtime_cleanup = $external || ($storage === record_storage_kind::unique) || $type->by_value();
			$accessible = $visibility === member_visibility::public_access;
			if (!$runtime_cleanup) { $accessible = Member_Preparation::accessible($visibility, $record, $context); }
			if (!$accessible) { throw new \RuntimeException('Storage requires an accessible destructor'); }
			return;
		}
		$canonical = Type_Queries::canonical($type);
		if ($canonical instanceof applied_template_type) {
			foreach (object_cast($canonical, applied_template_type::class)->arguments() as $argument) {
				self::require_storage($argument, $context, true);
			}
		}
	}

	/** Allocation helpers cannot acquire destructor access from the source caller. */
	public static function require_construction(canonical_type_use $type, preparation_context $context): void
	{
		if (Type_Queries::record_storage($type) === record_storage_kind::inline_value) {
			self::require_storage($type, $context);
			return;
		}
		$record = Type_Queries::source_record(Type_Queries::record_payload($type));
		if ($record === null) { return; }
		if ($context->worker->require_lifecycle($record, $context)->destructor_visibility !== member_visibility::public_access) {
			throw new \RuntimeException('Allocation requires a public destructor');
		}
	}
}
