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

	/** Borrowed capture chains preserve the owning binding's access mode. */
	public static function readonly_origin(?ast_local_binding $binding): ?ast_local_binding
	{
		while ($binding instanceof ast_closure_capture && $binding->by_reference) {
			$binding = $binding->initializer->local_def();
		}
		return $binding instanceof ast_closure_capture
			|| ($binding instanceof ast_parameter_declaration && $binding->mode === passing_mode::readonly_reference) ? $binding : null;
	}

	public static function require_writable(ast_node $site, ?ast_local_binding $binding): void
	{
		$origin = self::readonly_origin($binding);
		if ($origin !== null) {
			$message = $origin instanceof ast_closure_capture ? 'Cannot write to by-value capture: ' : 'Cannot write through read-only reference: ';
			throw new diagnostic($site->source(), $site->byte_start(), $message . $origin->short_name);
		}
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
		$storage = $site instanceof ast_index_expression ? $site->receiver : $site;
		if ($storage->type_def() instanceof segment_type && $storage->type_def()->read_only)
			throw new diagnostic($site->source(), $site->byte_start(), 'Cannot write through a read-only segment');
		if ($site instanceof ast_name_expression) self::require_writable($site, $site->local_binding());
	}
}

/** Transient presence and readonly initialization of fields owned by the current constructor. */
final class Constructor_Initialization
{
	/** PROPERTIES */
	/** Field states: 0 uninitialized, 1 initialized, 2 path-dependent. @var array<string,int> */
	public array $states /** array<string,uint8> */ = [];
	/** Native non-owning declaring record. */
	private readonly ast_record_declaration $record;

	/** CONSTRUCTOR */
	/** Seed the tracked fields from their declaration defaults. */
	public function __construct(ast_record_declaration $record)
	{
		$this->record = $record;
		foreach ($record->fields as $field) {
			$type = $field->type_def();
			if ($field->is_readonly || ($type instanceof shared_record_type_definition && $type->required)) {
				$this->states[$field->short_name] = $field->initializer === null ? 0 : 1;
			}
		}
	}

	/** METHODS */
	/** Recognize one direct constructor write before preparing its RHS; settlement follows successful conversion. */
	public function assignment(ast_field_expression $site, bool $in_loop): ?ast_instance_field
	{
		$field = Readonly_Access::field($site);
		$state = $this->states[$field->short_name] ?? null;
		if ($state === null || !($site->receiver instanceof ast_this_expression) || $field->parent !== $this->record) return null;
		if ($field->is_readonly && ($in_loop || $state !== 0)) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Readonly property must be initialized exactly once: ' . $field->short_name);
		}
		if ($state === 1) return null;
		if ($in_loop) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Required property must be initialized outside a loop: ' . $field->short_name);
		}
		return $field;
	}

	/** Check direct reads of tracked fields on the current instance. */
	public function read(ast_field_expression $site, ast_instance_field $field): void
	{
		if ($site->receiver instanceof ast_this_expression && $field->parent === $this->record
			&& ($this->states[$field->short_name] ?? 1) !== 1) {
			$this->uninitialized($site, $field);
		}
	}

	public function complete(ast_node $site): void
	{
		foreach ($this->states as $name => $state) {
			if ($state !== 1) $this->uninitialized($site, $this->record->fields[$name]);
		}
	}

	private function uninitialized(ast_node $site, ast_instance_field $field): never
	{
		$contract = $field->is_readonly ? 'Readonly' : 'Required';
		throw new diagnostic($site->source(), $site->byte_start(), $contract . ' property is not initialized: ' . $field->short_name);
	}
}
