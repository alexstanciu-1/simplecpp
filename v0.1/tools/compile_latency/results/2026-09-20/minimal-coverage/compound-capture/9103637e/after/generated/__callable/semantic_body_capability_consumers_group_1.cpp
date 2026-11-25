#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerPlan.hpp"
#include "__types/ScalarBodyBackendCollection.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_local_load_return.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_new_plan.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_append_scalar_body_common.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_local_return_plan.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_literal_scalar.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_new_plan.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_append_scalar_body_common.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_literal_return_plan.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_binary_operator.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_while_condition.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_local_return_plan.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_while_return_plan.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_binary_operator.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_for_condition.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_for_return_plan.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_local_return_plan.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_if_condition.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_literal_scalar.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_new_plan.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_append_scalar_body_common.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_scalar_if_literal_return_plan.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_body_capability_consumers[]; }
namespace scpp {
shared_p<CapabilityConsumerPlan> __latency_fn_semantic_body_capability_consumers_scalar_local_return_plan(shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> returnTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_body_capability_consumers::scalar_local_return_plan", "/tmp/scpp-edit-latency-20260919/app/compile/semantic/semantic_body_capability_consumers.phs", __latency_lines_semantic_body_capability_consumers[8]);
	shared_p<CapabilityConsumerPlan> plan = __latency_fn_capability_consumer_plans_new_plan();
	__latency_fn_semantic_body_capability_consumers_append_scalar_body_common(plan, body, assignmentSourceRowIds, assignmentTypeRefIds, cast<int_t<std::uint32_t>>(returnTypeRefId));
	__latency_fn_capability_consumer_plans_append_local_load_return(plan, returnSourceRowId, returnTypeRefId);
	return plan;
}

}

namespace scpp { extern const int __latency_lines_semantic_body_capability_consumers[]; }
namespace scpp {
shared_p<CapabilityConsumerPlan> __latency_fn_semantic_body_capability_consumers_scalar_literal_return_plan(shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> returnLiteralSourceRowId, int_t<std::uint32_t> returnTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_body_capability_consumers::scalar_literal_return_plan", "/tmp/scpp-edit-latency-20260919/app/compile/semantic/semantic_body_capability_consumers.phs", __latency_lines_semantic_body_capability_consumers[9]);
	shared_p<CapabilityConsumerPlan> plan = __latency_fn_capability_consumer_plans_new_plan();
	__latency_fn_capability_consumer_plans_add_registry(plan, __latency_fn_type_capability_readiness_capability_return_lowering_authorized_id());
	__latency_fn_semantic_body_capability_consumers_append_scalar_body_common(plan, body, assignmentSourceRowIds, assignmentTypeRefIds, cast<int_t<std::uint32_t>>(returnTypeRefId));
	__latency_fn_capability_consumer_plans_append_literal_scalar(plan, returnLiteralSourceRowId, returnTypeRefId);
	return plan;
}

}

namespace scpp { extern const int __latency_lines_semantic_body_capability_consumers[]; }
namespace scpp {
shared_p<CapabilityConsumerPlan> __latency_fn_semantic_body_capability_consumers_scalar_while_return_plan(shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> whileSourceRowId, int_t<std::uint32_t> whileConditionSourceRowId, int_t<std::uint16_t> whileConditionFeatureId, int_t<std::uint32_t> whileConditionProviderTypeRefId, int_t<std::uint32_t> whileConditionTypeRefId, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> returnTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_body_capability_consumers::scalar_while_return_plan", "/tmp/scpp-edit-latency-20260919/app/compile/semantic/semantic_body_capability_consumers.phs", __latency_lines_semantic_body_capability_consumers[10]);
	shared_p<CapabilityConsumerPlan> plan = __latency_fn_semantic_body_capability_consumers_scalar_local_return_plan(body, assignmentSourceRowIds, assignmentTypeRefIds, cast<int_t<std::uint32_t>>(returnSourceRowId), cast<int_t<std::uint32_t>>(returnTypeRefId));
	__latency_fn_capability_consumer_plans_append_binary_operator(plan, whileConditionSourceRowId, whileConditionProviderTypeRefId, whileConditionFeatureId);
	__latency_fn_capability_consumer_plans_append_while_condition(plan, whileSourceRowId, whileConditionTypeRefId);
	return plan;
}

}

namespace scpp { extern const int __latency_lines_semantic_body_capability_consumers[]; }
namespace scpp {
shared_p<CapabilityConsumerPlan> __latency_fn_semantic_body_capability_consumers_scalar_for_return_plan(shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> forSourceRowId, int_t<std::uint32_t> forConditionSourceRowId, int_t<std::uint16_t> forConditionFeatureId, int_t<std::uint32_t> forConditionProviderTypeRefId, int_t<std::uint32_t> forConditionTypeRefId, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> returnTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_body_capability_consumers::scalar_for_return_plan", "/tmp/scpp-edit-latency-20260919/app/compile/semantic/semantic_body_capability_consumers.phs", __latency_lines_semantic_body_capability_consumers[11]);
	shared_p<CapabilityConsumerPlan> plan = __latency_fn_semantic_body_capability_consumers_scalar_local_return_plan(body, assignmentSourceRowIds, assignmentTypeRefIds, cast<int_t<std::uint32_t>>(returnSourceRowId), cast<int_t<std::uint32_t>>(returnTypeRefId));
	__latency_fn_capability_consumer_plans_append_binary_operator(plan, forConditionSourceRowId, forConditionProviderTypeRefId, forConditionFeatureId);
	__latency_fn_capability_consumer_plans_append_for_condition(plan, forSourceRowId, forConditionTypeRefId);
	return plan;
}

}

namespace scpp { extern const int __latency_lines_semantic_body_capability_consumers[]; }
namespace scpp {
shared_p<CapabilityConsumerPlan> __latency_fn_semantic_body_capability_consumers_scalar_if_literal_return_plan(shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> ifSourceRowId, int_t<std::uint32_t> thenLiteralSourceRowId, int_t<std::uint32_t> fallbackLiteralSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> returnTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_body_capability_consumers::scalar_if_literal_return_plan", "/tmp/scpp-edit-latency-20260919/app/compile/semantic/semantic_body_capability_consumers.phs", __latency_lines_semantic_body_capability_consumers[12]);
	shared_p<CapabilityConsumerPlan> plan = __latency_fn_capability_consumer_plans_new_plan();
	__latency_fn_capability_consumer_plans_add_registry(plan, __latency_fn_type_capability_readiness_capability_return_lowering_authorized_id());
	__latency_fn_semantic_body_capability_consumers_append_scalar_body_common(plan, body, assignmentSourceRowIds, assignmentTypeRefIds, cast<int_t<std::uint32_t>>(returnTypeRefId));
	__latency_fn_capability_consumer_plans_append_if_condition(plan, ifSourceRowId, conditionTypeRefId);
	__latency_fn_capability_consumer_plans_append_literal_scalar(plan, thenLiteralSourceRowId, returnTypeRefId);
	__latency_fn_capability_consumer_plans_append_literal_scalar(plan, fallbackLiteralSourceRowId, returnTypeRefId);
	return plan;
}

}
