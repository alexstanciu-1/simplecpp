<?php
declare(strict_types=1);
namespace parse;

// Value views: source-local IDs, never semantic IDs or retained tree copies.
// parameter_parts.reference is a syntax tag; zero means no reference modifier.

/** @scpp-struct */
final class function_parts {
    public int $name_id /** uint32 */ = 0;
    public int $parameters_id /** uint32 */ = 0;
    public int $return_type_id /** uint32 */ = 0;
    public int $body_id /** uint32 */ = 0;
}

/** @scpp-struct */
final class local_declaration_parts {
    public int $variable_id /** uint32 */ = 0;
    public int $type_syntax_id /** uint32 */ = 0;
    public int $initializer_id /** uint32 */ = 0;
}

/** @scpp-struct */
final class parameter_parts {
    public int $variable_id /** uint32 */ = 0;
    public int $type_syntax_id /** uint32 */ = 0;
    public int $reference /** uint32 */ = 0;
}

/** @scpp-struct */
final class assignment_parts {
    public int $target_id /** uint32 */ = 0;
    public int $value_id /** uint32 */ = 0;
}

/** @scpp-struct */
final class control_parts {
    public int $condition /** uint32 */ = 0;
    public int $body /** uint32 */ = 0;
    public int $alternative /** uint32 */ = 0;
}

/** @scpp-struct */
final class struct_parts {
    public int $name_id /** uint32 */ = 0;
    public int $first_member_id /** uint32 */ = 0;
}

/** @scpp-struct */
final class field_declaration_parts {
    public int $type_syntax_id /** uint32 */ = 0;
    public int $variable_id /** uint32 */ = 0;
    public int $extent_id /** uint32 */ = 0;
}

/** @scpp-struct */
final class template_parts {
    public int $parameters_id /** uint32 */ = 0;
    public int $declaration_id /** uint32 */ = 0;
}

/** @scpp-struct */
final class template_parameter_parts {
    public int $name_id /** uint32 */ = 0;
    public int $type_syntax_id /** uint32 */ = 0;
}

/** @scpp-struct */
final class template_application_parts {
    public int $name_id /** uint32 */ = 0;
    public int $first_argument_id /** uint32 */ = 0;
}

/** @scpp-struct */
final class constant_parts {
    public int $name_id /** uint32 */ = 0;
    public int $type_syntax_id /** uint32 */ = 0;
    public int $initializer_id /** uint32 */ = 0;
}
