<?php
namespace scpp\compiler;

/** Transient read-only export of settled source identities, never a lookup index. */
final class prepared_source_evidence
{
	public Storage $declarations /** Storage<collected_definition> */;
	public Storage $references /** Storage<collected_definition> */;
	public function __construct(public collected_file $source)
	{
		$this->declarations = new Storage /** Storage<collected_definition> */();
		$this->references = new Storage /** Storage<collected_definition> */();
	}
}
