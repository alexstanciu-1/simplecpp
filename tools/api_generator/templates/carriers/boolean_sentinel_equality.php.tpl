	protected function lower_cpp_peer_equality(string $left, string $right, string $payloads_equal): string
	{
		return "(($left).@payload@() ? (($right).@payload@() && $payloads_equal)"
			. " : (!($right).@payload@() && ($left).@true@() == ($right).@true@()))";
	}
