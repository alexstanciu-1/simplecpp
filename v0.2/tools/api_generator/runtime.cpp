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
    exact(metadata, "version types representation construction promotion_policies operations values source_types aliases floating scalar_casts", "runtime metadata");
    exact(publication, "version namespace types operators headers values short_aliases", "runtime publication");
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
    return plan;
}

value describe(const runtime_plan &plan) {
    value out = value::object(); out["kind"] = "prepared_runtime_api";
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
    return out;
}
}
