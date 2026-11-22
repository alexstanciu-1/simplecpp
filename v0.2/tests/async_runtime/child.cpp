#include "child.hpp"

scpp::async::int64_task read_value(trace& log, bool fail) {
    struct cleanup {
        int& drops;
        ~cleanup() { ++drops; }
    } guard{log.child_drops};

    log.order = 10 * log.order + 2;
    co_await scpp::async::delay_ms(1);
    log.order = 10 * log.order + 3;
    if (fail) throw child_error{4294967297};
    co_return 4294967297;
}
