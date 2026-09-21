#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionAdapterDescriptorRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_binary_result_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_const_int_return_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_direct_call_return_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_i64_printf_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_for_step_kind.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_if_condition_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_local_slot_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_return_value_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_ternary_select_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_const_int_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_direct_call_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_for_condition_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_if_condition_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_local_alloca_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_local_load_return_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_local_store_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_return_value_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_while_condition_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_i64_printf_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_string_abi_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_for_type_policy.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_integer_echo_i64_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_runtime_string_echo_abi_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_runtime_text_coercion_echo_abi_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_any_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_for_step_kind_and_value_role.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_integer_echo_i64_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_runtime_string_echo_abi_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_runtime_text_coercion_echo_abi_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_for_step_kind.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_llvm_emitter_for_type_policy.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_route_descriptor.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_status_descriptor_backed_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_for_step_kind_and_value_role.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_name.hpp"
#include "__callable/__latency_fn_local_body_lowering_routes_step_kind_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_descriptors.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_route_descriptor.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_return_value_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_const_int_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_direct_call_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_for_condition_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_if_condition_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_local_alloca_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_local_load_return_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_local_store_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_return_value_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_while_condition_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_adapter_routes_llvm_emitter_for_step_kind(int_t<std::uint16_t> stepKindId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::llvm_emitter_for_step_kind", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[16]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_return_value_id())))) {
		return __latency_fn_backend_emission_adapter_routes_llvm_emitter_return_value_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_const_int_id())))) {
		return __latency_fn_backend_emission_adapter_routes_llvm_emitter_const_int_return_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_direct_call_id())))) {
		return __latency_fn_backend_emission_adapter_routes_llvm_emitter_direct_call_return_id();
	}
	if (static_cast<bool>(((php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_local_alloca_id())) || php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_local_store_id()))) || php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_local_load_return_id()))))) {
		return __latency_fn_backend_emission_adapter_routes_llvm_emitter_local_slot_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id())))) {
		return __latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_i64_printf_id();
	}
	if (static_cast<bool>(((php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_if_condition_id())) || php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_while_condition_id()))) || php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_for_condition_id()))))) {
		return __latency_fn_backend_emission_adapter_routes_llvm_emitter_if_condition_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id())))) {
		return __latency_fn_backend_emission_adapter_routes_llvm_emitter_ternary_select_id();
	}
	return __latency_fn_backend_emission_adapter_routes_llvm_emitter_binary_result_id();
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_adapter_routes_llvm_emitter_for_type_policy(int_t<std::uint16_t> typePolicyId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::llvm_emitter_for_type_policy", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[17]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(typePolicyId), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_type_policy_integer_echo_i64_id())))) {
		return __latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_i64_printf_id();
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(typePolicyId), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_type_policy_runtime_string_echo_abi_id())) || php::identical(cast<int_t<>>(typePolicyId), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_type_policy_runtime_text_coercion_echo_abi_id()))))) {
		return __latency_fn_backend_emission_adapter_routes_llvm_emitter_echo_string_abi_id();
	}
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_emission_adapter_routes_type_policy_for_step_kind_and_value_role(int_t<std::uint16_t> stepKindId, int_t<std::uint16_t> valueRoleId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::type_policy_for_step_kind_and_value_role", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[18]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id())) || php::identical(cast<int_t<>>(valueRoleId), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_scalar_id()))))) {
		return __latency_fn_backend_emission_adapter_routes_type_policy_integer_echo_i64_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id())))) {
		return __latency_fn_backend_emission_adapter_routes_type_policy_runtime_text_coercion_echo_abi_id();
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(stepKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id())) || php::identical(cast<int_t<>>(valueRoleId), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_string_id()))))) {
		return __latency_fn_backend_emission_adapter_routes_type_policy_runtime_string_echo_abi_id();
	}
	return __latency_fn_backend_emission_adapter_routes_type_policy_any_id();
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
shared_p<BackendEmissionAdapterDescriptorRow> __latency_fn_backend_emission_adapter_routes_route_descriptor(int_t<std::uint16_t> stepKindId, int_t<std::uint16_t> valueRoleId, const string_t& adapterKey) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::route_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[19]);
	shared_p<BackendEmissionAdapterDescriptorRow> row = create<BackendEmissionAdapterDescriptorRow>();
	row->step_kind_id = stepKindId;
	row->value_role_id = valueRoleId;
	row->status_id = __latency_fn_backend_emission_adapter_routes_status_descriptor_backed_id();
	row->type_policy_id = __latency_fn_backend_emission_adapter_routes_type_policy_for_step_kind_and_value_role(cast<int_t<std::uint16_t>>(stepKindId), cast<int_t<std::uint16_t>>(valueRoleId));
	row->llvm_emitter_id = __latency_fn_backend_emission_adapter_routes_llvm_emitter_for_type_policy(row->type_policy_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->llvm_emitter_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id())))) {
		row->llvm_emitter_id = __latency_fn_backend_emission_adapter_routes_llvm_emitter_for_step_kind(cast<int_t<std::uint16_t>>(stepKindId));
	}
	row->adapter_key = adapterKey;
	row->step_kind_key = __latency_fn_local_body_lowering_routes_step_kind_name(stepKindId);
	row->value_role_key = __latency_fn_backend_emission_decisions_value_role_name(valueRoleId);
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_adapter_routes[]; }
namespace scpp {
vector_t<shared_p<BackendEmissionAdapterDescriptorRow>> __latency_fn_backend_emission_adapter_routes_descriptors() {
	SCPP_CALL_DEPTH_GUARD("backend_emission_adapter_routes::descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_adapter_routes.phs", __latency_lines_backend_emission_adapter_routes[20]);
	vector_t<shared_p<BackendEmissionAdapterDescriptorRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(31));
	{
	auto __latency_local_0 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_return_value_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:return_value"));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_const_int_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:const_int_return"));
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_direct_call_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:direct_call_return"));
	(void) rows.push_back(__latency_local_2);
	}
	auto __latency_local_3 = __latency_fn_semantic_operator_lookup_binary_operator_rows();
	for (auto __latency_local_4 : foreach_range(__latency_local_3)) {
		auto operatorRow = __latency_local_4.value_copy();
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(operatorRow->lowering_step_kind_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))))) {
			{
			auto __latency_local_5 = __latency_fn_backend_emission_adapter_routes_route_descriptor(operatorRow->lowering_step_kind_id, __latency_fn_backend_emission_decisions_value_role_return_value_id(), (string_t("emission:") + cast<string_t>(operatorRow->lowering_step_key) + string_t("_return")));
			(void) rows.push_back(__latency_local_5);
			}
		}
	}
	{
	auto __latency_local_6 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_local_alloca_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:local_alloca"));
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_local_store_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:local_store"));
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id(), __latency_fn_backend_emission_decisions_value_role_echo_scalar_id(), string_t("emission:echo_scalar"));
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id(), __latency_fn_backend_emission_decisions_value_role_echo_string_id(), string_t("emission:echo_string"));
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id(), __latency_fn_backend_emission_decisions_value_role_echo_string_id(), string_t("emission:runtime_text_coercion_echo"));
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_if_condition_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:if_condition"));
	(void) rows.push_back(__latency_local_11);
	}
	{
	auto __latency_local_12 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_while_condition_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:while_condition"));
	(void) rows.push_back(__latency_local_12);
	}
	{
	auto __latency_local_13 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_for_condition_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:for_condition"));
	(void) rows.push_back(__latency_local_13);
	}
	{
	auto __latency_local_14 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_local_load_return_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:local_load_return"));
	(void) rows.push_back(__latency_local_14);
	}
	{
	auto __latency_local_15 = __latency_fn_backend_emission_adapter_routes_route_descriptor(__latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id(), __latency_fn_backend_emission_decisions_value_role_return_value_id(), string_t("emission:ternary_select_return"));
	(void) rows.push_back(__latency_local_15);
	}
	return rows;
}

}
