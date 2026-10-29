#include "scpp/ownership.hpp"
#include <atomic>
#include <cstdio>
#include <cstdlib>
#include <new>
#include <stdexcept>
#include <source_location>
#include <thread>
#include <type_traits>
#include <utility>

namespace ownership_test { extern std::atomic<int> live, destroyed, copied; }
static std::atomic<long> allocations{0}, releases{0};
static std::atomic<bool> fail_allocation{false};
void *operator new(std::size_t size) {
    if (fail_allocation.exchange(false)) throw std::bad_alloc();
    if (void *p = std::malloc(size ? size : 1)) { ++allocations; return p; }
    throw std::bad_alloc();
}
void operator delete(void *p) noexcept { if (p) { ++releases; std::free(p); } }
void *operator new[](std::size_t n) { return ::operator new(n); }
void operator delete[](void *p) noexcept { ::operator delete(p); }
void operator delete(void *p, std::size_t) noexcept { ::operator delete(p); }
void operator delete[](void *p, std::size_t) noexcept { ::operator delete(p); }

using namespace scpp;
static void require(bool ok, std::source_location location = std::source_location::current()) {
    if (!ok) { std::fprintf(stderr, "Ownership proof failed at line %u\n", location.line()); throw std::runtime_error("Ownership proof failed"); }
}
template<class E, class F> static void rejects(F work) {
    try { work(); } catch (const E &) { return; }
    throw std::runtime_error("Expected ownership rejection");
}
template<class T> concept condition = requires(T t) { static_cast<bool>(t); };
template<class T> concept dereferences = requires(T t) { *t; t.operator->(); };
template<class T> concept releases_raw = requires(T t) { t.release(); };
template<class T> concept adopts_raw = requires(T t, record_probe *p) { t.reset(p); };

