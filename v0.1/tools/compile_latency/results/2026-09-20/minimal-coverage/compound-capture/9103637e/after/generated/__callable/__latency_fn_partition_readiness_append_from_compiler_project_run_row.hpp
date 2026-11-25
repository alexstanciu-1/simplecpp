#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct CompilerProjectRunRow;
void __latency_fn_partition_readiness_append_from_compiler_project_run_row(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow projectRunRow);
}
