<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Coordinate stages; IO and lexical rules remain in their stage owners. */
final class Source_Frontend
{
	public function scan_file(string $path, flavor $flavor): token_list
	{
		return $this->scan(File_Loader::load($path, $flavor));
	}

	public function scan(source_file $source): token_list
	{
		return (new Tokenizer($source))->tokenize();
	}

	public function parse_file(string $path, flavor $flavor): ast_file_node
	{
		return $this->parse(File_Loader::load($path, $flavor));
	}

	public function parse(source_file $source): ast_file_node
	{
		return $this->collect($source);
	}

	public function collect(source_file $source, ?declaration_sink $sink = null, type_availability $availability = new type_availability()): ast_file_node
	{
		return (new Parser($this->scan($source), $sink, $availability))->parse();
	}

	public function collect_file(string $path, flavor $flavor, ?declaration_sink $sink = null): ast_file_node
	{
		return $this->collect(File_Loader::load($path, $flavor), $sink);
	}

	public function prepare(source_file $source, entry_target $target): prepared_entry
	{
		$publication = new Symbol_Publication();
		$file = $this->collect($source, $publication);
		return Program_Preparation::prepare('entry', ['entry' => $file], $target, $publication->publish(['entry' => $file]))->entry;
	}

	/** Publish the core prototype once per compilation before source-owned preparation jobs start. @param ast_file_node[] $changed */
	public static function prepare_core_interfaces(scope $symbols, array $changed /** array<string,ast_file_node> */): void
	{
		if (Stringable_Contract::SOURCE === null) return;
		$key = 'class:' . Stringable_Contract::NAME;
		if ($symbols->stringable !== null) return;
		foreach ($changed as $file) {
			// Includes import aliases and every source flavor. A mention in trivia only prepares an unused prototype.
			if (str_contains($file->tokens->source->content, Stringable_Contract::NAME)) {
				$symbols->stringable = self::core_interface($key);
				return;
			}
		}
	}

	/** Build a nominal signature through the same declaration and signature checks as authored interfaces. */
	private static function core_interface(string $key): ast_record_declaration
	{
		$source = new source_file('<Stringable>', Stringable_Contract::SOURCE, flavor::cpp);
		$file = (new Source_Frontend())->parse($source);
		$files = ['<Stringable>' => $file];
		$core = Symbol_Publication::completed($files);
		$dependencies = new Dependency_Watches();
		Program_Preparation::layouts($files, $core, $dependencies);
		Function_Preparation::declarations($core, $dependencies);
		return $core->resolve($key);
	}
}
