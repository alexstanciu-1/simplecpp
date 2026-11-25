#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSymbolDefinitionSnapshotRow;
void __latency_fn_resident_definition_granularity_append_symbol_definition_change(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionSnapshotRow current, ResidentSymbolDefinitionSnapshotRow previous, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> dirtyScopeId, int_t<std::uint16_t> reuseScopeId);
}
