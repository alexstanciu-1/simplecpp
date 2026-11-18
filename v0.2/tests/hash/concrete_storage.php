<?php
// Instantiate the production recipe for independent storage proofs.
declare(strict_types=1);
require __DIR__ . '/../../tools/api_generator/php/input.php';
require __DIR__ . '/../../tools/api_generator/php/render.php';

/** Independent native test types supplied to the production hash recipe. */
final readonly class HashProbeType implements \Scpp\Api\ConcreteType {
    public function __construct(private string $name) {}
    public function identity(): string { return $this->name; }
    public function cppType(string $namespace): string { return $this->name; }
    public function headers(): array { return []; }
    public function supports(string $requirement): ?bool { return null; }
}
$text = "#pragma once\n#include \"concrete_index_impl.hpp\"\n#include <new>\n#include <stdexcept>\n#include <string>\n";
foreach (['IntegerTable' => ['std::int64_t', '::scpp::detail::hash_integer(key)'],
    'CollisionTable' => ['std::int64_t', '7'], 'StringTable' => ['std::string', '::scpp::detail::hash_integer(key)']] as $name => [$value, $hash]) {
    $parts = \Scpp\Api\concreteTemplate('collections/hash', ['k' => new HashProbeType('std::int64_t'), 'v' => new HashProbeType($value)], [
        'n' => $name, 'inline' => '', 'index' => '::scpp::detail::concrete_hash_index', 'candidate' => '::scpp::detail::hash_candidate', 'count' => 'std::int64_t',
        'hash' => $hash, 'equal' => 'left == right', 'append' => \Scpp\Api\template('collections/hash_append', []),
    ]);
    $text .= "struct $name { struct storage; };\n" . $parts['storage'];
}
file_put_contents($argv[1], $text);
