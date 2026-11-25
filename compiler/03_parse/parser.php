<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Parser extension point for flavor-specific statements and function headers; all flavors produce the same AST families.
 */
interface statement_grammar {
	public function statement(Parser $parser, Body_Collection $collector, ?token_kind $terminator = token_kind::semicolon): ast_statement;
	public function function_start(Parser $parser): bool;
	public function function_header(Parser $parser, bool $static = false, bool $private = false, bool $protected = false, ?string $alias = null): ast_function_declaration;
}

/** Names recognized only in expression position immediately before an opening brace. */
enum keyword_block: string
{
	case parallel = 'parallel';
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
	/** Active only while parsing a PHP arrow expression. */
	private ?Arrow_Captures $arrow = null;
	private bool $started = false;
	private bool $integer_initializer = false;
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
		while (!$this->cursor->at_end() && (!$braced || !$this->cursor->is(token_kind::rbrace)))
		{
			if ($this->cursor->keyword('trait') || $this->cursor->keyword('interface') || (!($grammar instanceof Python_Grammar) && $this->cursor->tokens->text_at($this->cursor->position()) === 'final') || $this->cursor->keyword('abstract') || $this->cursor->keyword('global') || $this->cursor->keyword('const') || $this->cursor->keyword('struct') || $this->cursor->keyword('class') || $this->cursor->keyword('enum')) {
				$members = [];
				$node = match ($this->cursor->tokens->text_at($this->cursor->position())) {
					'trait', 'abstract', 'final', 'struct', 'class', 'interface' => Record_Syntax::read($this, $grammar, $members),
					'enum' => Enum_Syntax::read($this, $grammar, $members),
					'const' => Constant_Syntax::read($this, namespace_constant: true),
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
			if ($this->cursor->keyword('async') || $grammar->function_start($this)) {
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

	/** Decode a numbered exit once. Depths larger than int64 still exceed every possible source nesting. */
	private function exit_depth(): int /** int64 */
	{
		$at = $this->cursor->take(token_kind::integer, 'exit depth');
		$text = $this->cursor->tokens->text_at($at);
		$length = strlen($text);
		$depth = 0;
		for ($i = 0; $i < $length; ++$i) {
			$digit = ord($text[$i]) - 48;
			if ($digit < 0 || $digit > 9) break;
			$depth = $depth > intdiv(PHP_INT_MAX - $digit, 10) ? PHP_INT_MAX : $depth * 10 + $digit;
		}
		if ($i < $length || $depth === 0) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->tokens->tokens[$at]->offset,
				'Exit depth requires a positive decimal integer');
		}
		return $depth;
	}

	/** Share body grammar while keeping function definitions out of executable statement inventories. */
	private function statement(statement_grammar $grammar, Body_Collection $collector): ast_statement
	{
		if ($grammar instanceof Python_Grammar) return $this->python_statement($grammar, $collector);
		if ($this->cursor->keyword('unset') || $this->cursor->keyword('clean')) {
			$start = $this->cursor->position();
			$operation = $this->cursor->tokens->text_at($start);
			$this->cursor->take_keyword($operation);
			$this->cursor->take(token_kind::lparen, '(');
			$name = $this->cursor->name(token_kind::variable);
			$this->cursor->take(token_kind::rparen, ') after a single whole-variable ' . $operation . ' operand');
			$end = $this->statement_end(token_kind::semicolon);
			return $operation === 'clean' ? new ast_clean_statement($start, $end, $name) : new ast_unset_statement($start, $end, $name);
		}
		if ($this->cursor->keyword('global') || $this->cursor->keyword('const')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				($this->cursor->keyword('const') ? 'Constant' : 'Global') . ' declarations are only allowed at file/namespace level');
		}
		if ($this->cursor->keyword('namespace') || $this->cursor->keyword('export') || $this->cursor->keyword('use')) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(),
				'Nested/additional namespaces, body-local use and export are not supported in this slice');
		}
		if ($this->cursor->is(token_kind::lbrace)) return new ast_block_statement($this->lexical_block($grammar, $collector, '{'));
		if ($this->cursor->keyword('foreach')) return $this->foreach_loop($grammar, $collector);
		if ($this->cursor->keyword('for')) {
			if ($this->js_key_loop_ahead()) return $this->foreach_loop($grammar, $collector);
			return $this->for_loop($grammar, $collector);
		}
		if ($this->cursor->keyword('while') || $this->cursor->keyword('do')) {
			return $this->loop($grammar, $collector);
		}
		if ($this->cursor->keyword('break') || $this->cursor->keyword('continue')) {
			return $this->loop_transfer(token_kind::semicolon);
		}
		if ($this->cursor->keyword('switch')) return $this->switch_statement($grammar, $collector);
		if ($this->cursor->keyword('if')) {
			return $this->conditional($grammar, $collector);
		}
		if ($this->cursor->keyword('try')) return $this->try_statement($grammar, $collector);
		if ($this->cursor->keyword('throw')) return $this->throw_statement('throw', token_kind::semicolon);
		if ($this->cursor->keyword('await')) return $this->await_statement(token_kind::semicolon);
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

	/** Dispatch only the statements admitted by the initial Python surface. */
	private function python_statement(Python_Grammar $grammar, Body_Collection $collector): ast_statement
	{
		$c = $this->cursor;
		if ($c->is(token_kind::indent)) {
			throw new diagnostic($c->tokens->source, $c->offset(), 'Unexpected Python indentation');
		}
		if ($c->keyword('if')) return $this->conditional($grammar, $collector);
		if ($c->keyword('while')) return $this->loop($grammar, $collector);
		if ($c->keyword('for')) return $this->foreach_loop($grammar, $collector);
		if ($c->keyword('return')) return $this->return_statement(token_kind::newline);
		if ($c->keyword('break') || $c->keyword('continue')) return $this->loop_transfer(token_kind::newline);
		if ($c->keyword('pass')) {
			$start = $c->position();
			$c->take_keyword('pass');
			$end = $this->statement_end(token_kind::newline);
			return new ast_block_statement($collector->child()->block($start, $end, []));
		}
		if ($c->keyword('try')) return $this->try_statement($grammar, $collector);
		if ($c->keyword('await')) return $this->await_statement(token_kind::newline);
		if ($c->keyword('raise')) return $this->throw_statement('raise', token_kind::newline);
		if ($c->keyword('super') || $c->keyword('self') || ($c->is(token_kind::identifier)
			&& ($c->next_is(token_kind::lparen) || $c->next_is(token_kind::dot) || $c->next_is(token_kind::lbracket)))) {
			$value = $this->primary(field_target: true);
			if ($c->is(token_kind::lbracket)) return $this->indexed_write($value, token_kind::newline);
			if ($value instanceof ast_call_expression) return $this->call_statement($value, token_kind::newline);
			if ($value instanceof ast_field_expression) {
				$c->take(token_kind::assign, '= after Python field');
				$right = $this->assignment_value();
				return new ast_field_assignment($value, $right, $this->statement_end(token_kind::newline));
			}
			throw new diagnostic($c->tokens->source, $c->offset(), 'Expected Python member assignment or call');
		}
		return $grammar->statement($this, $collector, token_kind::newline);
	}

	/** Other flavors accept an exit depth; Python only accepts an unlabelled transfer. */
	private function loop_transfer(token_kind $terminator): ast_loop_transfer
	{
		$is_break = $this->cursor->keyword('break');
		$start = $this->cursor->position();
		$this->cursor->take_keyword($is_break ? 'break' : 'continue');
		$depth = $terminator === token_kind::semicolon && $this->cursor->is(token_kind::integer) ? $this->exit_depth() : 1;
		$expected = $terminator === token_kind::semicolon ? '; after optional positive decimal exit depth' : $terminator->label();
		$end = $this->cursor->take($terminator, $expected) + 1;
		return $is_break ? new ast_break_statement($start, $end, $depth) : new ast_continue_statement($start, $end, $depth);
	}

