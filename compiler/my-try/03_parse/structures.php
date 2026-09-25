<?php

/*
 * Role: retained syntax roots, node kinds and lexical scopes.
 * Used by: Parser, Symbol_Collector and preparation.
 */
namespace scpp\compiler;

enum node_category {
	case syntax;
	case expression;
	case statement;
}

enum node_kind
{
	case struct_declaration;
	case field_declaration;
	case field_expression;
	case file;
	case function_declaration;
	case parameter_declaration;
	case block;
	case identifier;
	case punctuation;
	case comment;
	case array_type;
	case array_literal;
	case index_expression;
	case integer_literal;
	case variable_reference;
	case binary_expression;
	case assignment_expression;
	case call_expression;
	case expression_statement;
	case return_statement;
	case variable_binding_statement;
}

/** Keep enum reflection beside its declaration for native lowering. */
final class Node_Kind_Name {
	public static function text(node_kind $kind): string
	{
		return enum_name($kind);
	}
}

enum binding_kind {
	case unresolved;
	case declaration;
	case assignment;
}

enum passing_mode {
	case value;
	case reference;
}

/** Marker for additional data owned by a concrete AST node. */
interface node_structure {
}

/** Common syntax header; links are private so native storage may later use positions. */
abstract class ast_node
{
	/** @storage.index token_list.tokens */
	public int $token_index /** uint32 */;
	/** Exclusive token boundary. @storage.boundary token_list.tokens */
	public int $end_token_index /** uint32 */;
	public node_kind $kind;
	/** Extra syntax data owned by this node. @ownership owner */
	public ?node_structure $structure = null;
	/** @reference.source parsed_file.root @reference.weak */
	private ?ast_node $parent_node /** weak<ast_node> */ = null;
	/** @reference.source parsed_file.root @reference.weak */
	private ?ast_node $previous_node /** weak<ast_node> */ = null;
	/** @ownership owner */
	private ?ast_node $next_node = null;
	/** @ownership owner */
	private ?ast_node $first_node = null;
	private int $position /** uint32 */ = 0;

	/** Initialize the compact header before the node is linked or published. */
	public function initialize(int $start, int $end, ?node_structure $data): void
	{
		if (($start < 0) || ($end < $start) || ($end > 4294967295)) {
			throw new \LogicException('AST token span exceeds uint32 bounds');
		}
		$this->token_index = $start;
		$this->end_token_index = $end;
		$this->position = 0;
		$this->structure = $data;
	}

	public function next(): ?ast_node
	{
		return $this->next_node;
	}

	public function prev(): ?ast_node
	{
		$previous = weakref_get($this->previous_node);
		if ($previous === null) {
			return null;
		}
		return object_cast($previous, ast_node::class);
	}

	public function parent(): ?ast_node
	{
		$owner = weakref_get($this->parent_node);
		if ($owner === null) {
			return null;
		}
		return object_cast($owner, ast_node::class);
	}

	public function first_child(): ?ast_node
	{
		return $this->first_node;
	}

	public function child_position(): int
	{
		return (int) $this->position;
	}

	public function has_children(): bool
	{
		return $this->first_node !== null;
	}

	/** Return a membership snapshot; traversal itself does not require a stored collection. */
	public function children(): Storage /** Storage<ast_node> */
	{
		$result /** Storage<ast_node> */ = new Storage();
		$current = $this->first_node;
		while ($current !== null) {
			$child = object_cast($current, ast_node::class);
			$result->append($child);
			$current = $child->next();
		}
		return $result;
	}

