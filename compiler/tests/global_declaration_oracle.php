<?php

declare(strict_types=1);

require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend};

$dialect = flavor::from($argv[1]);
$code = stream_get_contents(STDIN);
$file = (new Source_Frontend())->parse(new source_file('globals.' . $dialect->value, $code, $dialect));
$tokens = [];
foreach ($file->tokens->tokens as $index => $token) {
	$tokens[] = [$token->kind->name, $file->tokens->text_at($index)];
}
$globals = [];
foreach ($file->globals() as $global) {
	$type = $global->type_reference;
	$initializer = $global->initializer;
	$spelling = '';
	for ($index = $initializer->start; $index < $initializer->end; $index++) {
		$spelling .= $file->tokens->text_at($index);
	}
	$globals[] = [$global->short_name, $global->start, $global->end,
		$type === null ? null : [$type->spelling(), $type->start],
		$spelling, $initializer->start, $initializer->end];
}
echo json_encode(['tokens' => $tokens, 'globals' => $globals], JSON_THROW_ON_ERROR);
