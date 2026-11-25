<?php

/* Standalone composition adapter: retire through lifecycle, then run shared preparation. */
namespace scpp\compiler;

final class File_Preparation
{
	public function __construct(private collected_file $source, private scope $language) {}

	/** Standalone callers use the same incremental worker and selections as the compiler. */
	public function prepare(): prepared_file
	{
		$sources /** Storage<collected_file> */ = new Storage();
		$sources->append($this->source);
		Compiler_Lifecycle::retire_deleted_sources($sources, $this->language);
		Trait_Composition::synchronize($sources, $this->language);
		$prepared /** Storage<prepared_file> */ = Preparation_Run::prepare($sources, $this->language);
		return $prepared[0];
	}
}
