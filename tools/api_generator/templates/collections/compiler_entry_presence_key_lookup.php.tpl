
/** Probe the key without insertion, inspecting optional owners in storage. */
trait entry_presence_key_lookup
{
	/** A keyed probe of a nullable shared value inspects the stored owner after lookup. */
	public function probes_stored_owner(): bool
	{
		$element = $this->write_type();
		return $element instanceof shared_record_type_definition && !$element->required;
	}

	/** Probe one normalized key without insertion. Optional shared owners publish a fused probe. */
	public function entry_presence_method(): string
	{
		return $this->probes_stored_owner() ? '@owner_probe@' : $this->presence_function()->symbol;
	}

	public function lower_cpp_entry_presence(string $receiver, string $key): string
	{
		$element = $this->write_type();
		$present = $receiver . '.' . $this->entry_presence_method() . '(' . $key . ')';
		if ($element instanceof presence_test_type) $present .= ' && ' . $element->lower_cpp_presence($receiver . '.@read@(' . $key . ')');
		return $present;
	}
}
