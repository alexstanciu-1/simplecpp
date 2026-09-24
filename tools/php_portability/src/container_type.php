<?php
declare(strict_types=1);

namespace scpp\portability;

/** Recursive explicit container spelling, with no type or declaration lookup. */
final class Container_Type {
    public static function parse(string $annotation): string {
        if (!preg_match('~^/\*\*\s*(.*?)\s*\*/$~sD', $annotation, $match)) {
            throw new \RuntimeException('expected explicit container type annotation');
        }
        $source = $match[1];
        $position = 0;
        $read = function (int $depth = 0) use (&$read, &$position, $source): string {
            if ($depth > 32) { throw new \RuntimeException('container annotation nesting exceeds 32'); }
            if (!preg_match('/\G\s*(\\\\?[a-zA-Z_][a-zA-Z_0-9]*(?:\\\\[a-zA-Z_][a-zA-Z_0-9]*)*)\s*/', $source, $name, 0, $position)) {
                throw new \RuntimeException('expected explicit container element type');
            }
            $position += strlen($name[0]);
            $type = $name[1];
            if (($source[$position] ?? '') === '<') {
                if (!in_array($type, ['vector', 'hash', 'Storage', 'Keyed_Storage', 'shared'], true)) { throw new \RuntimeException('unsupported container type ' . $type); }
                ++$position;
                $arguments = [$read($depth + 1)];
                while (($source[$position] ?? '') === ',') {
                    ++$position;
                    $arguments[] = $read($depth + 1);
                }
                if (($source[$position] ?? '') !== '>') { throw new \RuntimeException('expected closing container angle'); }
                ++$position;
                while (isset($source[$position]) && ctype_space($source[$position])) { ++$position; }
                if ((in_array($type, ['Storage', 'Keyed_Storage', 'shared'], true)) && (count($arguments) !== 1 || str_contains($arguments[0], '<') || in_array(strtolower($arguments[0]), ['int', 'float', 'bool', 'string', 'uint8', 'uint16', 'uint32', 'uint64', 'int8', 'int16', 'int32', 'int64', 'double'], true))) {
                    throw new \RuntimeException('object container/wrapper requires exactly one literal record type');
                }
                if (($type === 'vector' && count($arguments) !== 1) || ($type === 'hash' && count($arguments) > 2)) {
                    throw new \RuntimeException('wrong container type argument count');
                }
                if ($type === 'hash' && isset($arguments[1]) && !in_array($arguments[1], ['int', 'string'], true) && !str_starts_with($arguments[1], 'shared<')) {
                    throw new \RuntimeException('hash key type must be int, string or shared<Record> in this profile');
                }
                return $type . '<' . implode(', ', $arguments) . '>';
            }
            if (in_array(strtolower($type), ['vector', 'hash', 'storage', 'keyed_storage', 'shared', 'mixed', 'dynamic', 'array', 'object', 'void', 'null', 'true', 'false', 'never', 'iterable', 'callable', 'self', 'parent', 'static'], true)) {
                throw new \RuntimeException('unsupported container element type ' . $type);
            }
            return $type;
        };
        $type = $read();
        if ($position !== strlen($source) || (!str_starts_with($type, 'vector<') && !str_starts_with($type, 'hash<') && !str_starts_with($type, 'Storage<') && !str_starts_with($type, 'Keyed_Storage<'))) {
            throw new \RuntimeException('expected one complete vector, hash or Storage annotation');
        }
        return $type;
    }
}
