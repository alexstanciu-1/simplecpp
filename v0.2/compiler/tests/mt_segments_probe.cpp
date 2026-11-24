// The harness includes the generated vector header and binds Rows to that concrete type.
#include <scpp/mt_segments.hpp>
#include <scpp/vectors.hpp>
#include <scpp/hashes.hpp>
#include <algorithm>
#include <array>
#include <set>
#include <atomic>
#include <cassert>
#include <cstdio>
#include <limits>
#include <latch>
#include <barrier>
#include <stdexcept>
#include <string_view>
#include <thread>
#include <vector>
using Point = App::scpp_records::Point;
using Int = scpp::int64;
using scpp::mt::segment;
namespace App {
Int record_hash_();
scpp::vector_int64 read_pair_(scpp::hash_int64_int64&, Int, Int);
scpp::vector_int64 hash_batch_(scpp::hash_int64_int64&, scpp::hash_int64_int64&, scpp::vector_int64&, Int);
Int work_(segment, segment);
Int note_entry_(Int);
scpp::vector_int64 independent_caps_(scpp::vector_int64&);
scpp::vector_int64 capped_batch_(scpp::vector_int64&, Int, Int, Int&);
scpp::vector_int64 each_batch_(scpp::vector_int64&, scpp::vector_int64&, Int, Int, Int);
scpp::mt::segment_range range_for_(Int, Int, Int);
Int snapshot_range_();
Int range_end_(scpp::mt::segment_range);
scpp::vector_int64 range_batch_(const scpp::vector_int64&, scpp::vector_int64&, Int, Int);
scpp::vector_int64 read_batch_(const Rows&, scpp::vector_int64&, Int);
scpp::vector_int64 pair_batch_(scpp::vector_int64&, scpp::vector_int64&, Int);
scpp::vector_int64 mixed_batch_(scpp::vector_int64&, scpp::vector_int64&, Int);
scpp::vector_int64 dispatch_(Rows&, scpp::vector_int64&, scpp::vector_int64&, Int);
}

template<class T> concept exposes_mt_data = requires(T& value) { value.mt_data(); };
static_assert(!exposes_mt_data<Rows> && !exposes_mt_data<scpp::vector_int64>);

static Point* points[2];
static Int* adjustments[2];
static std::atomic<int> entries{}, exits{};
static int failing_offset = -1;
static std::thread::id caller;
static int calls[2];
static thread_local int calls_on_this_thread;
static Int observed(const segment* inputs) {
    const auto& rows = inputs[0];
    const auto& deltas = inputs[1];
    ++entries;
    while (entries.load() < 2) std::this_thread::yield();
    const int offset = rows.at(0) == points[0] ? 0 : 1;
    assert((std::this_thread::get_id() == caller) == (offset == 0));
    calls[offset] = ++calls_on_this_thread;
    assert(rows.at(0) == points[offset] && deltas.at(0) == adjustments[offset]);
    auto result = App::work_(rows, deltas);
    ++exits;
    if (offset == failing_offset) rows.at(1); // Out of bounds after a completed in-place update.
    return result;
}

static void check_ranges() {
    assert(App::snapshot_range_() == 123);
    for (Int size = 0; size <= 10; ++size) {
        for (Int parts = 1; parts <= 12; ++parts) {
            Int next = 0;
            for (Int part = 0; part < parts; ++part) {
                auto range = App::range_for_(size, parts, part);
                assert(range.offset == next && range.length == size / parts + (part < size % parts));
                next += range.length;
                assert(App::range_end_(range) == next);
            }
            assert(next == size);
        }
    }
    auto max = std::numeric_limits<Int>::max();
    assert(App::range_end_(App::range_for_(max, 3, 2)) == max);
    assert(App::range_end_(App::range_for_(max, max, max - 1)) == max);
    for (auto [length, parts, part] : {std::array<Int,3>{-1,1,0}, {1,0,0}, {1,-1,0}, {1,1,-1}, {1,1,1}}) {
        try { App::range_for_(length, parts, part); assert(false); } catch (const std::invalid_argument&) {}
    }
    scpp::vector_int64 input, output;
    for (Int i = 0; i < 10; ++i) { input.append(i + 1); output.append(0); }
    for (Int part = 0; part < 3; ++part)
        assert(App::range_batch_(input, output, 3, part).at(0) == (part == 0 ? 4 : 3));
    for (Int i = 0; i < 10; ++i) assert(output.at(i) == i + 1);
    assert(App::range_batch_(input, output, 11, 10).at(0) == 0);
    scpp::vector_int64 shorter;
    shorter.append(0);
    try { App::range_batch_(input, shorter, 3, 0); assert(false); } catch (const std::out_of_range&) {}
    assert(shorter.at(0) == 0);
}

