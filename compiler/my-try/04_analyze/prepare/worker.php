<?php

/* One incremental preparation path: settle declarations, then process selected bodies once. */
namespace scpp\compiler;

final class Preparation_Worker
{
	private scope $language;
	/** A failed item is attempted at most once by this invocation, but stays changed for the next. */
	private \SplObjectStorage $attempt_failed /** hash<bool, shared<preparation_owner>> */;
	private \SplObjectStorage $declarations /** hash<bool, shared<preparation_owner>> */;
	private \SplObjectStorage $function_bodies /** hash<bool, shared<preparation_owner>> */;
	private \SplObjectStorage $file_bodies /** hash<bool, shared<preparation_owner>> */;
	private \SplObjectStorage $owners /** hash<bool, shared<preparation_owner>> */;

	/** Work lists are invocation-local; retained dependency records never reference this worker. */
	public function __construct(scope $language)
	{
		$this->language = $language;
		$this->attempt_failed = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->declarations = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->function_bodies = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->file_bodies = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->owners = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
	}

	/** Empty-model and update runs share selection, dependency propagation and execution. */
	public function prepare(Storage $sources /** Storage<collected_file> */): Storage /** Storage<prepared_file> */
	{
		foreach ($sources as $source) {
			if (!$source->deleted && !$source->parse_complete) {
				throw new \RuntimeException('Preparation requires a successful parsing/collection join');
			}
		}
		$this->remove_deleted_sources($sources);
		foreach ($sources as $source) {
			if (!$source->deleted) {
				$this->select($source);
			}
		}
		$this->changed_lookups();

		// Declaration processing may schedule more declarations; identity sets coalesce notifications.
		while (q_count($this->declarations) !== 0)
		{
			foreach ($this->declarations as $owner /** @object-key */)
			{
				try {
					$this->declaration($owner);
				}
				catch (\RuntimeException $error) {
					// Failure is retained on owners; independent declarations may still settle.
				}
				break;
			}
		}
		foreach ($this->function_bodies as $owner /** @object-key */) {
			$this->body($owner);
		}
		foreach ($this->file_bodies as $owner /** @object-key */) {
			$this->body($owner);
		}

		$this->require_success();

		$result /** Storage<prepared_file> */ = new Storage();
		foreach ($sources as $source)
		{
			if ($source->deleted) {
				continue;
			}
			if ($source->prepared === null) {
				$source->prepared = new prepared_file();
				$source->prepared->source = $source;
			}
			$prepared /** prepared_file */ = $source->prepared;
			$result->append($prepared);
		}
		return $result;
	}

	/** Successful frontend joins retire deleted declarations before either backend resolves names. */
	public function remove_deleted_sources(Storage $sources /** Storage<collected_file> */): void
	{
		$retired = $this->retiring_owners($sources, false);
		$this->notify_retiring($retired);
		foreach ($retired as $owner /** @object-key */) {
			$this->retire($owner);
		}
		foreach ($sources as $source) {
			$this->remove_deleted($source);
		}
	}

	/** Full reset has no surviving consumers; sever registrations before dropping graph roots. */
	public function discard_sources(Storage $sources /** Storage<collected_file> */): void
	{
		$retired = $this->retiring_owners($sources, true);
		foreach ($retired as $owner /** @object-key */) {
			$this->retire($owner);
		}
		foreach ($sources as $source) {
			$source->preparation_changes = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		}
	}

	/** Mark the complete batch before notification, including bodies belonging to deleted functions. */
	private function retiring_owners(Storage $sources /** Storage<collected_file> */, bool $all): \SplObjectStorage /** hash<bool, shared<preparation_owner>> */
	{
		$result /** hash<bool, shared<preparation_owner>> */ = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		foreach ($sources as $source)
		{
			if ($all || $source->deleted) {
				if ($source->body_preparation !== null) {
					$result[$source->body_preparation] = true;
				}
			}
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($entries as $entry)
			{
				if ((!$all) && (!$source->deleted) && ($entry->change_status !== change_state::deleted)) {
					continue;
				}
				if ($entry->preparation !== null) {
					$result[$entry->preparation] = true;
				}
				if ($entry->kind === collected_name_kind::function_declaration) {
					$function = Syntax_Nodes::function_data($entry->node);
					if ($function->body_preparation !== null) {
						$result[$function->body_preparation] = true;
					}
				}
			}
		}
		foreach ($result as $owner /** @object-key */) {
			$owner->change_status = change_state::deleted;
		}
		return $result;
	}

