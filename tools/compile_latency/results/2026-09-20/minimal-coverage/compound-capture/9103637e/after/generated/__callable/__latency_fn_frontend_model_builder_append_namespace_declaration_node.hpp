#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct TokenRow;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_namespace_declaration_node(shared_p<FrontendModel> model, TokenRow namespaceToken, int_t<std::uint32_t> nameRangeId, int_t<std::uint32_t> namePayloadId, TokenRow lastToken, shared_p<FrontendModelKernelCounters> counters);
}
