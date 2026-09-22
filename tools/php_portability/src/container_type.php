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
                if (!in_array($type, ['vector', 'hash'], true)) { throw new \RuntimeException('unsupported container type ' . $type); }
                ++$position;
                $arguments = [$read($depth + 1)];
                while (($source[$position] ?? '') === ',') {
                    ++$position;
                    $arguments[] = $read($depth + 1);
                }
                if (($source[$position] ?? '') !== '>') { throw new \RuntimeException('expected closing container angle'); }
                ++$position;
                while (isset($source[$position]) && ctype_space($source[$position])) { ++$position; }
                if (($type === 'vector' && count($arguments) !== 1) || ($type === 'hash' && count($arguments) > 2)) {
                    throw new \RuntimeException('wrong container type argument count');
                }
                if ($type === 'hash' && isset($arguments[1]) && !in_array($arguments[1], ['int', 'string'], true)) {
                    throw new \RuntimeException('hash key type must be int or string in this profile');
                }
                return $type . '<' . implode(', ', $arguments) . '>';
            }
            if (in_array(strtolower($type), ['vector', 'hash', 'mixed', 'dynamic', 'array', 'object', 'void', 'null', 'true', 'false', 'never', 'iterable', 'callable', 'self', 'parent', 'static'], true)) {
                throw new \RuntimeException('unsupported container element type ' . $type);
            }
            return $type;
        };
        $type = $read();
        if ($position !== strlen($source) || (!str_starts_with($type, 'vector<') && !str_starts_with($type, 'hash<'))) {
            throw new \RuntimeException('expected one complete vector or hash annotation');
        }
        return $type;
    }
}