	/** Traverse intact reverse links once; deleted owners cannot be scheduled back into work. */
	private function notify_retiring(\SplObjectStorage $retired /** hash<bool, shared<preparation_owner>> */): void
	{
		$pending /** Storage<preparation_owner> */ = new Storage();
		$seen /** hash<bool, shared<preparation_owner>> */ = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		foreach ($retired as $owner /** @object-key */) {
			$pending->append($owner);
		}
		for ($index = 0; $index < q_count($pending); $index++)
		{
			$owner = $pending[$index];
			if (isset($seen[$owner])) {
				continue;
			}
			$seen[$owner] = true;
			$this->schedule($owner);
			foreach ($owner->dependents as $dependent /** @object-key */) {
				$pending->append($dependent);
			}
		}
	}

	/** Source change flags seed stable owners; dependencies extend the same work lists. */
	private function select(collected_file $source): void
	{
		if ($source->body_preparation === null) {
			$source->body_preparation = new preparation_owner(preparation_kind::file_body, $source);
		}
		$file_body /** preparation_owner */ = $source->body_preparation;
		$this->select_owner($file_body);
		$child = $source->root->first_child();
		while ($child !== null)
		{
			$node /** ast_node */ = $child;
			if (($node->kind() === node_kind::function_declaration) || ($node->kind() === node_kind::struct_declaration)) {
				$owner = $this->declaration_owner($node->payload()->occurrence());
				$this->select_owner($owner);
			}
			if ($node->kind() === node_kind::function_declaration)
			{
				$function = Syntax_Nodes::function_data($node);
				if ($function->body_preparation === null) {
					$function->body_preparation = new preparation_owner(preparation_kind::function_body, $source, $function->occurrence());
				}
				$body /** preparation_owner */ = $function->body_preparation;
				$this->select_owner($body);
			}
			$child = $node->next();
		}
	}

	private function select_owner(preparation_owner $owner): void
	{
		$this->owners[$owner] = true;
		if ($owner->change_status !== change_state::unchanged) {
			$this->schedule($owner);
		}
	}

	/** A function signature and its body have different owners and independent queue membership. */
	private function schedule(preparation_owner $owner): void
	{
		if ($owner->source->deleted || ($owner->change_status === change_state::deleted)) {
			return;
		}
		if ($owner->declaration !== null) {
			$declaration /** collected_name */ = $owner->declaration;
			if ($declaration->change_status === change_state::deleted) {
				return;
			}
		}
		if ($owner->change_status !== change_state::added) {
			$owner->change_status = change_state::changed;
		}
		if ($owner->kind === preparation_kind::declaration) {
			$entry = object_cast($owner->declaration, collected_name::class);
			if ($entry->change_status !== change_state::added) {
				$entry->change_status = change_state::changed;
			}
		}
		if ($owner->state === preparation_state::processing) {
			return;
		}
		$owner->state = preparation_state::pending;
		if (isset($this->attempt_failed[$owner])) {
			return;
		}
		if ($owner->kind === preparation_kind::declaration) {
			$this->declarations[$owner] = true;
		}
		elseif ($owner->kind === preparation_kind::function_body) {
			$this->function_bodies[$owner] = true;
		}
		else {
			$this->file_bodies[$owner] = true;
		}
	}

	public function declaration_owner(collected_name $entry): preparation_owner
	{
		if ($entry->preparation === null) {
			$entry->preparation = new preparation_owner(preparation_kind::declaration, $entry->collection, $entry);
		}
		$owner /** preparation_owner */ = $entry->preparation;
		return $owner;
	}

	/** Identity lookup alone does not require a completed declaration; callers choose when it does. */
	public function depend(preparation_owner $owner, collected_name $entry): preparation_owner
	{
		$target = $this->declaration_owner($entry);
		$owner->dependencies[$target] = $target->version;
		$target->dependents[$owner] = true;
		return $target;
	}

	/** Register the dependency before demanding completed facts; failed facts are never consumed. */
	public function require_declaration(preparation_owner $owner, collected_name $entry): preparation_owner
	{
		$target = $this->depend($owner, $entry);
		$this->declaration($target);
		$owner->dependencies[$target] = $target->version;
		return $target;
	}

