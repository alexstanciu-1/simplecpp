// Native primitive proof only: no source foreach implementation is implied.
#include <scpp/vectors.hpp>
#include <scpp/hashes.hpp>
#include <scpp/mixed.hpp>
#include <scpp/dynamic.hpp>
#include "boxed_inspection.hpp"
#include "ownership_semantics.hpp"
#include <cassert>
#include <limits>
#include <memory>
#include <stdexcept>
#include <string>
#include <type_traits>

using scpp::int64;
static void check(bool ok) { if (!ok) throw std::runtime_error("iteration proof failed"); }
template<class F> static void fails(F action) {
    try { action(); } catch (const std::runtime_error&) { return; }
    throw std::logic_error("expected container failure");
}
template<class F> static void bounds(F action) {
    try { action(); } catch (const std::out_of_range&) { return; }
    throw std::logic_error("expected bounds failure");
}
template<class T> static T item(int64 i) {
    if constexpr (std::is_same_v<T, int64>) return i;
    else return scpp::string{std::to_string(i).c_str()};
}

template<class Vector, class Value> static void vector_proof() {
    Vector source; Value value; int64 key = -1, pos = 0;
    check(source.iteration_limit() == 0 && !source.iteration_next(pos, 0, key, value));
    for (int64 i = 0; i < 8; ++i) source.append(item<Value>(i));
    auto limit = source.iteration_limit();
    check(source.iteration_next(pos, limit, key, value) && key == 0 && value == item<Value>(0));
    for (int64 i = 8; i < 2048; ++i) source.append(item<Value>(i)); // buffer relocation
    int64 seen = 1;
    while (source.iteration_next(pos, limit, key, value)) { check(key == seen); ++seen; }
    check(seen == 8); // captured limit
    pos = 0;
    check(source.iteration_next(pos, limit, value));
    source.remove(int64{0}); // shifting must not leave a slot reference
    check(source.iteration_next(pos, limit, value));
    source.clear(); check(!source.iteration_next(pos, limit, value));
    source.append(item<Value>(42)); pos = 0;
    check(source.iteration_next(pos, limit, value) && value == item<Value>(42));
    Vector replacement; replacement.append(item<Value>(99)); source = replacement; pos = 0;
    check(source.iteration_next(pos, limit, value) && value == item<Value>(99));
    auto moved = std::move(source); pos = 0; check(!source.iteration_next(pos, limit, value));
    source = std::move(moved); pos = 0; check(source.iteration_next(pos, limit, value));
    pos = std::numeric_limits<int64>::max(); check(!source.iteration_next(pos, pos, value));
    pos = -1; bounds([&] { source.iteration_next(pos, limit, value); });
    pos = 0; bounds([&] { source.iteration_next(pos, -1, value); });
    for (int run = 0; run < 50; ++run) { pos = 0; source.iteration_next(pos, source.iteration_limit(), value); }
}

template<class Hash, class Value, class Key> static void hash_proof() {
    Hash source; Value value; Key key; int64 pos = 0;
    check(source.iteration_limit() == 0 && !source.iteration_next(pos, 0, value));
    for (int64 i = 0; i < 512; ++i) source.set(item<Key>(i), item<Value>(i));
    for (int64 i = 0; i < 511; ++i) check(source.remove(item<Key>(i)));
    const auto limit = source.iteration_limit(); check(limit == 512 && source.size() == 1);
    int64 key_position = 0;
    check(source.iteration_next_key(key_position, limit, key) && key == item<Key>(511));
    check(source.iteration_current_value(key_position) == item<Value>(511));
    bounds([&] { (void)source.iteration_current_value(1); }); // removed slot
    bounds([&] { (void)source.iteration_current_value(0); });
    check(key_position == limit && !source.iteration_next_key(key_position, limit, key) && key == item<Key>(511));
    key_position = -1;
    bounds([&] { source.iteration_next_key(key_position, limit, key); });
    check(source.iteration_next(pos, limit, key, value) && key == item<Key>(511) && value == item<Value>(511));
    check(!source.iteration_next(pos, limit, key, value));
    source.set(item<Key>(999), item<Value>(999)); // appends beyond captured limit
    check(!source.iteration_next(pos, limit, value));
    pos = 0; check(source.iteration_next(pos, limit, value));
    source.clear(); check(!source.iteration_next(pos, limit, value));
    Hash replacement; replacement.set(item<Key>(8), item<Value>(18)); source = replacement; pos = 0;
    check(source.iteration_next(pos, limit, key, value) && value == item<Value>(18));
    for (int64 i = 0; i < 2048; ++i) source.set(item<Key>(i), item<Value>(i)); // rehash/growth
    check(source.iteration_next(pos, limit, value));
    auto moved = std::move(source); check(!source.iteration_next(pos, limit, value));
    source = std::move(moved); pos = 0;
    int64 inner = 0; Value inner_value;
    check(source.iteration_next(pos, limit, value) && source.iteration_next(inner, limit, inner_value));
    check(value == inner_value && pos == inner);
    pos = -1; bounds([&] { source.iteration_next(pos, limit, value); });
}

