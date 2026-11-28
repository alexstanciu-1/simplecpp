
/** Check bounds before inspecting the selected value. */
trait entry_presence_checked_slot
{
	public function lower_cpp_entry_presence(string $receiver, string $key): string
	{
		$element = $this->write_type();
		$slot = $this->lower_cpp_slot($receiver, $key);
		if ($element instanceof presence_test_type) return $element->lower_cpp_presence($slot);
		if ($element instanceof shared_record_type_definition && !$element->required) return $element->lower_cpp_condition($slot);
		// Even an always-present element must pass the bounds check, without copying its payload.
		return '((void)' . $slot . ', true)';
	}
}
