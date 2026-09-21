#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentSourceUnitFrontendPayloadTableRow;
struct SourceUnitFrontendWorkerPayloadInstallPreflightRow;
SourceUnitFrontendWorkerPayloadInstallPreflightRow __latency_fn_resident_source_unit_frontend_payload_tables_payload_install_preflight_row_from_descriptor_row(ResidentSourceUnitFrontendPayloadTableRow descriptorRow, int_t<> preflightRowId, int_t<> adoptionPublicationRowCount, int_t<> workerPayloadReadyRows, int_t<> coordinatorAdoptedSegments, int_t<> publishedPayloadSegments, int_t<> payloadCopyBytes);
}
