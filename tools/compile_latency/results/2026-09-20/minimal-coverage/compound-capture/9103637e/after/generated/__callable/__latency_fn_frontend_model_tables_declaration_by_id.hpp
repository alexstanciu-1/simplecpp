#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModel;
class FrontendModelKernelCounters;
FrontendDeclarationPayloadRow __latency_fn_frontend_model_tables_declaration_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId, shared_p<FrontendModelKernelCounters> counters);
}
