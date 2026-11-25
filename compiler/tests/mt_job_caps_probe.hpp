#pragma once
#include <scpp/vectors.hpp>
#include <array>
#include <atomic>
#include <barrier>
#include <cassert>
#include <cstdio>
#include <set>
#include <stdexcept>
#include <thread>

using Int = scpp::int64;
namespace App {
Int note_entry_(Int);
scpp::vector_int64 independent_caps_(scpp::vector_int64&);
scpp::vector_int64 capped_batch_(scpp::vector_int64&, Int, Int, Int&);
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
