
	private ?runtime_function $filled_binding = null;

	public function filled_function(): runtime_function
	{
		return $this->filled_binding ??= new runtime_function(@source@, [@count@::instance(), $this->write_type()],
			$this, $this->cpp_type() . '::@member@', $this->cpp_headers()[0], const_reference_mask: @mask@, exact_arguments: true);
	}
