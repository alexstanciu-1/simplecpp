#include <scpp/lang/php.hpp>
#include "__types/RuntimeHelperContractReadinessArtifact.hpp"
#include "__types/RuntimeHelperContractReadinessRow.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_append_row.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_append_row.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_build.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_compare_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_concat_assign_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_echo_eval_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_to_string_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_row.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_compare_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_concat_assign_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_echo_eval_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_to_string_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_row_by_helper_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_status_name.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_status_not_applicable_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_compare_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_concat_assign_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_echo_eval_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_name.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_to_string_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_blocked_reason_callable_demand_not_wired_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_blocked_reason_helper_not_declared_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_blocked_reason_name.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_blocked_reason_string_storage_not_ready_id.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_json_escape.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_blocked_reason_name.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_helper_kind_name.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_json_escape.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_row_json.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_status_name.hpp"
namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
void __latency_fn_runtime_helper_contract_readiness_append_row(shared_p<RuntimeHelperContractReadinessArtifact>& artifact, shared_p<RuntimeHelperContractReadinessRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[14]);
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->declaration_status_id), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_status_ready_id())))) {
		artifact->declaration_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->declaration_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->generator_allowed_status_id), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_status_ready_id())))) {
		artifact->generator_allowed_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->generator_allowed_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->callable_demand_status_id), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_status_blocked_id())))) {
		artifact->callable_demand_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->callable_demand_blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->storage_lifetime_status_id), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_status_blocked_id())))) {
		artifact->string_storage_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->string_storage_blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->lowering_status_id), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_status_blocked_id())))) {
		artifact->lowering_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->lowering_blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->readiness_status_id), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_status_ready_id())))) {
		artifact->accepted_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->accepted_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
shared_p<RuntimeHelperContractReadinessArtifact> __latency_fn_runtime_helper_contract_readiness_build() {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::build", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[15]);
	shared_p<RuntimeHelperContractReadinessArtifact> artifact = __latency_fn_runtime_helper_contract_readiness_new_artifact();
	__latency_fn_runtime_helper_contract_readiness_append_row(artifact, __latency_fn_runtime_helper_contract_readiness_row(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_runtime_abi_declarations_helper_echo_eval_id(), __latency_fn_runtime_helper_contract_readiness_helper_kind_echo_eval_id(), string_t("echo_eval"), string_t("output_echo_argument_list"), bool_t(static_cast<bool_t>(false))));
	__latency_fn_runtime_helper_contract_readiness_append_row(artifact, __latency_fn_runtime_helper_contract_readiness_row(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(2)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), __latency_fn_runtime_helper_contract_readiness_helper_kind_to_string_id(), string_t("to_string"), string_t("printable_conversion_and_string_cast"), bool_t(static_cast<bool_t>(true))));
	__latency_fn_runtime_helper_contract_readiness_append_row(artifact, __latency_fn_runtime_helper_contract_readiness_row(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(3)), __latency_fn_semantic_runtime_abi_declarations_helper_concat_assign_id(), __latency_fn_runtime_helper_contract_readiness_helper_kind_concat_assign_id(), string_t("concat_assign"), string_t("string_concat_assignment"), bool_t(static_cast<bool_t>(true))));
	__latency_fn_runtime_helper_contract_readiness_append_row(artifact, __latency_fn_runtime_helper_contract_readiness_row(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(4)), __latency_fn_semantic_runtime_abi_declarations_helper_compare_id(), __latency_fn_runtime_helper_contract_readiness_helper_kind_compare_id(), string_t("compare"), string_t("string_comparison_operator"), bool_t(static_cast<bool_t>(true))));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
shared_p<RuntimeHelperContractReadinessRow> __latency_fn_runtime_helper_contract_readiness_row_by_helper_id(shared_p<RuntimeHelperContractReadinessArtifact> artifact, int_t<std::uint16_t> helperId) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::row_by_helper_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[16]);
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->helper_id), cast<int_t<>>(helperId)))) {
			return row;
		}
	}
	shared_p<RuntimeHelperContractReadinessRow> empty = create<RuntimeHelperContractReadinessRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
string_t __latency_fn_runtime_helper_contract_readiness_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[17]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_status_blocked_id())))) {
		return string_t("blocked");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_status_not_applicable_id())))) {
		return string_t("not_applicable");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
string_t __latency_fn_runtime_helper_contract_readiness_helper_kind_name(int_t<std::uint16_t> helperKindId) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::helper_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[18]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(helperKindId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_helper_kind_echo_eval_id())))) {
		return string_t("echo_eval");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(helperKindId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_helper_kind_to_string_id())))) {
		return string_t("to_string");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(helperKindId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_helper_kind_concat_assign_id())))) {
		return string_t("concat_assign");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(helperKindId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_helper_kind_compare_id())))) {
		return string_t("compare");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
string_t __latency_fn_runtime_helper_contract_readiness_blocked_reason_name(int_t<std::uint16_t> reasonId) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[19]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_blocked_reason_none_id())))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_blocked_reason_callable_demand_not_wired_id())))) {
		return string_t("callable_demand_not_wired");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_blocked_reason_string_storage_not_ready_id())))) {
		return string_t("string_storage_not_ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_runtime_helper_contract_readiness_blocked_reason_helper_not_declared_id())))) {
		return string_t("helper_not_declared");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
string_t __latency_fn_runtime_helper_contract_readiness_json_escape(const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::json_escape", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[20]);
	string_t escaped = required_cast<string_t>(str::replace(string_t("\\"), string_t("\\\\"), value));
	escaped = str::replace(string_t("\""), string_t("\\\""), escaped);
	escaped = str::replace(string_t("\n"), string_t("\\n"), escaped);
	return escaped;
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
string_t __latency_fn_runtime_helper_contract_readiness_row_json(shared_p<RuntimeHelperContractReadinessRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::row_json", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[21]);
	return (string_t("{\"row_id\":") + cast<string_t>(cast<int_t<>>(row->row_id)) + string_t(",\"helper_id\":") + cast<string_t>(cast<int_t<>>(row->helper_id)) + string_t(",\"helper_kind\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_helper_kind_name(row->helper_kind_id)) + string_t("\"") + string_t(",\"helper_key\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_json_escape(row->helper_key)) + string_t("\"") + string_t(",\"required_by\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_json_escape(row->required_by)) + string_t("\"") + string_t(",\"declared\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_status_name(row->declaration_status_id)) + string_t("\"") + string_t(",\"generator_allowed\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_status_name(row->generator_allowed_status_id)) + string_t("\"") + string_t(",\"callable_demand\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_status_name(row->callable_demand_status_id)) + string_t("\"") + string_t(",\"storage_lifetime\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_status_name(row->storage_lifetime_status_id)) + string_t("\"") + string_t(",\"lowering\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_status_name(row->lowering_status_id)) + string_t("\"") + string_t(",\"readiness\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_status_name(row->readiness_status_id)) + string_t("\"") + string_t(",\"blocked_reason\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_blocked_reason_name(row->blocked_reason_id)) + string_t("\"}"));
}

}
