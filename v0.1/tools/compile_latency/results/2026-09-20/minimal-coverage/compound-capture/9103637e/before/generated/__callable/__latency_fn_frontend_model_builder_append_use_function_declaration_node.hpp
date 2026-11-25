#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct TokenRow;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_use_function_declaration_node(shared_p<FrontendModel> model, TokenRow useToken, int_t<std::uint32_t> targetRangeId, int_t<std::uint32_t> targetPayloadId, TokenRow lastToken, int_t<std::uint32_t> namespaceNameId, int_t<std::uint32_t> namespaceSourceRangeId, shared_p<FrontendModelKernelCounters> counters);
}
