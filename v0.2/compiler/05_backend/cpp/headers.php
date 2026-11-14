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

	/** CONSTRUCTOR */
	/** Plan native declarations from completed watchers; header text is independent of source iteration order. */
	public function __construct(prepared_program $program)
	{
		$emitter = new Cpp_Emitter(root: $program->session?->cpp_namespace ?? 'App');
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
				if ($node instanceof ast_global_declaration && $node->parent instanceof ast_struct_declaration) {
					$this->publish_record($node->parent, $emitter, $program->session?->cpp_namespace ?? 'App');
					continue;
				}
				if ($node instanceof ast_enum_declaration) {
					$this->publish_enum($node, $emitter, $program->session?->cpp_namespace ?? 'App');
					continue;
				}
				if ($node instanceof ast_struct_declaration) {
					$this->publish_record($node, $emitter, $program->session?->cpp_namespace ?? 'App');
					continue;
				}
				if ($node instanceof ast_function_declaration) {
					if ($node->is_constructor()) continue;
					if ($node->has_receiver()) {
						$type = $node->parent->definition;
						$owners[$type->cpp_type()] = Cpp_Emitter::namespace_body($type->cpp_namespace(),
							'struct ' . Cpp_Identifier::source($node->parent->short_name) . ";\n");
					}
					foreach (Cpp_Emitter::signature_application_types($node) as $type) {
						if ($type instanceof enum_type_definition) {
							$record_headers[$this->publish_enum($type->declaration, $emitter, $program->session?->cpp_namespace ?? 'App')] = true;
						} elseif ($type instanceof struct_type_definition) {
							$header_name = $this->publish_record($type->declaration, $emitter, $program->session?->cpp_namespace ?? 'App');
							$record_headers[$header_name] = true;
						} else {
							$owners[$type->cpp_type()] = 'struct ' . $type->cpp_type() . ';';
							$this->publish_record($type->payload->declaration, $emitter, $program->session?->cpp_namespace ?? 'App');
						}
					}
				}
				$lines[$node->symbol_key()] = $emitter->declaration($node, $includes);
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
	/** Publish one nominal enum header for signatures and foreign case references. */
	private function publish_enum(ast_enum_declaration $enum, Cpp_Emitter $emitter, string $root): string
	{
		$key = $enum->symbol_key();
		if (isset($this->declarations[$key])) return $this->declarations[$key];
		$header = $this->declarations[$key] = hash('sha256', 'enum:' . $key) . '.hpp';
		$includes = [];
		$definition = $emitter->declaration($enum, $includes);
		$text = "// Generated enum declaration.\n#pragma once\n";
		foreach ($includes as $include => $_) $text .= "#include <$include>\n";
		$this->files['units/' . $header] = $text . Cpp_Emitter::namespace_body($root, $definition);
		return $header;
	}

	/** Publish the complete public type. A shared edge can lead back to a header already in progress. */
	private function publish_record(ast_struct_declaration $record, Cpp_Emitter $emitter, string $root): string
	{
		$key = $record->symbol_key();
		if (isset($this->declarations[$key])) return $this->declarations[$key];
		$header = $this->declarations[$key] = self::record_header($record);
		$layout = $this->publish_layout($record, $emitter, $root);
		$includes = [$layout => true];
		$methods = [];
		$headers = [];
		foreach ($record->fields as $field) {
			$type = $field->type_def();
			if ($type instanceof struct_handle_definition) {
				$methods[$type->payload->name] ??= $emitter->owner_methods($type->payload->shared, $headers);
				$type = $type->payload;
			}
			if ($type instanceof struct_type_definition) {
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
	private function publish_layout(ast_struct_declaration $record, Cpp_Emitter $emitter, string $root): string
	{
		$header = self::record_header($record);
		$layout = hash('sha256', "layout\0" . $record->symbol_key()) . '.hpp';
		if (isset($this->files['units/' . $layout])) return $layout;
		if (isset($this->files['units/' . $header])) return $header;
		$children = [];
		$owners = [];
		$needs_methods = false;
		$includes = [];
		foreach ($record->fields as $field) {
			$type = $field->type_def();
			if ($type instanceof struct_handle_definition) {
				$owners[$type->payload->name] ??= $emitter->owner_declaration($type->payload->shared, $includes);
				$needs_methods = true;
			} elseif ($type instanceof enum_type_definition) {
				$children[$this->publish_enum($type->declaration, $emitter, $root)] = true;
			} elseif ($type instanceof struct_type_definition) {
				$child = $this->publish_layout($type->declaration, $emitter, $root);
				$children[$child] = true;
				$needs_methods = $needs_methods || $child !== self::record_header($type->declaration);
			}
		}
		$definition = $emitter->declaration($record, $includes);
		$text = "// Generated inline record layout.\n#pragma once\n";
		foreach ($includes as $include => $_) $text .= "#include <$include>\n";
		foreach ($children as $child => $_) $text .= "#include \"$child\"\n";
		$path = $needs_methods ? $layout : $header;
		$this->files['units/' . $path] = $text . Cpp_Emitter::namespace_body($root, implode('', $owners) . $definition . "\n");
		return $path;
	}

	/** Was an application type moved between its source's private C++ and a published header? */
	public function type_visibility_changed(ast_file_node $file, cpp_program_output $previous): bool
	{
		foreach ($file->declaration_nodes as $record) {
			if (!($record instanceof ast_type_declaration)) continue;
			$header = $record instanceof ast_enum_declaration ? hash('sha256', 'enum:' . $record->symbol_key()) . '.hpp' : self::record_header($record);
			if (isset($this->declarations[$record->symbol_key()]) !== isset($previous->files['units/' . $header])) {
				return true;
			}
		}
		return false;
	}

	public function declaration_header(ast_declaration_interface $node): ?string
	{
		return $this->declarations[$node->symbol_key()] ?? null;
	}

	/** @return string[] */
	public function files(): array /** array<string,string> */
	{
		return $this->files;
	}

	/** STATIC METHODS */
	private static function record_header(ast_struct_declaration $record): string
	{
		return hash('sha256', "struct\0" . $record->symbol_key()) . '.hpp';
	}
}
