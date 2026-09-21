#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct ResidentSourceUnitFrontendPayloadTableRow;
struct SourceUnitTableRow;
class TokenStream;
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_row_from_built(SourceUnitTableRow sourceUnit, shared_p<TokenStream> tokens, shared_p<FrontendModel> model, int_t<std::uint32_t> payloadTableId, int_t<std::uint32_t> ownerRunId);
}
