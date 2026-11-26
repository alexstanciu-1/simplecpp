<?php

declare(strict_types=1);

use scpp\compiler\{Tokenizer, flavor, token_kind};

require_once __DIR__ . '/../../boot.php';

// Generate the bounded scanner's native enum and dispatch from the bootstrap inventory.
echo "enum Token_Kind: int {\n    case Unrecognized = -1;\n    case Rejected = -2;\n    case End = 0;\n";
foreach (token_kind::cases() as $kind) echo "    case T_$kind->name = $kind->value;\n";
echo "}\nenum Scan_Flavor: string {\n";
foreach (flavor::cases() as $mode) echo "    case $mode->name = \"$mode->value\";\n";
echo "}\n";

$branches = [];
foreach (flavor::cases() as $mode) {
	foreach (Tokenizer::punctuation_kinds($mode) as $kind) {
		$word = $kind->label();
		$branches[$word[0]][$word][$mode->name] = $kind;
	}
}
echo 'function punctuation_at(string &$text, int $start, byte $lead, Scan_Flavor $mode, int &$width): Token_Kind {', "\n";
foreach ($branches as $lead => $words) {
	echo '    if ($lead == ', ord($lead), ") {\n";
	// A flavor-specific longer spelling may first appear after a shorter common spelling.
	uksort($words, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
	foreach ($words as $word => $modes) {
		$kind = reset($modes);
		$width = strlen($word);
		$conditions = [];
		if (count($modes) !== count(flavor::cases())) {
			$conditions[] = '(' . implode(' || ', array_map(static fn($name) => '$mode === Scan_Flavor::' . $name, array_keys($modes))) . ')';
		}
		if ($width > 1) $conditions[] = 'string_byte_slice_equals($text, $start, ' . $width . ', ' . json_encode($word, JSON_UNESCAPED_SLASHES) . ')';
		$indent = '        ';
		if ($conditions !== []) {
			echo $indent, 'if (', implode(' && ', $conditions), ") {\n";
			$indent .= '    ';
		}
		if (isset($modes['python']) && !Tokenizer::python_accepts_punctuation($word)) {
			echo $indent, 'if ($mode === Scan_Flavor::python) return Token_Kind::Rejected;', "\n";
		}
		echo $indent, '$width = ', $width, ";\n", $indent, "return Token_Kind::T_$kind->name;\n";
		if ($conditions !== []) echo "        }\n";
	}
	echo "    }\n";
}
echo "    return Token_Kind::Unrecognized;\n}\n";
