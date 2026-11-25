	public function runtime_method(string $name): runtime_function|runtime_overloads|null
	{
		return match ($name) {
@bindings@
			default => null,
		};
	}
