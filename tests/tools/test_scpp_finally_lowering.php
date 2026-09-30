<?php
declare(strict_types=1);
require_once __DIR__ . '/../../generators/php/bin/bootstrap.php';

// The scope repair must not broaden exits that finally lowering cannot preserve.
$cases = [
	'break_out' => 'function f(): void { while (true) { try { break; } finally { echo "F"; } } }',
	'continue_out' => 'function f(): void { while (true) { try { continue; } finally { echo "F"; } } }',
	'finally_return' => 'function f(): int { try { return 1; } finally { return 2; } }',
];
foreach ($cases as $name => $source) {
	$message = '';
	try {
		$result = (new Scpp\S2S\Transpiler(phpProfile: 'strict'))->transpile('/tmp/finally-' . $name . '.phs', false, false, $source);
		$message = implode('; ', $result->errors);
	} catch (Throwable $error) {
		$message = $error->getMessage();
	}
	if (!str_contains($message, 'finally lowering does not support')) {
		throw new RuntimeException($name . ': lost protected-exit rejection: ' . $message);
	}
}
echo "Finally lowering: protected break/continue and finally return remain rejected\n";
