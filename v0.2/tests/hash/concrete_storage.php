<?php
// Instantiate the production recipe for independent storage proofs.
declare(strict_types=1);
require __DIR__ . '/../../tools/api_generator/php/input.php';
require __DIR__ . '/../../tools/api_generator/php/render.php';

/** Independent native test types supplied to the production hash recipe. */
final readonly class HashProbeType implements \Scpp\Api\ConcreteType, \Scpp\Api\ConcreteHashKey {
    public function __construct(private string $name, private string $hash = '::scpp::detail::hash_integer(key)') {}
    public function hashExpression(string $key): ?string { return str_replace("key", $key, $this->hash); }
    public function equalityExpression(string $left, string $right): ?string { return "$left == $right"; }
    public function identity(): string { return $this->name; }
    public function cppType(string $namespace): string { return $this->name; }
    public function headers(): array { return []; }
    public function supports(string $requirement): ?bool { return null; }
}
$text = "#pragma once\n#include \"concrete_index_impl.hpp\"\n#include <new>\n#include <stdexcept>\n#include <string>\n";
foreach (['IntegerTable' => ['std::int64_t', '::scpp::detail::hash_integer(key)'],
    'CollisionTable' => ['std::int64_t', '7'], 'StringTable' => ['std::string', '::scpp::detail::hash_integer(key)']] as $name => [$value, $hash]) {
    $parts = \Scpp\Api\concreteTemplate('collections/hash', ['k' => new HashProbeType('std::int64_t', $hash), 'v' => new HashProbeType($value)], [
        'n' => $name, 'inline' => '', 'index' => '::scpp::detail::concrete_hash_index', 'candidate' => '::scpp::detail::hash_candidate', 'count' => 'std::int64_t',
        'limit_method' => 'iteration_limit', 'next_method' => 'iteration_next', 'enable_keyed_input' => '', 'enable_shared_lookup' => '', 'enable_append' => '1', 'append' => \Scpp\Api\template('collections/hash_append', []),
    ]);
    $text .= "struct $name { struct storage; };\n" . $parts['storage'];
}
file_put_contents($argv[1], $text);
