#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class SourceUnitTable;
void __latency_fn_resident_source_unit_symbol_states_record_symbol_fact_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, const shared_p<SourceUnitTable>& sourceUnits, int_t<std::uint32_t> ownerRunId, const vector_t<int_t<std::uint32_t>>& firstSymbolIds, const vector_t<int_t<std::uint32_t>>& symbolCounts, int_t<> workerCount, bool_t frontendPayloadSourceWorkerReady, bool_t& symbolFactWorkerCandidateReady);
}
