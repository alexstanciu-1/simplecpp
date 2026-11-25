#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitFrontendPayloadTableRow;
class SourceUnitTable;
void __latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_descriptor_install_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& workerPayloadTables, const vector_t<int_t<>>& descriptors, int_t<std::uint32_t> ownerRunId, bool_t handoffMatches);
}
