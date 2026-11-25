#include "child_private.hpp"

#include <stdexcept>

async_proof::int_task read_value(AsyncTrace& trace, bool fail) {
    struct Cleanup {
        AsyncTrace& trace;
        ~Cleanup() { ++trace.child_drops; }
    } cleanup{trace};

    trace.events = trace.events * 10 + 2;
    co_await async_proof::delay_ms(1);
    trace.events = trace.events * 10 + 3;
    if (fail) throw std::runtime_error("child failed");
    co_return 42;
}
