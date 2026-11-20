<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Parser extension point for flavor-specific statements and function headers; all flavors produce the same AST families.
 */
interface statement_grammar {
	public function statement(Parser $parser, Body_Collection $collector): ast_statement;
	public function function_start(Parser $parser): bool;
	public function function_header(Parser $parser, bool $static = false, bool $private = false, bool $protected = false, ?string $alias = null): ast_function_declaration;
}

/** Shared syntax construction; flavor grammars select declaration and assignment intent. */
final class Parser
{
	/** PROPERTIES */
	/** Native owning; parser-local cursor. */
	public readonly Syntax_Cursor $cursor;
	/** Native owning; flavor-specific syntax adapter shares the parser cursor. */
	private readonly Namespace_Syntax $names;
	/** Native non-owning lexical collector used to distinguish JS local fields from namespace paths. */
	private ?Body_Collection $lexical = null;
	/** Temporary parameter names for JS dotted expressions within a lambda. @var bool[]|null */
	private ?array $lambda_names /** array<string,bool>|null */ = null;
	private bool $started = false;
	/** @var array<string,array<string,ast_trait_declaration>> Consumer-specific binding plan supplied after the source join. */
	private array $trait_bindings /** array<string,array<string,ast_trait_declaration>> */ = [];
	private ?string $declaring_type = null;
	/** Native owning until returned to the frontend. */
	private ast_file_node $root;
	/** Native non-owning; coordinator outlives parsing. */
	private ?declaration_sink $sink;
	/** Native shared-owning context passed unchanged to the file root. */
	private readonly type_availability $availability;

	/** CONSTRUCTOR */
	public function __construct(token_list $tokens, ?declaration_sink $sink = null, type_availability $availability = new type_availability(), int $start /** uint32 */ = 0)
	{
		$this->cursor = new Syntax_Cursor($tokens, $start);
		$this->names = new Namespace_Syntax($this->cursor);
		$this->sink = $sink;
		$this->availability = $availability;
	}

