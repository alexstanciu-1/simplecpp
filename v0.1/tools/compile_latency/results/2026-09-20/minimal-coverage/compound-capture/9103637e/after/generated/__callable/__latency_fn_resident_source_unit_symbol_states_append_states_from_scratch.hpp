#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class SourceUnitTable;
void __latency_fn_resident_source_unit_symbol_states_append_states_from_scratch(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> projectSymbolIndexSidecarId, vector_t<int_t<std::uint32_t>>& kindIds, vector_t<int_t<std::uint32_t>>& firstSymbolIds, vector_t<int_t<std::uint32_t>>& symbolCounts, vector_t<int_t<std::uint32_t>>& previousStateIds);
}
