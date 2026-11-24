// The harness includes the generated vector header and binds Rows to that concrete type.
#include <scpp/mt_segments.hpp>
#include <scpp/vectors.hpp>
#include <atomic>
#include <cassert>
#include <limits>
#include <latch>
#include <stdexcept>
#include <thread>
using Point = App::scpp_records::Point;
using Int = scpp::int64;
using scpp::mt::segment;
namespace App {
Int work_(segment, segment);
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
static Int observed(segment rows, segment deltas) {
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

int main() {
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
    job jobs[]{
        {observed, {job::borrow(rows, 0, 1), job::borrow(deltas, 0, 1)}},
        {observed, {job::borrow(rows, 1, 1), job::borrow(alias, 0, 1)}}
    };
    try { join(jobs, 2); return 1; } catch (const std::invalid_argument&) {}
    assert(entries == 0 && points[0]->x == 1 && points[1]->x == 1);

    jobs[1].inputs[1] = job::borrow(alias, 1, std::numeric_limits<Int>::max());
    try { join(jobs, 2); return 2; } catch (const std::out_of_range&) {}
    assert(entries == 0);
    jobs[1].inputs[1] = job::borrow(alias, 1, 1);
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
    job empty{App::work_, {job::borrow(rows, rows.size(), 0),
                           job::borrow(alias, alias.size(), 0)}};
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
