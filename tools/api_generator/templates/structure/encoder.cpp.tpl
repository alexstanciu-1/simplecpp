#pragma once
@includes@
#include <string_view>

namespace scpp {
@forwards@struct encoder_cycle {};

class Encoder {
    struct frame { const void* identity; const frame* parent; };
    const frame* active = nullptr;
public:
    using visit = void (*)(const void*, Encoder&);
    virtual void begin_object() = 0;
    // The name is a declared source field identifier, borrowed for this call.
    virtual void field(std::string_view name) = 0;
    virtual void end_object() = 0;
    virtual void begin_array() = 0;
    virtual void end_array() = 0;
    virtual void write_null() = 0;
@declarations@
    // Shared subobjects can recur on separate branches. Only an active ancestor is a cycle.
    void owned(const void* value, visit emit) {
        if (!value) { write_null(); return; }
        for (auto p = active; p; p = p->parent)
            if (p->identity == value) throw encoder_cycle{};
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
}