static void check_pool_cap() {
    using namespace scpp::mt;
    scpp::vector_int64 cells;
    for (Int i = 0; i < 12; ++i) cells.append(i);
    segment shared[]{job::borrow_read(cells, 0, 12)};
    std::array<std::thread::id, 12> ids;
    std::array<std::thread::id*, 12> destinations;
    std::vector<job> jobs;
    for (size_t i = 0; i < ids.size(); ++i) {
        destinations[i] = &ids[i];
        jobs.emplace_back(shared, destinations[i], [](const segment*, const void* context) -> Int {
            // Each probe owns one result slot, just as each language job does.
            **static_cast<std::thread::id* const*>(context) = std::this_thread::get_id();
            return 0;
        });
    }
    for (int cap : {1, 2, 4}) {
        join(jobs.data(), jobs.size(), cap);
        std::set<std::thread::id> participants(ids.begin(), ids.end());
        assert(participants.contains(std::this_thread::get_id()));
        assert(participants.size() == std::min<unsigned>(cap, std::max(2u, std::thread::hardware_concurrency())));
    }
}

// The generated batch calls this observer from every scan and both ordinary siblings.
static bool observe_caps;
static std::atomic<int> active_scans{}, active_siblings{};
static std::array<std::thread::id, 14> ran_on;
static std::barrier rendezvous(5, []() noexcept {
    assert(active_scans == 3 && active_siblings == 2);
});

Int App::note_entry_(Int id) {
    assert(id >= 0 && id < 14);
    ran_on[id] = std::this_thread::get_id();
    if (!observe_caps) return id;
    if (id < 12) {
        const auto concurrent = ++active_scans;
        assert(concurrent <= 3);
        if (id % 4 == 0) rendezvous.arrive_and_wait();
        --active_scans;
    } else {
        ++active_siblings;
        rendezvous.arrive_and_wait();
        --active_siblings;
    }
    return id;
}

static void check_job_caps() {
    scpp::vector_int64 ids;
    for (Int i = 0; i < 14; ++i) ids.append(i);
    Int evaluations = 0;
    observe_caps = std::thread::hardware_concurrency() >= 5;
    auto results = App::capped_batch_(ids, 12, 3, evaluations);
    assert(evaluations == 1 && results.size() == 14);
    for (Int i = 0; i < 14; ++i) assert(results.at(i) == i);
    if (observe_caps) {
        assert(std::set(ran_on.begin(), ran_on.end()).size() == 5);
        for (int i = 0; i < 12; ++i) assert(ran_on[i] == ran_on[i - i % 4]);
        std::puts("group=5 scan_cap=3 simultaneous_scans=3 simultaneous_siblings=2 chunk_lengths=4,4,4");
    }
    observe_caps = false;
    auto empty = App::capped_batch_(ids, 0, 3, evaluations);
    assert(evaluations == 2 && empty.size() == 2 && empty.at(0) == 12 && empty.at(1) == 13);
    try { App::capped_batch_(ids, 0, 0, evaluations); assert(false); } catch (const std::invalid_argument&) {}
    assert(evaluations == 3);
    auto independent = App::independent_caps_(ids);
    assert(independent.size() == 14);
    for (int i = 0; i < 14; ++i) {
        assert(independent.at(i) == i);
        const int first = i < 4 ? 0 : (i < 9 ? 4 : 9);
        assert(ran_on[i] == ran_on[first]);
    }
}

