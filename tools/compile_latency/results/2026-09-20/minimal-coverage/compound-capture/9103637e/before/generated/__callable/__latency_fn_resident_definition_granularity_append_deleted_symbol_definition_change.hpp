#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSymbolDefinitionSnapshotRow;
void __latency_fn_resident_definition_granularity_append_deleted_symbol_definition_change(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionSnapshotRow previous, int_t<std::uint32_t> ownerRunId);
}
