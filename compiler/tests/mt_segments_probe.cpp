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
Int work_(segment, segment);
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

#include "mt_job_caps_probe.hpp"

#include "mt_each_probe.hpp"

#include "mt_hash_segments_probe.hpp"

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
