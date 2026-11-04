<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Chooses the entry return conversion from known value facts and the native target range. */
final class Exit_Preparation
{
	/** Decide the native exit boundary once; unknown values retain a required runtime check. */
	public static function decide(?int $constant /** int64 */, entry_target $target, ast_node $span): exit_conversion
	{
		if ($constant === null) {
			return exit_conversion::checked;
		}
		if (!$target->contains($constant)) {
			throw new diagnostic($span->source(), $span->byte_start(),
				'Entry return is outside native int range');
		}
		return exit_conversion::proven;
	}
}

/**
 * Prepares a body in source order, resolving locals and calls and retaining only the additional facts lowering needs.
 */
final class Preparation
{
	/**
	 * Source-order visibility index, not a declaration collector; native non-owning declaration references.
	 *
	 * @var ast_local_binding[]
	 */
	private array $visible_locals /** array<string,ast_local_binding> */ = [];
	/** @var ast_assignment_statement[] Latest completed writes; transient non-owning body-local index. */
	private array $latest_writes /** array<uint32,ast_assignment_statement> */ = [];
	private int $statement_visits /** uint32 */ = 0;
	private int $expression_visits /** uint32 */ = 0;
	private int $name_lookups /** uint32 */ = 0;
	/** Native non-owning context for this body's variable lookup and dependency registration. */
	private scope $symbols;
	/** Native non-owning owner of the facts being prepared. */
	private ast_function_body $owner;
	/** Native non-owning; null for full, nonincremental preparation. */
	private ?Dependency_Watches $dependencies = null;

	/** Standalone clients use the same declaration-before-body orchestration as multifile programs. */
	public function prepare(ast_file_node $file, entry_target $target): prepared_entry
	{
		return Program_Preparation::prepare('entry', ['entry' => $file], $target)->entry;
	}

	/** Prepare only the selected executable body, consuming already settled callable contracts. */
	public function entry(ast_file_node $file, entry_target $target, scope $symbols): void
	{
		$source = $file->tokens->source;
		$main = $file->main();
		if ($main === null) {
			throw new diagnostic($source, strlen($source->content),
				'Entry slice requires an explicit value return');
		}
		$this->function_body($main->body(), $symbols, $target);
	}

	/** Reset one body, reject duplicate locals, and settle its facts and traversal statistics. */
	public function function_body(ast_function_body $syntax, scope $symbols, ?entry_target $target = null, ?Dependency_Watches $dependencies = null): void
	{
		$this->symbols = $symbols;
		$this->owner = $syntax;
		$this->dependencies = $dependencies;
		$dependencies?->forget($syntax);
		Function_Preparation::reset_body($syntax);
		$errors = Declaration_Diagnostics::locals($syntax);
		if ($errors !== []) {
			throw new diagnostic_group($errors);
		}
		$this->body($syntax->statements, $syntax->source(),
			($target === null ? $syntax->byte_end() : strlen($syntax->source()->content)), $target,
			$syntax);
		$syntax->settle_preparation($this->statement_visits, $this->expression_visits, $this->name_lookups,
			$target);
	}

	/**
	 * One source-order body traversal; null exit target denotes a void function, never inferred return typing.
	 *
	 * @param ast_statement[] $syntax
	 */
	private function body(array $syntax /** list<ast_statement> */,
		source_file $source, int $end /** uint32 */, ?entry_target $target, ast_function_body $owner): void
	{
		$this->visible_locals = [];
		$this->latest_writes = [];
		/** @var ast_function_declaration $function */
		$function = $owner->parent;
		foreach ($function->parameters as $parameter) {
			$this->visible_locals[$parameter->declaration_key()] = $parameter;
		}
		$this->statement_visits = 0;
		$this->expression_visits = 0;
		$this->name_lookups = 0;
		$returned = false;
		foreach ($syntax as $statement)
		{
			$this->statement_visits++;
			if ($returned) {
				throw new diagnostic($statement->source(), $statement->byte_start(),
					'Statements after return are outside the entry slice');
			}
			if ($statement instanceof ast_local_declaration) {
				$this->declaration($statement);
			}
			elseif ($statement instanceof ast_assignment_statement) {
				$this->assignment($statement);
			}
			elseif ($statement instanceof ast_echo_statement)
			{
				$this->value($statement->value);
				if ($statement->value->type_def() !== type_string::instance()) {
					$this->require_integer($statement->value, 'Echo currently supports only int64 and string values');
				}
			}
			elseif ($statement instanceof ast_call_statement) {
				$this->expression_visits++;
				$this->call($statement->value);
			}
			elseif ($statement instanceof ast_return_statement)
			{
				if ($target === null) {
					$return_type = $function->return_type_def();
					if ($return_type === type_void::instance()) {
						if ($statement->value !== null) {
							throw new diagnostic($source, $statement->byte_start(), 'Void function cannot return a value');
						}
					}
					else {
						if ($statement->value === null) {
							throw new diagnostic($source, $statement->byte_start(), 'Non-void function requires a value return');
						}
						$this->value($statement->value);
						$statement->settle_conversion($return_type->prepare_conversion($statement->value, true));
					}
				}
				else
				{
					if ($statement->value === null) {
						throw new diagnostic($source, $statement->byte_start(),
							'Entry requires a value return');
					}
					$this->value($statement->value);
					$this->require_integer($statement->value, 'Entry return requires int64');
					$statement->settle_conversion(Exit_Preparation::decide($statement->value->constant(),
							$target, $statement->value));
				}
				$returned = true;
			}
			else {
				throw new \LogicException('Unhandled statement preparation');
			}
		}
		if (($target === null) && !$returned && ($function->return_type_def() !== type_void::instance())) {
			throw new diagnostic($source, $end, 'Non-void function requires a value return');
		}
		if (($target !== null) && (!$returned)) {
			throw new diagnostic($source, $end, 'Entry slice requires an explicit value return');
		}
	}

