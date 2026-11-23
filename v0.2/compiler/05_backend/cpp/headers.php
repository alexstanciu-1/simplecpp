<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Plans per-unit headers from completed watch relations, without inspecting executable syntax.
 * Example: a function watched only in its own unit stays declared in that unit's .cpp.
 */
final class Cpp_Headers
{
	/** PROPERTIES */
	/** @var string[] Canonical symbol key to owning header basename; no AST copies. */
	private array $declarations /** array<string,string> */ = [];
	/** @var string[] Relative artifact path to generated header text. */
	private array $files /** array<string,string> */ = [];

	/** @var int[] Ephemeral lowering requirements from completed watch relations. */
	private ?array $helper_uses /** array<string,uint8>|null */ = null;

	/** CONSTRUCTOR */
	/** Plan completed declarations; null creates an empty standalone planner. @param cpp_generator_layout[] $layouts */
	public function __construct(?prepared_program $program = null, array $layouts /** array<string,cpp_generator_layout> */ = [])
	{
		if ($program === null) return;
		$this->helper_uses = [];
		foreach ($program->files as $file) {
			foreach ($file->declaration_nodes as $node) {
				if ($node instanceof ast_enum_declaration) $uses = [dependency_use::enum_from_value, dependency_use::enum_name, dependency_use::enum_value];
				elseif ($node instanceof ast_function_declaration && $node->is_async) $uses = [dependency_use::async_wait, dependency_use::async_task];
				else continue;
				$symbol = $file->scope->parent->entry($node->symbol_key());
				foreach ($uses as $use) {
					if ($program->dependencies->has_use($symbol, $use)) {
						$key = $node->symbol_key();
						$this->helper_uses[$key] = ($this->helper_uses[$key] ?? 0) | $use->value;
					}
				}
			}
		}
		$emitter = new Cpp_Emitter($this, $program->session?->cpp_namespace ?? 'App', $layouts);
		foreach ($program->files as $file) $this->publish_layout_dependencies($file, $emitter, $program->session?->cpp_namespace ?? 'App');
		foreach ($program->files as $key => $file) {
			$source_key = (string) $key;
			$header = hash('sha256', (string) $key) . '.hpp';
			$lines = [];
			$owners = [];
			$record_headers = [];
			$includes = [];
			foreach ($file->published_declarations() as $node) {
				if (!($node instanceof ast_function_declaration) && !($node instanceof ast_global_declaration) && !($node instanceof ast_type_declaration)) {
					continue;
				}
				$symbol = $file->scope->parent->entry($node->symbol_key());
				if (!$program->dependencies->has_external_observers($symbol, $source_key)) {
					continue;
				}
				if ($node instanceof ast_global_declaration && $node->parent instanceof ast_record_declaration) {
					$this->publish_record($node->parent, $emitter, $program->session?->cpp_namespace ?? 'App');
					continue;
				}
				if ($node instanceof ast_enum_declaration) {
					$this->publish_enum($node, $emitter, $program->session?->cpp_namespace ?? 'App');
					continue;
				}
				if ($node instanceof ast_record_declaration) {
					$this->publish_record($node, $emitter, $program->session?->cpp_namespace ?? 'App');
					continue;
				}
				if ($node instanceof ast_function_declaration) {
					if ($node->is_constructor() || $node->is_destructor()) continue;
					if ($node->has_virtual_dispatch()) {
						$this->declarations[$node->symbol_key()] = $this->publish_record($node->parent, $emitter, $program->session?->cpp_namespace ?? 'App');
						continue;
					}
					if ($node->has_receiver() && $node->parent instanceof ast_record_declaration) {
						$type = $node->parent->definition;
						$owners[$type->cpp_type()] = Cpp_Emitter::namespace_body($type->cpp_namespace(),
							'struct ' . Cpp_Identifier::source($node->parent->short_name) . ";\n");
					}
					$emitter->visit_application_types($node, function (type_definition $type) use ($node, $program, $emitter,
						&$owners, &$record_headers, &$includes): void {
						if ($type instanceof intersection_type_definition) {
							$owners[$type->cpp_type()] = 'struct ' . $type->cpp_type() . ';';
						} elseif ($type instanceof application_collection_type) {
							if ($node->return_type_def() instanceof generator_type_definition) {
								$record_headers[$this->collection_header($type, $program->session?->cpp_namespace ?? 'App')] = true;
							} else $owners[$type->cpp_type()] = 'class ' . $type->cpp_type() . ';';
						} elseif ($type instanceof enum_type_definition) {
							$record_headers[$this->publish_enum($type->declaration, $emitter, $program->session?->cpp_namespace ?? 'App')] = true;
						} elseif ($type instanceof record_type_definition) {
							$header_name = $this->publish_record($type->declaration, $emitter, $program->session?->cpp_namespace ?? 'App');
							$record_headers[$header_name] = true;
						} else {
							$owners[$type->cpp_type()] = 'struct ' . $type->cpp_type() . ';';
							$record_header = $this->publish_record($type->payload->declaration, $emitter, $program->session?->cpp_namespace ?? 'App');
							if ($node->return_type_def() instanceof generator_type_definition) {
								$record_headers[$record_header] = true;
								$includes['scpp/owner_access.hpp'] = true;
								$owners[$type->cpp_type()] = $emitter->owner_declaration($type->payload->shared, $includes)
									. $emitter->owner_methods($type->payload->shared, $includes);
							}
						}
					});
					if ($node->is_static) {
						foreach ($file->static_implementations($node) as $implementation) {
							if (!$implementation->instance->external) continue;
							$lines[Cpp_Emitter::function_name($implementation)] = $emitter->function_declaration($implementation, $includes);
						}
					} else $lines[$node->symbol_key()] = $emitter->function_declaration($node, $includes);
				} elseif ($node instanceof ast_global_declaration) {
					$lines[$node->symbol_key()] = $emitter->global_declaration($node, $includes);
				}
				$this->declarations[$node->symbol_key()] = $header;
			}
			if ($lines === []) {
				continue;
			}
			ksort($includes, \SORT_STRING);
			ksort($owners, \SORT_STRING);
			ksort($lines, \SORT_STRING);
			$text = "// Generated cross-unit declarations.\n#pragma once\n";
			foreach ($includes as $include => $_) {
				$text .= '#include <' . $include . ">\n";
			}
			ksort($record_headers, \SORT_STRING);
			foreach ($record_headers as $record_header => $_) $text .= "#include \"$record_header\"\n";
			$this->files['units/' . $header] = $text . Cpp_Emitter::namespace_body(
				$program->session?->cpp_namespace ?? 'App', implode("\n", array_merge($owners, $lines)) . "\n");
		}
		ksort($this->files, \SORT_STRING);
	}

