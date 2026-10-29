#include "packed_table.hpp"
#include "../hash/allocation_probe.hpp"
#include <algorithm>
#include <cstdio>
#include <cstdlib>
#include <memory>
#include <random>
#include <string>
#include <utility>
#include <vector>

using namespace scpp::detail;
using table = packed_table<int>;
void require(bool condition) { if (!condition) std::abort(); }
template<class Exception, class F> void throws(F&& fn) {
    bool caught = false;
    try { fn(); } catch (const Exception&) { caught = true; }
    require(caught);
}
using entries = std::vector<std::pair<std::uint32_t, int>>;
entries snapshot(const table& t) {
    entries result;
    t.visit_entries([&](auto key, auto value) { result.emplace_back(key, value); });
    return result;
}
std::uint32_t identity_from_other_translation_unit(std::string_view);

void semantics() {
    table t;
    require(t.is_packed() && t.size() == 0 && t.key_capacity() == 0 && t.index_bytes() == 0);
    for (int i = 0; i < 1000; ++i) require(t.append(i) == i);
    require(t.key_capacity() == 0 && t.index_bytes() == 0);
    require(t.remove(5));
    require(!t.is_packed() && !t.find(5) && *t.find(6) == 6);
    require(t.append(1000) == 1000);
    require(t.remove(1000) && t.append(1001) == 1000);
    t.set(6, 60);
    auto rows = snapshot(t);
    require(rows[5] == std::pair<std::uint32_t, int>{6, 60});
    t.set(5, 50);
    require(snapshot(t).back() == std::pair<std::uint32_t, int>{5, 50});
    for (std::int64_t invalid : {-1LL, 2147483648LL, 4294967297LL, 9223372036854775807LL}) {
        auto before = snapshot(t);
        throws<std::out_of_range>([&] { t.set(invalid, 9); });
        throws<std::out_of_range>([&] { (void)t.find(invalid); });
        throws<std::out_of_range>([&] { (void)t.remove(invalid); });
        require(snapshot(t) == before);
    }
    t.set(packed_key::max_integer, 123);
    auto before = snapshot(t);
    throws<std::overflow_error>([&] { (void)t.append(456); });
    require(snapshot(t) == before);
    require(t.remove(packed_key::max_integer));
    require(t.append(7) == 1001);
    table edge;
    edge.set(static_cast<std::int64_t>(packed_key::max_integer) - 1, 1);
    require(edge.append(2) == packed_key::max_integer);
    throws<std::overflow_error>([&] { (void)edge.append(3); });
    table only_strings; only_strings.set("text", 1);
    require(only_strings.append(2) == 0);
    t.clear();
    require(t.is_packed() && t.append(8) == 0);
    t.set("12", 12); t.set(12, 13);
    require(*t.find("12") == 12 && *t.find(12) == 13);
    std::string nul("a\0b", 3);
    t.set(nul, 40); t.set("a", 41);
    require(*t.find(nul) == 40 && *t.find("a") == 41);
    auto& pool = packed_string_pool::instance();
    require(pool.resolve(pool.intern(nul)) == nul);
    require(identity_from_other_translation_unit(nul) == pool.intern(nul));
    const auto pool_size = pool.size();
    const auto allocations = allocation_probe::calls;
    require(!t.find("never-interned-string") && !t.remove("never-interned-string"));
    require(pool.size() == pool_size && allocation_probe::calls == allocations);
    auto copy = t;
    t.set(nul, 99);
    require(*copy.find(nul) == 40);
    table moved(std::move(copy));
    require(copy.size() == 0 && copy.is_packed() && copy.append(3) == 0);
    copy = std::move(moved);
    require(moved.size() == 0 && *copy.find(nul) == 40);
    table* self = &copy;
    copy = *self; copy = std::move(*self);
    require(*copy.find(nul) == 40);
    packed_table<std::shared_ptr<int>> handles;
    auto value = std::make_shared<int>(1);
    handles.set("shared", value);
    auto aliases = handles;
    **aliases.find("shared") = 2;
    require(**handles.find("shared") == 2);
    handles.clear(); aliases.clear(); require(value.use_count() == 1);
    packed_table<std::string> strings;
    strings.append("owned"); auto independent = strings;
    strings.set(0, "changed"); require(*independent.find(0) == "owned");
}

