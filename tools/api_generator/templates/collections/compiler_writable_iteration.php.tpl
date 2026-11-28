
trait @trait@
{
	public function lower_cpp_writable_iteration_open(array &$lines, string $receiver, int $loop_start): void
	{
		$index = 'iteration_index_' . $loop_start;
@open@
	}

	public function lower_cpp_writable_iteration_close(): string
	{
		return '@close@';
	}

	public function lower_cpp_iteration_read(string $receiver, int $loop_start): string
	{
		return $this->@read@($receiver, "@selection@");
	}

	public function lower_cpp_iteration_replace(string $receiver, int $loop_start, string $value): string
	{
		return $this->lower_cpp_write($receiver, 'iteration_index_' . $loop_start, $value);
	}

	public function lower_cpp_iteration_reference(string $receiver, int $loop_start): string
	{
		return "$receiver.iteration_reference(@selection@)";
	}
}
