#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentProjectSnapshotRow;
ResidentProjectSnapshotRow __latency_fn_resident_snapshots_snapshot_by_owner_run_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId);
}