	/** Parse alternatives in source order. Recursion binds each else to the nearest unmatched if. */
	private function conditional(statement_grammar $grammar, Body_Collection $collector): ast_if_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('if');
		$arms = [];
		$conditional = true;
		$python = $grammar instanceof Python_Grammar;
		$else_if = $python ? 'elif' : 'elseif';
		do {
			$arm_start = $this->cursor->position();
			$condition = null;
			if ($conditional) {
				if (!$python) $this->cursor->take(token_kind::lparen, '(');
				$condition = $this->value();
				if (!$python) $this->cursor->take(token_kind::rparen, ')');
			}
			$body = $this->arm_body($grammar, $collector);
			$arms[] = new ast_if_arm($arm_start, $body->end, $condition, $body);
			if (!$conditional) {
				break;
			}
			if ($this->cursor->keyword($else_if)) {
				$this->cursor->take_keyword($else_if);
			}
			elseif ($this->cursor->keyword('else')) {
				$this->cursor->take_keyword('else');
				$conditional = !$python && $this->cursor->keyword('if');
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

	/** Collect case bodies and their labels in source order. */
	private function switch_statement(statement_grammar $grammar, Body_Collection $collector): ast_switch_statement
	{
		$c = $this->cursor;
		$start = $c->position();
		$c->take_keyword('switch');
		$c->take(token_kind::lparen, '(');
		$subject = $this->value();
		$c->take(token_kind::rparen, ')');
		$c->take(token_kind::lbrace, '{');
		$arms = [];
		while (!$c->is(token_kind::rbrace)) {
			$arm_start = $c->position();
			$label = null;
			if ($c->keyword('default')) $c->take_keyword('default');
			else {
				$c->take_keyword('case');
				$label = $this->constant_label();
			}
			$c->take(token_kind::colon, ':');
			$child = $collector->child();
			$this->lexical = $child;
			$body_start = $c->position();
			$statements = [];
			while (!$c->keyword('case') && !$c->keyword('default') && !$c->is(token_kind::rbrace)) {
				$statement = $this->statement($grammar, $child);
				$child->statement($statement, count($statements));
				$statements[] = $statement;
			}
			$this->lexical = $collector;
			$body = $child->block($body_start, $c->position(), $statements);
			$arms[] = new ast_switch_arm($arm_start, $body->end, $label, $body);
		}
		return new ast_switch_statement($start, $c->take(token_kind::rbrace, '}') + 1, $subject, $arms);
	}

	private function constant_label(): ast_expression
	{
		$c = $this->cursor;
		return ($c->is(token_kind::minus) || $c->is(token_kind::plus)) && $c->next_is(token_kind::integer)
			? $this->literal_initializer('Expected integer label', true) : $this->value();
	}

	/** Parse the protected region and the optional clauses in source order. */
	private function try_statement(statement_grammar $grammar, Body_Collection $collector): ast_try_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('try');
		$body = $this->lexical_block($grammar, $collector, '{ after try');
		$handlers = [];
		$catch_keyword = $grammar instanceof Python_Grammar ? 'except' : 'catch';
		while ($this->cursor->keyword($catch_keyword)) $handlers[] = $this->catch_clause($grammar, $collector);
		$finalizer = null;
		if ($this->cursor->keyword('finally')) {
			$this->cursor->take_keyword('finally');
			$finalizer = $this->lexical_block($grammar, $collector, '{ after finally');
		}
		if ($handlers === [] && $finalizer === null) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Try requires catch or finally');
		}
		return new ast_try_statement($start, $body, $handlers, $finalizer);
	}

