#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/ConditionTruthinessPolicyRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_condition_truthiness_policy_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_if_condition_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_statement_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_if_bool_then_block_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_if_condition_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_while_bool_loop_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_if_condition_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_while_bool_loop_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_for_bool_loop_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_if_condition_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_condition_truthiness_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_condition_truthiness_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_ternary_select_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_expression_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_ternary_select_consumer.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_load_return_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_load_return_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_value_storage_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_statement_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_invalidate_consumer_lookup_index.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_invalidate_readiness_lookup_index.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_if_condition_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::if_condition_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[129]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_control_flow_id();
	row->source_row_id = statementSourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_if_bool_then_block_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_statement_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	ConditionTruthinessPolicyRow policy = __latency_fn_type_capability_readiness_condition_truthiness_policy_from_type_ref(cast<int_t<std::uint32_t>>(typeRefId));
	row->status_id = policy->status_id;
	row->blocked_reason_id = policy->blocked_reason_id;
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_if_bool_then_block_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::if_bool_then_block_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[130]);
	return __latency_fn_type_capability_readiness_if_condition_consumer(cast<int_t<std::uint32_t>>(statementSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_while_bool_loop_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::while_bool_loop_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[131]);
	CapabilityConsumerRow row = __latency_fn_type_capability_readiness_if_condition_consumer(cast<int_t<std::uint32_t>>(statementSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	row->feature_id = __latency_fn_type_capability_readiness_feature_while_bool_loop_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_for_bool_loop_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::for_bool_loop_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[132]);
	CapabilityConsumerRow row = __latency_fn_type_capability_readiness_if_condition_consumer(cast<int_t<std::uint32_t>>(statementSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	row->feature_id = __latency_fn_type_capability_readiness_feature_for_bool_loop_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_ternary_select_consumer(int_t<std::uint32_t> expressionSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> thenTypeRefId, int_t<std::uint32_t> elseTypeRefId, int_t<std::uint32_t> resultTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::ternary_select_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[133]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_control_flow_id();
	row->source_row_id = expressionSourceRowId;
	row->provider_source_row_id = resultTypeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_ternary_select_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_expression_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = resultTypeRefId;
	TypeTraitRow resultTrait = __latency_fn_type_traits_row_from_type_ref_id(resultTypeRefId);
	if (static_cast<bool>((((__latency_fn_type_capability_readiness_condition_truthiness_supported_for_type_ref(cast<int_t<std::uint32_t>>(conditionTypeRefId)) && php::identical(cast<int_t<>>(thenTypeRefId), cast<int_t<>>(resultTypeRefId))) && php::identical(cast<int_t<>>(elseTypeRefId), cast<int_t<>>(resultTypeRefId))) && php::identical(cast<int_t<>>(resultTrait->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id()))))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_condition_truthiness_not_ready_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_local_load_return_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::local_load_return_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[134]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_scalar_local_storage_id();
	row->source_row_id = statementSourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_local_load_return_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_statement_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_local_value_storage_blocked_reason_from_trait(trait));
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityReadinessRow __latency_fn_type_capability_readiness_readiness_from_consumer(CapabilityConsumerRow consumer) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::readiness_from_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[135]);
	CapabilityReadinessRow row = CapabilityReadinessRow{};
	row->source_row_id = consumer->source_row_id;
	row->consumer_feature_id = consumer->feature_id;
	row->source_key_id = consumer->source_key_id;
	row->capability_id = consumer->capability_id;
	row->provider_type_ref_id = consumer->provider_type_ref_id;
	row->status_id = consumer->status_id;
	row->blocked_reason_id = consumer->blocked_reason_id;
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_append_consumer(shared_p<CapabilityCoverageArtifact> artifact, CapabilityConsumerRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::append_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[136]);
	(void) artifact->consumers.append(row);
	artifact->consumer_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->consumers));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_type_capability_readiness_status_blocked_id())))) {
		artifact->blocked_consumer_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_consumer_count) + static_cast<int_t<> >(1)));
	}
	__latency_fn_type_capability_readiness_invalidate_consumer_lookup_index(artifact);
	__latency_fn_type_capability_readiness_update_lookup_policy(artifact);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_append_readiness(shared_p<CapabilityCoverageArtifact> artifact, CapabilityReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::append_readiness", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[137]);
	(void) artifact->readiness.append(row);
	artifact->readiness_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->readiness));
	__latency_fn_type_capability_readiness_invalidate_readiness_lookup_index(artifact);
}

}