	/** METHODS */
	/** Own one source parse and its local collection; failure exposes neither partial result. */
	public function parse(): ast_file_node
	{
		if ($this->started) {
			throw new \LogicException('A parser instance processes one source once');
		}
		$this->started = true;
		$namespace = null;
		$braced = false;
		if ($this->cursor->keyword('namespace')) {
			$namespace = $this->names->declaration();
			$braced = $this->cursor->is(token_kind::lbrace);
			$delimiter = $braced ? token_kind::lbrace : token_kind::semicolon;
			if ((!$braced) && ($this->cursor->tokens->source->flavor !== flavor::php)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected { after namespace');
			}
			$this->cursor->take($delimiter, $braced ? '{' : ';');
		}
		$this->root = new ast_file_node($this->cursor->tokens, $namespace, $this->availability);
		$body_start = $this->cursor->position();
		$collector = new Body_Collection($this->root->scope);
		$this->lexical = $collector;
		$declarations = new Scope_Collection($this->root->scope);
		$grammar = $this->grammar();
		$statements = [];
		$nodes = [];
		$imports = [];
		while ((!$this->cursor->at_end()) && ((!$braced) || (!$this->cursor->is(token_kind::rbrace))))
		{
			if ($this->cursor->keyword('trait') || $this->cursor->keyword('interface') || ($this->cursor->tokens->text_at($this->cursor->position()) === 'final') || $this->cursor->keyword('abstract') || $this->cursor->keyword('global') || $this->cursor->keyword('const') || $this->cursor->keyword('struct') || $this->cursor->keyword('class') || $this->cursor->keyword('enum')) {
				$members = [];
				$node = match ($this->cursor->tokens->text_at($this->cursor->position())) {
					'trait', 'abstract', 'final', 'struct', 'class', 'interface' => Record_Syntax::read($this, $grammar, $members),
					'enum' => Enum_Syntax::read($this, $grammar, $members),
					'const' => Constant_Syntax::read($this),
					'global' => Global_Syntax::read($this),
				};
				$node->attach($this->root);
				if ($node instanceof ast_trait_declaration) {
					$context = $this->root->trait_context ??= new trait_file_context();
					$key = 'class:' . $node->name();
					if (isset($context->origins[$key])) throw new diagnostic($node->source(), $node->byte_start(), 'Duplicate trait: ' . $node->name());
					$context->origins[$key] = $node;
					continue;
				}
				$this->sink?->discovered($node);
				$declarations->import($node);
				$nodes[] = $node;
				foreach ($members as $member) {
					$this->sink?->discovered($member);
					$declarations->import($member);
					$nodes[] = $member;
				}
				continue;
			}
			if ($this->cursor->keyword('use')) {
				foreach (Import_Syntax::read($this->cursor, $this->names) as $import) {
					$import->attach($this->root);
					$imports[] = $import;
				}
				continue;
			}
			if ($grammar->function_start($this)) {
				$function = $this->function_declaration($grammar);
				$declarations->import($function);
				$nodes[] = $function;
				continue;
			}
			$statement = $this->statement($grammar, $collector);
			$collector->statement($statement, count($statements));
			$statements[] = $statement;
		}
		$body_end = $this->cursor->position();
		if ($braced) {
			$this->cursor->take(token_kind::rbrace, '}');
			if (!$this->cursor->at_end()) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
					'Only one namespace body per file is supported');
			}
		}
		if ($statements !== [])
		{
			$main = new ast_function_declaration('main',
				new ast_type_reference($body_start, $body_start, 'int'), $body_start, $body_start, true, $namespace);
			$main->attach($this->root);
			$main->complete_body($collector->body($body_start, $body_end, $statements));
			$declarations->import($main);
			array_unshift($nodes, $main);
		}
		/** @var (ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration)[] $heads */
		$heads = $declarations->publish_references()->heads();
		if (isset($heads['func:main'])) {
			$heads = ['func:main' => $heads['func:main']] + $heads;
		}
		$this->root->complete($heads, $nodes, Declaration_Diagnostics::file($nodes), $imports);
		return $this->root;
	}

	/**
	 * Materialize into the consumer's pending inventories before record construction.
	 * @param array<string,list<ast_function_declaration>> $hooks
	 * @param array<string,list<array{string,string,int}>> $aliases Original name, private alias and adaptation token position.
	 * @param ast_type_reference[] $uses
	 * @param ast_instance_field[] $fields
	 * @param ast_node[] $members
	 */
	public function contribute(string $consumer, array $uses /** array<string,ast_type_reference> */,
		array $aliases /** array<string,list<array{string,string,int}>> */, array &$fields /** array<string,ast_instance_field> */, array &$members /** list<ast_node> */, array &$hooks /** array<string,list<ast_function_declaration>> */): void
	{
		if ($uses === []) return;
		$context = $this->root->trait_context ??= new trait_file_context();
		$direct = [];
		$order = [];
		$local = [];
		$qualified_consumer = '\\' . Source_Names::resolve($consumer, $this->source_namespace());
		$offered = [];
		$data = [];
		foreach ($members as $member) {
			if ($member instanceof ast_function_declaration) $local[$member->short_name] = $member;
			if ($member instanceof ast_literal_declaration) $data[$member::class][$member->short_name] = $member;
		}
		foreach ($uses as $name => $use) {
			$context->uses[] = $use;
			$expansion = $this->trait_bindings[$name] ?? null;
			if ($expansion === null) continue;
			$key = array_key_first($expansion);
			if (isset($direct[$key])) throw new diagnostic($this->root->tokens->source, $this->root->byte_at($use->start), 'Duplicate trait use: ' . $name);
			$direct[$key] = $name;
			$order += $expansion;
		}
		foreach ($order as $symbol => $origin) {
			$adaptations = isset($direct[$symbol]) ? ($aliases[$direct[$symbol]] ?? []) : [];
			if ($origin->fields === [] && $origin->members === [] && $adaptations === []) continue;
			$file = $origin->root();
			$fragment = new self($file->tokens, availability: $file->type_availability, start: $origin->body_start);
			$fragment->root = $file;
			$contributions = [];
			$nested = [];
			$contributed = Record_Syntax::members($fragment, $fragment->grammar(), $qualified_consumer, record_kind::structure, true, true, $contributions, $nested, origin_name: $origin->short_name);
			foreach ($contributed as $key => $field) {
				if (isset($data[ast_global_declaration::class][$key])) $this->trait_conflict('property', $consumer, $data[ast_global_declaration::class][$key], $field, $origin);
				if (isset($fields[$key])) $this->trait_conflict('field', $consumer, $fields[$key], $field, $origin);
				if ($field->duplicate_element() !== null) {
					$field->trait_origin = $origin;
					$this->trait_conflict('field', $consumer, $field, $field->duplicate_element(), $origin);
				}
				$field->trait_origin = $origin;
				$fields[$key] = $field;
			}
			$originals = [];
			foreach ($contributions as $member) {
				$key = $member->short_name;
				if ($member instanceof ast_literal_declaration) {
					if ($member instanceof ast_global_declaration && isset($fields[$key])) $this->trait_conflict('property', $consumer, $fields[$key], $member, $origin);
					$first = $data[$member::class][$key] ?? null;
					if ($first !== null) $this->trait_conflict($member instanceof ast_constant_declaration ? 'constant' : 'static property', $consumer, $first, $member, $origin);
					$member->trait_origin = $origin;
					$data[$member::class][$key] = $member;
					$members[] = $member;
					continue;
				}
				if ($member->is_abstract) {
					$member->trait_origin = $origin;
					$hooks[$key][] = $member;
					continue;
				}
				if (isset($offered[$key])) $this->trait_conflict('method', $consumer, $offered[$key], $member, $origin);
				$member->trait_origin = $origin;
				$offered[$key] = $member;
				if (!$member->is_static && $key !== '__construct' && $key !== '__destruct') $originals[$key] = $member;
				if (isset($local[$key])) {
					if ($member->is_static || $local[$key]->is_static) $this->trait_conflict('method', $consumer, $local[$key], $member, $origin);
					continue;
				}
				$members[] = $member;
			}
			foreach ($adaptations as [$original, $alias, $at]) {
				$method = $originals[$original] ?? throw new diagnostic($this->root->source(), $this->root->byte_at($at), 'Unknown trait method for alias: ' . $original);
				if (isset($offered[$alias]) || isset($local[$alias])) throw new diagnostic($this->root->source(), $this->root->byte_at($at), 'Trait alias conflicts with method: ' . $alias);

				// Each effective name owns its signature, body and preparation facts.
				$copy = new self($file->tokens, availability: $file->type_availability, start: $method->start);
				$copy->root = $file;
				$grammar = $copy->grammar();
				$header = $file->tokens->source->flavor === flavor::js
					? $copy->named_function_header(false, private: true, alias: $alias)
					: $grammar->function_header($copy, private: true, alias: $alias);
				$header->trait_origin = $origin;
				$offered[$alias] = $header;
				$members[] = $copy->callable_body($grammar, $header, $qualified_consumer);
			}
		}
	}

	/** Identify both declarations while their nodes are still pending attachment to the consumer. */
	private function trait_conflict(string $kind, string $consumer, ast_node $first, ast_node $next, ast_trait_declaration $origin): never
	{
		$file = $first->trait_origin?->root() ?? $this->root;
		$source = $file->source();
		$at = $source->location($file->byte_at($first->start));
		$owners = ($first->trait_origin?->name() ?? $consumer) . ' and ' . $origin->name();
		throw new diagnostic($origin->source(), $origin->root()->byte_at($next->start), 'Conflicting trait ' . $kind . ' '
			. $next->short_name . ': ' . $owners . ' (first at ' . $source->path . ':' . $at['line'] . ':' . $at['column'] . ')');
	}

	/** Share body grammar while keeping function definitions out of executable statement inventories. */
	private function statement(statement_grammar $grammar, Body_Collection $collector): ast_statement
	{
		if ($this->cursor->keyword('global') || $this->cursor->keyword('const')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				($this->cursor->keyword('const') ? 'Constant' : 'Global') . ' declarations are only allowed at file/namespace level');
		}
		if ($this->cursor->keyword('namespace') || $this->cursor->keyword('export') || $this->cursor->keyword('use')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				'Nested/additional namespaces, body-local use and export are not supported in this slice');
		}
		if ($this->cursor->keyword('foreach')) return $this->foreach_loop($grammar, $collector);
		if ($this->cursor->keyword('for')) {
			return $this->for_loop($grammar, $collector);
		}
		if ($this->cursor->keyword('while') || $this->cursor->keyword('do')) {
			return $this->loop($grammar, $collector);
		}
		if ($this->cursor->keyword('break') || $this->cursor->keyword('continue')) {
			$is_break = $this->cursor->keyword('break');
			$start = $this->cursor->position();
			$this->cursor->take_keyword($is_break ? 'break' : 'continue');
			$end = $this->cursor->take(token_kind::semicolon, '; (only unlabelled break/continue are supported)') + 1;
			return $is_break ? new ast_break_statement($start, $end) : new ast_continue_statement($start, $end);
		}
		if ($this->cursor->keyword('if')) {
			return $this->conditional($grammar, $collector);
		}
		if ($this->cursor->keyword('try')) return $this->try_statement($grammar, $collector);
		if ($this->cursor->keyword('throw')) {
			$operation = $this->throw_expression();
			$end = $this->cursor->take(token_kind::semicolon, '; after throw') + 1;
			return new ast_throw_statement($operation, $end);
		}
		if ($this->cursor->keyword('yield')) return $this->yield_statement();
		if ($this->cursor->keyword('echo')) {
			return $this->echo_statement();
		}
		if ($grammar->function_start($this)) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				'Nested functions are not supported');
		}
		if ($this->cursor->keyword('return')) {
			return $this->return_statement();
		}
		if ($this->expression_statement_start()) {
			$value = $this->primary(field_target: true);
			if ($this->cursor->is(token_kind::lbracket)) return $this->indexed_write($value);
			if ($value instanceof ast_called_type_name || $value instanceof ast_qualified_data) return $this->property_write($value);
			if (!($value instanceof ast_call_expression)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected call statement');
			}
			return $this->call_statement($value);
		}
		$name_kind = $this->cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier;
		if ($this->property_statement_start()) {
			$target = $this->primary(field_target: true);
			if ($target instanceof ast_call_expression) {
				return $this->call_statement($target);
			}
			if (!($target instanceof ast_field_expression) && !($target instanceof ast_name_expression)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected writable struct field');
			}
			if ($this->cursor->is(token_kind::lbracket)) {
				return $this->indexed_write($target);
			}
			return $this->property_write($target);
		}
		if ($this->cursor->is($name_kind) && $this->cursor->next_is(token_kind::lbracket)) {
			$start = $this->cursor->position();
			$name = $this->cursor->name($name_kind);
			$receiver = new ast_name_expression($name, $start, $start + 1);
			return $this->indexed_write($receiver);
		}
		return $this->update_start() ? $this->update_statement(token_kind::semicolon, true) : $grammar->statement($this, $collector);
	}

	/** Parse only braced alternatives; each body gets its own lexical collector and shared storage IDs. */
	private function conditional(statement_grammar $grammar, Body_Collection $collector): ast_if_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('if');
		$arms = [];
		$conditional = true;
		do {
			$arm_start = $this->cursor->position();
			$condition = null;
			if ($conditional) {
				$this->cursor->take(token_kind::lparen, '(');
				$condition = $this->value();
				$this->cursor->take(token_kind::rparen, ')');
			}
			$body = $this->braced_block($grammar, $collector, '{ (braced conditional body)');
			$arms[] = new ast_if_arm($arm_start, $body->end, $condition, $body);
			if (!$conditional) {
				break;
			}
			if ($this->cursor->keyword('elseif')) {
				$this->cursor->take_keyword('elseif');
			}
			elseif ($this->cursor->keyword('else')) {
				$this->cursor->take_keyword('else');
				$conditional = $this->cursor->keyword('if');
				if ($conditional) {
					$this->cursor->take_keyword('if');
				}
			}
			else {
				break;
			}
		} while (true);
		return new ast_if_statement($start, $this->cursor->position(), $arms);
	}

	/** Parse the protected region and the optional clauses in source order. */
	private function try_statement(statement_grammar $grammar, Body_Collection $collector): ast_try_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('try');
		$body = $this->braced_block($grammar, $collector, '{ after try');
		$handler = $this->cursor->keyword('catch') ? $this->catch_clause($grammar, $collector) : null;
		$finalizer = null;
		if ($this->cursor->keyword('finally')) {
			$this->cursor->take_keyword('finally');
			$finalizer = $this->braced_block($grammar, $collector, '{ after finally');
		}
		if ($handler === null && $finalizer === null) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Try requires catch or finally');
		}
		return new ast_try_statement($start, $body, $handler, $finalizer);
	}

	/** Seed the handler's lexical collector with its one typed binding. */
	private function catch_clause(statement_grammar $grammar, Body_Collection $collector): ast_catch_clause
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('catch');
		$this->cursor->take(token_kind::lparen, '(');
		$flavor = $this->cursor->tokens->source->flavor;
		if ($flavor === flavor::js) {
			$position = $this->cursor->position();
			$name = $this->cursor->name(token_kind::identifier);
			$this->cursor->take(token_kind::colon, ': before catch type');
			$type = $this->type_reference();
		} else {
			$type = $this->type_reference();
			$position = $this->cursor->position();
			$name = $this->cursor->name($flavor === flavor::php ? token_kind::variable : token_kind::identifier);
		}
		$this->cursor->take(token_kind::rparen, ')');
		$child = $collector->child();
		$binding = $child->produced_binding($name, $position, true);
		$handler = $this->braced_block($grammar, $collector, '{ after catch', $child);
		return new ast_catch_clause($start, $type, $binding, $handler);
	}

	/** Parse a lexical block once, optionally with an existing header binding. */
	private function braced_block(statement_grammar $grammar, Body_Collection $collector, string $expected, ?Body_Collection $child = null): ast_block
	{
		$start = $this->cursor->take(token_kind::lbrace, $expected);
		$child ??= $collector->child();
		$this->lexical = $child;
		$statements = [];
		while (!$this->cursor->is(token_kind::rbrace)) {
			if ($this->cursor->at_end()) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected }');
			}
			$statement = $this->statement($grammar, $child);
			$child->statement($statement, count($statements));
			$statements[] = $statement;
		}
		$end = $this->cursor->take(token_kind::rbrace, '}') + 1;
		$this->lexical = $collector;
		return $child->block($start, $end, $statements);
	}

	/** All flavors normalize a bounded foreach header to the same produced bindings. */
	private function foreach_loop(statement_grammar $grammar, Body_Collection $collector): ast_foreach_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('foreach');
		$this->cursor->take(token_kind::lparen, '(');
		$source = $this->value();
		$this->cursor->take_keyword('as');
		$kind = $this->cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier;
		$first_pos = $this->cursor->position();
		$first = $this->cursor->name($kind);
		$header = $collector->child();
		$key = null;
		if ($this->cursor->is(token_kind::arrow)) {
			$this->cursor->take(token_kind::arrow, '=>');
			$key = $header->produced_binding($first, $first_pos, true);
			$first_pos = $this->cursor->position();
			$first = $this->cursor->name($kind);
			if ($first === $key->short_name) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Foreach key and value must have different names');
		}
		$value = $header->produced_binding($first, $first_pos, !$collector->has_local($first));
		$this->cursor->take(token_kind::rparen, ')');
		$body = $this->braced_block($grammar, $header, '{ (braced foreach body)');
		$this->lexical = $collector;
		return new ast_foreach_statement($start, $source, $key, $value, $body, $header->publish_scope());
	}

	/** Parse one initializer, condition and assignment update in a header-local scope. */
	private function for_loop(statement_grammar $grammar, Body_Collection $collector): ast_for_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('for');
		$this->cursor->take(token_kind::lparen, '(');
		$header = $collector->child();
		$this->lexical = $header;
		$initializer = $this->update_start() ? $this->update_statement(token_kind::semicolon) : $grammar->statement($this, $header);
		if (!($initializer instanceof ast_local_declaration) && !($initializer instanceof ast_storage_write)) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'For initializer requires a declaration or assignment');
		}
		$header->statement($initializer, 0);
		$condition = $this->value();
		$this->cursor->take(token_kind::semicolon, '; (for condition separator)');
		if ($this->update_start()) {
			$update = $this->update_statement(token_kind::rparen, true);
		} elseif ($this->property_statement_start() || $this->expression_statement_start() || $this->cursor->next_is(token_kind::lbracket)) {
			$position = $this->cursor->position();
			$target = $this->primary();
			$update = $this->property_update($position, $target, $this->take_update_operator(), true, token_kind::rparen);
		}
		else {
			$update_start = $this->cursor->position();
			$name = $this->cursor->name($this->cursor->tokens->source->flavor === flavor::php
				? token_kind::variable : token_kind::identifier);
			$update = $this->assignment($update_start, $name, assignment_intent::existing_binding, token_kind::rparen);
		}
		$body = $this->braced_block($grammar, $header, '{ (braced for body)');
		$this->lexical = $collector;
		return new ast_for_statement($start, $initializer, $condition, $update, $body, $header->publish_scope());
	}

	/** Parse pre-test/post-test loops with a required braced body and explicit do-while terminator. */
	private function loop(statement_grammar $grammar, Body_Collection $collector): ast_loop_statement
	{
		$start = $this->cursor->position();
		$post_test = $this->cursor->keyword('do');
		$this->cursor->take_keyword($post_test ? 'do' : 'while');
		if ($post_test) {
			$body = $this->braced_block($grammar, $collector, '{ (braced loop body)');
			$this->cursor->take_keyword('while');
		}
		$this->cursor->take(token_kind::lparen, '(');
		$condition = $this->value();
		$this->cursor->take(token_kind::rparen, ')');
		if ($post_test) {
			$end = $this->cursor->take(token_kind::semicolon, '; (do-while terminator)') + 1;
			return new ast_do_while_statement($start, $end, $condition, $body);
		}
		$body = $this->braced_block($grammar, $collector, '{ (braced loop body)');
		return new ast_while_statement($start, $body->end, $condition, $body);
	}

	/** Discover the stable header before parsing its body; local collection never enters the global index. */
	private function function_declaration(statement_grammar $grammar): ast_function_declaration
	{
		$function = $grammar->function_header($this);
		$function->attach($this->root);
		$source = $this->cursor->tokens->source;
		if ($function->short_name === 'main') {
			throw new diagnostic($source, $this->root->byte_at($function->name_position()),
				'Explicit main is not supported yet');
		}
		$this->sink?->discovered($function);
		return $this->callable_body($grammar, $function);
	}

	private function grammar(): statement_grammar
	{
		return match ($this->cursor->tokens->source->flavor) {
			flavor::cpp => new Cpp_Grammar(),
			flavor::php => new Php_Grammar(),
			flavor::js => new Js_Grammar(),
		};
	}

	/** Functions and methods share lexical collection, statement grammar and body ownership. */
	public function callable_body(statement_grammar $grammar, ast_function_declaration $function, ?string $declaring_type = null, bool $derived_constructor = false): ast_function_declaration
	{
		$function->complete_body($this->read_body($grammar, $function, $declaring_type, $derived_constructor));
		return $function;
	}

	/** Build one body and its local scope on the current token cursor. */
	private function read_body(statement_grammar $grammar, ast_function_declaration $function, ?string $declaring_type, bool $derived_constructor, bool $signature_shared = false): ast_function_body
	{
		$source = $this->cursor->tokens->source;
		$this->declaring_type = $declaring_type;
		$base = null;
		if ($derived_constructor && $source->flavor === flavor::cpp && $this->cursor->is(token_kind::colon)) {
			$start = $this->cursor->take(token_kind::colon, ':');
			$target = $this->named_type();
			$arguments = $this->arguments();
			$base = new ast_base_initialization($start, $this->cursor->position(), $arguments, true, $target);
		}
		$body_start = $this->cursor->take(token_kind::lbrace, '{');
		$previous = $this->lexical;
		$body_collector = new Body_Collection($this->root->scope, $function->parameters);
		$this->lexical = $body_collector;
		$statements = [];
		if ($derived_constructor) {
			$c = $this->cursor;
			$start = $c->position();
			$spelling = $c->tokens->text_at($start);
			if (($source->flavor === flavor::php && $spelling === 'parent'
					&& $c->tokens->text_at($start + 2) === '__construct')
				|| ($source->flavor === flavor::js && $spelling === 'super' && $c->next_is(token_kind::lparen))) {
				$c->name(token_kind::identifier);
				if ($source->flavor === flavor::php) {
					$c->take(token_kind::scope, '::');
					if ($c->name(token_kind::identifier) !== '__construct') throw new diagnostic($source, $c->offset(), 'Expected parent constructor');
				}
				$args = $this->arguments();
				$end = $c->take(token_kind::semicolon, ';') + 1;
				$base = new ast_base_initialization($start, $end, $args, true);
			}
			$base ??= new ast_base_initialization($start, $start, [], false);
			$statement = new ast_base_statement($base);
			$body_collector->statement($statement, 0);
			$statements[] = $statement;
		}
		while (!$this->cursor->is(token_kind::rbrace))
		{
			if ($this->cursor->at_end()) {
				throw new diagnostic($source, $this->cursor->offset(), 'Expected }');
			}
			$statement = $this->statement($grammar, $body_collector);
			$body_collector->statement($statement, count($statements));
			$statements[] = $statement;
		}
		$end = $this->cursor->take(token_kind::rbrace, '}') + 1;
		$this->lexical = $previous;
		$this->declaring_type = null;
		return $body_collector->body($body_start, $end, $statements, $signature_shared);
	}

	/** PHP and JS share the explicit function-keyword/colon-return header grammar. */
	public function named_function_header(bool $keyword = true, bool $static = false, bool $private = false, bool $protected = false, ?string $alias = null): ast_function_declaration
	{
		$start = $this->cursor->position();
		if ($keyword) $this->cursor->take_keyword('function');
		$name = $this->cursor->name(token_kind::identifier);
		$parameters = $this->parameters();
		$this->cursor->take(token_kind::colon, ': (explicit return type)');
		$type = $this->return_type();
		return new ast_function_declaration($alias ?? $name, $type,
			$start, $this->cursor->position(), false, $this->root->scope->namespace, $parameters, $static, $private, $protected);
	}

	public function source_namespace(): ?string
	{
		return $this->root->scope->namespace;
	}

	/**
	 * Parse explicit positional passing modes and optional literal-default syntax.
	 * @return ast_parameter_declaration[]
	 */
	public function parameters(): array /** list<ast_parameter_declaration> */
	{
		$this->cursor->take(token_kind::lparen, '(');
		$parameters = [];
		while (!$this->cursor->is(token_kind::rparen)) {
			$start = $this->cursor->position();
			if ($this->cursor->tokens->source->flavor === flavor::js) {
				$mode = $this->parameter_mode();
				$name = $this->cursor->name(token_kind::identifier);
				$this->cursor->take(token_kind::colon, ': (explicit parameter type)');
				$type = $this->parameter_type();
			}
			else {
				$type = $this->parameter_type();
				$mode = $this->parameter_mode();
				$name = $this->cursor->name($this->cursor->tokens->source->flavor === flavor::php
					? token_kind::variable : token_kind::identifier);
			}
			$default = null;
			if ($this->cursor->is(token_kind::assign)) {
				$this->cursor->take(token_kind::assign, '=');
				$default = $this->value();
			}
			$parameters[] = new ast_parameter_declaration($name, $type, count($parameters), $start, $this->cursor->position(), $default, $mode);
			if (!$this->cursor->is(token_kind::comma)) {
				break;
			}
			$this->cursor->take(token_kind::comma, ',');
			if ($this->cursor->is(token_kind::rparen)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Trailing parameter comma is deferred');
			}
		}
		$this->cursor->take(token_kind::rparen, ') (typed parameters)');
		return $parameters;
	}

	/** Distinguish a C++ reference declarator (Base &name) from Base & Capability name. */
	private function parameter_type(): ast_type_reference
	{
		$type = $this->return_type();
		$c = $this->cursor;
		if (!$c->is(token_kind::ampersand) || !$c->next_is(token_kind::identifier)) return $type;
		if ($c->tokens->source->flavor === flavor::cpp
			&& !in_array($c->tokens->tokens[$c->position() + 2]->kind ?? null, [token_kind::identifier, token_kind::ampersand], true)) return $type;
		$c->take(token_kind::ampersand, '&');
		return new ast_intersection_reference($type, $this->named_type());
	}

	/** The reference marker precedes the parameter name in each flavor. */
	private function parameter_mode(): passing_mode
	{
		if ($this->cursor->is(token_kind::ampersand)) {
			$this->cursor->take(token_kind::ampersand, '&');
			return passing_mode::reference;
		}
		return passing_mode::value;
	}

	/** Finish a discarded ordinary or member call. */
	private function call_statement(ast_call_expression $call): ast_call_statement
	{
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_call_statement($call->start, $end, $call);
	}

	/** Calls own ordinary value arguments; nested calls use the same parser and AST node. */
	private function call(): ast_expression
	{
		$start = $this->cursor->position();
		if ($this->cursor->is(token_kind::variable)) {
			$name = $this->cursor->name(token_kind::variable);
			$arguments = $this->arguments();
			return new ast_call_expression($start, $this->cursor->position(), $name, null, $arguments, qualifier: call_qualifier::local);
		}
		$name = $this->names->path(true, call: true);
		if ($this->cursor->tokens->source->flavor === flavor::php && $this->cursor->is(token_kind::scope)) {
			$this->cursor->take(token_kind::scope, '::');
			$name .= '::' . $this->cursor->name(token_kind::identifier);
			return $this->call_arguments($start, $name, token_kind::scope, null);
		}
		$member = $this->cursor->tokens->source->flavor === flavor::js
			&& !str_starts_with($name, '\\') && str_contains($name, '\\');
		$receiver = null;
		if ($member) {
			$prefix = substr($name, 0, strrpos($name, '\\'));
			$receiver = new ast_name_expression($prefix, $start, $this->cursor->position() - 2, str_contains($prefix, '\\'));
		}
		return $this->call_arguments($start, $name, $member ? token_kind::dot : null, $receiver);
	}

	/** Parse the common argument list after a function path or member name. */
	private function call_arguments(int $start /** uint32 */, string $name, ?token_kind $separator, ?ast_expression $receiver, call_qualifier $qualifier = call_qualifier::ordinary): ast_expression
	{
		if ($this->cursor->next_is(token_kind::ellipsis)) {
			$this->cursor->take(token_kind::lparen, '(');
			$this->cursor->take(token_kind::ellipsis, '...');
			$end = $this->cursor->take(token_kind::rparen, ')') + 1;
			if ($qualifier !== call_qualifier::ordinary || ($receiver !== null
				&& !($separator === token_kind::dot && str_contains($name, '\\')))) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->tokens->tokens[$start]->offset, 'Callback reference requires a named free function or explicitly qualified static method');
			}
			return new ast_callable_reference($name, $start, $end);
		}
		$arguments = $this->arguments();
		return new ast_call_expression($start, $this->cursor->position(), $name, $separator, $arguments, $receiver, $qualifier);
	}

	/** Parse ordinary or constructor arguments in source order. @return ast_expression[] */
	private function arguments(): array /** list<ast_expression> */
	{
		$this->cursor->take(token_kind::lparen, '(');
		$arguments = [];
		while (!$this->cursor->is(token_kind::rparen)) {
			$arguments[] = $this->value();
			if (!$this->cursor->is(token_kind::comma)) {
				break;
			}
			$this->cursor->take(token_kind::comma, ',');
			if ($this->cursor->is(token_kind::rparen)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Trailing argument comma is deferred');
			}
		}
		$this->cursor->take(token_kind::rparen, ')');
		return $arguments;
	}

	public function return_type(): ast_type_reference
	{
		return $this->type_reference(false, true);
	}

	/** Explicit outputs only; delegation and implicit keys are separate slices. */
	private function yield_statement(): ast_yield_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('yield');
		$first = $this->value();
		$key = null;
		if ($this->cursor->is(token_kind::arrow)) {
			$this->cursor->take(token_kind::arrow, '=>');
			$key = $first;
			$first = $this->value();
		}
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_yield_statement($start, $end, $first, $key);
	}

	/** All flavors share return syntax; analysis distinguishes void bodies from the integer entry. */
	private function return_statement(): ast_return_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('return');
		$value = $this->cursor->is(token_kind::semicolon) ? null : $this->value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_return_statement($start, $end, $value);
	}

	/** Type keywords and identifiers share generated registration; preparation owns argument admission. */
	public function named_type(): ast_type_reference
	{
		return $this->type_reference();
	}

	/** Build immutable ordered argument chains without an intermediate array; commas belong to the containing type. */
	private function type_reference(bool $argument = false, bool $allow_void = false): ast_type_reference
	{
		$start = $this->cursor->position();
		if ($this->cursor->is(token_kind::question)) {
			$this->cursor->take(token_kind::question, '?');
			$payload = $this->type_reference(false, false);
			$end = $this->cursor->position();
			$next = null;
			if ($argument && $this->cursor->is(token_kind::comma)) {
				$this->cursor->take(token_kind::comma, ',');
				$next = $this->type_reference(true, true);
			}
			return new ast_type_reference($start, $end, 'nullable', $payload, $next);
		}
		$word = $this->cursor->tokens->text_at($start);
		if ($this->cursor->is(token_kind::keyword)
			&& (($word === 'weak') || (type_registry::resolve($word) !== null) || ($allow_void && $word === 'void'))) {
			$this->cursor->take_keyword($word);
		}
		else {
			$word = $this->cursor->name(token_kind::identifier);
		}
		$child = null;
		if ($this->cursor->is(token_kind::less)) {
			$this->cursor->take(token_kind::less, '<');
			$child = $this->type_reference(true, true);
			$this->cursor->take(token_kind::greater, '>');
		}
		$end = $this->cursor->position();
		$next = null;
		if ($argument && $this->cursor->is(token_kind::comma)) {
			$this->cursor->take(token_kind::comma, ',');
			$next = $this->type_reference(true, true);
		}
		return new ast_type_reference($start, $end, $word, $child, $next);
	}

	/** Parse ordered comma-separated echo operands. */
	private function echo_statement(): ast_echo_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('echo');
		$values = [$this->value()];
		while ($this->cursor->is(token_kind::comma)) {
			$this->cursor->take(token_kind::comma, ',');
			$values[] = $this->value();
		}
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_echo_statement($start, $end, $values);
	}

	/** A selected throw evaluates its full operand up to the enclosing delimiter. */
	private function throw_expression(): ast_throw_expression
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('throw');
		return new ast_throw_expression($start, $this->value());
	}

	/** Parse logical, comparison and arithmetic precedence; grouping uses the same expression parser. */
	public function value(int $minimum_precedence /** uint8 */ = 0): ast_expression
	{
		$left = $this->primary();
		while (!$this->cursor->at_end()) {
			if ($this->cursor->keyword('instanceof') && $minimum_precedence <= binary_operator::power->precedence()) {
				$this->cursor->take_keyword('instanceof');
				$target = $this->named_type();
				$left = new ast_object_access_expression($left->start, $this->cursor->position(), $left, $target, true);
				continue;
			}
			$kind = $this->cursor->tokens->tokens[$this->cursor->position()]->kind;
			$operator = match ($kind) {
				token_kind::keyword => $this->cursor->keyword('xor') ? binary_operator::logical_xor : null,
				token_kind::coalesce => binary_operator::coalesce,
				token_kind::logical_and => binary_operator::logical_and,
				token_kind::logical_or => binary_operator::logical_or,
				token_kind::plus => binary_operator::add,
			token_kind::dot => $this->cursor->tokens->source->flavor === flavor::php ? binary_operator::concatenate : null,
				token_kind::minus => binary_operator::subtract,
				token_kind::star => binary_operator::multiply,
				token_kind::slash => binary_operator::divide,
				token_kind::percent => binary_operator::remainder,
				token_kind::ampersand => binary_operator::bit_and,
				token_kind::pipe => binary_operator::bit_or,
				token_kind::caret => binary_operator::bit_xor,
				token_kind::power => binary_operator::power,
				token_kind::spaceship => binary_operator::compare,
				token_kind::identical => binary_operator::identical,
				token_kind::not_identical => binary_operator::not_identical,
				token_kind::equal => binary_operator::equal,
				token_kind::not_equal => binary_operator::not_equal,
				token_kind::less => binary_operator::less,
				token_kind::less_equal => binary_operator::less_equal,
				token_kind::greater => binary_operator::greater,
				token_kind::greater_equal => binary_operator::greater_equal,
				default => null,
			};
			$shift = (($kind === token_kind::less) || ($kind === token_kind::greater))
				? $this->cursor->tokens->operator_text_at($this->cursor->position()) : '';
			if ($shift === '<<' || $shift === '>>') {
				$operator = $shift === '<<' ? binary_operator::shift_left : binary_operator::shift_right;
			}
			if (($operator === null) || ($operator->precedence() < $minimum_precedence)) {
				break;
			}
			$this->cursor->take($kind, 'binary operator');
			if ($shift === '<<' || $shift === '>>') {
				$this->cursor->take($kind, 'shift operator');
			}
			$right = $operator === binary_operator::coalesce && $this->cursor->keyword('throw')
				? $this->throw_expression()
				: $this->value($operator->precedence() + (($operator === binary_operator::power || $operator === binary_operator::coalesce) ? 0 : 1));
			$left = new ast_binary_expression($left, $operator, $right);
		}
		return $left;
	}

	/** Bounded scalar cast lookahead; preserve grouping when no unary operand follows, e.g. (null). */
	private function cast_ahead(): bool
	{
		$tokens = $this->cursor->tokens;
		$start = $this->cursor->position();
		if (!$this->cursor->is(token_kind::lparen)
			|| (($tokens->tokens[$start + 2]->kind ?? null) !== token_kind::rparen)) {
			return false;
		}
		$type = $tokens->text_at($start + 1);
		if ((type_registry::resolve($type) === null) && ($type !== 'void')) {
			return false;
		}
		$kind = $tokens->tokens[$start + 3]->kind ?? null;
		return in_array($kind, [token_kind::identifier, token_kind::variable, token_kind::keyword,
			token_kind::integer, token_kind::floating, token_kind::single_string, token_kind::double_string, token_kind::template_string,
			token_kind::lparen, token_kind::bang, token_kind::tilde, token_kind::backslash, token_kind::scope], true)
			|| ((($kind === token_kind::minus) || ($kind === token_kind::plus))
				&& in_array($tokens->tokens[$start + 4]->kind ?? null, [token_kind::integer, token_kind::floating], true));
	}

	/** Low-precedence xor requires grouping until value-producing assignments are implemented. */
	public function assignment_value(): ast_expression
	{
		$value = $this->value(1);
		if ($this->cursor->keyword('xor')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				'Parenthesize xor in an initializer or assignment RHS');
		}
		return $value;
	}

	/** Parse a declaration literal, optionally retaining a name for an enum field default. */
	public function literal_initializer(string $diagnostic, bool $allow_case = false): ast_expression
	{
		$cursor = $this->cursor;
		$literal_start = $cursor->position();
		if (($cursor->is(token_kind::minus) || $cursor->is(token_kind::plus)) && $cursor->next_is(token_kind::integer)) {
			$cursor->take($cursor->is(token_kind::minus) ? token_kind::minus : token_kind::plus, 'sign');
			$cursor->take(token_kind::integer, 'integer literal');
			$initializer = new ast_integer_expression($literal_start, $cursor->position());
		}
		else {
			$initializer = $this->assignment_value();
		}
		if (type_registry::literal_type($initializer) === null && !($allow_case && $initializer instanceof ast_name_expression
			&& !($initializer instanceof ast_qualified_data) && !($initializer instanceof ast_called_type_name))) {
			throw new diagnostic($cursor->tokens->source, $cursor->offset(), $diagnostic);
		}
		return $initializer;
	}

	/** Parse the discarded write or update on an already selected property. */
	private function property_write(ast_field_expression|ast_name_expression $target): ast_statement
	{
		if ($this->cursor->is(token_kind::increment) || $this->cursor->is(token_kind::decrement)) {
			return $this->property_update($target->start, $target, $this->take_update_operator(), true);
		}
		$operator = $this->compound_operator();
		if ($operator !== null) {
			if ($operator === binary_operator::power) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Unsupported property compound operator');
			}
			$this->cursor->take($this->cursor->tokens->tokens[$this->cursor->position()]->kind, 'compound assignment');
			$value = $this->assignment_value();
			return new ast_property_compound($target, $value, $operator, $this->cursor->take(token_kind::semicolon, ';') + 1);
		}
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->assignment_value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_field_assignment($target, $value, $end);
	}

	/** Parse a keyed write or append on a named local or field; preparation checks writability. */
	private function indexed_write(ast_expression $receiver): ast_statement
	{
		$this->cursor->take(token_kind::lbracket, '[');
		$index = $this->cursor->is(token_kind::rbracket) ? null : $this->value();
		$index_end = $this->cursor->take(token_kind::rbracket, ']') + 1;
		if ($index !== null && ($this->cursor->is(token_kind::scope) || $this->cursor->is(token_kind::lbracket)
			|| $this->cursor->is(token_kind::member) || $this->cursor->is(token_kind::dot))) {
			$target = $this->postfix(new ast_index_expression($receiver, $index, $index_end), false);
			if ($target instanceof ast_qualified_data) return $this->property_write($target);
			if ($target instanceof ast_call_expression) return $this->call_statement($target);
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected call statement');
		}
		if ($receiver instanceof ast_called_type_name) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Indexed late-static property writes are deferred');
		if ($receiver instanceof ast_qualified_data) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Indexed expression-qualified static property writes are deferred');
		}
		if ($this->cursor->is(token_kind::increment) || $this->cursor->is(token_kind::decrement)) {
			if ($index === null) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Indexed update requires a key');
			$operator = $this->take_update_operator();
			return new ast_index_update($receiver->start, $this->cursor->take(token_kind::semicolon, ';') + 1,
				new ast_index_expression($receiver, $index, $index_end), $operator, true);
		}
		$operator = $this->compound_operator();
		if ($operator !== null) {
			if ($index === null) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Indexed compound requires a key');
			$this->cursor->take($this->cursor->tokens->tokens[$this->cursor->position()]->kind, 'compound assignment');
			$value = $this->assignment_value();
			return new ast_index_compound(new ast_index_expression($receiver, $index, $index_end), $value, $operator,
				$this->cursor->take(token_kind::semicolon, ';') + 1);
		}
		if (!($receiver instanceof ast_name_expression || $receiver instanceof ast_field_expression)) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Indexed assignment requires named or field storage');
		}
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->assignment_value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return $index === null ? new ast_append_statement($receiver, $value, $end)
			: new ast_index_assignment($receiver, $index, $value, $end);
	}

	/** Admit generated core scalar literals and flavor-correct names without resolving declarations. */
	private function primary(bool $field_target = false): ast_expression
	{
		return $this->postfix($this->atom(), $field_target);
	}

	/** Continue member calls and indexing on the expression already parsed. */
	private function postfix(ast_expression $value, bool $field_target): ast_expression
	{
		while (true) {
			$flavor = $this->cursor->tokens->source->flavor;
			if ($this->cursor->is(token_kind::scope)) {
				$this->cursor->take(token_kind::scope, '::');
				$property = $flavor === flavor::php && $this->cursor->is(token_kind::variable);
				$name = $this->cursor->name($property ? token_kind::variable : token_kind::identifier);
				$value = !$property && $this->cursor->is(token_kind::lparen)
					? $this->call_arguments($value->start, $name, token_kind::scope, $value)
					: new ast_qualified_data($value, $name, $this->cursor->position(), $flavor === flavor::php && !$property);
				continue;
			}
			if ($flavor !== flavor::js && $this->cursor->is(token_kind::member)) {
				$this->cursor->take(token_kind::member, '->');
				$name = $this->cursor->name(token_kind::identifier);
				$value = $this->cursor->is(token_kind::lparen) ? $this->call_arguments($value->start, $name, token_kind::member, $value)
					: new ast_field_expression($value, $name, $this->cursor->position());
				continue;
			}
			if ($this->cursor->tokens->source->flavor !== flavor::php && $this->cursor->is(token_kind::dot)) {
				$this->cursor->take(token_kind::dot, '.');
				$name = $this->cursor->name(token_kind::identifier);
				$value = $flavor === flavor::js && !$this->cursor->is(token_kind::lparen)
					? new ast_field_expression($value, $name, $this->cursor->position())
					: $this->call_arguments($value->start, $name, token_kind::dot, $value);
				continue;
			}
			if ($field_target || !$this->cursor->is(token_kind::lbracket)) {
				break;
			}
			$this->cursor->take(token_kind::lbracket, '[');
			$index = $this->value();
			$end = $this->cursor->take(token_kind::rbracket, ']') + 1;
			$value = new ast_index_expression($value, $index, $end);
		}
		return $value;
	}

	/** Recognize contextual member qualifiers according to flavor, separator and supported syntax. */
	private function member_qualifier(): ?call_qualifier
	{
		$c = $this->cursor;
		$scope = $c->next_is(token_kind::scope);
		$flavor = $c->tokens->source->flavor;
		if (!$scope && ($flavor !== flavor::js || !$c->next_is(token_kind::dot))) return null;
		$name = $c->tokens->text_at($c->position());
		if ($scope && $name === ($flavor === flavor::cpp ? 'Static' : 'static')) return call_qualifier::called_type;
		return match ($flavor) {
			flavor::php => match ($name) {
				'self' => call_qualifier::lexical_self, 'parent' => call_qualifier::lexical_parent, default => null,
			},
			flavor::cpp => match ($name) {
				'Self' => call_qualifier::lexical_self,
				'Parent' => ($c->tokens->tokens[$c->position() + 3]->kind ?? null) === token_kind::lparen ? call_qualifier::lexical_parent : null,
				default => null,
			},
			flavor::js => $scope ? null : match ($name) {
				'class' => call_qualifier::lexical_self, 'super' => call_qualifier::lexical_parent, default => null,
			},
		};
	}

	/** Self names the lexical owner. Parent and late-static member names are resolved during preparation. */
	private function qualified_member(call_qualifier $qualifier): ast_expression
	{
		$c = $this->cursor;
		$start = $c->position();
		$self = $qualifier === call_qualifier::lexical_self;
		if ($self && $this->declaring_type === null) throw new diagnostic($c->tokens->source, $c->offset(), 'Declaring-type qualifier requires a struct body or enum method body');
		$flavor = $c->tokens->source->flavor;
		$c->take($c->tokens->tokens[$start]->kind, 'type qualifier');
		$separator = $c->tokens->tokens[$c->position()]->kind;
		$c->take($separator, 'member separator');
		$variable = $flavor === flavor::php && $c->is(token_kind::variable);
		$name = $c->name($variable ? token_kind::variable : token_kind::identifier);
		if ($self) $name = $this->declaring_type . '::' . $name;
		if (!$variable && $c->is(token_kind::lparen)) return $this->call_arguments($start, $name, $self ? token_kind::scope : $separator, null, $qualifier);
		$constant = $flavor === flavor::php && !$variable;
		if ($qualifier === call_qualifier::called_type) return new ast_called_type_name($name, $start, $c->position(), constant_only: $constant);
		if (!$self) return new ast_ancestor_name($name, $start, $c->position(), constant_only: $constant);
		return new ast_name_expression($name, $start, $c->position(), constant_only: $constant);
	}

	/** In C++ and JS, fn remains an ordinary name unless followed by an explicitly typed lambda header. */
	private function lambda_start(): bool
	{
		$c = $this->cursor;
		if ($c->keyword('fn')) return true;
		if (!$c->is(token_kind::identifier) || !$c->next_is(token_kind::lparen)
			|| $c->tokens->text_at($c->position()) !== 'fn') return false;
		$depth = 1;
		$tokens = $c->tokens->tokens;
		for ($index = $c->position() + 2; isset($tokens[$index]); ++$index) {
			$kind = $tokens[$index]->kind;
			if ($kind === token_kind::lparen) ++$depth;
			elseif ($kind === token_kind::rparen && --$depth === 0) return ($tokens[$index + 1]->kind ?? null) === token_kind::colon;
		}
		return false;
	}

	private function lambda_expression(): ast_lambda_expression
	{
		$c = $this->cursor;
		$start = $c->take($c->tokens->tokens[$c->position()]->kind, 'fn');
		$parameters = $this->parameters();
		$c->take(token_kind::colon, ': (lambda result type)');
		$result = $this->return_type();
		$c->take(token_kind::arrow, '=>');
		$outer = $this->lambda_names;
		$this->lambda_names = [];
		foreach ($parameters as $parameter) $this->lambda_names[$parameter->declaration_key()] = true;
		try {
			return new ast_lambda_expression($start, $parameters, $result, $this->value());
		} finally {
			$this->lambda_names = $outer;
		}
	}

	/** Parse one primary or prefix expression before postfix indexing. */
	private function atom(): ast_expression
	{
		$c = $this->cursor;
		if ($this->lambda_start()) return $this->lambda_expression();
		if ($c->keyword('throw')) {
			throw new diagnostic($c->tokens->source, $c->offset(), 'Throw expression requires a ?? fallback or match default');
		}
		$qualifier = $this->member_qualifier();
		if ($qualifier !== null) return $this->qualified_member($qualifier);
		if ($c->keyword('match')) {
			$start = $c->position();
			$c->take_keyword('match');
			$c->take(token_kind::lparen, '(');
			$subject = $this->value();
			$c->take(token_kind::rparen, ')');
			$c->take(token_kind::lbrace, '{');
			$arms = [];
			while (!$c->is(token_kind::rbrace)) {
				$arm_start = $c->position();
				$label = null;
				if ($c->keyword('default')) $c->take_keyword('default');
				elseif (!ast_match_expression::is_condition_subject($subject)
					&& ($c->is(token_kind::minus) || $c->is(token_kind::plus)) && $c->next_is(token_kind::integer)) {
					$label = $this->literal_initializer('Match label requires an integer literal', true);
				} else $label = $this->value();
				$c->take(token_kind::arrow, '=>');
				$result = $label === null && $c->keyword('throw') ? $this->throw_expression() : $this->value();
				$arms[] = new ast_match_arm($label, $result, $arm_start, $c->position());
				if (!$c->is(token_kind::comma)) break;
				$c->take(token_kind::comma, ',');
			}
			$c->take(token_kind::rbrace, '}');
			return new ast_match_expression($subject, $arms, $start, $c->position());
		}
		if ($this->cursor->keyword('new')) {
			$start = $this->cursor->position();
			$this->cursor->take_keyword('new');
			$at = $this->cursor->position();
			$called = $c->tokens->source->flavor === flavor::cpp ? 'Static' : 'static';
			if ($c->next_is(token_kind::lparen) && $c->tokens->text_at($at) === $called) {
				$c->take($c->tokens->tokens[$at]->kind, $called);
				$target = new ast_called_type_reference($at, $c->position(), $called);
			} else {
				$name = $this->names->path(true);
				$target = new ast_type_reference($at, $c->position(), $name);
			}
			$arguments = [];
			if ($this->cursor->is(token_kind::lparen)) $arguments = $this->arguments();
			elseif ($this->cursor->tokens->source->flavor !== flavor::php) $this->cursor->take(token_kind::lparen, '( after the constructed type');
			return new ast_new_expression($start, $this->cursor->position(), $target, $arguments);
		}
		if ($this->cursor->keyword('object_cast')) {
			$start = $this->cursor->position();
			$this->cursor->take_keyword('object_cast');
			$this->cursor->take(token_kind::lparen, '(');
			$operand = $this->value();
			$this->cursor->take(token_kind::comma, ', before the target class marker');
			$at = $this->cursor->position();
			$constant = false;
			$marker = false;
			$name = $this->names->variable_path($constant, $marker);
			if (!$marker) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'object_cast requires a class type marker');
			$target = new ast_type_reference($at, $this->cursor->position(), $name);
			$end = $this->cursor->take(token_kind::rparen, ')') + 1;
			return new ast_object_access_expression($start, $end, $operand, $target, false);
		}
		if ($this->cursor->keyword('weak') || $this->cursor->keyword('lock')) {
			$start = $this->cursor->position();
			$lock = $this->cursor->keyword('lock');
			$this->cursor->take_keyword($lock ? 'lock' : 'weak');
			$this->cursor->take(token_kind::lparen, '(');
			$operand = $this->value();
			$end = $this->cursor->take(token_kind::rparen, ')') + 1;
			return new ast_ownership_expression($start, $end, $operand, $lock);
		}
		if (!$this->cursor->at_end() && ($this->cursor->tokens->text_at($this->cursor->position()) === 'isset')) {
			$start = $this->cursor->position();
			$this->cursor->take($this->cursor->tokens->tokens[$start]->kind, 'isset');
			$this->cursor->take(token_kind::lparen, '(');
			$operand = $this->value();
			$this->cursor->take(token_kind::rparen, ')');
			return new ast_isset_expression($start, $this->cursor->position(), $operand);
		}
		if ($this->cursor->is(token_kind::lbracket)) {
			$start = $this->cursor->take(token_kind::lbracket, '[');
			$elements = [];
			$entries = [];
			while (!$this->cursor->is(token_kind::rbracket)) {
				$element = $this->value();
				if ($this->cursor->is(token_kind::arrow)) {
					if ($elements !== []) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Cannot mix positional and keyed entries');
					$this->cursor->take(token_kind::arrow, '=>');
					$entries[] = new ast_keyed_entry($element, $this->value());
				} else {
					if ($entries !== []) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Cannot mix keyed and positional entries');
					$elements[] = $element;
				}
				if (!$this->cursor->is(token_kind::comma)) break;
				$this->cursor->take(token_kind::comma, ',');
			}
			$end = $this->cursor->take(token_kind::rbracket, ']') + 1;
			return $entries === [] ? new ast_collection_expression($start, $end, $elements) : new ast_keyed_collection_expression($start, $end, $entries);
		}
		if ($this->cursor->is(token_kind::tilde) || (($this->cursor->is(token_kind::plus) || $this->cursor->is(token_kind::minus))
			&& !$this->cursor->next_is(token_kind::floating))) {
			$kind = $this->cursor->tokens->tokens[$this->cursor->position()]->kind;
			$start = $this->cursor->take($kind, 'unary sign');
			$operator = match ($kind) {
				token_kind::plus => unary_operator::positive,
				token_kind::minus => unary_operator::negative,
				token_kind::tilde => unary_operator::bit_not,
			};
			$operand = $this->value(binary_operator::power->precedence());
			// A direct signed prefixed literal is checked as one value, including the signed minimum.
			if ($kind !== token_kind::tilde && $operand instanceof ast_integer_expression
				&& $operand->start === $start + 1 && $operand->end === $this->cursor->position()
				&& preg_match('/^0[xXoO]/', $this->cursor->tokens->text_at($operand->start)) === 1) {
				return new ast_integer_expression($start, $operand->end);
			}
			return new ast_unary_expression($start, $this->cursor->position(), $operator, $operand);
		}
		if ($this->cursor->is(token_kind::bang)) {
			$start = $this->cursor->take(token_kind::bang, '!');
			$operand = $this->value(binary_operator::power->precedence());
			return new ast_not_expression($start, $this->cursor->position(), $operand);
		}
		if ($this->cast_ahead()) {
			$start = $this->cursor->take(token_kind::lparen, '(');
			$type = $this->cursor->tokens->text_at($this->cursor->position());
			$type_start = $this->cursor->position();
			$this->cursor->take($this->cursor->tokens->tokens[$type_start]->kind, 'cast type');
			$type = new ast_type_reference($type_start, $this->cursor->position(), $type);
			$this->cursor->take(token_kind::rparen, ')');
			$operand = $this->value(binary_operator::power->precedence());
			return new ast_cast_expression($start, $this->cursor->position(), $type, $operand);
		}
		if ($this->cursor->is(token_kind::lparen)) {
			$this->cursor->take(token_kind::lparen, '(');
			$value = $this->value();
			$this->cursor->take(token_kind::rparen, ')');
			return $value;
		}
		if ($this->declaring_type !== null && !$this->cursor->at_end()) {
			$start = $this->cursor->position();
			$self = $this->cursor->tokens->source->flavor === flavor::php ? '$this' : 'this';
			if ($this->cursor->tokens->text_at($start) === $self) {
				$this->cursor->take($this->cursor->tokens->tokens[$start]->kind, $self);
				return new ast_this_expression($start, $start + 1);
			}
		}
		if ($c->tokens->source->flavor === flavor::js && $c->is(token_kind::identifier) && $c->next_is(token_kind::dot)) {
			$name = $c->tokens->text_at($c->position());
			if ($this->lambda_names === null ? $this->lexical?->has_local($name) : isset($this->lambda_names[$name])) {
				$start = $c->position();
				return new ast_name_expression($c->name(token_kind::identifier), $start, $start + 1);
			}
		}
		if ($this->names->call_start()) {
			return $this->call();
		}
		$start = $this->cursor->position();
		if ($this->cursor->is(token_kind::single_string) || $this->cursor->is(token_kind::double_string)
			|| $this->cursor->is(token_kind::template_string)) {
			$kind = $this->cursor->tokens->tokens[$start]->kind;
			$this->cursor->take($kind, 'string literal');
			if ($kind === token_kind::template_string || ($kind === token_kind::double_string && $this->cursor->tokens->source->flavor === flavor::php)) {
				return Interpolation::parse($this->cursor->tokens, $start);
			}
			return new ast_string_expression($start, $start + 1);
		}
		if (($this->cursor->is(token_kind::minus) || $this->cursor->is(token_kind::plus))
			&& $this->cursor->next_is(token_kind::floating)) {
			$this->cursor->take($this->cursor->is(token_kind::minus) ? token_kind::minus : token_kind::plus, 'sign');
		}
		if ($this->cursor->is(token_kind::floating)) {
			$this->cursor->take(token_kind::floating, 'floating literal');
			return new ast_float_expression($start, $this->cursor->position());
		}
		if ($this->cursor->is(token_kind::integer)) {
			$position = $this->cursor->take(token_kind::integer, 'integer');
			return new ast_integer_expression($position, $position + 1);
		}
		$start = $this->cursor->position();
		$word = $this->cursor->at_end() ? '' : $this->cursor->tokens->text_at($start);
		if (type_registry::literal($word) !== null) {
			$kind = $this->cursor->tokens->tokens[$start]->kind;
			$this->cursor->take($kind, 'scalar literal');
			return new ast_keyword_expression($word, $start, $start + 1);
		}
		$constant_only = false;
		$type_marker = false;
		$name = $this->names->variable_path($constant_only, $type_marker);
		if ($type_marker) return new ast_type_marker($name, $start, $this->cursor->position());
		$member_syntax = ($this->cursor->tokens->source->flavor === flavor::js)
			&& (!str_starts_with($name, '\\')) && str_contains($name, '\\');
		return new ast_name_expression($name, $start, $this->cursor->position(), $member_syntax, $constant_only);
	}

	/** $start: token position. */
	public function declaration(int $start /** uint32 */, string $name, ?ast_type_reference $type_reference): ast_local_declaration
	{
		if ($type_reference !== null && $this->cursor->is(token_kind::lparen)) {
			$at = $this->cursor->position();
			$arguments = $this->arguments();
			if ($arguments === []) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Use a typed declaration without () for default initialization');
			$end = $this->cursor->take(token_kind::semicolon, ';');
			return new ast_local_declaration($start, $end + 1, $name, $type_reference, new ast_struct_default($at, $end, $arguments));
		}
		if ($type_reference !== null && $this->cursor->is(token_kind::semicolon)) {
			$at = $this->cursor->take(token_kind::semicolon, ';');
			return new ast_local_declaration($start, $at + 1, $name, $type_reference, new ast_struct_default($at, $at));
		}
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->assignment_value();
		$end = $this->cursor->take(token_kind::semicolon, ';') + 1;
		return new ast_local_declaration($start, $end, $name, $type_reference, $value);
	}

	/** Recognize expression-led calls and property targets before parsing their postfix operations. */
	private function expression_statement_start(): bool
	{
		$flavor = $this->cursor->tokens->source->flavor;
		return $this->names->call_start() || $this->cursor->is(token_kind::lparen) || ($this->cursor->is(token_kind::keyword)
				&& (in_array($this->cursor->tokens->text_at($this->cursor->position()), ['new', 'lock', 'object_cast', 'match'], true)
					|| ($this->cursor->next_is(token_kind::lparen) && $this->cursor->keyword('weak'))))
			|| ($flavor !== flavor::cpp && $this->cursor->is($flavor === flavor::php ? token_kind::variable : token_kind::identifier)
				&& $this->cursor->next_is(token_kind::scope))
			|| ($flavor === flavor::cpp && $this->cursor->is(token_kind::identifier) && $this->cursor->next_is(token_kind::dot));
	}

	/** Distinguish member writes from declarations and simple local updates. */
	private function property_statement_start(bool $prefix = false): bool
	{
		$flavor = $this->cursor->tokens->source->flavor;
		return ($this->cursor->is($flavor === flavor::php ? token_kind::variable : token_kind::identifier)
			&& $this->cursor->next_is($flavor === flavor::js ? token_kind::dot : token_kind::member))
			|| $this->member_qualifier() !== null || ($prefix
				? $this->cursor->next_is(token_kind::scope) || $this->cursor->next_is(token_kind::backslash)
					|| $this->cursor->is(token_kind::scope) || $this->cursor->is(token_kind::backslash)
				: $this->names->qualified_write());
	}

	private function take_update_operator(): update_operator
	{
		$kind = $this->cursor->is(token_kind::increment) ? token_kind::increment : token_kind::decrement;
		$this->cursor->take($kind, 'update operator');
		return $kind === token_kind::increment ? update_operator::increment : update_operator::decrement;
	}

	private function property_update(int $start /** uint32 */, ast_expression $target, update_operator $operator, bool $postfix,
		token_kind $terminator = token_kind::semicolon): ast_property_update
	{
		if (!($target instanceof ast_field_expression) && !($target instanceof ast_name_expression)) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected writable property');
		}
		$end = $this->cursor->take($terminator, $terminator->label()) + 1;
		return new ast_property_update($start, $end, $target, $operator, $postfix);
	}

	/** Recognize discarded-value update syntax only at statement and header boundaries. */
	private function update_start(): bool
	{
		return $this->cursor->is(token_kind::increment) || $this->cursor->is(token_kind::decrement)
			|| $this->cursor->next_is(token_kind::increment) || $this->cursor->next_is(token_kind::decrement);
	}

	/** Parse local updates in statements or loop headers; allow property targets in statements and the final for clause. */
	private function update_statement(token_kind $terminator, bool $allow_property = false): ast_update_statement
	{
		$cursor = $this->cursor;
		$start = $cursor->position();
		$postfix = !$cursor->is(token_kind::increment) && !$cursor->is(token_kind::decrement);
		if (!$postfix) {
			$operator = $this->take_update_operator();
			if ($allow_property && ($this->property_statement_start(true) || $this->expression_statement_start() || $cursor->next_is(token_kind::lbracket))) {
				$target = $this->primary();
				if ($target instanceof ast_index_expression) {
					if ($terminator !== token_kind::semicolon) {
						throw new diagnostic($cursor->tokens->source, $cursor->offset(), 'Indexed updates in for headers are deferred');
					}
					return new ast_index_update($start, $cursor->take($terminator, $terminator->label()) + 1, $target, $operator, false);
				}
				return $this->property_update($start, $target, $operator, false, $terminator);
			}
		}
		$name = $cursor->name($cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier);
		if ($postfix) $operator = $this->take_update_operator();
		$end = $cursor->take($terminator, $terminator->label()) + 1;
		return new ast_update_statement($start, $end, $name, $operator, $postfix);
	}

	/** Inspect only assignment spelling; the shared map does not authorize operand types. */
	public function compound_operator(): ?binary_operator
	{
		$kind = $this->cursor->tokens->tokens[$this->cursor->position()]->kind ?? null;
		if (($kind === token_kind::concat_assign) && ($this->cursor->tokens->source->flavor !== flavor::php)) {
			return null;
		}
		return $kind === null ? null : binary_operator::compound($kind);
	}

	/** $start: token position. */
	public function assignment(int $start /** uint32 */, string $name, assignment_intent $intent,
		token_kind $terminator = token_kind::semicolon): ast_assignment_statement
	{
		$operator = $this->compound_operator();
		$kind = $operator === null ? token_kind::assign : $this->cursor->tokens->tokens[$this->cursor->position()]->kind;
		$this->cursor->take($kind, 'assignment operator');
		$value = $this->assignment_value();
		$end = $this->cursor->take($terminator, $terminator->label()) + 1;
		return new ast_assignment_statement($start, $end, $name, $value, $intent, $operator);
	}

	/** STATIC METHODS */
	/** Reuse the consumer tokens and original trait token generations. @param array<string,array<string,ast_trait_declaration>> $bindings */
	public static function composed(ast_file_node $authored, array $bindings /** array<string,array<string,ast_trait_declaration>> */): ast_file_node
	{
		$parser = new self($authored->tokens, availability: $authored->type_availability);
		$parser->trait_bindings = $bindings;
		$file = $parser->parse();
		// Preserve authored identities on a dependency-only rebuild, including traits in this consumer's file.
		$file->trait_context->origins = $authored->trait_context->origins;
		$file->trait_context->bindings = $bindings;
		return $file;
	}

	/** Parse only the demanded executable fragment, sharing the published signature and token inventory. */
	public static function instance_body(ast_function_declaration $method): ast_function_body
	{
		$file = $method->lexical_root();
		$parser = new self($file->tokens, availability: $file->type_availability, start: $method->body()->start);
		$parser->root = $file;
		return $parser->read_body($parser->grammar(), $method, '\\' . $method->parent->name(), false, signature_shared: true);
	}
}
