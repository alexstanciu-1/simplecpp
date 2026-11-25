#include <scpp/lang/php.hpp>
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_all_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_lowering_step_kind.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lowering_adapter_id_for_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_local_immediate_operation_id_for_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_local_store_operation_id_for_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_precedence_for_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_all_operator_rows.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_local_operation_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_local_operation_is_immediate.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_local_operation_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_local_operation_is_store_immediate.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_local_operation_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row_by_feature_id(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::row_by_feature_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[46]);
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_all_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->feature_id), cast<int_t<>>(featureId)))) {
			return row;
		}
	}
	shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref(int_t<std::uint16_t> featureId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::row_by_feature_id_and_type_ref", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[47]);
	int_t<std::uint32_t> lookupTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId)));
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_binary_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->feature_id), cast<int_t<>>(featureId)) && php::identical(cast<int_t<>>(row->lhs_type_ref_id), cast<int_t<>>(lookupTypeRefId))) && php::identical(cast<int_t<>>(row->rhs_type_ref_id), cast<int_t<>>(lookupTypeRefId))))) {
			return row;
		}
	}
	shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_refs(int_t<std::uint16_t> featureId, int_t<std::uint32_t> lhsTypeRefId, int_t<std::uint32_t> rhsTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::row_by_feature_id_and_type_refs", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[48]);
	int_t<std::uint32_t> lookupLhsTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(cast<int_t<std::uint32_t>>(lhsTypeRefId)));
	int_t<std::uint32_t> lookupRhsTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(cast<int_t<std::uint32_t>>(rhsTypeRefId)));
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_binary_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->feature_id), cast<int_t<>>(featureId)) && php::identical(cast<int_t<>>(row->lhs_type_ref_id), cast<int_t<>>(lookupLhsTypeRefId))) && php::identical(cast<int_t<>>(row->rhs_type_ref_id), cast<int_t<>>(lookupRhsTypeRefId))))) {
			return row;
		}
	}
	shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row_by_feature_id_and_lowering_step_kind(int_t<std::uint16_t> featureId, int_t<std::uint16_t> loweringStepKindId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::row_by_feature_id_and_lowering_step_kind", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[49]);
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_binary_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->feature_id), cast<int_t<>>(featureId)) && php::identical(cast<int_t<>>(row->lowering_step_kind_id), cast<int_t<>>(loweringStepKindId))))) {
			return row;
		}
	}
	shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_operator_lookup_lowering_adapter_id_for_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::lowering_adapter_id_for_operator_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[50]);
	shared_p<SemanticOperatorLookupRow> row = __latency_fn_semantic_operator_lookup_row_by_operator_id(cast<int_t<std::uint16_t>>(operatorId));
	return cast<int_t<std::uint16_t>>(row->lowering_adapter_id);
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_operator_lookup_local_immediate_operation_id_for_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::local_immediate_operation_id_for_operator_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[51]);
	shared_p<SemanticOperatorLookupRow> row = __latency_fn_semantic_operator_lookup_row_by_operator_id(cast<int_t<std::uint16_t>>(operatorId));
	return cast<int_t<std::uint16_t>>(row->local_immediate_operation_id);
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_operator_lookup_local_store_operation_id_for_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::local_store_operation_id_for_operator_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[52]);
	shared_p<SemanticOperatorLookupRow> row = __latency_fn_semantic_operator_lookup_row_by_operator_id(cast<int_t<std::uint16_t>>(operatorId));
	return cast<int_t<std::uint16_t>>(row->local_store_operation_id);
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_operator_lookup_precedence_for_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::precedence_for_operator_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[53]);
	shared_p<SemanticOperatorLookupRow> row = __latency_fn_semantic_operator_lookup_row_by_operator_id(cast<int_t<std::uint16_t>>(operatorId));
	return cast<int_t<std::uint16_t>>(row->precedence);
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row_by_local_operation_id(int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::row_by_local_operation_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[54]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(localOperationId), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id())))) {
		shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
		return empty;
	}
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_all_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::not_identical(cast<int_t<>>(row->local_immediate_operation_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id())) && php::identical(cast<int_t<>>(row->local_immediate_operation_id), cast<int_t<>>(localOperationId))) || (php::not_identical(cast<int_t<>>(row->local_store_operation_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id())) && php::identical(cast<int_t<>>(row->local_store_operation_id), cast<int_t<>>(localOperationId)))))) {
			return row;
		}
	}
	shared_p<SemanticOperatorLookupRow> empty = create<SemanticOperatorLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
bool_t __latency_fn_semantic_operator_lookup_local_operation_is_immediate(int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::local_operation_is_immediate", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[55]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(localOperationId), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id())))) {
		return bool_t(static_cast<bool_t>(false));
	}
	shared_p<SemanticOperatorLookupRow> row = __latency_fn_semantic_operator_lookup_row_by_local_operation_id(cast<int_t<std::uint16_t>>(localOperationId));
	return ((cast<int_t<>>(row->operator_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(row->local_immediate_operation_id), cast<int_t<>>(localOperationId)));
}

}

namespace scpp { extern const int __latency_lines_semantic_operator_lookup[]; }
namespace scpp {
bool_t __latency_fn_semantic_operator_lookup_local_operation_is_store_immediate(int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("semantic_operator_lookup::local_operation_is_store_immediate", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_operator_lookup.phs", __latency_lines_semantic_operator_lookup[56]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(localOperationId), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id())))) {
		return bool_t(static_cast<bool_t>(false));
	}
	shared_p<SemanticOperatorLookupRow> row = __latency_fn_semantic_operator_lookup_row_by_local_operation_id(cast<int_t<std::uint16_t>>(localOperationId));
	return ((cast<int_t<>>(row->operator_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(row->local_store_operation_id), cast<int_t<>>(localOperationId)));
}

}
