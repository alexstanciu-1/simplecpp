#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
struct ResidentSymbolDefinitionSnapshotRow;
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_symbol_snapshot_from_row(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow symbol, int_t<std::uint32_t> ownerRunId);
}
