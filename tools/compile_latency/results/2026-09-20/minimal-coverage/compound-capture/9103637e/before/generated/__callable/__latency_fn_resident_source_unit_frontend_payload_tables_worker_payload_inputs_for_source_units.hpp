#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SourceUnitFrontendWorkerPayloadInput;
class SourceUnitTable;
vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_inputs_for_source_units(const shared_p<SourceUnitTable>& sourceUnits);
}
