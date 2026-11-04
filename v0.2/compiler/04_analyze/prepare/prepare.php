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
	 * @var ast_local_declaration[]
	 */
	private array $visible_locals /** array<string,ast_local_declaration> */ = [];
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
			$symbols, $syntax, $dependencies);
		$syntax->settle_preparation($this->statement_visits, $this->expression_visits, $this->name_lookups,
			$target);
	}

	/**
	 * One source-order body traversal; null exit target denotes a void function, never inferred return typing.
	 *
	 * @param ast_statement[] $syntax
	 */
	private function body(array $syntax /** list<ast_statement> */,
		source_file $source, int $end /** uint32 */, ?entry_target $target, scope $symbols, ast_function_body $owner,
		?Dependency_Watches $dependencies): void
	{
		$this->visible_locals = [];
		$this->statement_visits = 0;
		$this->expression_visits = 0;
		$this->name_lookups = 0;
		$returned = false;
		$file = $owner->root();
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
			elseif ($statement instanceof ast_echo_statement)
			{
				if ($statement->value instanceof ast_string_expression) {
					++$this->expression_visits;
					$statement->value->settle_bytes(Output_Literals::decode($statement->value));
				}
				else {
					$this->value($statement->value);
				}
			}
			elseif ($statement instanceof ast_call_statement)
			{
				// JS dotted spelling may name a value; it must not silently bypass local visibility.
				$separator = strpos($statement->name, '\\');
				if (($statement->member_syntax) && ($separator !== false) && ($separator > 0)
					&& isset($this->visible_locals[substr($statement->name, 0, $separator)])) {
					throw new diagnostic($source, $statement->byte_start(), 'Member calls are not supported in this slice');
				}
				$this->name_lookups++;
				$symbol = $symbols->entry($file->function_key($statement->name));
				$dependencies?->watch($symbol, $owner);
				$function = $symbol->declaration();
				if ($function === null) {
					throw new diagnostic($source, $statement->byte_start(), 'Unknown function: ' . $statement->name);
				}
				$statement->settle_function($symbol);
			}
			elseif ($statement instanceof ast_return_statement)
			{
				if ($target === null) {
					if ($statement->value !== null) {
						throw new diagnostic($source, $statement->byte_start(),
							'Void function cannot return a value');
					}
				}
				else
				{
					if ($statement->value === null) {
						throw new diagnostic($source, $statement->byte_start(),
							'Entry requires a value return');
					}
					$this->value($statement->value);
					$statement->settle_conversion(Exit_Preparation::decide($statement->value->constant(),
							$target, $statement->value));
				}
				$returned = true;
			}
			else {
				throw new diagnostic($source, $statement->byte_start(),
					'Assignments are outside the entry slice');
			}
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
		if ($declaration->type_name === null) {
			throw new diagnostic($source, $root->byte_at($declaration->name_position()),
				'Inferred declarations are outside the entry slice');
		}
		if (!in_array($declaration->type_name, ['int', 'int64'], true)) {
			throw new diagnostic($source, $root->byte_at($declaration->type_position()),
				'Unknown or unsupported type: ' . $declaration->type_name);
		}
		$this->value($declaration->initializer, $name);
		$declaration->settle_type(type_definition::int64, $declaration->initializer->constant());
		$this->visible_locals[$name] = $declaration;
	}

	/** Resolve each expression once; a reference retains its exact declaration binding. */
	private function value(ast_expression $syntax, ?string $initializing = null): void
	{
		$this->expression_visits++;
		if ($syntax instanceof ast_integer_expression) {
			$syntax->settle_integer(Integer_Preparation::value($syntax));
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
				$syntax->settle_local($binding);
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

}
