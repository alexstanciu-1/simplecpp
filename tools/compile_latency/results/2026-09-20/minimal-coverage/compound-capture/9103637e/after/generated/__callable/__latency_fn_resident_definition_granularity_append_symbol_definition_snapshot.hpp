#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSymbolDefinitionSnapshotRow;
void __latency_fn_resident_definition_granularity_append_symbol_definition_snapshot(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionSnapshotRow row);
}
