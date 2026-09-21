#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectSymbolIndexRow;
struct ResidentSymbolDefinitionSnapshotRow;
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_copy_reused_symbol_snapshot(ResidentSymbolDefinitionSnapshotRow previous, ProjectSymbolIndexRow currentSymbol, int_t<std::uint32_t> ownerRunId);
}
