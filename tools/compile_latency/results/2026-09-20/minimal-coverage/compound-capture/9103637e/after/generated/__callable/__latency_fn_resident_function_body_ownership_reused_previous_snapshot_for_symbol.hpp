#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ProjectSymbolIndexRow;
struct ResidentFrontendNodeListSnapshotRow;
struct ResidentFunctionBodySnapshotRow;
struct ResidentSourceUnitFrontendStateRow;
struct ResidentSourceUnitSymbolStateRow;
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_ownership_reused_previous_snapshot_for_symbol(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> previousReport, ProjectSymbolIndexRow symbol, ResidentSourceUnitSymbolStateRow currentState, ResidentSourceUnitFrontendStateRow frontendState, ResidentFrontendNodeListSnapshotRow frontendSnapshot, vector_t<int_t<std::uint32_t>>& previousSnapshotIds, int_t<std::uint32_t> ownerRunId);
}
