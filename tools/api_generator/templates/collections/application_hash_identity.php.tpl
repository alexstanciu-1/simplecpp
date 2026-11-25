	use concrete_async_task;

	private readonly string $native_name;

	public function __construct(type_definition $value, type_definition $key)
	{
		parent::__construct(@family@ . '<' . $value->name . ',' . $key->name . '>', $value, $key);
@presence@
		$this->shared_lookup = $value instanceof shared_record_type_definition && $value->required
			? new runtime_function('hash_get_or_null', [$this, $key], $value->payload->shared,
				'get_or_null', $this->cpp_headers()[0], const_reference_mask: 3, receiver_method: true)
			: null;
		$this->native_name = 'scpp_hash_' . hash('sha256', $value::class . ':' . $value->name . ':' . $key::class . ':' . $key->name);
	}
