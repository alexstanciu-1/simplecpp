<?php
declare(strict_types=1);
namespace scpp\portability;

/** Declaration-local facts; no inferred receiver or payload types. */
final class Staged_Field {
    public function __construct(public string $name, public string $payload, public string $path, public int $line) {}
    public function backing(): string { return '$__scpp_portability_state_' . $this->name; }
    public function reader(): string { return 'scpp_portability_staged_read_' . $this->name; }
    public function writer(): string { return 'scpp_portability_staged_write_' . $this->name; }
}

/** Bounded current-class value reads and standalone whole-field writes. */
final class Staged_Fields {
    public static function lower(Node $file): void {
        $classes = [];
        foreach ($file->children as $node) {
            if (in_array($node->kind, ['class', 'final_class', 'abstract_class'], true)) {
                $classes[strtolower(explode(' ', $node->text)[0])] = true;
            }
        }
        foreach ($file->children as $owner) {
            $fields = [];
            foreach ($owner->children as $node) {
                if ($node->staged !== null) { $fields[$node->staged->name] = $node->staged; }
            }
            if ($fields === []) { continue; }
            foreach ($fields as $field) {
                if (str_contains($owner->text, ' extends ') || $field->path !== $owner->path) {
                    self::fail($field, 'inherited or trait-owned staged fields require explicit source accessors');
                }
                if (!isset($classes[strtolower($field->payload)])) {
                    self::fail($field, 'staged payload must be a class declared in this file; use explicit accessors and copies for other payloads');
                }
            }
            $members = [];
            foreach ($owner->children as $member) {
                if ($member->staged !== null) {
                    $field = $member->staged;
                    $backing = '$this->' . substr($field->backing(), 1);
                    $error = 'new \\scpp_portability_logic_exception("Uninitialized field: ' . $field->name . '")';
                    $nullable = 'nullable<' . $field->payload . '>';
                    $members[] = new Node('property', 'private ' . $field->backing() . ' ' . $nullable . ' = null;', $field->line);
                    $members[] = new Node('method', 'private function ' . $field->reader() . '(): ' . $field->payload,
                        $field->line, [new Node('token', 'if (' . $backing . ' === null) { throw ' . $error . '; } return ' . $backing . ';', $field->line)]);
                    $members[] = new Node('method', 'private function ' . $field->writer() . '(' . $nullable . ' $value): void',
                        $field->line, [new Node('token', 'if ($value === null) { throw ' . $error . '; } ' . $backing . ' = $value;', $field->line)]);
                } else {
                    if (str_contains(strtolower($member->text), '__scpp_portability_') || str_contains(strtolower($member->text), 'scpp_portability_staged_')) {
                        self::fail(reset($fields), 'reserved staged-field accessor name');
                    }
                    if ($member->kind === 'method') { $member->children = self::rewrite($member->children, $fields, true); }
                    $members[] = $member;
                }
            }
            $owner->children = $members;
        }
    }

    private static function significant(array $nodes, int $at, int $step = 1): int {
        while (isset($nodes[$at]) && ($nodes[$at]->kind === 'comment' || trim($nodes[$at]->text) === '')) { $at += $step; }
        return $at;
    }
    private static function contains(array $nodes, Staged_Field $field): bool {
        foreach ($nodes as $index => $node) {
            if ($node->text === '$this') {
                $next = self::significant($nodes, $index + 1);
                if (($nodes[$next]->kind ?? '') === 'property_access' && $nodes[$next]->text === '->' . $field->name) { return true; }
            }
            if (self::contains($node->children, $field)) { return true; }
            if (in_array($node->kind, ['probe', 'keyed_removal'], true) && self::opaqueContains($node->text, $field->name)) { return true; }
        }
        return false;
    }
    /** Probe/removal parsers retain PHP path text; inspect tokens, never string contents. */
    private static function opaqueContains(string $text, string $name): bool {
        $tokens = array_values(array_filter(token_get_all('<?php ' . $text),
            static fn($token): bool => !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)));
        foreach ($tokens as $i => $token) {
            if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$this'
                && ($tokens[$i + 1][0] ?? null) === T_OBJECT_OPERATOR
                && ($tokens[$i + 2][1] ?? null) === $name) { return true; }
        }
        return false;
    }
    private static function rewrite(array $nodes, array $fields, bool $statements): array {
        $result = [];
        for ($i = 0; $i < count($nodes); ++$i) {
            $node = $nodes[$i];
            foreach ($fields as $field) {
                if (($node->kind === 'method' || in_array($node->kind, ['probe', 'keyed_removal'], true)
                    || ($node->kind === 'call' && in_array($node->text, ['isset', 'empty', 'unset'], true)))
                    && self::contains([$node], $field)) {
                    self::fail($field, 'staged fields in probes, removal or nested callables require explicit source accessors');
                }
            }
            $access = self::significant($nodes, $i + 1);
            $field = $node->text === '$this' && ($nodes[$access]->kind ?? '') === 'property_access'
                ? ($fields[substr($nodes[$access]->text, 2)] ?? null) : null;
            if ($field !== null) {
                $after = self::significant($nodes, $access + 1);
                $before = self::significant($nodes, $i - 1, -1);
                $next = $nodes[$after]->text ?? '';
                $previous = $nodes[$before]->text ?? '';
                if ($next === '=') {
                    if (!$statements || ($before >= 0 && !in_array($previous, [';', '{', '}'], true))) {
                        self::fail($field, 'staged writes must be standalone whole-field assignments');
                    }
                    $end = $after + 1;
                    while (isset($nodes[$end]) && $nodes[$end]->text !== ';') { ++$end; }
                    if (!isset($nodes[$end])) { self::fail($field, 'staged write requires a statement terminator'); }
                    $rhs = self::rewrite(array_slice($nodes, $after + 1, $end - $after - 1), $fields, false);
                    $result[] = $node;
                    $result[] = new Node('call', '->' . $field->writer(), $node->line, $rhs);
                    $result[] = $nodes[$end];
                    $i = $end;
                    continue;
                }
                if (in_array($next, ['++', '--', '+=', '-=', '*=', '/=', '%=', '**=', '.=', '&=', '|=', '^=', '<<=', '>>=', '??=', '['], true) || str_starts_with($next, '->')
                    || in_array($previous, ['++', '--', '&'], true)) {
                    self::fail($field, 'staged field mutation, projection or reference requires an explicit checked local and whole replacement');
                }
                $result[] = $node;
                $result[] = new Node('call', '->' . $field->reader(), $node->line);
                $i = $access;
                continue;
            }
            if ($node->kind !== 'method') {
                $node->children = self::rewrite($node->children, $fields, $node->kind === 'body' || ($node->kind === 'group' && $node->text === '{'));
            }
            $result[] = $node;
        }
        return $result;
    }
    private static function fail(Staged_Field $field, string $message): never {
        throw new \RuntimeException($field->path . ':' . $field->line . ': ' . $message);
    }
}
