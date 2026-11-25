#include <scpp/lang/php.hpp>
#include "__types/ScalarBodyBackendCollection.hpp"
#include "__types/TypeRefTable.hpp"
#include "__types/scalar_body_backend_collection.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_new_collection.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_local_count.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_assignment_count.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_binary_count.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_local_source_row_id_for_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_typed_local_source_if_missing.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_local_source_row_id_for_name.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_literal_assignment.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_binary_assignment.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_literal_assignment.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_type_refs_to_table.hpp"
#include "__callable/__latency_fn_type_refs_add_type.hpp"
#include "__callable/__latency_fn_scalar_body_backend_collection_append_coverage_assignment_sources.hpp"
namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
bool_t scalar_body_backend_collection::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == scalar_body_backend_collection::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
shared_p<ScalarBodyBackendCollection> __latency_fn_scalar_body_backend_collection_new_collection() {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::new_collection", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[0]);
	return create<ScalarBodyBackendCollection>();
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
int_t<> __latency_fn_scalar_body_backend_collection_local_count(shared_p<ScalarBodyBackendCollection> body) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::local_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[1]);
	return php::count(body->local_source_row_ids);
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
int_t<> __latency_fn_scalar_body_backend_collection_assignment_count(shared_p<ScalarBodyBackendCollection> body) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::assignment_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[2]);
	return php::count(body->assignment_source_row_ids);
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
int_t<> __latency_fn_scalar_body_backend_collection_binary_count(shared_p<ScalarBodyBackendCollection> body) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::binary_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[3]);
	return php::count(body->binary_source_row_ids);
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_scalar_body_backend_collection_local_source_row_id_for_name(shared_p<ScalarBodyBackendCollection> body, const string_t& name) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::local_source_row_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[4]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((index < php::count(body->local_names)) && (index < php::count(body->local_source_row_ids))))) {
		if (static_cast<bool>(php::identical(body->local_names.at(index), name))) {
			return cast<int_t<std::uint32_t>>(body->local_source_row_ids.at(index));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_scalar_body_backend_collection_local_type_ref_id_for_name(shared_p<ScalarBodyBackendCollection> body, const string_t& name) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::local_type_ref_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[5]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((index < php::count(body->local_names)) && (index < php::count(body->local_type_ref_ids))))) {
		if (static_cast<bool>(php::identical(body->local_names.at(index), name))) {
			return cast<int_t<std::uint32_t>>(body->local_type_ref_ids.at(index));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
void __latency_fn_scalar_body_backend_collection_append_typed_local_source_if_missing(shared_p<ScalarBodyBackendCollection>& body, const string_t& name, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::append_typed_local_source_if_missing", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[6]);
	if (static_cast<bool>(((php::identical(name, string_t("")) || php::identical(cast<int_t<>>(sourceRowId), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(typeRefId), static_cast<int_t<> >(0))))) {
		return;
	}
	if (static_cast<bool>((cast<int_t<>>(__latency_fn_scalar_body_backend_collection_local_source_row_id_for_name(body, name)) > static_cast<int_t<> >(0)))) {
		return;
	}
	(void) body->local_names.push_back(name);
	(void) body->local_source_row_ids.push_back(sourceRowId);
	(void) body->local_type_ref_ids.push_back(typeRefId);
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
void __latency_fn_scalar_body_backend_collection_append_literal_assignment(shared_p<ScalarBodyBackendCollection>& body, int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> targetTypeRefId, int_t<std::uint32_t> targetLocalSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::int32_t> value, const string_t& valueText, int_t<std::uint16_t> operationId) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::append_literal_assignment", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[7]);
	(void) body->assignment_source_row_ids.push_back(statementSourceRowId);
	(void) body->assignment_type_ref_ids.push_back(targetTypeRefId);
	(void) body->assignment_target_local_source_row_ids.push_back(targetLocalSourceRowId);
	(void) body->assignment_value_source_row_ids.push_back(valueSourceRowId);
	(void) body->assignment_values.push_back(value);
	(void) body->assignment_value_texts.push_back(valueText);
	(void) body->assignment_local_operation_ids.push_back(operationId);
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
void __latency_fn_scalar_body_backend_collection_append_binary_assignment(shared_p<ScalarBodyBackendCollection>& body, int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> targetTypeRefId, int_t<std::uint32_t> targetLocalSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::int32_t> value, int_t<std::uint16_t> operationId, int_t<std::uint32_t> binarySourceRowId, int_t<std::uint16_t> binaryFeatureId, int_t<std::uint32_t> binaryProviderTypeRefId, int_t<std::uint32_t> binaryResultTypeRefId, int_t<std::uint32_t> binaryLeftLocalSourceRowId, int_t<std::uint32_t> binaryRightSourceRowId, int_t<std::int32_t> binaryRightValue) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::append_binary_assignment", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[8]);
	__latency_fn_scalar_body_backend_collection_append_literal_assignment(body, cast<int_t<std::uint32_t>>(statementSourceRowId), cast<int_t<std::uint32_t>>(targetTypeRefId), cast<int_t<std::uint32_t>>(targetLocalSourceRowId), cast<int_t<std::uint32_t>>(valueSourceRowId), cast<int_t<std::int32_t>>(value), string_t(""), cast<int_t<std::uint16_t>>(operationId));
	(void) body->binary_source_row_ids.push_back(binarySourceRowId);
	(void) body->binary_feature_ids.push_back(binaryFeatureId);
	(void) body->binary_provider_type_ref_ids.push_back(binaryProviderTypeRefId);
	(void) body->binary_result_type_ref_ids.push_back(binaryResultTypeRefId);
	(void) body->binary_left_local_source_row_ids.push_back(binaryLeftLocalSourceRowId);
	(void) body->binary_right_source_row_ids.push_back(binaryRightSourceRowId);
	(void) body->binary_right_values.push_back(binaryRightValue);
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
void __latency_fn_scalar_body_backend_collection_append_type_refs_to_table(TypeRefTable& typeRefs, shared_p<ScalarBodyBackendCollection> body) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::append_type_refs_to_table", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[9]);
	auto __latency_local_0 = body->local_type_ref_ids;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRefId = __latency_local_1.value_copy();
		__latency_fn_type_refs_add_type(typeRefs, typeRefId);
	}
	auto __latency_local_2 = body->assignment_type_ref_ids;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto typeRefId = __latency_local_3.value_copy();
		__latency_fn_type_refs_add_type(typeRefs, typeRefId);
	}
	auto __latency_local_4 = body->binary_provider_type_ref_ids;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto typeRefId = __latency_local_5.value_copy();
		__latency_fn_type_refs_add_type(typeRefs, typeRefId);
	}
	auto __latency_local_6 = body->binary_result_type_ref_ids;
	for (auto __latency_local_7 : foreach_range(__latency_local_6)) {
		auto typeRefId = __latency_local_7.value_copy();
		__latency_fn_type_refs_add_type(typeRefs, typeRefId);
	}
}

}