int main() {
    static_assert(!std::is_copy_constructible_v<unique_record_probe>);
    static_assert(!std::is_copy_assignable_v<unique_record_probe>);
    static_assert(std::is_nothrow_move_constructible_v<unique_record_probe>);
    static_assert(std::is_nothrow_copy_constructible_v<shared_record_noncopyable>);
    static_assert(std::is_nothrow_copy_constructible_v<weak_record_noncopyable>);
    static_assert(!condition<weak_record_probe> && !dereferences<weak_record_probe>);
    static_assert(!releases_raw<unique_record_probe> && !adopts_raw<unique_record_probe>);
    static_assert(!std::is_constructible_v<shared_record_probe, record_probe *>);
    static_assert(!std::is_convertible_v<shared_record_probe, record_probe>);
    static_assert(!std::is_convertible_v<shared_record_probe, weak_record_probe>);
    static_assert(noexcept(std::declval<weak_record_probe>().lock()));
    const auto empty_start = allocations.load();
    shared_record_probe empty;
    unique_record_probe empty_unique;
    weak_record_probe empty_weak;
    require(!empty && !empty_unique && !empty_weak.lock());
    empty.reset(); empty_unique.reset(); empty_weak.reset();
    require(allocations == empty_start);
    rejects<std::runtime_error>([&] { (void)*empty; });
    rejects<std::runtime_error>([&] { (void)empty.operator->(); });
    rejects<std::runtime_error>([&] { (void)*empty_unique; });
    rejects<std::runtime_error>([&] { (void)empty_unique.operator->(); });

    auto start = allocations.load();
    auto owner = shared_record_probe::make(int64{7});
    const auto shared_cost = allocations.load() - start;
    require(shared_cost == 1); // Supported native toolchain, nonallocating probe payload.
    const auto *address = &*owner;
    start = allocations.load();
    auto copy = owner;
    weak_record_probe observer{owner};
    auto observer_copy = observer;
    auto locked = observer_copy.lock();
    require(&*locked == address && &*copy == address);
    copy->value = int64{9};
    require(owner->value.native_value() == 9 && ownership_test::copied == 0);
    auto moved = std::move(copy);
    require(!copy && &*moved == address);
    copy = std::move(moved);
    auto &self = copy; copy = self; copy = std::move(self);
    weak_record_probe moved_observer{std::move(observer_copy)};
    require(!observer_copy.lock());
    observer_copy = std::move(moved_observer);
    auto &weak_self = observer_copy; observer_copy = weak_self; observer_copy = std::move(weak_self);
    const auto release_before_last_owner = releases.load();
    owner.reset(); copy.reset();
    require(ownership_test::live == 1);
    locked.reset();
    require(ownership_test::live == 0 && !observer.lock());
    require(releases == release_before_last_owner); // Weak observers retain the coallocated control block.
    observer.reset(); observer_copy.reset();
    require(releases == release_before_last_owner + 1);
    require(allocations == start);

    start = allocations.load();
    auto unique = unique_record_probe::make(int64{3});
    const auto unique_cost = allocations.load() - start;
    require(unique_cost == 1);
    start = allocations.load();
    auto moved_unique = std::move(unique);
    require(!unique && moved_unique->value.native_value() == 3);
    unique = std::move(moved_unique);
    auto &unique_self = unique; unique = std::move(unique_self);
    require(static_cast<bool>(unique));
    unique.swap(empty_unique); require(!unique && static_cast<bool>(empty_unique));
    empty_unique.reset();
    require(allocations == start && ownership_test::live == 0);

    // Noncopyable payloads remain shareable/observable and uniquely ownable.
    {
        auto noncopy = shared_record_noncopyable::make(int64{5});
        auto second = noncopy;
        weak_record_noncopyable weak{noncopy};
        auto acquired = weak.lock();
        require(&*acquired == &*second);
        auto exclusive = unique_record_noncopyable::make(int64{6});
        require(exclusive->value.native_value() == 6);
    }
    require(ownership_test::live == 0);
    // Failed construction must release its allocation, and replacement preserves the old owner.
    auto preserved = shared_record_probe::make(int64{11});
    const auto baseline = allocations.load() - releases.load();
    rejects<record_construction_failure>([&] { preserved = shared_record_probe::make(int64{-1}); });
    rejects<record_construction_failure>([] { (void)unique_record_probe::make(int64{-1}); });
    require(preserved->value.native_value() == 11 && allocations.load() - releases.load() == baseline);
    fail_allocation = true;
    rejects<std::bad_alloc>([&] { preserved = shared_record_probe::make(int64{12}); });
    fail_allocation = true;
    rejects<std::bad_alloc>([] { (void)unique_record_probe::make(int64{12}); });
    require(preserved->value.native_value() == 11);
    preserved.reset();

    // Distinct weak handles race lock against last strong release; payload is read-only.
    for (unsigned round = 0; round < 64; ++round) {
        auto strong = shared_record_probe::make(int64{42});
        weak_record_probe weak{strong};
        std::atomic<bool> ready{false}, go{false};
        const auto destroyed_before = ownership_test::destroyed.load();
        std::thread worker([weak, &ready, &go] {
            ready = true;
            while (!go.load()) std::this_thread::yield();
            for (unsigned i = 0; i < 256; ++i) {
                auto acquired = weak.lock();
                if (acquired) require(acquired->value.native_value() == 42);
            }
        });
        while (!ready.load()) std::this_thread::yield();
        go = true; strong.reset(); worker.join();
        require(!weak.lock() && ownership_test::live == 0);
        require(ownership_test::destroyed == destroyed_before + 1);
    }
    require(ownership_test::copied == 0);
    std::printf("handle bytes: shared=%zu weak=%zu unique=%zu\n", sizeof(shared_record_probe), sizeof(weak_record_probe), sizeof(unique_record_probe));
    std::printf("shared creation: %ld allocation; unique creation: %ld allocation; empty/copy/move/weak/lock: 0 allocations\n", shared_cost, unique_cost);
}