	/** Establish a binding only after its initializer succeeds; collection grants no forward visibility. */
	private function declaration(ast_local_declaration $declaration): void
	{
		$name = $declaration->declaration_key();
		$source = $declaration->source();
		$root = $declaration->root();
		if (isset($this->visible_locals[$name])) {
			throw new diagnostic($source, $root->byte_at($declaration->name_position()),
				'Duplicate local: ' . $name);
		}
		$type = null;
		if ($declaration->type_name !== null) {
			$type = type_registry::resolve($declaration->type_name);
			if ($type === null) {
				throw new diagnostic($source, $root->byte_at($declaration->type_position()),
					'Unknown or unsupported type: ' . $declaration->type_name);
			}
		}
		$this->value($declaration->initializer, $name);
		if ($type === null) {
			$type = $declaration->initializer->type_def();
			if ($type === type_null::instance()) {
				throw new diagnostic($source, $declaration->initializer->byte_start(),
					'Cannot infer a local type from null; write an explicit type');
			}
		}
		$conversion = $type->prepare_conversion($declaration->initializer, $declaration->type_name !== null);
		$declaration->settle_type($type, $type->convert_constant($conversion, $declaration->initializer->constant()),
			$declaration->initializer->constant_known(), $conversion);
		$this->visible_locals[$name] = $declaration;
	}

	/** Prepare the RHS before publishing the write, preserving old-value reads such as x = x. */
	private function assignment(ast_assignment_statement $statement): void
	{
		$this->name_lookups++;
		$local = $this->visible_locals[$statement->name] ?? null;
		if ($local === null) {
			throw new diagnostic($statement->source(), $statement->byte_start(),
				'Assignment requires an existing local; global writes are deferred: ' . $statement->name);
		}
		$this->value($statement->value);
		$conversion = $local->type_def()->prepare_conversion($statement->value, false);
		$statement->settle_assignment($local, $conversion);
		$this->latest_writes[$local->storage_id()] = $statement;
	}

	/** Resolve each expression once; a reference retains its exact declaration binding. */
	private function value(ast_expression $syntax, ?string $initializing = null): void
	{
		$this->expression_visits++;
		if ($syntax instanceof ast_call_expression) {
			$this->call($syntax);
			if ($syntax->type_def() === type_void::instance()) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Void call cannot be used as a value');
			}
			return;
		}
		if (($syntax instanceof ast_integer_expression) || ($syntax instanceof ast_float_expression)
			|| ($syntax instanceof ast_keyword_expression) || ($syntax instanceof ast_string_expression)) {
			type_registry::prepare_literal($syntax);
			return;
		}
		if ($syntax instanceof ast_name_expression)
		{
			$this->name_lookups++;
			$name = $syntax->name;
			if ($name === $initializing) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(),
					'Cannot read local during its initialization: ' . $name);
			}
			$binding = $this->visible_locals[$name] ?? null;
			if ($binding !== null) {
				$syntax->settle_local($binding, $this->latest_writes[$binding->storage_id()] ?? null);
				return;
			}
			$separator = strpos($name, '\\');
			if ($syntax->member_syntax && ($separator !== false)
				&& isset($this->visible_locals[substr($name, 0, $separator)])) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Member reads are not supported in this slice');
			}
			$key = 'var:' . Source_Names::resolve($name, $this->owner->scope->namespace);
			$symbol = $this->symbols->entry($key);
			$this->dependencies?->watch($symbol, $this->owner);
			if ($symbol->declaration() === null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(),
					'Unknown or not-yet-initialized local/global: ' . $name);
			}
			$syntax->settle_global($symbol);
			return;
		}
		throw new \LogicException('Unhandled expression preparation');
	}

	/** Resolve only the published signature, then validate and convert arguments in source order. */
	private function call(ast_call_expression $call): void
	{
		$separator = strpos($call->name, '\\');
		if ($call->member_syntax && ($separator !== false) && isset($this->visible_locals[substr($call->name, 0, $separator)])) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Member calls are not supported in this slice');
		}
		$this->name_lookups++;
		$symbol = $this->symbols->entry($call->symbol_key());
		$this->dependencies?->watch($symbol, $this->owner);
		$function = $symbol->declaration();
		if (!($function instanceof ast_function_declaration)) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Unknown function: ' . $call->name);
		}
		$supplied = count($call->arguments);
		// Settled signatures have a trailing optional suffix: checking its first omitted slot is sufficient.
		if (($supplied > count($function->parameters))
			|| (isset($function->parameters[$supplied]) && ($function->parameters[$supplied]->default_value === null))) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
		}
		$conversions = [];
		foreach ($call->arguments as $index => $argument) {
			$this->value($argument);
			$conversions[] = $function->parameters[$index]->type_def()->prepare_conversion($argument, true);
		}
		$call->settle_function($symbol, $conversions);
	}

	/** Prevent C++ implicit scalar conversions from widening the supported echo/entry contracts. */
	private function require_integer(ast_expression $value, string $message): void
	{
		if ($value->type_def() !== type_registry::integer()) {
			throw new diagnostic($value->source(), $value->byte_start(), $message);
		}
	}

}
