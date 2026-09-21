#include <scpp/lang/php.hpp>
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_add_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_add_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_and_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_and_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_eq_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_eq_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_identical_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_identical_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_ne_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_ne_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_not_identical_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_not_identical_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_or_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_bool_or_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_div_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_div_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_double_add_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_double_add_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_float_add_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_float_add_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_eq_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_eq_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_identical_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_identical_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_ne_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_ne_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_not_identical_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_not_identical_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_sge_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_sge_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_sgt_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_sgt_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_sle_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_sle_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_slt_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_int_slt_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_mod_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_mod_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_mul_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_mul_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_sub_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_sub_store_immediate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_bool_and_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_bool_eq_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_bool_ne_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_bool_or_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_double_add_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_float_add_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_add_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_div_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_eq_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_mod_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_mul_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_ne_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_sge_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_sgt_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_sle_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_slt_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_int_sub_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_divide_int_int_to_int_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_equal_bool_bool_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_equal_int_int_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_greater_than_int_int_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_greater_than_or_equal_int_int_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_identical_bool_bool_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_identical_int_int_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_less_than_int_int_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_less_than_or_equal_int_int_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_logical_and_bool_bool_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_logical_or_bool_bool_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_minus_int_int_to_int_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_modulo_int_int_to_int_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_multiply_int_int_to_int_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_not_equal_bool_bool_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_not_equal_int_int_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_not_identical_bool_bool_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_not_identical_int_int_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_plus_double_double_to_double_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_plus_float_float_to_float_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_binary_plus_int_int_to_int_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_string_comparison_runtime_helper_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_none_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_f32_add_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_f64_add_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i1_and_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i1_eq_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i1_ne_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i1_or_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_add_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_div_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_eq_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_mod_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_mul_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_ne_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_sge_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_sgt_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_sle_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_slt_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_primitive_i64_sub_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_divide_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_equal_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_or_equal_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_identical_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_less_than_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_less_than_or_equal_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_logical_and_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_logical_or_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_minus_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_modulo_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_multiply_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_not_equal_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_not_identical_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_plus_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operand_category_bool_scalar_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operand_category_string_scalar_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_bool_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_bool_identical_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_bool_not_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_bool_not_identical_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_divide_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_double_plus_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_float_plus_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_greater_than_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_greater_than_or_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_identical_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_less_than_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_less_than_or_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_logical_and_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_logical_or_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_minus_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_modulo_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_multiply_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_not_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_not_identical_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_plus_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_string_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_string_greater_than_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_string_greater_than_or_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_string_less_than_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_string_less_than_or_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_string_not_equal_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_bool_logical_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_numeric_binary_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_divide_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_equal_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_greater_than_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_greater_than_or_equal_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_identical_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_less_than_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_less_than_or_equal_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_logical_and_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_logical_or_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_minus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_modulo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_multiply_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_not_equal_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_not_identical_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_plus_id.hpp"
#include "__callable/__latency_fn_type_refs_bool_id.hpp"
#include "__callable/__latency_fn_type_refs_double_id.hpp"
#include "__callable/__latency_fn_type_refs_float_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_string_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_semantic_operator_lookup_operator_count() {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::operator_count", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[33]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(29));
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row(int_t<std::uint16_t> operatorId, int_t<std::uint16_t> arity, const string_t& operatorSymbol, const string_t& operatorName, const string_t& authorityItemId, const string_t& authorityRowFile, int_t<std::uint32_t> lhsTypeRefId, int_t<std::uint32_t> rhsTypeRefId, int_t<std::uint32_t> resultTypeRefId, int_t<std::uint16_t> capabilityId, int_t<std::uint16_t> operandCategoryId, int_t<std::uint16_t> featureId, int_t<std::uint16_t> operationKindId, int_t<std::uint16_t> contractId, int_t<std::uint16_t> loweringAdapterId, int_t<std::uint16_t> loweringStepKindId, int_t<std::uint16_t> localImmediateOperationId, int_t<std::uint16_t> localStoreOperationId, int_t<std::uint16_t> precedence, const string_t& loweringStepKey, const string_t& binaryResultKey, const string_t& llvmOpcode, const string_t& runtimeSymbol, const string_t& diagnosticFamily, const string_t& diagnosticOperandSuffix) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::row", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[34]);
	shared_p<SemanticOperatorLookupRow> row = create<SemanticOperatorLookupRow>();
	row->operator_id = operatorId;
	row->arity = arity;
	row->operand_category_id = operandCategoryId;
	row->lhs_type_ref_id = lhsTypeRefId;
	row->rhs_type_ref_id = rhsTypeRefId;
	row->result_type_ref_id = resultTypeRefId;
	row->capability_id = capabilityId;
	row->feature_id = featureId;
	row->operation_kind_id = operationKindId;
	row->contract_id = contractId;
	row->lowering_adapter_id = loweringAdapterId;
	row->lowering_step_kind_id = loweringStepKindId;
	row->local_immediate_operation_id = localImmediateOperationId;
	row->local_store_operation_id = localStoreOperationId;
	row->precedence = precedence;
	row->operator_symbol = operatorSymbol;
	row->operator_name = operatorName;
	row->authority_item_id = authorityItemId;
	row->authority_row_file = authorityRowFile;
	row->lowering_step_key = loweringStepKey;
	row->binary_result_key = binaryResultKey;
	row->llvm_opcode = llvmOpcode;
	row->runtime_symbol = runtimeSymbol;
	row->diagnostic_key = (cast<string_t>(diagnosticFamily) + string_t(".") + cast<string_t>(authorityItemId) + string_t(".") + cast<string_t>(diagnosticOperandSuffix));
	return row;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
