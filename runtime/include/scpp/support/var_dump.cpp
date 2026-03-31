#include "var_dump.hpp"
#include <unordered_set>
#include <sstream>

namespace scpp::php {

static void dump_value(const value_t& v, int indent_lvl, std::unordered_set<const void*>& seen);

static void print_indent(int n) {
    for (int i = 0; i < n; ++i) std::cout << "  ";
}

static std::string float_to_php_string(double value) {
    std::ostringstream oss;
    oss << value;
    return oss.str();
}

static void dump_table_ptr(const table_t<value_t>* t, const void* identity, int indent_lvl, std::unordered_set<const void*>& seen) {
    if (t == nullptr) {
        std::cout << "NULL\n";
        return;
    }
    if (seen.count(identity) != 0U) {
        std::cout << "*RECURSION*\n";
        return;
    }
    seen.insert(identity);

    std::cout << "array(" << t->size() << ") {\n";
    t->debug_visit_entries([&](const auto& key, const value_t& val) {
        print_indent(indent_lvl + 1);
        std::cout << "[";
        using key_t = std::remove_cvref_t<decltype(key)>;
        if constexpr (std::is_same_v<key_t, int_t>) {
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

static void dump_table(const table_t<value_t>& t, int indent_lvl, std::unordered_set<const void*>& seen) {
    dump_table_ptr(&t, &t, indent_lvl, seen);
}

static void dump_value(const value_t& v, int indent_lvl, std::unordered_set<const void*>& seen) {
    switch (v.kind()) {
        case value_t::kind_t::null_v:
            std::cout << "NULL\n";
            break;
        case value_t::kind_t::bool_v:
            std::cout << "bool(" << (v.bool_value().native_value() ? "true" : "false") << ")\n";
            break;
        case value_t::kind_t::int_v:
            std::cout << "int(" << v.int_value().native_value() << ")\n";
            break;
        case value_t::kind_t::float_v:
            std::cout << "float(" << float_to_php_string(v.float_value().native_value()) << ")\n";
            break;
        case value_t::kind_t::string_v: {
            const auto* s = v.string_if();
            const std::string native = (s == nullptr) ? std::string() : s->native_value();
            std::cout << "string(" << native.size() << ") \"" << native << "\"\n";
            break;
        }
        case value_t::kind_t::table_v:
            dump_table(*v.table_if(), indent_lvl, seen);
            break;
        case value_t::kind_t::shared_table_v: {
            const auto* shared = v.shared_table_if();
            const auto* ptr = (shared == nullptr) ? nullptr : shared->get();
            dump_table_ptr(ptr, ptr, indent_lvl, seen);
            break;
        }
        case value_t::kind_t::weak_table_v: {
            const auto* weak = v.weak_table_if();
            const auto locked = (weak == nullptr) ? shared_p<table_t<value_t>>(null_t{}) : weak->lock();
            const auto* ptr = locked.get();
            dump_table_ptr(ptr, ptr, indent_lvl, seen);
            break;
        }
    }
}

void var_dump(const value_t& v) {
    std::unordered_set<const void*> seen;
    dump_value(v, 0, seen);
}

void var_dump(const table_t<value_t>& t) {
    std::unordered_set<const void*> seen;
    dump_table(t, 0, seen);
}

}
