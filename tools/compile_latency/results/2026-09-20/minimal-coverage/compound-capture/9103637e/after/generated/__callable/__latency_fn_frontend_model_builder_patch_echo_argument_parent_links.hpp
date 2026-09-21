#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
void __latency_fn_frontend_model_builder_patch_echo_argument_parent_links(shared_p<FrontendModel> model, int_t<std::uint32_t> echoNodeId, int_t<std::uint32_t> firstArgumentNodeId, shared_p<FrontendModelKernelCounters> counters);
}
