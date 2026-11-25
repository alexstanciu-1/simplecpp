#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentFrontendNodeListSnapshotRow;
ResidentFrontendNodeListSnapshotRow __latency_fn_resident_function_body_ownership_frontend_snapshot_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> report, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId);
}
