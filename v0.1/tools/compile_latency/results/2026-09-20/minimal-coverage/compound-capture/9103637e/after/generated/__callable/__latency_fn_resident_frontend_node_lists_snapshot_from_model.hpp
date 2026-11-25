#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct ResidentFrontendNodeListSnapshotRow;
ResidentFrontendNodeListSnapshotRow __latency_fn_resident_frontend_node_lists_snapshot_from_model(int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId, shared_p<FrontendModel> model);
}
