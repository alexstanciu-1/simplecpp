#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerPlan.hpp"
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/capability_consumer_plans.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_new_plan.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_local_storage.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_local_assignment.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_local_load_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_load_return_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_literal_scalar.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_binary_operator.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_if_condition.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_if_condition_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_while_condition.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_while_bool_loop_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_add_registry.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_consumer.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_for_condition.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_for_bool_loop_consumer.hpp"
namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
bool_t capability_consumer_plans::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == capability_consumer_plans::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
shared_p<CapabilityConsumerPlan> __latency_fn_capability_consumer_plans_new_plan() {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::new_plan", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[0]);
	shared_p<CapabilityConsumerPlan> plan = create<CapabilityConsumerPlan>();
	{
	auto __latency_local_0 = __latency_fn_type_capability_readiness_capability_type_ref_known_id();
	(void) plan->registry_capability_ids.append(__latency_local_0);
	}
	return plan;
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_add_registry(shared_p<CapabilityConsumerPlan>& plan, int_t<std::uint16_t> capabilityId) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::add_registry", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[1]);
	auto __latency_local_0 = plan->registry_capability_ids;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existingId = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(existingId), cast<int_t<>>(capabilityId)))) {
			return;
		}
	}
	(void) plan->registry_capability_ids.append(capabilityId);
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_append_consumer(shared_p<CapabilityConsumerPlan>& plan, CapabilityConsumerRow consumer) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::append_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[2]);
	(void) plan->consumers.append(consumer);
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_append_local_storage(shared_p<CapabilityConsumerPlan>& plan, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::append_local_storage", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[3]);
	__latency_fn_capability_consumer_plans_add_registry(plan, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_capability_consumer_plans_append_consumer(plan, __latency_fn_type_capability_readiness_local_storage_consumer(sourceRowId, typeRefId));
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_append_local_assignment(shared_p<CapabilityConsumerPlan>& plan, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::append_local_assignment", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[4]);
	__latency_fn_capability_consumer_plans_add_registry(plan, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_capability_consumer_plans_append_consumer(plan, __latency_fn_type_capability_readiness_local_assignment_consumer(sourceRowId, typeRefId));
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_append_local_load_return(shared_p<CapabilityConsumerPlan>& plan, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::append_local_load_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[5]);
	__latency_fn_capability_consumer_plans_add_registry(plan, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_capability_consumer_plans_append_consumer(plan, __latency_fn_type_capability_readiness_local_load_return_consumer(sourceRowId, typeRefId));
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_append_literal_scalar(shared_p<CapabilityConsumerPlan>& plan, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::append_literal_scalar", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[6]);
	__latency_fn_capability_consumer_plans_append_consumer(plan, __latency_fn_type_capability_readiness_literal_scalar_consumer(sourceRowId, typeRefId));
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_append_binary_operator(shared_p<CapabilityConsumerPlan>& plan, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> providerTypeRefId, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::append_binary_operator", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[7]);
	__latency_fn_capability_consumer_plans_add_registry(plan, __latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(featureId));
	__latency_fn_capability_consumer_plans_append_consumer(plan, __latency_fn_type_capability_readiness_binary_operator_consumer(sourceRowId, providerTypeRefId, featureId));
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_append_if_condition(shared_p<CapabilityConsumerPlan>& plan, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> conditionTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::append_if_condition", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[8]);
	__latency_fn_capability_consumer_plans_add_registry(plan, __latency_fn_type_capability_readiness_capability_control_flow_id());
	__latency_fn_capability_consumer_plans_append_consumer(plan, __latency_fn_type_capability_readiness_if_condition_consumer(sourceRowId, conditionTypeRefId));
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_append_while_condition(shared_p<CapabilityConsumerPlan>& plan, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> conditionTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::append_while_condition", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[9]);
	__latency_fn_capability_consumer_plans_add_registry(plan, __latency_fn_type_capability_readiness_capability_control_flow_id());
	__latency_fn_capability_consumer_plans_append_consumer(plan, __latency_fn_type_capability_readiness_while_bool_loop_consumer(sourceRowId, conditionTypeRefId));
}

}

namespace scpp { extern const int __latency_lines_capability_consumer_plans[]; }
namespace scpp {
void __latency_fn_capability_consumer_plans_append_for_condition(shared_p<CapabilityConsumerPlan>& plan, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> conditionTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("capability_consumer_plans::append_for_condition", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/capability_consumer_plans.phs", __latency_lines_capability_consumer_plans[10]);
	__latency_fn_capability_consumer_plans_add_registry(plan, __latency_fn_type_capability_readiness_capability_control_flow_id());
	__latency_fn_capability_consumer_plans_append_consumer(plan, __latency_fn_type_capability_readiness_for_bool_loop_consumer(sourceRowId, conditionTypeRefId));
}

}
