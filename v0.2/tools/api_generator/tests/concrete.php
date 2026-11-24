<?php
// Reusable instantiation proof: a two-parameter record and a native-only lifetime probe.
declare(strict_types=1);
require __DIR__ . '/../php/input.php';
require __DIR__ . '/../php/render.php';
use function Scpp\Api\concreteTemplate;
use Scpp\Api\ConcreteType;
use Scpp\Api\RuntimeConcreteType;

/** Independent provider proof: no metadata plan or AST is exposed to the engine. */
final readonly class FixtureConcreteType implements ConcreteType, \Scpp\Api\ConcreteHashKey {
    public function __construct(private string $name, private ?bool $copyable = null) {}
    public function hashExpression(string $key): ?string { return "(void($key), 7)"; }
    public function equalityExpression(string $left, string $right): ?string { return "$left == $right"; }
    public function identity(): string { return 'fixture:' . $this->name; }
    public function cppType(string $namespace): string { return $this->name; }
    public function headers(): array { return []; }
    public function supports(string $requirement): ?bool {
        return $requirement === 'copy_constructible' ? $this->copyable : null;
    }
}
$int = new FixtureConcreteType('int');
$double = new FixtureConcreteType('double');

function rejected(Closure $action): void {
    try { $action(); } catch (RuntimeException) { return; }
    throw new RuntimeException('Invalid instantiation accepted');
}
$pair = concreteTemplate('../tests/fixtures/pair', ['left' => $int, 'right' => $double], ['name' => 'Pair']);
rejected(fn() => concreteTemplate('../tests/fixtures/pair', ['left' => $int], ['name' => 'Pair']));
rejected(fn() => concreteTemplate('../tests/fixtures/pair', ['left' => $int, 'right' => $double, 'extra' => $int], ['name' => 'Pair']));
rejected(fn() => concreteTemplate('../tests/fixtures/pair', ['left' => $int, 'right' => $double], []));
rejected(fn() => concreteTemplate('../tests/fixtures/pair', ['left' => $int, 'right' => $double], ['name' => 'Pair', 'left' => 'char']));
rejected(fn() => concreteTemplate('../tests/fixtures/pair', ['left' => new FixtureConcreteType('NoCopy', false), 'right' => $double], ['name' => 'Pair']));
rejected(fn() => concreteTemplate('../tests/fixtures/pair', ['left' => 'int', 'right' => $double], ['name' => 'Pair']));
$plan = json_decode(file_get_contents(__DIR__ . '/../../../runtime/generated/prepared.json'), true, flags: JSON_THROW_ON_ERROR);
$plan['aliases'] = (object)$plan['aliases'];
$integer = new RuntimeConcreteType($plan, 'int64');
$string = new RuntimeConcreteType($plan, 'string');
$byte = new RuntimeConcreteType($plan, 'byte');
if ($integer->cppType('scpp') !== 'int64' || $integer->cppType('App') !== '::scpp::int64'
    || $byte->identity() !== 'uint8' || $string->supports('nothrow_move_constructible') !== true
    || $string->supports('not_a_requirement') !== null) throw new RuntimeException('Runtime type contract mismatch');
$provider = $plan['submodules']['type_exports']['string']['provider'];
$plan['submodules']['nodes'][$provider]['header'] = 'relocated/text.hpp';
if ((new RuntimeConcreteType($plan, 'string'))->headers() !== ['relocated/text.hpp']) throw new RuntimeException('Type provider relocation lost');
$plan['submodules']['published'] = [];
rejected(fn() => new RuntimeConcreteType($plan, 'string'));
rejected(fn() => new RuntimeConcreteType($plan, 'missing'));
$parts = concreteTemplate('collections/vector', ['e' => new FixtureConcreteType('Probe')], ['enable_reserve' => '1', 'n' => 'ProbeVector', 'count' => 'int64', 'inline' => '', 'buffer' => 'detail', 'access' => 'detail::vector_access',
    'limit_method' => 'iteration_limit', 'next_method' => 'iteration_next']);