	/** Link a complete child list once; explicit handles avoid manufacturing ownership from $this. */
	public static function link_children(ast_node $owner, Storage $children /** Storage<ast_node> */): void
	{
		if ($owner->first_node !== null) {
			throw new \LogicException('AST children already linked');
		}
		if (q_count($children) > 4294967295) {
			throw new \LogicException('AST child positions exceed uint32 bounds');
		}
		// Validate before publishing any links, including duplicate membership.
		$seen /** hash<bool, shared<ast_node>> */ = new \SplObjectStorage /** hash<bool, shared<ast_node>> */();
		foreach ($children as $child)
		{
			if (($child === $owner) || ($child->parent() !== null)) {
				throw new \LogicException('AST child already belongs to a parent');
			}
			if (isset($seen[$child])) {
				throw new \LogicException('Duplicate AST child');
			}
			$ancestor = $owner->parent();
			while ($ancestor !== null) {
				$ancestor_node = object_cast($ancestor, ast_node::class);
				if ($ancestor_node === $child) {
					throw new \LogicException('AST links would form a cycle');
				}
				$ancestor = $ancestor_node->parent();
			}
			$seen[$child] = true;
		}
		$previous = $owner->first_node;
		$position = 0;
		foreach ($children as $child)
		{
			$child->parent_node = $owner;
			$child->position = $position;
			if ($previous === null) {
				$owner->first_node = $child;
			}
			else {
				$prior = object_cast($previous, ast_node::class);
				$prior->next_node = $child;
				$child->previous_node = $prior;
			}
			$previous = $child;
			$position++;
		}
	}
}

final class scope
{
	/** Upward observers; owners are Model roots or parsed_file.scopes. */
	private ?scope $enclosing /** weak<scope> */ = null;
	private ?scope $publication /** weak<scope> */ = null;
	private bool $function_boundary = false;
	private array $template_parameters /** hash<int> */ = [];
	/** @storage.reference collected_file.entries @reference.weak */
	private array $variables /** hash<vector<collected_name>> */ = [];
	/** @storage.reference collected_file.entries @reference.weak */
	private array $functions /** hash<vector<collected_name>> */ = [];
	/** Definitions owned here; published scopes retain the same definition objects. */
	private Storage $types /** Storage<type_definition> */;

	public function __construct()
	{
		$this->types = new Storage /** Storage<type_definition> */();
	}

	public function set_parent(scope $parent): void
	{
		$this->enclosing = $parent;
	}

	public function parent_scope(): ?scope
	{
		return weakref_get($this->enclosing);
	}

	public function published_scope(): ?scope
	{
		return weakref_get($this->publication);
	}

	public function mark_function(): void
	{
		$this->function_boundary = true;
	}

	public function is_function(): bool
	{
		return $this->function_boundary;
	}

	public function set_templates(array $slots /** hash<int> */): void
	{
		$this->template_parameters = $slots;
	}

	public function has_template(string $name): bool
	{
		return isset($this->template_parameters[$name]);
	}

	public function template_slot(string $name): int
	{
		return $this->template_parameters[$name];
	}

	/** Register source identities without merging duplicates or doing name resolution. */
	public function register(collected_name $entry): void
	{
		if ($entry->kind === collected_name_kind::function_declaration) {
			$this->functions[$entry->name][] = $entry;
		}
		elseif ($entry->kind === collected_name_kind::struct_declaration)
		{
			$type = new type_definition();
			$type->name = $entry->name;
			$type->kind = type_kind::record;
			$type->origin = type_origin::source;
			$type->declaration = $entry;
			$this->register_type($type);
		}
		else {
			$this->variables[$entry->name][] = $entry;
		}
	}

	public function register_type(type_definition $definition): void
	{
		$types /** Storage<type_definition> */ = $this->types;
		$types->append($definition);
	}

	/** Local inventories include tombstones; callers choose live resolution explicitly. */
	public function variables_named(string $name): array /** vector<collected_name> */
	{
		$result /** vector<collected_name> */ = [];
		if (isset($this->variables[$name])) {
			$result = $this->variables[$name];
		}
		return $result;
	}