	/** By-value record layout requires completion and therefore detects declaration cycles. */
	public function require_record(type_definition $type, preparation_context $context): void
	{
		if ($type->kind !== type_kind::record) {
			return;
		}
		$entry = object_cast($type->declaration, collected_name::class);
		$this->require_declaration($context->owner, $entry);
	}

	/** Preserve dependency ownership while replacing only the selected owner's registrations. */
	private function detach_dependencies(preparation_owner $owner): void
	{
		foreach ($owner->dependencies as $target /** @object-key */) {
			unset($target->dependents[$owner]);
		}
		foreach ($owner->lookups as $lookup /** @object-key */) {
			unset($lookup->dependents[$owner]);
			if (q_count($lookup->dependents) === 0) {
				$lookup->scope->remove_preparation_lookup($lookup);
			}
		}
		$owner->dependencies = new \SplObjectStorage /** hash<int, shared<preparation_owner>> */();
		$owner->lookups = new \SplObjectStorage /** hash<bool, shared<preparation_lookup>> */();
	}

	/** Rebuild selected signatures/layout facts; only effective changes propagate to consumers. */
	public function declaration(preparation_owner $owner): void
	{
		if ($owner->state === preparation_state::processing) {
			throw new \RuntimeException('Cyclic by-value declaration dependency');
		}
		unset($this->declarations[$owner]);
		if (isset($this->attempt_failed[$owner])) {
			throw new \RuntimeException($owner->failure_message);
		}
		if ($owner->change_status === change_state::unchanged)
		{
			// A new edge can close a cycle through previously ready declarations.
			$owner->state = preparation_state::processing;
			try {
				foreach ($owner->dependencies as $dependency /** @object-key */) {
					$this->declaration($dependency);
				}
			}
			catch (\RuntimeException $error) {
				$this->failure($owner, $error->getMessage());
				throw $error;
			}
			$owner->state = preparation_state::ready;
			if ($owner->change_status === change_state::unchanged) {
				return;
			}
		}
		$entry = object_cast($owner->declaration, collected_name::class);
		$node = $entry->node;
		$recovering = $owner->failed;
		$old_dependencies /** hash<int, shared<preparation_owner>> */ = $owner->dependencies;
		$this->detach_dependencies($owner);
		$owner->state = preparation_state::processing;
		$context = $this->context($owner);
		try
		{
			if ($entry->kind === collected_name_kind::function_declaration)
			{
				$function_syntax = Syntax_Nodes::function_data($node);
				$old_signature = $function_syntax->preparation();
				Declaration_Preparation::prepare_function($function_syntax, $context);
				$changed = !Preparation_Changes::function_signature($old_signature, $function_syntax->require_preparation());
				if (!$changed) {
					$previous_signature /** prepared_function */ = $old_signature;
					$function_syntax->set_preparation($previous_signature);
					Preparation_Changes::restore_parameters($function_syntax, $previous_signature);
				}
			}
			else
			{
				$record_syntax = Syntax_Nodes::struct_data($node);
				$old_record = $record_syntax->preparation();
				Declaration_Preparation::prepare_struct($record_syntax, $context);
				$changed = !Preparation_Changes::record($old_record, $record_syntax->require_preparation());
				foreach ($old_dependencies as $target /** @object-key */) {
					if ($old_dependencies[$target] !== $target->version) {
						$changed = true;
					}
				}
				if (!$changed) {
					$previous_record /** prepared_record */ = $old_record;
					$record_syntax->set_preparation($previous_record);
					Preparation_Changes::restore_fields($record_syntax, $previous_record);
				}
			}
			$this->settle($owner);
			$this->settle_members($entry);
			if ($changed || $recovering) {
				$owner->version++;
				$owner->source->preparation_changes[$owner] = true;
				foreach ($owner->dependents as $dependent /** @object-key */) {
					$this->schedule($dependent);
				}
			}
		}
		catch (\RuntimeException $error) {
			$this->failure($owner, $error->getMessage());
			throw $error;
		}
	}

