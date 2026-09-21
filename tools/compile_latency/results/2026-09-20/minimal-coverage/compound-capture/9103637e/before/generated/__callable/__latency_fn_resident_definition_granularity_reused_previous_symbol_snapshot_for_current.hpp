#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ProjectSymbolIndexRow;
struct ResidentSourceUnitSymbolStateRow;
struct ResidentSymbolDefinitionSnapshotRow;
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_reused_previous_symbol_snapshot_for_current(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> previousReport, ProjectSymbolIndexRow symbol, ResidentSourceUnitSymbolStateRow currentState, vector_t<int_t<std::uint32_t>>& previousSnapshotIds, int_t<std::uint32_t> ownerRunId);
}
