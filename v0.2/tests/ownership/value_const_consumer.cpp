#include "scpp/ownership.hpp"
#include "allocation_probe.hpp"
#include <cstdio>
#include <source_location>
#include <stdexcept>
#include <type_traits>
#include <utility>

using namespace scpp;
namespace ownership_test { extern std::atomic<int> live, destroyed, copied; }
static void require(bool ok, std::source_location location = std::source_location::current()) {
    if (!ok) { std::fprintf(stderr, "Value/const proof failed at line %u\n", location.line()); std::abort(); }
}
template<class E, class F> static void rejects(F work) {
    try { work(); } catch (const E &) { return; }
    require(false);
}
int main() {
    static_assert(sizeof(value_record_probe) == sizeof(record_probe));
    static_assert(sizeof(value_record_move_only) == sizeof(record_move_only));
    static_assert(alignof(value_record_move_only) == 32);
    static_assert(!std::is_default_constructible_v<value_record_probe>);
    static_assert(!std::is_convertible_v<int64, value_record_probe>);
    static_assert(!std::is_copy_constructible_v<value_record_noncopyable>);
    static_assert(!std::is_move_constructible_v<value_record_noncopyable>);
    static_assert(!std::is_copy_constructible_v<value_record_move_only>);
    static_assert(std::is_nothrow_move_constructible_v<value_record_move_only>);
    static_assert(std::is_nothrow_move_assignable_v<value_record_move_only>);
    static_assert(std::is_nothrow_default_constructible_v<value_record_move_only>);
    static_assert(!std::is_nothrow_move_constructible_v<value_record_throwing_move>);
    static_assert(!std::is_nothrow_move_assignable_v<value_record_throwing_move>);
    static_assert(std::is_same_v<decltype(*std::declval<const value_record_probe &>()), const record_probe &>);
    static_assert(std::is_same_v<decltype(*std::declval<const shared_record_probe &>()), record_probe &>);
    static_assert(std::is_same_v<decltype(*std::declval<shared_const_record_probe &>()), const record_probe &>);
    static_assert(std::is_same_v<decltype(*std::declval<unique_const_record_probe &>()), const record_probe &>);
    static_assert(std::is_same_v<decltype(std::declval<weak_const_record_probe>().lock()), shared_const_record_probe>);
    static_assert(!std::is_convertible_v<shared_record_probe, shared_const_record_probe>);
    static_assert(!std::is_constructible_v<shared_record_probe, shared_const_record_probe>);
    static_assert(!std::is_constructible_v<unique_record_probe, unique_const_record_probe>);
    static_assert(!std::is_constructible_v<weak_record_probe, weak_const_record_probe>);
    static_assert(!std::is_constructible_v<unique_const_record_probe, const unique_record_probe &>);
    static_assert(std::is_nothrow_constructible_v<shared_const_record_probe, shared_record_probe &&>);
    const auto start = allocations.load();
    const auto released_start = releases.load();
    {
        value_record_probe a{int64{7}};
        auto b = a;
        b->value = int64{8};
        require(a->value.native_value() == 7 && b->value.native_value() == 8);
        value_record_noncopyable immobile{int64{4}};
        require(immobile->value.native_value() == 4);
        value_record_move_only m{int64{9}}, destination;
        auto moved = std::move(m);
        destination = std::move(moved);
        require(destination->value.native_value() == 9 && m->value.native_value() == 0);
        value_record_throwing_move throwing{int64{-1}}, kept{int64{10}};
        rejects<record_move_failure>([&] { auto bad = std::move(throwing); (void)bad; });
        rejects<record_move_failure>([&] { kept = std::move(throwing); });
        require(kept->value.native_value() == 10);
        value_record_throwing_move successful{int64{11}};
        kept = std::move(successful);
        require(kept->value.native_value() == 11 && successful->value.native_value() == 0);
    }
    require(allocations == start && ownership_test::live == 0);
    {
        auto mutable_owner = shared_record_probe::make(int64{20});
        auto unique_owner = unique_record_probe::make(int64{30});
        const auto before = allocations.load();
        const auto copies = ownership_test::copied.load();
        shared_const_record_probe view{mutable_owner};
        weak_record_probe mutable_weak{mutable_owner};
        weak_const_record_probe observer{mutable_weak}, from_shared{mutable_owner};
        weak_const_record_probe moved_weak{std::move(mutable_weak)};
        require(!mutable_weak.lock());
        auto locked = observer.lock();
        require(&*locked == &*mutable_owner && &*from_shared.lock() == &*view);
        mutable_owner->value = int64{21};
        require(view->value.native_value() == 21); // Read-only access is not deep immutability.
        shared_const_record_probe moved{std::move(mutable_owner)};
        require(!mutable_owner && &*moved == &*view);
        unique_const_record_probe exclusive{std::move(unique_owner)};
        require(!unique_owner && exclusive->value.native_value() == 30);
        auto const_copy = view;
        weak_const_record_probe const_observer{const_copy};
        require(&*const_observer.lock() == &*view);
        const_copy.reset(); locked.reset(); view.reset(); moved.reset();
        require(!observer.lock() && !moved_weak.lock() && !from_shared.lock());
        exclusive.reset();
        require(allocations == before && ownership_test::copied == copies);
    }
    require(ownership_test::live == 0);
    const auto creation = allocations.load();
    {
        auto shared = shared_const_record_noncopyable::make(int64{40});
        auto unique = unique_const_record_noncopyable::make(int64{50});
        weak_const_record_noncopyable weak{shared};
        require(weak.lock()->value.native_value() == 40 && unique->value.native_value() == 50);
        require(allocations == creation + 2);
    }
    require(allocations.load() - start == releases.load() - released_start);
    shared_const_record_probe empty;
    unique_const_record_probe empty_unique;
    rejects<std::runtime_error>([&] { (void)*empty; });
    rejects<std::runtime_error>([&] { (void)*empty_unique; });
    require(ownership_test::live == 0);
    std::puts("Inline value wrapper allocations: 0; const qualification/copy/move/lock allocations: 0; const owner creation: 1 each");
}
