#pragma once
namespace scpp::detail {
struct cli_context {
    int count;
    const char* const* values;
};
extern cli_context invocation;
}
