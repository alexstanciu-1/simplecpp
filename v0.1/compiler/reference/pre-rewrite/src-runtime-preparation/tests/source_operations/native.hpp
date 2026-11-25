#pragma once
// Native boundary investigation only; these are not new source-language types.
#include <scpp/vector_t.hpp>
#include <cstddef>
#include <cstdint>
#include <cstring>
#include <new>
#include <type_traits>
#include <utility>

extern "C" void observe_field(std::int32_t operation, void *self, const void *source) noexcept;
extern "C" void observe_source(std::int32_t operation, std::int32_t phase, void *self) noexcept;
extern "C" void check_equal(std::int32_t actual, std::int32_t expected) noexcept;

// Complete source operations. C++ supplies declarations only; LLVM defines them.
extern "C" void source_construct(void *self) noexcept;
extern "C" void source_copy(void *self, const void *source) noexcept;
extern "C" void source_move(void *self, void *source) noexcept;
extern "C" void source_assign(void *self, const void *source) noexcept;
extern "C" void source_move_assign(void *self, void *source) noexcept;
extern "C" void source_destroy(void *self) noexcept;
extern "C" void compiler_check_payload(const void *self, std::int32_t expected) noexcept;

namespace source_operations_probe {
struct item { std::int32_t value; };

// Imported test provider. The real runtime vector owns this field's resources.
struct managed_field {
    scpp::vector_t<item> items;
    managed_field() = delete;
    explicit managed_field(std::int32_t value) {
        items.append(item{value});
        observe_field(0, this, nullptr);
    }
    managed_field(const managed_field& source) : items(source.items) { observe_field(1, this, &source); }
    managed_field(managed_field&& source) noexcept : items(std::move(source.items)) { observe_field(2, this, &source); }
    managed_field& operator=(const managed_field& source) {
        items = source.items;
        observe_field(3, this, &source);
        return *this;
    }
    managed_field& operator=(managed_field&& source) noexcept {
        items = std::move(source.items);
        observe_field(4, this, &source);
        return *this;
    }
    ~managed_field() { observe_field(5, this, nullptr); }
};

// Layout witness only: no object of this type is ever constructed or accessed.
// An over-aligned fixture ensures the adapter preserves alignment as well as size.
struct alignas(32) source_layout {
    managed_field first;
    std::int32_t tag;
    managed_field second;
};
static_assert(std::is_standard_layout_v<source_layout>);

// Candidate native adaptation. Byte storage has no automatic field lifecycle.
// C++ constructs the adapter itself; LLVM alone constructs its source payload.
struct source_view { const void *payload; };
template<bool Copyable>
struct element_adapter {
    alignas(source_layout) std::byte payload[sizeof(source_layout)];
    element_adapter() noexcept { source_construct(payload); }
    explicit element_adapter(source_view source) noexcept requires Copyable { source_copy(payload, source.payload); }
    element_adapter(const element_adapter& other) noexcept requires Copyable { source_copy(payload, other.payload); }
    element_adapter(const element_adapter&) requires (!Copyable) = delete;
    element_adapter(element_adapter&& other) noexcept { source_move(payload, other.payload); }
    element_adapter& operator=(const element_adapter& other) noexcept requires Copyable {
        source_assign(payload, other.payload);
        return *this;
    }
    element_adapter& operator=(const element_adapter&) requires (!Copyable) = delete;
    element_adapter& operator=(element_adapter&& other) noexcept {
        source_move_assign(payload, other.payload);
        return *this;
    }
    ~element_adapter() noexcept { source_destroy(payload); }
};
using source_element = element_adapter<true>;
using sequence = scpp::vector_t<source_element>;
static_assert(sizeof(source_element) == sizeof(source_layout));
static_assert(alignof(source_element) == alignof(source_layout));
static_assert(offsetof(source_element, payload) == 0);
static_assert(!std::is_trivially_copyable_v<source_element>);
static_assert(!std::is_copy_constructible_v<element_adapter<false>>);
static_assert(std::is_nothrow_move_constructible_v<element_adapter<false>>);

// These helpers implement imported-field operations, not source composition.
// Placement construction receives raw storage, not a fabricated live reference.
// This operation uses a returned prvalue to fit the existing caller-storage ABI.
inline managed_field move_value(managed_field& source) { return managed_field(std::move(source)); }
inline void assign(managed_field& destination, const managed_field& source) { destination = source; }
inline void move_assign(managed_field& destination, managed_field& source) { destination = std::move(source); }

inline managed_field& first(source_element& value) {
    return *std::launder(reinterpret_cast<managed_field*>(value.payload + offsetof(source_layout, first)));
}
inline std::int32_t tag(const source_element& value) {
    std::int32_t result;
    std::memcpy(&result, value.payload + offsetof(source_layout, tag), sizeof(result));
    return result;
}
inline void length(source_element& value, std::int32_t expected) {
    check_equal(static_cast<std::int32_t>(first(value).items.size()), expected);
}

// Real vector demand: copy insertion, relocation, copying, assignments and erase.
inline void exercise(std::int32_t revision) {
    source_element local;
    check_equal(tag(local), revision);
    sequence values;
    values.reserve(1);
    values.append(local);
    first(local).items.append(item{99});
    length(values.at(0), 1);
    values.reserve(values.capacity() + 1); // force relocation through source_move
    length(values.at(0), 1);
    values.append(local);
    sequence copy(values);
    first(values.at(0)).items.append(item{77});
    length(copy.at(0), 1);
    values.at(0) = local;
    first(local).items.append(item{100});
    length(values.at(0), 2);
    values.at(0) = values.at(0); // copy self-assignment must remain live
    length(values.at(0), 2);
    values.native_value().erase(values.native_value().begin()); // source_move_assign
    length(values.at(0), 2);
    check_equal(tag(values.at(0)), revision);
    values.clear();
    copy.clear();
}
} // namespace source_operations_probe

#ifdef PROBE_LAYOUT_FACTS
// Explicit raw-payload crossing, never a cast from source storage to adapter&.
// The vector owns a real adapter object; its constructor copies the source payload.
extern "C" void native_payload_roundtrip(const void *source, void *result, std::int32_t revision) noexcept {
    using namespace source_operations_probe;
    sequence values;
    values.native_value().emplace_back(source_view{source});
    compiler_check_payload(values.at(0).payload, revision);
    source_copy(result, values.at(0).payload);
    first(values.at(0)).items.append(item{99});
    auto *original = std::launder(reinterpret_cast<const managed_field*>(
        static_cast<const std::byte*>(source) + offsetof(source_layout, first)));
    auto *copied = std::launder(reinterpret_cast<const managed_field*>(
        static_cast<const std::byte*>(result) + offsetof(source_layout, first)));
    check_equal(static_cast<std::int32_t>(original->items.size()), 1);
    check_equal(static_cast<std::int32_t>(copied->items.size()), 1);
}
extern "C" const std::uint64_t source_size = sizeof(source_operations_probe::source_layout);
extern "C" const std::uint64_t source_alignment = alignof(source_operations_probe::source_layout);
extern "C" const std::uint64_t source_first = offsetof(source_operations_probe::source_layout, first);
extern "C" const std::uint64_t source_second = offsetof(source_operations_probe::source_layout, second);
extern "C" const std::uint64_t source_tag = offsetof(source_operations_probe::source_layout, tag);
#endif