vector_t<shared_p<SemanticOperatorLookupRow>> __latency_fn_semantic_operator_lookup_binary_operator_rows() {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::binary_operator_rows", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[35]);
	vector_t<shared_p<SemanticOperatorLookupRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(27));
	{
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_plus_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("+"), string_t("plus"), string_t("add"), string_t("semantics/operators_binary_arithmetic/add__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_capability_readiness_capability_numeric_binary_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_plus_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_plus_id(), __latency_fn_operation_readiness_contract_binary_plus_int_int_to_int_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_add_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_add_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_add_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_add_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10)), string_t("int_add"), string_t("add"), string_t("add"), string_t(""), string_t("operator.binary_arithmetic"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_minus_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("-"), string_t("minus"), string_t("subtract"), string_t("semantics/operators_binary_arithmetic/subtract__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_capability_readiness_capability_numeric_binary_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_minus_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_minus_id(), __latency_fn_operation_readiness_contract_binary_minus_int_int_to_int_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_sub_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_sub_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_sub_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_sub_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10)), string_t("int_sub"), string_t("sub"), string_t("sub"), string_t(""), string_t("operator.binary_arithmetic"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_multiply_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("*"), string_t("multiply"), string_t("multiply"), string_t("semantics/operators_binary_arithmetic/multiply__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_capability_readiness_capability_numeric_binary_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_multiply_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_multiply_id(), __latency_fn_operation_readiness_contract_binary_multiply_int_int_to_int_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_mul_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_mul_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_mul_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_mul_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(20)), string_t("int_mul"), string_t("mul"), string_t("mul"), string_t(""), string_t("operator.binary_arithmetic"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_divide_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("/"), string_t("divide"), string_t("divide"), string_t("semantics/operators_binary_arithmetic/divide__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_capability_readiness_capability_numeric_binary_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_divide_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_divide_id(), __latency_fn_operation_readiness_contract_binary_divide_int_int_to_int_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_div_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_div_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_div_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_div_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(20)), string_t("int_div"), string_t("div"), string_t("sdiv"), string_t(""), string_t("operator.binary_arithmetic"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_modulo_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("%"), string_t("modulo"), string_t("modulo"), string_t("semantics/operators_binary_arithmetic/modulo__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_capability_readiness_capability_numeric_binary_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_modulo_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_modulo_id(), __latency_fn_operation_readiness_contract_binary_modulo_int_int_to_int_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_mod_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_mod_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_mod_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_mod_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(20)), string_t("int_mod"), string_t("mod"), string_t("srem"), string_t(""), string_t("operator.binary_arithmetic"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("=="), string_t("equal"), string_t("equal"), string_t("semantics/operators_comparison_equality/equal__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_equal_id(), __latency_fn_operation_readiness_contract_binary_equal_int_int_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_eq_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_eq_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_eq_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_eq_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("int_eq"), string_t("eq"), string_t("icmp eq"), string_t(""), string_t("operator.comparison_equality"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_not_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("!="), string_t("not_equal"), string_t("not_equal"), string_t("semantics/operators_comparison_equality/not_equal__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_not_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_not_equal_id(), __latency_fn_operation_readiness_contract_binary_not_equal_int_int_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_ne_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_ne_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_ne_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_ne_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("int_ne"), string_t("ne"), string_t("icmp ne"), string_t(""), string_t("operator.comparison_equality"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_less_than_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("<"), string_t("less_than"), string_t("less_than"), string_t("semantics/operators_comparison_ordering/less_than__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_less_than_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_less_than_id(), __latency_fn_operation_readiness_contract_binary_less_than_int_int_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_slt_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_slt_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_slt_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_slt_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("int_slt"), string_t("slt"), string_t("icmp slt"), string_t(""), string_t("operator.comparison_ordering"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_greater_than_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t(">"), string_t("greater_than"), string_t("greater_than"), string_t("semantics/operators_comparison_ordering/greater_than__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_greater_than_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_id(), __latency_fn_operation_readiness_contract_binary_greater_than_int_int_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_sgt_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_sgt_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_sgt_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_sgt_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("int_sgt"), string_t("sgt"), string_t("icmp sgt"), string_t(""), string_t("operator.comparison_ordering"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_less_than_or_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("<="), string_t("less_than_or_equal"), string_t("less_than_or_equal"), string_t("semantics/operators_comparison_ordering/less_than_or_equal__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_less_than_or_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_less_than_or_equal_id(), __latency_fn_operation_readiness_contract_binary_less_than_or_equal_int_int_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_sle_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_sle_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_sle_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_sle_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("int_sle"), string_t("sle"), string_t("icmp sle"), string_t(""), string_t("operator.comparison_ordering"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_greater_than_or_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t(">="), string_t("greater_than_or_equal"), string_t("greater_than_or_equal"), string_t("semantics/operators_comparison_ordering/greater_than_or_equal__int_t__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_greater_than_or_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_or_equal_id(), __latency_fn_operation_readiness_contract_binary_greater_than_or_equal_int_int_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_sge_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_sge_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_sge_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_sge_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("int_sge"), string_t("sge"), string_t("icmp sge"), string_t(""), string_t("operator.comparison_ordering"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_identical_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("==="), string_t("identical"), string_t("identical"), string_t("semantics/operators_strict_identity/identical__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_identical_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_identical_id(), __latency_fn_operation_readiness_contract_binary_identical_int_int_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_eq_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_eq_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_identical_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_identical_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("int_identical"), string_t("identical"), string_t("icmp eq"), string_t(""), string_t("operator.strict_identity"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_11);
	}
	{
	auto __latency_local_12 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_not_identical_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("!=="), string_t("not_identical"), string_t("not_identical"), string_t("semantics/operators_strict_identity/not_identical__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_not_identical_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_not_identical_id(), __latency_fn_operation_readiness_contract_binary_not_identical_int_int_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i64_ne_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_int_ne_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_not_identical_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_int_not_identical_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("int_not_identical"), string_t("not_identical"), string_t("icmp ne"), string_t(""), string_t("operator.strict_identity"), string_t("int_t.int_t"));
	(void) rows.push_back(__latency_local_12);
	}
	{
	auto __latency_local_13 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_logical_and_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("&&"), string_t("logical_and"), string_t("logical_and"), string_t("semantics/operators_binary_logical/logical_and__bool_t__bool_t.tsv"), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_bool_logical_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_bool_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_logical_and_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_logical_and_id(), __latency_fn_operation_readiness_contract_binary_logical_and_bool_bool_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i1_and_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_bool_and_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_and_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_and_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4)), string_t("bool_and"), string_t("logical_and"), string_t("and"), string_t(""), string_t("operator.binary_logical"), string_t("bool_t.bool_t"));
	(void) rows.push_back(__latency_local_13);
	}
	{
	auto __latency_local_14 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_logical_or_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("||"), string_t("logical_or"), string_t("logical_or"), string_t("semantics/operators_binary_logical/logical_or__bool_t__bool_t.tsv"), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_bool_logical_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_bool_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_logical_or_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_logical_or_id(), __latency_fn_operation_readiness_contract_binary_logical_or_bool_bool_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i1_or_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_bool_or_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_or_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_or_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3)), string_t("bool_or"), string_t("logical_or"), string_t("or"), string_t(""), string_t("operator.binary_logical"), string_t("bool_t.bool_t"));
	(void) rows.push_back(__latency_local_14);
	}
	{
	auto __latency_local_15 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_bool_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("=="), string_t("bool_equal"), string_t("equal"), string_t("semantics/operators_comparison_equality/equal__bool_t__bool_t.tsv"), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_bool_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_equal_id(), __latency_fn_operation_readiness_contract_binary_equal_bool_bool_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i1_eq_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_bool_eq_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_eq_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_eq_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("bool_eq"), string_t("bool_eq"), string_t("icmp eq"), string_t(""), string_t("operator.comparison_equality"), string_t("bool_t.bool_t"));
	(void) rows.push_back(__latency_local_15);
	}
	{
	auto __latency_local_16 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_bool_not_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("!="), string_t("bool_not_equal"), string_t("not_equal"), string_t("semantics/operators_comparison_equality/not_equal__bool_t__bool_t.tsv"), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_bool_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_not_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_not_equal_id(), __latency_fn_operation_readiness_contract_binary_not_equal_bool_bool_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i1_ne_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_bool_ne_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_ne_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_ne_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("bool_ne"), string_t("bool_ne"), string_t("icmp ne"), string_t(""), string_t("operator.comparison_equality"), string_t("bool_t.bool_t"));
	(void) rows.push_back(__latency_local_16);
	}
	{
	auto __latency_local_17 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_bool_identical_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("==="), string_t("bool_identical"), string_t("identical"), string_t("semantics/operators_strict_identity/identical__bool_t.tsv"), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_bool_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_identical_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_identical_id(), __latency_fn_operation_readiness_contract_binary_identical_bool_bool_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i1_eq_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_bool_eq_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_identical_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_identical_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("bool_identical"), string_t("bool_identical"), string_t("icmp eq"), string_t(""), string_t("operator.strict_identity"), string_t("bool_t.bool_t"));
	(void) rows.push_back(__latency_local_17);
	}
	{
	auto __latency_local_18 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_bool_not_identical_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("!=="), string_t("bool_not_identical"), string_t("not_identical"), string_t("semantics/operators_strict_identity/not_identical__bool_t.tsv"), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_bool_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_not_identical_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_not_identical_id(), __latency_fn_operation_readiness_contract_binary_not_identical_bool_bool_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_i1_ne_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_bool_ne_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_not_identical_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_bool_not_identical_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("bool_not_identical"), string_t("bool_not_identical"), string_t("icmp ne"), string_t(""), string_t("operator.strict_identity"), string_t("bool_t.bool_t"));
	(void) rows.push_back(__latency_local_18);
	}
	{
	auto __latency_local_19 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_float_plus_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("+"), string_t("float_plus"), string_t("add"), string_t("semantics/operators_binary_arithmetic/add__float_t__float_t.tsv"), __latency_fn_type_refs_float_id(), __latency_fn_type_refs_float_id(), __latency_fn_type_refs_float_id(), __latency_fn_type_capability_readiness_capability_numeric_binary_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_plus_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_plus_id(), __latency_fn_operation_readiness_contract_binary_plus_float_float_to_float_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_f32_add_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_float_add_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_float_add_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_float_add_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10)), string_t("float_add"), string_t("float_add"), string_t("fadd"), string_t(""), string_t("operator.binary_arithmetic"), string_t("float_t.float_t"));
	(void) rows.push_back(__latency_local_19);
	}
	{
	auto __latency_local_20 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_double_plus_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("+"), string_t("double_plus"), string_t("add"), string_t("compiler/docs/future/90_percent_functionality/01_scalar_foundations/float_double_provider_abi_lowering_gap_contract_2026_08_05.md:double_f64_arithmetic"), __latency_fn_type_refs_double_id(), __latency_fn_type_refs_double_id(), __latency_fn_type_refs_double_id(), __latency_fn_type_capability_readiness_capability_numeric_binary_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_plus_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_plus_id(), __latency_fn_operation_readiness_contract_binary_plus_double_double_to_double_id(), __latency_fn_operation_readiness_lowering_adapter_primitive_f64_add_id(), __latency_fn_backend_preflight_requests_lowering_step_kind_double_add_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_double_add_immediate_id(), __latency_fn_backend_preflight_requests_local_operation_loaded_double_add_store_immediate_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10)), string_t("double_add"), string_t("double_add"), string_t("fadd"), string_t(""), string_t("operator.binary_arithmetic"), string_t("double_t.double_t"));
	(void) rows.push_back(__latency_local_20);
	}
	{
	auto __latency_local_21 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_string_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("=="), string_t("string_equal"), string_t("equal"), string_t("semantics/operators_comparison_equality/equal__string_t__string_t.tsv"), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_string_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_equal_id(), __latency_fn_operation_readiness_contract_string_comparison_runtime_helper_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("runtime_string_compare_blocked"), string_t("string_eq"), string_t("icmp eq"), string_t(""), string_t("operator.comparison_equality"), string_t("string_t.string_t"));
	(void) rows.push_back(__latency_local_21);
	}
	{
	auto __latency_local_22 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_string_not_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("!="), string_t("string_not_equal"), string_t("not_equal"), string_t("semantics/operators_comparison_equality/not_equal__string_t__string_t.tsv"), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_string_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_not_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_not_equal_id(), __latency_fn_operation_readiness_contract_string_comparison_runtime_helper_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("runtime_string_compare_blocked"), string_t("string_ne"), string_t("icmp ne"), string_t(""), string_t("operator.comparison_equality"), string_t("string_t.string_t"));
	(void) rows.push_back(__latency_local_22);
	}
	{
	auto __latency_local_23 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_string_less_than_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("<"), string_t("string_less_than"), string_t("less_than"), string_t("semantics/operators_comparison_ordering/less_than__string_t__string_t.tsv"), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_string_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_less_than_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_less_than_id(), __latency_fn_operation_readiness_contract_string_comparison_runtime_helper_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("runtime_string_compare_blocked"), string_t("string_lt"), string_t("icmp slt"), string_t(""), string_t("operator.comparison_ordering"), string_t("string_t.string_t"));
	(void) rows.push_back(__latency_local_23);
	}
	{
	auto __latency_local_24 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_string_greater_than_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t(">"), string_t("string_greater_than"), string_t("greater_than"), string_t("semantics/operators_comparison_ordering/greater_than__string_t__string_t.tsv"), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_string_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_greater_than_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_id(), __latency_fn_operation_readiness_contract_string_comparison_runtime_helper_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("runtime_string_compare_blocked"), string_t("string_gt"), string_t("icmp sgt"), string_t(""), string_t("operator.comparison_ordering"), string_t("string_t.string_t"));
	(void) rows.push_back(__latency_local_24);
	}
	{
	auto __latency_local_25 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_string_less_than_or_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t("<="), string_t("string_less_than_or_equal"), string_t("less_than_or_equal"), string_t("semantics/operators_comparison_ordering/less_than_or_equal__string_t__string_t.tsv"), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_string_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_less_than_or_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_less_than_or_equal_id(), __latency_fn_operation_readiness_contract_string_comparison_runtime_helper_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("runtime_string_compare_blocked"), string_t("string_lte"), string_t("icmp sle"), string_t(""), string_t("operator.comparison_ordering"), string_t("string_t.string_t"));
	(void) rows.push_back(__latency_local_25);
	}
	{
	auto __latency_local_26 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_string_greater_than_or_equal_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), string_t(">="), string_t("string_greater_than_or_equal"), string_t("greater_than_or_equal"), string_t("semantics/operators_comparison_ordering/greater_than_or_equal__string_t__string_t.tsv"), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_string_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_string_scalar_id(), __latency_fn_type_capability_readiness_feature_binary_operator_greater_than_or_equal_id(), __latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_or_equal_id(), __latency_fn_operation_readiness_contract_string_comparison_runtime_helper_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), string_t("runtime_string_compare_blocked"), string_t("string_gte"), string_t("icmp sge"), string_t(""), string_t("operator.comparison_ordering"), string_t("string_t.string_t"));
	(void) rows.push_back(__latency_local_26);
	}
	return rows;
}

}
