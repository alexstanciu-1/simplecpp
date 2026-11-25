	/** The two bounded input buffers retain evaluated entries until the runtime factory runs. */
	private readonly initialization_transport $list_input;
	private readonly initialization_transport $tuple_input;
	private readonly initialization_transport $keyed_input;

	protected function __construct(string $name)
	{
		parent::__construct($name);
		$type = $this->cpp_type();
		$this->list_input = new initialization_transport($type, $type . '::list_input', $type . '::from_list', 32);
		$this->tuple_input = new initialization_transport($type, $type . '::list_input', $type . '::from_tuple', 32);
		$this->keyed_input = new initialization_transport($type . '::keyed_entry', $type . '::keyed_input', $type . '::from_entries', 32);
	}

	public function tuple_initialization(): initialization_transport { return $this->tuple_input; }
	public function list_initialization(): initialization_transport { return $this->list_input; }
	public function keyed_initialization(): initialization_transport { return $this->keyed_input; }

	public function lower_cpp_key(ast_expression $syntax, string $key): string
	{
		$normalized = 'scpp::boxed_key(' . $key . ')';
		return $syntax->type_def() === type_int64::instance() ? 'scpp::boxed_text_key{' . $normalized . ', {}}' : $normalized;
	}

	public function lower_cpp_sequence_append(ast_expression $element, int $conversion /** uint16 */, string $receiver, string $value, ?Cpp_Adapters $adapters = null): string
	{
		throw new \LogicException('Boxed literals use ordered input');
	}
