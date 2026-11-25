<?php
declare(strict_types=1);
namespace load_runtime;

/** Explicit catalog input path. Reuse authoritative definitions only for exact equal content. */
final class Language_Types {
    public function __construct(private readonly ?\type_model\Type_Catalog $previous) {}
    public function read(string $path): \type_model\Type_Catalog {
        $content = fs_read_text($path);
        if ($this->previous !== null) {
            if ($this->previous->content_key === 'catalog-v1:' . $content) { return $this->previous; }
        }
        return Catalog_Syntax::parse($content);
    }
}
