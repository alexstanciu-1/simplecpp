#include <scpp/lang/php.hpp>
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_id_for_source_type_ref_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_refs_int16_id.hpp"
#include "__callable/__latency_fn_type_refs_int32_id.hpp"
#include "__callable/__latency_fn_type_refs_int64_id.hpp"
#include "__callable/__latency_fn_type_refs_int8_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_uint16_id.hpp"
#include "__callable/__latency_fn_type_refs_uint32_id.hpp"
#include "__callable/__latency_fn_type_refs_uint8_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lowering_strategy_runtime_call_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_optimization_visibility_opaque_call_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_row.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_c_string_bytes_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_mut_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_vector_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i32_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_blocked_reason_mutating_string_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_blocked_reason_source_consumption_not_wired_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_caller_retains_arguments_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_cleanup_consumes_owned_argument_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_in_place_mutation_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_consumes_owned_argument_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_mutates_lhs_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_row.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_rows.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_blocked_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_compare_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_concat_assign_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_echo_eval_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_to_string_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_family_vector_id.hpp"
#include "__callable/__latency_fn_type_refs_int16_id.hpp"
#include "__callable/__latency_fn_type_refs_int32_id.hpp"
#include "__callable/__latency_fn_type_refs_int64_id.hpp"
#include "__callable/__latency_fn_type_refs_int8_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_uint16_id.hpp"
#include "__callable/__latency_fn_type_refs_uint32_id.hpp"
#include "__callable/__latency_fn_type_refs_uint8_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_runtime_abi_bridge_text_coercion_family_id_for_source_type_ref_id(int_t<std::uint32_t> sourceTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::text_coercion_family_id_for_source_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[61]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceTypeRefId), cast<int_t<>>(__latency_fn_type_refs_int_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceTypeRefId), cast<int_t<>>(__latency_fn_type_refs_int64_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceTypeRefId), cast<int_t<>>(__latency_fn_type_refs_int8_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceTypeRefId), cast<int_t<>>(__latency_fn_type_refs_int16_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceTypeRefId), cast<int_t<>>(__latency_fn_type_refs_int32_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceTypeRefId), cast<int_t<>>(__latency_fn_type_refs_uint8_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceTypeRefId), cast<int_t<>>(__latency_fn_type_refs_uint16_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceTypeRefId), cast<int_t<>>(__latency_fn_type_refs_uint32_id())))) {
		return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_semantic_runtime_abi_bridge_row(int_t<std::uint16_t> bridgeRowId, int_t<std::uint16_t> helperId, int_t<std::uint16_t> argumentCarrierId, int_t<std::uint16_t> returnCarrierId, int_t<std::uint16_t> ownershipPolicyId, int_t<std::uint16_t> lifetimePolicyId, int_t<std::uint16_t> callLoweringStatusId, int_t<std::uint16_t> sourceConsumptionStatusId, int_t<std::uint16_t> blockedReasonId, int_t<std::uint16_t> argumentCount, int_t<std::uint32_t> sourceTypeRefId, int_t<std::uint32_t> resultTypeRefId, const string_t& operationKey, const string_t& helperKey, const string_t& runtimeNamespace, const string_t& runtimeSymbol, const string_t& argumentSignatureKey, const string_t& resultRuntimeType, const string_t& llvmReturnType, const string_t& llvmArgumentSignature, const string_t& authoritySource) {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::row", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[62]);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row = create<SemanticRuntimeAbiBridgeDescriptorRow>();
	row->bridge_row_id = bridgeRowId;
	row->helper_id = helperId;
	row->lowering_strategy_id = __latency_fn_semantic_runtime_abi_bridge_lowering_strategy_runtime_call_id();
	row->abi_carrier_id = returnCarrierId;
	row->argument_carrier_id = argumentCarrierId;
	row->return_carrier_id = returnCarrierId;
	row->ownership_policy_id = ownershipPolicyId;
	row->lifetime_policy_id = lifetimePolicyId;
	row->optimization_visibility_id = __latency_fn_semantic_runtime_abi_bridge_optimization_visibility_opaque_call_id();
	row->argument_count = argumentCount;
	row->source_type_ref_id = sourceTypeRefId;
	row->result_type_ref_id = resultTypeRefId;
	row->declaration_status_id = __latency_fn_semantic_runtime_abi_bridge_status_ready_id();
	row->call_lowering_status_id = callLoweringStatusId;
	row->source_consumption_status_id = sourceConsumptionStatusId;
	row->blocked_reason_id = blockedReasonId;
	row->operation_key = operationKey;
	row->helper_key = helperKey;
	row->runtime_namespace = runtimeNamespace;
	row->runtime_symbol = runtimeSymbol;
	row->argument_signature_key = argumentSignatureKey;
	row->result_runtime_type = resultRuntimeType;
	row->llvm_return_type = llvmReturnType;
	row->llvm_argument_signature = llvmArgumentSignature;
	row->authority_source = authoritySource;
	return row;
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
vector_t<shared_p<SemanticRuntimeAbiBridgeDescriptorRow>> __latency_fn_semantic_runtime_abi_bridge_rows() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::rows", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[63]);
	vector_t<shared_p<SemanticRuntimeAbiBridgeDescriptorRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(17));
	{
	auto __latency_local_0 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_c_string_bytes_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), string_t("string.literal.cstr_ctor"), string_t(""), string_t("scpp"), string_t("scpp_v2_string_from_cstr"), string_t("c_string_bytes"), string_t("string_t"), string_t("ptr"), string_t("ptr, i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:types.string_t.entry_ctors.const_char_ptr"));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_int_id(), __latency_fn_structure_row_ids_none_id(), string_t("php.text_coercion.int_t"), string_t("to_string"), string_t("scpp::php"), string_t("scpp_v2_php_to_string_i64"), string_t("primitive_i64"), string_t("string_t"), string_t("ptr"), string_t("i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:coercions.text.abi_bridge_rule_families.php_text_integer_widths_to_string_i64.int"));
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_int64_id(), __latency_fn_structure_row_ids_none_id(), string_t("php.text_coercion.int64"), string_t("to_string"), string_t("scpp::php"), string_t("scpp_v2_php_to_string_i64"), string_t("primitive_i64"), string_t("string_t"), string_t("ptr"), string_t("i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:coercions.text.abi_bridge_rule_families.php_text_integer_widths_to_string_i64.int64"));
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_int8_id(), __latency_fn_structure_row_ids_none_id(), string_t("php.text_coercion.int8"), string_t("to_string"), string_t("scpp::php"), string_t("scpp_v2_php_to_string_i64"), string_t("primitive_i64"), string_t("string_t"), string_t("ptr"), string_t("i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:coercions.text.abi_bridge_rule_families.php_text_integer_widths_to_string_i64.int8"));
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_int16_id(), __latency_fn_structure_row_ids_none_id(), string_t("php.text_coercion.int16"), string_t("to_string"), string_t("scpp::php"), string_t("scpp_v2_php_to_string_i64"), string_t("primitive_i64"), string_t("string_t"), string_t("ptr"), string_t("i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:coercions.text.abi_bridge_rule_families.php_text_integer_widths_to_string_i64.int16"));
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_int32_id(), __latency_fn_structure_row_ids_none_id(), string_t("php.text_coercion.int32"), string_t("to_string"), string_t("scpp::php"), string_t("scpp_v2_php_to_string_i64"), string_t("primitive_i64"), string_t("string_t"), string_t("ptr"), string_t("i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:coercions.text.abi_bridge_rule_families.php_text_integer_widths_to_string_i64.int32"));
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_uint8_id(), __latency_fn_structure_row_ids_none_id(), string_t("php.text_coercion.uint8"), string_t("to_string"), string_t("scpp::php"), string_t("scpp_v2_php_to_string_i64"), string_t("primitive_i64"), string_t("string_t"), string_t("ptr"), string_t("i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:coercions.text.abi_bridge_rule_families.php_text_integer_widths_to_string_i64.uint8"));
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_uint16_id(), __latency_fn_structure_row_ids_none_id(), string_t("php.text_coercion.uint16"), string_t("to_string"), string_t("scpp::php"), string_t("scpp_v2_php_to_string_i64"), string_t("primitive_i64"), string_t("string_t"), string_t("ptr"), string_t("i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:coercions.text.abi_bridge_rule_families.php_text_integer_widths_to_string_i64.uint16"));
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(9)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_uint32_id(), __latency_fn_structure_row_ids_none_id(), string_t("php.text_coercion.uint32"), string_t("to_string"), string_t("scpp::php"), string_t("scpp_v2_php_to_string_i64"), string_t("primitive_i64"), string_t("string_t"), string_t("ptr"), string_t("i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:coercions.text.abi_bridge_rule_families.php_text_integer_widths_to_string_i64.uint32"));
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10)), __latency_fn_semantic_runtime_abi_declarations_helper_echo_eval_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_caller_retains_arguments_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), string_t("php.echo_eval.string"), string_t("echo_eval"), string_t("scpp::php"), string_t("scpp_v2_php_echo_eval_string"), string_t("opaque_runtime_string"), string_t("void"), string_t("void"), string_t("ptr"), string_t("vendor/simple_cpp/runtime/specs/config.json:runtime_helpers_contract.echo_eval"));
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(11)), __latency_fn_semantic_runtime_abi_declarations_helper_compare_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_caller_retains_arguments_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), string_t("string.compare.lexicographic"), string_t("compare"), string_t("scpp"), string_t("scpp_v2_string_compare"), string_t("opaque_runtime_string,opaque_runtime_string"), string_t("int64"), string_t("i64"), string_t("ptr, ptr"), string_t("vendor/simple_cpp/runtime/specs/config.json:runtime_helpers_contract.compare+types.string_t.stable_core_api.lexicographic_compare_via_native_value+operator_matrix.string_t.comparison"));
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(12)), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_consumes_owned_argument_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_cleanup_consumes_owned_argument_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), string_t("string.release"), string_t(""), string_t("scpp"), string_t("scpp_v2_string_release"), string_t("opaque_runtime_string"), string_t("void"), string_t("void"), string_t("ptr"), string_t("vendor/simple_cpp/runtime/specs/config.json:types.string_t.abi_lifecycle.release_owned"));
	(void) rows.push_back(__latency_local_11);
	}
	{
	auto __latency_local_12 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(13)), __latency_fn_semantic_runtime_abi_declarations_helper_concat_assign_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_mut_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_mutates_lhs_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_in_place_mutation_id(), __latency_fn_semantic_runtime_abi_bridge_status_blocked_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_mutating_string_lifetime_not_ready_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), string_t("string.concat_assign.string"), string_t("concat_assign"), string_t("scpp"), string_t("scpp_v2_string_concat_assign"), string_t("opaque_runtime_string_mut,opaque_runtime_string"), string_t("string_t"), string_t("ptr"), string_t("ptr, ptr"), string_t("vendor/simple_cpp/runtime/specs/config.json:runtime_helpers_contract.concat_assign+types.string_t.stable_core_api.append"));
	(void) rows.push_back(__latency_local_12);
	}
	{
	auto __latency_local_13 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(14)), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_vector_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_source_consumption_not_wired_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(0)), __latency_fn_type_refs_family_instance_type_ref_id(__latency_fn_type_refs_family_vector_id(), __latency_fn_type_refs_int32_id()), __latency_fn_structure_row_ids_none_id(), string_t("vector.construct.default"), string_t(""), string_t("scpp"), string_t("scpp_v2_vector_i32_new"), string_t("void"), string_t("vector_t<int32>"), string_t("ptr"), string_t("void"), string_t("vendor/simple_cpp/runtime/specs/config.json:types.vector_t.stable_core_api.default_ctor"));
	(void) rows.push_back(__latency_local_13);
	}
	{
	auto __latency_local_14 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(15)), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_vector_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_mutates_lhs_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_in_place_mutation_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_source_consumption_not_wired_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_type_refs_family_instance_type_ref_id(__latency_fn_type_refs_family_vector_id(), __latency_fn_type_refs_int32_id()), __latency_fn_structure_row_ids_none_id(), string_t("vector.append"), string_t(""), string_t("scpp"), string_t("scpp_v2_vector_i32_append"), string_t("opaque_runtime_vector,primitive_i32"), string_t("void"), string_t("void"), string_t("ptr, i32"), string_t("vendor/simple_cpp/runtime/specs/config.json:types.vector_t.stable_core_api.append"));
	(void) rows.push_back(__latency_local_14);
	}
	{
	auto __latency_local_15 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(16)), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_vector_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i32_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_caller_retains_arguments_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_source_consumption_not_wired_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_type_refs_family_instance_type_ref_id(__latency_fn_type_refs_family_vector_id(), __latency_fn_type_refs_int32_id()), __latency_fn_structure_row_ids_none_id(), string_t("vector.read.index"), string_t(""), string_t("scpp"), string_t("scpp_v2_vector_i32_at"), string_t("opaque_runtime_vector,primitive_i64"), string_t("int32"), string_t("i32"), string_t("ptr, i64"), string_t("vendor/simple_cpp/runtime/specs/config.json:types.vector_t.stable_core_api.index"));
	(void) rows.push_back(__latency_local_15);
	}
	{
	auto __latency_local_16 = __latency_fn_semantic_runtime_abi_bridge_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(17)), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_vector_id(), __latency_fn_semantic_runtime_abi_bridge_abi_carrier_void_id(), __latency_fn_semantic_runtime_abi_bridge_ownership_consumes_owned_argument_id(), __latency_fn_semantic_runtime_abi_bridge_lifetime_cleanup_consumes_owned_argument_id(), __latency_fn_semantic_runtime_abi_bridge_status_ready_id(), __latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id(), __latency_fn_semantic_runtime_abi_bridge_blocked_reason_source_consumption_not_wired_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_family_instance_type_ref_id(__latency_fn_type_refs_family_vector_id(), __latency_fn_type_refs_int32_id()), __latency_fn_structure_row_ids_none_id(), string_t("vector.cleanup"), string_t(""), string_t("scpp"), string_t("scpp_v2_vector_i32_release"), string_t("opaque_runtime_vector"), string_t("void"), string_t("void"), string_t("ptr"), string_t("vendor/simple_cpp/runtime/specs/config.json:types.vector_t.lifecycle.destroy_owned"));
	(void) rows.push_back(__latency_local_16);
	}
	return rows;
}

}
