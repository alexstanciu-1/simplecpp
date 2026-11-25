#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendExpressionPayloadRow;
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_expression_payload(shared_p<FrontendModel> model, FrontendExpressionPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
