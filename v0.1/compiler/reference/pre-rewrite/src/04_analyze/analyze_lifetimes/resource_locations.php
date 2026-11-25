<?php
declare(strict_types=1);

/*
 * Role: Map checked places and static resource leaves to exact body-local identities.
 * Used by: Allocation_Flow; ownership selection and result validation
 * Call map: locals()/parameters() -> paths(); place(); overlaps()
 *   project()/prefix()/parameter_parts() own exact path and endpoint encoding.
 */
namespace analyze_lifetimes;

final class Resource_Locations
{
    /** Share type paths while allocating only the leaves used by this body's bindings. */
    public static function locals(\check_bodies\Checked_Body $body): array
    {
        $out = [];
        foreach ($body->names->locals as $index => $local) {
            foreach (self::paths($body->definition_for($body->local_type_for($index + 1))) as $path) {
                $location = new resource_location($index + 1, $path);
                $out[$location->key()] = $location;
            }
        }
        return $out;
    }

    /** Resource-bearing parameter positions use the same paths as local flow and join coverage. */
    public static function parameters(\check_bodies\Checked_Body $body): array
    {
        $parameters = [];
        for ($index = 0; $index < $body->entry_parameter_count(); ++$index) {
            $paths = self::paths($body->definition_for($body->local_type_for($index + 1)));
            if ($paths !== []) {
                $parameters[$index] = $paths;
            }
        }
        return $parameters;
    }

    /** A descriptor is its own resource leaf; records expose their accepted static leaf paths. */
    public static function paths(\type_model\named_type_definition $definition): array
    {
        return $definition->resource !== null ? [[]] : $definition->resource_paths;
    }

    /** Exact ordered field ordinals; the empty path denotes the root descriptor. */
    public static function path_key(array $path): string
    {
        return implode('.', $path);
    }

    /** Project an accepted summary path onto an existing caller location. */
    public static function project(resource_location $base, string|int $path): resource_location
    {
        $path = (string)$path;
        return new resource_location($base->local, [...$base->path, ...($path === '' ? [] : array_map('intval', explode('.', $path)))]);
    }

    /** Compose relative paths without creating an empty component at either end. */
    public static function prefix(string|int $prefix, string|int $path): string
    {
        return $prefix === '' ? (string)$path : ($path === '' ? (string)$prefix : $prefix . '.' . $path);
    }

    public static function parameter_key(int $position, string|int $path): string
    {
        return $position . ':' . $path;
    }

    /** Decode an endpoint at summary acceptance; reject ambiguous spellings and overflowing ordinals. */
    public static function parameter_parts(string $key): array
    {
        if (!preg_match('/^(0|[1-9][0-9]*):((?:0|[1-9][0-9]*)(?:\.(?:0|[1-9][0-9]*))*)?$/D', $key, $parts)) {
            throw new \LogicException('Unknown ownership alias endpoint');
        }
        $path = $parts[2] ?? '';
        foreach ([$parts[1], ...($path === '' ? [] : explode('.', $path))] as $ordinal) {
            if ((string)(int)$ordinal !== $ordinal) {
                throw new \LogicException('Unknown ownership alias endpoint ordinal');
            }
        }
        return [(int)$parts[1], $path];
    }

    /** One canonical unordered pair for both inference and lifecycle projection. */
    public static function distinct_pair(string $left, string $right): array
    {
        return strcmp($left, $right) < 0 ? [$left, $right] : [$right, $left];
    }

    public static function distinct_key(array $pair): string
    {
        return implode('|', $pair);
    }

    /** An element borrow depends on its descriptor, stopping before the dynamic projection. */
    public static function place(\check_bodies\place $place): resource_location
    {
        $path = [];
        foreach ($place->projections as $projection) {
            if ($projection->kind !== \check_bodies\projection_kind::field) {
                break;
            }
            $path[] = $projection->operand;
        }
        return new resource_location($place->local_id, $path);
    }

    public static function overlaps(resource_location $left, resource_location $right): bool
    {
        $count = min(count($left->path), count($right->path));
        return ($left->local === $right->local)
            && (array_slice($left->path, 0, $count) === array_slice($right->path, 0, $count));
    }

    public static function fail(\check_bodies\Checked_Body $body, int $node_id, string $message): never
    {
        $node = $body->owner->frontend->syntax->nodes[$node_id - 1];
        $source = $body->owner->frontend->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path, $node->start, $node->length, $message);
    }
}