	/** METHODS */
	public function uses_helper(ast_function_declaration|ast_enum_declaration $node, dependency_use $use): bool
	{
		if ($this->helper_uses === null) return $node instanceof ast_function_declaration;
		return (($this->helper_uses[$node->symbol_key()] ?? 0) & $use->value) !== 0;
	}

	/** Publish one nominal enum header for signatures and foreign case references. */
	private function publish_enum(ast_enum_declaration $enum, Cpp_Emitter $emitter, string $root): string
	{
		$key = $enum->symbol_key();
		if (isset($this->declarations[$key])) return $this->declarations[$key];
		$header = $this->declarations[$key] = hash('sha256', 'enum:' . $key) . '.hpp';
		$includes = [];
		$definition = $emitter->enum_declaration($enum, $includes);
		$text = "// Generated enum declaration.\n#pragma once\n";
		foreach ($includes as $include => $_) $text .= "#include <$include>\n";
		$this->files['units/' . $header] = $text . Cpp_Emitter::namespace_body($root, $definition);
		return $header;
	}

	/** Publish the complete public type. A shared edge can lead back to a header already in progress. */
	private function publish_record(ast_record_declaration $record, Cpp_Emitter $emitter, string $root): string
	{
		$key = $record->symbol_key();
		if (isset($this->declarations[$key])) return $this->declarations[$key];
		$header = $this->declarations[$key] = self::record_header($record);
		$layout = $this->publish_layout($record, $emitter, $root);
		$includes = [$layout => true];
		if ($record->definition->base() !== null) {
			$base_header = $this->publish_record($record->definition->base()->declaration, $emitter, $root);
			$includes[$base_header] = true;
		}
		foreach ($record->definition->interfaces() as $interface) {
			$includes[$this->publish_record($interface->declaration, $emitter, $root)] = true;
		}
		$methods = [];
		$headers = [];
		foreach ($record->fields as $field) {
			$type = $field->storage_type();
			if ($type instanceof record_handle_definition) {
				$methods[$type->payload->name] ??= $emitter->owner_methods($type->payload->shared, $headers);
				$type = $type->payload;
			}
			if ($type instanceof record_type_definition) {
				$child = $this->publish_record($type->declaration, $emitter, $root);
				if ($child !== $header) $includes[$child] = true;
			}
		}
		if ($layout !== $header) {
			$text = "#pragma once\n";
			foreach ($headers as $include => $_) $text .= "#include <$include>\n";
			if ($methods !== []) $text .= "#include <scpp/owner_access.hpp>\n";
			foreach ($includes as $include => $_) $text .= "#include \"$include\"\n";
			$this->files['units/' . $header] = $text . Cpp_Emitter::namespace_body($root, implode('', $methods));
		}
		return $header;
	}

