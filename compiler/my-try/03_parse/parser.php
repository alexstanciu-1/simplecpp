<?php

/*
 * Role: parse retained tokens and collect occurrences.
 * Call map: Source_Frontend::run -> Parser::parse -> Parser_Run::parse -> statement/expression -> Symbol_Collector.
 */
namespace scpp\compiler;

final class Parser
{
	private token_list $tokens;
	private ?scope $target_scope = null;
	private ?parsed_file $previous = null;
	private ?scope $global = null;
	private ?parsed_file $parsed_result = null;

	public function __construct(token_list $tokens, ?scope $target_scope = null, ?parsed_file $previous = null, ?scope $global = null)
	{
		$this->tokens = $tokens;
		$this->target_scope = $target_scope;
		$this->previous = $previous;
		$this->global = $global;
	}

	/** Select one file update; prior declarations are mutable identities, not a snapshot. */
	public function init(token_list $tokens, ?scope $target_scope = null, ?parsed_file $previous = null, ?scope $global = null): void
	{
		$this->tokens = $tokens;
		$this->target_scope = $target_scope;
		$this->previous = $previous;
		$this->global = $global;
		$this->parsed_result = null;
	}

	public function parse(): parsed_file
	{
		$run = new Parser_Run($this->tokens, $this->target_scope, $this->previous, $this->global);
		$this->parsed_result = $run->result();
		return $run->parse();
	}

	/** Failed files keep partial declaration identities for the next update, but remain incomplete. */
	public function result(): ?parsed_file
	{
		return $this->parsed_result;
	}
}

/** One file worker; collector methods execute synchronously inside this parser. */
final class Parser_Run
{
	private token_list $tokens;
	private bool $reuse_previous = false;
	private Storage $scopes /** Storage<scope> */;
	private scope $current_scope;
	private scope $source_scope;
	private Symbol_Collector $collector;
	private parsed_file $parsed;
	private int $position = 0;
	private bool $shared_publication = false;
	private Storage $selected_bodies /** Storage<namespace_body_node> */;
	private \SplObjectStorage $claimed_bodies /** hash<bool, shared<namespace_body_node>> */;

	/** Establish the retained file and symbol inventory before any declaration can be registered. */
	public function __construct(token_list $tokens, ?scope $target_scope = null, ?parsed_file $previous = null, ?scope $global = null)
	{
		if ($previous !== null) {
			if (($tokens !== $previous->tokens) && ($tokens->first_token === 0) && ($tokens->content_offset === 0)) {
				Token_Cleanup::file($previous);
				Token_Buffer::append($tokens, $previous->tokens);
			}
		}
		$tokens->retained_ranges = new Storage /** Storage<retained_token_range> */();
		$this->tokens = $tokens;
		$this->position = $tokens->first_token;
		$this->scopes = new Storage /** Storage<scope> */();
		$this->shared_publication = $global !== null;
		$this->selected_bodies = new Storage /** Storage<namespace_body_node> */();
		$this->claimed_bodies = new \SplObjectStorage /** hash<bool, shared<namespace_body_node>> */();
		$selected_scope /** nullable<scope> */ = $target_scope;
		if ($previous !== null) {
			if (q_count($previous->root->namespace_bodies) === 1) {
				$prior_scope = $previous->root_scope();
				$identity = $prior_scope->source_namespace();
				if ($identity === null) {
					$selected_scope = $prior_scope;
				}
				elseif ($identity->path() === '') {
					$selected_scope = $prior_scope;
				}
				elseif ($global === null) {
					// Standalone callers may supply an implicit namespace environment explicitly.
					$prior_body = $previous->root->single_body();
					if ($prior_body->start_token() === $prior_body->end_token()) {
						$selected_scope = $prior_scope;
					}
					elseif ($previous->tokens->text_at($prior_body->start_token()) !== 'namespace') {
						$selected_scope = $prior_scope;
					}
				}
			}
		}
		$file_scope /** scope */ = $selected_scope ?? new scope();
		if ($previous !== null) {
			$prior_bodies /** Storage<namespace_body_node> */ = $previous->root->namespace_bodies;
			if ($prior_bodies->is_empty()) {
				// Reappearance after completed deletion starts fresh syntax in the retained source.
				$revived_root = new file_node($file_scope);
				$revived_root->set_span(0, 0);
				$previous->root = $revived_root;
				$previous->collection->root = $revived_root;
			}
		}
		if ($previous === null) {
			$root = new file_node($file_scope);
			$root->set_span(0, 0);
			$new_collection = new collected_file($tokens);
			$new_collection->root = $root;
			$this->parsed = new parsed_file($tokens, $root, $new_collection, new Storage /** Storage<scope> */());
		}
		else
		{
			$this->parsed = $previous;
			$retired_scopes /** Storage<scope> */ = $previous->collection->retired_scopes;
			$prior_scopes /** Storage<scope> */ = $previous->scopes;
			foreach ($prior_scopes as $prior_scope) {
				$retired_scopes->append($prior_scope);
			}
			if ($previous->complete && $previous->collection->parse_complete) {
				$this->reuse_previous = true;
			}
		}
		$this->source_scope = $file_scope;
		if ($global !== null) {
			$file_scope->set_parent($global);
			$file_scope->set_publication($global);
		}
		$owned_scopes /** Storage<scope> */ = $this->parsed->scopes;
		$owned_scopes->append($file_scope);
		$this->parsed->complete = false;
		$this->parsed->collection->parse_complete = false;

		$this->parsed->tokens = $tokens;
		$collection = $this->parsed->collection;
		$revision = (int)$collection->revision + 1;
		if ($revision > 4294967295) {
			$entries /** Storage<collected_name> */ = $collection->entries;
			foreach ($entries as $entry) {
				$entry->revision = 0;
			}
			$revision = 1;
		}
		$this->collector = new Symbol_Collector($collection, $tokens, $file_scope, $global, $revision);
	}

	/** Keep old and new scopes owned on failure; select the next successful file inventory separately. */
	private function retain_scope(scope $retained_scope): void
	{
		$selected /** Storage<scope> */ = $this->scopes;
		$selected->append($retained_scope);
		$owned /** Storage<scope> */ = $this->parsed->scopes;
		$owned->append($retained_scope);
	}

	public function result(): parsed_file
	{
		return $this->parsed;
	}

	/** Reparse selected member spans into a private collection; the caller supplies ownership, not name resolution. */
	public function parse_member_fragments(collected_record $consumer, Storage $origins /** Storage<declaration_node> */): parsed_file
	{
		$this->current_scope = $this->source_scope;
		$this->retain_scope($this->source_scope);
		$root = $this->parsed->root;
		$body = $root->single_body();
		$declarations /** Storage<declaration_node> */ = $body->declarations;
		foreach ($origins as $origin) {
			$this->position = $origin->start_token();
			$member /** declaration_node */;
			if ($origin instanceof method_node) {
				$modifiers = $this->member_modifiers();
				$member = $this->callable_declaration($consumer, $modifiers, $origin->start_token(), $this->source_scope);
			}
			elseif ($origin instanceof property_node) {
				$member = $this->field_declaration($this->source_scope);
			}
			elseif ($origin instanceof member_constant_node) {
				$modifiers = $this->member_modifiers();
				$member = $this->constant_declaration($consumer, $modifiers, $origin->start_token(), $this->source_scope);
			}
			else { throw new \LogicException('Member fragment requires a method, property or constant origin'); }
			if ($this->position !== $origin->end_token()) {
				throw new \LogicException('Member fragment did not consume its complete source span');
			}
			$declarations->append($member);
		}
		$root->set_span($this->tokens->first_token, $this->tokens->end_token);
		$body->set_span($root->start_token(), $root->end_token());
		$body->body->set_span($root->start_token(), $root->start_token());
		$this->collector->finish($root);
		$this->parsed->scopes = $this->scopes;
		$this->parsed->complete = true;
		$this->parsed->collection->parse_complete = true;
		return $this->parsed;
	}

	/** Namespace occurrences share one body parser and retain identities by path and occurrence order. */
	public function parse(): parsed_file
	{
		$root = $this->parsed->root;
		if ($this->text() !== 'namespace') {
			$implicit = $this->select_namespace_body('', $this->source_scope);
			$this->parse_source_body($implicit, false, $this->position);
		}
		while ($this->position < $this->tokens->end_token)
		{
			$start = $this->position;
			$this->expect('namespace');
			$path = '';
			if ($this->text() !== '{') {
				if (!$this->identifier()) {
					throw new \RuntimeException($this->error_message('Expected namespace path'));
				}
				$path = $this->text();
				$this->position++;
				while ($this->text() === '\\') {
					$this->position++;
					if (!$this->identifier()) {
						throw new \RuntimeException($this->error_message('Expected namespace path segment'));
					}
					$path .= '\\' . $this->text();
					$this->position++;
				}
			}
			$braced = $this->text() === '{';
			$this->expect($braced ? '{' : ';');
			$namespace_body = $this->select_namespace_body($path, null);
			$this->parse_source_body($namespace_body, $braced, $start);
		}
		$previous_bodies /** Storage<namespace_body_node> */ = $root->namespace_bodies;
		$retired /** Storage<namespace_body_node> */ = $root->retired_namespace_bodies;
		$retired_scopes /** Storage<scope> */ = $this->parsed->collection->retired_scopes;
		foreach ($previous_bodies as $previous_body) {
			if (!isset($this->claimed_bodies[$previous_body])) {
				$retired->append($previous_body);
				// Includes a first parse's unused implicit placeholder, not only prior parses.
				$retired_scopes->append($previous_body->body_scope());
				$retired_scopes->append($previous_body->body->local_scope());
			}
		}
		$root->namespace_bodies = $this->selected_bodies;
		$root->set_span($this->tokens->first_token, $this->position);
		$this->collector->finish($root);
		$this->parsed->scopes = $this->scopes;
		$this->parsed->complete = true;
		$this->parsed->collection->parse_complete = true;
		return $this->parsed;
	}

	/** New occurrences remain owned even on failure; never move a definition between namespaces. */
	private function select_namespace_body(string $path, ?scope $implicit_scope): namespace_body_node
	{
		$selected /** nullable<namespace_body_node> */ = null;
		$bodies /** Storage<namespace_body_node> */ = $this->parsed->root->namespace_bodies;
		foreach ($bodies as $candidate)
		{
			if (isset($this->claimed_bodies[$candidate])) {
				continue;
			}
			$identity = $candidate->body_scope()->source_namespace();
			$candidate_path = $identity === null ? '' : $identity->path();
			if ($implicit_scope !== null) {
				if ($candidate->body_scope() !== $implicit_scope) {
					continue;
				}
			}
			elseif ($candidate_path !== $path) {
				continue;
			}
			$selected = $candidate;
			break;
		}
		if ($selected === null) {
			$local_scope = $implicit_scope ?? new scope();
			if ($implicit_scope === null) {
				if ($this->shared_publication) {
					task_synchronize(function () use ($local_scope, $path): void {
						$identity = Model::$namespaces->register($path);
						$local_scope->set_parent($identity->published_scope());
						$local_scope->set_publication($identity->published_scope());
					});
				}
				else {
					$identity = Model::$namespaces->register($path);
					$local_scope->set_parent($identity->published_scope());
				}
			}
			// Own the scope before the temporary expires; namespace bodies observe it weakly.
			$this->retain_scope($local_scope);
			$selected = new namespace_body_node($local_scope);
			$bodies->append($selected);
		}
		else {
			$this->retain_scope($selected->body_scope());
		}
		$body /** namespace_body_node */ = $selected;
		$this->claimed_bodies[$body] = true;
		$next /** Storage<namespace_body_node> */ = $this->selected_bodies;
		$next->append($body);
		return $body;
	}

