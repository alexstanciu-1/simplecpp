#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendControlFlowOperandRow;
class BackendRequestAuthorizationArtifact;
BackendControlFlowOperandRow __latency_fn_backend_preflight_requests_control_flow_operand_by_owner_row_id(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerRowId);
}
