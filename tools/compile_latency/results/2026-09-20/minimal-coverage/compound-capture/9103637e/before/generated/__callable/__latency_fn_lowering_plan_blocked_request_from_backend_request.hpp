#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendRequestAuthorizationRow;
struct LoweringBlockedRequestRow;
LoweringBlockedRequestRow __latency_fn_lowering_plan_blocked_request_from_backend_request(int_t<std::uint32_t> blockedRequestId, BackendRequestAuthorizationRow request);
}