	/** Declarations have settled; each selected body replaces facts and dependencies once. */
	private function body(preparation_owner $owner): void
	{
		$this->detach_dependencies($owner);
		$owner->state = preparation_state::processing;
		$context = $this->context($owner);
		try
		{
			if ($owner->kind === preparation_kind::function_body) {
				$entry = object_cast($owner->declaration, collected_name::class);
				$syntax = Syntax_Nodes::function_data($entry->node);
				$this->require_declaration($owner, $entry);
				Preparation_Cleanup::tree($syntax->body);
				Declaration_Preparation::prepare_body($syntax, $context);
			}
			else {
				$this->file_body($owner->source->root, $context);
			}
			$this->settle($owner);
			$owner->version++;
			$owner->source->preparation_changes[$owner] = true;
		}
		catch (\RuntimeException $error) {
			$this->failure($owner, $error->getMessage());
		}
	}

	/** Success alone clears persistent selection/error state; prior increments need no reset. */
	private function settle(preparation_owner $owner): void
	{
		$owner->state = preparation_state::ready;
		$owner->change_status = change_state::unchanged;
		$owner->failed = false;
		$owner->failure_message = '';
	}

	/** Parameters and fields settle with their enclosing declaration, not during parsing. */
	private function settle_members(collected_name $entry): void
	{
		$entry->change_status = change_state::unchanged;
		$nodes /** Storage<ast_node> */ = $entry->kind === collected_name_kind::function_declaration
		? Syntax_Nodes::function_data($entry->node)->parameters
		: Syntax_Nodes::struct_data($entry->node)->fields;
		foreach ($nodes as $node) {
			$node->payload()->occurrence()->change_status = change_state::unchanged;
		}
	}

	/** Propagate through reverse links once; unchanged signatures do not hide later recovery. */
	private function failure(preparation_owner $owner, string $message): void
	{
		$this->attempt_failed[$owner] = true;
		$owner->state = preparation_state::pending;
		$pending /** Storage<preparation_owner> */ = new Storage();
		$seen /** hash<bool, shared<preparation_owner>> */ = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$pending->append($owner);
		for ($index = 0; $index < q_count($pending); $index++)
		{
			$current = $pending[$index];
			if (isset($seen[$current])) {
				continue;
			}
			$seen[$current] = true;
			$current->failed = true;
			$current->failure_message = $message;
			$this->schedule($current);
			foreach ($current->dependents as $dependent /** @object-key */) {
				$pending->append($dependent);
			}
		}
	}

	/** Deduplicate originating messages and withhold completion if any selected owner remains dirty. */
	private function require_success(): void
	{
		$messages /** hash<bool> */ = [];
		$error = '';
		foreach ($this->owners as $owner /** @object-key */)
		{
			if ($owner->change_status !== change_state::unchanged)
			{
				$message = $owner->failure_message;
				if ($message === '') {
					$message = 'Preparation still has changed work';
				}
				if (!isset($messages[$message])) {
					$messages[$message] = true;
					$error .= $message . "\n";
				}
			}
		}
		if ($error !== '') {
			throw new \RuntimeException($error);
		}
	}

	/** File declarations are prepared by their own list, never as executable entry statements. */
	private function file_body(ast_node $root, preparation_context $context): void
	{
		$child = $root->first_child();
		while ($child !== null)
		{
			$node /** ast_node */ = $child;
			if (($node->kind() !== node_kind::function_declaration) && ($node->kind() !== node_kind::struct_declaration)) {
				Preparation_Cleanup::tree($node);
				$node->payload()->prepare_statement($node, $context);
			}
			$child = $node->next();
		}
	}

	/** Context is transient and always uses the selected owner's source token generation. */
	private function context(preparation_owner $owner): preparation_context
	{
		$context = new preparation_context();
		$context->worker = $this;
		$context->owner = $owner;
		$context->collection = $owner->source;
		$context->locals = new Key_Storage_List /** Key_Storage_List<prepared_storage> */();
		$context->integer = Language_Types::integer($this->language);
		$context->boolean = Language_Types::boolean($this->language);
		$context->floating = Language_Types::floating($this->language);
		return $context;
	}

	/** Observe every visited lookup pool, including absent names, before selecting a target. */
	public function observe(scope $scope, string $name, preparation_lookup_kind $kind, preparation_owner $owner): void
	{
		$existing /** nullable<preparation_lookup> */ = null;
		foreach ($scope->preparation_lookups_named($name) as $candidate) {
			if ($candidate->kind === $kind) {
				$existing = $candidate;
				break;
			}
		}
		if ($existing === null) {
			$existing = new preparation_lookup($scope, $name, $kind);
			$existing->candidates = $this->candidates($existing);
			$scope->add_preparation_lookup($existing);
		}
		$lookup /** preparation_lookup */ = $existing;
		$owner->lookups[$lookup] = true;
		$lookup->dependents[$owner] = true;
	}