	/** Seed the handler's lexical collector with its one typed binding. */
	private function catch_clause(statement_grammar $grammar, Body_Collection $collector): ast_catch_clause
	{
		$start = $this->cursor->position();
		$flavor = $this->cursor->tokens->source->flavor;
		$python = $flavor === flavor::python;
		$this->cursor->take_keyword($python ? 'except' : 'catch');
		if ($python && !$this->cursor->is(token_kind::identifier)) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected exception type after except');
		}
		if (!$python) $this->cursor->take(token_kind::lparen, '(');
		if ($flavor === flavor::js) {
			$position = $this->cursor->position();
			$name = $this->cursor->name(token_kind::identifier);
			$this->cursor->take(token_kind::colon, ': before catch type');
			$type = $this->type_reference();
		} else {
			$type = $this->type_reference();
			if ($python) $this->cursor->take_keyword('as');
			$position = $this->cursor->position();
			$name = $this->cursor->binding_name($flavor === flavor::php ? token_kind::variable : token_kind::identifier);
		}
		if (!$python) $this->cursor->take(token_kind::rparen, ')');
		$child = $collector->child();
		$binding = $child->produced_binding($name, $position, true);
		$handler = $this->lexical_block($grammar, $collector, '{ after catch', $child);
		return new ast_catch_clause($start, $type, $binding, $handler);
	}

	/** A single-statement arm uses the same lexical scope and block representation as a braced arm. */
	private function arm_body(statement_grammar $grammar, Body_Collection $collector): ast_block
	{
		if ($grammar instanceof Python_Grammar || $this->cursor->is(token_kind::lbrace)) return $this->lexical_block($grammar, $collector, '{');
		$start = $this->cursor->position();
		$child = $collector->child();
		$this->lexical = $child;
		$statement = $this->statement($grammar, $child);
		$child->statement($statement, 0);
		$this->lexical = $collector;
		return $child->block($start, $this->cursor->position(), [$statement]);
	}

	/** Parse a lexical block once, optionally with an existing header binding. */
	private function lexical_block(statement_grammar $grammar, Body_Collection $collector, string $expected, ?Body_Collection $child = null): ast_block
	{
		$start = $this->open_suite($grammar, $expected);
		$close = $grammar instanceof Python_Grammar ? token_kind::dedent : token_kind::rbrace;
		$child ??= $collector->child();
		$this->lexical = $child;
		$statements = [];
		while (!$this->cursor->is($close)) {
			if ($this->cursor->at_end()) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected }');
			}
			$statement = $this->statement($grammar, $child);
			$child->statement($statement, count($statements));
			$statements[] = $statement;
		}
		$end = $this->close_suite($close, $statements);
		$this->lexical = $collector;
		return $child->block($start, $end, $statements);
	}

	/** Consume the opening layout of a callable body or nested suite. */
	public function open_suite(statement_grammar $grammar, string $expected): int /** uint32 */
	{
		if (!($grammar instanceof Python_Grammar)) return $this->cursor->take(token_kind::lbrace, $expected);
		$start = $this->cursor->take(token_kind::colon, ': before Python suite');
		$this->cursor->take(token_kind::newline, 'NEWLINE before Python suite');
		$this->cursor->take(token_kind::indent, 'INDENT for Python suite');
		return $start;
	}

	/** Consume the terminator and exclude Python dedent lookahead from the source span.
	 * @param ast_statement[] $statements
	 */
	private function close_suite(token_kind $close, array $statements): int /** uint32 */
	{
		$position = $this->cursor->take($close, $close->label());
		return $close === token_kind::dedent ? $statements[array_key_last($statements)]->end : $position + 1;
	}

	/** Distinguish JS for (name in source) from the three-clause for header. */
	private function js_key_loop_ahead(): bool
	{
		$c = $this->cursor;
		$start = $c->position();
		return $c->tokens->source->flavor === flavor::js && $c->next_is(token_kind::lparen)
			&& ($c->tokens->tokens[$start + 2]->kind ?? null) === token_kind::identifier
			&& ($c->tokens->tokens[$start + 3]->kind ?? null) === token_kind::identifier
			&& $c->tokens->text_at($start + 3) === 'in';
	}

	/** All flavors normalize a bounded foreach header to the same produced bindings. */
	private function foreach_loop(statement_grammar $grammar, Body_Collection $collector): ast_foreach_statement
	{
		$c = $this->cursor;
		$start = $c->position();
		$python = $grammar instanceof Python_Grammar;
		$requires_key = !$python && $c->keyword('for');
		$key = null;
		$header = $collector->child();
		if ($python || $requires_key) {
			$c->take_keyword('for');
			if ($requires_key) $c->take(token_kind::lparen, '(');
			$value_pos = $c->position();
			$name = $c->name(token_kind::identifier);
			if ($python && $c->is(token_kind::comma)) {
				$c->take(token_kind::comma, ',');
				$key = $header->produced_binding($name, $value_pos, true);
				$value_pos = $c->position();
				$name = $c->name(token_kind::identifier);
				if ($name === $key->short_name) throw new diagnostic($c->tokens->source, $c->tokens->tokens[$value_pos]->offset, 'Foreach key and value must have different names');
			}
			if ($python) $c->take_keyword('in');
			else $c->take(token_kind::identifier, 'in');
			$source = $key === null ? $this->value() : $this->python_items_receiver();
			if ($requires_key) $c->take(token_kind::rparen, ')');
		}
		else {
			$c->take_keyword('foreach');
			$c->take(token_kind::lparen, '(');
			$source = $this->value();
			$c->take_keyword('as');
			$kind = $c->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier;
			$value_pos = $c->position();
			$name = $c->binding_name($kind);
			if ($c->is(token_kind::arrow)) {
				$c->take(token_kind::arrow, '=>');
				$key = $header->produced_binding($name, $value_pos, true);
				$value_pos = $c->position();
				$name = $c->binding_name($kind);
				if ($name === $key->short_name) throw new diagnostic($c->tokens->source, $c->offset(), 'Foreach key and value must have different names');
			}
			$c->take(token_kind::rparen, ')');
		}
		$value = $header->produced_binding($name, $value_pos, !$collector->has_local($name));
		$body = $this->lexical_block($grammar, $header, '{ (braced foreach body)');
		$this->lexical = $collector;
		return new ast_foreach_statement($start, $source, $key, $value, $body, $header->publish_scope(), $requires_key);
	}

	/** The header owns its separators; nested calls and constructors consume their own commas. */
	private function for_loop(statement_grammar $grammar, Body_Collection $collector): ast_for_statement
	{
		$c = $this->cursor;
		$start = $c->position();
		$c->take_keyword('for');
		$c->take(token_kind::lparen, '(');
		$header = $collector->child();
		$this->lexical = $header;
		$initializers = $this->for_actions($grammar, $header, true);
		$c->take(token_kind::semicolon, '; after for initialization');
		$conditions = [];
		if (!$c->is(token_kind::semicolon)) {
			do {
				$conditions[] = $this->value();
				if (!$c->is(token_kind::comma)) break;
				$c->take(token_kind::comma, ',');
			} while (true);
		}
		$c->take(token_kind::semicolon, '; after for conditions');
		$updates = $this->for_actions($grammar, $header, false);
		$c->take(token_kind::rparen, ') after for updates');
		$body = $this->lexical_block($grammar, $header, '{ (braced for body)');
		$this->lexical = $collector;
		return new ast_for_statement($start, $initializers, $conditions, $updates, $body, $header->publish_scope());
	}

	/** Parse declarations only during initialization. A null terminator leaves the delimiter with the header. @return ast_statement[] */
	private function for_actions(statement_grammar $grammar, Body_Collection $header, bool $initial): array /** list<ast_statement> */
	{
		$c = $this->cursor;
		$items = [];
		if ($c->is($initial ? token_kind::semicolon : token_kind::rparen)) return $items;
		do {
			$start = $c->position();
			if ($this->update_start()) $item = $this->update_statement(null, true);
			elseif ($this->property_statement_start() || $this->expression_statement_start() || $c->next_is(token_kind::lbracket)) {
				$target = $this->primary();
				$item = $target instanceof ast_call_expression ? new ast_call_statement($start, $c->position(), $target)
					: $this->property_update($start, $target, $this->take_update_operator(), true, null);
			} elseif ($initial) $item = $grammar->statement($this, $header, null);
			else {
				$name = $c->name($c->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier);
				$item = $this->assignment($start, $name, assignment_intent::existing_binding, null);
			}
			if ($item instanceof ast_local_declaration) $header->declare_local($item);
			$items[] = $item;
			if (!$c->is(token_kind::comma)) break;
			$c->take(token_kind::comma, ',');
		} while (true);
		return $items;
	}

	/** Parse pre-test/post-test loops with a required braced body and explicit do-while terminator. */
	private function loop(statement_grammar $grammar, Body_Collection $collector): ast_loop_statement
	{
		$start = $this->cursor->position();
		$python = $grammar instanceof Python_Grammar;
		$post_test = $this->cursor->keyword('do');
		$this->cursor->take_keyword($post_test ? 'do' : 'while');
		if ($post_test) {
			$body = $this->lexical_block($grammar, $collector, '{ (braced loop body)');
			$this->cursor->take_keyword('while');
		}
		if (!$python) $this->cursor->take(token_kind::lparen, '(');
		$condition = $this->value();
		if (!$python) $this->cursor->take(token_kind::rparen, ')');
		if ($post_test) {
			$end = $this->cursor->take(token_kind::semicolon, '; (do-while terminator)') + 1;
			return new ast_do_while_statement($start, $end, $condition, $body);
		}
		$body = $this->lexical_block($grammar, $collector, '{ (braced loop body)');
		if ($python && $this->cursor->keyword('else')) {
			$this->cursor->take_keyword('else');
			$otherwise = $this->lexical_block($grammar, $collector, 'Python while else suite');
			return new ast_while_else_statement($start, $condition, $body, $otherwise);
		}
		return new ast_while_statement($start, $body->end, $condition, $body);
	}

	/** Discover the stable header before parsing its body; local collection never enters the global index. */
	private function function_declaration(statement_grammar $grammar): ast_function_declaration
	{
		$modifier = $this->cursor->position();
		$async = $this->cursor->keyword('async');
		if ($async) $this->cursor->take_keyword('async');
		$function = $grammar->function_header($this);
		$function->is_async = $async;
		if ($async) $function->modifier_start = $modifier;
		$function->attach($this->root);
		$source = $this->cursor->tokens->source;
		if ($function->short_name === 'main') {
			throw new diagnostic($source, $this->root->byte_at($function->name_position()),
				'Explicit main is not supported yet');
		}
		$this->sink?->discovered($function);
		return $this->callable_body($grammar, $function);
	}

	/** Select the statement grammar for the active body context. */
	private function grammar(): statement_grammar
	{
		return match ($this->cursor->tokens->source->flavor) {
			flavor::cpp => new Cpp_Grammar(),
			flavor::php => new Php_Grammar(),
			flavor::js => new Js_Grammar(),
			flavor::python => new Python_Grammar(),
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
			$arguments = $this->constructor_arguments($named, $unpacked);
			$base = new ast_base_initialization($start, $this->cursor->position(), $arguments, true, $target, $named, $unpacked);
		}
		$body_start = $this->open_suite($grammar, '{');
		$close = $grammar instanceof Python_Grammar ? token_kind::dedent : token_kind::rbrace;
		$previous = $this->lexical;
		$body_collector = new Body_Collection($this->root->scope, $function->parameters);
		$this->lexical = $body_collector;
		$statements = [];
		if ($derived_constructor) {
			$c = $this->cursor;
			$start = $c->position();
			$spelling = $c->tokens->text_at($start);
			if ($source->flavor === flavor::python && $c->keyword('super')
				&& isset($c->tokens->tokens[$start + 4]) && $c->tokens->text_at($start + 4) === '__init__') {
				$this->python_super_member();
				$args = $this->constructor_arguments($named, $unpacked);
				$end = $c->take(token_kind::newline, 'NEWLINE') + 1;
				$base = new ast_base_initialization($start, $end, $args, true, named: $named, unpacked: $unpacked);
			} elseif (($source->flavor === flavor::php && $spelling === 'parent'
					&& $c->tokens->text_at($start + 2) === '__construct')
				|| ($source->flavor === flavor::js && $spelling === 'super' && $c->next_is(token_kind::lparen))) {
				$c->name(token_kind::identifier);
				if ($source->flavor === flavor::php) {
					$c->take(token_kind::scope, '::');
					if ($c->name(token_kind::identifier) !== '__construct') throw new diagnostic($source, $c->offset(), 'Expected parent constructor');
				}
				$args = $this->constructor_arguments($named, $unpacked);
				$end = $c->take(token_kind::semicolon, ';') + 1;
				$base = new ast_base_initialization($start, $end, $args, true, named: $named, unpacked: $unpacked);
			}
			$base ??= new ast_base_initialization($start, $start, [], false);
			$statement = new ast_base_statement($base);
			$body_collector->statement($statement, 0);
			$statements[] = $statement;
		}
		while (!$this->cursor->is($close))
		{
			if ($this->cursor->at_end()) {
				throw new diagnostic($source, $this->cursor->offset(), 'Expected }');
			}
			$statement = $this->statement($grammar, $body_collector);
			$body_collector->statement($statement, count($statements));
			$statements[] = $statement;
		}
		$end = $this->close_suite($close, $statements);
		$this->lexical = $previous;
		$this->declaring_type = null;
		return $body_collector->body($body_start, $end, $statements, $signature_shared);
	}

	/** Read named headers with the flavor's function keyword and return separator. */
	public function named_function_header(bool $keyword = true, bool $static = false, bool $private = false, bool $protected = false, ?string $alias = null,
		bool $python_receiver = false): ast_function_declaration
	{
		$start = $this->cursor->position();
		$python = $this->cursor->tokens->source->flavor === flavor::python;
		if ($keyword) $this->cursor->take_keyword($python ? 'def' : 'function');
		$returns_reference = $this->cursor->tokens->source->flavor === flavor::php && $this->cursor->is(token_kind::ampersand);
		if ($returns_reference) $this->cursor->take(token_kind::ampersand, '&');
		$name = $this->cursor->name(token_kind::identifier);
		$parameters = $this->parameters($python_receiver);
		$this->cursor->take($python ? token_kind::member : token_kind::colon, $python ? '-> (explicit return type)' : ': (explicit return type)');
		$type = $this->return_type(allow_union: true);
		return new ast_function_declaration($alias ?? $name, $type,
			$start, $this->cursor->position(), false, $this->root->scope->namespace, $parameters, $static, $private, $protected, $returns_reference);
	}

	public function source_namespace(): ?string
	{
		return $this->root->scope->namespace;
	}

	/**
	 * Parse explicit positional passing modes and optional literal-default syntax.
	 * @return ast_parameter_declaration[]
	 */
	public function parameters(bool $python_receiver = false, bool $lambda = false): array /** list<ast_parameter_declaration> */
	{
		$this->cursor->take(token_kind::lparen, '(');
		if ($python_receiver) {
			$this->cursor->take_keyword('self');
			if (!$this->cursor->is(token_kind::rparen)) $this->cursor->take(token_kind::comma, ', after self');
		}
		$parameters = [];
		while (!$this->cursor->is(token_kind::rparen)) {
			$start = $this->cursor->position();
			if (in_array($this->cursor->tokens->source->flavor, [flavor::js, flavor::python], true)) {
				$mode = $this->cursor->tokens->source->flavor === flavor::python ? passing_mode::value : $this->parameter_mode();
				$name = $this->cursor->name(token_kind::identifier);
				$type = null;
				if (!$lambda || $this->cursor->is(token_kind::colon)) {
					$this->cursor->take(token_kind::colon, ': (explicit parameter type)');
					$type = $this->type_reference(allow_void: true, parameter: true);
				}
			}
			elseif ($lambda && $this->cursor->tokens->source->flavor === flavor::php && $this->cursor->is(token_kind::ellipsis)) {
				$type = new ast_variadic_type_reference(null, $this->cursor->take(token_kind::ellipsis, '...') + 1);
				$mode = passing_mode::value;
				$name = $this->cursor->binding_name(token_kind::variable);
			}
			elseif ($lambda && ($this->cursor->is(token_kind::ampersand) || $this->cursor->is(token_kind::variable)
				|| ($this->cursor->tokens->source->flavor === flavor::cpp && $this->cursor->is(token_kind::identifier)
					&& ($this->cursor->next_is(token_kind::rparen) || $this->cursor->next_is(token_kind::comma) || $this->cursor->next_is(token_kind::assign))))) {
				$type = null;
				$mode = $this->parameter_mode();
				$name = $this->cursor->binding_name($this->cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier);
			}
			else {
				if ($this->cursor->tokens->source->flavor === flavor::php
					&& ($this->cursor->is(token_kind::variable)
						|| ($this->cursor->is(token_kind::ampersand) && $this->cursor->next_is(token_kind::variable)))) {
					throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected explicit parameter type before parameter name');
				}
				$readonly = $this->cursor->tokens->source->flavor === flavor::php && $this->cursor->keyword('const');
				if ($readonly) $this->cursor->take_keyword('const');
				$type = $this->type_reference(allow_void: true, parameter: true);
				if ($readonly) {
					$this->cursor->take(token_kind::ampersand, '& after const parameter type');
					$mode = passing_mode::readonly_reference;
				} else $mode = $this->parameter_mode();
				if ($mode === passing_mode::value && $this->cursor->tokens->source->flavor === flavor::php
					&& $this->cursor->is(token_kind::ellipsis)) {
					$type = new ast_variadic_type_reference($type, $this->cursor->take(token_kind::ellipsis, '...') + 1);
				}
				$name = $this->cursor->binding_name($this->cursor->tokens->source->flavor === flavor::php
					? token_kind::variable : token_kind::identifier);
			}
			if ($type instanceof ast_variadic_type_reference && !$this->cursor->is(token_kind::rparen)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Variadic parameter must be last and has no default');
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
	private function intersection_follows(): bool
	{
		$c = $this->cursor;
		return $c->is(token_kind::ampersand) && $c->next_is(token_kind::identifier)
			&& ($c->tokens->source->flavor !== flavor::cpp
				|| in_array($c->tokens->tokens[$c->position() + 2]->kind ?? null, [token_kind::identifier, token_kind::ampersand], true));
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
	private function call_statement(ast_call_expression $call, token_kind $terminator = token_kind::semicolon): ast_call_statement
	{
		$end = $this->statement_end($terminator);
		return new ast_call_statement($call->start, $end, $call);
	}

	/** Calls own ordinary value arguments; nested calls use the same parser and AST node. */
	private function call(): ast_expression
	{
		$start = $this->cursor->position();
		if ($this->cursor->is(token_kind::variable)) {
			$name = $this->cursor->name(token_kind::variable);
			$this->arrow?->read($name, $start);
			return $this->call_arguments($start, $name, null, null, call_qualifier::local);
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
		if ($this->cursor->next_is(token_kind::ellipsis)
			&& ($this->cursor->tokens->tokens[$this->cursor->position() + 2]->kind ?? null) === token_kind::rparen) {
			$this->cursor->take(token_kind::lparen, '(');
			$this->cursor->take(token_kind::ellipsis, '...');
			$end = $this->cursor->take(token_kind::rparen, ')') + 1;
			if ($qualifier !== call_qualifier::ordinary || ($receiver !== null
				&& !($separator === token_kind::dot && str_contains($name, '\\')))) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->tokens->tokens[$start]->offset, 'Callback reference requires a named free function or explicitly qualified static method');
			}
			return new ast_callable_reference($name, $start, $end);
		}
		$labels = [];
		$unpacked = false;
		$arguments = $this->arguments($labels, $unpacked);
		if ($unpacked) return new ast_unpacked_call($start, $this->cursor->position(), $name, $separator, $arguments, $receiver, $qualifier);
		if ($labels) return new ast_named_call($start, $this->cursor->position(), $name, $separator, $arguments, $receiver, $labels, $qualifier);
		return new ast_call_expression($start, $this->cursor->position(), $name, $separator, $arguments, $receiver, $qualifier);
	}

	/** @return ast_expression[] Only a constructor with labels owns a named binding. */
	private function constructor_arguments(?Named_Arguments &$named, ?bool &$unpacked): array /** list<ast_expression> */
	{
		$labels = [];
		$unpacked = false;
		$arguments = $this->arguments($labels, $unpacked);
		$named = $labels ? new Named_Arguments($labels) : null;
		return $arguments;
	}

	/**
	 * Parse operands in written order. A direct call can also collect labels by operand index.
	 * @param string[]|null $labels
	 * @return ast_expression[]
	 */
	private function arguments(?array &$labels = null, ?bool &$unpacked = null): array /** list<ast_expression> */
	{
		$this->cursor->take(token_kind::lparen, '(');
		$arguments = [];
		while (!$this->cursor->is(token_kind::rparen)) {
			if ($this->cursor->is(token_kind::identifier) && ($this->cursor->next_is(token_kind::colon)
				|| ($this->cursor->tokens->source->flavor === flavor::python && $this->cursor->next_is(token_kind::assign)))) {
				if ($labels === null) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), ast_named_call::UNSUPPORTED_TARGET);
				$labels[count($arguments)] = $this->cursor->name(token_kind::identifier);
				$this->cursor->take($this->cursor->tokens->source->flavor === flavor::python ? token_kind::assign : token_kind::colon, 'argument label separator');
			} elseif ($labels) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Positional argument after named argument');
			}
			if ($this->cursor->is(token_kind::ellipsis)) {
				if ($unpacked === null || $labels) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Unpacking requires a positional variadic call');
				$this->cursor->take(token_kind::ellipsis, '...');
				$unpacked = true;
			}
			$arguments[] = $this->value(argument_operand: true);
			if ($unpacked && !$this->cursor->is(token_kind::rparen)) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Unpacked vector must be the final argument');
			}
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

	public function return_type(bool $allow_union = false): ast_type_reference
	{
		return $this->type_reference(allow_void: true, allow_union: $allow_union);
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
	private function return_statement(token_kind $terminator = token_kind::semicolon): ast_return_statement
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword('return');
		$value = $this->cursor->is($terminator) ? null : $this->value();
		$end = $this->statement_end($terminator);
		return new ast_return_statement($start, $end, $value);
	}

	/** Type keywords and identifiers share generated registration; preparation owns argument admission. */
	public function named_type(bool $allow_union = false): ast_type_reference
	{
		return $this->type_reference(allow_union: $allow_union);
	}

	/** Build immutable ordered argument chains without an intermediate array; commas belong to the containing type. */
	private function type_reference(string $argument_of = '', bool $allow_void = false, bool $parameter = false, bool $allow_union = false): ast_type_reference
	{
		$start = $this->cursor->position();
		$child = null;
		if ($this->cursor->is(token_kind::question)) {
			$this->cursor->take(token_kind::question, '?');
			$word = 'nullable';
			$child = $this->type_reference();
		} else {
			$word = $this->cursor->tokens->text_at($start);
			if ($this->cursor->is(token_kind::keyword)
				&& (($word === 'weak') || ($word === 'function' && $this->cursor->tokens->source->flavor === flavor::php) || (type_registry::resolve($word) !== null) || ($allow_void && $word === 'void'))) {
				$this->cursor->take_keyword($word);
			} else {
				$word = $this->cursor->name(token_kind::identifier);
			}
			if ($this->cursor->is(token_kind::less)) {
				$this->cursor->take(token_kind::less, '<');
				$child = $this->type_reference($word, true);
				$this->cursor->take(token_kind::greater, '>');
			}
		}
		$end = $this->cursor->position();
		if ($this->cursor->tokens->source->flavor !== flavor::python
			&& ((($parameter || $allow_union) && $this->cursor->is(token_kind::pipe)) || ($parameter && $this->intersection_follows()))) {
			$operator = $this->cursor->tokens->tokens[$end]->kind;
			$separator = $operator === token_kind::pipe ? '|' : '&';
			$this->cursor->take($operator, $separator);
			$next = $this->named_type();
			$first = new ast_type_reference($start, $end, $word, $child, $next);
			return new ast_compound_type_reference($start, $next->end, $separator, $first);
		}
		if ($argument_of === 'callback' && $this->cursor->is(token_kind::ellipsis)) {
			$element = new ast_type_reference($start, $end, $word, $child);
			return new ast_variadic_type_reference($element, $this->cursor->take(token_kind::ellipsis, '...') + 1);
		}
		$reference = ($argument_of === 'callback' || $argument_of === 'function') && $this->cursor->is(token_kind::ampersand);
		if ($reference) $end = $this->cursor->take(token_kind::ampersand, '&') + 1;
		$next = null;
		if ($argument_of === 'function') {
			$this->cursor->take(token_kind::lparen, '( after callable result type');
			// Inputs reuse the comma-separated callback component grammar.
			if (!$this->cursor->is(token_kind::rparen)) $next = $this->type_reference('callback');
			$this->cursor->take(token_kind::rparen, ') after callable inputs');
		} elseif ($argument_of !== '' && in_array($argument_of, type_registry::EXTENT_FAMILIES, true)) {
			$this->cursor->take(token_kind::comma, ", before $argument_of extent");
			$at = $this->cursor->position();
			if ($this->cursor->is(token_kind::integer)) {
				$this->cursor->take(token_kind::integer, 'extent');
				$next = new ast_extent_literal($this->cursor->tokens, $at);
			} else {
				$name = $this->names->path(true);
				$next = new ast_extent_reference($at, $this->cursor->position(), $name);
			}
		} elseif ($argument_of !== '' && $this->cursor->is(token_kind::comma)) {
			$this->cursor->take(token_kind::comma, ',');
			$next = $this->type_reference($argument_of, true);
		}
		return $reference ? new ast_callable_reference_component($start, $end, $word, $child, $next) : new ast_type_reference($start, $end, $word, $child, $next);
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

	/** A bare throw or raise keeps its lexical rethrow context. */
	private function throw_statement(string $keyword, token_kind $terminator): ast_statement
	{
		if ($this->cursor->next_is($terminator)) {
			$start = $this->cursor->position();
			$this->cursor->take_keyword($keyword);
			return new ast_rethrow_statement($start, $this->statement_end($terminator));
		}
		$operation = $this->throw_expression($keyword);
		$expected = $terminator === token_kind::semicolon ? '; after throw' : $terminator->label();
		return new ast_throw_statement($operation, $this->cursor->take($terminator, $expected) + 1);
	}

	/** A selected throw evaluates its full operand up to the enclosing delimiter. */
	private function throw_expression(string $keyword = 'throw'): ast_throw_expression
	{
		$start = $this->cursor->position();
		$this->cursor->take_keyword($keyword);
		return new ast_throw_expression($start, $this->value());
	}

	/** Parse logical, comparison and arithmetic precedence; grouping uses the same expression parser. */
	public function value(int $minimum_precedence /** uint8 */ = 0, bool $argument_operand = false): ast_expression
	{
		$python = $this->cursor->tokens->source->flavor === flavor::python;
		$start = $this->cursor->position();
		$left = $python && $this->cursor->keyword('not')
			? $this->python_not($minimum_precedence) : $this->primary(argument_operand: $argument_operand);
		while (!$this->cursor->at_end()) {
			if ($this->cursor->keyword('instanceof') && $minimum_precedence <= binary_operator::power->precedence()) {
				$this->cursor->take_keyword('instanceof');
				$target = $this->named_type();
				$left = new ast_object_access_expression($left->start, $this->cursor->position(), $left, $target, true);
				continue;
			}
			if ($python && $minimum_precedence <= ast_ternary_expression::PRECEDENCE && $this->cursor->keyword('if')) {
				$this->cursor->take_keyword('if');
				$condition = $this->value(ast_ternary_expression::PRECEDENCE + 1);
				$this->cursor->take_keyword('else');
				$when_false = $this->value(ast_ternary_expression::PRECEDENCE);
				return new ast_ternary_expression($condition, $left, $when_false, $start, $this->cursor->position());
			}
			$kind = $this->cursor->tokens->tokens[$this->cursor->position()]->kind;
			if ($kind === token_kind::question && $minimum_precedence <= ast_ternary_expression::PRECEDENCE) {
				$this->cursor->take(token_kind::question, '?');
				if ($this->cursor->is(token_kind::colon)) {
					throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Full ternary requires a true branch');
				}
				$when_true = $this->value();
				$this->cursor->take(token_kind::colon, ': after ternary true branch');
				$when_false = $this->value(ast_ternary_expression::PRECEDENCE);
				$left = new ast_ternary_expression($left, $when_true, $when_false, $start, $this->cursor->position());
				continue;
			}
			$operator = match ($kind) {
				token_kind::keyword => match (true) {
					$python && $this->cursor->keyword('and') => binary_operator::logical_and,
					$python && $this->cursor->keyword('or') => binary_operator::logical_or,
					$this->cursor->keyword('xor') => binary_operator::logical_xor,
					default => $python ? $this->python_word_comparison() : null,
				},
				token_kind::coalesce => binary_operator::coalesce,
				token_kind::logical_and => binary_operator::logical_and,
				token_kind::logical_or => binary_operator::logical_or,
				token_kind::plus => binary_operator::add,
			token_kind::dot => $this->cursor->tokens->source->flavor === flavor::php ? binary_operator::concatenate : null,
				token_kind::minus => binary_operator::subtract,
				token_kind::star => binary_operator::multiply,
				token_kind::slash => $python ? binary_operator::true_divide : binary_operator::divide,
				token_kind::double_slash => binary_operator::floor_divide,
				token_kind::percent => $python ? binary_operator::floor_remainder : binary_operator::remainder,
				token_kind::ampersand => binary_operator::bit_and,
				token_kind::pipe => binary_operator::bit_or,
				token_kind::caret => binary_operator::bit_xor,
				token_kind::power => binary_operator::power,
				token_kind::spaceship => binary_operator::compare,
				token_kind::identical => binary_operator::identical,
				token_kind::not_identical => binary_operator::not_identical,
				default => self::comparison_operator($kind),
			};
			$shift = !$python && (($kind === token_kind::less) || ($kind === token_kind::greater))
				? $this->cursor->tokens->operator_text_at($this->cursor->position()) : '';
			if ($shift === '<<' || $shift === '>>') {
				$operator = $shift === '<<' ? binary_operator::shift_left : binary_operator::shift_right;
			}
			if ($operator === null) break;
			$precedence = $python ? Python_Grammar::precedence($operator) : $operator->precedence();
			if ($precedence < $minimum_precedence) break;
			$comparison = $python && $precedence === Python_Grammar::COMPARISON_PRECEDENCE;
			$this->take_binary_operator($kind, $operator);
			if ($shift === '<<' || $shift === '>>') {
				$this->cursor->take($kind, 'shift operator');
			}
			$rhs_start = $this->cursor->position();
			$right = $operator === binary_operator::coalesce && $this->cursor->keyword('throw')
				? $this->throw_expression()
				: $this->value($precedence + (($operator === binary_operator::power || $operator === binary_operator::coalesce) ? 0 : 1));
			$key_test = $left instanceof ast_index_expression && $right instanceof ast_name_expression
				&& ($operator === binary_operator::identical || $operator === binary_operator::not_identical)
				&& $right->name === 'undefined' && $this->cursor->position() === $rhs_start + 1
				&& $this->cursor->tokens->text_at($rhs_start) === 'undefined';
			$links = null;
			if ($comparison) {
				while (!$this->cursor->at_end()) {
					$next = $this->cursor->tokens->tokens[$this->cursor->position()]->kind;
					$continuation = $next === token_kind::keyword ? $this->python_word_comparison() : self::comparison_operator($next);
					if ($continuation === null) break;
					// Every interior operand is a value. The unbound missing-key marker belongs to standalone tests.
					$links ??= [new ast_binary_expression($left, $operator, $right)];
					$this->take_binary_operator($next, $continuation);
					$middle = $right;
					$right = $this->value($precedence + 1);
					$links[] = new ast_continued_comparison($middle, $continuation, $right);
				}
			}
			if ($links !== null) $left = new ast_comparison_chain($start, $this->cursor->position(), $links);
			elseif ($key_test) $left = new ast_key_comparison($left, $operator, $right);
			else $left = new ast_binary_expression($left, $operator, $right);
		}
		return $left;
	}

	/** Consume a binary operator, including the second keyword of Python's two-word comparisons. */
	private function take_binary_operator(token_kind $kind, binary_operator $operator): void
	{
		$this->cursor->take($kind, 'binary operator');
		if ($kind !== token_kind::keyword) return;
		if ($operator === binary_operator::key_not_in) $this->cursor->take_keyword('in');
		if ($operator === binary_operator::not_identical) $this->cursor->take_keyword('not');
	}

	/** Recognize both words of an identity or membership operator before consuming either. */
	private function python_word_comparison(): ?binary_operator
	{
		if ($this->cursor->keyword('is')) {
			return $this->cursor->next_is(token_kind::keyword) && $this->cursor->tokens->text_at($this->cursor->position() + 1) === 'not'
				? binary_operator::not_identical : binary_operator::identical;
		}
		if ($this->cursor->keyword('in')) return binary_operator::key_in;
		if ($this->cursor->keyword('not') && $this->cursor->next_is(token_kind::keyword)
			&& $this->cursor->tokens->text_at($this->cursor->position() + 1) === 'in') return binary_operator::key_not_in;
		return null;
	}

	/** Python not binds below comparisons and above and. */
	private function python_not(int $minimum_precedence /** uint8 */): ast_not_expression
	{
		$precedence = binary_operator::logical_and->precedence() + 1;
		if ($minimum_precedence > $precedence) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Parenthesize not in this Python operand');
		}
		$start = $this->cursor->position();
		$this->cursor->take_keyword('not');
		$operand = $this->value($precedence);
		return new ast_not_expression($start, $this->cursor->position(), $operand);
	}

	/** Recognize cast syntax and diagnose historical PHP cast targets at their source span. */
	private function cast_ahead(): bool
	{
		$tokens = $this->cursor->tokens;
		$start = $this->cursor->position();
		if (!$this->cursor->is(token_kind::lparen)
			|| (($tokens->tokens[$start + 2]->kind ?? null) !== token_kind::rparen)) {
			return false;
		}
		$type = $tokens->text_at($start + 1);
		$kind = $tokens->tokens[$start + 3]->kind ?? null;
		if (($type === 'mixed' || $type === 'dynamic') && $kind === token_kind::lbracket) return true;
		if ($tokens->source->flavor === flavor::python) return false;
		$historical_cast = $tokens->source->flavor === flavor::php && ($type === 'object' || $type === 'array');
		if (!$historical_cast && (type_registry::resolve($type) === null) && ($type !== 'void')) {
			return false;
		}
		$has_operand = in_array($kind, [token_kind::identifier, token_kind::variable, token_kind::keyword,
			token_kind::integer, token_kind::floating, token_kind::single_string, token_kind::double_string, token_kind::template_string,
			token_kind::lparen, token_kind::bang, token_kind::tilde, token_kind::backslash, token_kind::scope], true)
			|| (!$historical_cast && (($kind === token_kind::minus) || ($kind === token_kind::plus))
				&& in_array($tokens->tokens[$start + 4]->kind ?? null, [token_kind::integer, token_kind::floating], true));
		if ($historical_cast && $has_operand) {
			throw new diagnostic($tokens->source, $this->cursor->offset(), 'Unsupported PHP (' . $type . ') cast');
		}
		return $has_operand;
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

	/** A bare prefix or postfix update is allowed only as the complete RHS of a plain local statement. */
	private function local_assignment_value(): ast_expression
	{
		$cursor = $this->cursor;
		$name = $cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier;
		if ($cursor->is(token_kind::increment) || $cursor->is(token_kind::decrement)
			|| ($cursor->is($name) && ($cursor->next_is(token_kind::increment) || $cursor->next_is(token_kind::decrement)))) {
			return new ast_update_expression($this->update_statement(null));
		}
		return $this->assignment_value();
	}

	/** Preserve direct signed decimal literals (including INT64_MIN) inside a constant expression. */
	public function integer_initializer(): ast_expression
	{
		$this->integer_initializer = true;
		try {
			return $this->value();
		} finally {
			$this->integer_initializer = false;
		}
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

	/** Parse ordered bracket entries, Python dictionary keys or PHP/JS brace property names. */
	private function collection_literal(bool $dictionary): ast_collection_expression|ast_keyed_collection_expression
	{
		$c = $this->cursor;
		$open = $dictionary ? token_kind::lbrace : token_kind::lbracket;
		$close = $dictionary ? token_kind::rbrace : token_kind::rbracket;
		$separator = $dictionary ? token_kind::colon : token_kind::arrow;
		$start = $c->take($open, $open->label());
		$elements = [];
		$entries = [];
		while (!$c->is($close)) {
			if ($dictionary && $c->tokens->source->flavor !== flavor::python) {
				$at = $c->position();
				if ($c->is(token_kind::identifier) || $c->is(token_kind::keyword)) {
					$c->take($c->tokens->tokens[$at]->kind, 'property name');
					$element = new ast_property_name_string($at, $at + 1);
				} elseif ($c->is(token_kind::single_string) || $c->is(token_kind::double_string)) {
					$element = $this->atom();
					if (!($element instanceof ast_string_expression)) throw new diagnostic($c->tokens->source, $c->tokens->tokens[$at]->offset, 'Expected literal property name');
				} else throw new diagnostic($c->tokens->source, $c->offset(), 'Expected property name');
			} else $element = $this->value();
			if ($dictionary || $c->is($separator)) {
				if ($elements !== []) throw new diagnostic($c->tokens->source, $c->offset(), 'Cannot mix positional and keyed entries');
				$c->take($separator, $separator->label());
				$entries[] = new ast_keyed_entry($element, $this->value());
			} else {
				if ($entries !== []) throw new diagnostic($c->tokens->source, $c->offset(), 'Cannot mix keyed and positional entries');
				$elements[] = $element;
			}
			if (!$c->is(token_kind::comma)) break;
			$c->take(token_kind::comma, ',');
		}
		$end = $c->take($close, $close->label()) + 1;
		return $dictionary || $entries !== [] ? new ast_keyed_collection_expression($start, $end, $entries)
			: new ast_collection_expression($start, $end, $elements);
	}

	/** Parse a keyed write or append on a named local or field; preparation checks writability. */
	private function indexed_write(ast_expression $receiver, token_kind $terminator = token_kind::semicolon): ast_statement
	{
		$this->cursor->take(token_kind::lbracket, '[');
		$index = $this->cursor->is(token_kind::rbracket) ? null : $this->value();
		$index_end = $this->cursor->take(token_kind::rbracket, ']') + 1;
		if ($terminator === token_kind::newline && ($index === null || (!$this->cursor->is(token_kind::assign) && !$this->cursor->is(token_kind::dot)))) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Python indexed write requires one key and =');
		}
		if ($index !== null && $this->cursor->is(token_kind::lbracket)) {
			return $this->indexed_write(new ast_index_expression($receiver, $index, $index_end), $terminator);
		}
		if ($index !== null && (($this->cursor->tokens->source->flavor === flavor::php && $this->cursor->is(token_kind::lparen)) || $this->cursor->is(token_kind::scope)
			|| $this->cursor->is(token_kind::member) || $this->cursor->is(token_kind::dot))) {
			$target = $this->postfix(new ast_index_expression($receiver, $index, $index_end), false);
			if ($target instanceof ast_field_expression && $terminator === token_kind::newline) {
				$this->cursor->take(token_kind::assign, '= after Python field');
				$value = $this->assignment_value();
				return new ast_field_assignment($target, $value, $this->statement_end($terminator));
			}
			if ($target instanceof ast_qualified_data || $target instanceof ast_field_expression) return $this->property_write($target);
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
		if (!($receiver instanceof ast_name_expression || $receiver instanceof ast_field_expression || ($index !== null && $receiver instanceof ast_index_expression))) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Indexed assignment requires named or field storage');
		}
		$this->cursor->take(token_kind::assign, '=');
		$value = $this->assignment_value();
		$end = $this->statement_end($terminator);
		return $index === null ? new ast_append_statement($receiver, $value, $end)
			: new ast_index_assignment($receiver, $index, $value, $end);
	}

	/** Read the receiver of a paired Python loop without constructing an items call. */
	private function python_items_receiver(): ast_expression
	{
		$c = $this->cursor;
		$receiver = $this->postfix($this->atom(), false, stop_before_items: true);
		if (!$this->python_items_suffix_ahead()) {
			throw new diagnostic($c->tokens->source, $c->offset(), 'Python paired traversal requires terminal .items()');
		}
		$c->take(token_kind::dot, '.');
		$c->name(token_kind::identifier);
		$c->take(token_kind::lparen, '(');
		$c->take(token_kind::rparen, ')');
		return $receiver;
	}

	/** Recognize the contextual suffix only when it ends the loop header. */
	private function python_items_suffix_ahead(): bool
	{
		$c = $this->cursor;
		$at = $c->position();
		$tokens = $c->tokens->tokens;
		return $c->is(token_kind::dot) && $c->next_is(token_kind::identifier)
			&& $c->tokens->text_at($at + 1) === 'items'
			&& ($tokens[$at + 2]->kind ?? null) === token_kind::lparen
			&& ($tokens[$at + 3]->kind ?? null) === token_kind::rparen
			&& ($tokens[$at + 4]->kind ?? null) === token_kind::colon;
	}

	/** Admit generated core scalar literals and flavor-correct names without resolving declarations. */
	private function primary(bool $field_target = false, bool $argument_operand = false): ast_expression
	{
		return $this->postfix($this->atom($argument_operand), $field_target);
	}

	/** Continue member calls and indexing on the expression already parsed. */
	private function postfix(ast_expression $value, bool $field_target, bool $stop_before_items = false): ast_expression
	{
		while (true) {
			$flavor = $this->cursor->tokens->source->flavor;
			if ($flavor === flavor::python && !$this->cursor->is(token_kind::dot) && !$this->cursor->is(token_kind::lbracket)) break;
			if ($flavor === flavor::php && $value instanceof ast_index_expression && $this->cursor->is(token_kind::lparen)) {
				$value = $this->call_arguments($value->start, 'indexed callable', null, $value, call_qualifier::stored_value);
				continue;
			}
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
				if ($stop_before_items && $this->python_items_suffix_ahead()) break;
				$this->cursor->take(token_kind::dot, '.');
				$name = $this->cursor->name(token_kind::identifier);
				$value = ($flavor === flavor::js || $flavor === flavor::python) && !$this->cursor->is(token_kind::lparen)
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

	/** Consume the zero-argument ancestor qualifier and its named member. */
	private function python_super_member(): string
	{
		$c = $this->cursor;
		$c->take_keyword('super');
		$c->take(token_kind::lparen, '(');
		$c->take(token_kind::rparen, ') after super');
		$c->take(token_kind::dot, '. after super()');
		return $c->name(token_kind::identifier);
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
			flavor::python => null,
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

	/** A result-less C++ or JS arrow at a callback argument must remain distinguishable from a call before a match-arm separator. */
	private function lambda_start(bool $argument_operand): bool
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
			elseif ($kind === token_kind::rparen && --$depth === 0) {
				$next = $tokens[$index + 1]->kind ?? null;
				return $next === token_kind::colon || ($argument_operand && $next === token_kind::arrow);
			}
		}
		return false;
	}

	/** Parse the parameters, return type and body of a closure. */
	private function lambda_expression(bool $block = false): ast_lambda_expression
	{
		$c = $this->cursor;
		$start = $c->take($c->tokens->tokens[$c->position()]->kind, 'fn');
		$reference = $c->tokens->source->flavor === flavor::php && $c->is(token_kind::ampersand);
		if ($reference) $c->take(token_kind::ampersand, '&');
		$parameters = $this->parameters(lambda: true);
		$captures = [];
		$receiver_capture = false;
		if ($block && $c->keyword('use')) {
			$c->take_keyword('use');
			$c->take(token_kind::lparen, '( after use');
			while (true) {
				$mode = $this->parameter_mode();
				$at = $c->position();
				$name = $c->name(token_kind::variable);
				if ($name === 'this') {
					if ($mode === passing_mode::reference || $receiver_capture) {
						throw new diagnostic($c->tokens->source, $c->tokens->tokens[$at]->offset,
							$receiver_capture ? 'Duplicate closure capture: this' : 'The receiver cannot be captured with &$this');
					}
					$receiver_capture = true;
				} else {
					$captures[] = new ast_closure_capture($name, $at, by_reference: $mode === passing_mode::reference);
				}
				if (!$c->is(token_kind::comma)) break;
				$c->take(token_kind::comma, ',');
			}
			$c->take(token_kind::rparen, ') after captures');
		}
		$result = null;
		if ($c->is(token_kind::colon)) {
			$c->take(token_kind::colon, ': (lambda result type)');
			$result = $this->return_type();
		}
		if ($block) {
			$outer_scope = $this->lexical;
			$collector = new Body_Collection($this->root->scope, $parameters);
			$outer_arrow = $this->arrow;
			foreach ($captures as $capture) {
				$collector->declare_local($capture);
				$outer_arrow?->read($capture->declaration_key(), $capture->start);
			}
			$this->arrow = null;
			try {
				$body = $this->lexical_block($this->grammar(), $collector, '{ before closure body', $collector);
				return new ast_lambda_expression($start, $parameters, $result, $body, $captures, $receiver_capture, $reference);
			} finally {
				$this->lexical = $outer_scope;
				$this->arrow = $outer_arrow;
			}
		}
		$c->take(token_kind::arrow, '=>');
		if ($c->tokens->source->flavor === flavor::php) {
			$outer_arrow = $this->arrow;
			$this->arrow = new Arrow_Captures($parameters, $outer_arrow, $this->lexical);
			try {
				$body = $this->value();
				return new ast_lambda_expression($start, $parameters, $result, $body, $this->arrow->captures(), returns_reference: $reference);
			} finally {
				$this->arrow = $outer_arrow;
			}
		}
		$outer = $this->lambda_names;
		$this->lambda_names = [];
		foreach ($parameters as $parameter) $this->lambda_names[$parameter->declaration_key()] = true;
		try {
			return new ast_lambda_expression($start, $parameters, $result, $this->value());
		} finally {
			$this->lambda_names = $outer;
		}
	}

	/** Braced and indented suites share await/timer syntax with their own terminator. */
	private function await_statement(token_kind $terminator): ast_statement
	{
		$c = $this->cursor;
		if ($c->next_is(token_kind::identifier) && $c->tokens->text_at($c->position() + 1) === 'async_sleep_ms') {
			$start = $c->position();
			$c->take_keyword('await');
			$c->take(token_kind::identifier, 'async_sleep_ms');
			$c->take(token_kind::lparen, '(');
			$duration = $this->value();
			$c->take(token_kind::rparen, ')');
			return new ast_delay_statement($start, $this->statement_end($terminator), $duration);
		}
		$value = $this->await_expression();
		return new ast_await_statement($value, $this->statement_end($terminator));
	}

	/** Parse direct task consumption in either expression or statement position. */
	private function await_expression(): ast_await_expression
	{
		$c = $this->cursor;
		$start = $c->position();
		$c->take_keyword('await');
		$call = $this->call();
		if (!($call instanceof ast_call_expression)) throw new diagnostic($c->tokens->source, $c->offset(), 'Await requires a direct async call');
		return new ast_await_expression($start, $call);
	}

	/** Contextual expression introducers. Ordinary names and calls keep their normal grammar. */
	private function keyword_block_start(): ?keyword_block
	{
		$c = $this->cursor;
		if (!$c->is(token_kind::identifier) || $c->tokens->source->flavor === flavor::python) return null;
		$kind = keyword_block::tryFrom($c->tokens->text_at($c->position()));
		if ($kind === null || $c->next_is(token_kind::lbrace)) return $kind;
		if (!$c->next_is(token_kind::lparen)) return null;
		// A settings list belongs to a block only when its closing parenthesis precedes a brace.
		$depth = 1;
		for ($i = $c->position() + 2; isset($c->tokens->tokens[$i]); ++$i) {
			$token = $c->tokens->tokens[$i]->kind;
			if ($token === token_kind::lparen) ++$depth;
			if ($token !== token_kind::rparen) continue;
			--$depth;
			if ($depth === 0) return ($c->tokens->tokens[$i + 1]->kind ?? null) === token_kind::lbrace ? $kind : null;
		}
		return null;
	}

	/** Only the introducer is shared. Each block parser owns its contents and builds its own expression. */
	private function keyword_block(keyword_block $kind): ast_expression
	{
		$start = $this->cursor->take(token_kind::identifier, $kind->value);
		return match ($kind) {
			keyword_block::parallel => $this->parallel_body($start),
		};
	}

	/** Read group settings and the ordered entries submitted together. */
	private function parallel_body(int $start /** uint32 */): ast_parallel_expression
	{
		$jobs = [];
		$c = $this->cursor;
		$workers = null;
		if ($c->is(token_kind::lparen)) {
			$c->take(token_kind::lparen, '(');
			if ($c->name(token_kind::identifier) !== 'workers') throw new diagnostic($c->tokens->source, $c->offset(), 'Expected group workers setting');
			$c->take(token_kind::colon, ': after workers');
			$workers = $this->value();
			$c->take(token_kind::rparen, ') after group workers');
		}
		$c->take(token_kind::lbrace, '{');
		while (!$c->is(token_kind::rbrace)) {
			if ($c->is(token_kind::identifier) && $c->tokens->text_at($c->position()) === 'job'
				&& $c->next_is(token_kind::lparen) && ($c->tokens->tokens[$c->position() + 3]->kind ?? null) === token_kind::colon) {
				$jobs[] = $this->inline_job();
				continue;
			}
			if (!$this->names->call_start()) throw new diagnostic($c->tokens->source,
				$c->offset(), 'Expected worker call or } in parallel block');
			$jobs[] = $this->call();
			$c->take(token_kind::semicolon, '; after parallel worker call');
		}
		$end = $c->take(token_kind::rbrace, '}') + 1;
		return new ast_parallel_expression($start, $end, $jobs, $workers);
	}

	/** Binding expressions belong to the caller; only their aliases enter the worker body. */
	private function inline_job(): ast_inline_job
	{
		$c = $this->cursor;
		$start = $c->take(token_kind::identifier, 'job');
		$c->take(token_kind::lparen, '(');
		$parameters = [];
		$inputs = [];
		$binding_kinds = [];
		$iteration = null;
		$outer_arrow = $this->arrow;
		do {
			if ($parameters !== []) $c->take(token_kind::comma, ',');
			$at = $c->position();
			$kind = $c->name(token_kind::identifier);
			if ($kind === 'workers') {
				if ($binding_kinds !== ['each'] || $inputs[0] instanceof ast_job_repetition)
					throw new diagnostic($c->tokens->source, $c->offset(), 'Job workers must appear once, immediately after each');
				$c->take(token_kind::colon, ': after workers');
				// The cap is evaluated outside the iteration scope, like the bound.
				$index_arrow = $this->arrow;
				$this->arrow = $outer_arrow;
				try {
					$workers = $this->value();
				} finally {
					$this->arrow = $index_arrow;
				}
				$inputs[0] = new ast_job_repetition($inputs[0], $workers);
				continue;
			}
			if (!in_array($kind, ['read', 'write', 'value', 'each'], true)) throw new diagnostic($c->tokens->source,
				$c->tokens->tokens[$at]->offset, 'Expected each, read, write or value in inline job');
			$binding_kinds[] = $kind;
			$c->take(token_kind::colon, ': after binding kind');
			$at = $c->position();
			$name = $c->binding_name($c->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier);
			$parameters[] = new ast_parameter_declaration($name, null, count($parameters), $at, $c->position());
			if ($kind === 'each') {
				if (count($parameters) !== 1) throw new diagnostic($c->tokens->source, $c->offset(), 'each must be the first job clause');
				$iteration = $this->lexical->produced_binding($name, $at, false);
				if ($c->keyword('in')) $c->take_keyword('in');
				elseif ($c->name(token_kind::identifier) !== 'in') throw new diagnostic($c->tokens->source, $c->offset(), 'Expected in after each index');
				$zero = $c->take(token_kind::integer, '0 before ..<');
				if ($c->tokens->text_at($zero) !== '0') throw new diagnostic($c->tokens->source, $c->offset(), 'each ranges start at zero');
				$c->take(token_kind::dot, '..<');
				$c->take(token_kind::dot, '..<');
				$c->take(token_kind::less, '..<');
			} else $c->take(token_kind::assign, '= before binding expression');
			$inputs[] = $this->value();
			if ($kind === 'each' && $outer_arrow !== null) {
				// Supply the index locally while forwarding other capture requests to the enclosing arrow.
				$this->arrow = new Arrow_Captures([$parameters[0]], $outer_arrow, $this->lexical);
			}
		} while ($c->is(token_kind::comma));
		$this->arrow = $outer_arrow;
		$c->take(token_kind::rparen, ') after job bindings');
		$outer = $this->lexical;
		$arrow = $this->arrow;
		$names = $this->lambda_names;
		$this->arrow = null;
		$this->lambda_names = null;
		$collector = new Body_Collection($this->root->scope, $parameters);
		try {
			$body = $this->lexical_block($this->grammar(), $collector, '{ before inline job body', $collector);
		} finally {
			$this->lexical = $outer;
			$this->arrow = $arrow;
			$this->lambda_names = $names;
		}
		return new ast_inline_job(new ast_lambda_expression($start, $parameters, null, $body), $inputs, $binding_kinds, $iteration);
	}

	/** Parse one primary or prefix expression before postfix indexing. */
	private function atom(bool $argument_operand = false): ast_expression
	{
		$c = $this->cursor;
		$block = $this->keyword_block_start();
		if ($block !== null) return $this->keyword_block($block);
		if ($c->keyword('await')) return $this->await_expression();
		if ($c->tokens->source->flavor === flavor::php && $c->keyword('function')) return $this->lambda_expression(true);
		if ($c->tokens->source->flavor !== flavor::python && $this->lambda_start($argument_operand)) return $this->lambda_expression();
		if ($c->keyword('throw')) {
			throw new diagnostic($c->tokens->source, $c->offset(), 'Throw expression requires a ?? fallback or match default');
		}
		if ($c->keyword('super') && $c->tokens->source->flavor === flavor::python) {
			$start = $c->position();
			$name = $this->python_super_member();
			if ($name === '__init__') throw new diagnostic($c->tokens->source, $c->tokens->tokens[$start]->offset, 'super().__init__ must start a derived constructor');
			return $this->call_arguments($start, $name, token_kind::dot, null, call_qualifier::lexical_parent);
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
				$labels = [];
				if ($c->keyword('default')) $c->take_keyword('default');
				elseif (ast_match_expression::is_condition_subject($subject)) $labels[] = $this->value();
				else {
					$labels[] = $this->constant_label();
					while ($c->is(token_kind::comma)) {
						$c->take(token_kind::comma, ',');
						$labels[] = $this->constant_label();
					}
				}
				$c->take(token_kind::arrow, '=>');
				$result = $labels === [] && $c->keyword('throw') ? $this->throw_expression() : $this->value();
				$arms[] = new ast_match_arm($labels, $result, $arm_start, $c->position());
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
			$named = null;
			$unpacked = false;
			if ($this->cursor->is(token_kind::lparen)) $arguments = $this->constructor_arguments($named, $unpacked);
			elseif ($this->cursor->tokens->source->flavor !== flavor::php) $this->cursor->take(token_kind::lparen, '( after the constructed type');
			return new ast_new_expression($start, $this->cursor->position(), $target, $arguments, $named, $unpacked);
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
		if ($this->cursor->is(token_kind::lbracket)) return $this->collection_literal(false);
		if ($this->cursor->is(token_kind::lbrace) && $this->cursor->tokens->source->flavor !== flavor::cpp) {
			return $this->collection_literal(true);
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
			// Keep the sign on a single integer token when its range must include the signed minimum.
			if ($kind !== token_kind::tilde && $operand instanceof ast_integer_expression
				&& $operand->start === $start + 1 && $operand->end === $start + 2 && $operand->end === $this->cursor->position()
				&& ($this->integer_initializer || preg_match('/^0[xXoO]/', $this->cursor->tokens->text_at($operand->start)) === 1)) {
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
			$operand = $c->is(token_kind::lbracket) ? $this->collection_literal(false) : $this->value(binary_operator::power->precedence());
			return new ast_cast_expression($start, $this->cursor->position(), $type, $operand);
		}
		if ($this->cursor->is(token_kind::lparen)) {
			$start = $c->take(token_kind::lparen, '(');
			$name_kind = $c->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier;
			if ($c->is($name_kind) && $c->next_is(token_kind::assign)) {
				$at = $c->position();
				$target = new ast_name_expression($c->binding_name($name_kind), $at, $at + 1);
				$this->arrow?->read($target->name, $at);
				$c->take(token_kind::assign, '=');
				$rhs = $this->assignment_value();
				return new ast_condition_assignment($start, $c->take(token_kind::rparen, ') after assignment') + 1, $target, $rhs);
			}
			$value = $this->value(argument_operand: $argument_operand);
			$c->take(token_kind::rparen, ')');
			if ($c->tokens->source->flavor === flavor::php && $c->is(token_kind::lparen)) {
				if ($value instanceof ast_field_expression) return $this->call_arguments($start, $value->field, null, $value, call_qualifier::stored_value);
				// Every PHP static property read ends in :: followed by a variable token.
				if ($value instanceof ast_name_expression && !$value->constant_only && $value->end - $value->start >= 3
					&& $c->tokens->tokens[$value->end - 2]->kind === token_kind::scope) {
					return $this->call_arguments($start, $value->name, null, $value, call_qualifier::stored_value);
				}
			}
			return $value;
		}
		if (($this->declaring_type !== null || $this->arrow !== null) && !$this->cursor->at_end()) {
			$start = $this->cursor->position();
			$self = match ($c->tokens->source->flavor) {
				flavor::php => '$this',
				flavor::python => 'self',
				default => 'this',
			};
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
				return Interpolation::parse($this->cursor->tokens, $start, $this->arrow);
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
		if ($this->cursor->tokens->source->flavor === flavor::python) {
			$word = match ($word) {
				'True' => 'true', 'False' => 'false', 'None' => 'null', 'true', 'false', 'null' => '', default => $word
			};
		}
		if (type_registry::literal($word) !== null) {
			$kind = $this->cursor->tokens->tokens[$start]->kind;
			$this->cursor->take($kind, 'scalar literal');
			return new ast_keyword_expression($word, $start, $start + 1);
		}
		$constant_only = false;
		$type_marker = false;
		$name = $this->names->variable_path($constant_only, $type_marker);
		if ($type_marker) return new ast_type_marker($name, $start, $this->cursor->position());
		if ($this->arrow !== null && $c->tokens->tokens[$start]->kind === token_kind::variable) $this->arrow->read($name, $start);
		$member_syntax = ($this->cursor->tokens->source->flavor === flavor::js)
			&& (!str_starts_with($name, '\\')) && str_contains($name, '\\');
		return new ast_name_expression($name, $start, $this->cursor->position(), $member_syntax, $constant_only);
	}

	/** $start: token position. */
	public function declaration(int $start /** uint32 */, string $name, ?ast_type_reference $type_reference, ?token_kind $terminator = token_kind::semicolon): ast_local_declaration
	{
		if ($type_reference !== null && $this->cursor->is(token_kind::lparen)) {
			$at = $this->cursor->position();
			$arguments = $this->constructor_arguments($named, $unpacked);
			if ($arguments === []) throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Use a typed declaration without () for default initialization');
			$value = new ast_struct_default($at, $this->cursor->position(), $arguments, $named, $unpacked);
			return new ast_local_declaration($start, $this->statement_end($terminator), $name, $type_reference, $value);
		}
		if ($type_reference !== null && ($this->cursor->is(token_kind::semicolon) || ($terminator === null && $this->cursor->is(token_kind::comma)))) {
			$at = $this->cursor->position();
			return new ast_local_declaration($start, $this->statement_end($terminator), $name, $type_reference, new ast_struct_default($at, $at));
		}
		$this->cursor->take(token_kind::assign, '=');
		if ($this->cursor->tokens->source->flavor === flavor::php && $this->cursor->is(token_kind::ampersand)) {
			if ($terminator !== token_kind::semicolon) {
				throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Local aliases require an ordinary statement block');
			}
			$this->cursor->take(token_kind::ampersand, '&');
			$target = $this->assignment_value();
			return new ast_local_alias($start, $this->statement_end($terminator), $name, $type_reference, $target);
		}
		$value = $terminator === token_kind::semicolon ? $this->local_assignment_value() : $this->assignment_value();
		$end = $this->statement_end($terminator);
		return new ast_local_declaration($start, $end, $name, $type_reference, $value);
	}

	/** Recognize expression-led calls and property targets before parsing their postfix operations. */
	private function expression_statement_start(): bool
	{
		$flavor = $this->cursor->tokens->source->flavor;
		return $this->keyword_block_start() !== null || $this->names->call_start() || $this->cursor->is(token_kind::lparen) || ($this->cursor->is(token_kind::keyword)
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
		?token_kind $terminator = token_kind::semicolon): ast_property_update
	{
		if (!($target instanceof ast_field_expression) && !($target instanceof ast_name_expression)) {
			throw new diagnostic($this->cursor->tokens->source, $this->cursor->offset(), 'Expected writable property');
		}
		$end = $this->statement_end($terminator);
		return new ast_property_update($start, $end, $target, $operator, $postfix);
	}

	/** Recognize update syntax at discarded-value statement and header boundaries. */
	private function update_start(): bool
	{
		return $this->cursor->is(token_kind::increment) || $this->cursor->is(token_kind::decrement)
			|| $this->cursor->next_is(token_kind::increment) || $this->cursor->next_is(token_kind::decrement);
	}

	/** Parse local updates in statements or loop headers; allow property targets in statements and the final for clause. */
	private function update_statement(?token_kind $terminator, bool $allow_property = false): ast_update_statement
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
					return new ast_index_update($start, $this->statement_end($terminator), $target, $operator, false);
				}
				return $this->property_update($start, $target, $operator, false, $terminator);
			}
		}
		$name = $cursor->name($cursor->tokens->source->flavor === flavor::php ? token_kind::variable : token_kind::identifier);
		if ($postfix) $operator = $this->take_update_operator();
		$end = $this->statement_end($terminator);
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
		?token_kind $terminator = token_kind::semicolon): ast_assignment_statement
	{
		$operator = $this->compound_operator();
		$kind = $operator === null ? token_kind::assign : $this->cursor->tokens->tokens[$this->cursor->position()]->kind;
		$this->cursor->take($kind, 'assignment operator');
		$value = $operator === null && $terminator === token_kind::semicolon
			? $this->local_assignment_value() : $this->assignment_value();
		$end = $this->statement_end($terminator);
		return new ast_assignment_statement($start, $end, $name, $value, $intent, $operator);
	}

	/** Header item spans exclude the separator; ordinary statement spans include their terminator. */
	private function statement_end(?token_kind $terminator): int /** uint32 */
	{
		return $terminator === null ? $this->cursor->position() : $this->cursor->take($terminator, $terminator->label()) + 1;
	}

	/** STATIC METHODS */
	/** Map a comparison token to its binary operator. */
	private static function comparison_operator(token_kind $kind): ?binary_operator
	{
		return match ($kind) {
			token_kind::equal => binary_operator::equal,
			token_kind::not_equal => binary_operator::not_equal,
			token_kind::less => binary_operator::less,
			token_kind::less_equal => binary_operator::less_equal,
			token_kind::greater => binary_operator::greater,
			token_kind::greater_equal => binary_operator::greater_equal,
			default => null,
		};
	}
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
