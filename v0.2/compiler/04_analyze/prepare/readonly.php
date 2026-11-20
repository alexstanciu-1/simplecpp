<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Stored-value write checks stop at reference-identity boundaries, never at inline value fields. */
final class Readonly_Access
{
	public static function field(ast_field_expression $site): ast_instance_field
	{
		$type = $site->receiver->type_def();
		if ($type instanceof shared_record_type_definition) $type = $type->payload;
		return $type->field($site->field) ?? throw new \LogicException('Unresolved field');
	}

	/** Validate a mutation path; only a direct, approved constructor initialization can bypass its own field. */
	public static function write(ast_expression $site, bool $payload = false, ?ast_instance_field $initializing = null): void
	{
		if ($payload && $site->type_def()->mutates_referenced_value()) return;
		while ($site instanceof ast_field_expression) {
			$field = self::field($site);
			if ($field->is_readonly && $field !== $initializing) {
				throw new diagnostic($site->source(), $site->byte_start(), 'Cannot modify readonly property ' . $field->short_name);
			}
			$site = $site->receiver;
			if ($site->type_def()->mutates_referenced_value()) return;
		}
	}
}

/** Transient constructor state: 0 uninitialized, 1 initialized, 2 path-dependent; no occurrence facts. */
final class Readonly_Initialization
{
	/** PROPERTIES */
	/** @var array<string,int> */
	public array $states /** array<string,uint8> */ = [];
	/** Native non-owning declaring record. */
	private readonly ast_record_declaration $record;

	/** CONSTRUCTOR */
	public function __construct(ast_record_declaration $record)
	{
		$this->record = $record;
		foreach ($record->fields as $field) {
			if ($field->is_readonly) $this->states[$field->short_name] = $field->initializer === null ? 0 : 1;
		}
	}

	/** METHODS */
	/** Recognize one direct constructor write before preparing its RHS; settlement follows successful conversion. */
	public function assignment(ast_field_expression $site, bool $in_loop): ?ast_instance_field
	{
		$field = Readonly_Access::field($site);
		if (!$field->is_readonly || !($site->receiver instanceof ast_this_expression) || $field->parent !== $this->record) return null;
		if ($in_loop || $this->states[$field->short_name] !== 0) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Readonly property must be initialized exactly once: ' . $field->short_name);
		}
		return $field;
	}

	public function read(ast_field_expression $site): void
	{
		if ($site->receiver instanceof ast_this_expression && isset($this->states[$site->field]) && $this->states[$site->field] !== 1) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Readonly property is not initialized: ' . $site->field);
		}
	}

	public function complete(ast_node $site): void
	{
		foreach ($this->states as $name => $state) {
			if ($state !== 1) throw new diagnostic($site->source(), $site->byte_start(), 'Readonly property is not initialized: ' . $name);
		}
	}
}