	/** Compare executable statements within this occurrence, independently of its declarations. */
	private function parse_source_body(namespace_body_node $namespace_body, bool $braced, int $source_start): void
	{
		$this->source_scope = $namespace_body->body_scope();
		$this->current_scope = new scope();
		$this->current_scope->set_parent($this->source_scope);
		$this->retain_scope($this->current_scope);
		$published /** nullable<scope> */ = null;
		if ($this->shared_publication) {
			$published = $this->source_scope->published_scope();
		}
		$this->collector->select_source_scope($this->source_scope, $published);
		$body_start = $this->position;
		$old_body = $namespace_body->body;
		$old_statements /** Storage<statement_node> */ = $old_body->statements;
		$body = new function_body_node($this->current_scope);
		$statements /** Storage<statement_node> */ = $body->statements;
		$declarations /** Storage<declaration_node> */ = new Storage();
		$imports /** Storage<import_node> */ = new Storage();
		$body_changed = !$this->reuse_previous;
		$index = 0;
		while ($this->position < $this->tokens->end_token)
		{
			if ($braced) {
				if ($this->text() === '}') {
					break;
				}
			}
			elseif ($this->text() === 'namespace') {
				break;
			}
			$start = $this->position;
			if ($this->text() === 'use') {
				$this->import_statement($imports);
				continue;
			}
			if ($this->text() === 'const') {
				$declarations->append($this->constant_declaration(null, /** vector<declaration_modifier> */ [], -1));
				continue;
			}
			if ($this->text() === 'enum') {
				$declarations->append($this->enum_declaration());
				continue;
			}
			$node = $this->statement();
			if ($node instanceof declaration_node) {
				$declarations->append(object_cast($node, declaration_node::class));
				continue;
			}
			$statement = object_cast($node, statement_node::class);
			$statements->append($statement);
			if (!isset($old_statements[$index])) {
				$body_changed = true;
			}
			else {
				$old = $old_statements[$index];
				if (!$this->same_tokens($old->start_token(), $old->end_token(), $start, $this->position)) {
					$body_changed = true;
				}
			}
			$index++;
		}
		if ($index !== q_count($old_statements)) {
			$body_changed = true;
		}
		if (!$body_changed) {
			foreach ($statements as $index => $temporary) {
				$old = $old_statements[$index];
				$this->retain_range($old, $temporary->start_token());
			}
			$body = $old_body;
			$this->retain_scope($body->local_scope());
		}
		else {
			$this->transfer_body_work($old_body, $body);
		}
		$body->syntax_changed = $body->syntax_changed || $body_changed;
		$body->set_span($body_start, $this->position);
		$namespace_body->body = $body;
		$namespace_body->declarations = $declarations;
		$this->reconcile_imports($namespace_body, $imports);
		if ($braced) {
			$this->expect('}');
		}
		$namespace_body->set_span($source_start, $this->position);
	}

	/** Imports are syntax, not executable statements or published declarations. */
	private function import_statement(Storage $imports /** Storage<import_node> */): void
	{
		$this->expect('use');
		$restriction = import_kind::symbol;
		if ($this->text() === 'function') { $restriction = import_kind::function_name; $this->position++; }
		elseif ($this->text() === 'const') { $restriction = import_kind::constant; $this->position++; }
		$start = $this->position;
		if ($this->text() === '\\') { $this->position++; }
		$prefix = '';
		$grouped = false;
		while (true) {
			if (!$this->identifier()) { throw new \RuntimeException($this->error_message('Expected import path segment')); }
			$prefix .= $this->text();
			$this->position++;
			if ($this->text() !== '\\') { break; }
			$this->position++;
			$prefix .= '\\';
			if ($this->text() === '{') { $grouped = true; break; }
		}
		if ($grouped) {
			$this->position++;
			do {
				$item_start = $this->position;
				if (!$this->identifier() || ($this->text() === 'function') || ($this->text() === 'const')) {
					throw new \RuntimeException($this->error_message('Expected homogeneous grouped import member'));
				}
				$member = $this->source_name();
				$this->import_binding($imports, $restriction, '\\' . $prefix . $member, $item_start);
				if ($this->text() !== ',') { break; }
				$this->position++;
			} while ($this->text() !== '}');
			$this->expect('}');
		}
		else { $this->import_binding($imports, $restriction, '\\' . $prefix, $start); }
		$this->expect(';');
	}

	private function import_binding(Storage $imports /** Storage<import_node> */, import_kind $restriction,
		string $target, int $start): void
	{
		$binding = new import_node();
		$binding->restriction = $restriction;
		$binding->target = $target;
		$binding->alias = Source_Name_Path::member($target);
		if ($this->text() === 'as') {
			$this->position++;
			if (!$this->identifier()) { throw new \RuntimeException($this->error_message('Expected import alias')); }
			$binding->alias = $this->text();
			$this->position++;
		}
		$this->finish_node($binding, $start);
		$imports->append($binding);
	}

	/** Preserve unchanged binding identity; analysis observes changed membership itself. */
	private function reconcile_imports(namespace_body_node $body, Storage $imports /** Storage<import_node> */): void
	{
		$previous /** Storage<import_node> */ = $body->imports;
		$selected /** Storage<import_node> */ = new Storage();
		$used /** hash<bool, shared<import_node>> */ = new \SplObjectStorage /** hash<bool, shared<import_node>> */();
		foreach ($imports as $binding) {
			$match = $binding;
			foreach ($previous as $old) {
				if (isset($used[$old])) { continue; }
				if (($old->target === $binding->target) && ($old->alias === $binding->alias) && ($old->restriction === $binding->restriction)) {
					$match = $old;
					$used[$old] = true;
					$old->set_span($binding->start_token(), $binding->end_token());
					break;
				}
			}
			$selected->append($match);
		}
		$body->imports = $selected;
		if (q_count($selected) !== q_count($previous)) { $body->imports_changed = true; }
		else {
			foreach ($selected as $index => $binding) {
				if ($binding !== $previous[$index]) { $body->imports_changed = true; }
			}
		}
		$body->body_scope()->set_imports($selected);
	}

	/** Match braces only to delimit comparison; changed bodies still go through normal parsing. */
	private function body_end(): int
	{
		if ($this->text() !== '{') {
			return $this->position;
		}
		$depth = 0;
		$rows /** Storage<token> */ = $this->tokens->tokens;
		for ($index = $this->position; $index < $this->tokens->end_token; $index++)
		{
			$text = $rows[$index]->text();
			if ($text === '{') {
				$depth++;
			}
			elseif ($text === '}') {
				$depth = $depth - 1;
				if ($depth === 0) {
					return $index + 1;
				}
			}
		}
		return $this->tokens->end_token;
	}

	/** Preserve work identity on replacement without deciding analysis scheduling state. */
	private function transfer_body_work(function_body_node $old, function_body_node $body): void
	{
		$work = $old->detach_work();
		if ($work !== null) {
			$work->rebind_body($body);
			$body->attach_work($work);
		}
	}

	/** Retain indexes during compilation; collection and deferred cleanup use this interval. */
	private function retain_range(ast_node $node, int $current_first): void
	{
		$ranges /** Storage<retained_token_range> */ = $this->tokens->retained_ranges;
		$ranges->append(new retained_token_range($node->start_token(), $node->end_token(), $current_first));
	}

	/** Existing half-open token bounds identify the exact body bytes, including internal whitespace. */
	private function same_body_text(int $old_start, int $old_end, int $start, int $end): bool
	{
		if ((!$this->reuse_previous) || ($old_start < 0)) {
			return false;
		}
		if (($old_end <= $old_start) || ($end <= $start)) {
			return false;
		}
		$current /** Storage<token> */ = $this->tokens->tokens;
		$old_first = $current[$old_start];
		$old_last = $current[$old_end - 1];
		$first = $current[$start];
		$last = $current[$end - 1];
		$old_length = (int)$old_last->offset + (int)$old_last->length - (int)$old_first->offset;
		$length = (int)$last->offset + (int)$last->length - (int)$first->offset;
		if ($old_length !== $length) {
			return false;
		}
		return string_byte_slice($this->tokens->content, (int)$old_first->offset, $old_length) === string_byte_slice($this->tokens->content, (int)$first->offset, $length);
	}

	/** Token spellings preserve literal accuracy and ignore source offset/whitespace changes. */
	private function same_tokens(int $old_start, int $old_end, int $start, int $end): bool
	{
		if ((!$this->reuse_previous) || ($old_start < 0)) {
			return false;
		}
		if (($old_end - $old_start) !== ($end - $start)) {
			return false;
		}
		while ($start < $end) {
			if ($this->tokens->text_at($old_start) !== $this->tokens->text_at($start)) {
				return false;
			}
			$old_start++;
			$start++;
		}
		return true;
	}

	/** Refresh source spans without replacing a matched declaration. */
	private function finish_declaration(ast_node $node, int $start, bool $same): void
	{
		$entry = object_cast($node->optional_occurrence(), collected_name::class);
		if (!$same && ($entry->change_status !== change_state::added)) {
			$entry->change_status = change_state::changed;
		}
		$node->set_span($start, $this->position);
	}

	/** Select a statement from its leading syntax; names are never looked up here. */
	private function statement(): ast_node
	{
		if (($this->text() === 'unset') || ($this->text() === 'clean'))
		{
			$cleaning = $this->text() === 'clean';
			$start = $this->position++;
			$removal /** cleanup_node */ = $cleaning
				? object_cast(new clean_node(), cleanup_node::class)
				: object_cast(new unset_node(), cleanup_node::class);
			$this->expect('(');
			$removal->target = $this->expression();
			$this->expect(')');
			$this->expect(';');
			$removal->set_span($start, $this->position);
			return $removal;
		}
		if ($this->text() === 'switch') { return $this->switch_statement(); }
		if ($this->text() === 'while') { return $this->while_statement(); }
		if ($this->text() === 'do') { return $this->do_while_statement(); }
		if ($this->text() === 'for') { return $this->for_statement(); }
		if ($this->text() === 'foreach') { return $this->foreach_statement(); }
		if (($this->text() === 'break') || ($this->text() === 'continue')) {
			return $this->control_transfer();
		}
		if ($this->text() === 'if') {
			return $this->conditional_arm(if_arm_kind::initial);
		}
		if ($this->text() === '{') {
			return $this->statement_block();
		}
		if (($this->text() === 'struct') || ($this->text() === 'class') || ($this->text() === 'data')
			|| ($this->text() === 'interface') || ($this->text() === 'trait') || ($this->text() === 'final') || ($this->text() === 'abstract')) {
			return $this->type_declaration();
		}
		if ((($this->text() === 'function') || ($this->text() === 'template'))) {
			return $this->function_declaration();
		}
		if ($this->text() === 'return') {
			return $this->return_statement();
		}
		if (string_byte_starts_with($this->text(), '$'))
		{
			$next = $this->text_at_offset(1);
			if (($next === '\\') || $this->nullable_declaration_ahead() || (Source_Text::identifier($next) && ($next !== 'and') && ($next !== 'or') && ($next !== 'xor'))) {
				return $this->variable_declaration();
			}
			return $this->expression_statement();
		}
		return $this->expression_statement();
	}

