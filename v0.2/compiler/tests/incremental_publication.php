<?php

declare(strict_types=1);
namespace scpp\compiler {
	// Count contract serialization without adding production instrumentation.
	function json_encode(mixed $value, int $flags = 0, int $depth = 512): string|false
	{
		$GLOBALS['publication_contract_reads']++;
		return \json_encode($value, $flags, $depth);
	}
}
namespace {
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{scope, Source_Frontend, Symbol_Publication, Dependency_Watches, flavor, source_file, change_status};
function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}
foreach (flavor::cases() as $flavor) {
	$make = static function (string $path, string $name) use ($flavor): \scpp\compiler\ast_file_node {
		$header = $flavor === flavor::cpp ? 'void ' . $name . '()' : 'function ' . $name . '(): void';
		return (new Source_Frontend())->collect(new source_file($path, $header . '{}', $flavor));
	};
	$scope = new scope();
	$watches = new Dependency_Watches();
	$a = $make('a', 'shared');
	$b = $make('b', 'shared');
	$c = $make('c', 'shared');
	$other = $make('other', 'other');
	$GLOBALS['publication_contract_reads'] = 0;
	Symbol_Publication::changes(['a' => $a, 'other' => $other], [], $scope, $watches);
	verify($GLOBALS['publication_contract_reads'] === 2, 'Initial publication contracts');
	$entry = $scope->entry('func:shared');
	$other_entry = $scope->entry('func:other');
	$GLOBALS['publication_contract_reads'] = 0;
	Symbol_Publication::changes([], [], $scope, $watches);
	verify($GLOBALS['publication_contract_reads'] === 0, 'No-op inspected declaration contracts');
	verify($entry->status === change_status::none && $other_entry->status === change_status::none, 'Status reset');
	$replacement = $make('a', 'shared');
	$GLOBALS['publication_contract_reads'] = 0;
	Symbol_Publication::changes(['a' => $replacement], [$a], $scope, $watches);
	verify($GLOBALS['publication_contract_reads'] === 2, 'Replacement inspected unaffected contracts');
	verify($scope->entry('func:shared') === $entry && $entry->status === change_status::changed, 'Stable replacement');
	$a = $replacement;
	Symbol_Publication::changes(['c' => $c, 'b' => $b], [], $scope, $watches);
	[$na, $nb, $nc] = [$a->functions()[0], $b->functions()[0], $c->functions()[0]];
	verify($scope->declarations['func:shared'] === $na && $na->duplicate_element() === $nb
		&& $nb->duplicate_element() === $nc, 'Sorted duplicate insertion');
	$GLOBALS['publication_contract_reads'] = 0;
	Symbol_Publication::changes([], [$b], $scope, $watches);
	verify($GLOBALS['publication_contract_reads'] === 0, 'Ambiguous update inspected unrelated contracts');
	verify($na->duplicate_element() === $nc && $nb->duplicate_element() === null, 'Middle removal');
	Symbol_Publication::changes([], [$a], $scope, $watches);
	verify($entry->declaration() === $nc && $na->duplicate_element() === null, 'Duplicate recovery');
	Symbol_Publication::changes([], [$c], $scope, $watches);
	verify($entry->status === change_status::removed && $entry->declaration() === null, 'Tombstone');
	Symbol_Publication::changes([], [], $scope, $watches);
	verify($entry->status === change_status::removed, 'No-op lost tombstone');
	Symbol_Publication::changes(['b' => $b], [], $scope, $watches);
	verify($scope->entry('func:shared') === $entry && $entry->declaration() === $nb
		&& $entry->status === change_status::added, 'Stable reactivation');
	verify($scope->entry('func:other') === $other_entry && $other_entry->declaration() === $other->functions()[0]
		&& $other_entry->status === change_status::none, 'Unaffected symbol changed');
}
echo "Changed-source publication: selective contracts, stable slots, duplicates and recovery passed for all flavors\n";
}
