#include "child.hpp"
#include "tasks/void.hpp"
#include "tasks/result_int64.hpp"

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

namespace {
scpp::async::string_task relay_text(scpp::string value, const char*& payload, bool fail) {
    co_return co_await produce_text(static_cast<scpp::string&&>(value), payload, fail);
}
}

bool verify_owned_text() {
    const char bytes[] = "abcdefghijklmnopqrstuvwxyz\0XYZ";
    constexpr bool paths[] = {false, true};
    constexpr int sizes[] = {0, 4, 30};
    for (bool nested : paths) {
        for (int size : sizes) {
            const char* allocation = nullptr;
            scpp::string source(bytes, size);
            auto result = scpp::async::sync_wait(nested
                ? relay_text(source, allocation, false) : produce_text(source, allocation, false));
            if (result.size() != size || source.size() != size) return false;
            if (!result.is_inline() && (allocation != result.data() || allocation == source.data())) return false;
            for (int i = 0; i < size; ++i) if (result.data()[i] != bytes[i]) return false;
        }
        const char* allocation = nullptr;
        try {
            scpp::string source(bytes, 30);
            scpp::async::sync_wait(nested ? relay_text(source, allocation, true) : produce_text(source, allocation, true));
            return false;
        } catch (const child_error& e) {
            if (e.code != 4294967297) return false;
        }
    }
    return true;
}

namespace {
scpp::async::result_int64_task error_result(bool fail) {
    if (fail) throw child_error{23};
    co_return scpp::result_int64{scpp::error_t{scpp::string{"returned error"}, scpp::int64{23}}};
}

scpp::async::result_int64_task relay_error(bool fail) {
    co_return co_await error_result(fail);
}
}

bool verify_error_result() {
    auto value = scpp::async::sync_wait(relay_error(false));
    if (value.has_value() || !value.has_error() || value.error().get_line() != 23) return false;
    try {
        scpp::async::sync_wait(relay_error(true));
        return false;
    } catch (const child_error& e) {
        return e.code == 23;
    }
}
