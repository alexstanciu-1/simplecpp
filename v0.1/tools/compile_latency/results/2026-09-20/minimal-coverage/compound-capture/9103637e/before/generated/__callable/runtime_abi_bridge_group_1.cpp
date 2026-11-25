#include <scpp/lang/php.hpp>
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_status_name.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_blocked_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_ownership_name.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_consumes_owned_argument_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_mutates_lhs_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_lifetime_name.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_caller_retains_arguments_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_cleanup_consumes_owned_argument_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_in_place_mutation_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_blocked_reason_name.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_blocked_reason_mutating_string_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_blocked_reason_source_consumption_not_wired_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_json_escape.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_blocked_reason_name.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_carrier_name.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_json_escape.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_lifetime_name.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_ownership_name.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_row_json.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_status_name.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_artifact_json.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_json_escape.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_row_json.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_debug_string.hpp"
namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_runtime_abi_bridge_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[7]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_runtime_abi_bridge_ownership_name(int_t<std::uint16_t> policyId) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::ownership_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[8]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id())))) {
		return string_t("owned_return");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id())))) {
		return string_t("borrowed_arguments");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_ownership_mutates_lhs_id())))) {
		return string_t("mutates_lhs");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_ownership_consumes_owned_argument_id())))) {
		return string_t("consumes_owned_argument");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_runtime_abi_bridge_lifetime_name(int_t<std::uint16_t> policyId) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::lifetime_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[9]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id())))) {
		return string_t("explicit_cleanup_required");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_lifetime_caller_retains_arguments_id())))) {
		return string_t("caller_retains_arguments");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_lifetime_in_place_mutation_id())))) {
		return string_t("in_place_mutation");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_lifetime_cleanup_consumes_owned_argument_id())))) {
		return string_t("cleanup_consumes_owned_argument");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_runtime_abi_bridge_blocked_reason_name(int_t<std::uint16_t> reasonId) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[10]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id())))) {
		return string_t("none");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_blocked_reason_source_consumption_not_wired_id())))) {
		return string_t("source_consumption_not_wired");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_blocked_reason_mutating_string_lifetime_not_ready_id())))) {
		return string_t("mutating_string_lifetime_not_ready");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_runtime_abi_bridge_json_escape(const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::json_escape", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[11]);
	string_t out = required_cast<string_t>(str::replace(string_t("\\"), string_t("\\\\"), value));
	out = str::replace(string_t("\""), string_t("\\\""), out);
	out = str::replace(string_t("\n"), string_t("\\n"), out);
	return out;
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_runtime_abi_bridge_row_json(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::row_json", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[12]);
	return (string_t("{\"bridge_row_id\":") + cast<string_t>(cast<int_t<>>(row->bridge_row_id)) + string_t(",\"source_type_ref_id\":") + cast<string_t>(cast<int_t<>>(row->source_type_ref_id)) + string_t(",\"operation_key\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_json_escape(row->operation_key)) + string_t("\"") + string_t(",\"helper_key\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_json_escape(row->helper_key)) + string_t("\"") + string_t(",\"runtime_namespace\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_json_escape(row->runtime_namespace)) + string_t("\"") + string_t(",\"runtime_symbol\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_json_escape(row->runtime_symbol)) + string_t("\"") + string_t(",\"argument_carrier\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_carrier_name(row->argument_carrier_id)) + string_t("\"") + string_t(",\"return_carrier\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_carrier_name(row->return_carrier_id)) + string_t("\"") + string_t(",\"ownership\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_ownership_name(row->ownership_policy_id)) + string_t("\"") + string_t(",\"lifetime\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_lifetime_name(row->lifetime_policy_id)) + string_t("\"") + string_t(",\"optimization_visibility\":\"opaque_call\"") + string_t(",\"declaration_status\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_status_name(row->declaration_status_id)) + string_t("\"") + string_t(",\"call_lowering_status\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_status_name(row->call_lowering_status_id)) + string_t("\"") + string_t(",\"source_consumption_status\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_status_name(row->source_consumption_status_id)) + string_t("\"") + string_t(",\"blocked_reason\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_blocked_reason_name(row->blocked_reason_id)) + string_t("\"") + string_t(",\"llvm_return_type\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_json_escape(row->llvm_return_type)) + string_t("\"") + string_t(",\"llvm_argument_signature\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_json_escape(row->llvm_argument_signature)) + string_t("\"") + string_t(",\"authority_source\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_json_escape(row->authority_source)) + string_t("\"}"));
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_runtime_abi_bridge_artifact_json(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::artifact_json", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[13]);
	string_t json = required_cast<string_t>((string_t("{\"artifact\":\"runtime_abi_bridge_artifact\",\"schema_version\":") + cast<string_t>(cast<int_t<>>(artifact->schema_version)) + string_t(",\"row_count\":") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(",\"declaration_ready_count\":") + cast<string_t>(cast<int_t<>>(artifact->declaration_ready_count)) + string_t(",\"call_lowering_ready_count\":") + cast<string_t>(cast<int_t<>>(artifact->call_lowering_ready_count)) + string_t(",\"call_lowering_blocked_count\":") + cast<string_t>(cast<int_t<>>(artifact->call_lowering_blocked_count)) + string_t(",\"source_consumption_ready_count\":") + cast<string_t>(cast<int_t<>>(artifact->source_consumption_ready_count)) + string_t(",\"source_consumption_blocked_count\":") + cast<string_t>(cast<int_t<>>(artifact->source_consumption_blocked_count)) + string_t(",\"opaque_call_count\":") + cast<string_t>(cast<int_t<>>(artifact->opaque_call_count)) + string_t(",\"owned_return_count\":") + cast<string_t>(cast<int_t<>>(artifact->owned_return_count)) + string_t(",\"consumes_owned_argument_count\":") + cast<string_t>(cast<int_t<>>(artifact->consumes_owned_argument_count)) + string_t(",\"text_coercion_family_count\":") + cast<string_t>(cast<int_t<>>(artifact->text_coercion_family_count)) + string_t(",\"text_coercion_family_member_count\":") + cast<string_t>(cast<int_t<>>(artifact->text_coercion_family_member_count)) + string_t(",\"text_coercion_family_deferred_member_count\":") + cast<string_t>(cast<int_t<>>(artifact->text_coercion_family_deferred_member_count)) + string_t(",\"policy_state\":\"") + cast<string_t>(__latency_fn_runtime_abi_bridge_json_escape(artifact->policy_state)) + string_t("\"") + string_t(",\"rows\":[")));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((index > static_cast<int_t<> >(0)))) {
			json = (cast<string_t>(json) + string_t(","));
		}
		json = (cast<string_t>(json) + cast<string_t>(__latency_fn_runtime_abi_bridge_row_json(row)));
		index = (index + static_cast<int_t<> >(1));
	}
	return (cast<string_t>(json) + string_t("]}"));
}

}

namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_runtime_abi_bridge_debug_string(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[14]);
	return (string_t("runtime_abi_bridge:rows=") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":declaration_ready=") + cast<string_t>(cast<int_t<>>(artifact->declaration_ready_count)) + string_t(":call_lowering_ready=") + cast<string_t>(cast<int_t<>>(artifact->call_lowering_ready_count)) + string_t(":call_lowering_blocked=") + cast<string_t>(cast<int_t<>>(artifact->call_lowering_blocked_count)) + string_t(":source_consumption_ready=") + cast<string_t>(cast<int_t<>>(artifact->source_consumption_ready_count)) + string_t(":source_consumption_blocked=") + cast<string_t>(cast<int_t<>>(artifact->source_consumption_blocked_count)) + string_t(":opaque_calls=") + cast<string_t>(cast<int_t<>>(artifact->opaque_call_count)) + string_t(":owned_returns=") + cast<string_t>(cast<int_t<>>(artifact->owned_return_count)) + string_t(":consumes_owned_arguments=") + cast<string_t>(cast<int_t<>>(artifact->consumes_owned_argument_count)) + string_t(":text_families=") + cast<string_t>(cast<int_t<>>(artifact->text_coercion_family_count)) + string_t(":text_family_members=") + cast<string_t>(cast<int_t<>>(artifact->text_coercion_family_member_count)) + string_t(":text_family_deferred=") + cast<string_t>(cast<int_t<>>(artifact->text_coercion_family_deferred_member_count)) + string_t(":state=") + cast<string_t>(artifact->policy_state));
}

}
