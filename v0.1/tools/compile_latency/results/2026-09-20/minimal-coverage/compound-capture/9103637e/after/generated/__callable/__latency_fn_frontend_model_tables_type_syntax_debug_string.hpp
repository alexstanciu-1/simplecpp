#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModelKernelCounters;
struct FrontendTypeSyntaxPayloadRow;
string_t __latency_fn_frontend_model_tables_type_syntax_debug_string(FrontendTypeSyntaxPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
