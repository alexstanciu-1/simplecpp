#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectFrontendSourceRow;
struct ResidentTokenListSnapshotRow;
ResidentTokenListSnapshotRow __latency_fn_resident_token_lists_snapshot_from_source_row(int_t<std::uint32_t> ownerRunId, ProjectFrontendSourceRow sourceRow);
}
