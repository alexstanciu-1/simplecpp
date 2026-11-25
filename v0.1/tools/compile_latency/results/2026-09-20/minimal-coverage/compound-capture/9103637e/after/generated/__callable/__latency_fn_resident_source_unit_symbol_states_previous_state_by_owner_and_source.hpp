#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitSymbolStateRow;
ResidentSourceUnitSymbolStateRow __latency_fn_resident_source_unit_symbol_states_previous_state_by_owner_and_source(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId);
}
