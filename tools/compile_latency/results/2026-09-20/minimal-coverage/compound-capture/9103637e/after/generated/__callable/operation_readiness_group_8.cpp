#include <scpp/lang/php.hpp>
#include "__types/FeatureOperationRouteDescriptorRow.hpp"
#include "__types/OperationBlockedReasonRouteRow.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_backend_lowering_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_condition_truthiness_not_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_string_concat_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_operation_readiness_next_unblock_step.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_operation_route_by_feature_id.hpp"
#include "__callable/__latency_fn_operation_readiness_is_numeric_binary_feature.hpp"
#include "__callable/__latency_fn_operation_readiness_route_kind_numeric_binary_id.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_operation_route_by_feature_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_for_numeric_binary_feature.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_id_for_numeric_binary_feature.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_operation_route_by_feature_id.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_operation_route_by_feature_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_for_numeric_binary_feature.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_route.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_condition_truthiness_not_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_route.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_routes_from_capability.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_string_concat_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_unsupported_numeric_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_condition_truthiness_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_string_concat_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unsupported_numeric_operator_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_route_from_capability.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_routes_from_capability.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_backend_lowering_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_from_capability_blocked_reason.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_route_from_capability.hpp"
namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
string_t __latency_fn_operation_readiness_next_unblock_step(int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::next_unblock_step", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[114]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_backend_lowering_not_reintroduced_id())))) {
		return string_t("restore_backend_preflights_requests_and_lowering_plan_rows");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_unknown_type_trait_id())))) {
		return string_t("add_or_import_type_trait_descriptor");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_non_numeric_type_id())))) {
		return string_t("choose_a_numeric_provider_or_add_a_non_numeric_feature_contract");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id())))) {
		return string_t("add_operator_bit_to_numeric_trait_and_backend_adapter");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_backend_numeric_abi_not_ready_id())))) {
		return string_t("add_backend_numeric_abi_and_lowering_adapter_for_width");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_condition_truthiness_not_ready_id())))) {
		return string_t("add_condition_truthiness_policy_or_cast_adapter");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_string_concat_lifetime_not_ready_id())))) {
		return string_t("accept_mutable_string_concat_lifetime_policy");
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
bool_t __latency_fn_operation_readiness_is_numeric_binary_feature(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::is_numeric_binary_feature", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[115]);
	FeatureOperationRouteDescriptorRow route = __latency_fn_operation_readiness_feature_operation_route_by_feature_id(cast<int_t<std::uint16_t>>(featureId));
	return bool_t(php::identical(cast<int_t<>>(route->route_kind_id), cast<int_t<>>(__latency_fn_operation_readiness_route_kind_numeric_binary_id())));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_operation_kind_for_numeric_binary_feature(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::operation_kind_for_numeric_binary_feature", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[116]);
	FeatureOperationRouteDescriptorRow route = __latency_fn_operation_readiness_feature_operation_route_by_feature_id(cast<int_t<std::uint16_t>>(featureId));
	return route->operation_kind_id;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_contract_id_for_numeric_binary_feature(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::contract_id_for_numeric_binary_feature", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[117]);
	FeatureOperationRouteDescriptorRow route = __latency_fn_operation_readiness_feature_operation_route_by_feature_id(cast<int_t<std::uint16_t>>(featureId));
	return route->contract_id;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_lowering_adapter_for_numeric_binary_feature(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::lowering_adapter_for_numeric_binary_feature", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[118]);
	FeatureOperationRouteDescriptorRow route = __latency_fn_operation_readiness_feature_operation_route_by_feature_id(cast<int_t<std::uint16_t>>(featureId));
	return route->lowering_adapter_id;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
OperationBlockedReasonRouteRow __latency_fn_operation_readiness_blocked_reason_route(int_t<std::uint16_t> capabilityBlockedReasonId, int_t<std::uint16_t> operationBlockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_route", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[119]);
	OperationBlockedReasonRouteRow row = OperationBlockedReasonRouteRow{};
	row->capability_blocked_reason_id = capabilityBlockedReasonId;
	row->operation_blocked_reason_id = operationBlockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
vector_t<OperationBlockedReasonRouteRow> __latency_fn_operation_readiness_blocked_reason_routes_from_capability() {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_routes_from_capability", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[120]);
	vector_t<OperationBlockedReasonRouteRow> rows = {};
	{
	auto __latency_local_0 = __latency_fn_operation_readiness_blocked_reason_route(__latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id(), __latency_fn_operation_readiness_blocked_reason_unknown_type_trait_id());
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_operation_readiness_blocked_reason_route(__latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id(), __latency_fn_operation_readiness_blocked_reason_non_numeric_type_id());
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_operation_readiness_blocked_reason_route(__latency_fn_type_capability_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id(), __latency_fn_operation_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id());
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_operation_readiness_blocked_reason_route(__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id(), __latency_fn_operation_readiness_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_operation_readiness_blocked_reason_route(__latency_fn_type_capability_readiness_blocked_reason_unsupported_numeric_operator_id(), __latency_fn_operation_readiness_blocked_reason_unsupported_numeric_operator_id());
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_operation_readiness_blocked_reason_route(__latency_fn_type_capability_readiness_blocked_reason_condition_truthiness_not_ready_id(), __latency_fn_operation_readiness_blocked_reason_condition_truthiness_not_ready_id());
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_operation_readiness_blocked_reason_route(__latency_fn_type_capability_readiness_blocked_reason_string_concat_lifetime_not_ready_id(), __latency_fn_operation_readiness_blocked_reason_string_concat_lifetime_not_ready_id());
	(void) rows.push_back(__latency_local_6);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
OperationBlockedReasonRouteRow __latency_fn_operation_readiness_blocked_reason_route_from_capability(int_t<std::uint16_t> capabilityBlockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_route_from_capability", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[121]);
	auto __latency_local_0 = __latency_fn_operation_readiness_blocked_reason_routes_from_capability();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->capability_blocked_reason_id), cast<int_t<>>(capabilityBlockedReasonId)))) {
			return row;
		}
	}
	OperationBlockedReasonRouteRow empty = OperationBlockedReasonRouteRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_blocked_reason_from_capability_blocked_reason(int_t<std::uint16_t> capabilityBlockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_from_capability_blocked_reason", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[122]);
	OperationBlockedReasonRouteRow route = __latency_fn_operation_readiness_blocked_reason_route_from_capability(cast<int_t<std::uint16_t>>(capabilityBlockedReasonId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(route->capability_blocked_reason_id), cast<int_t<>>(capabilityBlockedReasonId)))) {
		return route->operation_blocked_reason_id;
	}
	return __latency_fn_operation_readiness_blocked_reason_backend_lowering_not_reintroduced_id();
}

}