namespace scpp { extern const int __latency_lines_scalar_body_backend_collection[]; }
namespace scpp {
void __latency_fn_scalar_body_backend_collection_append_coverage_assignment_sources(shared_p<ScalarBodyBackendCollection> body, vector_t<int_t<std::uint32_t>>& sourceRowIds, vector_t<int_t<std::uint32_t>>& typeRefIds) {
	SCPP_CALL_DEPTH_GUARD("scalar_body_backend_collection::append_coverage_assignment_sources", "/tmp/scpp-edit-latency-20260919/app/compile/backend/scalar_body_backend_collection.phs", __latency_lines_scalar_body_backend_collection[10]);
	int_t<> localIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((localIndex < php::count(body->local_source_row_ids)) && (localIndex < php::count(body->local_type_ref_ids))))) {
		{
		auto __latency_local_0 = body->local_source_row_ids.at(localIndex);
		(void) sourceRowIds.push_back(__latency_local_0);
		}
		{
		auto __latency_local_1 = body->local_type_ref_ids.at(localIndex);
		(void) typeRefIds.push_back(__latency_local_1);
		}
		localIndex = (localIndex + static_cast<int_t<> >(1));
	}
	int_t<> assignmentIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((assignmentIndex < php::count(body->assignment_source_row_ids)) && (assignmentIndex < php::count(body->assignment_type_ref_ids))))) {
		{
		auto __latency_local_2 = body->assignment_source_row_ids.at(assignmentIndex);
		(void) sourceRowIds.push_back(__latency_local_2);
		}
		{
		auto __latency_local_3 = body->assignment_type_ref_ids.at(assignmentIndex);
		(void) typeRefIds.push_back(__latency_local_3);
		}
		assignmentIndex = (assignmentIndex + static_cast<int_t<> >(1));
	}
}

}