	/** Inline edges require complete field layouts; shared fields require only the pointer-sized owner declaration. */
	private function publish_layout(ast_record_declaration $record, Cpp_Emitter $emitter, string $root): string
	{
		$header = self::record_header($record);
		$layout = hash('sha256', "layout\0" . $record->symbol_key()) . '.hpp';
		if (isset($this->files['units/' . $layout])) return $layout;
		if (isset($this->files['units/' . $header])) return $header;
		$children = [];
		$owners = [];
		$needs_methods = false;
		$includes = [];
		if ($record->definition->base() !== null) {
			$base = $record->definition->base()->declaration;
			$base_layout = $this->publish_layout($base, $emitter, $root);
			$children[$base_layout] = true;
			$needs_methods = $base_layout !== self::record_header($base);
		}
		foreach ($record->definition->interfaces() as $interface) {
			$children[$this->publish_layout($interface->declaration, $emitter, $root)] = true;
		}
		foreach ($record->fields as $field) {
			$type = $field->storage_type();
			if ($type instanceof application_collection_type) {
				$children[$this->publish_collection($type, $emitter, $root)] = true;
			} elseif ($type instanceof record_handle_definition) {
				$owners[$type->payload->name] ??= $emitter->owner_declaration($type->payload->shared, $includes);
				$needs_methods = true;
			} elseif ($type instanceof enum_type_definition) {
				$children[$this->publish_enum($type->declaration, $emitter, $root)] = true;
			} elseif ($type instanceof record_type_definition) {
				$child = $this->publish_layout($type->declaration, $emitter, $root);
				$children[$child] = true;
				$needs_methods = $needs_methods || $child !== self::record_header($type->declaration);
			}
		}
		$definition = $emitter->record_declaration($record, $includes);
		$text = "// Generated inline record layout.\n#pragma once\n";
		foreach ($includes as $include => $_) $text .= "#include <$include>\n";
		foreach ($children as $child => $_) $text .= "#include \"$child\"\n";
		$path = $needs_methods ? $layout : $header;
		$this->files['units/' . $path] = $text . Cpp_Emitter::namespace_body($root, implode('', $owners) . $definition . "\n");
		return $path;
	}

	/** Publish a self-contained application value header for a separate implementation unit. */
	public function publish_value_header(record_type_definition|application_collection_type $type, Cpp_Emitter $emitter, string $root): string
	{
		if ($type instanceof application_collection_type) return $this->publish_collection($type, $emitter, $root);
		return $this->publish_record($type->declaration, $emitter, $root);
	}

