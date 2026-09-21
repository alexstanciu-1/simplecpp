#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionValueRow;
struct BackendRequestAuthorizationRow;
BackendEmissionValueRow __latency_fn_backend_emission_decisions_value_from_backend_request(int_t<std::uint32_t> valueId, int_t<std::uint32_t> decisionId, int_t<std::uint32_t> loweringStepId, BackendRequestAuthorizationRow request);
}
