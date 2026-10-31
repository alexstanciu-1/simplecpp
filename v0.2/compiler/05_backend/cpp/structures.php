<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Complete candidate bundle; logical source membership is independent of physical output spelling. */
final readonly class cpp_program_output
{
	public string $entry_path;
	public array $source_units /** array<string,string>; logical source -> relative C++ path */;
	public array $files /** array<string,string>; relative artifact path -> complete text */;

	public function __construct(string $entry_path, array $source_units /** array<string,string> */,
		array $files /** array<string,string> */)
	{
		$this->entry_path = $entry_path;
		$this->source_units = $source_units;
		$this->files = $files;
	}
}
