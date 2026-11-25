#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct CompilerProjectRunRow;
void __latency_fn_compiler_project_runner_append_row(shared_p<CompilerProjectRunReport>& report, const string_t& runLabel, CompilerProjectRunRow row);
}
