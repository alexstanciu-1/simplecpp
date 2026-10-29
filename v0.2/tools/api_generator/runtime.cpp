#include "runtime.hpp"
#include <algorithm>
#include <limits>
#include <set>
#include <tuple>

namespace scpp::api_generation {
namespace {
void require(bool ok, const std::string &message) {
    if (!ok) throw std::runtime_error(message);
}
void exact(const value &v, const std::string &fields, const std::string &where) {
    fields_only(v, fields, where);
    // Required fields are read with at() at their owning validation site.
}
void literal(const value &v, const std::string &field, const std::string &expected) {
    require(v.at(field).text() == expected, "Unsupported " + field);
}
const std::vector<value> &array(const value &v) {
    require(v.type == value::kind::array, "Expected metadata array");
    return v.items;
}
bool boolean(const value &v) {
    require(v.type == value::kind::boolean && (v.scalar == "true" || v.scalar == "false"), "Expected metadata boolean");
    return v.scalar == "true";
}
unsigned width(const value &v) {
    require(v.type == value::kind::number, "Expected integer width");
    for (unsigned n : {8u, 16u, 32u, 64u}) if (v.scalar == std::to_string(n)) return n;
    throw std::runtime_error("Unsupported integer width");
}
void version(const value &v) {
    require(v.at("version").type == value::kind::number && v.at("version").scalar == "1", "Unsupported metadata version");
}
template<class T> native_integer native(const char *cpp) {
    return {cpp, std::numeric_limits<T>::is_signed, std::numeric_limits<T>::digits};
}
const std::vector<native_integer> &native_types() {
    // C++ fundamental type facts, not another runtime type/promotion registry.
    static const std::vector<native_integer> types = {
        native<int>("int"), native<unsigned int>("unsigned int"),
        native<long>("long"), native<unsigned long>("unsigned long"),
        native<long long>("long long"), native<unsigned long long>("unsigned long long")};
    return types;
}
unsigned digits(const integer_type &t) { return t.bits - unsigned(t.is_signed); }
bool fits(const integer_type &to, bool signed_source, unsigned source_digits) {
    return (!signed_source || to.is_signed) && digits(to) >= source_digits;
}
std::string header(const value &v) {
    auto path = v.text();
    require(path.starts_with("scpp/") && path.ends_with(".hpp") && path.find("..") == std::string::npos,
            "Invalid public header path");
    require(path.find("//") == std::string::npos && path.find("/.") == std::string::npos,
            "Public header paths must be normalized");
    require(std::all_of(path.begin(), path.end(), [](unsigned char c) {
        return (c >= 'a' && c <= 'z') || (c >= '0' && c <= '9') || c == '_' || c == '/' || c == '.';
    }), "Invalid public header path");
    return path;
}
value flag(bool v) { value out; out.type = value::kind::boolean; out.scalar = v ? "true" : "false"; return out; }
value number(unsigned v) { value out; out.type = value::kind::number; out.scalar = std::to_string(v); return out; }
}

const std::vector<std::string> &native_cpp_spellings() {
    static const std::vector<std::string> names = {
        "int", "unsigned int", "long", "unsigned long", "long long", "unsigned long long",
        "short", "unsigned short", "bool", "char", "signed char", "unsigned char",
        "wchar_t", "char8_t", "char16_t", "char32_t", "float", "double", "long double"};
    return names;
}

runtime_plan prepare_runtime(const value &metadata, const value &publication) {
    exact(metadata, "version types representation construction promotion_policies operations values source_types aliases floating scalar_casts string vector carriers ownership", "runtime metadata");
    exact(publication, "version namespace types operators headers values short_aliases string vectors carriers ownership", "runtime publication");
    version(metadata); version(publication); literal(publication, "namespace", "scpp");
    const auto &rep = metadata.at("representation");
    exact(rep, "binding default_initialization", "representation");
    literal(rep, "binding", "fixed_width_integer"); literal(rep, "default_initialization", "zero");
    const auto &construction = metadata.at("construction");
    exact(construction, "public_integer_inputs native_integer_inputs native_conversion explicit", "construction");
    literal(construction, "public_integer_inputs", "checked"); literal(construction, "native_conversion", "checked");
    require(boolean(construction.at("explicit")), "Integer constructors must be explicit");
    std::map<std::string, integer_type> types;
    const auto &records = metadata.at("types");
    require(records.type == value::kind::object && !records.fields.empty(), "Expected integer type definitions");
    for (const auto &[name, record] : records.fields) {
        exact(record, "signed bits", "integer type");
        bool signed_type = boolean(record.at("signed")); unsigned bits = width(record.at("bits"));
        const auto prefix = signed_type ? "int" : "uint";
        require(name == prefix + std::to_string(bits), "Integer identity disagrees with width/signedness");
        auto macro = std::string(signed_type ? "__INT" : "__UINT") + std::to_string(bits) + "_TYPE__";
        types.emplace(name, integer_type{name, macro, signed_type, bits});
    }
    const auto &policies = metadata.at("promotion_policies");
    exact(policies, "integer_domain_maximum", "promotion policies");
    const auto &policy = policies.at("integer_domain_maximum");
    exact(policy, "same_type different_signed different_unsigned signed_and_unsigned_below_64_bits signed_and_uint64", "promotion");
    literal(policy, "same_type", "preserve"); literal(policy, "signed_and_uint64", "reject");
    for (const auto &[field, expected_signed] : std::vector<std::pair<std::string, bool>>{
            {"different_signed", true}, {"different_unsigned", false}, {"signed_and_unsigned_below_64_bits", true}}) {
        const auto found = types.find(policy.at(field).text());
        require(found != types.end() && found->second.bits == 64 && found->second.is_signed == expected_signed,
                "Domain maximum must be a registered 64-bit type with matching signedness");
    }
    const auto &operations = metadata.at("operations");
    // Binding capabilities of the handwritten runtime. Policies and publication
    // remain metadata-owned; arbitrary code strings cannot become implementations.
    struct operation_binding {
        std::string symbol, implementation, promotion, result, form;
    };
    const std::map<std::string, operation_binding> bindings = {
        {"add", {"+", "integer_add", "integer_domain_maximum", "execution_type", "binary"}},
        {"subtract", {"-", "integer_subtract", "integer_domain_maximum", "execution_type", "binary"}},
        {"multiply", {"*", "integer_multiply", "integer_domain_maximum", "execution_type", "binary"}},
        {"divide", {"/", "integer_divide", "integer_domain_maximum", "execution_type", "binary"}},
        {"remainder", {"%", "integer_remainder", "integer_domain_maximum", "execution_type", "binary"}},
        {"bit_and", {"&", "integer_bit_and", "integer_domain_maximum", "execution_type", "binary"}},
        {"bit_or", {"|", "integer_bit_or", "integer_domain_maximum", "execution_type", "binary"}},
        {"bit_xor", {"^", "integer_bit_xor", "integer_domain_maximum", "execution_type", "binary"}},
        {"equal", {"==", "integer_equal", "exact_integer_comparison", "boolean", "binary"}},
        {"not_equal", {"!=", "integer_not_equal", "exact_integer_comparison", "boolean", "binary"}},
        {"less", {"<", "integer_less", "exact_integer_comparison", "boolean", "binary"}},
        {"less_equal", {"<=", "integer_less_equal", "exact_integer_comparison", "boolean", "binary"}},
        {"greater", {">", "integer_greater", "exact_integer_comparison", "boolean", "binary"}},
        {"greater_equal", {">=", "integer_greater_equal", "exact_integer_comparison", "boolean", "binary"}},
        {"positive", {"+", "integer_positive", "preserve_operand", "operand_type", "unary"}},
        {"negate", {"-", "integer_negate", "preserve_operand", "operand_type", "unary"}},
        {"bit_not", {"~", "integer_bit_not", "preserve_operand", "operand_type", "unary"}},
        {"shift_left", {"<<", "integer_shift_left", "left_with_checked_count", "left_type", "binary"}},
        {"shift_right", {">>", "integer_shift_right", "left_with_checked_count", "left_type", "binary"}},
        {"add_assign", {"+=", "integer_add", "integer_domain_maximum", "left_reference", "compound"}},
        {"subtract_assign", {"-=", "integer_subtract", "integer_domain_maximum", "left_reference", "compound"}},
        {"multiply_assign", {"*=", "integer_multiply", "integer_domain_maximum", "left_reference", "compound"}},
        {"divide_assign", {"/=", "integer_divide", "integer_domain_maximum", "left_reference", "compound"}},
        {"remainder_assign", {"%=", "integer_remainder", "integer_domain_maximum", "left_reference", "compound"}},
        {"bit_and_assign", {"&=", "integer_bit_and", "integer_domain_maximum", "left_reference", "compound"}},
        {"bit_or_assign", {"|=", "integer_bit_or", "integer_domain_maximum", "left_reference", "compound"}},
        {"bit_xor_assign", {"^=", "integer_bit_xor", "integer_domain_maximum", "left_reference", "compound"}},
        {"prefix_increment", {"++", "integer_increment", "preserve_operand", "left_reference", "prefix"}},
        {"postfix_increment", {"++", "integer_increment", "preserve_operand", "old_value", "postfix"}},
        {"prefix_decrement", {"--", "integer_decrement", "preserve_operand", "left_reference", "prefix"}},
        {"postfix_decrement", {"--", "integer_decrement", "preserve_operand", "old_value", "postfix"}},
        {"shift_left_assign", {"<<=", "integer_shift_left", "left_with_checked_count", "left_reference", "compound"}},
        {"shift_right_assign", {">>=", "integer_shift_right", "left_with_checked_count", "left_reference", "compound"}},
    };
    require(operations.type == value::kind::object && !operations.fields.empty(), "Expected integer operations");
    for (const auto &[name, operation] : operations.fields) {
        require(bindings.contains(name), "Unknown integer operation binding");
        exact(operation, "public_operator promotion implementation result native_arguments form", "integer operation");
        const auto &binding = bindings.at(name);
        literal(operation, "public_operator", binding.symbol);
        literal(operation, "implementation", binding.implementation);
        literal(operation, "promotion", binding.promotion);
        literal(operation, "result", binding.result);
        literal(operation, "form", binding.form);
        if (operation.has("native_arguments")) {
            require(binding.promotion != "exact_integer_comparison", "Native comparison adapters require explicit runtime casts");
            require(binding.promotion == "integer_domain_maximum", "Native adapters require arithmetic normalization");
            const auto &native_policy = operation.at("native_arguments");
            exact(native_policy, "policy inputs", "native arguments");
            literal(native_policy, "policy", "type_range");
            literal(native_policy, "inputs", "native_integer_inputs");
        }
    }

    runtime_plan plan;
    const auto &values = metadata.at("values");
    require(values.type == value::kind::object, "Expected value definitions");
    std::map<std::string, simple_value_type> value_types;
    for (const auto &[name, record] : values.fields) {
        exact(record, "kind representation construction condition operations", "simple value");
        const auto kind = record.at("kind").text();
        require((kind == "boolean" && name == "bool_t") || (kind == "null" && name == "null_t"),
                "Unsupported simple value identity");
        literal(record, "representation", kind == "boolean" ? "native_bool" : "empty");
        literal(record, "construction", kind == "boolean" ? "explicit" : "default");
        if (kind == "boolean") literal(record, "condition", "explicit_native_bool");
        else require(!record.has("condition"), "Null has no condition conversion");
        simple_value_type type{name, kind, record.at("representation").text(), {}};
        std::set<std::string> seen;
        for (const auto &entry : array(record.at("operations"))) {
            const auto op = entry.text();
            require(op == "equal" || op == "not_equal" || (kind == "boolean" && op == "not"),
                    "Unsupported simple value operation");
            require(seen.insert(op).second, "Duplicate simple value operation");
            type.operations.push_back(op);
        }
        std::sort(type.operations.begin(), type.operations.end());
        value_types.emplace(name, type);
    }
    const auto &floating = metadata.at("floating");
    exact(floating, "type representation native_inputs integer_inputs mixed_integer division_by_zero operations", "floating metadata");
    literal(floating, "type", "float64"); literal(floating, "representation", "ieee_binary64");
    literal(floating, "integer_inputs", "native_integer_inputs");
    literal(floating, "mixed_integer", "exact_type_range"); literal(floating, "division_by_zero", "throw");
    require(std::numeric_limits<double>::is_iec559 && std::numeric_limits<double>::radix == 2
        && std::numeric_limits<double>::digits == 53 && std::numeric_limits<double>::max_exponent == 1024,
        "float64 requires IEEE binary64 double");
    const auto floating_name = floating.at("type").text();
    value_types.emplace(floating_name, simple_value_type{floating_name, "floating", "double", {}});
    const std::set<std::string> floating_capabilities = {
        "add",
        "subtract",
        "multiply",
        "divide",
        "equal",
        "not_equal",
        "less",
        "less_equal",
        "greater",
        "greater_equal",
        "positive",
        "negate",
        "add_assign",
        "subtract_assign",
        "multiply_assign",
        "divide_assign",
        "prefix_increment",
        "postfix_increment",
        "prefix_decrement",
        "postfix_decrement",
    };
    const auto &floating_operations = floating.at("operations");
    require(floating_operations.type == value::kind::object, "Expected floating operation definitions");
    for (const auto &[name, operation] : floating_operations.fields) {
        require(floating_capabilities.contains(name), "Unsupported floating operation");
        exact(operation, "public_operator promotion implementation result native_arguments form", "floating operation");
        const auto &binding = bindings.at(name);
        literal(operation, "public_operator", binding.symbol); literal(operation, "form", binding.form);
        literal(operation, "result", binding.result); literal(operation, "promotion", "floating_exact_integer");
        literal(operation, "implementation", "floating_" + binding.implementation.substr(8));
        if (operation.has("native_arguments")) {
            require(binding.promotion == "integer_domain_maximum", "Native floating adapters require arithmetic");
            const auto &native_policy = operation.at("native_arguments");
            exact(native_policy, "policy inputs", "native arguments");
            literal(native_policy, "policy", "type_range"); literal(native_policy, "inputs", "native_integer_inputs");
        }
    }
    std::set<std::string> selected_values;
    for (const auto &entry : array(publication.at("values"))) {
        const auto name = entry.text();
        require(value_types.contains(name), "Unknown published value type");
        require(selected_values.insert(name).second, "Duplicate published value type");
        plan.values.push_back(value_types.at(name));
        if (value_types.at(name).kind == "boolean") plan.boolean_type = name;
        if (value_types.at(name).kind == "floating") plan.floating_type = name;
    }
    std::sort(plan.values.begin(), plan.values.end(), [](const auto &a, const auto &b) { return a.name < b.name; });
    for (const auto &type : plan.values) if (!type.operations.empty())
        require(!plan.boolean_type.empty(), "Value operations require published boolean");

    std::set<std::string> selected;
    for (const auto &item : array(publication.at("types"))) {
        const auto name = item.text();
        require(types.contains(name), "Unknown published integer type");
        require(selected.insert(name).second, "Duplicate published type");
        plan.types.push_back(types.at(name));
    }
    require(!selected.empty() || !plan.values.empty(), "Empty runtime publication");
    const auto &source_types = metadata.at("source_types");
    exact(source_types, "int bool null float", "source types");
    for (const auto &[source, target] : source_types.fields) {
        const auto name = target.text();
        if (source == "int") require(types.contains(name) && types.at(name).is_signed,
                                     "Source int must name a registered signed integer");
        else require(value_types.contains(name) && value_types.at(name).kind == (source == "bool" ? "boolean" : source == "float" ? "floating" : "null"),
                     "Source value identity disagrees with kind");
        if (selected.contains(name) || selected_values.contains(name)) plan.source_types.emplace(source, name);
    }
    const auto &aliases = metadata.at("aliases");
    exact(aliases, "int_t float_t", "configured aliases");
    for (const auto &[alias, source] : aliases.fields) {
        require(source.text() == (alias == "int_t" ? "int" : "float"), "Alias must follow the configured int/float default");
        require(source_types.has(source.text()), "Alias requires a configured source default");
        if (plan.source_types.contains(source.text())) plan.aliases.emplace(alias, plan.source_types.at(source.text()));
    }
    std::set<std::string> short_aliases;
    for (const auto &entry : array(publication.at("short_aliases"))) {
        const auto alias = entry.text();
        require(aliases.has(alias), "Unknown short alias");
        require(short_aliases.insert(alias).second, "Duplicate short alias");
        if (plan.aliases.contains(alias)) plan.short_aliases.push_back(alias);
    }
    std::sort(plan.short_aliases.begin(), plan.short_aliases.end());
    std::sort(plan.types.begin(), plan.types.end(), [](const auto &a, const auto &b) { return a.name < b.name; });
    std::set<std::string> inputs;
    for (const auto &item : array(construction.at("native_integer_inputs"))) {
        auto name = item.text();
        auto found = std::find_if(native_types().begin(), native_types().end(), [&](const auto &t) { return t.cpp == name; });
        require(found != native_types().end(), "Unsupported native integer input");
        require(inputs.insert(name).second, "Duplicate native integer input");
        plan.native_inputs.push_back(*found);
    }
    require(!inputs.empty(), "Empty native integer input set");
    std::sort(plan.native_inputs.begin(), plan.native_inputs.end(), [](const auto &a, const auto &b) { return a.cpp < b.cpp; });
    for (const auto &to : plan.types) {
        for (const auto &from : plan.native_inputs)
            plan.conversions.push_back({to.name, from.cpp, true, fits(to, from.is_signed, from.digits), "checked_integer_cast", !fits(to, from.is_signed, from.digits)});
        for (const auto &from : plan.types) if (from.name != to.name)
            plan.conversions.push_back({to.name, from.name, false, fits(to, from.is_signed, digits(from)), "checked_integer_cast", !fits(to, from.is_signed, digits(from))});
    }
    std::set<std::string> floating_inputs;
    for (const auto &entry : array(floating.at("native_inputs"))) {
        const auto input = entry.text();
        require(input == "float" || input == "double", "Unsupported native floating constructor input");
        require(floating_inputs.insert(input).second, "Duplicate native floating input");
        if (!plan.floating_type.empty()) plan.conversions.push_back({plan.floating_type, input, true, true, "numeric_cast", false});
    }
    require(floating_inputs.contains("double"), "float64 requires its native double constructor");
    if (!plan.floating_type.empty()) for (const auto &input : plan.native_inputs)
        plan.conversions.push_back({plan.floating_type, input.cpp, true, input.digits <= 53, "numeric_cast", false});
    std::set<std::string> cast_policies;
    for (const auto &entry : array(metadata.at("scalar_casts"))) {
        const auto cast = entry.text();
        require(cast_policies.insert(cast).second, "Duplicate scalar cast policy");
        if (cast == "integer_to_floating_rounding" || cast == "floating_to_integer_truncate_checked") {
            if (plan.floating_type.empty()) continue;
            for (const auto &type : plan.types) {
                if (cast == "integer_to_floating_rounding")
                    plan.conversions.push_back({plan.floating_type, type.name, false, digits(type) <= 53, "numeric_cast", false});
                else plan.conversions.push_back({type.name, plan.floating_type, false, false, "floating_integer_cast", true});
            }
        } else if (cast == "numeric_to_boolean_nonzero" || cast == "boolean_to_numeric_01") {
            if (plan.boolean_type.empty()) continue;
            std::vector<std::string> numeric_types;
            for (const auto &type : plan.types) numeric_types.push_back(type.name);
            if (!plan.floating_type.empty()) numeric_types.push_back(plan.floating_type);
            for (const auto &name : numeric_types) {
                if (cast == "numeric_to_boolean_nonzero")
                    plan.conversions.push_back({plan.boolean_type, name, false, false, "truth_cast", false});
                else plan.conversions.push_back({name, plan.boolean_type, false, true, "numeric_cast", false});
            }
        } else throw std::runtime_error("Unsupported scalar cast policy");
    }
    if (metadata.has("string")) {
        const auto &record = metadata.at("string");
        exact(record, "type source_type storage count_type construction null_pointer text_casts operations", "string metadata");
        literal(record, "type", "string"); literal(record, "source_type", "string");
        literal(record, "storage", "private_std_string"); literal(record, "construction", "explicit");
        literal(record, "null_pointer", "reject");
        require(types.contains(record.at("count_type").text()) && types.at(record.at("count_type").text()).is_signed,
                "String count requires a signed integer type");
        std::set<std::string> capabilities;
        for (const auto &entry : array(record.at("operations"))) {
            const auto op = entry.text();
            require(std::set<std::string>{"add", "add_assign", "equal", "not_equal", "less", "less_equal", "greater", "greater_equal"}.contains(op),
                    "Unsupported string operation");
            require(capabilities.insert(op).second, "Duplicate string operation");
        }
        std::set<std::string> text_casts;
        for (const auto &entry : array(record.at("text_casts"))) {
            const auto cast = entry.text();
            require(cast == "decimal_integer" || cast == "decimal_float" || cast == "boolean_words", "Unsupported text cast policy");
            require(text_casts.insert(cast).second, "Duplicate text cast policy");
        }
        if (publication.has("string")) {
            const auto &request = publication.at("string");
            exact(request, "header operations casts", "string publication");
            plan.string = {record.at("type").text(), record.at("count_type").text(), header(request.at("header")), {}};
            require(selected.contains(plan.string.count_type), "String requires published count type");
            require(!plan.boolean_type.empty(), "String requires published boolean type");
            require(inputs.contains("long long"), "String count requires explicit native long long construction");
            plan.source_types.emplace(record.at("source_type").text(), plan.string.name);
            std::set<std::string> seen;
            for (const auto &entry : array(request.at("operations"))) {
                const auto op = entry.text();
                require(capabilities.contains(op), "String operation is not enabled by metadata");
                require(seen.insert(op).second, "Duplicate published string operation");
                const std::map<std::string, std::string> symbols = {{"add", "+"}, {"add_assign", "+="}, {"equal", "=="}, {"not_equal", "!="}, {"less", "<"}, {"less_equal", "<="}, {"greater", ">"}, {"greater_equal", ">="}};
                const auto name = plan.string.name;
                plan.string.operations.push_back({op, symbols.at(op), op == "add" || op == "add_assign" ? "string_append" : "string_compare",
                    "same_string", op == "add_assign" ? "compound" : "binary", {name, name, false}, {name, name, false}, name,
                    op == "add" || op == "add_assign" ? name : plan.boolean_type});
            }
            std::sort(plan.string.operations.begin(), plan.string.operations.end(), [](const auto &a, const auto &b) { return a.operation < b.operation; });
            seen.clear();
            for (const auto &entry : array(request.at("casts"))) {
                const auto target = entry.text();
                require(seen.insert(target).second, "Duplicate published text cast");
                const auto policy = selected.contains(target) ? "decimal_integer"
                    : target == plan.floating_type && !target.empty() ? "decimal_float"
                    : target == plan.boolean_type ? "boolean_words" : "";
                require(*policy != '\0', "Text cast requires a published numeric or boolean type");
                require(text_casts.contains(policy), "Text cast is not enabled by metadata");
                const bool words = target == plan.boolean_type;
                plan.conversions.push_back({target, plan.string.name, false, false,
                    words ? "parse_boolean_text" : "parse_decimal", true});
                plan.conversions.push_back({plan.string.name, target, false, target != plan.floating_type,
                    words ? "format_boolean_text" : "format_decimal", true});
            }
        }
    } else require(!publication.has("string"), "String publication requires metadata");
    std::sort(plan.conversions.begin(), plan.conversions.end(), [](const auto &a, const auto &b) {
        return std::pair{a.target, a.source} < std::pair{b.target, b.source};
    });
    std::set<std::tuple<std::string, std::string, std::string>> pairs;
    const auto operand = [&](const value &input, const value &operation) -> scalar_operand {
        if (input.type == value::kind::text) {
            const auto name = input.text();
            require(selected.contains(name) || (!plan.floating_type.empty() && name == plan.floating_type), "Operator requires published operand types");
            return {name, name, false};
        }
        exact(input, "native", "native operand");
        require(operation.has("native_arguments"), "Native arithmetic is not enabled");
        const auto cpp = input.at("native").text();
        const auto found = std::find_if(plan.native_inputs.begin(), plan.native_inputs.end(),
            [&](const auto &t) { return t.cpp == cpp; });
        require(found != plan.native_inputs.end(), "Unregistered native arithmetic input");
        const auto equivalent = std::find_if(types.begin(), types.end(), [&](const auto &entry) {
            return entry.second.is_signed == found->is_signed && digits(entry.second) == found->digits;
        });
        require(equivalent != types.end(), "Native range has no exact integer identity");
        return {cpp, equivalent->first, true};
    };
    const auto resolve = [&](const std::string &name, const scalar_operand &left, const scalar_operand &right) {
        require(!left.native || !right.native, "Native-only operators are not runtime overloads");
        require(pairs.emplace(name, left.cpp, right.cpp).second, "Duplicate operator request");
        const bool uses_float = !plan.floating_type.empty() && (left.identity == plan.floating_type || right.identity == plan.floating_type);
        const auto &operation = uses_float ? floating_operations.at(name) : operations.at(name);
        const auto promotion = operation.at("promotion").text();
        const auto form = operation.at("form").text();
        require(form != "compound" || !left.native, "Compound assignment requires a public left operand");
        if (promotion == "floating_exact_integer") {
            const bool comparison = operation.at("result").text() == "boolean";
            if (comparison) require(!plan.boolean_type.empty(), "Floating comparisons require published boolean");
            for (const auto &arg : {left, right}) if (!arg.identity.empty() && arg.identity != plan.floating_type)
                require(types.contains(arg.identity) && digits(types.at(arg.identity)) <= 53,
                        "Wide integer/floating operations require an explicit cast");
            // Every runtime adaptation must be admitted by the same cast policy.
            for (const auto &arg : {left, right}) if (!arg.identity.empty() && arg.identity != plan.floating_type)
                require(std::any_of(plan.conversions.begin(), plan.conversions.end(), [&](const auto &c) {
                    return c.target == plan.floating_type && c.source == arg.cpp && c.native_source == arg.native;
                }), "Floating normalization requires an admitted cast");
            if (form == "compound" && left.identity != plan.floating_type)
                require(std::any_of(plan.conversions.begin(), plan.conversions.end(), [&](const auto &c) {
                    return c.target == left.identity && c.source == plan.floating_type && !c.native_source;
                }), "Floating write-back requires an admitted cast");
            plan.operations.push_back({name, operation.at("public_operator").text(), operation.at("implementation").text(),
                promotion, form, left, right, plan.floating_type, comparison ? plan.boolean_type
                    : form == "compound" ? left.identity : plan.floating_type});
            return;
        }
        if (promotion == "preserve_operand" || promotion == "left_with_checked_count") {
            require(!left.native && !right.native, "Unary and shift operations require runtime operands");
            plan.operations.push_back({name, operation.at("public_operator").text(),
                operation.at("implementation").text(), promotion, form, left, right, left.identity, left.identity});
            return;
        }
        if (promotion == "exact_integer_comparison") {
            require(!plan.boolean_type.empty(), "Integer comparisons require published boolean");
            plan.operations.push_back({name, operation.at("public_operator").text(),
                operation.at("implementation").text(), promotion, form, left, right, "direct_values", plan.boolean_type});
            return;
        }
        const auto &l = types.at(left.identity), &r = types.at(right.identity);
        std::string execution;
        if (left.identity == right.identity) execution = left.identity;
        else if (l.is_signed == r.is_signed)
            execution = policy.at(l.is_signed ? "different_signed" : "different_unsigned").text();
        else {
            require((l.is_signed ? r.bits : l.bits) < 64, "Signed and uint64 arithmetic is not admitted");
            execution = policy.at("signed_and_unsigned_below_64_bits").text();
        }
        require(selected.contains(execution), "Operator requires published execution type: " + execution);
        const auto &target = types.at(execution);
        require(fits(target, l.is_signed, digits(l)) && fits(target, r.is_signed, digits(r)), "Lossy implicit promotion");
        plan.operations.push_back({name, operation.at("public_operator").text(),
            operation.at("implementation").text(), promotion, form, left, right, execution, form == "compound" ? left.identity : execution});
    };
    for (const auto &request : array(publication.at("operators"))) {
        exact(request, "operation operands both_orders", "operator request");
        const auto name = request.at("operation").text();
        const auto &args = array(request.at("operands"));
        const bool uses_float = std::any_of(args.begin(), args.end(), [&](const auto &arg) {
            return arg.type == value::kind::text && arg.text() == floating_name;
        });
        const auto &family = uses_float ? floating_operations : operations;
        require(family.has(name), "Unregistered numeric operation");
        const auto &operation = family.at(name);
        const auto form = operation.at("form").text();
        const bool binary = form == "binary" || form == "compound";
        require(args.size() == (binary ? 2u : 1u), "Operator operand count disagrees with form");
        const auto left = operand(args[0], operation);
        const auto right = binary ? operand(args[1], operation) : scalar_operand{"", "", false};
        resolve(name, left, right);
        if (request.has("both_orders") && boolean(request.at("both_orders"))) {
            require(form == "binary", "Both orders applies only to binary value operators");
            if (left.cpp != right.cpp) resolve(name, right, left);
        }
    }
    for (const auto &op : plan.operations) if (op.left.native || op.right.native)
        require(pairs.contains({op.operation, op.form == "compound" ? op.left.identity : op.execution, op.execution}),
                "Native bridge requires published same-type execution operator: " + op.execution);
    std::sort(plan.operations.begin(), plan.operations.end(), [](const auto &a, const auto &b) {
        return std::tuple{a.operation, a.left.cpp, a.right.cpp} < std::tuple{b.operation, b.left.cpp, b.right.cpp};
    });
    const auto &headers = publication.at("headers"); exact(headers, "types operators short_names native_bridge values", "headers");
    plan.types_header = header(headers.at("types")); plan.operators_header = header(headers.at("operators"));
    plan.short_names_header = header(headers.at("short_names"));
    plan.native_bridge_header = header(headers.at("native_bridge"));
    plan.values_header = header(headers.at("values"));
    require(std::set{plan.values_header, plan.types_header, plan.operators_header, plan.short_names_header, plan.native_bridge_header}.size() == 5, "Conflicting public headers");
    if (!plan.string.name.empty())
        require(!std::set{plan.values_header, plan.types_header, plan.operators_header, plan.short_names_header, plan.native_bridge_header}.contains(plan.string.header), "Conflicting public headers");
    if (metadata.has("vector")) {
        const auto &record = metadata.at("vector");
        exact(record, "source_type storage count_type read mutation element_families native_index native_value operations", "vector metadata");
        literal(record, "source_type", "vector"); literal(record, "storage", "private_std_vector");
        literal(record, "read", "value_copy"); literal(record, "mutation", "explicit_set");
        literal(record, "native_index", "int");
        const auto count = record.at("count_type").text();
        require(types.contains(count) && types.at(count).is_signed, "Vector count requires signed integer type");
        std::set<std::string> families, operations, native_values;
        for (const auto &entry : array(record.at("element_families"))) {
            const auto family = entry.text();
            require(std::set<std::string>{"integer", "floating", "boolean", "null", "string"}.contains(family), "Unsupported vector element family");
            require(families.insert(family).second, "Duplicate vector element family");
        }
        for (const auto &entry : array(record.at("operations"))) {
            require(entry.text() == "equal" || entry.text() == "not_equal", "Unsupported vector operation");
            require(operations.insert(entry.text()).second, "Duplicate vector operation");
        }
        for (const auto &entry : array(record.at("native_value"))) {
            require(entry.text() == "int" || entry.text() == "const char*", "Unsupported vector native value");
            require(native_values.insert(entry.text()).second, "Duplicate vector native value");
        }
        if (publication.has("vectors")) {
            const auto &request = publication.at("vectors");
            exact(request, "header specializations", "vector publication");
            plan.vectors_header = header(request.at("header"));
            require(!std::set{plan.values_header, plan.types_header, plan.operators_header, plan.short_names_header,
                plan.native_bridge_header, plan.string.header}.contains(plan.vectors_header), "Conflicting public headers");
            require(selected.contains(count) && inputs.contains("long long"), "Vector requires published count and native long long construction");
            require(!plan.boolean_type.empty(), "Vector requires published boolean type");
            std::set<std::string> names;
            for (const auto &item : array(request.at("specializations"))) {
                exact(item, "element native_index native_value operations casts_from", "vector specialization");
                const auto element = item.at("element").text();
                std::string family;
                if (selected.contains(element)) family = "integer";
                else if (!plan.string.name.empty() && element == plan.string.name) family = "string";
                else if (selected_values.contains(element)) family = value_types.at(element).kind;
                require(!family.empty() && families.contains(family), "Vector requires supported published element type");
                vector_plan vector{"vector_" + element, element, count, "", boolean(item.at("native_index")), {}, {}};
                require(names.insert(vector.name).second, "Duplicate vector specialization");
                if (vector.native_index) require(inputs.contains("int"), "Vector native index requires int construction");
                const auto native = item.at("native_value").text();
                if (native != "none") {
                    require(native_values.contains(native), "Vector native value is not enabled");
                    if (native == "const char*") require(family == "string", "C-string adapter requires string element");
                    else require(std::any_of(plan.conversions.begin(), plan.conversions.end(), [&](const auto &c) {
                        return c.target == element && c.source == "int" && c.native_source;
                    }), "Vector int adapter requires checked element construction");
                    vector.native_value = native;
                }
                std::set<std::string> seen;
                for (const auto &op : array(item.at("operations"))) {
                    require(operations.contains(op.text()), "Vector operation is not enabled");
                    require(seen.insert(op.text()).second, "Duplicate published vector operation");
                    vector.operations.push_back(op.text());
                }
                if (!vector.operations.empty()) {
                    const bool equality = family == "string" ? std::any_of(plan.string.operations.begin(), plan.string.operations.end(), [](const auto &op) { return op.operation == "equal"; })
                        : family == "integer" || family == "floating" ? std::any_of(plan.operations.begin(), plan.operations.end(), [&](const auto &op) { return op.operation == "equal" && op.left.identity == element && op.right.identity == element && !op.left.native && !op.right.native; })
                        : std::find(value_types.at(element).operations.begin(), value_types.at(element).operations.end(), "equal") != value_types.at(element).operations.end();
                    require(equality, "Vector equality requires published element equality");
                }
                seen.clear();
                for (const auto &from : array(item.at("casts_from"))) {
                    require(seen.insert(from.text()).second, "Duplicate vector cast");
                    require(from.text() != vector.name, "Vector identity uses copy construction");
                    vector.casts_from.push_back(from.text());
                }
                std::sort(vector.operations.begin(), vector.operations.end());
                std::sort(vector.casts_from.begin(), vector.casts_from.end());
                plan.vectors.push_back(vector);
                plan.source_types.emplace("vector<" + element + ">", vector.name);
            }
            require(!plan.vectors.empty(), "Empty vector publication");
            std::sort(plan.vectors.begin(), plan.vectors.end(), [](const auto &a, const auto &b) { return a.name < b.name; });
            for (const auto &target : plan.vectors) for (const auto &from : target.casts_from) {
                const auto source = std::find_if(plan.vectors.begin(), plan.vectors.end(), [&](const auto &v) { return v.name == from; });
                require(source != plan.vectors.end(), "Vector cast requires published source specialization");
                require(std::any_of(plan.conversions.begin(), plan.conversions.end(), [&](const auto &c) {
                    return c.target == target.element && c.source == source->element && !c.native_source;
                }), "Vector cast requires published explicit element conversion");
            }
        }
    } else require(!publication.has("vectors"), "Vector publication requires metadata");
    if (metadata.has("carriers")) {
        const auto &record = metadata.at("carriers");
        exact(record, "extraction construction families error", "carrier metadata");
        const auto extraction = record.at("extraction").text();
        require(extraction == "implicit_checked_one_layer_value_copy" || extraction == "explicit_checked_one_layer_value_copy", "Unsupported carrier extraction policy");
        const auto &error = record.at("error");
        exact(error, "type message line file state", "carrier error");
        literal(error, "type", "error_t"); literal(error, "message", "string"); literal(error, "line", "int64");
        literal(error, "file", "string"); literal(error, "state", "explicit_present");
        literal(record, "construction", "explicit");
        const auto &families = record.at("families");
        exact(families, "nullable result result_or_false result_or_bool", "carrier families");
        for (const auto &[family, state] : families.fields)
            require(state.text() == (family == "nullable" ? "empty" : family == "result" ? "value" : "false"), "Unsupported carrier default state");
        if (publication.has("carriers")) {
            const auto &request = publication.at("carriers");
            exact(request, "header specializations", "carrier publication");
            plan.carriers_header = header(request.at("header"));
            require(!std::set{plan.types_header, plan.values_header, plan.operators_header, plan.short_names_header,
                plan.native_bridge_header, plan.string.header, plan.vectors_header}.contains(plan.carriers_header), "Conflicting public headers");
            require(!plan.boolean_type.empty(), "Carriers require published boolean type");
            std::set<std::string> names;
            for (const auto &item : array(request.at("specializations"))) {
                exact(item, "family payload", "carrier specialization");
                const auto family = item.at("family").text(), payload = item.at("payload").text();
                require(families.has(family), "Unknown carrier family");
                if (family == "nullable") require(selected_values.contains("null_t"), "Nullable requires published null_t");
                bool supported = selected.contains(payload) || (!plan.string.name.empty() && payload == plan.string.name)
                    || std::any_of(plan.vectors.begin(), plan.vectors.end(), [&](const auto &v) { return v.name == payload; })
                    || (selected_values.contains(payload) && value_types.at(payload).kind != "null");
                require(supported, "Carrier payload must be a published scalar, string or vector; null/nested carriers are not admitted");
                const auto name = family + "_" + payload;
                require(names.insert(name).second, "Duplicate carrier specialization");
                if (family == "result") require(!plan.string.name.empty() && selected.contains("int64"), "Result error payload requires string and int64");
                plan.carriers.push_back({name, family, payload, families.at(family).text(), extraction.starts_with("implicit")});
                plan.source_types.emplace(family + "<" + payload + ">", name);
            }
            require(!plan.carriers.empty(), "Empty carrier publication");
            std::sort(plan.carriers.begin(), plan.carriers.end(), [](const auto &a, const auto &b) { return a.name < b.name; });
        }
    } else require(!publication.has("carriers"), "Carrier publication requires metadata");
    prepare_ownership(metadata, publication, plan);
    return plan;
}

value describe(const runtime_plan &plan) {
    value out = value::object(); out["kind"] = "prepared_runtime_api";
    if (!plan.string.name.empty()) {
        auto &record = out["string"]; record = value::object();
        record["name"] = plan.string.name; record["count_type"] = plan.string.count_type;
        record["header"] = plan.string.header; record["storage"] = "private_std_string";
        record["lifecycle"] = "owned_copy_noexcept_move";
        record["empty_state"] = "null_storage";
        record["operations"] = value::array();
        for (const auto &op : plan.string.operations) {
            auto row = value::object(); row["operation"] = op.operation; row["symbol"] = op.symbol;
            row["form"] = op.form; row["implementation"] = op.implementation; row["execution"] = op.execution; row["result"] = op.result;
            row["left"] = op.left.identity; row["right"] = op.right.identity;
            row["result_category"] = op.form == "compound" ? "left_reference" : "value";
            record["operations"].items.push_back(row);
        }
        record["methods"] = value::array();
        for (const auto &[method, result] : std::vector<std::pair<std::string, std::string>>{
                {"size", plan.string.count_type}, {"empty", plan.boolean_type}, {"data", "const char*"}, {"append", "void"}, {"clear", "void"}}) {
            auto row = value::object(); row["name"] = method; row["result"] = result;
            row["receiver"] = method == "append" || method == "clear" ? "mutable_reference" : "const_reference";
            row["arguments"] = value::array();
            if (method == "append") row["arguments"].items.emplace_back("const " + plan.string.name + "&");
            row["result_category"] = method == "data" ? "borrowed_bytes_until_mutation_or_destruction" : "value";
            row["can_fail"] = flag(method == "size" || method == "append");
            record["methods"].items.push_back(row);
        }
        record["construction"] = value::array();
        for (const auto &arguments : std::vector<std::vector<std::string>>{{"const char*"}, {"const char*", plan.string.count_type}}) {
            auto row = value::object(); row["arguments"] = value::array();
            for (const auto &arg : arguments) row["arguments"].items.emplace_back(arg);
            row["explicit"] = flag(true); row["null_pointer"] = "reject"; row["can_fail"] = flag(true);
            record["construction"].items.push_back(row);
        }
    }
    out["vectors"] = value::array();
    for (const auto &v : plan.vectors) {
        auto row = value::object(); row["name"] = v.name; row["element"] = v.element;
        row["source_type"] = "vector<" + v.element + ">"; row["count_type"] = v.count_type;
        row["storage"] = "private_std_vector"; row["lifecycle"] = "owned_copy_noexcept_move";
        row["read"] = "checked_index_value_copy"; row["set"] = "checked_index_move_value";
        row["native_index"] = flag(v.native_index); row["native_value"] = v.native_value;
        row["operations"] = value::array(); row["casts_from"] = value::array();
        for (const auto &op : v.operations) {
            auto operation = value::object(); operation["operation"] = op;
            operation["symbol"] = op == "equal" ? "==" : "!=";
            operation["implementation"] = "elementwise_equality"; operation["element_dependency"] = "operator==";
            operation["result"] = plan.boolean_type;
            row["operations"].items.push_back(operation);
        }
        row["methods"] = value::array();
        const auto method = [&](const std::string &name, const std::string &result, const std::vector<std::string> &arguments,
                                bool mutates, const std::string &adaptation) {
            auto member = value::object(); member["name"] = name; member["result"] = result;
            member["receiver"] = mutates ? "mutable_reference" : "const_reference";
            member["result_category"] = "value"; member["adaptation"] = adaptation;
            member["arguments"] = value::array();
            for (const auto &argument : arguments) member["arguments"].items.emplace_back(argument);
            row["methods"].items.push_back(member);
        };
        method("size", v.count_type, {}, false, "checked_count");
        method("empty", plan.boolean_type, {}, false, "none"); method("clear", "void", {}, true, "none");
        std::vector<std::string> indices{v.count_type}, values{v.element};
        if (v.native_index) indices.push_back("int");
        if (!v.native_value.empty()) values.push_back(v.native_value);
        for (const auto &index : indices) {
            method("at", v.element, {index}, false, index == v.count_type ? "checked_index_copy" : "checked_native_index_copy");
            for (const auto &element : values) method("set", "void", {index, element}, true,
                index == v.count_type && element == v.element ? "checked_index_move_value" : "explicit_arguments_then_checked_set");
        }
        for (const auto &element : values) method("append", "void", {element}, true,
            element == v.element ? "move_value" : "explicit_element_then_append");
        for (const auto &from : v.casts_from) {
            auto cast = value::object(); cast["source"] = from; cast["explicit"] = flag(true);
            const auto source = std::find_if(plan.vectors.begin(), plan.vectors.end(), [&](const auto &item) { return item.name == from; });
            cast["element_source"] = source->element; cast["element_target"] = v.element;
            cast["implementation"] = "elementwise_explicit_conversion"; cast["failure"] = "discard_partial_target_keep_source";
            row["casts_from"].items.push_back(cast);
        }
        out["vectors"].items.push_back(row);
    }
    if (!plan.vectors.empty()) out["vectors_header"] = plan.vectors_header;
    out["carriers"] = value::array();
    for (const auto &c : plan.carriers) {
        auto row = value::object(); row["name"] = c.name; row["family"] = c.family; row["payload"] = c.payload;
        row["default_state"] = c.default_state; row["boxing"] = "explicit";
        row["extraction"] = c.implicit_extraction ? "implicit_checked_one_layer_value_copy" : "explicit_checked_one_layer_value_copy"; row["extraction_can_fail"] = flag(true);
        row["non_payload_extraction"] = "throw_runtime_error"; row["condition"] = "query_state_explicitly";
        row["lifecycle"] = "owned_copy_move_resets_source_to_default";
        row["lifted_operators"] = flag(false); row["result_category"] = "value";
        row["explicit_lowering"] = "value_then_payload_operation";
        out["carriers"].items.push_back(row);
    }
    if (!plan.carriers.empty()) out["carriers_header"] = plan.carriers_header;
    if (std::any_of(plan.carriers.begin(), plan.carriers.end(), [](const auto &c) { return c.family == "result"; })) {
        out["error"] = value::object(); out["error"]["type"] = "error_t"; out["error"]["state"] = "explicit_present";
        out["error"]["message"] = "string"; out["error"]["line"] = "int64"; out["error"]["file"] = "string";
        out["error"]["inspection"] = "value_copy"; out["error"]["global_short_name"] = flag(false);
    }
    out["short_aliases"] = value::array();
    for (const auto &alias : plan.short_aliases) out["short_aliases"].items.emplace_back(alias);
    out["values"] = value::array(); out["source_types"] = value::object(); out["aliases"] = value::object();
    for (const auto &[alias, target] : plan.aliases) out["aliases"][alias] = target;
    for (const auto &type : plan.values) {
        value row = value::object(); row["name"] = type.name; row["kind"] = type.kind;
        row["representation"] = type.representation; row["operations"] = value::array();
        for (const auto &op : type.operations) row["operations"].items.emplace_back(op);
        out["values"].items.push_back(row);
    }
    for (const auto &[source, target] : plan.source_types) out["source_types"][source] = target;
    out["types"] = value::array(); out["native_inputs"] = value::array();
    out["conversions"] = value::array(); out["operations"] = value::array();
    for (const auto &t : plan.types) {
        value row = value::object(); row["name"] = t.name; row["representation"] = t.representation;
        row["signed"] = flag(t.is_signed); row["bits"] = number(t.bits); out["types"].items.push_back(row);
    }
    for (const auto &t : plan.native_inputs) {
        value row = value::object(); row["cpp"] = t.cpp; row["signed"] = flag(t.is_signed); row["digits"] = number(t.digits);
        out["native_inputs"].items.push_back(row);
    }
    for (const auto &c : plan.conversions) {
        value row = value::object(); row["target"] = c.target; row["source"] = c.source;
        row["native_source"] = flag(c.native_source); row["lossless"] = flag(c.lossless);
        row["implementation"] = c.implementation; row["can_fail"] = flag(c.can_fail);
        row["source_category"] = c.source == plan.string.name ? "const_reference" : "value";
        out["conversions"].items.push_back(row);
    }
    for (const auto &op : plan.operations) {
        value row = value::object(); row["operation"] = op.operation;
        row["form"] = op.form; row["promotion"] = op.promotion; row["symbol"] = op.symbol; row["implementation"] = op.implementation;
        for (const auto &[side, operand] : std::vector<std::pair<std::string, scalar_operand>>{{"left", op.left}, {"right", op.right}}) {
            if (operand.cpp.empty()) continue;
            row[side] = value::object(); row[side]["cpp"] = operand.cpp;
            row[side]["identity"] = operand.identity; row[side]["native"] = flag(operand.native);
            row[side + "_conversion"] = op.promotion == "floating_exact_integer" && operand.identity != op.execution ? "exact_integer_to_floating" : (op.promotion == "exact_integer_comparison" || op.promotion == "preserve_operand" || op.promotion == "left_with_checked_count") ? "identity" : operand.native ? "lossless_native_integer"
                : operand.identity == op.execution ? "identity" : "lossless_integer";
        }
        row["execution"] = op.execution; row["result"] = op.result;
        row["write_back"] = (op.form == "compound") ? "checked_after_calculation" : "none";
        row["result_category"] = (op.form == "compound" || op.form == "prefix") ? "left_reference"
            : op.form == "postfix" ? "old_value" : "value";
        out["operations"].items.push_back(row);
    }
    out["headers"] = value::object(); out["headers"]["types"] = plan.types_header;
    out["headers"]["operators"] = plan.operators_header; out["headers"]["short_names"] = plan.short_names_header;
    out["headers"]["native_bridge"] = plan.native_bridge_header;
    out["headers"]["values"] = plan.values_header;
    if (!plan.ownership.records.empty()) out["ownership"] = describe_ownership(plan.ownership);
    return out;
}
}
