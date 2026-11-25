#include <scpp/lang/php.hpp>
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_unary_logical_not_bool_to_bool_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_unary_minus_int_to_int_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_none_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_unary_operator_logical_not_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_unary_operator_minus_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operand_category_bool_scalar_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_logical_not_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_unary_minus_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_unary_operator_rows.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_bool_logical_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_numeric_unary_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_unary_operator_logical_not_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_unary_operator_minus_id.hpp"
#include "__callable/__latency_fn_type_refs_bool_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_all_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_unary_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_int_int_binary_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_all_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_int32_id.hpp"
#include "__callable/__latency_fn_type_refs_int64_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_symbol_and_type_refs.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_id_for_symbol_and_type_refs.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_symbol_and_type_refs.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_unary_symbol_and_type_ref.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_unary_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_operator_id_for_unary_symbol_and_type_ref.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_unary_symbol_and_type_ref.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_feature_id_for_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
vector_t<shared_p<SemanticOperatorLookupRow>> __latency_fn_semantic_operator_lookup_unary_operator_rows() {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::unary_operator_rows", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[36]);
	vector_t<shared_p<SemanticOperatorLookupRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(2));
	{
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_unary_minus_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), string_t("-"), string_t("unary_minus"), string_t("unary_minus"), string_t("semantics/operators_unary/unary_minus__int_t.tsv"), __latency_fn_type_refs_int_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_capability_readiness_capability_numeric_unary_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_numeric_scalar_id(), __latency_fn_type_capability_readiness_feature_unary_operator_minus_id(), __latency_fn_operation_readiness_operation_kind_unary_operator_minus_id(), __latency_fn_operation_readiness_contract_unary_minus_int_to_int_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(30)), string_t("int_unary_minus_literal_normalized"), string_t("unary_minus"), string_t(""), string_t(""), string_t("operator.unary"), string_t("int_t"));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_semantic_operator_lookup_row(__latency_fn_semantic_operator_lookup_operator_logical_not_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), string_t("!"), string_t("logical_not"), string_t("logical_not"), string_t("semantics/operators_unary/logical_not__bool_t.tsv"), __latency_fn_type_refs_bool_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_capability_readiness_capability_bool_logical_operator_id(), __latency_fn_semantic_operator_lookup_operand_category_bool_scalar_id(), __latency_fn_type_capability_readiness_feature_unary_operator_logical_not_id(), __latency_fn_operation_readiness_operation_kind_unary_operator_logical_not_id(), __latency_fn_operation_readiness_contract_unary_logical_not_bool_to_bool_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(30)), string_t("bool_logical_not_literal_normalized"), string_t("logical_not"), string_t(""), string_t(""), string_t("operator.unary"), string_t("bool_t"));
	(void) rows.push_back(__latency_local_1);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
vector_t<shared_p<SemanticOperatorLookupRow>> __latency_fn_semantic_operator_lookup_all_operator_rows() {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::all_operator_rows", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[37]);
	vector_t<shared_p<SemanticOperatorLookupRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(29));
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_binary_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		(void) rows.push_back(row);
	}
	auto __latency_local_2 = __latency_fn_semantic_operator_lookup_unary_operator_rows();
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto row = __latency_local_3.value_copy();
		(void) rows.push_back(row);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
vector_t<shared_p<SemanticOperatorLookupRow>> __latency_fn_semantic_operator_lookup_int_int_binary_rows() {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::int_int_binary_rows", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[38]);
	return __latency_fn_semantic_operator_lookup_binary_operator_rows();
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row_by_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::row_by_operator_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[39]);
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_all_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->operator_id), cast<int_t<>>(operatorId)))) {
			return row;
		}
	}
	shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_semantic_operator_lookup_lookup_type_ref_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::lookup_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[40]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_int64_id())) || php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_int32_id()))))) {
		return __latency_fn_type_refs_int_id();
	}
	return cast<int_t<std::uint32_t>>(typeRefId);
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row_by_symbol_and_type_refs(const string_t& operatorSymbol, int_t<std::uint32_t> lhsTypeRefId, int_t<std::uint32_t> rhsTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::row_by_symbol_and_type_refs", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[41]);
	int_t<std::uint32_t> lookupLhsTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(cast<int_t<std::uint32_t>>(lhsTypeRefId)));
	int_t<std::uint32_t> lookupRhsTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(cast<int_t<std::uint32_t>>(rhsTypeRefId)));
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_binary_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(row->operator_symbol, operatorSymbol) && php::identical(cast<int_t<>>(row->lhs_type_ref_id), cast<int_t<>>(lookupLhsTypeRefId))) && php::identical(cast<int_t<>>(row->rhs_type_ref_id), cast<int_t<>>(lookupRhsTypeRefId))))) {
			return row;
		}
	}
	shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_operator_lookup_operator_id_for_symbol_and_type_refs(const string_t& operatorSymbol, int_t<std::uint32_t> lhsTypeRefId, int_t<std::uint32_t> rhsTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::operator_id_for_symbol_and_type_refs", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[42]);
	shared_p<SemanticOperatorLookupRow> row = __latency_fn_semantic_operator_lookup_row_by_symbol_and_type_refs(operatorSymbol, cast<int_t<std::uint32_t>>(lhsTypeRefId), cast<int_t<std::uint32_t>>(rhsTypeRefId));
	return cast<int_t<std::uint16_t>>(row->operator_id);
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row_by_unary_symbol_and_type_ref(const string_t& operatorSymbol, int_t<std::uint32_t> operandTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::row_by_unary_symbol_and_type_ref", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[43]);
	int_t<std::uint32_t> lookupTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(cast<int_t<std::uint32_t>>(operandTypeRefId)));
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_unary_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(row->operator_symbol, operatorSymbol) && php::identical(cast<int_t<>>(row->lhs_type_ref_id), cast<int_t<>>(lookupTypeRefId))))) {
			return row;
		}
	}
	shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_operator_lookup_operator_id_for_unary_symbol_and_type_ref(const string_t& operatorSymbol, int_t<std::uint32_t> operandTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::operator_id_for_unary_symbol_and_type_ref", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[44]);
	shared_p<SemanticOperatorLookupRow> row = __latency_fn_semantic_operator_lookup_row_by_unary_symbol_and_type_ref(operatorSymbol, cast<int_t<std::uint32_t>>(operandTypeRefId));
	return cast<int_t<std::uint16_t>>(row->operator_id);
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_operator_lookup_feature_id_for_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::feature_id_for_operator_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[45]);
	shared_p<SemanticOperatorLookupRow> row = __latency_fn_semantic_operator_lookup_row_by_operator_id(cast<int_t<std::uint16_t>>(operatorId));
	return cast<int_t<std::uint16_t>>(row->feature_id);
}

}
