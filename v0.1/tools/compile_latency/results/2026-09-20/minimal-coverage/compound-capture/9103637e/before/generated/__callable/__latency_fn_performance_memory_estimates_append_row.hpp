#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct PerformanceMemoryEstimateRow;
void __latency_fn_performance_memory_estimates_append_row(shared_p<CompilerProjectRunReport>& report, PerformanceMemoryEstimateRow row);
}
