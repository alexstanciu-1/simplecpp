<?php
declare(strict_types=1);

// Project-wide source bindings. null means use ordinary PHP without an import.
return [
	'sequence_map' => ['php' => 'scpp\\sequence_map', 'target' => 'sequence_map', 'arity' => 2],
	'sequence_filter' => ['php' => 'scpp\\sequence_filter', 'target' => 'sequence_filter', 'arity' => 2],
	'keyed_map' => ['php' => 'scpp\\keyed_map', 'target' => 'keyed_map', 'arity' => 2],
	'keyed_filter' => ['php' => 'scpp\\keyed_filter', 'target' => 'keyed_filter', 'arity' => 2],

	'string_byte_len' => ['php' => 'scpp\\string_byte_len', 'target' => 'string_byte_len', 'arity' => 1],
	'string_byte_starts_with' => ['php' => 'scpp\\string_byte_starts_with', 'target' => 'str_starts_with', 'arity' => 2],
	'string_byte_ends_with' => ['php' => 'scpp\\string_byte_ends_with', 'target' => 'str_ends_with', 'arity' => 2],
	'string_utf8_is_valid' => ['php' => 'scpp\\string_utf8_is_valid', 'target' => 'scpp_portability_utf8_valid', 'arity' => 1],
	'string_codepoint_at' => ['php' => 'scpp\\string_codepoint_at', 'target' => 'scpp_portability_codepoint_at', 'arity' => 2],
	'substr' => ['php' => 'scpp\\compat\\substr', 'target' => 'scpp_portability_substr', 'targets' => [2 => 'scpp_portability_substr_to_end'], 'arity' => [2, 3]],
	'strpos' => ['php' => 'scpp\\compat\\strpos', 'target' => 'scpp_portability_strpos', 'arity' => [2, 3]],
	'strrpos' => ['php' => 'scpp\\compat\\strrpos', 'target' => 'scpp_portability_strrpos', 'arity' => [2, 3]],
	'same_exception' => ['php' => 'scpp\\same_exception', 'target' => 'scpp_portability_same_exception', 'arity' => 2],
	'string_byte_at' => ['php' => 'scpp\\string_byte_at', 'target' => 'string_byte_at', 'arity' => 2],
	'count' => ['php' => null, 'target' => 'count', 'arity' => 1],
	'take_nullable' => ['php' => 'scpp\\take_nullable', 'target' => 'take', 'arity' => 2],
	'take_false' => ['php' => 'scpp\\take_false', 'target' => 'take', 'arity' => 2],
	'take_bool' => ['php' => 'scpp\\take_bool', 'target' => 'take', 'arity' => 3],
	'is_bool' => ['php' => null, 'target' => 'is_bool', 'arity' => 1],
	'str_starts_with' => ['php' => 'scpp\\compat\\str_starts_with', 'target' => 'scpp_portability_str_starts_with', 'arity' => 2],
	'str_ends_with' => ['php' => 'scpp\\compat\\str_ends_with', 'target' => 'scpp_portability_str_ends_with', 'arity' => 2],
	'strlen' => ['php' => 'scpp\\compat\\strlen', 'target' => 'scpp_portability_strlen', 'arity' => 1],
	'string_byte_slice' => ['php' => 'scpp\\string_byte_slice', 'target' => 'string_byte_slice', 'arity' => 3],
];