$text = implode('', $parts);
if (preg_match('/\btemplate\s*</', $text) || preg_match('/std::(?:vector|move|swap|exchange|equal|is_)/', $text)) {
    throw new RuntimeException('Concrete vector depends on a template algorithm');
}
$hashArguments = [
    'n' => 'ProbeHash', 'inline' => '', 'index' => 'detail::concrete_hash_index', 'candidate' => 'detail::hash_candidate', 'count' => 'int64',
    'limit_method' => 'iteration_limit', 'next_method' => 'iteration_next', 'next_key_method' => 'iteration_next_key', 'current_value_method' => 'iteration_current_value', 'enable_keyed_input' => '', 'enable_shared_lookup' => '', 'enable_append' => '1', 'append' => \Scpp\Api\template('collections/hash_append', []),
];
$hashTypes = ['k' => new FixtureConcreteType('int64'), 'v' => new FixtureConcreteType('Probe')];
$hash = concreteTemplate('collections/hash', $hashTypes, $hashArguments);
rejected(fn() => concreteTemplate('collections/hash', ['k' => $byte, 'v' => $integer], $hashArguments));
rejected(fn() => concreteTemplate('collections/hash', $hashTypes, $hashArguments + ['hash' => '7']));
rejected(fn() => concreteTemplate('collections/hash', $hashTypes, $hashArguments + ['equal' => 'true']));
if ($integer->hashExpression('k') !== '::scpp::detail::hash_integer(k)'
    || $integer->equalityExpression('a', 'b') !== 'a == b'
    || !str_contains($string->hashExpression('k'), 'hash_string_bytes(k.data()')) throw new RuntimeException('Key contract lost');
$invalid = $hashArguments; unset($invalid['enable_append']);
rejected(fn() => concreteTemplate('collections/hash', $hashTypes, $invalid));
$invalid['enable_append'] = 'true';
rejected(fn() => concreteTemplate('collections/hash', $hashTypes, $invalid));
$disabled = $hashArguments; $disabled['enable_append'] = '';
$withoutAppend = concreteTemplate('collections/hash', $hashTypes, $disabled);
if ($withoutAppend['append_declaration'] !== '' || $withoutAppend['append_definition'] !== ''
    || $hash['append_declaration'] === '' || $hash['append_definition'] === '') throw new RuntimeException('Conditional concrete fragment mismatch');
if (preg_match('/\btemplate\s*</', implode('', $hash)) || str_contains($hash['storage'], 'std::vector')) throw new RuntimeException('Concrete hash depends on templates');
$applicationHash = \Scpp\Api\concreteApplication('collections/hash', ['k' => new FixtureConcreteType('int64'), 'v' => new FixtureConcreteType('Probe')], [
    'n' => 'ProbeConcreteHash', 'inline' => 'inline ', 'index' => 'detail::concrete_hash_index', 'candidate' => 'detail::hash_candidate', 'count' => 'int64',
    'enable_keyed_input' => '', 'enable_shared_lookup' => '', 'enable_append' => '', 'append' => '', 'limit_method' => 'iteration_limit', 'next_method' => 'iteration_next', 'next_key_method' => 'iteration_next_key', 'current_value_method' => 'iteration_current_value',
], 'scpp');
$source = file_get_contents(__DIR__ . '/fixtures/concrete_probe.cpp');
$source = str_replace(['@pair@', '@declaration@', '@implementation@', '@hash@'], [$pair['declaration'], $parts['output_declaration'] . $parts['declaration'],
    $parts['storage'] . 'namespace detail { struct vector_access {' . $parts['access'] . '}; }' . $parts['definition'] . $parts['output_definition'], $hash['storage'] . $applicationHash], $source);
file_put_contents($argv[1], $source);
echo "Concrete recipe binding and validation passed\n";
