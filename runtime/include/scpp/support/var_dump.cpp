#include "var_dump.hpp"
#include <unordered_set>
#include <sstream>

namespace scpp::php {

static void dump_value(const mixed_t& v, int indent_lvl, std::unordered_set<const void*>& seen);
static void dump_value(const detail::dynamic_value& v, int indent_lvl, std::unordered_set<const void*>& seen);

static void print_indent(int n) {
    for (int i = 0; i < n; ++i) std::cout << "  ";
}

static std::string float_to_php_string(double value) {
    std::ostringstream oss;
    oss << value;
    return oss.str();
}

template <typename Table>
static void dump_table_ptr(const Table* t, const void* identity, int indent_lvl, std::unordered_set<const void*>& seen, const char* label = "array") {
    if (t == nullptr) {
        std::cout << "NULL\n";
        return;
    }
    if (seen.count(identity) != 0U) {
        std::cout << "*RECURSION*\n";
        return;
    }
    seen.insert(identity);

    std::cout << label << "(" << t->size() << ") {\n";
    t->debug_visit_entries([&](const auto& key, const auto& val) {
        print_indent(indent_lvl + 1);
        std::cout << "[";
        using key_t = std::remove_cvref_t<decltype(key)>;
        if constexpr (std::is_same_v<key_t, int_t<>>) {
            std::cout << key.native_value();
        } else {
            std::cout << '"' << key.native_value() << '"';
        }
        std::cout << "]=>\n";
        print_indent(indent_lvl + 1);
        dump_value(val, indent_lvl + 1, seen);
    });
    print_indent(indent_lvl);
    std::cout << "}\n";
}

static void dump_table(const hash_t<mixed_t>& t, int indent_lvl, std::unordered_set<const void*>& seen) {
    dump_table_ptr(&t, &t, indent_lvl, seen);
}

template <typename Box>
static bool dump_boxed_scalar(const Box& v) {
    using kind = typename Box::kind_t;
    switch (v.kind()) {
        case kind::null_v:
            std::cout << "NULL\n";
            return true;
        case kind::bool_v:
            std::cout << "bool(" << (v.get_bool().native_value() ? "true" : "false") << ")\n";
            return true;
        case kind::int_v:
            std::cout << "int(" << v.get_int().native_value() << ")\n";
            return true;
        case kind::float_v:
            std::cout << "float(" << float_to_php_string(v.get_float().native_value()) << ")\n";
            return true;
        case kind::string_v: {
            const auto& native = v.get_string().native_value();
            std::cout << "string(" << native.size() << ") \"" << native << "\"\n";
            return true;
        }
        default: return false;
    }
}

static void dump_value(const detail::dynamic_value& v, int indent_lvl, std::unordered_set<const void*>& seen) {
    if (dump_boxed_scalar(v)) return;
    const auto* table = v.table_if();
    dump_table_ptr(table, table, indent_lvl, seen, "object(dynamic_t)");
}

static void dump_value(const mixed_t& v, int indent_lvl, std::unordered_set<const void*>& seen) {
    if (dump_boxed_scalar(v)) return;
    switch (v.kind()) {
        case mixed_t::kind_t::table_v:
            dump_table(*v.table_if(), indent_lvl, seen);
            break;
        case mixed_t::kind_t::shared_table_v: {
            const auto* shared = v.shared_table_if();
            const auto* ptr = (shared == nullptr) ? nullptr : shared->get();
            dump_table_ptr(ptr, ptr, indent_lvl, seen);
            break;
        }
        case mixed_t::kind_t::dynamic_v: {
            const auto* dynamic = v.dynamic_if();
            const auto* ptr = (dynamic == nullptr) ? nullptr : dynamic->get();
            dump_table_ptr(ptr, ptr, indent_lvl, seen, "object(dynamic_t)");
            break;
        }
        case mixed_t::kind_t::weak_table_v: {
            const auto* weak = v.weak_table_if();
            const auto locked = (weak == nullptr) ? shared_p<hash_t<mixed_t>>(null_t{}) : weak->lock();
            const auto* ptr = locked.get();
            dump_table_ptr(ptr, ptr, indent_lvl, seen);
            break;
        }
        default: break;
    }
}

void var_dump(const mixed_t& v) {
    std::unordered_set<const void*> seen;
    dump_value(v, 0, seen);
}

void var_dump(const detail::dynamic_value& v) {
    std::unordered_set<const void*> seen;
    dump_value(v, 0, seen);
}

void var_dump(const hash_t<mixed_t>& t) {
    std::unordered_set<const void*> seen;
    dump_table(t, 0, seen);
}

}
