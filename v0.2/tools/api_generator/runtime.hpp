#pragma once
#include "value.hpp"

namespace scpp::api_generation {

struct integer_type {
    std::string name, representation;
    bool is_signed;
    unsigned bits;
};
struct native_integer {
    std::string cpp;
    bool is_signed;
    unsigned digits;
};
struct scalar_conversion {
    std::string target, source;
    bool native_source, lossless;
    std::string implementation = "checked_integer_cast";
    bool can_fail = true;
};
struct scalar_operand {
    std::string cpp, identity;
    bool native;
};
struct scalar_operation {
    std::string operation, symbol, implementation, promotion, form;
    scalar_operand left, right;
    std::string execution, result;
};
struct simple_value_type {
    std::string name, kind, representation;
    std::vector<std::string> operations;
};
struct string_plan {
    std::string name, count_type, header;
    std::vector<scalar_operation> operations;
};
struct vector_plan {
    std::string name, element, count_type, native_value;
    bool native_index = false;
    std::vector<std::string> operations, casts_from;
};
struct carrier_plan {
    std::string name, family, payload, default_state;
    bool implicit_extraction = true;
};
struct runtime_plan {
    std::string carriers_header;
    std::vector<carrier_plan> carriers;
    string_plan string;
    std::string vectors_header;
    std::vector<vector_plan> vectors;
    std::vector<simple_value_type> values;
    std::map<std::string, std::string> source_types, aliases;
    std::vector<std::string> short_aliases;
    std::string boolean_type, floating_type, values_header;
    std::vector<integer_type> types;
    std::vector<native_integer> native_inputs;
    std::vector<scalar_conversion> conversions;
    std::vector<scalar_operation> operations;
    std::string types_header, operators_header, short_names_header, native_bridge_header;
};

// Physical preparation only: no source admission, expression order or file I/O.
runtime_plan prepare_runtime(const value &metadata, const value &publication);
value describe(const runtime_plan &plan);
// Emission consumes resolved facts; it does not choose promotion or admission.
std::map<std::string, std::string> emit_runtime(const runtime_plan &plan);
// Exact deleted overloads fence off standard numeric conversions for unrequested inputs.
const std::vector<std::string> &native_cpp_spellings();
}
