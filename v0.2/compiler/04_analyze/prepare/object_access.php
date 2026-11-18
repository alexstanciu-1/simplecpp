<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Admit checked shared-class access using settled ancestry; occurrence owner retains all dependency watches. */
final class Object_Access_Preparation
{
	/** Resolve one target and observe both ancestry chains, including the source's inherited polymorphism. */
	public static function prepare(ast_object_access_expression $site, scope $symbols, ast_function_body $owner, ?Dependency_Watches $dependencies): void
	{
		if ($site->target->first_child !== null) self::fail($site, 'Object access target must be a bare class name');
		$target = Type_Resolution::local($site->target, $symbols, $owner, $dependencies);
		if (!($target instanceof shared_record_type_definition) || !$target->required
			|| $target->payload->declaration->kind !== record_kind::class_type) {
			self::fail($site, 'Object access target must be a class');
		}
		$source = $site->operand->type_def();
		if (!($source instanceof shared_record_type_definition) || $source->payload->declaration->kind !== record_kind::class_type) {
			self::fail($site, 'Object access operand must be a shared class');
		}
		foreach ([$source->payload, $target->payload] as $record) {
			for ($type = $record; $type !== null; $type = $type->base()) {
				$dependencies?->watch($symbols->entry($type->declaration->symbol_key()), $owner);
			}
		}
		$upward = $source->payload->derives_from($target->payload);
		if (!$upward && !$target->payload->derives_from($source->payload)) self::fail($site, 'Object access requires related classes');
		if (!$upward && !$source->payload->polymorphic()) self::fail($site, 'Checked downcast requires a polymorphic source class');
		$site->settle_access($target, !$upward);
	}

	private static function fail(ast_object_access_expression $site, string $message): never
	{
		throw new diagnostic($site->source(), $site->byte_start(), $message);
	}
}
