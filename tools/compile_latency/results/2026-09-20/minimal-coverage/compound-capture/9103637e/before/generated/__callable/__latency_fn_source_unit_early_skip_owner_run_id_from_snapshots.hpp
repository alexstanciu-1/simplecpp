#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentSourceUnitSnapshotRow;
int_t<std::uint32_t> __latency_fn_source_unit_early_skip_owner_run_id_from_snapshots(ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous, int_t<std::uint32_t> fallbackOwnerRunId);
}