template<class Box, scpp::detail::boxed_family Family> static void boxed_proof() {
    using Native = scpp::detail::boxed_value<Family>;
    const auto table = [](const Box& source) {
        return &scpp::detail::boxed_inspection::table(scpp::detail::ownership_access::get<Native>(source));
    };
    Box source = Box::container(), value; int64 pos = 0, key = -2; scpp::string text;
    check(source.iteration_limit() == 0 && !source.iteration_next(pos, 0, value));
    for (int64 i = 0; i < 8; ++i) source.append(Box::from_integer(i));
    const auto original = table(source); const auto limit = source.iteration_limit();
    check(source.iteration_next(pos, limit, key, text, value) && key == 0 && value.get_integer() == 0);
    source.set(int64{1}, Box::from_integer(int64{10}));
    check(table(source) == original); // traversal did not introduce a COW owner
    check(source.iteration_next(pos, limit, value) && value.get_integer() == 10);
    auto alias = source;
    source.set(int64{2}, Box::from_integer(int64{20}));
    if constexpr (Family == scpp::detail::boxed_family::mixed) {
        check(table(source) != original && alias.read(int64{2}).get_integer() == 2);
    } else {
        check(table(source) == original && alias.read(int64{2}).get_integer() == 20);
    }
    check(source.iteration_next(pos, limit, value) && value.get_integer() == 20);
    source.remove(int64{4}); check(!source.is_packed_container()); // packed -> associative and a hole
    source.set(scpp::string{"k\0x", int64{3}}, Box::from_integer(int64{30}));
    for (int64 i = 8; i < 1024; ++i) source.set(i, Box::from_integer(i));
    int64 steps = 0;
    while (source.iteration_next(pos, limit, key, text, value)) { check(key != 4); ++steps; }
    check(steps == 4);
    pos = 0; bool saw_text = false;
    while (source.iteration_next(pos, source.iteration_limit(), key, text, value)) {
        if (key == -1) { check(text.size() == 3 && text.byte_at(int64{1}) == 0); saw_text = true; }
    }
    check(saw_text);
    // Replacement follows the wrapper; scalar/null replacement fails before any table access.
    source = Box::container(); source.append(Box::from_integer(int64{77})); pos = 0;
    check(source.iteration_next(pos, limit, value) && value.get_integer() == 77);
    source = Box::from_integer(int64{9}); fails([&] { source.iteration_next(pos, limit, value); });
    source.clean(); fails([&] { source.iteration_next(pos, limit, value); });
    fails([&] { source.iteration_limit(); });
    source = Box::container(); source.append(Box::from_integer(int64{1})); pos = -1;
    bounds([&] { source.iteration_next(pos, limit, value); });
    // Even aliasing output with the receiver must not read its destroyed table afterwards.
    alias.clean(); pos = 0;
    check(source.iteration_next(pos, limit, source) && source.get_integer() == 1);
    fails([&] { source.iteration_next(pos, limit, value); });
    source = Box::container(); source.set(scpp::string{"long-key-that-needs-heap-storage"}, Box::from_integer(int64{5})); pos = 0;
    check(source.iteration_next(pos, limit, key, text, source) && source.get_integer() == 5 && key == -1);
    // Nested values retain their family's ordinary copy semantics, never recursive snapshots.
    Box child = Box::container(); child.append(Box::from_integer(int64{3})); source = Box::container(); source.append(child); pos = 0;
    check(source.iteration_next(pos, source.iteration_limit(), value));
    value.set(int64{0}, Box::from_integer(int64{6}));
    check(source.read(int64{0}).read(int64{0}).get_integer() == (Family == scpp::detail::boxed_family::mixed ? 3 : 6));
}

int main() {
    vector_proof<scpp::vector_int64, int64>(); vector_proof<scpp::vector_string, scpp::string>();
    hash_proof<scpp::hash_int64_int64, int64, int64>();
    hash_proof<scpp::hash_int64_string, int64, scpp::string>();
    hash_proof<scpp::hash_string_int64, scpp::string, int64>();
    hash_proof<scpp::hash_string_string, scpp::string, scpp::string>();
    boxed_proof<scpp::mixed, scpp::detail::boxed_family::mixed>();
    boxed_proof<scpp::dynamic, scpp::detail::boxed_family::dynamic>();
    // The loop caller, not the primitive, owns wrapper lifetime.
    auto owner = std::make_shared<scpp::vector_int64>(); owner->append(int64{7});
    auto retained = owner; int64 pos = 0, value = 0; const auto limit = retained->iteration_limit(); owner.reset();
    check(retained->iteration_next(pos, limit, value) && value == 7);
}