void limits_and_pool() {
    require(packed_key::integer(2147483647) == 0x7fffffffu);
    require(packed_key::string_id(0x7ffffffeu) == 0xfffffffeu);
    throws<std::overflow_error>([] { (void)packed_key::string_id(0x7fffffffu); });
    throws<std::overflow_error>([] { (void)packed_key::string_id(std::numeric_limits<std::size_t>::max()); });
    throws<std::length_error>([] { (void)table::checked_capacity(0x100000000ull); });
    packed_string_pool pool(2);
    auto first = pool.intern("first"), second = pool.intern("second");
    require(first != second && pool.intern("first") == first);
    throws<std::overflow_error>([&] { (void)pool.intern("third"); });
    require(pool.size() == 2 && !pool.find("third") && pool.resolve(first) == "first");
    throws<std::out_of_range>([&] { (void)pool.resolve(packed_key::tombstone); });
    throws<std::out_of_range>([&] { (void)pool.resolve(0); });
    bool saw_failure = false, saw_success = false;
    for (long n = 0; n < 16; ++n) {
        packed_string_pool tested;
        auto stable = tested.intern("stable");
        allocation_probe::fail_after = n;
        bool failed = false;
        try { (void)tested.intern("long-string-that-requires-its-own-allocation"); }
        catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        require(tested.resolve(stable) == "stable");
        if (failed) {
            saw_failure = true;
            require(tested.size() == 1 && !tested.find("long-string-that-requires-its-own-allocation"));
        } else saw_success = true;
        auto id = tested.intern("long-string-that-requires-its-own-allocation");
        require(tested.resolve(id) == "long-string-that-requires-its-own-allocation" && tested.size() == 2);
    }
    require(saw_failure && saw_success);
}

template<class Operation> void failure_sweep(const table& seed, Operation operation) {
    const auto expected = snapshot(seed);
    bool failure = false, success = false;
    for (long n = 0; n < 24; ++n) {
        table t = seed;
        allocation_probe::fail_after = n;
        bool failed = false;
        try { operation(t); } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) {
            failure = true;
            require(snapshot(t) == expected && t.size() == seed.size());
            for (auto [key, value] : expected) require(*t.find(static_cast<std::int64_t>(key)) == value);
            operation(t); // the same object remains usable after failure
        } else success = true;
    }
    require(failure && success);
}
void failures() {
    table dense; dense.append(10); dense.append(20);
    failure_sweep(dense, [](table& t) { t.set(5, 30); });
    failure_sweep(dense, [](table& t) { require(t.remove(0)); });
    table wide; wide.set(10000, 7);
    for (int i = 0; i < 255; ++i) { wide.set(i, i); wide.remove(i); }
    require(wide.physical_size() == 256);
    failure_sweep(wide, [](table& t) { t.set(999, 30); });
    // A completed interning step may survive a later table failure, but must
    // remain resolvable. Each spelling is new, exercising both owners together.
    for (long n = 0; n < 20; ++n) {
        table t = dense;
        const auto before = snapshot(t);
        const auto name = std::string("new-identity-before-table-failure-") + std::to_string(n);
        allocation_probe::fail_after = n;
        bool failed = false;
        try { t.set(name, 99); } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) require(snapshot(t) == before && !t.find(name));
        auto& pool = packed_string_pool::instance();
        if (auto code = pool.find(name)) require(pool.resolve(*code) == name);
        t.set(name, 99); require(*t.find(name) == 99);
    }
    bool failed_copy = false;
    for (long n = 0; n < 16; ++n) {
        table destination; destination.append(77);
        allocation_probe::fail_after = n;
        bool failed = false;
        try { destination = wide; } catch (const std::bad_alloc&) { failed = true; }
        allocation_probe::fail_after = -1;
        if (failed) { failed_copy = true; require(destination.size() == 1 && *destination.find(0) == 77); }
        else require(snapshot(destination) == snapshot(wide));
    }
    require(failed_copy);
}
void trace_and_widths() {
    table t;
    entries reference;
    std::mt19937 random(12345);
    for (int step = 0; step < 12000; ++step) {
        auto key = random() % 150;
        auto it = std::find_if(reference.begin(), reference.end(), [&](auto row) { return row.first == key; });
        if (random() % 3 == 0) {
            require(t.remove(static_cast<std::int64_t>(key)) == (it != reference.end()));
            if (it != reference.end()) reference.erase(it);
        } else {
            t.set(static_cast<std::int64_t>(key), step);
            if (it == reference.end()) reference.emplace_back(key, step); else it->second = step;
        }
        require(snapshot(t) == reference && t.size() == reference.size());
        for (auto [k, v] : reference) require(*t.find(static_cast<std::int64_t>(k)) == v);
    }
    table churn; churn.set(1000000, 1);
    for (int i = 0; i < 65536; ++i) {
        churn.set(i, i);
        require(*churn.find(i) == i && *churn.find(1000000) == 1);
        if (i == 254) require(churn.index_bytes() == 1);
        if (i == 255) require(churn.index_bytes() == 2);
        if (i == 65535) require(churn.index_bytes() == 4);
        require(churn.remove(i));
    }
    require(churn.size() == 1);
}
int main() {
    semantics(); limits_and_pool(); failures(); trace_and_widths();
    table dense, sparse;
    for (int i = 0; i < 1000; ++i) { dense.append(i); sparse.set(i * 2, i); }
    std::printf("packed storage passed; object=%zu dense1000=%zu sparse1000=%zu\n", sizeof(table), dense.estimated_storage_bytes(), sparse.estimated_storage_bytes());
}
