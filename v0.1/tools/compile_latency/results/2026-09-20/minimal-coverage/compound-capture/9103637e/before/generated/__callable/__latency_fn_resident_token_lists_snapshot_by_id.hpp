#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentTokenListSnapshotRow;
ResidentTokenListSnapshotRow __latency_fn_resident_token_lists_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId);
}
