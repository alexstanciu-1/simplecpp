#include "typed_hash.hpp"
#include "allocation_probe.hpp"
#include <algorithm>
#include <chrono>
#include <cstdio>
#include <map>
#include <string>

static void require(bool ok) { if (!ok) throw std::runtime_error("hash storage proof failed"); }
struct integer {
    std::int64_t value = 0;
    integer() noexcept = default;
    explicit integer(std::int64_t n) noexcept : value(n) {}
    std::int64_t native_value() const noexcept { return value; }
};
using policy = scpp::detail::integer_hash_key<integer>;
using table = scpp::detail::typed_hash<integer, integer, policy>;
struct collisions : policy { static std::uint64_t hash(const integer&) noexcept { return 7; } };
using collision_table = scpp::detail::typed_hash<integer, integer, collisions>;

static void compare_trace() {
    collision_table actual;
    std::map<std::int64_t, std::int64_t> expected;
    std::vector<std::int64_t> order;
    std::uint32_t random = 12345;
    for (unsigned step = 0; step < 6000; ++step) {
        random = random * 1664525 + 1013904223;
        const auto key = static_cast<std::int64_t>((random >> 8) % 97) - 16;
        if (random % 3) {
            if (!expected.contains(key)) order.push_back(key);
            expected[key] = step;
            actual.set(integer{key}, integer{step});
        } else {
            const bool removed = expected.erase(key) != 0;
            require(actual.remove(integer{key}) == removed);
            if (removed) std::erase(order, key);
        }
        require(actual.size() == expected.size());
        std::size_t position = 0;
        actual.visit_entries([&](const auto& k, const auto& v) {
            require(position < order.size() && k.value == order[position++]);
            require(v.value == expected.at(k.value));
        });
        require(position == order.size());
        for (std::int64_t k = -16; k <= 80; ++k) {
            const auto* value = actual.find(integer{k});
            require((value != nullptr) == expected.contains(k));
            if (value) require(value->value == expected.at(k));
        }
    }
}
static void churn() {
    table values;
    for (int i = 0; i < 66000; ++i) {
        values.set(integer{i+100}, integer{i});
        if (i >= 16) require(values.remove(integer{i+84}));
        require(values.size() == static_cast<unsigned>(std::min(i+1,16)));
        require(values.physical_size() == static_cast<unsigned>(i+1));
        require(values.index_bytes() == (i < 256 ? 1u : i < 65536 ? 2u : 4u));
        require(values.find(integer{i+100})->value == i);
    }
    int expected = 65984;
    values.visit_entries([&](const auto& k, const auto& v) { require(k.value == expected+100 && v.value == expected); ++expected; });
    require(expected == 66000);
    table copy(values);
    require(copy.size() == 16 && copy.index_bytes() == 4);
    auto moved = std::move(copy);
    require(copy.size() == 0 && moved.size() == 16);
    copy.set(integer{1},integer{2});
    auto* self = &copy; copy = *self; copy = std::move(*self);
    require(copy.find(integer{1})->value == 2);
}
static void append() {
    table values;
    values.set(integer{-10},integer{1});
    require(values.append(integer{2}).value == 0);
    values.set(integer{10},integer{3});
    require(values.append(integer{4}).value == 11);
    values.remove(integer{10});
    require(values.append(integer{5}).value == 12);
    values.remove(integer{12});
    require(values.append(integer{6}).value == 12);
    values.set(integer{INT64_MAX},integer{7});
    try { (void) values.append(integer{8}); require(false); } catch(const std::overflow_error&) {}
    require(values.size() == 5 && values.find(integer{INT64_MAX})->value == 7);
    values.remove(integer{INT64_MAX});
    require(values.append(integer{9}).value == 13);
    values.clear(); require(values.append(integer{0}).value == 0);
    try { (void) table::checked_capacity(std::uint64_t{UINT32_MAX}+1); require(false); } catch(const std::length_error&) {}
}
static void failures() {
    // Inject each allocation failure, including after key/value/live growth and
    // while building a replacement index. Recovery must remain usable.
    for (int count : {0, 3, 4, 255, 256}) {
        bool succeeded = false;
        for (int fail = 0; fail < 16; ++fail) {
            table values;
            for (int i = 0; i < count; ++i) values.set(integer{i}, integer{i*2});
            allocation_probe::fail_after = fail;
            bool failed = false;
            try { values.set(integer{count}, integer{777}); } catch(const std::bad_alloc&) { failed = true; }
            allocation_probe::fail_after = -1;
            require(values.size() == static_cast<unsigned>(count + !failed));
            for(int i=0;i<count;++i) require(values.find(integer{i})->value==i*2);
            if (failed) {
                require(!values.find(integer{count}));
                require(values.physical_size() == static_cast<unsigned>(count));
                values.set(integer{count},integer{888});
                require(values.find(integer{count})->value==888);
            } else { succeeded=true;break; }
        }
        require(succeeded);
    }
    using strings = scpp::detail::typed_hash<std::string, integer, policy>;
    strings source; source.set(integer{1},std::string(256,'a'));
    bool succeeded = false;
    for (int fail = 0; fail < 16; ++fail) {
        strings destination; destination.set(integer{9},"kept");
        allocation_probe::fail_after=fail;
        bool failed=false;
        try { destination=source; } catch(const std::bad_alloc&) { failed=true; }
        allocation_probe::fail_after=-1;
        require(source.find(integer{1})->size()==256);
        if(failed) require(destination.size()==1 && *destination.find(integer{9})=="kept");
        else { require(destination.size()==1 && destination.find(integer{1})->size()==256); succeeded=true;break; }
    }
    require(succeeded);
}
int main() {
    compare_trace(); append(); failures();
    const auto start=std::chrono::steady_clock::now();
    const auto before=allocation_probe::calls;
    churn();
    const auto micros=std::chrono::duration_cast<std::chrono::microseconds>(std::chrono::steady_clock::now()-start).count();
    std::printf("typed hash: 66000-entry churn + copy proof: %lld us, %zu allocations\n",static_cast<long long>(micros),allocation_probe::calls-before);
}
