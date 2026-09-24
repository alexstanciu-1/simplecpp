<?php
declare(strict_types=1);
require_once __DIR__ . '/../../generators/php/bin/bootstrap.php';
$source = <<<'PHS'
namespace cast_proof;
interface Payload {}
class Row implements Payload { public int $number = 7; }
function is_row($value nullable<Payload>): bool { return $value instanceof Row; }
function row($value nullable<Payload>): Row { return scpp_portability_object_cast($value, Row::class); }
PHS;
$result = (new Scpp\S2S\Transpiler())->transpile('/tmp/scpp-object-cast-emission.phs', false, false, $source);
if ($result->errors !== []) { throw new RuntimeException(implode('; ', $result->errors)); }
$text = implode("\n", [...$result->headerLines, ...$result->sourceLines]);
foreach (['virtual ~Payload() = default;', '::scpp::object_is<', '::scpp::checked_object_cast<'] as $required) {
    if (!str_contains($text, $required)) { throw new RuntimeException('Missing ' . $required); }
}
if (str_contains($text, 'shared_p<instanceof')) { throw new RuntimeException('instanceof swallowed as a type'); }
echo "Object cast and instanceof emission passed\n";
