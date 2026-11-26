	protected function lower_cpp_peer_equality(string $left, string $right, string $payloads_equal, binary_operator $comparator): string
	{
		if ($comparator === binary_operator::equal) {
			return parent::lower_cpp_peer_equality($left, $right, $payloads_equal, $comparator);
		}
		$left_present = $this->lower_cpp_presence($left);
		$right_present = $this->lower_cpp_presence($right);
		return "($left_present ? ($right_present && $payloads_equal) : ($left).same_error_identity($right))";
	}
