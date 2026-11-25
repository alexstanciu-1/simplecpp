#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModelKernelCounters;
string_t __latency_fn_frontend_model_tables_declaration_debug_string(FrontendDeclarationPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
