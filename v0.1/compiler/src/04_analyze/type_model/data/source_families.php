<?php
declare(strict_types=1);
namespace type_model;

/** Source names are separate from provider identity; this grants no native layout. */
final class Family_Declaration {
    public readonly string $provider;
    public readonly string $id;
    public readonly string $name;
    public readonly string $namespace_name;
    private array $language_types /** hash<Type_Reference> */ = [];
    public function __construct(public readonly Family_Definition $definition, array $language_types /** hash<Type_Reference> */) {
        $exposure = $definition->language_type;
        if ($exposure === null) { throw new \InvalidArgumentException('Family requires source exposure'); }
        $this->provider = $definition->provider; $this->id = $definition->id;
        $this->name = $exposure->name(); $this->namespace_name = $exposure->namespace_name();
        foreach ($language_types as $key => $name) {
            if ($name->kind !== \type_model\TYPE_REFERENCE_NAMED) { throw new \InvalidArgumentException('Provider mapping requires a language name'); }
            $this->language_types[$key] = $name;
        }
    }
    public function find_language_type(string $key): ?Type_Reference {
        if (isset($this->language_types[$key])) { return $this->language_types[$key]; }
        return null;
    }
}

/** A member keeps its declared owner and signature; it never receives a fake AST. */
final class Family_Method {
    public readonly string $provider;
    public readonly string $id;
    public readonly string $name;
    public readonly string $namespace_name;
    public function __construct(public readonly Family_Declaration $family, public readonly Family_Operation $operation) {
        $exposure = $operation->expose_as;
        if ($exposure === null) { throw new \InvalidArgumentException('Method requires source exposure'); }
        $this->provider = $family->provider; $this->id = $operation->id;
        $this->name = $exposure->name(); $this->namespace_name = $family->namespace_name;
    }
}
