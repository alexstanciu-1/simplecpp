#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
void __latency_fn_frontend_model_builder_attach_synthetic_script_entry_body(shared_p<FrontendModel> model, const string_t& source, int_t<std::uint32_t> declarationNodeId, int_t<std::uint32_t> declarationPayloadId, int_t<std::uint32_t> bodyNodeId, shared_p<FrontendModelKernelCounters> counters);
}