	/** Consecutive labels select a single body; a statement begins a new lexical group. */
	private function switch_statement(): switch_node
	{
		$start = $this->position++;
		$selection = new switch_node();
		$this->expect('(');
		$selection->selector = $this->expression();
		$this->expect(')');
		$this->expect('{');
		$groups /** Storage<switch_case_group> */ = $selection->groups;
		while ($this->text() !== '}')
		{
			$group_start = $this->position;
			$group = new switch_case_group();
			$labels /** Storage<switch_label> */ = $group->labels;
			while (($this->text() === 'case') || ($this->text() === 'default'))
			{
				$label_start = $this->position;
				$label = new switch_label($this->text() === 'case' ? switch_label_kind::value : switch_label_kind::fallback);
				$this->position++;
				if ($label->label_kind === switch_label_kind::value) { $label->value = $this->expression(); }
				$this->expect(':');
				$this->finish_node($label, $label_start);
				$labels->append($label);
			}
			if ($labels->is_empty()) {
				throw new \RuntimeException($this->error_message('Expected case or default label in switch'));
			}
			$body_start = $this->position;
			$group->body = new block_node();
			$statements /** Storage<statement_node> */ = $group->body->statements;
			while (($this->text() !== '}') && ($this->text() !== 'case') && ($this->text() !== 'default'))
			{
				if (($this->text() === 'function') || ($this->text() === 'struct') || ($this->text() === 'class') || ($this->text() === 'data') || ($this->text() === 'template')) {
					throw new \RuntimeException($this->error_message('Local declarations of functions or structs are not supported'));
				}
				$statements->append(object_cast($this->statement(), statement_node::class));
			}
			$this->finish_node($group->body, $body_start);
			$this->finish_node($group, $group_start);
			$groups->append($group);
		}
		$this->expect('}');
		$this->finish_node($selection, $start);
		return $selection;
	}

	private function while_statement(): while_node
	{
		$start = $this->position++;
		$loop = new while_node();
		$this->expect('(');
		$loop->condition = $this->expression();
		$this->expect(')');
		$loop->body = $this->statement_block();
		$this->finish_node($loop, $start);
		return $loop;
	}

	private function do_while_statement(): do_while_node
	{
		$start = $this->position++;
		$loop = new do_while_node();
		$loop->body = $this->statement_block();
		$this->expect('while');
		$this->expect('(');
		$loop->condition = $this->expression();
		$this->expect(')');
		$this->expect(';');
		$this->finish_node($loop, $start);
		return $loop;
	}

	/** Header initialization owns statement-root writes; tests and updates are expressions. */
	private function for_statement(): for_node
	{
		$start = $this->position++;
		$loop = new for_node();
		$this->expect('(');
		$initialization /** Storage<statement_node> */ = $loop->initialization;
		while ($this->text() !== ';')
		{
			$next = $this->text_at_offset(1);
			if (string_byte_starts_with($this->text(), '$') && (($next === '\\') || $this->nullable_declaration_ahead() || (Source_Text::identifier($next)
				&& ($next !== 'and') && ($next !== 'or') && ($next !== 'xor')))) {
				$initialization->append($this->variable_declaration(false));
			}
			else {
				$item_start = $this->position;
				$item = new expression_statement_node();
				$item->expression = $this->expression();
				$this->finish_node($item, $item_start);
				$initialization->append($item);
			}
			if ($this->text() !== ',') { break; }
			$this->position++;
			if ($this->text() === ';') {
				throw new \RuntimeException($this->error_message('Expected for initializer after comma'));
			}
		}
		$this->expect(';');
		$this->for_expressions($loop->conditions, ';');
		$this->for_expressions($loop->updates, ')');
		$loop->body = $this->statement_block();
		$this->finish_node($loop, $start);
		return $loop;
	}

	/** One value-iteration grammar owns the source, optional key and value bindings. */
	private function foreach_statement(): foreach_node
	{
		$start = $this->position++;
		$loop = new foreach_node();
		$this->expect('(');
		$loop->iterable = $this->expression();
		$this->expect('as');
		$first = $this->foreach_binding();
		if ($this->text() === '=>') {
			$this->position++;
			$loop->key = $first;
			$loop->value = $this->foreach_binding();
		}
		else {
			$loop->value = $first;
		}
		$this->expect(')');
		$loop->body = $this->statement_block();
		$this->finish_node($loop, $start);
		return $loop;
	}

	/** Syntax-only binding inventory; scope/reuse/type decisions belong to preparation. */
	private function foreach_binding(): variable_reference_node
	{
		if (!string_byte_starts_with($this->text(), '$')) {
			throw new \RuntimeException($this->error_message('foreach requires a simple value binding; references and destructuring are deferred'));
		}
		$start = $this->position++;
		$binding = $this->variable_reference($start);
		$binding->collect_write($this->collector, $this->current_scope, $start);
		return $binding;
	}

	private function for_expressions(Storage $expressions /** Storage<expression_node> */, string $end): void
	{
		if ($this->text() !== $end)
		{
			while (true) {
				$expressions->append($this->expression());
				if ($this->text() !== ',') { break; }
				$this->position++;
			}
		}
		$this->expect($end);
	}

	private function control_transfer(): control_transfer_node
	{
		$start = $this->position;
		$transfer = $this->text() === 'break'
			? object_cast(new break_node(), control_transfer_node::class)
			: object_cast(new continue_node(), control_transfer_node::class);
		$this->position++;
		$this->expect(';');
		$this->finish_node($transfer, $start);
		return $transfer;
	}

	/** Parse an ordinary braced statement body without inventing an executable work owner. */
	private function statement_block(): block_node
	{
		$start = $this->expect('{');
		$body = new block_node();
		$statements /** Storage<statement_node> */ = $body->statements;
		while (($this->text() !== '}') && ($this->text() !== ''))
		{
			if (($this->text() === 'function') || ($this->text() === 'struct') || ($this->text() === 'class') || ($this->text() === 'data') || ($this->text() === 'template')) {
				throw new \RuntimeException($this->error_message('Local declarations of functions or structs are not supported'));
			}
			$statement = $this->statement();
			$statements->append(object_cast($statement, statement_node::class));
		}
		$this->expect('}');
		$this->finish_node($body, $start);
		return $body;
	}

	/** Preserve arm roles and brace bodies; only the initial arm enters the parent sequence. */
	private function conditional_arm(if_arm_kind $kind): if_node
	{
		$start = $this->position++;
		$arm = new if_node($kind);
		if ($kind !== if_arm_kind::else_arm) {
			$this->expect('(');
			$arm->condition = $this->expression();
			$this->expect(')');
		}
		$arm->body = $this->statement_block();
		if ($kind !== if_arm_kind::else_arm)
		{
			if ($this->text() === 'elseif') {
				$arm->next_arm = $this->conditional_arm(if_arm_kind::elseif_arm);
			}
			elseif ($this->text() === 'else') {
				$arm->next_arm = $this->conditional_arm(if_arm_kind::else_arm);
			}
		}
		$this->finish_node($arm, $start);
		return $arm;
	}

	/** Retain the declaration identity while replacing only changed initializer syntax. */
	private function constant_declaration(?collected_member_owner $record, array $modifiers /** vector<declaration_modifier> */,
		int $member_start, ?scope $registration_scope = null): constant_declaration_node
	{
		$start = $this->expect('const');
		if ($member_start >= 0) { $start = $member_start; }
		$declaration_scope = $record === null ? $this->source_scope : $record->syntax()->member_scope();
		if ($registration_scope !== null) { $declaration_scope = $registration_scope; }
		if (!$this->identifier()) { throw new \RuntimeException($this->error_message('Expected constant name')); }
		$name_index = $this->position++;
		$name = $this->tokens->text_at($name_index);
		$previous = $this->collector->previous_constant($declaration_scope, $name);
		$constant /** constant_declaration_node */;
		if ($previous !== null) { $constant = $previous; }
		elseif ($record === null) { $constant = new namespace_constant_node(); }
		else { $constant = new member_constant_node(); }
		if ($constant instanceof member_constant_node) { object_cast($constant, member_constant_node::class)->modifiers = $modifiers; }
		if ($previous === null) { $constant->set_span($start, $start); }
		$old_start = -1;
		$old_end = -1;
		if ($previous !== null) {
			if ($previous->occurrence()->change_status !== change_state::deleted) {
				$old_start = $previous->start_token();
				$old_end = $previous->end_token();
			}
		}
		$constant->name = $name;
		$this->collector->collect_constant($constant, $declaration_scope, $name_index, $record);
		$this->expect('=');
		$saved_scope = $this->current_scope;
		$this->current_scope = $declaration_scope;
		$initializer = $this->expression();
		$this->current_scope = $saved_scope;
		$this->expect(';');
		$same = $this->same_tokens($old_start, $old_end, $start, $this->position);
		if ($same) { $this->retain_range($constant->initializer, $initializer->start_token()); }
		else { $constant->initializer = $initializer; }
		$this->finish_declaration($constant, $start, $same);
		return $constant;
	}

