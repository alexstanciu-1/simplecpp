#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitSnapshotRow;
void __latency_fn_resident_definition_granularity_append_source_change(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> dirtyStatusId, int_t<std::uint16_t> reuseStatusId);
}
