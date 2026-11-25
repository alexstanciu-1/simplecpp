#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitSnapshotRow;
void __latency_fn_resident_definition_granularity_append_deleted_source_change(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitSnapshotRow previous, int_t<std::uint32_t> ownerRunId);
}
