#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitEarlySkipArtifact;
struct ResidentSourceUnitSnapshotRow;
ResidentSourceUnitSnapshotRow __latency_fn_source_unit_early_skip_source_snapshot_from_lookup(ResidentSourceUnitEarlySkipArtifact& artifact, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId);
}
