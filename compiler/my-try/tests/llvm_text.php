<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function text_check(bool $ok): void
{
	if (!$ok) {
		throw new \LogicException('LLVM text parity failed');
	}
}
$all = '';
for ($index = 0; $index < 256; $index++) {
	$byte = chr($index);
	$expected = ctype_alnum($byte) && $index < 128 ? $byte : ($byte === '_' ? '__' : sprintf('_x%02X_', $index));
	text_check(LLVM_Names::encode($byte) === $expected);
	text_check(LLVM_Names::decode($expected) === $byte);
	$all .= $byte;
}
text_check(LLVM_Names::decode(LLVM_Names::encode($all)) === $all);
foreach (['', '_Gf1d2', 'hello_世界', "a\0b"] as $name) {
	$encoded = LLVM_Names::encode($name);
	text_check(LLVM_Names::decode($encoded) === $name);
	text_check(LLVM_Names::decode($encoded . '_Gf012d034') === $name);
}
foreach (['_', '_x', '_x0', '_x00', '_x0G_', '_xff_', '_Gf', '_Gfd1', '_Gf1d', '_Gf1d2x', "_Gf1d2\n", '_Q'] as $encoded)
{
	$rejected = false;
	try {
		LLVM_Names::decode($encoded);
	}
	catch (\InvalidArgumentException $error) {
		$rejected = true;
	}
	text_check($rejected);
}
$writer = new LLVM_Writer();
$module = new llvm_module();
text_check($writer->text($module) === "\n");
$module->types = ['type', ''];
$module->globals = ['global'];
$module->external_functions = ['external'];
$module->metadata = ['', 'metadata'];
$function = new llvm_function();
$function->name = 'f';
$function->return_type = 'i32';
foreach (['%a', '%b'] as $name) {
	$parameter = new llvm_operand();
	$parameter->type = 'i32';
	$parameter->text = $name;
	$function->parameters[] = $parameter;
}
$block = new llvm_block();
$block->label = 'entry';
$block->instructions = ['ret i32 7'];
$block->terminated = true;
$function->blocks[] = $block;
$module->functions[] = $function;
text_check($writer->text($module) === "type\n\n\n\nglobal\n\nexternal\n\ndefine i32 @f(i32 %a, i32 %b) {\nentry:\n    ret i32 7\n}\n\n\n\nmetadata\n");
$block->terminated = false;
$rejected = false;
try {
	$writer->text($module);
}
catch (\LogicException $error) {
	$rejected = true;
}
text_check($rejected);
foreach (['0' => '0', '000' => '0', '00042' => '42', '2147483647' => '2147483647'] as $input => $expected) {
	text_check(LLVM_Text::decimal((string) $input) === $expected);
}
foreach (['2147483646' => false, '2147483647' => false, '2147483648' => true, '999999999999999999999' => true, '9' => false] as $input => $expected) {
	text_check(LLVM_Text::decimal_exceeds((string) $input, '2147483647') === $expected);
}
text_check(LLVM_Text::join([], ',') === '');
text_check(LLVM_Text::join(['', 'x', ''], ',') === ',x,');
foreach (['/a/name.phs' => 'name.ll', '/a/multi.part.phs' => 'multi.part.ll', '/a/.phs' => '.ll', '/a/plain' => 'plain.ll'] as $path => $expected) {
	text_check(LLVM_Text::output_name($path) === $expected);
}
echo "LLVM text: all byte values, invalid encodings, suffixes, section order, separators and terminator rejection passed\n";