	/** Publish a concrete field dependency after complete element declarations; reuse the source recipe artifacts. */
	private function publish_collection(application_collection_type $type, Cpp_Emitter $emitter, string $root): string
	{
		$header = hash('sha256', "collection-field\0" . $root . "\0" . $type->cpp_type()) . '.hpp';
		if (isset($this->files['units/' . $header])) return $header;
		$text = "#pragma once\n";
		foreach ($type->concrete_types() as $argument) {
			if ($argument instanceof application_collection_type) {
				$text .= '#include "' . $this->publish_collection($argument, $emitter, $root) . "\"\n";
				continue;
			}
			$record = $argument instanceof record_handle_definition ? $argument->payload : $argument;
			if (!($record instanceof record_type_definition)) continue;
			$dependency = $this->publish_record($record->declaration, $emitter, $root);
			$text .= '#include "' . $dependency . "\"\n";
			if ($argument instanceof record_handle_definition) {
				$includes = [];
				$owner = $emitter->owner_declaration($record->shared, $includes) . $emitter->owner_methods($record->shared, $includes);
				foreach ($includes as $include => $_) $text .= "#include <$include>\n";
				$text .= Cpp_Emitter::namespace_body($root, $owner);
			}
		}
		$concrete = new Cpp_Concrete_Collections($root);
		$concrete->request($type);
		foreach ($concrete->files() as $path => $body) {
			$this->files[$path] = $body;
			$text .= '#include "' . basename($path) . "\"\n";
		}
		$this->files['units/' . $header] = $text;
		return $header;
	}

	/** Publish complete concrete types before record or cursor layouts, including source-private declarations. */
	public function publish_layout_dependencies(ast_file_node $file, Cpp_Emitter $emitter, string $root): void
	{
		foreach ($file->declaration_nodes as $node) {
			if ($node instanceof ast_function_declaration && $node->return_type_def() instanceof generator_type_definition) {
				$emitter->visit_application_types($node, function (type_definition $type) use ($emitter, $root): void {
					if ($type instanceof application_collection_type) $this->publish_collection($type, $emitter, $root);
				});
			}
			if (!($node instanceof ast_record_declaration)) continue;
			foreach ($node->fields as $field) {
				if (!($field->type_def() instanceof application_collection_type)) continue;
				$this->publish_record($node, $emitter, $root);
				break;
			}
		}
	}

	/** Read settled publication only; private source lowering must not mutate the shared header plan. */
	public function collection_header(application_collection_type $type, string $root): string
	{
		$header = hash('sha256', "collection-field\0" . $root . "\0" . $type->cpp_type()) . '.hpp';
		if (!isset($this->files['units/' . $header])) throw new \LogicException('Concrete layout dependency was not published');
		return $header;
	}

	/** Did type visibility or the published enum helper contract change for this source? */
	public function type_visibility_changed(ast_file_node $file, cpp_program_output $previous): bool
	{
		foreach ($file->declaration_nodes as $record) {
			if (!($record instanceof ast_type_declaration)) continue;
			$header = $record instanceof ast_enum_declaration ? hash('sha256', 'enum:' . $record->symbol_key()) . '.hpp' : self::record_header($record);
			if ($record instanceof ast_enum_declaration && ($this->files['units/' . $header] ?? null) !== ($previous->files['units/' . $header] ?? null)) return true;
			if (isset($this->declarations[$record->symbol_key()]) !== isset($previous->files['units/' . $header])) {
				return true;
			}
		}
		return false;
	}

	public function declaration_header(ast_declaration_interface $node): ?string
	{
		if ($node instanceof ast_function_declaration && $node->instance !== null && !$node->instance->external) return null;
		return $this->declarations[$node->symbol_key()] ?? null;
	}

	/** @return string[] */
	public function files(): array /** array<string,string> */
	{
		return $this->files;
	}

	/** STATIC METHODS */
	private static function record_header(ast_record_declaration $record): string
	{
		return hash('sha256', "struct\0" . $record->symbol_key()) . '.hpp';
	}
}