	/** Preserve enum identity and no-op syntax; source meaning belongs to preparation. */
	private function enum_declaration(): enum_node
	{
		$start = $this->expect('enum');
		if (!$this->identifier()) {
			throw new \RuntimeException($this->error_message('Expected enum name'));
		}
		$name_index = $this->position++;
		$name = $this->tokens->text_at($name_index);
		$previous = $this->collector->previous_enum($this->source_scope, $name);
		$node /** enum_node */ = $previous ?? new enum_node();
		if ($previous === null) {
			$node->set_span($start, $start);
		}
		$old_start = -1;
		$old_end = -1;
		if ($previous !== null) {
			if ($previous->occurrence()->change_status !== change_state::deleted) {
				$old_start = $node->start_token();
				$old_end = $node->end_token();
			}
		}
		$node->name = $name;
		$node->collect($this->collector, $this->source_scope, $name_index);
		$backing /** nullable<type_node> */ = null;
		if ($this->text() === ':') {
			$this->position++;
			$backing = $this->type_syntax($this->source_scope);
		}
		$cases /** Storage<enum_case_node> */ = new Storage();
		$this->expect('{');
		$saved_scope = $this->current_scope;
		$this->current_scope = $this->source_scope;
		while ($this->text() !== '}') {
			$case_start = $this->expect('case');
			if (!$this->identifier()) {
				throw new \RuntimeException($this->error_message('Expected enum case name'));
			}
			$case_node = new enum_case_node();
			$case_node->name = $this->text();
			$this->position++;
			if ($this->text() === '=') {
				$this->position++;
				$case_node->value = $this->expression();
			}
			$this->expect(';');
			$case_node->set_span($case_start, $this->position);
			$cases->append($case_node);
		}
		$this->current_scope = $saved_scope;
		$this->expect('}');
		$same = $this->same_tokens($old_start, $old_end, $start, $this->position);
		if ($same) {
			// The declaration name was refreshed above; retain child ranges, not its new occurrence.
			if ($node->backing_type !== null) {
				$parsed_backing /** type_node */ = $backing;
				$this->retain_range($node->backing_type, $parsed_backing->start_token());
			}
			$old_cases /** Storage<enum_case_node> */ = $node->cases;
			foreach ($old_cases as $case_index => $old_case) {
				$this->retain_range($old_case, $cases[$case_index]->start_token());
			}
		}
		else {
			$node->backing_type = $backing;
			$node->cases = $cases;
		}
		$this->finish_declaration($node, $start, $same);
		return $node;
	}

	/** Register the common type identity, then reconcile its declared member categories. */
	private function type_declaration(): type_declaration_node
	{
		if ($this->current_scope->is_function()) {
			throw new \RuntimeException($this->error_message('Local record declarations are not supported yet'));
		}
		$start = $this->position;
		$modifiers /** vector<declaration_modifier> */ = [];
		while (($this->text() === 'data') || ($this->text() === 'final') || ($this->text() === 'abstract')) {
			if ($this->text() === 'data') { $modifiers[] = declaration_modifier::data_initializable; }
			elseif ($this->text() === 'final') { $modifiers[] = declaration_modifier::final_declaration; }
			else { $modifiers[] = declaration_modifier::abstract_declaration; }
			$this->position++;
		}
		if (($this->text() !== 'class') && ($this->text() !== 'struct') && ($this->text() !== 'interface') && ($this->text() !== 'trait')) {
			throw new \RuntimeException($this->error_message('Expected class, struct, interface or trait declaration'));
		}
		$kind = $this->text() === 'class' ? type_declaration_kind::class_type : type_declaration_kind::struct_type;
		if ($this->text() === 'interface') { $kind = type_declaration_kind::interface_type; }
		if ($this->text() === 'trait') { $kind = type_declaration_kind::trait_type; }
		$this->position++;
		if (!$this->identifier()) {
			throw new \RuntimeException($this->error_message('Expected record name'));
		}
		$name_index = $this->position++;
		$name = $this->tokens->text_at($name_index);
		$previous = $this->collector->previous_member_owner($this->source_scope, $name, $kind);
		$record /** type_declaration_node */ = $previous ?? new type_declaration_node();
		if ($previous === null) {
			$record->set_span($start, $start);
		}
		$old_fields /** Storage<property_node> */ = $record->properties;
		$old_methods /** Storage<method_node> */ = $record->methods;
		$old_constants /** Storage<member_constant_node> */ = $record->constants;
		$old_modifiers /** vector<declaration_modifier> */ = $record->modifiers;
		$record->name = $name;
		$record->declaration_kind = $kind;
		$record->modifiers = $modifiers;
		$record->collect($this->collector, $this->source_scope, $name_index);
		$record->member_scope()->set_parent($this->source_scope);
		$old_relationship_text = '';
		if ($previous !== null) {
			// Original syntax owns the old header; compare spellings below through retained names.
			$old_relationship_text = $this->relationship_key($record);
		}
		$record->base_class = null;
		$record->extended_interfaces = new Storage /** Storage<named_type_node> */();
		$record->implemented_interfaces = new Storage /** Storage<named_type_node> */();
		if ($this->text() === 'extends') {
			$this->position++;
			if ($kind === type_declaration_kind::interface_type) {
				$record->extended_interfaces = $this->relationship_names();
			}
			else {
				$record->base_class = $this->relationship_name();
			}
		}
		if ($this->text() === 'implements') {
			$this->position++;
			$record->implemented_interfaces = $this->relationship_names();
		}
		$fields /** Storage<property_node> */ = new Storage();
		$methods /** Storage<method_node> */ = new Storage();
		$constants /** Storage<member_constant_node> */ = new Storage();
		$uses /** Storage<trait_use_node> */ = new Storage();
		$this->expect('{');
		while ($this->text() !== '}') {
			$member_start = $this->position;
			if ($this->text() === 'use') {
				$this->position++;
				$use = new trait_use_node();
				$use->traits = $this->relationship_names();
				$this->expect(';');
				$use->set_span($member_start, $this->position);
				$uses->append($use);
				continue;
			}
			$member_modifiers = $this->member_modifiers();
			if ($this->text() === 'function') {
				$methods->append(object_cast($this->callable_declaration(object_cast($record->occurrence(), collected_member_owner::class), $member_modifiers, $member_start), method_node::class));
			}
			elseif ($this->text() === 'const') {
				$constants->append(object_cast($this->constant_declaration(object_cast($record->occurrence(), collected_member_owner::class), $member_modifiers, $member_start), member_constant_node::class));
			}
			else {
				$this->position = $member_start;
				$fields->append($this->field_declaration($record->member_scope()));
			}
		}
		$this->expect('}');
		$record->properties = $fields;
		$record->methods = $methods;
		$record->constants = $constants;
		$record->use_traits = $uses;
		$same = $previous !== null;
		if ($old_relationship_text !== $this->relationship_key($record)) { $same = false; }
		if (q_count($old_constants) !== q_count($constants)) { $same = false; }
		else {
			foreach ($constants as $index => $constant) {
				if ($old_constants[$index] !== $constant) { $same = false; }
			}
		}
		if (q_count($old_modifiers) !== q_count($modifiers)) { $same = false; }
		else {
			foreach ($modifiers as $index => $modifier) {
				if ($old_modifiers[$index] !== $modifier) { $same = false; }
			}
		}
		if ((q_count($old_fields) !== q_count($fields)) || (q_count($old_methods) !== q_count($methods))) { $same = false; }
		else {
			foreach ($fields as $index => $field) {
				if (($old_fields[$index] !== $field) || ($field->occurrence()->change_status !== change_state::unchanged)) { $same = false; }
			}
			// Method contents have their own change flags/work. Record layout observes membership only.
			foreach ($methods as $index => $method) {
				if ($old_methods[$index] !== $method) { $same = false; }
			}
		}
		$this->finish_declaration($record, $start, $same);
		return $record;
	}

	/** Header syntax only: semantic target kind, uniqueness and cycles belong to analysis. */
	private function relationship_names(): Storage /** Storage<named_type_node> */
	{
		$result /** Storage<named_type_node> */ = new Storage();
		while (true) {
			$result->append($this->relationship_name());
			if ($this->text() !== ',') { break; }
			$this->position++;
		}
		return $result;
	}

	/** This grammar admits a named declaration, not a value-storage modifier or template use. */
	private function relationship_name(): named_type_node
	{
		$syntax = $this->type_syntax($this->source_scope);
		if (!($syntax instanceof named_type_node)) {
			throw new \RuntimeException($this->error_message('Relationship requires a named type'));
		}
		return object_cast($syntax, named_type_node::class);
	}

	/** Compare retained source names without introducing semantic ancestry into parsing. */
	private function relationship_key(type_declaration_node $node): string
	{
		$key = '';
		if ($node->base_class !== null) { $key .= 'base:' . $node->base_class->name; }
		$extended /** Storage<named_type_node> */ = $node->extended_interfaces;
		foreach ($extended as $type_name) { $key .= ';extends:' . $type_name->name; }
		$implemented /** Storage<named_type_node> */ = $node->implemented_interfaces;
		foreach ($implemented as $type_name) { $key .= ';implements:' . $type_name->name; }
		$uses /** Storage<trait_use_node> */ = $node->use_traits;
		foreach ($uses as $use) {
			$key .= ';use';
			$traits /** Storage<named_type_node> */ = $use->traits;
			foreach ($traits as $type_name) { $key .= ':' . $type_name->name; }
		}
		return $key;
	}

