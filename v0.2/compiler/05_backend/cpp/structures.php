<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Complete candidate bundle; logical source membership is independent of physical output spelling.
 */
final readonly class cpp_program_output
{
	public string $entry_path;
	/** Prebuilt dependency artifacts, unchecked. */
	public array $link_inputs /** list<string> */;
	/** External native inputs, never generated-output cleanup ownership. */
	public array $native_sources /** list<string> */;
	/** Logical source -> relative C++ path. */
	public array $source_units /** array<string,string> */;
	/** Relative artifact path -> complete text. */
	public array $files /** array<string,string> */;

	public function __construct(string $entry_path, array $source_units /** array<string,string> */,
		array $files /** array<string,string> */, array $native_sources /** list<string> */ = [], array $link_inputs /** list<string> */ = [])
	{
		$this->entry_path = $entry_path;
		$this->link_inputs = $link_inputs;
		$this->native_sources = $native_sources;
		$this->source_units = $source_units;
		$this->files = $files;
	}
}
