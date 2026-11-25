<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';

use Scpp\S2S\Analysis\FrontEndSymbolExtractor;
use Scpp\S2S\Stan\StanExpressionTypeResolver;

/** Exercise extracted source facts, rather than supplying synthetic STAN summaries. */
function analyzeBoundedSource(string $source): array
{
	$extractor = new FrontEndSymbolExtractor();
	$file = $extractor->extract('/tmp/stan-bounded.phs', $source);
	$result = (new StanExpressionTypeResolver())->analyzeWorkspaceExpressions([$extractor->summarize($file, $source)], []);
	$diagnostics = [];
	foreach ($result as $name => $rows) {
		if (str_ends_with($name, '_diagnostics')) {
			$diagnostics = array_merge($diagnostics, $rows);
		}
	}
	return $diagnostics;
}

function requireBoundedResult(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$source = <<<'PHS'
namespace Example;
class Row {
 public int $value = 0;
 public $lookups hash<bool, shared<Row>>;
}
class Worker {
 private $owners hash<bool, shared<Row>>;
 private Row $row;
 function __construct(?Row $row) {
  $this->owners = [];
  $this->row = $row ?? new Row();
 }
 function row(): Row { return $this->row; }
 function run(): void {
  foreach ($this->owners as $owner => $unused) {
   foreach ($owner->lookups as $lookup => $unused2) {
    $lookup->value = 1;
   }
  }
 }
}
PHS;
$diagnostics = analyzeBoundedSource($source);
requireBoundedResult($diagnostics === [], json_encode($diagnostics, JSON_PRETTY_PRINT));

// Resolving shared receivers must still enforce the member's declared type.
$badWrite = analyzeBoundedSource(str_replace('$lookup->value = 1;', '$lookup->value = "wrong";', $source));
requireBoundedResult(in_array('property_type_morph_warning', array_column($badWrite, 'kind'), true), 'Invalid shared-key property write was accepted.');

// Coalescing removes null from the left arm only; a null fallback remains nullable.
$nullable = analyzeBoundedSource(str_replace('$row ?? new Row()', '$row ?? null', $source));
requireBoundedResult(in_array('unchecked_wrapper_property_boundary', array_column($nullable, 'kind'), true), 'Nullable fallback lost its boundary diagnostic.');

// An ordinary conditional must not acquire coalescing's non-null left-arm rule.
$ternary = analyzeBoundedSource(str_replace('$row ?? new Row()', 'true ? $row : new Row()', $source));
requireBoundedResult(in_array('unchecked_wrapper_property_boundary', array_column($ternary, 'kind'), true), 'Ternary incorrectly removed null from its true arm.');

$partial = analyzeBoundedSource(<<<'PHS'
class Partial {
 private int $value;
 function __construct(bool $set) {
  if ($set) { $this->value = 1 + 2; }
 }
 function read(): int { return $this->value; }
}
PHS);
requireBoundedResult(in_array('initialization_warning', array_column($partial, 'kind'), true), 'Conditional constructor initialization was treated as definite.');

$selfRead = analyzeBoundedSource(<<<'PHS'
class Uninitialized {
 private int $value;
 function bump(): void {
  $this->value = $this->value + 1;
 }
}
PHS);
requireBoundedResult(in_array('maybe_uninitialized_property', array_column($selfRead, 'initialization_kind'), true), 'RHS read was hidden by the assignment.');

echo "PASS: bounded STAN assignment, coalescing and shared-key inference\n";
