#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class SourceUnitTable;
void __latency_fn_resident_source_unit_symbol_states_record_symbol_fact_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, const vector_t<int_t<>>& workerDescriptors, int_t<std::uint32_t> coordinatorSymbolRows, bool_t matches);
}
