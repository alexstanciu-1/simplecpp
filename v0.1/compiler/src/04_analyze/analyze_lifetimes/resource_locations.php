<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Static field paths and exact parameter endpoints shared by resource flow and acceptance. */
final class Resource_Locations {
    public static function locals(\check_bodies\Checked_Body $body): array /** hash<Resource_Location> */ {
        $out /** hash<Resource_Location> */ = [];
        for ($id = 1; $id < $body->names->locals_count()+1; $id++) {
            foreach (Resource_Locations::paths($body->definition_for($body->local_type_for($id))) as $path) {
                $location = new Resource_Location($id,$path); $key = $location->key(); $out[$key] = $location;
            }
        }
        return $out;
    }
    public static function parameters(\check_bodies\Checked_Body $body): array /** vector<Parameter_Resources> */ {
        $out /** vector<Parameter_Resources> */ = [];
        for ($position = 0; $position < $body->entry_parameter_count(); $position++) {
            $paths = Resource_Locations::paths($body->definition_for($body->local_type_for($position+1)));
            if (q_count($paths) !== 0) { $out[] = new Parameter_Resources($position,$paths); }
        }
        return $out;
    }
    public static function paths(\type_model\Named_Definition $definition): array /** vector<vector<int>> */ {
        $out /** vector<vector<int>> */ = []; $resource = $definition->ownership;
        if ($resource === null) { return $out; }
        if ($resource->kind !== \type_model\RESOURCE_NONE) { $empty /** vector<int> */ = []; $out[] = $empty; }
        else { for ($i = 0; $i < $resource->path_count(); $i++) { $out[] = $resource->path_at($i); } }
        return $out;
    }
    public static function path_key(array $path /** vector<int> */): string {
        $key = ''; $separator = '';
        foreach ($path as $ordinal) {
            if ($ordinal < 0) { throw new \LogicException('Unknown ownership alias endpoint'); }
            $key .= $separator . $ordinal; $separator = '.';
        }
        return $key;
    }
    /** Exact byte ordering, including length ties; PHP numeric-string comparison is unsuitable. */
    private static function less(string $left, string $right): bool {
        $length = string_byte_len($left); $other = string_byte_len($right); if ($other < $length) { $length = $other; }
        for ($i = 0; $i < $length; $i++) {
            $a = string_byte_at($left,$i); $b = string_byte_at($right,$i);
            if ($a !== $b) { return $a < $b; }
        }
        return string_byte_len($left) < $other;
    }
    private static function ordinal(string $text): int {
        $length = string_byte_len($text);
        if ($length === 0) { throw new \LogicException('Unknown ownership alias endpoint'); }
        if ($length > 1) { if (string_byte_at($text,0) === 48) { throw new \LogicException('Unknown ownership alias endpoint'); } }
        for ($i = 0; $i < $length; $i++) {
            $byte = string_byte_at($text,$i);
            if (($byte < 48) || ($byte > 57)) { throw new \LogicException('Unknown ownership alias endpoint'); }
        }
        if ($length > 19) { throw new \LogicException('Unknown ownership alias endpoint ordinal'); }
        if ($length === 19) { if (Resource_Locations::less('9223372036854775807',$text)) { throw new \LogicException('Unknown ownership alias endpoint ordinal'); } }
        $value = 0;
        for ($i = 0; $i < $length; $i++) { $digit = string_byte_at($text,$i)-48; $value = $value*10 + $digit; }
        return $value;
    }
    private static function parse_path(string $path): array /** vector<int> */ {
        $out /** vector<int> */ = []; $length = string_byte_len($path);
        if ($length === 0) { return $out; }
        $start = 0;
        for ($i = 0; $i < $length; $i++) {
            if (string_byte_at($path,$i) === 46) { $out[] = Resource_Locations::ordinal(string_byte_slice($path,$start,$i-$start)); $start = $i+1; }
        }
        $out[] = Resource_Locations::ordinal(string_byte_slice($path,$start,$length-$start));
        return $out;
    }
    public static function project(Resource_Location $base, string $path): Resource_Location {
        $ordinals = $base->path(); foreach (Resource_Locations::parse_path($path) as $ordinal) { $ordinals[] = $ordinal; }
        return new Resource_Location($base->local,$ordinals);
    }
    public static function prefix(string $prefix, string $path): string {
        $result = $path;
        if ($prefix !== '') { $result = $prefix; if ($path !== '') { $result .= '.' . $path; } }
        return $result;
    }
    public static function parameter_key(int $position, string $path): string { return $position . ':' . $path; }
    public static function parameter_parts(string $key): Parameter_Endpoint {
        $separator = -1; $length = string_byte_len($key);
        for ($i = 0; $i < $length; $i++) {
            if (string_byte_at($key,$i) === 58) {
                if ($separator !== -1) { throw new \LogicException('Unknown ownership alias endpoint'); }
                $separator = $i;
            }
        }
        if ($separator < 0) { throw new \LogicException('Unknown ownership alias endpoint'); }
        $position = Resource_Locations::ordinal(string_byte_slice($key,0,$separator));
        $path = string_byte_slice($key,$separator+1,$length-$separator-1); Resource_Locations::parse_path($path);
        return new Parameter_Endpoint($position,$path);
    }
    public static function distinct_pair(string $left, string $right): Distinct_Endpoints {
        $first = $right; $second = $left;
        if (Resource_Locations::less($left,$right)) { $first = $left; $second = $right; }
        return new Distinct_Endpoints($first,$second);
    }
    public static function distinct_key(Distinct_Endpoints $pair): string { return $pair->left . '|' . $pair->right; }
    public static function place(\check_bodies\Place $place): Resource_Location {
        $path /** vector<int> */ = [];
        for ($i = 0; $i < $place->size(); $i++) { $projection = $place->at($i); if ($projection->kind !== \check_bodies\PROJECTION_FIELD) { break; } $path[] = $projection->operand; }
        return new Resource_Location($place->local_id,$path);
    }
    public static function overlaps(Resource_Location $left, Resource_Location $right): bool {
        if ($left->local !== $right->local) { return false; }
        $count = $left->size(); if ($right->size() < $count) { $count = $right->size(); }
        for ($i = 0; $i < $count; $i++) { if ($left->at($i) !== $right->at($i)) { return false; } }
        return true;
    }
    public static function diagnostic(\check_bodies\Checked_Body $body, int $node, string $reason): \resolve_types\Annotation_Diagnostic {
        $frontend = $body->input->owner->source_frontend(); $syntax = $frontend->tree->row($node);
        return new \resolve_types\Annotation_Diagnostic($frontend->tokens->source->path,(int)$syntax->start,(int)$syntax->length,$reason);
    }
}
