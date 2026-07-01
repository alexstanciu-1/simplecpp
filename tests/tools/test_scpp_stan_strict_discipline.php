<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\Stan\StanWorkspaceSession;

final class ScppStanStrictDisciplineTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_stan_strict_discipline_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertDependencyEditsAffectRootStanFingerprint();

			$project = $this->root . '/app';
			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$text string = fs_get("missing-language-case-file.txt");
	echo strlen($text), "\n";
}

main();
PHS
 . "\n");

			$session = new StanWorkspaceSession();
			$diagnostics = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $diagnostics['warning_count'] ?? null, 'unchecked wrapper boundary should produce one STAN finding');
			$first = $diagnostics['diagnostics'][0] ?? null;
			if (!is_array($first)) {
				throw new RuntimeException('unchecked wrapper boundary diagnostic should be present');
			}
			$this->assertSame('stan.unchecked_wrapper_boundary', $first['code'] ?? null, 'diagnostic code should be stable');
			$this->assertSame(3, $first['line'] ?? null, 'diagnostic should point at the typed boundary line');
			$this->assertContains('Unchecked wrapper result assigned to required `string` local `$text`', (string) ($first['message'] ?? ''), 'diagnostic should describe the unchecked wrapper boundary');
			$this->assertContains('Use `take(...)`, `isset(...)`, or an explicit false/null/error-state check', (string) ($first['message'] ?? ''), 'diagnostic should recommend strict wrapper handling');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$text string = "";
	if (take($text, fs_get("missing-language-case-file.txt"))) {
		echo strlen($text), "\n";
	}
}

main();
PHS
 . "\n");

			$checked = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $checked['warning_count'] ?? null, 'take(...) wrapper handling should stay clean');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$min int8 = -128;
	$max byte = 255;
	$wide uint32 = 4294967295;
	echo (int)$min, " ", (int)$max, " ", (int)$wide, "\n";
}

main();
PHS
 . "\n");

			$validFixedWidthLiterals = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $validFixedWidthLiterals['warning_count'] ?? null, 'in-range fixed-width integer literals should stay clean');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$small int8 = 7;
	$wide int16 = $small;
	$byteValue byte = 8;
	$wider uint16 = $byteValue;
	echo (int)$wide, " ", (int)$wider, "\n";
}

main();
PHS
 . "\n");

			$validFixedWidthWidening = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $validFixedWidthWidening['warning_count'] ?? null, 'same-signed fixed-width integer widening should stay clean');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$small int8 = 7;
	$bad uint16 = $small;
	echo (int)$bad, "\n";
}

main();
PHS
 . "\n");

			$fixedWidthSignedness = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $fixedWidthSignedness['warning_count'] ?? null, 'signed/unsigned fixed-width assignment should produce one STAN finding');
			$signednessDiagnostic = $fixedWidthSignedness['diagnostics'][0] ?? null;
			if (!is_array($signednessDiagnostic)) {
				throw new RuntimeException('fixed-width signedness assignment diagnostic should be present');
			}
			$this->assertSame('stan.fixed_width_integer_assignment', $signednessDiagnostic['code'] ?? null, 'fixed-width assignment diagnostic code should be stable');
			$this->assertContains('cannot assign `int8` to `uint16`', (string) ($signednessDiagnostic['message'] ?? ''), 'fixed-width assignment diagnostic should describe signedness rejection');
			$classifiedFixedWidthSignedness = classify_stan_build_diagnostics([$signednessDiagnostic]);
			$this->assertSame(1, $classifiedFixedWidthSignedness['compile_error_count'] ?? null, 'fixed-width signed/unsigned assignment diagnostics should block pre-build');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$tooSmall int8 = -129;
	$tooLarge uint8 = 256;
	$negative byte = -1;
	echo "bad\n";
}

