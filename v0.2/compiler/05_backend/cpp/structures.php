<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Complete candidate bundle; logical source membership is independent of physical output spelling. */
final readonly class cpp_program_output
{
	/** PROPERTIES */
	public string $entry_path;
	/** @var string[] Sources rendered during this emission pass, excluding reused text. */
	public array $emitted_sources /** list<string> */;
	/** @var string[] Prebuilt dependency artifacts, unchecked. */
	public array $link_inputs /** list<string> */;
	/** @var string[] External native inputs, never generated-output cleanup ownership. */
	public array $native_sources /** list<string> */;
	/** @var string[] Logical source -> relative C++ path. */
	public array $source_units /** array<string,string> */;
	/** @var string[] Relative artifact path -> complete text. */
	public array $files /** array<string,string> */;

	/** CONSTRUCTOR */
	/**
	 * @param string[] $source_units
	 * @param string[] $files
	 * @param string[] $native_sources
	 * @param string[] $emitted_sources
	 * @param string[] $link_inputs
	 */
	public function __construct(string $entry_path, array $source_units /** array<string,string> */,
		array $files /** array<string,string> */, array $native_sources /** list<string> */ = [], array $link_inputs /** list<string> */ = [], array $emitted_sources /** list<string> */ = [])
	{
		$this->entry_path = $entry_path;
		$this->emitted_sources = $emitted_sources;
		$this->link_inputs = $link_inputs;
		$this->native_sources = $native_sources;
		$this->source_units = $source_units;
		$this->files = $files;
	}

	/** METHODS */
	/** Native build inputs exclude generated headers; depfiles track includes. @return string[] */
	public function translation_units(): array /** list<string> */
	{
		return array_values(array_filter(array_keys($this->files), static fn(string $path): bool => str_ends_with($path, '.cpp')));
	}

	/** @return string[] */
	public function headers(): array /** list<string> */
	{
		return array_values(array_filter(array_keys($this->files), static fn(string $path): bool => str_ends_with($path, '.hpp')));
	}
}
