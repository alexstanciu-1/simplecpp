#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentSourceUnitEarlySkipDecisionRow;
struct ResidentSourceUnitSnapshotRow;
ResidentSourceUnitEarlySkipDecisionRow __latency_fn_source_unit_early_skip_row_from_snapshots(int_t<std::uint32_t> decisionId, ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous, int_t<std::uint32_t> fallbackOwnerRunId);
}