static void check_each() {
    for (Int cap : {1, 2, 8}) {
        scpp::vector_int64 entered, output;
        for (int i = 0; i < 12; ++i) { entered.append(0); output.append(0); }
        assert(App::each_batch_(entered, output, 0, cap, 12).size() == 0);
        // First eleven output grants are disjoint. Only the final partition wraps to slot zero.
        try { App::each_batch_(entered, output, 12, cap, 11); assert(false); }
        catch (const std::invalid_argument& error) { assert(std::string_view(error.what()) == "Overlapping exclusive segments"); }
        for (int i = 0; i < 12; ++i) assert(entered.at(i) == 0 && output.at(i) == 0);
        auto results = App::each_batch_(entered, output, 12, cap, 12);
        assert(results.size() == 12);
        for (int i = 0; i < 12; ++i) assert(results.at(i) == i && entered.at(i) == 1 && output.at(i) == i + 10);
        try { App::each_batch_(entered, output, 0, 0, 12); assert(false); } catch (const std::invalid_argument&) {}
        try { App::each_batch_(entered, output, -1, cap, 12); assert(false); } catch (const std::invalid_argument&) {}
    }
}

static void check_hash_segments() {
    scpp::hash_int64_int64 table;
    scpp::vector_int64 entered;
    entered.append(0);
    entered.append(0);
    for (Int key = 1; key <= 5; ++key) table.set(key, key);
    assert(table.remove(Int{2}));
    table.set(Int{3}, Int{30});
    auto deleted = App::read_pair_(table, 0, 4);
    assert(deleted.at(0) == 1345 && deleted.at(1) == 4);
    table.set(Int{2}, Int{20});
    auto reinserted = App::read_pair_(table, 0, 5);
    assert(reinserted.at(0) == 13452 && reinserted.at(1) == 5);
    auto empty = App::read_pair_(table, 5, 0);
    assert(empty.at(0) == 0 && empty.at(1) == 0);
    assert(App::record_hash_() == 8);
    try {
        App::hash_batch_(table, table, entered, 2);
        assert(false);
    } catch (const std::invalid_argument& error) {
        assert(std::string_view(error.what()) == "Overlapping read and write segments");
    }
    assert(entered.at(0) == 0 && entered.at(1) == 0);
    assert(table.at(Int{3}) == 30 && table.at(Int{4}) == 4);
    auto results = App::hash_batch_(table, table, entered, 4);
    assert(results.at(0) == 34 && results.at(1) == 2);
    assert(entered.at(0) == 1 && entered.at(1) == 1);
    assert(table.at(Int{3}) == 40 && table.at(Int{4}) == 14);
    assert(table.at(Int{1}) == 1 && table.at(Int{5}) == 5 && table.at(Int{2}) == 20);
}

