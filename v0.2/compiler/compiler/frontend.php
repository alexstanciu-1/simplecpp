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

	public function collect(source_file $source, ?declaration_sink $sink = null): ast_file_node
	{
		return (new Parser($this->scan($source), $sink))->parse();
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
}
