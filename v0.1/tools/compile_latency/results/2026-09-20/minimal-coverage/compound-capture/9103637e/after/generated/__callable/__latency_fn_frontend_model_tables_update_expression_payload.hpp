#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendExpressionPayloadRow;
class FrontendModel;
class FrontendModelKernelCounters;
bool_t __latency_fn_frontend_model_tables_update_expression_payload(shared_p<FrontendModel> model, FrontendExpressionPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
