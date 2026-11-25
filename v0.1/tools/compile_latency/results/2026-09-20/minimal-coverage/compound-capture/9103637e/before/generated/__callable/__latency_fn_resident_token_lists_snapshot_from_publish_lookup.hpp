#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentTokenListSnapshotRow;
ResidentTokenListSnapshotRow __latency_fn_resident_token_lists_snapshot_from_publish_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId);
}
