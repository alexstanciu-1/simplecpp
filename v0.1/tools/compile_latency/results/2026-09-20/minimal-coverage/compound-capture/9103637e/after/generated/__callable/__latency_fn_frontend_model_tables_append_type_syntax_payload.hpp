#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendTypeSyntaxPayloadRow;
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_type_syntax_payload(shared_p<FrontendModel> model, FrontendTypeSyntaxPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
