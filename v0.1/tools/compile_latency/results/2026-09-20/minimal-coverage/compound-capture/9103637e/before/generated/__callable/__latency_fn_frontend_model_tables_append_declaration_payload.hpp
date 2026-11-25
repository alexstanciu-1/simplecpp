#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendDeclarationPayloadRow;
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_declaration_payload(shared_p<FrontendModel> model, FrontendDeclarationPayloadRow row, shared_p<FrontendModelKernelCounters> counters);
}
