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
}
