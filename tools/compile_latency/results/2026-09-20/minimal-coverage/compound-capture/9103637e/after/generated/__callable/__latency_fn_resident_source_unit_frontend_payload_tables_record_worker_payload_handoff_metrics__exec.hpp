#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitFrontendPayloadTableRow;
class SourceUnitTable;
void __latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_handoff_metrics__exec(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables, int_t<> workerCount, bool_t& frontendPayloadSourceWorkerReady);
}
