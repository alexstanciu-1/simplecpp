<?php
declare(strict_types=1);

/*
 * Role: Source-visible family declarations and members, independent of ABI readiness.
 * Used by: Family_Adapter; declaration collection; symbolic template checking
 * Flow: validated family + explicit language mappings -> source symbol payloads
 */
namespace type_model;

/** No storage/layout claim: the family remains a declaration until specialization is prepared. */
final class family_declaration
{
    public readonly string $provider;
    public readonly string $id;
    public readonly string $name;
    public readonly string $namespace_name;

    /** Preserve exact provider identity while exposing the separately declared source name.
     * @param array<string, named_type_reference> $language_types Exact provider/type mapping keys. */
    public function __construct(public readonly family_definition $definition, public readonly array $language_types)
    {
        $exposure = $definition->language_type ?? throw new \InvalidArgumentException('Family requires source exposure');
        $this->provider = $definition->provider;
        $this->id = $definition->id;
        $this->name = $exposure->name;
        $this->namespace_name = $exposure->namespace_name;
    }
}

/** Member identity is scoped by the family symbol; no synthetic source function is created. */
final class family_method
{
    public readonly string $provider;
    public readonly string $id;
    public readonly string $name;
    public readonly string $namespace_name;

    /** Retain the declared semantic operation and its owner without a prepared implementation. */
    public function __construct(public readonly family_declaration $family, public readonly family_operation $operation)
    {
        $exposure = $operation->expose_as ?? throw new \InvalidArgumentException('Method requires source exposure');
        $this->provider = $family->provider;
        $this->id = $operation->id;
        $this->name = $exposure->name;
        $this->namespace_name = $family->namespace_name;
    }
}
