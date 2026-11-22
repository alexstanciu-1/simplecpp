#include "child.hpp"
#include <scpp/async_void.hpp>

namespace {
scpp::async::int64_task report_async(trace& log, bool fail) {
    struct cleanup {
        int& drops;
        ~cleanup() { ++drops; }
    } guard{log.parent_drops};

    log.order = 10 * log.order + 1;
    const scpp::int64 value = co_await read_value(log, fail);
    log.order = 10 * log.order + 4;
    co_return value + 1;
}
}

scpp::int64 report(trace& log, bool fail) {
    return scpp::async::sync_wait(report_async(log, fail));
}

namespace {
scpp::async::void_task notify_async(trace& log, bool fail) {
    static_cast<void>(co_await report_async(log, fail));
    co_return;
}
}

void notify(trace& log, bool fail) {
    scpp::async::sync_wait(notify_async(log, fail));
}
