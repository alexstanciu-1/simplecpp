#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSymbolDefinitionSnapshotRow;
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_previous_symbol_snapshot_for_current(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> previousReport, vector_t<int_t<std::uint32_t>>& previousSnapshotIds, vector_t<int_t<std::uint32_t>>& previousIdentitySnapshotIds, int_t<std::uint32_t> ownerRunId, ResidentSymbolDefinitionSnapshotRow current);
}
