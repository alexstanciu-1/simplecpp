<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

// Host fixture: exercise native enforcement independently of semantic type admission.
$artifacts = new Storage();
$artifacts->append(new cpp_artifact('entry', cpp_artifact_kind::translation_unit, $argv[3] ?? 'entry.cpp',
	"#include \"provider.hpp\"\nint main() { return provider_value(); }\n"));
$artifacts->append(new cpp_artifact('support', cpp_artifact_kind::translation_unit, 'specializations/support.cpp',
	"template<class T> T identity(T value) { return value; }\nint support() { return identity(0); }\n"));
$publication = new Output_Publication($argv[1]);
try {
	$publication->publish($artifacts, new runtime_cpp_build_spec(), $argv[2] === 'strict');
} finally { $publication->close(); }
