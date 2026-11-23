// With no arguments this checks nested wait restoration. With a count it times immediate awaits.
#include <scpp/async_int64.hpp>
#include <chrono>
#include <cstdio>
#include <cstdlib>

using scpp::async::int64_task;
using scpp::async::sync_wait;

namespace {
struct guard {
    int& drops;
    ~guard() { ++drops; }
};
struct failure {};

int64_task leaf(scpp::int64 value) {
    co_return value;
}

int64_task inner(bool fail, int& drops) {
    guard cleanup{drops};
    auto value = co_await leaf(4294967297);
    if (fail) throw failure{};
    co_return value;
}

int64_task outer(bool fail, int& drops) {
    guard cleanup{drops};
    scpp::int64 value = 0;
    try {
        value = sync_wait(inner(fail, drops));
        if (fail) co_return -1;
    } catch (const failure&) {
        if (!fail) throw;
        value = 4294967297;
    }
    // A further await must resume on the outer loop after either inner exit.
    co_return co_await leaf(value + 1);
}

int64_task repeat(int count) {
    scpp::int64 sum = 0;
    for (int i = 0; i < count; ++i) sum += co_await leaf(i);
    co_return sum;
}
}

int main(int argc, char** argv) {
    for (bool fail : {false, true}) {
        int drops = 0;
        if (sync_wait(outer(fail, drops)) != 4294967298 || drops != 2) return 1;
    }
    if (argc == 2) {
        const int count = std::atoi(argv[1]);
        if (count < 1) return 2;
        const auto start = std::chrono::steady_clock::now();
        const auto sum = sync_wait(repeat(count));
        const auto elapsed = std::chrono::steady_clock::now() - start;
        if (sum != scpp::int64(count) * (count - 1) / 2) return 3;
        std::printf("%.3f\n", std::chrono::duration<double, std::nano>(elapsed).count() / count);
    }
}
