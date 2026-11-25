#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendRequestAuthorizationRow;
struct LoweringStep;
LoweringStep __latency_fn_lowering_plan_step_from_backend_request(int_t<std::uint32_t> stepId, BackendRequestAuthorizationRow request);
}