main();
PHS
 . "\n");

			$fixedWidthRange = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(3, $fixedWidthRange['warning_count'] ?? null, 'out-of-range fixed-width integer literals should produce STAN findings');
			$fixedWidthDiagnostics = $fixedWidthRange['diagnostics'] ?? [];
			if (!is_array($fixedWidthDiagnostics) || count($fixedWidthDiagnostics) !== 3) {
				throw new RuntimeException('fixed-width literal range diagnostics should be present');
			}
			$firstFixedWidthDiagnostic = $fixedWidthDiagnostics[0] ?? null;
			if (!is_array($firstFixedWidthDiagnostic)) {
				throw new RuntimeException('first fixed-width literal range diagnostic should be present');
			}
			$this->assertSame('stan.fixed_width_integer_literal_range', $firstFixedWidthDiagnostic['code'] ?? null, 'fixed-width range diagnostic code should be stable');
			$this->assertSame(3, $firstFixedWidthDiagnostic['line'] ?? null, 'fixed-width range diagnostic should point at the typed boundary line');
			$this->assertContains('Integer literal `-129` is outside the range of fixed-width type `int8`', (string) ($firstFixedWidthDiagnostic['message'] ?? ''), 'fixed-width range diagnostic should describe the int8 failure');
			$classifiedFixedWidth = classify_stan_build_diagnostics($fixedWidthDiagnostics);
			$this->assertSame(3, $classifiedFixedWidth['compile_error_count'] ?? null, 'fixed-width literal range diagnostics should block pre-build');

			$this->writeProject($project, <<<'PHS'
class Box
{
	public uint32 $value = 0;
}

struct BadRow {
	private uint16 $hidden = 0;
	public static uint16 $counter = 0;
	public Box $box;
	public function nope(): void {
		return;
	}
}

echo "bad\n";
PHS
 . "\n");

			$invalidStruct = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(4, $invalidStruct['warning_count'] ?? null, 'invalid struct declaration should produce STAN findings');
			$structDiagnostics = is_array($invalidStruct['diagnostics'] ?? null) ? $invalidStruct['diagnostics'] : [];
			foreach ($structDiagnostics as $diagnostic) {
				if (!is_array($diagnostic)) {
					continue;
				}
				$this->assertSame('stan.struct_contract_mismatch', $diagnostic['code'] ?? null, 'struct contract diagnostic code should be stable');
			}
			$structMessages = implode("\n", array_map(static fn (array $diagnostic): string => (string) ($diagnostic['message'] ?? ''), $structDiagnostics));
			$this->assertContains('Struct `BadRow` cannot declare methods', $structMessages, 'struct method diagnostic should be reported by STAN');
			$this->assertContains('Struct field `BadRow::$hidden` must be public', $structMessages, 'struct private field diagnostic should be reported by STAN');
			$this->assertContains('Struct field `BadRow::$counter` cannot be static', $structMessages, 'struct static field diagnostic should be reported by STAN');
			$this->assertContains('unsupported first-slice field type `Box`', $structMessages, 'struct object field diagnostic should be reported by STAN');
			$classifiedStruct = classify_stan_build_diagnostics($structDiagnostics);
			$this->assertSame(4, $classifiedStruct['compile_error_count'] ?? null, 'struct contract diagnostics should block pre-build');

			$this->writeProject($project, <<<'PHS'
function consume(string $text): void
{
	echo strlen($text), "\n";
}

function main(): void
{
	consume(fs_get("missing-language-case-file.txt"));
}

main();
PHS
 . "\n");

			$wrapperArg = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $wrapperArg['warning_count'] ?? null, 'unchecked wrapper argument should produce one STAN finding');
			$wrapperArgDiagnostic = $wrapperArg['diagnostics'][0] ?? null;
			if (!is_array($wrapperArgDiagnostic)) {
				throw new RuntimeException('unchecked wrapper argument diagnostic should be present');
			}
			$this->assertSame('stan.unchecked_wrapper_argument', $wrapperArgDiagnostic['code'] ?? null, 'wrapper argument diagnostic code should be stable');
			$this->assertSame(8, $wrapperArgDiagnostic['line'] ?? null, 'wrapper argument diagnostic should point at the call line');
			$this->assertContains('Unchecked wrapper result passed to required `string` parameter $text of `consume()`', (string) ($wrapperArgDiagnostic['message'] ?? ''), 'wrapper argument diagnostic should describe the required call boundary');
			$this->assertContains('Use `take(...)`, `isset(...)`, or an explicit false/null/error-state check', (string) ($wrapperArgDiagnostic['message'] ?? ''), 'wrapper argument diagnostic should recommend strict wrapper handling');

			$this->writeProject($project, <<<'PHS'
function consume(string $text): void
{
	echo strlen($text), "\n";
}

function main(): void
{
	$text string = "";
	if (take($text, fs_get("missing-language-case-file.txt"))) {
		consume($text);
	}
}

main();
PHS
 . "\n");

			$checkedWrapperArg = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $checkedWrapperArg['warning_count'] ?? null, 'take(...) wrapper argument handling should stay clean');

			$this->writeProject($project, <<<'PHS'
function load_text(): string
{
	return fs_get("missing-language-case-file.txt");
}

function main(): void
{
	echo load_text(), "\n";
}

main();
PHS
 . "\n");

			$wrapperReturn = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $wrapperReturn['warning_count'] ?? null, 'unchecked wrapper return should produce one STAN finding');
			$wrapperReturnDiagnostic = $wrapperReturn['diagnostics'][0] ?? null;
			if (!is_array($wrapperReturnDiagnostic)) {
				throw new RuntimeException('unchecked wrapper return diagnostic should be present');
			}
			$this->assertSame('stan.unchecked_wrapper_return', $wrapperReturnDiagnostic['code'] ?? null, 'wrapper return diagnostic code should be stable');
			$this->assertSame(3, $wrapperReturnDiagnostic['line'] ?? null, 'wrapper return diagnostic should point at the return line');
			$this->assertContains('Unchecked wrapper result returned from required `string` function `load_text`', (string) ($wrapperReturnDiagnostic['message'] ?? ''), 'wrapper return diagnostic should describe the required return boundary');
			$this->assertContains('Use `take(...)`, `isset(...)`, or an explicit false/null/error-state check', (string) ($wrapperReturnDiagnostic['message'] ?? ''), 'wrapper return diagnostic should recommend strict wrapper handling');

			$this->writeProject($project, <<<'PHS'
function load_text(): string
{
	$text string = "";
	if (take($text, fs_get("missing-language-case-file.txt"))) {
		return $text;
	}
	return "";
}

function main(): void
{
	echo load_text(), "\n";
}

main();
PHS
 . "\n");

			$checkedWrapperReturn = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $checkedWrapperReturn['warning_count'] ?? null, 'take(...) wrapper return handling should stay clean');

			$this->writeProject($project, <<<'PHS'
class Holder
{
	public string $text = "";

	function load(): void
	{
		$this->text = fs_get("missing-language-case-file.txt");
	}
}

$holder = new Holder();
$holder->load();
PHS
 . "\n");

			$wrapperProperty = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $wrapperProperty['warning_count'] ?? null, 'unchecked wrapper property write should produce one STAN finding');
			$wrapperPropertyDiagnostic = $wrapperProperty['diagnostics'][0] ?? null;
			if (!is_array($wrapperPropertyDiagnostic)) {
				throw new RuntimeException('unchecked wrapper property diagnostic should be present');
			}
			$this->assertSame('stan.unchecked_wrapper_property_boundary', $wrapperPropertyDiagnostic['code'] ?? null, 'wrapper property diagnostic code should be stable');
			$this->assertSame(7, $wrapperPropertyDiagnostic['line'] ?? null, 'wrapper property diagnostic should point at the property write line');
			$this->assertContains('Unchecked wrapper result assigned to required `string` property `Holder::$text`', (string) ($wrapperPropertyDiagnostic['message'] ?? ''), 'wrapper property diagnostic should describe the required property boundary');

			$this->writeProject($project, <<<'PHS'
class Holder
{
	public string $text = "";

	function load(): void
	{
		$text string = "";
		if (take($text, fs_get("missing-language-case-file.txt"))) {
			$this->text = $text;
		}
	}
}

$holder = new Holder();
$holder->load();
PHS
 . "\n");

			$checkedWrapperProperty = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $checkedWrapperProperty['warning_count'] ?? null, 'take(...) wrapper property handling should stay clean');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$row = json_decode("{\"name\":\"Ada\"}");
	$name string = $row["name"];
	echo $name, "\n";
}

