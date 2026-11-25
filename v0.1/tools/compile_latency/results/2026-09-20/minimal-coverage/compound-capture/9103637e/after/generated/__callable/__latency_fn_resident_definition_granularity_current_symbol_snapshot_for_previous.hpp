#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSymbolDefinitionSnapshotRow;
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_current_symbol_snapshot_for_previous(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> currentReport, vector_t<int_t<std::uint32_t>>& currentSnapshotIds, vector_t<int_t<std::uint32_t>>& currentIdentitySnapshotIds, int_t<std::uint32_t> ownerRunId, ResidentSymbolDefinitionSnapshotRow previous);
}
