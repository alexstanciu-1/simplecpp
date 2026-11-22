#include "child_private.hpp"

#include <chrono>
#include <stdexcept>

async_proof::int_task read_value(AsyncTrace& trace, bool fail) {
    struct Cleanup {
        AsyncTrace& trace;
        ~Cleanup() { ++trace.child_drops; }
    } cleanup{trace};

    trace.events = trace.events * 10 + 2;
    co_await scpp::async_core::sleep_for(std::chrono::milliseconds{1});
    trace.events = trace.events * 10 + 3;
    if (fail) throw std::runtime_error("child failed");
    co_return 42;
}
