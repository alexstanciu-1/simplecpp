#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitSnapshotRow;
ResidentSourceUnitSnapshotRow __latency_fn_partition_readiness_first_source_snapshot_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId);
}
