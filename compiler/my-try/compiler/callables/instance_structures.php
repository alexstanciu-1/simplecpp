<?php
namespace scpp\compiler;

/** Original method owns each independent working body; called type is not its declaring type. */
final class callable_instance
{
	private collected_method $origin_reference /** weak<collected_method> */;
	private collected_record $called_reference /** weak<collected_record> */;
	public parsed_file $parsed;
	public collected_method $working;
	public string $origin_input;
	public string $called_input;
	public scope $called_scope /** weak<scope> */;

	/** Capture provenance without retaining either owning declaration through a backlink. */
	public function __construct(collected_method $origin, collected_record $called, parsed_file $parsed, collected_method $working)
	{
		$this->origin_reference = weak($origin);
		$this->called_reference = weak($called);
		$this->parsed = $parsed;
		$this->working = $working;
		$this->origin_input = $origin->collection->source_file()->content;
		$this->called_input = $called->collection->source_file()->content;
		$this->called_scope = weak($called->syntax()->member_scope());
	}

	public function origin(): collected_method
	{
		return weakref_get($this->origin_reference);
	}

	public function called_type(): collected_record
	{
		return weakref_get($this->called_reference);
	}
}