int main() {
    check_hash_segments();
    check_job_caps();
    check_pool_cap();
    check_each();
    check_ranges();
    caller = std::this_thread::get_id();
    Rows rows;
    scpp::vector_int64 deltas;
    for (int i = 0; i < 3; ++i) { rows.append(Point{}); deltas.append(i + 2); }
    for (int i = 0; i < 2; ++i) {
        points[i] = static_cast<Point*>(scpp::mt::job::borrow(rows, i, 1).at(0));
        adjustments[i] = static_cast<Int*>(scpp::mt::job::borrow(deltas, i, 1).at(0));
    }
    auto& alias = deltas;
    try { App::dispatch_(rows, deltas, alias, 0); return 4; }
    catch (const std::invalid_argument&) {}
    assert(points[0]->x == 1 && points[1]->x == 1 && *adjustments[0] == 2 && *adjustments[1] == 3);
    using namespace scpp::mt;
    // Only the final grant of the three-input worker aliases the one-input worker.
    scpp::vector_int64 cells;
    for (int i = 0; i < 4; ++i) cells.append(i);
    try { App::mixed_batch_(cells, cells, 0); return 5; }
    catch (const std::invalid_argument&) {}
    for (int i = 0; i < 4; ++i) assert(cells.at(i) == i);
    // Both write bindings of the inline job reach cell zero through different names.
    try { App::pair_batch_(cells, cells, 0); return 7; }
    catch (const std::invalid_argument&) {}
    for (int i = 0; i < 4; ++i) assert(cells.at(i) == i);
    // A read/write overlap within the second job rejects the entire compiled batch.
    try { App::read_batch_(rows, cells, 1); return 8; }
    catch (const std::invalid_argument&) {}
    for (int i = 0; i < 4; ++i) assert(cells.at(i) == i);
    segment one[]{job::borrow(cells, 0, 1)};
    segment three[]{job::borrow(cells, 1, 1), job::borrow(cells, 2, 1), job::borrow(cells, 0, 1)};
    auto counted = [](const segment*) -> Int { ++entries; return 0; };
    segment readers[]{job::borrow_read(cells, 0, 1)};
    assert(readers[0].read_at(0) == one[0].at(0));
    job writer_first[]{{one, counted}, {readers, counted}};
    job reader_first[]{{readers, counted}, {one, counted}};
    for (auto* batch : {writer_first, reader_first}) {
        try { join(batch, 2); return 9; } catch (const std::invalid_argument&) {}
        assert(entries == 0);
    }
    job conflicting[]{{one, counted}, {three, counted}};
    try { join(conflicting, 2); return 6; } catch (const std::invalid_argument&) {}
    assert(entries == 0);

    segment first[]{job::borrow(rows, 0, 1), job::borrow(deltas, 0, 1)};
    segment second[]{job::borrow(rows, 1, 1), job::borrow(alias, 0, 1)};
    job jobs[]{{first, observed}, {second, observed}};
    try { join(jobs, 2); return 1; } catch (const std::invalid_argument&) {}
    assert(entries == 0 && points[0]->x == 1 && points[1]->x == 1);

    second[1] = job::borrow(alias, 1, std::numeric_limits<Int>::max());
    try { join(jobs, 2); return 2; } catch (const std::out_of_range&) {}
    assert(entries == 0);
    second[1] = job::borrow(alias, 1, 1);
    join(jobs, 2);
    assert(exits == 2 && jobs[0].result == 3 && jobs[1].result == 4);
    assert(rows.at(0).x == 3 && rows.at(1).x == 4 && rows.at(2).x == 1);
    entries = exits = 0;
    assert(calls[0] == 1 && calls[1] == 1);
    failing_offset = 1;
    try { join(jobs, 2); return 3; } catch (const std::out_of_range&) {}
    assert(exits == 2 && rows.at(0).x == 6 && rows.at(1).x == 8);
    assert(calls[0] == 2 && calls[1] == 2);
    entries = exits = 0;
    failing_offset = -1;
    join(jobs, 2);
    assert(exits == 2 && calls[0] == 3 && calls[1] == 3);
    auto results = App::dispatch_(rows, deltas, alias, 1);
    assert(results.at(0) == 15 && results.at(1) == 19 && rows.at(2).x == 1);
    // Empty half-open ranges can coincide at the end of the same vector.
    segment empty_inputs[]{job::borrow(rows, rows.size(), 0), job::borrow(alias, alias.size(), 0)};
    job empty{empty_inputs, [](const segment* inputs) { return App::work_(inputs[0], inputs[1]); }};
    job empties[]{empty, empty};
    join(empties, 2);
    assert(empties[0].result == 0 && empties[1].result == 0);
    rows.clear();

    // Two independent compiled callers repeatedly lease and return capacity.
    std::latch ready(2);
    auto independent = [&](Int seed) {
        Rows local_rows;
        scpp::vector_int64 local_deltas;
        for (int i = 0; i < 2; ++i) {
            local_rows.append(Point{});
            local_deltas.append(seed + i);
        }
        ready.arrive_and_wait();
        Int first = 1, second = 1;
        for (int pass = 0; pass < 64; ++pass) {
            auto out = App::dispatch_(local_rows, local_deltas, local_deltas, 1);
            first += seed + pass;
            second += seed + pass + 1;
            assert(out.at(0) == first && out.at(1) == second);
        }
    };
    std::thread other(independent, 5);
    independent(11);
    other.join();
}