	/** Fields match by their own spelling inside the structure, never by a project-wide name. */
	private function field_declaration(scope $scope): property_node
	{
		$start = $this->position;
		$modifiers = $this->member_modifiers();
		$type /** nullable<type_node> */ = null;
		if (!string_byte_starts_with($this->text(), '$')) {
			if (!$this->identifier() && ($this->text() !== '?') && ($this->text() !== '\\')) {
				throw new \RuntimeException($this->error_message('Expected field type or variable'));
			}
			$type = $this->type_syntax($scope);
		}
		if (!string_byte_starts_with($this->text(), '$')) {
			throw new \RuntimeException($this->error_message('Expected field variable name'));
		}
		$name_index = $this->position++;
		$name = $this->name_at($name_index);
		$previous = $this->collector->previous_field($scope, $name);
		$field /** property_node */ = $previous ?? new property_node();
		if ($previous === null) {
			$field->set_span($start, $start);
		}
		$old_start = $field->start_token();
		if ($previous !== null) {
			if (object_cast($field->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = $field->end_token();
		$field->type_syntax = $type;
		$field->modifiers = $modifiers;
		$field->name = $name;
		$field->collect($this->collector, $scope, $name_index);
		$initializer /** nullable<expression_node> */ = null;
		if ($this->text() === '=') {
			$this->position++;
			$saved_scope = $this->current_scope;
			$this->current_scope = $scope;
			$initializer = $this->expression();
			$this->current_scope = $saved_scope;
		}
		$this->expect(';');
		$same = $this->same_tokens($old_start, $old_end, $start, $this->position);
		if ($same && ($field->initializer !== null) && ($initializer !== null)) {
			$this->retain_range($field->initializer, $initializer->start_token());
		}
		else {
			$field->initializer = $initializer;
		}
		$this->finish_declaration($field, $start, $same);
		return $field;
	}

	/** Syntax retains modifiers; preparation owns duplicates and supported combinations. */
	private function member_modifiers(): array /** vector<declaration_modifier> */
	{
		$result /** vector<declaration_modifier> */ = [];
		while (true) {
			$modifier /** nullable<declaration_modifier> */ = null;
			$text = $this->text();
			if ($text === '#[') {
				$this->position++;
				if ($this->text() === '\\') { $this->position++; }
				$this->expect('Override');
				$this->expect(']');
				$result[] = declaration_modifier::override_declaration;
				continue;
			}
			if ($text === 'public') { $modifier = declaration_modifier::public_access; }
			elseif ($text === 'private') { $modifier = declaration_modifier::private_access; }
			elseif ($text === 'protected') { $modifier = declaration_modifier::protected_access; }
			elseif ($text === 'static') { $modifier = declaration_modifier::static_member; }
			elseif ($text === 'final') { $modifier = declaration_modifier::final_declaration; }
			elseif ($text === 'abstract') { $modifier = declaration_modifier::abstract_declaration; }
			elseif ($text === 'readonly') { $modifier = declaration_modifier::readonly_declaration; }
			if ($modifier === null) { break; }
			$result[] = $modifier;
			$this->position++;
		}
		return $result;
	}

	/** A statement owns its semicolon; its value expression keeps its existing syntax shape. */
	private function expression_statement(): expression_statement_node
	{
		$start = $this->position;
		$statement = new expression_statement_node();
		$statement->expression = $this->expression();
		$this->expect(';');
		$this->finish_node($statement, $start);
		return $statement;
	}

	/** Parse ordered parameters into the function scope owned by the parsed file. */
	private function function_declaration(): function_node
	{
		return object_cast($this->callable_declaration(null, /** vector<declaration_modifier> */ [], -1), function_node::class);
	}

	/** Reconcile callable signatures and bodies identically for namespace functions and members. */
	private function callable_declaration(?collected_member_owner $record, array $modifiers /** vector<declaration_modifier> */, int $source_start, ?scope $registration_scope = null): callable_declaration_node
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		if ($this->current_scope->is_function()) {
			throw new \RuntimeException($this->error_message('Nested function declarations are not implemented'));
		}
		$start = $source_start < 0 ? $this->position : $source_start;
		$formals /** hash<int> */ = [];
		if ($this->text() === 'template')
		{
			$this->position++;
			$this->expect('<');
			do
			{
				if ($this->text() === 'typename') {
					$this->position++;
				}
				$formal_name = $this->text();
				if (!$this->identifier() || isset($formals[$formal_name])) {
					throw new \RuntimeException($this->error_message('Expected a unique template type parameter'));
				}
				$formals[$formal_name] = $this->position;
				$this->position++;
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
			$this->expect('>');
		}

		$this->expect('function');
		$returns_reference = $this->text() === '&';
		if ($returns_reference) {
			$this->position++;
		}
		if (!$this->identifier() || (($this->text() === 'function') || ($this->text() === 'return') || ($this->text() === 'void'))) {
			throw new \RuntimeException($this->error_message('Expected function name'));
		}
		$name_token_index = $this->position++;
		$function_name = $token_rows[$name_token_index]->text();
		if (isset($formals[$function_name])) {
			throw new \RuntimeException($this->error_message('Template parameter conflicts with function name'));
		}

		$declaration_scope = $this->source_scope;
		$previous /** nullable<callable_declaration_node> */ = null;
		$function /** callable_declaration_node */;
		if ($record === null) {
			$previous = $this->collector->previous_function($declaration_scope, $function_name);
			if ($previous === null) { $function = new function_node(); }
			else { $function = $previous; }
		}
		else {
			$declaration_scope = $registration_scope ?? $record->syntax()->member_scope();
			$previous = $this->collector->previous_method($declaration_scope, $function_name);
			if ($previous === null) { $function = new method_node(); }
			else { $function = $previous; }
			object_cast($function, method_node::class)->modifiers = $modifiers;
		}
		if ($previous === null) {
			$function->set_span($start, $start);
		}
		$old_start = $function->start_token();
		if ($previous !== null) {
			if (object_cast($function->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_body_start = $old_start;
		$old_body_end = $old_start;
		if ($previous !== null) {
			if (($this->reuse_previous) && ($old_start >= 0) && $function->has_parsed_body()) {
				$old_body_start = $function->require_body()->start_token();
				$old_body_end = $function->require_body()->end_token();
			}
		}
		$function->name = $function_name;
		if ($record === null) {
			$this->collector->collect_function(object_cast($function, function_node::class), $declaration_scope, $name_token_index);
		}
		else {
			$this->collector->collect_method(object_cast($function, method_node::class), $record, $name_token_index, $declaration_scope);
		}
		$local_scope = $function->signature_scope();
		$local_scope->set_parent($declaration_scope);
		$slots /** hash<int> */ = [];
		$slot = 0;
		foreach ($formals as $name => $token_index) {
			$slots[$name] = $slot++;
		}
		$local_scope->set_templates($slots);

		$parameters /** Storage<parameter_node> */ = $this->callable_parameters($local_scope, $record !== null);
		$lifecycle_method = ($record !== null) && (($function_name === '__construct') || ($function_name === '__destruct'));
		$return_type /** nullable<type_node> */ = null;
		if (!$lifecycle_method && ($this->text() !== ':')) {
			throw new \RuntimeException($this->error_message('Function requires an explicit return type, including void'));
		}
		if (!$lifecycle_method || ($this->text() === ':')) {
			$this->expect(':');
			if ((!$this->identifier() && ($this->text() !== '?') && ($this->text() !== '\\')) || (($this->text() === 'function') || ($this->text() === 'return'))) {
				throw new \RuntimeException($this->error_message('Expected return type name'));
			}

			$return_type = $this->type_syntax($local_scope);
		}

		if ($record !== null) {
			if ($this->text() === ';') {
				$this->position++;
				$function->signature_syntax->return_type = $return_type;
				$function->signature_syntax->returns_reference = $returns_reference;
				$function->signature_syntax->parameters = $parameters;
				$function->template_parameters = $formals;
				$function->set_bodyless_declaration();
				$this->finish_declaration($function, $start, false);
				return $function;
			}
		}
		$body_start = $this->position;
		$body_end = $this->body_end();
		$body_changed = !$this->same_body_text($old_body_start, $old_body_end, $body_start, $body_end);
		$selected_scope /** scope */ = !$body_changed ? $function->require_body()->local_scope() : new scope();
		if ($body_changed) {
			$selected_scope->set_parent($local_scope);
			$selected_scope->mark_function();
		}
		$this->retain_scope($selected_scope);
		$body /** function_body_node */ = !$body_changed ? $function->require_body() : $this->block($selected_scope);
		if (!$body_changed) {
			$this->retain_range($body, $body_start);
			$this->position = $body_end;
		}
		else {
			if ($previous !== null) {
				if ($function->has_parsed_body()) {
					$this->transfer_body_work($function->require_body(), $body);
				}
			}
		}

		$function->signature_syntax->return_type = $return_type;
		$function->signature_syntax->returns_reference = $returns_reference;
		$body->syntax_changed = $body->syntax_changed || $body_changed;
		$function->set_parsed_body($body);
		$function->signature_syntax->parameters = $parameters;
		$function->template_parameters = $formals;
		$this->finish_declaration($function, $start, $this->same_tokens($old_start, $old_body_start, $start, $body_start));
		return $function;
	}

	/** One parameter grammar for named declarations and anonymous expressions. */
	private function callable_parameters(scope $parameter_scope, bool $member): Storage /** Storage<parameter_node> */
	{
		$parameters /** Storage<parameter_node> */ = new Storage();
		$this->expect('(');
		if ($this->text() !== ')') {
			do {
				$parameters->append($this->parameter($parameter_scope, $member));
				if ($this->text() !== ',') { break; }
				$this->position++;
			} while (true);
		}
		$this->expect(')');
		return $parameters;
	}

	/** Anonymous syntax owns ordinary callable scopes without entering named lookup. */
	private function closure_expression(): closure_node
	{
		$start = $this->expect('function');
		$closure = new closure_node();
		$signature_scope = $closure->signature_scope();
		$signature_scope->set_parent($this->current_scope);
		// Keep parameter observers valid even when parsing fails before the node is attached.
		$this->retain_scope($signature_scope);
		$signature = $closure->signature_syntax;
		$signature->returns_reference = $this->text() === '&';
		if ($signature->returns_reference) { $this->position++; }
		$signature->parameters = $this->callable_parameters($signature_scope, false);
		if ($this->text() === 'use') {
			throw new \RuntimeException($this->error_message('Closure capture syntax is deferred'));
		}
		$this->expect(':');
		$signature->return_type = $this->type_syntax($signature_scope);
		$body_scope = $closure->body_scope();
		$this->retain_scope($body_scope);
		$closure->set_parsed_body($this->block($body_scope));
		$this->finish_node($closure, $start);
		return $closure;
	}

	/** Register a typed parameter as an explicit variable declaration in the body scope. */
	private function parameter(scope $scope, bool $member = false): parameter_node
	{
		$start = $this->position;
		$access = storage_access::writable;
		if ($this->text() === 'const') {
			$access = storage_access::read_only;
			$this->position++;
		}
		$property_backed = false;
		$type /** nullable<type_node> */ = null;
		if ($member && ($this->text() === 'set')) {
			$this->position++;
			if ($this->text() === ':') {
				$property_backed = true;
				$this->position++;
			}
			else { $this->position -= 1; }
		}
		if (!$property_backed) {
			if ((!$this->identifier() && ($this->text() !== '?') && ($this->text() !== '\\')) || (($this->text() === 'function') || ($this->text() === 'return'))) {
				throw new \RuntimeException($this->error_message('Expected parameter type'));
			}
			$type = $this->type_syntax($scope);
		}
		$mode = passing_mode::value;
		if ($this->text() === '&') {
			$mode = passing_mode::reference;
			$this->position++;
		}
		$variadic = $this->text() === '...';
		if ($variadic) { $this->position++; }
		if (!string_byte_starts_with($this->text(), '$')) {
			throw new \RuntimeException($this->error_message('Expected parameter name'));
		}
		$name_index = $this->position++;
		$name = $this->name_at($name_index);
		$previous = $this->collector->previous_parameter($scope, $name);
		$parameter /** parameter_node */ = $previous ?? new parameter_node();
		if ($previous === null) {
			$parameter->set_span($start, $start);
		}
		$old_start = $parameter->start_token();
		if ($previous !== null) {
			if (object_cast($parameter->optional_occurrence(), collected_name::class)->change_status === change_state::deleted) {
				$old_start = -1;
			}
		}
		$old_end = $parameter->end_token();
		$parameter->type_syntax = $type;
		$parameter->property_backed = $property_backed;
		$parameter->mode = $mode;
		$parameter->access = $access;
		$parameter->variadic = $variadic;
		$parameter->default_value = null;
		if ($this->text() === '=') {
			$this->position++;
			$parameter->default_value = $this->expression();
		}

		$parameter->name = $name;
		$parameter->collect($this->collector, $scope, $name_index);
		$this->finish_declaration($parameter, $start, $this->same_tokens($old_start, $old_end, $start, $this->position));
		return $parameter;
	}

	/** Reference the selected scope; restore enclosing context even if parsing fails. */
	private function block(scope $scope): function_body_node
	{
		$start = $this->expect('{');
		$body = new function_body_node($scope);
		$children /** Storage<statement_node> */ = $body->statements;
		$enclosing = $this->current_scope;
		$this->current_scope = $scope;
		try {
			while (($this->text() !== '}') && ($this->text() !== '')) {
				$statement = $this->statement();
				$children->append(object_cast($statement, statement_node::class));
			}
			$this->expect('}');
		}
		finally {
			$this->current_scope = $enclosing;
		}
		$this->finish_node($body, $start);
		return $body;
	}

	private function identifier(): bool
	{
		return Source_Text::identifier($this->text());
	}

	/** Scan one source name without resolving it or collecting speculative occurrences. */
	private function source_name_end(int $offset): int
	{
		if ($this->text_at_offset($offset) === '\\') { $offset++; }
		if (!Source_Text::identifier($this->text_at_offset($offset))) { return -1; }
		$offset++;
		while ($this->text_at_offset($offset) === '\\') {
			$offset++;
			if (!Source_Text::identifier($this->text_at_offset($offset))) { return -1; }
			$offset++;
		}
		return $offset;
	}

	/** Existing name-bearing nodes retain source spelling, not a resolved identity. */
	private function source_name(): string
	{
		$length = $this->source_name_end(0);
		if ($length < 0) {
			throw new \RuntimeException($this->error_message('Expected source name with identifier path segments'));
		}
		$name = '';
		for ($offset = 0; $offset < $length; $offset++) {
			$name .= $this->text();
			$this->position++;
		}
		return $name;
	}

	/** Preserve the return keyword, optional expression and terminating semicolon. */
	private function return_statement(): return_node
	{
		$start = $this->position;
		$return_statement = new return_node();
		$this->position++;
		if ($this->text() !== ';') {
			$return_statement->expression = $this->expression();
		}
		$this->expect(';');
		$this->finish_node($return_statement, $start);
		return $return_statement;
	}

	/** Explicit type syntax declares storage; plain writes use the expression grammar. */
	private function variable_declaration(bool $terminated = true): statement_node
	{
		$start = $this->position++;
		$declaration = new variable_declaration_node();
		$declaration->name = $this->name_at($start);
		$type = $this->type_syntax($this->current_scope);
		$declaration->type_syntax = $this->text() === '['
			? object_cast($this->array_type($type), type_node::class)
			: object_cast($type, type_node::class);
		if ($this->text() === '=') {
			$this->position++;
			$declaration->binds_reference = $this->text() === '&';
			if ($declaration->binds_reference) {
				$this->position++;
			}
			$declaration->initializer = $this->expression();
		}
		if ($terminated) { $this->expect(';'); }
		$this->finish_node($declaration, $start);
		$declaration->collect($this->collector, $this->current_scope, $start);
		return $declaration;
	}

	/** One precedence ladder includes right-associative writes and weaker keyword logic. */
	private function expression(): expression_node
	{
		return $this->binary_expression(1);
	}

	/** Assignments and coalescing associate right; ordinary binary operators associate left. */
	private function binary_expression(int $minimum_precedence): expression_node
	{
		$start = $this->position;
		$left = $this->unary_expression();
		$conditional_seen = false;
		$previous_shorthand = false;
		while (true)
		{
			$precedence = $this->binary_precedence();
			if ($precedence < $minimum_precedence) {
				break;
			}
			if ($this->text() === '??') {
				$this->position++;
				$coalesce = new coalesce_expression_node();
				$coalesce->left = $left;
				$coalesce->fallback = $this->binary_expression($precedence);
				$this->finish_node($coalesce, $start);
				$left = $coalesce;
				continue;
			}
			if ($this->text() === '?')
			{
				$this->position++;
				$shorthand = $this->text() === ':';
				if ($conditional_seen && (!$previous_shorthand || !$shorthand)) {
					throw new \RuntimeException($this->error_message('Nested full ternary requires parentheses'));
				}
				$selection = new conditional_expression_node();
				$selection->condition = $left;
				if (!$shorthand) { $selection->true_branch = $this->expression(); }
				$this->expect(':');
				$selection->fallback = $this->binary_expression($precedence + 1);
				$this->finish_node($selection, $start);
				$left = $selection;
				$conditional_seen = true;
				$previous_shorthand = $shorthand;
				continue;
			}
			$operator_text = $this->tokens->operator_text_at($this->position);
			$operator_token_index = $this->position++;
			if (($operator_text === '<<') || ($operator_text === '>>')) {
				$this->position++;
			}
			if ($precedence === 4)
			{
				if ($operator_text === '=') {
					if (!($left instanceof assignable_expression_node)) {
						throw new \RuntimeException($this->error_message('Assignment requires an assignable target'));
					}
					$assignment = new assignment_expression_node();
					$assignment->target = object_cast($left, assignable_expression_node::class);
					$assignment->binds_reference = $this->text() === '&';
					if ($assignment->binds_reference) {
						$this->position++;
					}
					$assignment->value = $this->binary_expression($precedence);
					$this->finish_node($assignment, $start);
					$left = $assignment;
				}
				else {
					$compound = new compound_assignment_expression_node();
					$compound->target = $left;
					$compound->operator_token_index = $operator_token_index;
					$compound->value = $this->binary_expression($precedence);
					$this->finish_node($compound, $start);
					$left = $compound;
				}
				continue;
			}
			$binary = new binary_expression_node();
			$binary->left = $left;
			$binary->operator_token_index = $operator_token_index;
			$binary->right = $this->binary_expression($precedence + 1);
			$this->finish_node($binary, $start);
			$left = $binary;
		}
		return $left;
	}

	/** Zero ends the expression; only admitted operators receive a precedence level. */
	private function binary_precedence(): int
	{
		if ($this->position >= $this->tokens->end_token) {
			return 0;
		}
		$text = $this->tokens->operator_text_at($this->position);
		if ($text === 'or') {
			return 1;
		}
		if ($text === 'xor') {
			return 2;
		}
		if ($text === 'and') {
			return 3;
		}
		if (($text === '=') || ($text === '+=') || ($text === '-=') || ($text === '*=')
			|| ($text === '/=') || ($text === '%=') || ($text === '.=')
			|| ($text === '&=') || ($text === '|=') || ($text === '^=')
			|| ($text === '<<=') || ($text === '>>=')) {
			return 4;
		}
		if ($text === '?') {
			return 5;
		}
		if ($text === '??') { return 6; }
		if ($text === '||') {
			return 7;
		}
		if ($text === '&&') {
			return 8;
		}
		if ($text === '|') {
			return 9;
		}
		if ($text === '^') {
			return 10;
		}
		if ($text === '&') {
			return 11;
		}
		if (($text === '==') || ($text === '!=') || ($text === '===') || ($text === '!==') || ($text === '<=>')) {
			return 12;
		}
		if (($text === '<') || ($text === '<=') || ($text === '>') || ($text === '>=')) {
			return 13;
		}
		if ($text === '.') {
			return 14;
		}
		if (($text === '<<') || ($text === '>>')) {
			return 15;
		}
		if (($text === '+') || ($text === '-')) {
			return 16;
		}
		if (($text === '*') || ($text === '/') || ($text === '%')) {
			return 17;
		}
		return 0;
	}

	/** Prefix operators nest right-to-left; exponentiation binds inside their operand. */
	private function unary_expression(): expression_node
	{
		$text = $this->text();
		if (($text === '++') || ($text === '--'))
		{
			$start = $this->position++;
			$mutation = new mutation_expression_node();
			$mutation->operator_token_index = $start;
			$mutation->target = $this->unary_expression();
			$this->finish_node($mutation, $start);
			return $mutation;
		}
		if (($text !== '+') && ($text !== '-') && ($text !== '~') && ($text !== '!')) {
			return $this->object_type_test();
		}
		$start = $this->position++;
		$unary = new unary_expression_node();
		$unary->operator_token_index = $start;
		$unary->operand = $this->unary_expression();
		$this->finish_node($unary, $start);
		return $unary;
	}

	/** Literal type tests bind before boolean negation; no expression-valued type operand. */
	private function object_type_test(): expression_node
	{
		$start = $this->position;
		$operand = $this->power_expression();
		if ($this->text() !== 'instanceof') { return $operand; }
		$this->position++;
		$test = new object_type_node();
		$test->operation = object_type_operation::type_test;
		$test->operand = $operand;
		$test->target_type = $this->object_type_target();
		$this->finish_node($test, $start);
		return $test;
	}

	private function object_type_target(): type_node
	{
		$target = $this->type_syntax($this->current_scope);
		if (!($target instanceof named_type_node)) {
			throw new \RuntimeException($this->error_message('Object type operation requires a literal named target'));
		}
		return $target;
	}

	/** Power associates right-to-left and accepts a signed exponent through unary parsing. */
	private function power_expression(): expression_node
	{
		$start = $this->position;
		$left = $this->postfix_expression();
		if ($this->text() !== '**') {
			return $left;
		}
		$power = new binary_expression_node();
		$power->left = $left;
		$power->operator_token_index = $this->position++;
		$power->right = $this->unary_expression();
		$this->finish_node($power, $start);
		return $power;
	}

	/** Postfix mutation binds to the complete primary/access expression, before prefix operators. */
	private function postfix_expression(): expression_node
	{
		$start = $this->position;
		$target = $this->access_suffix($this->primary_expression(), $start);
		while (($this->text() === '++') || ($this->text() === '--'))
		{
			$mutation = new mutation_expression_node();
			$mutation->target = $target;
			$mutation->operator_token_index = $this->position++;
			$mutation->postfix = true;
			$this->finish_node($mutation, $start);
			$target = $mutation;
		}
		return $target;
	}

	/** Both take spellings normalize to the same conditional binding operation. */
	private function take_expression(): expression_node
	{
		$start = $this->position++;
		$this->expect('(');
		$arguments /** Storage<expression_node> */ = new Storage();
		if ($this->text() !== ')')
		{
			$arguments->append($this->expression());
			while ($this->text() === ',') {
				$this->position++;
				$arguments->append($this->expression());
			}
		}
		$this->expect(')');
		$count = $arguments->count();
		$node = new take_expression_node();
		if ($this->text() === '=')
		{
			if (($count < 1) || ($count > 2)) {
				throw new \RuntimeException($this->error_message('take assignment requires one or two outputs'));
			}
			$this->position++;
			$node->source = $this->binary_expression(4);
		}
		else
		{
			if (($count < 2) || ($count > 3)) {
				throw new \RuntimeException($this->error_message('take helper requires outputs followed by a source'));
			}
			$count -= 1;
			$node->source = $arguments[$count];
		}
		$node->value_binding = $arguments[0];
		if ($count === 2) {
			$node->alternative_binding = $arguments[1];
		}
		$this->finish_node($node, $start);
		return $node;
	}

	/** Parse literals, calls, variables and normalized grouping. */
	private function primary_expression(): expression_node
	{
		if ($this->text() === 'function') {
			return $this->closure_expression();
		}
		if ($this->text() === 'object_cast') {
			$start = $this->position++;
			$cast = new object_type_node();
			$cast->operation = object_type_operation::checked_cast;
			$this->expect('(');
			$cast->operand = $this->expression();
			$this->expect(',');
			$cast->target_type = $this->object_type_target();
			$this->expect('::');
			$this->expect('class');
			$this->expect(')');
			$this->finish_node($cast, $start);
			return $cast;
		}
		if (($this->text() === 'weak') || ($this->text() === 'lock'))
		{
			$start = $this->position;
			$observation = new ownership_observation_node();
			$observation->operation = $this->text() === 'weak'
				? ownership_observation_kind::derive_weak : ownership_observation_kind::lock_shared;
			$this->position++;
			$this->expect('(');
			$observation->operand = $this->expression();
			$this->expect(')');
			$this->finish_node($observation, $start);
			return $observation;
		}
		if ($this->text() === 'move')
		{
			$start = $this->position++;
			$move = new move_expression_node();
			$this->expect('(');
			$move->operand = $this->expression();
			$this->expect(')');
			$this->finish_node($move, $start);
			return $move;
		}
		if ($this->text() === 'new') {
			return $this->object_construction();
		}
		if ($this->data_construction_ahead())
		{
			$start = $this->position;
			$target = $this->type_syntax($this->current_scope);
			$this->expect('::');
			return $this->data_construction($start, $target);
		}
		if ($this->text() === 'take') {
			return $this->take_expression();
		}
		if (($this->text() === 'isset') || ($this->text() === 'empty')) {
			$start = $this->position;
			$probe = new value_probe_node();
			$probe->probe_kind = $this->text() === 'isset' ? value_probe_kind::isset : value_probe_kind::empty;
			$this->position++;
			$this->expect('(');
			$probe->operand = $this->expression();
			$this->expect(')');
			$this->finish_node($probe, $start);
			return $probe;
		}
		if ($this->text() === 'null') {
			$start = $this->position++;
			$literal = new null_literal_node();
			$this->finish_node($literal, $start);
			return $literal;
		}
		if ($this->text() === 'match') {
			return $this->match_expression();
		}
		if ($this->text() === '(')
		{
			if ($this->cast_ahead()) {
				return $this->cast_expression();
			}
			$this->position++;
			$inner = $this->expression();
			$this->expect(')');
			return $inner;
		}
		if (($this->text() === 'true') || ($this->text() === 'false')) {
			$start = $this->position;
			$literal = new boolean_literal_node();
			$literal->value = $this->text() === 'true';
			$this->position++;
			$this->finish_node($literal, $start);
		return $literal;
		}
		if (($this->text() === '[') || ($this->text() === '{')) {
			return $this->data_construction($this->position, null);
		}
		if ($this->identifier() || ($this->text() === '\\')) {
			if ($this->text_at_offset($this->source_name_end(0)) === '::')
			{
				$start = $this->position;
				$name = $this->source_name();
				$owner_end = $this->position;
				$this->expect('::');
				if (!$this->identifier() && !string_byte_starts_with($this->text(), '$')) {
					throw new \RuntimeException($this->error_message('Expected static member name'));
				}
				$owner_type = new named_type_node();
				$owner_type->name = $name;
				$owner_type->set_span($start, $owner_end);
				$owner_type->collect($this->collector, $this->current_scope, $start);
				if ($this->identifier() && ($this->text_at_offset(1) === '(')) {
					$call = $this->call_expression();
					$call->owner_type = $owner_type;
					$this->finish_node($call, $start);
					return $call;
				}
				$member = new static_member_reference_node();
				$member->owner_type = $owner_type;
				$member->member_name = $this->text();
				$this->position++;
				$this->finish_node($member, $start);
				return $member;
			}
			if ($this->call_ahead()) {
				return $this->call_expression();
			}
			$start = $this->position;
			$constant = new constant_reference_node();
			$constant->name = $this->source_name();
			$this->finish_node($constant, $start);
			$constant->collect($this->collector, $this->current_scope, $start);
			return $constant;
		}
		$start = $this->position;
		$text = $this->text();
		$this->position++;
		$node /** expression_node */;
		if (string_byte_starts_with($text, '$')) {
			$variable = $this->variable_reference($start);
			// Parentheses normalize away; a grouped bare variable keeps its write role.
			$offset = 0;
			while ($this->text_at_offset($offset) === ')') {
				$offset++;
			}
			if ($this->text_at_offset($offset) === '=') {
				$variable->collect_write($this->collector, $this->current_scope, $start);
			}
			else {
				$variable->collect($this->collector, $this->current_scope, $start);
			}
			$node = $variable;
		}
		elseif (($text !== '') && Source_Text::digits($text)) {
			$node = new integer_literal_node();
			$this->finish_node($node, $start);
		}
		elseif (($text !== '') && ((string_byte_at($text, 0) === 39) || (string_byte_at($text, 0) === 34))) {
			$node = $this->string_expression($start, $text);
		}
		elseif (($text !== '.') && Source_Text::floating($text)) {
			$node = new float_literal_node();
			$this->finish_node($node, $start);
		}
		else {
			throw new \RuntimeException($this->error_message('Expected scalar literal or variable reference'));
		}
		return $node;
	}

	/** Match arms own grouped labels and one expression, never statement blocks. */
	private function match_expression(): match_expression_node
	{
		$start = $this->position++;
		$selection = new match_expression_node();
		$this->expect('(');
		$selection->selector = $this->expression();
		$this->expect(')');
		$this->expect('{');
		$arms /** Storage<match_arm_node> */ = $selection->arms;
		while ($this->text() !== '}')
		{
			$arm_start = $this->position;
			$arm = new match_arm_node();
			$labels /** Storage<expression_node> */ = $arm->labels;
			if ($this->text() === 'default') {
				$this->position++;
			}
			else
			{
				$labels->append($this->expression());
				while ($this->text() === ',') {
					$this->position++;
					$labels->append($this->expression());
				}
			}
			$this->expect('=>');
			$arm->result = $this->expression();
			$this->finish_node($arm, $arm_start);
			$arms->append($arm);
			if ($this->text() !== ',') {
				break;
			}
			$this->position++;
		}
		$this->expect('}');
		$this->finish_node($selection, $start);
		return $selection;
	}

	/** Split only double-quoted variable insertions; all offsets stay relative to the source token. */
	private function string_expression(int $start, string $text): expression_node
	{
		$literal = new string_literal_node();
		$this->finish_node($literal, $start);
		if (string_byte_at($text, 0) !== 34) {
			return $literal;
		}
		$interpolation = new interpolated_string_node();
		$this->finish_node($interpolation, $start);
		$parts /** Storage<interpolation_part_node> */ = $interpolation->parts;
		$end = string_byte_len($text) - 1;
		$segment = 1;
		$offset = 1;
		$insertions = 0;
		while ($offset < $end)
		{
			$byte = string_byte_at($text, $offset);
			if ($byte === 92) {
				$offset += 2;
				continue;
			}
			$braced = ($byte === 123) && (string_byte_at($text, $offset + 1) === 36);
			if (($byte !== 36) && !$braced) {
				$offset++;
				continue;
			}
			$first = $offset;
			$variable_start = $braced ? $offset + 1 : $offset;
			$name_start = $variable_start + 1;
			$initial = string_byte_at($text, $name_start);
			if (!Source_Text::letter($initial)) {
				if ($braced || ($initial === 123) || ($initial === 36) || ($initial >= 128)) {
					throw new \RuntimeException($this->error_message_at('Unsupported interpolation; expected $name or {$name}', $start, $first));
				}
				$offset++;
				continue;
			}
			$offset = $name_start + 1;
			while (Source_Text::letter(string_byte_at($text, $offset)) || Source_Text::digit(string_byte_at($text, $offset))) {
				$offset++;
			}
			$name = string_byte_slice($text, $name_start, $offset - $name_start);
			if ($braced) {
				if (string_byte_at($text, $offset) !== 125) {
					throw new \RuntimeException($this->error_message_at('Unsupported braced interpolation; expected {$name}', $start, $first));
				}
				$offset++;
			}
			elseif ((string_byte_at($text, $offset) === 91) || (string_byte_at($text, $offset) === 40)
				|| (string_byte_slice($text, $offset, 2) === '->') || (string_byte_at($text, $offset) >= 128)) {
				throw new \RuntimeException($this->error_message_at('Unsupported interpolation suffix; use {$name} before literal suffix text', $start, $first));
			}
			$this->interpolation_text($interpolation, $start, $segment, $first - $segment);
			$variable = new variable_reference_node();
			$variable->name = $name;
			$this->finish_node($variable, $start);
			$variable->collect($this->collector, $this->current_scope, $start);
			$part = new interpolation_value_node();
			$part->expression = $variable;
			$part->byte_offset = $first;
			$part->byte_length = $offset - $first;
			$this->finish_node($part, $start);
			$parts->append($part);
			$insertions++;
			$segment = $offset;
		}
		if ($insertions === 0) {
			return $literal;
		}
		$this->interpolation_text($interpolation, $start, $segment, $end - $segment);
		return $interpolation;
	}

	/** Keep literal source bytes separate from decoded preparation facts. */
	private function interpolation_text(interpolated_string_node $parent, int $start,
		int $offset, int $length): void
	{
		if ($length === 0) {
			return;
		}
		$part = new interpolation_text_node();
		$part->byte_offset = $offset;
		$part->byte_length = $length;
		$this->finish_node($part, $start);
		$parts /** Storage<interpolation_part_node> */ = $parent->parts;
		$parts->append($part);
	}

	/** Recognize cast syntax without moving the parser or collecting speculative type names. */
	private function cast_ahead(): bool
	{
		$end = $this->type_syntax_end(1);
		if ($end < 0) {
			return false;
		}
		if ($this->text_at_offset($end) !== ')') {
			return false;
		}
		$operand = $this->text_at_offset($end + 1);
		if (($operand === '') || ($operand === 'and') || ($operand === 'or') || ($operand === 'xor')) {
			return false;
		}
		$first = string_byte_at($operand, 0);
		return Source_Text::identifier($operand) || Source_Text::digit($first)
			|| ($first === 36) || ($first === 39) || ($first === 34)
			|| ($operand === '!') || ($operand === '~') || ($operand === '\\')
			|| ($operand === '(') || ($operand === '[') || ($operand === '{')
			|| (($first === 46) && (string_byte_len($operand) > 1));
	}

	/** Distinguish named/template calls from constants followed by relational operators. */
	private function call_ahead(): bool
	{
		if ($this->text_at_offset(1) === '(') {
			return true;
		}
		$end = $this->type_syntax_end(0);
		if ($end < 0) {
			return false;
		}
		return $this->text_at_offset($end) === '(';
	}

	/** Scan the existing name / name<type,...> grammar; -1 means no complete type form. */
	private function type_syntax_end(int $offset): int
	{
		while ($this->text_at_offset($offset) === '?') { $offset++; }
		$offset = $this->source_name_end($offset);
		if ($offset < 0) {
			return -1;
		}
		if ($this->text_at_offset($offset) !== '<') {
			return $offset;
		}
		do
		{
			$offset = $this->type_syntax_end($offset + 1);
			if ($offset < 0) {
				return -1;
			}
		}
		while ($this->text_at_offset($offset) === ',');
		if ($this->text_at_offset($offset) !== '>') {
			return -1;
		}
		return $offset + 1;
	}

	/** Parse one general source cast; the type registry decides its target meaning later. */
	private function cast_expression(): expression_node
	{
		$start = $this->expect('(');
		$cast = new cast_expression_node();
		$cast->target_type = $this->type_syntax($this->current_scope);
		$this->expect(')');
		$cast->operand = $this->unary_expression();
		$this->finish_node($cast, $start);
		return $cast;
	}

	/** Preserve the element type and literal extent independently of LLVM spelling. */
	private function array_type(type_node $element): array_type_node
	{
		$this->expect('[');
		$type = new array_type_node();
		$type->element_type = $element;
		$count_expression = $this->expression();
		if (!($count_expression instanceof integer_literal_node)) {
			throw new \RuntimeException($this->error_message('Fixed array size must be a nonnegative integer literal'));
		}
		$type->count = object_cast($count_expression, integer_literal_node::class);
		$this->expect(']');
		$this->finish_node($type, $element->start_token());
		return $type;
	}

	/** Recognize explicit construction without collecting speculative target names. */
	private function data_construction_ahead(): bool
	{
		$end = $this->type_syntax_end(0);
		if ($end < 0) {
			return false;
		}
		if ($this->text_at_offset($end) !== '::') {
			return false;
		}
		$opening = $this->text_at_offset($end + 1);
		return ($opening === '[') || ($opening === '{');
	}

	/** One entry loop handles both spellings; target semantics remain outside parsing. */
	private function data_construction(int $start, ?type_node $target): data_construction_node
	{
		$braces = $this->text() === '{';
		$closing = $braces ? '}' : ']';
		$separator = $braces ? ':' : '=>';
		$this->position++;
		$construction = new data_construction_node();
		$construction->target_type = $target;
		$entries /** Storage<data_entry_node> */ = $construction->entries;
		while ($this->text() !== $closing)
		{
			$entry_start = $this->position;
			$entry = new data_entry_node();
			$first = $braces ? $this->data_property_key() : $this->expression();
			if ($this->text() === $separator) {
				$this->position++;
				$entry->key = $first;
				$entry->value = $this->expression();
			}
			elseif ($braces) {
				throw new \RuntimeException($this->error_message('JS-like data entries require a colon after the property name'));
			}
			else {
				$entry->value = $first;
			}
			$this->finish_node($entry, $entry_start);
			$entries->append($entry);
			if ($this->text() !== ',') {
				break;
			}
			$this->position++;
		}
		$this->expect($closing);
		$this->finish_node($construction, $start);
		return $construction;
	}

	/** Bare names are literal string keys, never collected constant/name occurrences. */
	private function data_property_key(): expression_node
	{
		$start = $this->position;
		if ($this->identifier()) {
			$this->position++;
			$key = new string_literal_node();
			$this->finish_node($key, $start);
			return $key;
		}
		$text = $this->text();
		if ($text !== '')
		{
			$first = string_byte_at($text, 0);
			if (($first === 39) || ($first === 34))
			{
				$this->position++;
				$quoted = $this->string_expression($start, $text);
				if ($quoted instanceof string_literal_node) {
					return $quoted;
				}
			}
		}
		throw new \RuntimeException($this->error_message('JS-like property names must be bare identifiers or literal strings'));
	}

	/** Member and index access retain a base expression so reads, writes and references share one shape. */
	private function access_suffix(expression_node $base, int $start): expression_node
	{
		while (($this->text() === '[') || ($this->text() === '->') || ($this->text() === '::'))
		{
			if ($this->text() === '::') {
				$this->position++;
				if (!$this->identifier() || ($this->text_at_offset(1) !== '(')) {
					if (!$this->identifier() && !string_byte_starts_with($this->text(), '$')) {
						throw new \RuntimeException($this->error_message('Expression qualification requires a fixed member name'));
					}
					$qualified_member = new static_member_reference_node();
					$qualified_member->qualifier = $base;
					$qualified_member->member_name = $this->text();
					$this->position++;
					$this->finish_node($qualified_member, $start);
					$base = $qualified_member;
					continue;
				}
				$qualified_call = $this->call_expression();
				$qualified_call->receiver = $base;
				$qualified_call->static_qualification = true;
				$this->finish_node($qualified_call, $start);
				$base = $qualified_call;
				continue;
			}
			if ($this->text() === '->')
			{
				$this->position++;
				if (!$this->identifier()) {
					throw new \RuntimeException($this->error_message('Expected field name'));
				}
				if ($this->text_at_offset(1) === '(') {
					$call = $this->call_expression();
					$call->receiver = $base;
					$this->finish_node($call, $start);
					$base = $call;
					continue;
				}
				$field = new field_access_node();
				$field->base = $base;
				$field_index = $this->position++;
				$field->name = $this->name_at($field_index);
				$this->finish_node($field, $start);
				$base = $field;
				$field->collect($this->collector, $this->current_scope, $field_index);
				continue;
			}
			$this->position++;
			$access = new index_node();
			$access->base = $base;
			if ($this->text() !== ']') {
				$access->index = $this->expression();
			}
			$this->expect(']');
			$this->finish_node($access, $start);
			$base = $access;
		}
		return $base;
	}

	/** Preserve argument expressions in source order and record the unresolved call target. */
	private function call_expression(): call_node
	{
		$start = $this->position;
		$call = new call_node();
		$template_arguments /** Storage<named_type_node> */ = $call->template_arguments;
		$arguments /** Storage<expression_node> */ = $call->arguments;
		$call->name = $this->source_name();
		if ($this->text() === '<')
		{
			$this->position++;
			do
			{
				$type_start = $this->position;
				$type_name = $this->source_name();
				$type = $this->named_type($type_start, $type_name);
				$template_arguments->append($type);
				$type->collect($this->collector, $this->current_scope, $type_start);
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
			$this->expect('>');
		}
		$this->expect('(');
		if ($this->text() !== ')')
		{
			do {
				$arguments->append($this->expression());
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while (true);
		}
		$this->expect(')');
		$this->finish_node($call, $start);
		$call->collect($this->collector, $this->current_scope, $start);
		return $call;
	}

	/** Ownership and constructor validity belong to preparation, not syntax recognition. */
	private function object_construction(): object_construction_node
	{
		$start = $this->position++;
		$node = new object_construction_node();
		$node->target_type = $this->type_syntax($this->current_scope);
		$this->expect('(');
		$arguments /** Storage<expression_node> */ = $node->arguments;
		if ($this->text() !== ')')
		{
			do {
				$arguments->append($this->expression());
				if ($this->text() !== ',') {
					break;
				}
				$this->position++;
			}
			while ($this->text() !== ')');
		}
		$this->expect(')');
		$this->finish_node($node, $start);
		return $node;
	}

	/** Complete source provenance without erasing concrete types. */
	private function finish_node(ast_node $node, int $start): void
	{
		$node->set_span($start, $this->position);
	}

	/** Type names retain spelling independently of token generations. */
	private function named_type(int $start, string $name): named_type_node
	{
		$node = new named_type_node();
		$node->name = $name;
		$this->finish_node($node, $start);
		return $node;
	}

	/** Parse one named type, template application or registered type-use modifier. */
	private function type_syntax(scope $lookup_scope): type_node
	{
		if ($this->text() === '?') {
			$start = $this->position++;
			$optional = new nullable_type_node();
			$optional->payload = $this->type_syntax($lookup_scope);
			$this->finish_node($optional, $start);
			return $optional;
		}
		if (!$this->identifier() && ($this->text() !== '\\')) {
			throw new \RuntimeException($this->error_message('Expected type name'));
		}
		$start = $this->position;
		$name = $this->source_name();
		$modifier = Model::$type_catalog->source()->modifier_or_null($name);
		if (($modifier !== null) && ($this->text() === '<'))
		{
			$this->position++;
			$modified = new type_use_modifier_node();
			$modified->name = $name;
			$modified->modifier = $modifier->kind();
			$modified->operand = $this->type_syntax($lookup_scope);
			$this->expect('>');
			$this->finish_node($modified, $start);
			return $modified;
		}
		$definition = $this->named_type($start, $name);
		$definition->collect($this->collector, $lookup_scope, $start);
		if ($this->text() !== '<') {
			return $definition;
		}

		$application = new template_application_type_node();
		$application->definition = $definition;
		$arguments /** Storage<type_node> */ = $application->arguments;
		$this->position++;
		if ($this->text() === '>') {
			throw new \RuntimeException($this->error_message('Template type application requires an argument'));
		}
		do {
			$arguments->append($this->type_syntax($lookup_scope));
			if ($this->text() !== ',') {
				break;
			}
			$this->position++;
		}
		while (true);
		$this->expect('>');
		$this->finish_node($application, $start);
		return $application;
	}

	/** Distinguish a nullable local declaration from a ternary expression statement. */
	private function nullable_declaration_ahead(): bool
	{
		if ($this->text_at_offset(1) !== '?') { return false; }
		$offset = $this->type_syntax_end(1);
		if ($offset < 0) { return false; }
		$following = $this->text_at_offset($offset);
		return ($following === '=') || ($following === ';');
	}

	/** A variable occurrence is classified as a read or write by its parser context. */
	private function variable_reference(int $start): variable_reference_node
	{
		$node = new variable_reference_node();
		$node->name = $this->name_at($start);
		$this->finish_node($node, $start);
		return $node;
	}

	private function name_at(int $index): string
	{
		$text = $this->tokens->text_at($index);
		return string_byte_starts_with($text, '$') ? string_byte_slice($text, 1, string_byte_len($text) - 1) : $text;
	}

	private function text(): string
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		if ($this->position >= $this->tokens->end_token) {
			return '';
		}
		return $token_rows[$this->position]->text();
	}

	private function text_at_offset(int $offset): string
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		$index = $this->position + $offset;
		if ($index >= $this->tokens->end_token) {
			return '';
		}
		return $token_rows[$index]->text();
	}

	private function expect(string $text): int
	{
		if ($this->text() !== $text) {
			throw new \RuntimeException($this->error_message(("Expected '" . $text . "'")));
		}
		return $this->position++;
	}

	/** Describe the current source position without publishing incomplete syntax. */
	private function error_message(string $message): string
	{
		return $this->error_message_at($message, $this->position, 0);
	}

	/** Describe a byte within a specific token, including interpolation failures. */
	private function error_message_at(string $message, int $token_index, int $within_token): string
	{
		$token_rows /** Storage<token> */ = $this->tokens->tokens;
		$offset = string_byte_len($this->tokens->content) - $this->tokens->content_offset;
		if (isset($token_rows[$token_index])) {
			$offset = (int)$token_rows[$token_index]->offset - $this->tokens->content_offset + $within_token;
		}
		return $message . " at " . $this->tokens->file->path . ": byte " . $offset;
	}
}
