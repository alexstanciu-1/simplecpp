// The generated adapter implements exactly the leaves published on this Encoder.
#include "scpp/encoder.hpp"
#include "scpp/output.hpp"
#include "var_dump_bindings.hpp"
#include <charconv>
#include <cstdint>
#include <vector>
#include <stdexcept>

namespace scpp {
namespace {
using bytes = std::string_view;

class dump_writer final : public Encoder {
    struct frame { bool indexed; std::size_t next; };
    std::vector<frame> stack;
    void (*send)(const char*, std::size_t);

    void text(bytes s) { if (!s.empty()) send(s.data(), s.size()); }
    template<class T> void number(T value) {
        char buffer[64];
        auto result = std::to_chars(buffer, buffer + sizeof(buffer), value);
        if (result.ec != std::errc{}) throw std::runtime_error("var_dump: number formatting failed");
        send(buffer, static_cast<std::size_t>(result.ptr - buffer));
    }
    void indent() { for (std::size_t i = 0; i < stack.size(); ++i) text("  "); }
    template<class Integer> void index(Integer key) { indent(); text("["); number(key); text("]=>\n"); }
    void prefix() {
        if (!stack.empty() && stack.back().indexed) index(stack.back().next++);
        indent();
    }
    void begin(bytes label, std::size_t count, bool indexed) {
        prefix(); text(label); text("("); number(count); text(") {\n");
        stack.push_back({indexed, 0});
    }
    void end() { stack.pop_back(); indent(); text("}\n"); }

    void scalar(bool v) { prefix(); text(v ? "bool(true)\n" : "bool(false)\n"); }
    void scalar(std::int64_t v) { prefix(); text("int("); number(v); text(")\n"); }
    void scalar(double v) { prefix(); text("float("); number(v); text(")\n"); }
    void scalar(bytes v) {
        prefix(); text("string("); number(v.size()); text(") \""); text(v); text("\"\n");
    }

public:
    explicit dump_writer(decltype(send) output) : send(output) {}
    void tuple(std::size_t count) { begin("tuple", count, true); }
    void string_bytes(bytes value) { scalar(value); }
    bool stable_record_elements() const noexcept override { return true; }
    void record(bytes name, std::size_t count) override {
        prefix(); text("object("); text(name); text(")("); number(count); text(") {\n");
        stack.push_back({false, 0});
    }
    void begin_object() override { prefix(); text("object {\n"); stack.push_back({false, 0}); }
    void field(bytes key) override { indent(); text("[\""); text(key); text("\"]=>\n"); }
    void end_object() override { end(); }
    void sequence(std::size_t count) override { begin("array", count, true); }
    void begin_array() override { prefix(); text("array {\n"); stack.push_back({true, 0}); }
    void end_array() override { end(); }
    void write_null() override { prefix(); text("NULL\n"); }
    void mapping(std::size_t count) override { begin("array", count, false); }
    void integer_key(std::int64_t key) override { index(key); }
    void write_unsigned(std::uint64_t v) override { prefix(); text("int("); number(v); text(")\n"); }
    void recursion() override { prefix(); text("*RECURSION*\n"); }
#include "var_dump_methods.hpp"
};
}

void dump_structure(const void* source, Encoder::visit walk) {
    dump_writer out{echo_bytes};
    walk(source, out);
}
}
