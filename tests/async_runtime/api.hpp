#pragma once
#include <scpp/core/scalars.hpp>

struct trace {
    int order{};
    int parent_drops{};
    int child_drops{};
};

struct child_error { scpp::int64 code; };
scpp::int64 report(trace& log, bool fail);
void notify(trace& log, bool fail);
bool verify_owned_text();
bool verify_error_result();
