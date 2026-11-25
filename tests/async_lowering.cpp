// Native proof of the proposed async lowering. See async_lowering.md.
#include "scpp/async_core.hpp"

#include <cassert>
#include <chrono>
#include <cstdint>
#include <iostream>
#include <stdexcept>
#include <string>

namespace {
using scpp::async_core::task;
using namespace std::chrono_literals;

struct trace {
    std::string output;
    std::string events;
    int label_calls{};
    int task_calls{};
    int skipped_calls{};
    int parent_drops{};
    int child_drops{};
};

struct cleanup {
    int& drops;
    ~cleanup() { ++drops; }
};

const char* label(trace& state) {
    ++state.label_calls;
    state.events += 'L';
    return "value=";
}

task<std::int64_t> read_value_task(trace& state, bool fail) {
    cleanup child{state.child_drops};
    state.events += 'B';
    co_await scpp::async_core::sleep_for(1ms);
    assert(state.child_drops == 0);
    state.events += 'R';
    if (fail) throw std::runtime_error("read failed");
    co_return 42;
}

task<std::int64_t> read_value(trace& state, bool fail) {
    ++state.task_calls;
    state.events += 'C';
    assert(state.output == "value=");
    return read_value_task(state, fail);
}

task<bool> skipped_call(trace& state) {
    ++state.skipped_calls;
    return scpp::async_core::ready_task(true);
}

// Proposed lowering for ordered echo and two lazy boolean expressions.
task<void> report_async(trace& state, bool fail) {
    cleanup parent{state.parent_drops};
    state.output += label(state);
    const auto value = co_await read_value(state, fail);
    state.output += std::to_string(value);
    state.output += '\n';

    bool both = false;
    if (both) both = co_await skipped_call(state);
    bool either = true;
    if (!either) either = co_await skipped_call(state);
    assert(!both && either);
}

// Concrete entry callable from a synchronous translation unit.
void report(trace& state, bool fail) {
    scpp::async_core::sync_wait(report_async(state, fail));
}
}

int main() {
    trace success;
    report(success, false);
    assert(success.output == "value=42\n" && success.events == "LCBR");
    assert(success.label_calls == 1 && success.task_calls == 1 && success.skipped_calls == 0);
    assert(success.parent_drops == 1 && success.child_drops == 1);

    trace failure;
    bool caught = false;
    try {
        report(failure, true);
    } catch (const std::runtime_error& error) {
        caught = std::string{error.what()} == "read failed";
    }
    assert(caught);
    assert(failure.output == "value=" && failure.events == "LCBR");
    assert(failure.label_calls == 1 && failure.task_calls == 1 && failure.skipped_calls == 0);
    assert(failure.parent_drops == 1 && failure.child_drops == 1);
    std::cout << "Ordered echo, short-circuiting and failure cleanup passed\n";
}
