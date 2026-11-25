<?php
declare(strict_types=1);
namespace resolve_types;

/** Read-only view of accepted definitions; providers take precedence over source types. */
final class Definition_View {
    public function __construct(private readonly \type_model\Type_Catalog $catalog, private readonly ?\type_model\Type_Store $types = null) {}
    public function content_key(): string { return $this->catalog->content_key; }
    public function representation_scope(): string { return $this->catalog->representation_scope; }
    public function entry_return_type(): \type_model\Named_Definition { return $this->catalog->entry_return_type; }
    public function find_type(string $name, string $namespace_name): ?\type_model\Named_Definition {
        $provided=$this->catalog->find_type($name,$namespace_name);
        if ($provided!==null) { return $provided; }
        $types=$this->types;
        if ($types===null) { return null; }
        $id=$types->find_type($name,$namespace_name);
        if ($id===0) { return null; }
        return $types->definition_for_type($id);
    }
}
