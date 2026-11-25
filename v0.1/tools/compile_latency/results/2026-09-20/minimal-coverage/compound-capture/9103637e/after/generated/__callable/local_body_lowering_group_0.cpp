#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__types/local_body_lowering.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_text.hpp"
#include "__callable/__latency_fn_local_body_lowering_variable_name_text.hpp"
#include "__callable/__latency_fn_local_body_lowering_operation_from_capability_source.hpp"
#include "__callable/__latency_fn_operation_readiness_readiness_from_capability.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_by_feature_and_source_row_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_feature_id_for_binary_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_feature_id_for_operator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_local_operation_id_for_binary_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_local_immediate_operation_id_for_operator_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_local_store_operation_id_for_binary_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_local_store_operation_id_for_operator_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_local_source_row_id_for_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_local_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_typed_local_source_if_missing.hpp"
#include "__callable/__latency_fn_local_body_lowering_local_source_row_id_for_name.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_typed_source_row_id_if_missing.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_source_text_for_node.hpp"
#include "__callable/__latency_fn_local_body_lowering_type_ref_uses_inline_scalar_storage.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_storage_policy_inline_scalar_id.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_backend_request.hpp"
#include "__callable/__latency_fn_local_body_lowering_append_local_backend_request_with_value_text.hpp"
namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
bool_t local_body_lowering::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == local_body_lowering::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
string_t __latency_fn_local_body_lowering_variable_name_text(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow variableNode) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::variable_name_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[0]);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	return __latency_fn_frontend_body_summaries_variable_name_text(model, sourceText, variableNode, counters);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
OperationReadiness __latency_fn_local_body_lowering_operation_from_capability_source(shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint16_t> featureId, int_t<std::uint32_t> sourceRowId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::operation_from_capability_source", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[1]);
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_consumer_by_feature_and_source_row_id(capabilityCoverage, featureId, sourceRowId);
	CapabilityReadinessRow readiness = __latency_fn_type_capability_readiness_readiness_by_feature_and_source_row_id(capabilityCoverage, featureId, sourceRowId);
	return __latency_fn_operation_readiness_readiness_from_capability(consumer, readiness);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_local_body_lowering_feature_id_for_binary_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::feature_id_for_binary_operator_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[2]);
	int_t<std::uint16_t> featureId = required_cast<int_t<std::uint16_t>>(__latency_fn_semantic_operator_lookup_feature_id_for_operator_id(operatorId));
	if (static_cast<bool>((cast<int_t<>>(featureId) > static_cast<int_t<> >(0)))) {
		return cast<int_t<std::uint16_t>>(featureId);
	}
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_local_body_lowering_local_operation_id_for_binary_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::local_operation_id_for_binary_operator_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[3]);
	return __latency_fn_semantic_operator_lookup_local_immediate_operation_id_for_operator_id(operatorId);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_local_body_lowering_local_store_operation_id_for_binary_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::local_store_operation_id_for_binary_operator_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[4]);
	return __latency_fn_semantic_operator_lookup_local_store_operation_id_for_operator_id(operatorId);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_local_body_lowering_local_source_row_id_for_name(const vector_t<string_t>& localNames, const vector_t<int_t<std::uint32_t>>& localSourceRowIds, const string_t& name) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::local_source_row_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[5]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((index < php::count(localNames)) && (index < php::count(localSourceRowIds))))) {
		if (static_cast<bool>(php::identical(localNames.at(index), name))) {
			return cast<int_t<std::uint32_t>>(localSourceRowIds.at(index));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_local_body_lowering_local_type_ref_id_for_name(const vector_t<string_t>& localNames, const vector_t<int_t<std::uint32_t>>& localTypeRefIds, const string_t& name) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::local_type_ref_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[6]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((index < php::count(localNames)) && (index < php::count(localTypeRefIds))))) {
		if (static_cast<bool>(php::identical(localNames.at(index), name))) {
			return cast<int_t<std::uint32_t>>(localTypeRefIds.at(index));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_typed_local_source_if_missing(vector_t<string_t>& localNames, vector_t<int_t<std::uint32_t>>& localSourceRowIds, vector_t<int_t<std::uint32_t>>& localTypeRefIds, const string_t& name, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_typed_local_source_if_missing", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[7]);
	if (static_cast<bool>(((php::identical(name, string_t("")) || php::identical(cast<int_t<>>(localSourceRowId), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(typeRefId), static_cast<int_t<> >(0))))) {
		return;
	}
	if (static_cast<bool>((cast<int_t<>>(__latency_fn_local_body_lowering_local_source_row_id_for_name(localNames, localSourceRowIds, name)) > static_cast<int_t<> >(0)))) {
		return;
	}
	(void) localNames.push_back(name);
	(void) localSourceRowIds.push_back(localSourceRowId);
	(void) localTypeRefIds.push_back(typeRefId);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_typed_source_row_id_if_missing(vector_t<int_t<std::uint32_t>>& sourceRowIds, vector_t<int_t<std::uint32_t>>& typeRefIds, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_typed_source_row_id_if_missing", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[8]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(sourceRowId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(typeRefId), static_cast<int_t<> >(0))))) {
		return;
	}
	auto& __latency_local_0 = sourceRowIds;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existingSourceRowId = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(existingSourceRowId), cast<int_t<>>(sourceRowId)))) {
			return;
		}
	}
	(void) sourceRowIds.push_back(sourceRowId);
	(void) typeRefIds.push_back(typeRefId);
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
string_t __latency_fn_local_body_lowering_source_text_for_node(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow node) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::source_text_for_node", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[9]);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	SourceRangeRow range = __latency_fn_frontend_model_tables_source_range_by_id(model, node->source_range_id, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(range->source_range_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return str::byte_slice(sourceText, cast<int_t<>>(range->start_offset), cast<int_t<>>(range->length));
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
bool_t __latency_fn_local_body_lowering_type_ref_uses_inline_scalar_storage(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::type_ref_uses_inline_scalar_storage", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[10]);
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	return bool_t(php::identical(cast<int_t<>>(trait->storage_policy_id), cast<int_t<>>(__latency_fn_type_traits_storage_policy_inline_scalar_id())));
}

}

namespace scpp { extern const int __latency_lines_local_body_lowering[]; }
namespace scpp {
void __latency_fn_local_body_lowering_append_local_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint16_t> featureId, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::int32_t> value, int_t<std::uint16_t> localOperationId, ProjectSymbolIndexRow entrySymbol) {
	SCPP_CALL_DEPTH_GUARD("local_body_lowering::append_local_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/local_body_lowering.phs", __latency_lines_local_body_lowering[11]);
	__latency_fn_local_body_lowering_append_local_backend_request_with_value_text(backendRequests, capabilityCoverage, cast<int_t<std::uint16_t>>(featureId), cast<int_t<std::uint32_t>>(sourceRowId), cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(valueSourceRowId), cast<int_t<std::int32_t>>(value), string_t(""), cast<int_t<std::uint16_t>>(localOperationId), entrySymbol);
}

}
