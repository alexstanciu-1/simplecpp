<?php
declare(strict_types=1);
namespace load_runtime;

/** Pure composition inputs. The adapter must supply the verified content-derived key; these records grant no file trust. */
final class Package_Composition_Input {
    public function __construct(public readonly Package_Context $context, public readonly Package_Manifest $manifest,
        public readonly Package_Metadata $metadata, public readonly string $catalog_content_key,
        public readonly ?Runtime_Package $previous = null) {}
}
final class Package_Contract_Set {
    public function __construct(public readonly array $types /** hash<Runtime_Type> */,
        public readonly \type_model\Type_Catalog $catalog, public readonly array $families /** hash<\type_model\Storage_Family> */,
        public readonly array $callables /** vector<\type_model\Runtime_Callable> */) {}
}