	/** Return a typed snapshot; an absent name has no candidates. */
	public function functions_named(string $name): array /** vector<collected_name> */
	{
		$result /** vector<collected_name> */ = [];
		if (isset($this->functions[$name])) {
			$result = $this->functions[$name];
		}
		return $result;
	}

	/** The small definition store owns records; lookup does not introduce another registry. */
	public function types_named(string $name): array /** vector<type_definition> */
	{
		$result /** vector<type_definition> */ = [];
		$types /** Storage<type_definition> */ = $this->types;
		foreach ($types as $definition) {
			if ($definition->name === $name) {
				$result[] = $definition;
			}
		}
		return $result;
	}

	/** Source-only projection keeps the experimental consumer independent of built-in metadata. */
	public function source_types_named(string $name): array /** vector<collected_name> */
	{
		$result /** vector<collected_name> */ = [];
		foreach ($this->types_named($name) as $definition) {
			if ($definition->declaration !== null) {
				$result[] = object_cast($definition->declaration, collected_name::class);
			}
		}
		return $result;
	}

	public function has_functions(): bool
	{
		return q_count($this->functions) !== 0;
	}

	public function has_variables(): bool
	{
		return q_count($this->variables) !== 0;
	}

	/** Publish references from a completed root without copying source/type identities. */
	public static function publish(scope $local_scope, scope $global): void
	{
		if ($local_scope->published_scope() !== null) {
			throw new \LogicException('Parsed file was already published');
		}
		foreach ($local_scope->functions as $name => $entries) {
			foreach ($entries as $entry) {
				$global->functions[$name][] = $entry;
			}
		}
		foreach ($local_scope->variables as $name => $entries) {
			foreach ($entries as $entry) {
				$global->variables[$name][] = $entry;
			}
		}
		$types /** Storage<type_definition> */ = $local_scope->types;
		foreach ($types as $definition) {
			$global->register_type($definition);
		}
		$local_scope->publication = $global;
	}

	/** Drop superseded live definitions from one file while retaining deletion evidence. */
	public function replace_source(string $path): void
	{
		$this->variables = self::retain_other($this->variables, $path);
		$this->functions = self::retain_other($this->functions, $path);
		$types /** Storage<type_definition> */ = new Storage();
		$previous /** Storage<type_definition> */ = $this->types;
		foreach ($previous as $definition)
		{
			$keep = true;
			if ($definition->declaration !== null) {
				$entry = object_cast($definition->declaration, collected_name::class);
				if ($entry->changes !== \scpp\compiler\SYNC_DELETED) {
					$keep = $entry->file->source->file->path !== $path;
				}
			}
			if ($keep) {
				$types->append($definition);
			}
		}
		$this->types = $types;
	}

	/** Replacement keeps other files and tombstones; it does not create version history. */
	private static function retain_other(array $index /** hash<vector<collected_name>> */, string $path): array /** hash<vector<collected_name>> */
	{
		$result /** hash<vector<collected_name>> */ = [];
		foreach ($index as $name => $entries)
		{
			foreach ($entries as $entry)
			{
				if ($entry->changes === \scpp\compiler\SYNC_DELETED) {
					$result[$name][] = $entry;
					continue;
				}
				if ($entry->file->source->file->path !== $path) {
					$result[$name][] = $entry;
				}
			}
		}
		return $result;
	}
}

/** Completed parsing output, sharing its source and collection with other model roots. */
final class parsed_file
{
	/** @storage.reference model.tokens */
	public token_list $tokens;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $root;
	/**
	 * Convenience link to this file's published collection result.
	 * @storage.reference model.collected_files
	 * @reference.weak
	 */
	public collected_file $collection;
	/** Local scopes; also owns the root scope when parsing without an external scope.
	 * @storage.owner
	 */
	public Storage $scopes /** Storage<scope> */;

	/** Initialize owned stores only; parser operations populate them. */
	public function __construct()
	{
		$this->scopes = new Storage /** Storage<scope> */();
	}
}
