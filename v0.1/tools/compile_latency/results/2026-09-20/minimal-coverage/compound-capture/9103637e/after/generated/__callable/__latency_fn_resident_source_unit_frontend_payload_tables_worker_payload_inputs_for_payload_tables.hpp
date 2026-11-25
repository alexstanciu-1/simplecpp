#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentSourceUnitFrontendPayloadTableRow;
class SourceUnitFrontendWorkerPayloadInput;
class SourceUnitTable;
vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_inputs_for_payload_tables(shared_p<SourceUnitTable> sourceUnits, const vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables);
}
