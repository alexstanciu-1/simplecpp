#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
void __latency_fn_frontend_model_builder_apply_node_segment_policy(shared_p<FrontendModel> model, int_t<std::uint32_t> segmentThreshold, int_t<std::uint32_t> segmentCapacity);
}
