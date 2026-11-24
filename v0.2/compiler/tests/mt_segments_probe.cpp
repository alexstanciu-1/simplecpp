// The harness includes the generated vector header and binds Rows to that concrete type.
#include <scpp/mt_segments.hpp>
#include <scpp/vectors.hpp>
#include <atomic>
#include <cassert>
#include <limits>
#include <stdexcept>
#include <thread>
using Point = App::scpp_records::Point;
using Int = scpp::int64;
using scpp::mt::segment;
namespace App {
Int work_(segment, segment);
scpp::vector_int64 dispatch_(Rows&, scpp::vector_int64&, scpp::vector_int64&, Int);
}

static Point* points[2];
static Int* adjustments[2];
static std::atomic<int> entries{}, exits{};
static bool fail;
static Int observed(segment rows, segment deltas) {
    ++entries;
    while (entries.load() < 2) std::this_thread::yield();
    const auto offset = rows.offset;
    assert(rows.at(0) == points[offset] && deltas.at(0) == adjustments[offset]);
    auto result = App::work_(rows, deltas);
    ++exits;
    if (fail && offset == 0) rows.at(1); // Out of bounds after a completed in-place update.
    return result;
}

int main() {
    Rows rows;
    scpp::vector_int64 deltas;
    for (int i = 0; i < 3; ++i) { rows.append(Point{}); deltas.append(i + 2); }
    for (int i = 0; i < 2; ++i) {
        points[i] = reinterpret_cast<Point*>(rows.mt_data() + i * sizeof(Point));
        adjustments[i] = reinterpret_cast<Int*>(deltas.mt_data() + i * sizeof(Int));
    }
    auto& alias = deltas;
    try { App::dispatch_(rows, deltas, alias, 0); return 4; }
    catch (const std::invalid_argument&) {}
    assert(points[0]->x == 1 && points[1]->x == 1 && *adjustments[0] == 2 && *adjustments[1] == 3);
    using namespace scpp::mt;
    job jobs[]{
        {observed, {{rows.mt_data(), sizeof(Point), rows.size(), 0, 1}, {deltas.mt_data(), sizeof(Int), deltas.size(), 0, 1}}},
        {observed, {{rows.mt_data(), sizeof(Point), rows.size(), 1, 1}, {alias.mt_data(), sizeof(Int), alias.size(), 0, 1}}}
    };
    try { join(jobs, 2); return 1; } catch (const std::invalid_argument&) {}
    assert(entries == 0 && points[0]->x == 1 && points[1]->x == 1);

    jobs[1].inputs[1].offset = 1;
    jobs[1].inputs[1].length = std::numeric_limits<Int>::max();
    try { join(jobs, 2); return 2; } catch (const std::out_of_range&) {}
    assert(entries == 0);
    jobs[1].inputs[1].length = 1;
    join(jobs, 2);
    assert(exits == 2 && jobs[0].result == 3 && jobs[1].result == 4);
    assert(rows.at(0).x == 3 && rows.at(1).x == 4 && rows.at(2).x == 1);
    entries = exits = 0;
    fail = true;
    try { join(jobs, 2); return 3; } catch (const std::out_of_range&) {}
    assert(exits == 2 && rows.at(0).x == 6 && rows.at(1).x == 8);
    // Empty half-open ranges can coincide at the end of the same vector.
    job empty{App::work_, {{rows.mt_data(), sizeof(Point), rows.size(), rows.size(), 0},
                           {alias.mt_data(), sizeof(Int), alias.size(), alias.size(), 0}}};
    job empties[]{empty, empty};
    join(empties, 2);
    assert(empties[0].result == 0 && empties[1].result == 0);
    rows.clear();
}
