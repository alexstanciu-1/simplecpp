#include "scpp/foreach.hpp"
#include "scpp/shared_p.hpp"
#include <cassert>
#include <type_traits>
#include <utility>

struct record { int value = 7; };
using handle = scpp::shared_p<record>;
using map_type = scpp::hash_t<handle, handle>;
using const_entry = decltype(*scpp::foreach_range(std::declval<const map_type&>()).begin());
using mutable_entry = decltype(*scpp::foreach_range(std::declval<map_type&>()).begin());
static_assert(std::is_same_v<decltype(std::declval<const_entry>().value_ref()), const handle&>);
static_assert(std::is_same_v<decltype(std::declval<mutable_entry>().value_ref()), handle&>);
static_assert(!std::is_assignable_v<decltype(std::declval<const_entry>().value_ref()), handle>);

int main() {
    auto first = handle(std::make_shared<record>());
    auto removed = handle(std::make_shared<record>());
    auto last = handle(std::make_shared<record>());
    auto value = handle(std::make_shared<record>());
    map_type map;
    map.set(first, value);
    map.set(removed, value);
    map.set(last, value);
    assert(map.remove(removed));
    const map_type& view = map;
    const auto key_owners = first.native_value().use_count();
    const auto value_owners = value.native_value().use_count();
    auto range = scpp::foreach_range(view);
    assert(first.native_value().use_count() == key_owners);
    assert(value.native_value().use_count() == value_owners);
    int count = 0;
    for (auto entry : range) {
        assert(entry.key().get() == (count == 0 ? first.get() : last.get()));
        assert(&entry.value_ref() == &view.at(entry.key())); // Original membership, no snapshot.
        auto copy = entry.value_copy();
        assert(copy.get() == value.get());
        copy->value = 11; // Const container membership does not make shared pointees const.
        ++count;
    }
    assert(count == 2 && value->value == 11);
    const map_type empty;
    assert(scpp::foreach_range(empty).begin() == scpp::foreach_range(empty).end());
    // Mutable iteration retains its existing entry replacement behavior.
    for (auto entry : scpp::foreach_range(map)) entry.value_ref() = first;
    assert(map.at(last).get() == first.get());
    scpp::hash_t<scpp::int_t<>> scalar;
    scalar.set(scpp::string_t("x"), scpp::int_t<>(3));
    for (auto entry : scpp::foreach_range(std::as_const(scalar))) {
        assert(entry.value_copy().native_value() == 3);
    }
}
