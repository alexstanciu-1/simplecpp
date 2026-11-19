#include <scpp/value_error.hpp>
#include "vector_buffer.hpp"
#include "concrete_index_impl.hpp"
#include <cassert>
#include <cstdint>
@pair@
namespace scpp {
using int64 = std::int64_t;
using bool_t = bool;
// Test-only native type: forbidden default construction, observable lifetime and throwing copies.
struct alignas(64) Probe {
    static inline int live = 0, authored = 0, copies = 0, moves = 0, copy_budget = -1;
    int value;
    Probe() = delete;
    explicit Probe(int n) : value(n) { assert(reinterpret_cast<std::uintptr_t>(this) % 64 == 0); ++live; ++authored; }
    Probe(const Probe& other) : value(other.value) {
        if (copy_budget == 0) throw 42;
        if (copy_budget > 0) --copy_budget;
        assert(reinterpret_cast<std::uintptr_t>(this) % 64 == 0); ++live; ++copies;
    }
    Probe(Probe&& other) noexcept : value(other.value) { assert(reinterpret_cast<std::uintptr_t>(this) % 64 == 0); ++live; ++moves; other.value = -1; }
    Probe& operator=(Probe&& other) noexcept { value = other.value; other.value = -1; return *this; }
    ~Probe() noexcept { --live; }
};
namespace detail { struct vector_access; }
class ProbeVector {
public:
@declaration@
private:
    struct storage;
    storage* storage_ = nullptr;
    friend struct detail::vector_access;
};
@implementation@
struct ProbeHash { struct storage; };
@hash@
}
int main() {
    Pair pair{3, 4.5}; assert(pair.first == 3 && pair.second == 4.5);
    using scpp::Probe; using scpp::ProbeVector;
    {
        ProbeVector values;
        assert(Probe::authored == 0 && values.empty());
        for (int i = 0; i < 129; ++i) values.append(Probe{i});
        assert(Probe::authored == 129 && Probe::copies == 0 && Probe::live == 129);
        assert(values.at(128).value == 128);
        const auto before = Probe::live;
        for (int prefix = 0; prefix < 129; ++prefix) {
            Probe::copy_budget = prefix;
            bool failed = false;
            try { ProbeVector copy(values); } catch (int code) { assert(code == 42); failed = true; }
            assert(failed && Probe::live == before && values.size() == 129);
        }
        Probe::copy_budget = -1;
        ProbeVector copy(values); copy.set(0, Probe{777});
        assert(values.at(0).value == 0 && copy.at(0).value == 777);
        values.append(values.at(0));
        assert(values.at(129).value == 0);
        ProbeVector destination; destination.append(Probe{999});
        Probe::copy_budget = 2;
        try { destination = values; assert(false); } catch (int) {}
        Probe::copy_budget = -1;
        assert(destination.size() == 1 && destination.at(0).value == 999);
        assert(values.remove(0) && values.at(0).value == 1);
        auto* self = &values; values = *self; values = static_cast<ProbeVector&&>(*self);
        auto moved = static_cast<ProbeVector&&>(values); assert(values.empty() && moved.size() == 129);
        Probe output{999}; scpp::int64 position = 0, key = -1;
        assert(moved.iteration_next(position, moved.iteration_limit(), key, output) && output.value == 1 && key == 0);
        const int live_before = Probe::live, copies_before = Probe::copies, moves_before = Probe::moves;
        destination = static_cast<ProbeVector&&>(moved);
        assert(Probe::live == live_before - 1 && Probe::copies == copies_before && Probe::moves == moves_before);
        assert(moved.empty() && destination.size() == 129);
        destination.clear(); assert(Probe::live == live_before - 130);
        moved.clear(); assert(!moved.iteration_next(position, 129, key, output) && output.value == 1);
    }
    assert(Probe::live == 0);
    {
        scpp::ProbeHash::storage values;
        const auto authored = Probe::authored;
        for (int i = 0; i < 300; ++i) values.set(i, Probe{i});
        for (int i = 0; i < 300; i += 2) assert(values.remove(i));
        assert(Probe::authored == authored + 300 && Probe::live == 150);
        assert(values.physical_size() == 300 && values.index_bytes() == 2);
        for (int prefix = 0; prefix < 150; ++prefix) {
            Probe::copy_budget = prefix;
            try { scpp::ProbeHash::storage copy(values); assert(false); } catch (int code) { assert(code == 42); }
            assert(Probe::live == 150 && values.size() == 150);
        }
        Probe::copy_budget = -1;
        scpp::ProbeHash::storage copy(values);
        assert(Probe::live == 300 && copy.physical_size() == 300);
        copy.set(1, Probe{777}); assert(values.find(1)->value == 1 && copy.find(1)->value == 777);
        copy.set(0, Probe{999}); assert(copy.physical_size() == 301);
        copy.clear(); assert(Probe::live == 150);
        for (int i = 300; i < 600; ++i) values.set(i, Probe{i});
        assert(values.size() == 450 && values.find(599)->value == 599);
        Probe output{999}; scpp::int64 position = 0, key = -1;
        Probe::copy_budget = 0;
        try { values.iteration_next(position, 600, output, &key); assert(false); } catch (int) {}
        assert(position == 0 && key == -1 && output.value == 999);
        Probe::copy_budget = -1;
        assert(values.iteration_next(position, 600, output, &key) && key == 1 && output.value == 1);
    }
    assert(Probe::live == 0);
    {
        ProbeVector values;
        ProbeVector::iteration_output output;
        const auto authored = Probe::authored;
        scpp::int64 position = 0, key = -1;
        assert(!output.active && !values.iteration_next(position, 0, output));
        assert(Probe::authored == authored);
        values.append(Probe{5}); values.append(Probe{6});
        Probe::copy_budget = 0;
        try { values.iteration_next(position, 2, output); assert(false); } catch (int code) { assert(code == 42); }
        assert(position == 0 && !output.active && Probe::live == 2);
        Probe::copy_budget = -1;
        assert(values.iteration_next(position, 2, key, output) && key == 0 && output.value.value == 5);
        Probe::copy_budget = 0;
        try { values.iteration_next(position, 2, key, output); assert(false); } catch (int) {}
        assert(position == 1 && key == 0 && output.value.value == 5);
        Probe::copy_budget = -1;
        for (int i = 0; i < 100; ++i) values.append(Probe{i});
        assert(values.iteration_next(position, 2, output) && output.value.value == 6);
        assert(!values.iteration_next(position, 2, output));
        values.clear(); assert(output.value.value == 6 && Probe::live == 1);
        auto moved = static_cast<ProbeVector::iteration_output&&>(output);
        assert(moved.active && moved.value.value == 6);
        position = -1;
        try { values.iteration_next(position, 2, moved); assert(false); } catch (const std::out_of_range&) {}
    }
    assert(Probe::live == 0);
    {
        scpp::ProbeConcreteHash values;
        scpp::ProbeConcreteHash::iteration_output output;
        scpp::int64 position = 0, key = -1;
        assert(!values.iteration_next(position, 0, output) && !output.active);
        values.set(0, Probe{10}); values.set(1, Probe{11}); values.set(2, Probe{12}); values.remove(0);
        Probe::copy_budget = 0;
        try { values.iteration_next(position, 3, key, output); assert(false); } catch (int) {}
        assert(position == 0 && key == -1 && !output.active && Probe::live == 2);
        Probe::copy_budget = -1;
        assert(values.iteration_next(position, 3, key, output) && key == 1 && output.value.value == 11);
        Probe::copy_budget = 0;
        try { values.iteration_next(position, 3, key, output); assert(false); } catch (int) {}
        assert(position == 2 && key == 1 && output.value.value == 11);
        Probe::copy_budget = -1;
        for (int i = 3; i < 100; ++i) values.set(i, Probe{i});
        assert(values.iteration_next(position, 3, output) && output.value.value == 12);
        assert(!values.iteration_next(position, 3, output));
        values.clear(); assert(output.value.value == 12 && Probe::live == 1);
        auto moved = static_cast<scpp::ProbeConcreteHash::iteration_output&&>(output);
        assert(moved.active && moved.value.value == 12);
    }
    assert(Probe::live == 0);
    const auto limit = scpp::detail::vector_capacity_limit(sizeof(Probe));
    try { (void)scpp::detail::vector_growth(limit, limit + 1, sizeof(Probe)); assert(false); }
    catch (const std::length_error&) {}
}