main();
PHS
 . "\n");

			$dynamic = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $dynamic['warning_count'] ?? null, 'dynamic JSON typed boundary should produce one STAN finding');
			$dynamicDiagnostic = $dynamic['diagnostics'][0] ?? null;
			if (!is_array($dynamicDiagnostic)) {
				throw new RuntimeException('dynamic shape boundary diagnostic should be present');
			}
			$this->assertSame('stan.dynamic_shape_boundary', $dynamicDiagnostic['code'] ?? null, 'dynamic diagnostic code should be stable');
			$this->assertSame(4, $dynamicDiagnostic['line'] ?? null, 'dynamic diagnostic should point at the required typed local');
			$this->assertContains('Dynamic value assigned to required `string` local `$name`', (string) ($dynamicDiagnostic['message'] ?? ''), 'dynamic diagnostic should describe the required boundary');
			$this->assertContains('Guard the field with `isset(...)`', (string) ($dynamicDiagnostic['message'] ?? ''), 'dynamic diagnostic should recommend a shape guard');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$row = json_decode("{\"name\":\"Ada\"}");
	if (isset($row["name"])) {
		$name string = (string) $row["name"];
		echo $name, "\n";
	}
}

main();
PHS
 . "\n");

			$guardedDynamic = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $guardedDynamic['warning_count'] ?? null, 'guarded dynamic JSON extraction with an explicit cast should stay clean');

			$this->writeProject($project, <<<'PHS'
function main(): void
{
	$row = json_decode("{\"name\":\"Ada\"}");
	$name string = $row["name"] ?? "";
	echo $name, "\n";
}

main();
PHS
 . "\n");

			$coalescedDynamic = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $coalescedDynamic['warning_count'] ?? null, 'dynamic JSON extraction with a typed coalesce fallback should stay clean');

			$this->writeProject($project, <<<'PHS'
class Box
{
	public int $value;

	function read(): int
	{
		return $this->value;
	}
}

$box = new Box();
echo $box->read(), "\n";
PHS
 . "\n");

			$uninitializedBuild = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
			$this->assertNotSame(0, $uninitializedBuild['exit_code'], 'uninitialized required property read should stop in STAN pre-build');
			$this->assertContains('STAN pre-build check failed', $uninitializedBuild['stderr'], 'uninitialized property read should be a compile-error bucket');
			$this->assertContains('Property `$this->value` may be read before initialization', $uninitializedBuild['stderr'], 'uninitialized property read should explain the strict property initialization issue');

			$this->writeProject($project, <<<'PHS'
class Box
{
	public int $value;

	function __construct(int $value)
	{
		$this->value = $value;
	}

	function read(): int
	{
		return $this->value;
	}
}

$box = new Box(7);
echo $box->read(), "\n";
PHS
 . "\n");

			$initialized = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $initialized['warning_count'] ?? null, 'constructor-initialized required property read should stay clean');

			$this->writeProject($project, <<<'PHS'
class BaseBox
{
	public int $value = 7;
}

class Box extends BaseBox
{
	function read(): int
	{
		return $this->value;
	}
}

$box = new Box();
echo $box->read(), "\n";
PHS
 . "\n");

			$inheritedDefault = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $inheritedDefault['warning_count'] ?? null, 'inherited default-initialized required property read should stay clean');

			$this->writeProject($project, <<<'PHS'
class BaseBox
{
	public int $value;

	function __construct(int $value)
	{
		$this->value = $value;
	}
}

class Box extends BaseBox
{
	function read(): int
	{
		return $this->value;
	}
}

$box = new Box(7);
echo $box->read(), "\n";
PHS
 . "\n");

			$inheritedConstructor = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $inheritedConstructor['warning_count'] ?? null, 'inherited constructor-initialized required property read should stay clean');

			$this->writeProject($project, <<<'PHS'
class Box
{
	public int $value;

	function __construct(bool $ok)
	{
		if ($ok) {
			$this->value = 7;
		}
	}

	function read(): int
	{
		return $this->value;
	}
}

$box = new Box(false);
echo $box->read(), "\n";
PHS
 . "\n");

			$partialConstructor = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
			$this->assertNotSame(0, $partialConstructor['exit_code'], 'partially initialized constructor property should still stop in STAN pre-build');
			$this->assertContains('Property `$this->value` may be read before initialization', $partialConstructor['stderr'], 'partial constructor initialization should not satisfy later required property reads');

			$this->writeProject($project, <<<'PHS'
function choose(bool $ok): int
{
	if ($ok) {
		return 1;
	}
}

echo choose(false), "\n";
PHS
 . "\n");

			$partialReturnPath = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $partialReturnPath['warning_count'] ?? null, 'partial return path should produce one STAN finding');
			$partialReturnDiagnostic = $partialReturnPath['diagnostics'][0] ?? null;
			if (!is_array($partialReturnDiagnostic)) {
				throw new RuntimeException('partial return path diagnostic should be present');
			}
			$this->assertSame('stan.missing_return', $partialReturnDiagnostic['code'] ?? null, 'partial return path diagnostic code should be stable');
			$this->assertContains('may exit without returning a value', (string) ($partialReturnDiagnostic['message'] ?? ''), 'partial return path diagnostic should explain the missing return');

			$this->writeProject($project, <<<'PHS'
function choose(bool $ok): int
{
	if ($ok) {
		return 1;
	} else {
		return 2;
	}
}

echo choose(false), "\n";
PHS
 . "\n");

			$exhaustiveReturnPath = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $exhaustiveReturnPath['warning_count'] ?? null, 'exhaustive if/else return path should stay clean');

			$this->writeProject($project, <<<'PHS'
interface Renderable
{
	function render(): string;
}

class Label implements Renderable
{
}

$label = new Label();
PHS
 . "\n");

			$missingInterfaceMethod = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $missingInterfaceMethod['warning_count'] ?? null, 'missing interface method should produce one STAN finding');
			$missingInterfaceDiagnostic = $missingInterfaceMethod['diagnostics'][0] ?? null;
			if (!is_array($missingInterfaceDiagnostic)) {
				throw new RuntimeException('missing interface method diagnostic should be present');
			}
			$this->assertSame('stan.interface_contract_mismatch', $missingInterfaceDiagnostic['code'] ?? null, 'interface contract diagnostic code should be stable');
			$this->assertContains('Class `Label` implements interface `Renderable` but is missing method `render()`', (string) ($missingInterfaceDiagnostic['message'] ?? ''), 'interface contract diagnostic should describe the missing method');

			$missingInterfaceBuild = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
			$this->assertNotSame(0, $missingInterfaceBuild['exit_code'], 'missing interface method should stop in STAN pre-build');
			$this->assertContains('STAN pre-build check failed', $missingInterfaceBuild['stderr'], 'missing interface method should be a compile-error bucket');
			$this->assertContains('Class `Label` implements interface `Renderable` but is missing method `render()`', $missingInterfaceBuild['stderr'], 'missing interface method build failure should explain the contract mismatch');

			$this->writeProject($project, <<<'PHS'
interface Renderable
{
	function render(): string;
}

class Label implements Renderable
{
	function render(): string
	{
		return "ok";
	}
}

$label = new Label();
echo $label->render(), "\n";
PHS
 . "\n");

			$implementedInterface = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $implementedInterface['warning_count'] ?? null, 'implemented interface method should stay clean');

			$this->writeProject($project, <<<'PHS'
interface Renderable
{
	function render(): string;
}

class Label implements Renderable
{
	function render(): int
	{
		return 7;
	}
}

$label = new Label();
echo $label->render(), "\n";
PHS
 . "\n");

			$interfaceReturnMismatch = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $interfaceReturnMismatch['warning_count'] ?? null, 'interface return mismatch should produce one STAN finding');
			$interfaceReturnDiagnostic = $interfaceReturnMismatch['diagnostics'][0] ?? null;
			if (!is_array($interfaceReturnDiagnostic)) {
				throw new RuntimeException('interface return mismatch diagnostic should be present');
			}
			$this->assertSame('stan.interface_contract_mismatch', $interfaceReturnDiagnostic['code'] ?? null, 'interface return mismatch diagnostic code should be stable');
			$this->assertContains('expected return `string`, got `int`', (string) ($interfaceReturnDiagnostic['message'] ?? ''), 'interface return mismatch diagnostic should describe expected and actual return types');

			$this->writeProject($project, <<<'PHS'
interface Formatter
{
	function format(string $value): string;
}

class Label implements Formatter
{
	function format(): string
	{
		return "";
	}
}

$label = new Label();
echo $label->format(), "\n";
PHS
 . "\n");

			$interfaceParamCountMismatch = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $interfaceParamCountMismatch['warning_count'] ?? null, 'interface parameter count mismatch should produce one STAN finding');
			$interfaceParamCountDiagnostic = $interfaceParamCountMismatch['diagnostics'][0] ?? null;
			if (!is_array($interfaceParamCountDiagnostic)) {
				throw new RuntimeException('interface parameter count mismatch diagnostic should be present');
			}
			$this->assertSame('stan.interface_contract_mismatch', $interfaceParamCountDiagnostic['code'] ?? null, 'interface parameter count diagnostic code should be stable');
			$this->assertContains('expected 1 parameter(s), got 0', (string) ($interfaceParamCountDiagnostic['message'] ?? ''), 'interface parameter count diagnostic should describe expected and actual parameter counts');

			$this->writeProject($project, <<<'PHS'
interface Formatter
{
	function format(string $value): string;
}

class Label implements Formatter
{
	function format(int $value): string
	{
		return "ok";
	}
}

$label = new Label();
echo $label->format(7), "\n";
PHS
 . "\n");

			$interfaceParamTypeMismatch = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $interfaceParamTypeMismatch['warning_count'] ?? null, 'interface parameter type mismatch should produce one STAN finding');
			$interfaceParamTypeDiagnostic = $interfaceParamTypeMismatch['diagnostics'][0] ?? null;
			if (!is_array($interfaceParamTypeDiagnostic)) {
				throw new RuntimeException('interface parameter type mismatch diagnostic should be present');
			}
			$this->assertSame('stan.interface_contract_mismatch', $interfaceParamTypeDiagnostic['code'] ?? null, 'interface parameter type diagnostic code should be stable');
			$this->assertContains('parameter $value does not match interface `Formatter`: expected `string`, got `int`', (string) ($interfaceParamTypeDiagnostic['message'] ?? ''), 'interface parameter type diagnostic should describe expected and actual parameter types');

			$this->writeProject($project, <<<'PHS'
interface Renderable
{
	function render(): string;
}

class Label implements Renderable
{
	protected function render(): string
	{
		return "ok";
	}
}

$label = new Label();
PHS
 . "\n");

			$interfaceVisibilityMismatch = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $interfaceVisibilityMismatch['warning_count'] ?? null, 'interface method visibility mismatch should produce one STAN finding');
			$interfaceVisibilityDiagnostic = $interfaceVisibilityMismatch['diagnostics'][0] ?? null;
			if (!is_array($interfaceVisibilityDiagnostic)) {
				throw new RuntimeException('interface method visibility mismatch diagnostic should be present');
			}
			$this->assertSame('stan.interface_contract_mismatch', $interfaceVisibilityDiagnostic['code'] ?? null, 'interface method visibility diagnostic code should be stable');
			$this->assertContains('Interface methods must be implemented as public', (string) ($interfaceVisibilityDiagnostic['message'] ?? ''), 'interface method visibility diagnostic should require public implementation');

			$this->writeProject($project, <<<'PHS'
interface Node
{
	function id(): int;
}

interface Renderable extends Node
{
	function render(): string;
}

class Label implements Renderable
{
	function render(): string
	{
		return "ok";
	}
}

$label = new Label();
PHS
 . "\n");

			$inheritedInterfaceMethod = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $inheritedInterfaceMethod['warning_count'] ?? null, 'inherited interface method should be required');
			$inheritedInterfaceDiagnostic = $inheritedInterfaceMethod['diagnostics'][0] ?? null;
			if (!is_array($inheritedInterfaceDiagnostic)) {
				throw new RuntimeException('inherited interface method diagnostic should be present');
			}
			$this->assertSame('stan.interface_contract_mismatch', $inheritedInterfaceDiagnostic['code'] ?? null, 'inherited interface diagnostic code should be stable');
			$this->assertContains('missing method `id()`', (string) ($inheritedInterfaceDiagnostic['message'] ?? ''), 'inherited interface diagnostic should require parent interface methods');

			$this->writeProject($project, <<<'PHS'
interface LeftId
{
	function id(): int;
}

interface RightId
{
	function id(): string;
}

interface Conflicted extends LeftId, RightId
{
}

class Label implements Conflicted
{
	function id(): int
	{
		return 7;
	}
}

$label = new Label();
PHS
 . "\n");

			$conflictedInterface = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $conflictedInterface['warning_count'] ?? null, 'conflicting inherited interface method should produce one STAN finding');
			$conflictedInterfaceDiagnostic = $conflictedInterface['diagnostics'][0] ?? null;
			if (!is_array($conflictedInterfaceDiagnostic)) {
				throw new RuntimeException('conflicting inherited interface diagnostic should be present');
			}
			$this->assertSame('stan.interface_contract_mismatch', $conflictedInterfaceDiagnostic['code'] ?? null, 'conflicting inherited interface diagnostic code should be stable');
			$this->assertContains('inherits conflicting method contract `id()`', (string) ($conflictedInterfaceDiagnostic['message'] ?? ''), 'conflicting inherited interface diagnostic should explain the duplicate contract');

			$this->writeProject($project, <<<'PHS'
abstract class AbstractRunner
{
	abstract function run(): int;
}

class Runner extends AbstractRunner
{
}

$runner = new Runner();
PHS
 . "\n");

			$missingAbstractMethod = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(1, $missingAbstractMethod['warning_count'] ?? null, 'missing abstract method implementation should produce one STAN finding');
			$missingAbstractDiagnostic = $missingAbstractMethod['diagnostics'][0] ?? null;
			if (!is_array($missingAbstractDiagnostic)) {
				throw new RuntimeException('missing abstract method diagnostic should be present');
			}
			$this->assertSame('stan.abstract_contract_mismatch', $missingAbstractDiagnostic['code'] ?? null, 'abstract contract diagnostic code should be stable');
			$this->assertContains('extends abstract class `AbstractRunner` but is missing method `run()`', (string) ($missingAbstractDiagnostic['message'] ?? ''), 'abstract contract diagnostic should explain the missing method');

			$this->writeProject($project, <<<'PHS'
abstract class AbstractRunner
{
	abstract function run(): int;
}

class Runner extends AbstractRunner
{
	function run(): int
	{
		return 7;
	}
}

$runner = new Runner();
echo $runner->run(), "\n";
PHS
 . "\n");

			$implementedAbstractMethod = $session->runDiagnostics($project, $project . '/prism.json');
			$this->assertSame(0, $implementedAbstractMethod['warning_count'] ?? null, 'implemented abstract method should stay clean');

			echo "PASS: scpp stan strict discipline\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertDependencyEditsAffectRootStanFingerprint(): void
	{
		$dep = $this->root . '/dep';
		$app = $this->root . '/dep_app';
		$this->writeProject($dep, <<<'PHS'
/** @lib-export */
function dep_value(): int
{
	return 1;
}
PHS
 . "\n");
		$this->writeProject($app, <<<'PHS'
function main(): void
{
	echo dep_value(), "\n";
}

main();
PHS
 . "\n", ['../dep']);

		$before = compute_stan_source_fingerprint($app, $app . '/prism.json');
		$this->write($dep . '/main.phs', <<<'PHS'
/** @lib-export */
function dep_value(): int
{
	return 2;
}
PHS
 . "\n");
		$after = compute_stan_source_fingerprint($app, $app . '/prism.json');
		if ($before === $after) {
			throw new RuntimeException('dependency-only source edits should change the root STAN source fingerprint');
		}
	}

	/** @param list<string> $dependencies */
	private function writeProject(string $project, string $mainSource, array $dependencies = []): void
	{
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'config_version' => 1,
			'project_name' => 'stan-strict-discipline',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'dependencies' => $dependencies,
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', $mainSource);
	}

	private function write(string $path, string $contents): void
	{
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write ' . $path);
		}
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true)) {
			throw new RuntimeException('Failed to create ' . $path);
		}
	}

	/** @return array{exit_code:int,stdout:string,stderr:string} */
	private function runCommand(array $command, string $cwd, int $timeoutSeconds): array
	{
		$descriptor = [
			0 => ['file', '/dev/null', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment([
			'SCPP_CXX_LAUNCHER' => ' ',
		]));
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start command: ' . implode(' ', $command));
		}
		$stdout = '';
		$stderr = '';
		$started = microtime(true);
		$observedExitCode = null;
		foreach ([1, 2] as $index) {
			stream_set_blocking($pipes[$index], false);
		}
		while (true) {
			$status = proc_get_status($process);
			$stdout .= (string) stream_get_contents($pipes[1]);
			$stderr .= (string) stream_get_contents($pipes[2]);
			if (($status['running'] ?? false) !== true) {
				$exitCode = $status['exitcode'] ?? null;
				$observedExitCode = is_int($exitCode) ? $exitCode : null;
				break;
			}
			if ((microtime(true) - $started) > $timeoutSeconds) {
				proc_terminate($process);
				throw new RuntimeException('Timed out after ' . $timeoutSeconds . 's: ' . implode(' ', $command));
			}
			usleep(100000);
		}
		$stdout .= (string) stream_get_contents($pipes[1]);
		$stderr .= (string) stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);
		return [
			'exit_code' => $observedExitCode ?? (is_int($exitCode) ? $exitCode : 1),
			'stdout' => $stdout,
			'stderr' => $stderr,
		];
	}

	private function removeTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}
		$items = scandir($path);
		if ($items === false) {
			return;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$child = $path . '/' . $item;
			if (is_dir($child) && !is_link($child)) {
				$this->removeTree($child);
				continue;
			}
			unlink($child);
		}
		rmdir($path);
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '` in: ' . $haystack);
		}
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	private function assertNotSame(mixed $unexpected, mixed $actual, string $message): void
	{
		if ($unexpected === $actual) {
			throw new RuntimeException($message . ' did not expect ' . var_export($actual, true));
		}
	}
}

exit((new ScppStanStrictDisciplineTest())->run());
