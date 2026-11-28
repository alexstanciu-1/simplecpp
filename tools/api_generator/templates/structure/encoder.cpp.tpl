#pragma once
@includes@
#include <string_view>
#include <cstdint>
#include <type_traits>

namespace scpp {
@forwards@struct encoder_cycle {};
struct unsupported_encoding {};

class Encoder {
    struct frame { const void* identity; const frame* parent; };
    const frame* active = nullptr;
public:
    using visit = void (*)(const void*, Encoder&);
    // Opt in only if no operation in this synchronous traversal can mutate the input through an alias.
    virtual bool stable_record_elements() const noexcept { return false; }
    virtual void begin_object() = 0;
    // The name is a declared source field identifier, borrowed for this call.
    virtual void field(std::string_view name) = 0;
    virtual void end_object() = 0;
    virtual void begin_array() = 0;
    virtual void end_array() = 0;
    virtual void record(std::string_view, std::size_t) { begin_object(); }
    virtual void sequence(std::size_t) { begin_array(); }
    virtual void mapping(std::size_t) { throw unsupported_encoding{}; }
    virtual void integer_key(std::int64_t) { throw unsupported_encoding{}; }
    virtual void write_unsigned(std::uint64_t) { throw unsupported_encoding{}; }
    virtual void recursion() { throw encoder_cycle{}; }
    virtual void write_null() = 0;
@declarations@
    // Shared subobjects can recur on separate branches. Only an active ancestor is a cycle.
    void owned(const void* value, visit emit) {
        if (!value) { write_null(); return; }
        for (auto p = active; p; p = p->parent)
            if (p->identity == value) { recursion(); return; }
        frame current{value, active};
        struct restore {
            const frame*& slot;
            const frame* previous;
            ~restore() { slot = previous; }
        } guard{active, active};
        active = &current;
        emit(value, *this);
    }
protected:
    Encoder() = default;
    Encoder(const Encoder&) = delete;
    Encoder& operator=(const Encoder&) = delete;
    ~Encoder() = default;
};
void dump_structure(const void*, Encoder::visit);

// Borrow the evaluated operand for one synchronous traversal.
template<class Value> void dump_value(const std::type_identity_t<Value>& value, void (*walk)(const Value&, Encoder&)) {
    struct call { const Value& value; decltype(walk) visit; } context{value, walk};
    dump_structure(&context, [](const void* raw, Encoder& out) {
        const auto& input = *static_cast<const call*>(raw);
        input.visit(input.value, out);
    });
}
}
