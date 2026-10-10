<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';

foreach (['===' => operator_operation::string_identical, '!==' => operator_operation::string_not_identical] as $symbol => $operation) {
	$syntax = operator_test_source('$a = "x"; $b = $a ' . $symbol . ' "x"; return 0;');
	$decision = $syntax->root->single_body()->body->statements[1]->expression->value->require_binary_preparation()->decision;
	if ($decision->operation !== $operation || !$decision->result_type->matches(Language_Types::boolean(Model::$language_scope))) {
		throw new \LogicException('String identity lost its operation or boolean result');
	}
	foreach ($decision->operands as $conversion) {
		if ($conversion->operation !== conversion_operation::identity) {
			throw new \LogicException('String identity introduced coercion');
		}
	}
}
foreach (['"1" === 1', '1 !== "1"', '"a" < "b"', '"a" == "a"'] as $expression) {
	$failed = false;
	try { operator_test_source('$a = ' . $expression . ';'); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('String comparison scope expanded'); }
}

operator_test_source(<<<'PHS'
class Box { public string $code = "old"; }
function replace(Box $box): string { $box->code = "new"; return "old"; }
function step(int &$n, int $digit): string { $n = $n * 10 + $digit; return "same"; }
function same(string $a, string $b): bool { return $a === $b; }
function different(string $a, string $b): bool { return $a !== $b; }
$box = new Box();
if (!($box->code === replace($box)) || $box->code !== "new") { return 1; }
$n = 0;
if (!(step($n, 1) === step($n, 2)) || $n !== 12) { return 2; }
if (!("" === "") || !("01" !== "1") || !("A" !== "a")) { return 3; }
if (!("é" === "é") || !("é" !== "é")) { return 4; }
return 0;
PHS);
$cpp = CPP_Probe::text(Model::$cpp_files);
$compiler = new Compiler(); $compiler->prepare(); $compiler->cpp();
if ($cpp !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('String identity no-op changed output'); }
file_put_contents($argv[1] . '/values.cpp', $cpp);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', $cpp);
$cpp .= <<<'CPP'

int main() {
    scpp::string_t a{std::string("a\0b", 3)}, b{std::string("a\0b", 3)}, c{std::string("a\0c", 3)};
    if (!function_same(a, b).native_value()) return 1;
    if (!function_different(a, c).native_value()) return 2;
    if (function_same(a, scpp::string_t("a")).native_value()) return 3;
    return 0;
}
CPP;
file_put_contents($argv[1] . '/binary.cpp', $cpp);
echo "String identity checks passed\n";
