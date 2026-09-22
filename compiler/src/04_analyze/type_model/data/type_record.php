<?php
declare(strict_types=1);
namespace type_model;
/** Immutable canonical row; pending identity does not imply a declaration or representation. */
final class Type_Record {
    public function __construct(public readonly string $name, public readonly string $namespace_name,
        public readonly int $representation_id, public readonly bool $declared,
        public readonly ?Named_Definition $definition) {}
}
