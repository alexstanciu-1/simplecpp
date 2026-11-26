<?php

declare(strict_types=1);

use scpp\compiler\{Module_Loader, Program_Frontend, entry_target, application_vector_type, cpp_concrete_type};

require_once __DIR__ . '/../boot.php';

// Extract only settled inputs. Header bytes and manifest contents are checked separately.
$session = (new Module_Loader())->load($argv[1]);
$program = (new Program_Frontend())->prepare($session, new entry_target(31));
$canonical = null;
$demands = [];
foreach ($program->files as $file) {
	foreach ($file->functions() as $fn) {
		foreach ($fn->parameters as $parameter) {
			$type = $parameter->type_def();
			if (!($type instanceof application_vector_type)) continue;
			if ($canonical !== null && $canonical !== $type) throw new LogicException('Split vector identity');
			$canonical = $type;
			$element = new cpp_concrete_type($type->concrete_types()['e'], $argv[2]);
			$name = $type->cpp_type();
			$headers = array_unique([...$type->cpp_headers(), ...$element->headers()]);
			$demands[$fn->name()] = [$name, $element->cppType($argv[2]), hash('sha256', $argv[2] . "\0" . $name), implode("\n", $headers)];
		}
	}
}
echo json_encode((object)$demands, JSON_THROW_ON_ERROR);
