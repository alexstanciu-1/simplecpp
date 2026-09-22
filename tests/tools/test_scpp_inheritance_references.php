<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bin/bootstrap.php';

use Scpp\S2S\Analysis\FrontEndSymbolExtractor;
use Scpp\S2S\Stan\StanDependencyResolver;
use Scpp\S2S\Transpiler;

function check(bool $condition, string $message): void {
	if (!$condition) { throw new RuntimeException($message); }
}
$source = <<<'PHS'
namespace diagnostics;
class Child extends \Root_Base implements \models\Contract {
    public $peer UserPeer = null;
}
class Same extends LocalBase {}
PHS;
$extractor = new FrontEndSymbolExtractor();
$file = $extractor->extract('/tmp/inheritance-references.phs', $source);
check($file->namespaces[0]->classes[0]->parentClass === '\\Root_Base', 'IR lost global base marker');
check($file->namespaces[0]->classes[0]->interfaces === ['\\models\\Contract'], 'IR lost global interface marker');
check($file->namespaces[0]->classes[1]->parentClass === 'LocalBase', 'relative inheritance changed');
$summary = $extractor->summarize($file, $source);
$dependencies = $summary['dependencies'];
check(count(array_filter($dependencies, static fn(array $row): bool => $row['kind'] === 'extends' && $row['target'] === '\\Root_Base')) === 1, 'dependency summary lost root');
$result = (new Transpiler(phpProfile: 'strict'))->transpile('/tmp/inheritance-references.phs', sourceOverride: $source);
check($result->errors === [], 'lowering errors');
$header = implode("\n", $result->headerLines);
check(str_contains($header, 'public ::scpp::Root_Base'), 'inheritance path not rooted');
check(str_contains($header, 'public ::scpp::models::Contract'), 'interface path not rooted');
check(!str_contains($header, 'class Root_Base;'), 'wrong namespace base forward');
check(str_contains($header, 'class UserPeer;'), 'ordinary class forward lost');
$resolver = new StanDependencyResolver();
$symbols = [
	['kind'=>'class', 'name'=>'Root_Base', 'scope'=>'', 'path'=>'base.phs', 'key'=>'global'],
	['kind'=>'class', 'name'=>'Root_Base', 'scope'=>'diagnostics', 'path'=>'shadow.phs', 'key'=>'shadow'],
	['kind'=>'class', 'name'=>'Base', 'scope'=>'models', 'path'=>'model.phs', 'key'=>'model'],
	['kind'=>'class', 'name'=>'Base', 'scope'=>'other', 'path'=>'other.phs', 'key'=>'other'],
	['kind'=>'interface', 'name'=>'Contract', 'scope'=>'models', 'path'=>'contract.phs', 'key'=>'contract'],
	['kind'=>'interface', 'name'=>'Contract', 'scope'=>'other', 'path'=>'wrong.phs', 'key'=>'wrong'],
];
$lookup = $resolver->buildResolutionLookup($symbols);
foreach ([['extends', '\\Root_Base', 'global'], ['extends', '\\models\\Base', 'model'], ['implements', '\\models\\Contract', 'contract']] as [$kind, $target, $key]) {
	$matches = $resolver->resolveDependencyTarget($kind, $target, $lookup);
	check(count($matches) === 1 && $matches[0]['key'] === $key, 'absolute dependency matched wrong scope: ' . $target);
}
check($resolver->resolveDependencyTarget('extends', '\\missing\\Base', $lookup) === [], 'missing absolute base fell back to a basename');
echo "PASS: inheritance reference IR, emission, exact dependencies and user forwards\n";
