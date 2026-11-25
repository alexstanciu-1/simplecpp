#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class SourceUnitFrontendWorkerFrontendPayloadCarrier;
class TokenStream;
shared_p<SourceUnitFrontendWorkerFrontendPayloadCarrier> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_payload_carrier_from_model(int_t<> sourceUnitId, shared_p<TokenStream> tokens, shared_p<FrontendModel> model);
}
