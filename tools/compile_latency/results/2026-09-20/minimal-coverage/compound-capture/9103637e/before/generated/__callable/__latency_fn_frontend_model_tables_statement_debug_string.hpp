#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModelKernelCounters;
struct FrontendStatementPayloadRow;
string_t __latency_fn_frontend_model_tables_statement_debug_string(FrontendStatementPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
