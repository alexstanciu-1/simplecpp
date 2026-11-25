<?php
declare(strict_types=1);

/*
 * Role: Exact portable identity projected from accepted nominal provenance.
 * Used by: Source_Identities; source export preparation
 * Flow: source/provider/language identity -> tagged exact key, independent of local IDs.
 */
namespace resolve_types;

/** Keys are complete tagged encodings, never content digests or display names. */
final class export_type_identity
{
    public readonly string $key;

    /** Nested keys embed tagged components, avoiding repeated JSON-string escaping at each generic depth. */
    public function __construct(public readonly array $parts, public readonly bool $source)
    {
        $this->key = json_encode($parts, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
