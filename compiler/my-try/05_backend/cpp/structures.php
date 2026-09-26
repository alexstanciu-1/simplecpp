<?php

/* C++ representation data stays separate from canonical language type identity. */
namespace scpp\compiler;

enum cpp_literal_kind {
	case signed_integer;
	case boolean;
	case floating;
}

final class cpp_type {
	public string $spelling;
	public string $header;
	public cpp_literal_kind $literal;
}

/** Final bytes own no references into preparation or source syntax. */
final class cpp_module {
	public string $file_name;
	public string $text;
}

/** One emission invocation; syntax and prepared facts never retain it. */
final class cpp_generation_context {
	public array $headers /** hash<bool> */ = [];
}
