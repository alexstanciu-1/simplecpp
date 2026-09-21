#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_condition_truthiness_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_string_concat_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unknown_type_ref_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unsupported_numeric_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_condition_truthiness_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_string_concat_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unknown_type_ref_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_next_unblock_step.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_equals.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_equals.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_equals.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_debug_string.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_debug_string.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_name.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_debug_string.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_name.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
string_t __latency_fn_type_capability_readiness_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[188]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_type_capability_readiness_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_type_capability_readiness_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
string_t __latency_fn_type_capability_readiness_blocked_reason_name(int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[189]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id())))) {
		return string_t("backend_preflight_not_reintroduced");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_unsupported_numeric_operator_id())))) {
		return string_t("unsupported_numeric_operator");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id())))) {
		return string_t("unknown_type_trait");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_unknown_type_ref_id())))) {
		return string_t("unknown_type_ref");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id())))) {
		return string_t("non_numeric_type");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id())))) {
		return string_t("numeric_operator_not_in_trait_mask");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id())))) {
		return string_t("backend_numeric_abi_not_ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_condition_truthiness_not_ready_id())))) {
		return string_t("condition_truthiness_not_ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id())))) {
		return string_t("runtime_string_abi_not_ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_string_concat_lifetime_not_ready_id())))) {
		return string_t("string_concat_lifetime_not_ready");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
string_t __latency_fn_type_capability_readiness_next_unblock_step(int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::next_unblock_step", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[190]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id())))) {
		return string_t("restore_backend_preflights_and_backend_request_rows");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_unknown_type_trait_id())))) {
		return string_t("add_or_import_type_trait_descriptor");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_unknown_type_ref_id())))) {
		return string_t("add_or_materialize_type_ref_row");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_non_numeric_type_id())))) {
		return string_t("choose_a_numeric_provider_or_add_a_non_numeric_feature_contract");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id())))) {
		return string_t("add_operator_bit_to_numeric_trait_and_backend_adapter");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id())))) {
		return string_t("add_backend_numeric_abi_and_lowering_adapter_for_width");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_condition_truthiness_not_ready_id())))) {
		return string_t("add_condition_truthiness_policy_or_cast_adapter");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id())))) {
		return string_t("make_string_runtime_abi_bridge_source_consumable");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_string_concat_lifetime_not_ready_id())))) {
		return string_t("accept_mutable_string_concat_lifetime_policy");
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
bool_t __latency_fn_type_capability_readiness_provider_equals(CapabilityProviderRow left, CapabilityProviderRow right) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::provider_equals", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[191]);
	return (((php::identical(cast<int_t<>>(left->capability_id), cast<int_t<>>(right->capability_id)) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->type_ref_id), cast<int_t<>>(right->type_ref_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
bool_t __latency_fn_type_capability_readiness_consumer_equals(CapabilityConsumerRow left, CapabilityConsumerRow right) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::consumer_equals", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[192]);
	return ((((php::identical(cast<int_t<>>(left->capability_id), cast<int_t<>>(right->capability_id)) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->provider_type_ref_id), cast<int_t<>>(right->provider_type_ref_id))) && php::identical(cast<int_t<>>(left->feature_id), cast<int_t<>>(right->feature_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
bool_t __latency_fn_type_capability_readiness_readiness_equals(CapabilityReadinessRow left, CapabilityReadinessRow right) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::readiness_equals", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[193]);
	return ((((php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id)) && php::identical(cast<int_t<>>(left->consumer_feature_id), cast<int_t<>>(right->consumer_feature_id))) && php::identical(cast<int_t<>>(left->capability_id), cast<int_t<>>(right->capability_id))) && php::identical(cast<int_t<>>(left->provider_type_ref_id), cast<int_t<>>(right->provider_type_ref_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
string_t __latency_fn_type_capability_readiness_provider_debug_string(CapabilityProviderRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::provider_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[194]);
	return (string_t("capability_provider:") + cast<string_t>(__latency_fn_type_capability_readiness_capability_name(row->capability_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->type_ref_id)));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
string_t __latency_fn_type_capability_readiness_consumer_debug_string(CapabilityConsumerRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::consumer_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[195]);
	return (string_t("capability_consumer:") + cast<string_t>(__latency_fn_type_capability_readiness_feature_name(row->feature_id)) + string_t(":") + cast<string_t>(__latency_fn_type_capability_readiness_capability_name(row->capability_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->provider_type_ref_id)) + string_t(":") + cast<string_t>(__latency_fn_type_capability_readiness_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
string_t __latency_fn_type_capability_readiness_readiness_debug_string(CapabilityReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::readiness_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[196]);
	return (string_t("capability_readiness:") + cast<string_t>(__latency_fn_type_capability_readiness_feature_name(row->consumer_feature_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->provider_type_ref_id)) + string_t(":") + cast<string_t>(__latency_fn_type_capability_readiness_status_name(row->status_id)));
}

}
