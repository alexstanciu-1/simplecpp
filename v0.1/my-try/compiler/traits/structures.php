<?php
namespace scpp\compiler;

/** Independent working syntax paired with its authored provenance. */
final class trait_member_instance
{
	public function __construct(
		/** @reference.source authored trait collection */
		public collected_name $origin,
		/** @reference.source trait_instance.collection */
		public collected_name $working) {}
}

/** A consumer owns syntax, collection, scopes and a private token snapshot as one unit. */
final class trait_instance
{
	public collected_trait $origin;
	/** @reference.weak */
	private collected_record $consumer_reference /** weak<collected_record> */;
	public parsed_file $parsed;
	/** Private collection owner: not published as another source file. */
	public collected_file $collection;
	public Storage $members /** Storage<trait_member_instance> */;
	public string $origin_input;
	public string $consumer_input;

	public function __construct(collected_trait $origin, collected_record $consumer, parsed_file $parsed)
	{
		$this->origin = $origin;
		$this->consumer_reference = weak($consumer);
		$this->parsed = $parsed;
		$this->collection = $parsed->collection;
		$this->members = new Storage /** Storage<trait_member_instance> */();
		$this->origin_input = $origin->collection->source_file()->content;
		$this->consumer_input = $consumer->collection->source_file()->content;
	}

	public function consumer(): collected_record { return weakref_get($this->consumer_reference); }
}
