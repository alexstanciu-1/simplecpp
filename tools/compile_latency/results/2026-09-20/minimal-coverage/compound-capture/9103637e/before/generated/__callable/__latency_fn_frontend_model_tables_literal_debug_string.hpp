#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendLiteralPayloadRow;
class FrontendModelKernelCounters;
string_t __latency_fn_frontend_model_tables_literal_debug_string(FrontendLiteralPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
