<?php
declare(strict_types=1);

/*
 * Role: Read the accepted source and provider definitions through one fixed view.
 * Used by: annotation workers and joins
 * Flow: provider catalog + accepted type store -> definition lookup
 */
namespace resolve_types;

use type_model\Type_Store;

/** Fixed merged definition view; provider configuration identity stays separate from source declarations. */
final class Definition_View
{
    public readonly string $content_key;
    public readonly string $representation_scope;
    public readonly \type_model\named_type_definition $entry_return_type;

    /** Capture accepted canonical source definitions alongside the unchanged provider catalog. */
    public function __construct(private readonly \type_model\Type_Catalog $catalog, private readonly Type_Store $types)
    {
        $this->content_key = $catalog->content_key;
        $this->representation_scope = $catalog->representation_scope;
        $this->entry_return_type = $catalog->entry_return_type;
    }

    /** Read authoritative provider definitions first, then accepted source declarations. */
    public function find_type(string $name, string $namespace_name): ?\type_model\named_type_definition
    {
        $provided = $this->catalog->find_type($name, $namespace_name);
        if ($provided !== null) {
            return $provided;
        }
        $id = $this->types->find_type($name, $namespace_name);
        return $id === 0 ? null : $this->types->definition_for_type($id);
    }
}

