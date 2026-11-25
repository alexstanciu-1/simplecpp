#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendExpressionPayloadRow;
class FrontendModel;
class FrontendModelKernelCounters;
FrontendExpressionPayloadRow __latency_fn_frontend_model_tables_expression_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId, shared_p<FrontendModelKernelCounters> counters);
}
