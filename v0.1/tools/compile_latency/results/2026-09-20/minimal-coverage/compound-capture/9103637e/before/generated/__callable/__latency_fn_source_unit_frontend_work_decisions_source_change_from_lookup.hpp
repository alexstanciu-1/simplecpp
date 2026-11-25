#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitChangeRow;
ResidentSourceUnitChangeRow __latency_fn_source_unit_frontend_work_decisions_source_change_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> changeReport, vector_t<int_t<std::uint32_t>>& changeIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId);
}
