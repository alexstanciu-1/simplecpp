#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendExpressionPayloadRow;
class FrontendModelKernelCounters;
string_t __latency_fn_frontend_model_tables_expression_debug_string(FrontendExpressionPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