	private function candidates(preparation_lookup $lookup): array /** vector<collected_name> */
	{
		if ($lookup->kind === preparation_lookup_kind::type) {
			return $lookup->scope->source_types_named($lookup->name);
		}
		return $lookup->scope->functions_named($lookup->name);
	}

	/** Membership changes can invalidate successful, ambiguous or previously missing lookups. */
	private function changed_lookups(): void
	{
		$seen /** hash<bool, shared<preparation_lookup>> */ = new \SplObjectStorage /** hash<bool, shared<preparation_lookup>> */();
		foreach ($this->owners as $owner /** @object-key */)
		{
			foreach ($owner->lookups as $lookup /** @object-key */)
			{
				if (isset($seen[$lookup])) {
					continue;
				}
				$seen[$lookup] = true;
				$candidates = $this->candidates($lookup);
				$changed = q_count($candidates) !== q_count($lookup->candidates);
				if (!$changed) {
					foreach ($candidates as $index => $entry) {
						if ($entry !== $lookup->candidates[$index]) {
							$changed = true;
						}
					}
				}
				$lookup->candidates = $candidates;
				if ($changed) {
					foreach ($lookup->dependents as $dependent /** @object-key */) {
						$this->schedule($dependent);
					}
				}
			}
		}
	}

	/** Relationships are already detached; remove collected rows and their scope/index memberships. */
	private function remove_deleted(collected_file $source): void
	{
		$entries /** Storage<collected_name> */ = $source->entries;
		$removed /** vector<int> */ = [];
		foreach ($entries as $entry)
		{
			if (!$source->deleted && ($entry->change_status !== change_state::deleted)) {
				continue;
			}
			if ($entry->preparation !== null) {
				$entry->preparation = null;
			}
			if ($entry->kind === collected_name_kind::function_declaration) {
				$function = Syntax_Nodes::function_data($entry->node);
				if ($function->body_preparation !== null) {
					$function->body_preparation = null;
				}
			}
			$scope = object_cast(weakref_get($entry->scope), scope::class);
			$scope->unregister($entry);
			$global = $scope->published_scope();
			if ($entry->exported && ($global !== null)) {
				$published /** scope */ = $global;
				$published->unregister($entry);
			}
			$removed[] = $entry->local_index;
		}
		foreach ($removed as $index) {
			$entries->remove($index);
		}
		$source->defined_elements = $this->present($entries, $source->defined_elements);
		$source->variable_references = $this->present($entries, $source->variable_references);
		$source->function_references = $this->present($entries, $source->function_references);
		$source->type_references = $this->present($entries, $source->type_references);
		$source->field_references = $this->present($entries, $source->field_references);
		$source->pending_bindings = $this->present($entries, $source->pending_bindings);
		if ($source->deleted)
		{
			if ($source->body_preparation !== null) {
				$source->body_preparation = null;
			}
			$source->prepared = null;
			$source->parse_complete = false;
			$source->root->detach_children();
			object_cast($source->root->payload(), block_structure::class)->children = new Storage /** Storage<ast_node> */();
		}
	}

	/** Compact occurrence work lists after their collected rows have been removed. */
	private function present(Storage $entries /** Storage<collected_name> */, array $indexes /** vector<int> */): array /** vector<int> */
	{
		$result /** vector<int> */ = [];
		foreach ($indexes as $index) {
			if (isset($entries[$index])) {
				$result[] = $index;
			}
		}
		return $result;
	}

	/** Explicitly sever both directions; strong-reference storage must not keep retired owners alive. */
	private function retire(preparation_owner $owner): void
	{
		$owner->change_status = change_state::deleted;
		$owner->source->preparation_changes[$owner] = true;
		foreach ($owner->dependents as $dependent /** @object-key */) {
			unset($dependent->dependencies[$owner]);
		}
		$this->detach_dependencies($owner);
		$owner->dependents = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		unset($this->declarations[$owner]);
		unset($this->function_bodies[$owner]);
		unset($this->file_bodies[$owner]);
		unset($this->owners[$owner]);
		unset($this->attempt_failed[$owner]);
	}
}
