#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentFrontendNodeListSnapshotRow;
ResidentFrontendNodeListSnapshotRow __latency_fn_resident_frontend_node_lists_snapshot_by_owner_and_source(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId);
}
