<?php

/* Resolve declaration signatures and member/call boundaries before preparing function bodies. */
namespace scpp\compiler;

final class Declaration_Preparation
{
	/** Named syntax resolves through the existing lexical/publication scope chain. */
	public static function type(ast_node $node): type_definition
	{
		if ($node->kind() !== node_kind::identifier) {
			throw new \RuntimeException('S2S constructed types are not supported yet');
		}
		$entry = $node->payload()->occurrence();
		$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
		$types = Scope_Lookup::types($lexical_scope, $entry->name);
		if (q_count($types) !== 1) {
			throw new \RuntimeException('S2S needs one resolved type for ' . $entry->name);
		}
		return $types[0];
	}

	/** Publish a complete signature before any body so calls do not depend on source order. */
	public static function prepare_function(function_structure $syntax, preparation_context $context): void
	{
		if (q_count($syntax->template_parameters) !== 0) {
			throw new \RuntimeException('S2S function templates are deferred');
		}
		$facts = new prepared_function();
		$facts->return_type = self::type($syntax->return_type);
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		$nodes /** Storage<ast_node> */ = $syntax->parameters;
		foreach ($nodes as $node)
		{
			$parameter = Syntax_Nodes::parameter_data($node);
			$prepared = new prepared_parameter();
			$prepared->declaration = $parameter->occurrence();
			$prepared->type = self::type($parameter->type_syntax);
			self::require_value_type($prepared->type);
			$prepared->mode = $parameter->mode;
			$parameter->set_preparation($prepared);
			$parameters->append($prepared);
		}
		$syntax->set_preparation($facts);
	}

	/** Fields belong to their record, never to the surrounding local-variable scope. */
	public static function prepare_struct(struct_structure $syntax, preparation_context $context): void
	{
		$facts = new prepared_record();
		$fields /** Key_Storage_List<prepared_field> */ = $facts->fields;
		$nodes /** Storage<ast_node> */ = $syntax->fields;
		foreach ($nodes as $node)
		{
			$field = Syntax_Nodes::field_data($node);
			$prepared = new prepared_field();
			$prepared->declaration = $field->occurrence();
			$prepared->type = self::type($field->type_syntax);
			$type = $prepared->type;
			$fixed_integer = ($type->kind === type_kind::integer) && ($type->name !== 'int');
			if ((!$fixed_integer) && ($type->kind !== type_kind::boolean) && ($type->kind !== type_kind::record)) {
				throw new \RuntimeException('S2S struct fields require bool, fixed-width integers or supported structs');
			}
			$field->set_preparation($prepared);
			$fields->add($field->occurrence()->name, $prepared);
		}
		$syntax->set_preparation($facts);
	}

	/** Each body receives fresh locals; parameters enter before source-order bindings. */
	public static function prepare_body(function_structure $syntax, preparation_context $outer): void
	{
		$context = new preparation_context();
		$context->collection = $outer->collection;
		$context->integer = $outer->integer;
		$context->boolean = $outer->boolean;
		$context->floating = $outer->floating;
		$context->locals = new Key_Storage_List /** Key_Storage_List<prepared_storage> */();
		$signature = $syntax->require_preparation();
		$context->return_type = $signature->return_type;
		$locals /** Key_Storage_List<prepared_storage> */ = $context->locals;
		$parameters /** Storage<prepared_parameter> */ = $signature->parameters;
		foreach ($parameters as $parameter) {
			$entry = object_cast(weakref_get($parameter->declaration), collected_name::class);
			$locals->add($entry->name, $parameter);
		}
		File_Preparation::prepare_statements($syntax->body, $context);
	}

	/** Resolve one named callable and establish reference/value argument boundaries. */
	public static function prepare_call(call_structure $syntax, preparation_context $context): prepared_call
	{
		$templates /** Storage<ast_node> */ = $syntax->template_arguments;
		if (!$templates->is_empty()) {
			throw new \RuntimeException('S2S template calls are deferred');
		}
		$entry = $syntax->occurrence();
		$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
		$targets = Scope_Lookup::functions($lexical_scope, $entry->name);
		if (q_count($targets) !== 1) {
			throw new \RuntimeException('S2S needs one resolved function for ' . $entry->name);
		}
		$facts = new prepared_call();
		$facts->declaration = $targets[0];
		$facts->signature = Syntax_Nodes::function_data($targets[0]->node)->require_preparation();
		$facts->type = $facts->signature->return_type;
		$parameters /** Storage<prepared_parameter> */ = $facts->signature->parameters;
		$arguments /** Storage<ast_node> */ = $syntax->arguments;
		if (q_count($parameters) !== q_count($arguments)) {
			throw new \RuntimeException('S2S call argument count does not match its signature');
		}
		foreach ($arguments as $index => $argument)
		{
			$value = $argument->payload()->prepare_expression($argument, $context);
			$parameter = $parameters[$index];
			if ($parameter->mode === passing_mode::reference) {
				if ((!$value->addressable) || !self::same_storage_type($value->type, $parameter->type)) {
					throw new \RuntimeException('S2S reference arguments require stable storage of the exact parameter type');
				}
			}
			else {
				self::require_assignable($parameter->type, $value->type);
			}
		}
		return $facts;
	}

	/** A member selection carries the exact field identity and inherits base addressability. */
	public static function prepare_field_access(field_access_structure $syntax, preparation_context $context): prepared_field_access
	{
		$base = $syntax->base;
		$value = $base->payload()->prepare_expression($base, $context);
		if ($value->type->kind !== type_kind::record) {
			throw new \RuntimeException('S2S member access requires a struct value');
		}
		$declaration = object_cast($value->type->declaration, collected_name::class);
		$record = Syntax_Nodes::struct_data($declaration->node)->require_preparation();
		$fields /** Key_Storage_List<prepared_field> */ = $record->fields;
		$matches /** vector<prepared_field> */ = $fields->named($syntax->occurrence()->name);
		if (q_count($matches) !== 1) {
			throw new \RuntimeException('S2S needs one resolved struct field');
		}
		$facts = new prepared_field_access();
		$facts->field = $matches[0];
		$facts->type = $matches[0]->type;
		$facts->addressable = $value->addressable;
		return $facts;
	}

	/** Integer aliases with the same representation designate compatible reference storage. */
	public static function same_storage_type(type_definition $left, type_definition $right): bool
	{
		if ($left === $right) {
			return true;
		}
		return ($left->kind === type_kind::integer) && ($right->kind === type_kind::integer)
		&& ($left->value_bits === $right->value_bits) && ($left->signed === $right->signed);
	}

	public static function require_value_type(type_definition $type): void
	{
		if ($type->kind === type_kind::void_type) {
			throw new \RuntimeException('S2S void is not a storage type');
		}
	}

	/** Integer destinations use the existing runtime conversion; other values keep exact identity. */
	public static function require_assignable(type_definition $destination, type_definition $source): void
	{
		self::require_value_type($destination);
		if ($destination === $source) {
			return;
		}
		if (($destination->kind === type_kind::integer) && ($source->kind === type_kind::integer)) {
			return;
		}
		throw new \RuntimeException('S2S value boundary requires matching types or an integer conversion');
	}
}
