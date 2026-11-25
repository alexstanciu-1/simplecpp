#pragma once
// Native investigation fixture, not an extension of accepted Simple C++ syntax.
#include <scpp/vector_t.hpp>
#include <scpp/shared_p.hpp>
#include <cstddef>
#include <cstdint>
#include <memory>
#include <type_traits>

extern "C" void proof_event(std::int32_t kind, std::int32_t value) noexcept;
extern "C" void proof_equal(std::int32_t actual, std::int32_t expected) noexcept;
extern "C" std::int32_t proof_initializer(std::int32_t seed) noexcept;
extern "C" void proof_constructor_body(void *self, std::int32_t seed) noexcept;
extern "C" void proof_destructor_body(void *self) noexcept;

namespace lifecycle_probe {

// Observable non-default construction makes default-construct-then-assign fail.
struct traced {
    std::int32_t value;
    traced() = delete;
    explicit traced(std::int32_t v) : value(v) { proof_event(1, value); }
    traced(const traced& source) : value(source.value) { proof_event(2, value); }
    traced& operator=(const traced& source) {
        value = source.value;
        proof_event(3, value);
        return *this;
    }
    ~traced() { proof_event(4, value); }
};

struct branch {
    traced elements[2];
    explicit branch(std::int32_t seed = 1) : elements{traced(seed), traced(seed + 1)} {}
};

struct item { std::int32_t value = 0; };

struct automatic_record {
    branch child;
    scpp::vector_t<item> items;
};

// The field initializers and lifecycle declarations are preparation inputs.
// The external body implementations are application inputs, linked separately.
struct custom_record {
    traced first;
    branch child;
    scpp::vector_t<item> items;
    std::int32_t marker = 0;

    explicit custom_record(std::int32_t seed)
        : first(proof_initializer(seed)), child(seed + 1) {
        proof_constructor_body(this, seed);
    }
    ~custom_record() noexcept { proof_destructor_body(this); }
};

template<class T>
void append(T& self, std::int32_t value) { self.items.append(item{value}); }

template<class T>
std::int32_t length(const T& self) { return static_cast<std::int32_t>(self.items.size()); }

template<class T>
std::int32_t first_value(const T& self) { return self.items.at(0).value; }

template<class T>
void assign(T& destination, const T& source) { destination = source; }

// A native owner triggers the same source-body hook as stack destruction.
inline void shared_lifetime(std::int32_t seed) {
    scpp::shared_p<custom_record> owner(std::make_shared<custom_record>(seed));
    auto alias = owner;
    owner.reset();
    proof_equal(static_cast<std::int32_t>(alias.use_count()), 1);
    proof_event(8, seed);
    alias.reset();
    proof_event(9, seed);
}

// A user destructor suppresses an implicit move constructor. Constructibility
// from an rvalue may nevertheless be true because the copy constructor accepts it.
struct copy_fallback {
    traced field{7};
    ~copy_fallback() = default;
};
static_assert(std::is_move_constructible_v<copy_fallback>);
inline void rvalue_copy() {
    copy_fallback source;
    copy_fallback destination(static_cast<copy_fallback&&>(source));
}

static_assert(std::is_standard_layout_v<custom_record>);
static_assert(std::is_standard_layout_v<traced>);
} // namespace lifecycle_probe

// Fixture-only scalar access facts. No guessed target layout in the LLVM bodies.
extern "C" const std::uint64_t proof_marker_offset = offsetof(lifecycle_probe::custom_record, marker);
extern "C" const std::uint64_t proof_first_offset = offsetof(lifecycle_probe::custom_record, first)
    + offsetof(lifecycle_probe::traced, value);
