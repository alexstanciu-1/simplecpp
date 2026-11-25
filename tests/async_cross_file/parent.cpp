#include "child_private.hpp"

namespace {
async_proof::int_task report_async(AsyncTrace& trace, bool fail) {
    struct Cleanup {
        AsyncTrace& trace;
        ~Cleanup() { ++trace.parent_drops; }
    } cleanup{trace};

    trace.events = trace.events * 10 + 1;
    const int value = co_await read_value(trace, fail);
    trace.events = trace.events * 10 + 4;
    co_return value + 1;
}
}

int report(AsyncTrace& trace, bool fail) {
    return async_proof::sync_wait(report_async(trace, fail));
}
