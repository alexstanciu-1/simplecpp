#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentSourceUnitSnapshotRow;
class SourceUnitTable;
struct SourceUnitTableRow;
ResidentSourceUnitSnapshotRow __latency_fn_resident_definition_granularity_source_snapshot_from_row(shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> ownerRunId);
}
