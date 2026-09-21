#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
bool_t __latency_fn_frontend_model_builder_node_is_synthetic_script_entry(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, shared_p<FrontendModelKernelCounters> counters);
}
