<?php
namespace reader_proof;

class Module_Record {
    public string $name = '';
    public bool $enabled = false;
    public array $codes /** vector<int> */ = [];
}

class Reader_Proof {
public static function decode_record(string $text): Module_Record {
    $reader = new \scpp\Json_Reader($text, 'module.json', 128);
    $record = new Module_Record();
    $has_name = false;
    $has_enabled = false;
    $has_codes = false;
    $reader->begin_object();
    while ($reader->next_property()) {
        $key = $reader->property_name();
        if ($key === 'name') {
            $record->name = $reader->read_string();
            $has_name = true;
        } elseif ($key === 'enabled') {
            $record->enabled = $reader->read_boolean();
            $has_enabled = true;
        } elseif ($key === 'codes') {
            $codes /** vector<int> */ = [];
            $reader->begin_array();
            while ($reader->next_element()) { $codes[] = $reader->read_integer(); }
            $record->codes = $codes;
            $has_codes = true;
        } else { $reader->reject('unknown module property'); }
    }
    if (!$has_name || !$has_enabled || !$has_codes) { $reader->reject('missing required module property'); }
    $reader->finish_document();
    return $record;
}

// Walk syntax without retaining a generic data tree; useful for parity corpus.
public static function consume(\scpp\Json_Reader $reader): void {
    $kind = $reader->kind();
    if ($kind === 'object') {
        $reader->begin_object();
        while ($reader->next_property()) { self::consume($reader); }
    } elseif ($kind === 'array') {
        $reader->begin_array();
        while ($reader->next_element()) { self::consume($reader); }
    } elseif ($kind === 'string') { $reader->read_string(); }
    elseif ($kind === 'number') { $reader->read_number(); }
    elseif ($kind === 'boolean') { $reader->read_boolean(); }
    else { $reader->read_null(); }
}

public static function accepts(string $text, int $limit): bool {
    try {
        $reader = new \scpp\Json_Reader($text, 'corpus.json', $limit);
        self::consume($reader);
        $reader->finish_document();
        return true;
    } catch (\RuntimeException $error) { return false; }
}

public static function record_rejects(string $text): bool {
    try { $record = self::decode_record($text); return false; }
    catch (\RuntimeException $error) { return true; }
}
}

$record = Reader_Proof::decode_record('{"codes":[0,-2,9223372036854775807,-9223372036854775808],"enabled":false,"name":"json"}');
$codes /** vector<int> */ = $record->codes;
echo $record->name, ':', $record->enabled ? 'true' : 'false', ':', $codes[0], ':', $codes[1], ':', $codes[2], ':', $codes[3], "\n";
$valid /** vector<string> */ = ['null', 'false', 'true', '0', '-0', '1e9999', '""', '"a\\u0000b"', '"\\uD83D\\uDE00"', '{}', '[]', '{"0":0,"00":1}', '{"a":{"a":2},"b":[null,false]}'];
foreach ($valid as $text) { if (!Reader_Proof::accepts($text, 128)) { throw new \RuntimeException('rejected valid JSON'); } }
$invalid /** vector<string> */ = ['', ' ', '01', '1.', '1e', '+1', 'NaN', 'null null', '[1,]', '{"a":1,}', '{"a" 1}', '{"a":1,"\\u0061":2}', '{"a":{"x":1,"x":2}}', '[', '{', '"\\uD800"', '"\\uDC00"', '"\\q"'];
foreach ($invalid as $text) { if (Reader_Proof::accepts($text, 128)) { throw new \RuntimeException('accepted invalid JSON'); } }
if (Reader_Proof::accepts('[]', 0) || Reader_Proof::accepts('[]', 257) || Reader_Proof::accepts('[[]]', 1)) { throw new \RuntimeException('accepted invalid depth'); }
if (!Reader_Proof::accepts('[]', 1)) { throw new \RuntimeException('rejected depth boundary'); }
$bad_records /** vector<string> */ = ['{}', '[]', '{"name":1}', '{"name":"a","name":"b"}', '{"unknown":0}', '{"name":"ok","enabled":false,"codes":[1.0]}', '{"name":"ok","enabled":false,"codes":[9223372036854775808]}', '{"name":"ok","enabled":false,"codes":[]} false'];
foreach ($bad_records as $text) { if (!Reader_Proof::record_rejects($text)) { throw new \RuntimeException('accepted bad record'); } }
// Decoding is private until success; a failed replacement keeps the published row.
try { $record = Reader_Proof::decode_record('{"name":"partial","codes":[1.0]}'); }
catch (\RuntimeException $error) { $caught_message = $error->getMessage(); }
if ($record->name !== 'json') { throw new \RuntimeException('published partial record'); }
// A failure remains terminal, including after an otherwise valid scalar read.
$broken = new \scpp\Json_Reader('[1]', 'protocol.json', 128);
$broken->begin_array();
$broken->next_element();
$caught = 0;
try { $broken->next_element(); } catch (\RuntimeException $error) { $caught += 1; }
try { $broken->read_integer(); } catch (\RuntimeException $error) { $caught += 1; }
if ($caught !== 2) { throw new \RuntimeException('reader resumed after failure'); }
echo "reader-ok\n";
