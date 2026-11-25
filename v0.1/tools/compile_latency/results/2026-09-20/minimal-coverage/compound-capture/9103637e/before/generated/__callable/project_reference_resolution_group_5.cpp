#include <scpp/lang/php.hpp>
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields_with_first_argument_type.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields_with_first_argument_type.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name_is_qualified.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_callee_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_from_source_unit_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_from_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_resolved_source_unit_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_resolved_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_normalized_qualified_callee_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_direct_function_symbol_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_multiple_matching_symbols_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolution_kind_no_matching_symbol_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_ambiguous_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_missing_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_unresolved_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_name_match_count_by_name_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_qualified_name_match_count_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_symbol_by_name_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_symbol_by_qualified_name_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_name_id_by_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_namespace_function_import_target_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_namespace_relative_function_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_qualified_name_id_by_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_lookup_probe.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_unit_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_key.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_direct_call_from_call_expression_source_range.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name_from_source_range.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_symbol_body_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_publication_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
ProjectReferenceResolutionRow __latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields(shared_p<ProjectReferenceResolution> artifact, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow fromSymbol, int_t<std::uint32_t> callExpressionNodeId, const string_t& calleeName, int_t<std::uint32_t> actualArgCount) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_direct_call_from_call_expression_fields", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[55]);
	return __latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields_with_first_argument_type(artifact, symbols, fromSymbol, cast<int_t<std::uint32_t>>(callExpressionNodeId), calleeName, cast<int_t<std::uint32_t>>(actualArgCount), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_kind_id());
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
ProjectReferenceResolutionRow __latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields_with_first_argument_type(shared_p<ProjectReferenceResolution> artifact, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow fromSymbol, int_t<std::uint32_t> callExpressionNodeId, const string_t& calleeName, int_t<std::uint32_t> actualArgCount, int_t<std::uint32_t> actualFirstArgumentTypeRefId, int_t<std::uint16_t> actualFirstArgumentStatusId) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_direct_call_from_call_expression_fields_with_first_argument_type", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[56]);
	ProjectReferenceResolutionRow row = ProjectReferenceResolutionRow{};
	row->reference_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->rows));
	row->from_symbol_id = fromSymbol->symbol_id;
	row->from_source_unit_id = fromSymbol->source_unit_id;
	row->call_expression_node_id = callExpressionNodeId;
	string_t fromSymbolKey = required_cast<string_t>(__latency_fn_project_symbol_index_symbol_key(symbols, fromSymbol));
	string_t fromSourceUnitKey = required_cast<string_t>(__latency_fn_project_symbol_index_source_unit_key(symbols, fromSymbol));
	row->from_symbol_key_id = __latency_fn_project_reference_resolution_intern_from_symbol_key(artifact, fromSymbolKey);
	row->from_source_unit_key_id = __latency_fn_project_reference_resolution_intern_from_source_unit_key(artifact, fromSourceUnitKey);
	row->callee_name_id = __latency_fn_project_reference_resolution_intern_callee_name(artifact, calleeName);
	row->actual_arg_count = actualArgCount;
	row->actual_first_argument_type_ref_id = actualFirstArgumentTypeRefId;
	row->actual_first_argument_status_id = actualFirstArgumentStatusId;
	if (static_cast<bool>((php::identical(cast<int_t<>>(callExpressionNodeId), static_cast<int_t<> >(0)) || php::identical(calleeName, string_t(""))))) {
		row->resolution_kind_id = __latency_fn_project_reference_resolution_resolution_kind_no_call_expression_id();
		row->status_id = __latency_fn_project_reference_resolution_status_unresolved_id();
		artifact->unresolved_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->unresolved_count) + static_cast<int_t<> >(1)));
		(void) artifact->rows.append(row);
		artifact->reference_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
		return row;
	}
	ProjectSymbolIndexRow symbol = ProjectSymbolIndexRow{};
	__latency_fn_project_symbol_index_record_lookup_probe(symbols, __latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>(php::condition_truthy(__latency_fn_project_reference_resolution_callee_name_is_qualified(calleeName)))) {
		string_t qualifiedName = required_cast<string_t>(__latency_fn_project_reference_resolution_normalized_qualified_callee_name(calleeName));
		int_t<std::uint32_t> qualifiedNameId = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_qualified_name_id_by_text(symbols, qualifiedName));
		row->match_count = __latency_fn_project_symbol_index_function_qualified_name_match_count_by_id(symbols, qualifiedNameId);
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->match_count), static_cast<int_t<> >(1)))) {
			symbol = __latency_fn_project_symbol_index_function_symbol_by_qualified_name_id(symbols, qualifiedNameId);
		}
	}
	else {
		bool_t usedNamespaceRelativeLookup = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
		bool_t usedImportLookup = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
		string_t relativeQualifiedName = required_cast<string_t>(__latency_fn_project_symbol_index_namespace_relative_function_qualified_name(symbols, fromSymbol, calleeName));
		if (static_cast<bool>(php::condition_truthy(php::not_identical(relativeQualifiedName, string_t(""))))) {
			int_t<std::uint32_t> relativeQualifiedNameId = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_qualified_name_id_by_text(symbols, relativeQualifiedName));
			int_t<std::uint32_t> relativeMatchCount = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_function_qualified_name_match_count_by_id(symbols, relativeQualifiedNameId));
			if (static_cast<bool>((cast<int_t<>>(relativeMatchCount) > static_cast<int_t<> >(0)))) {
				usedNamespaceRelativeLookup = bool_t(static_cast<bool_t>(true));
				row->match_count = relativeMatchCount;
				if (static_cast<bool>(php::identical(cast<int_t<>>(row->match_count), static_cast<int_t<> >(1)))) {
					symbol = __latency_fn_project_symbol_index_function_symbol_by_qualified_name_id(symbols, relativeQualifiedNameId);
				}
			}
		}
		if (static_cast<bool>((!usedNamespaceRelativeLookup))) {
			string_t importQualifiedName = required_cast<string_t>(__latency_fn_project_symbol_index_namespace_function_import_target_qualified_name(symbols, fromSymbol, calleeName));
			if (static_cast<bool>(php::condition_truthy(php::not_identical(importQualifiedName, string_t(""))))) {
				int_t<std::uint32_t> importQualifiedNameId = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_qualified_name_id_by_text(symbols, importQualifiedName));
				int_t<std::uint32_t> importMatchCount = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_function_qualified_name_match_count_by_id(symbols, importQualifiedNameId));
				if (static_cast<bool>((cast<int_t<>>(importMatchCount) > static_cast<int_t<> >(0)))) {
					usedImportLookup = bool_t(static_cast<bool_t>(true));
					row->match_count = importMatchCount;
					if (static_cast<bool>(php::identical(cast<int_t<>>(row->match_count), static_cast<int_t<> >(1)))) {
						symbol = __latency_fn_project_symbol_index_function_symbol_by_qualified_name_id(symbols, importQualifiedNameId);
					}
				}
			}
		}
		if (static_cast<bool>(((!usedNamespaceRelativeLookup) && (!usedImportLookup)))) {
			int_t<std::uint32_t> calleeNameId = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_name_id_by_text(symbols, calleeName));
			row->match_count = __latency_fn_project_symbol_index_function_name_match_count_by_name_id(symbols, calleeNameId);
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->match_count), static_cast<int_t<> >(1)))) {
				symbol = __latency_fn_project_symbol_index_function_symbol_by_name_id(symbols, calleeNameId);
			}
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->match_count), static_cast<int_t<> >(1)))) {
		if (static_cast<bool>((cast<int_t<>>(symbol->symbol_id) > static_cast<int_t<> >(0)))) {
			row->resolved_symbol_id = symbol->symbol_id;
			row->resolved_source_unit_id = symbol->source_unit_id;
			row->resolved_symbol_key_id = __latency_fn_project_reference_resolution_intern_resolved_symbol_key(artifact, __latency_fn_project_symbol_index_symbol_key(symbols, symbol));
			row->resolved_source_unit_key_id = __latency_fn_project_reference_resolution_intern_resolved_source_unit_key(artifact, __latency_fn_project_symbol_index_source_unit_key(symbols, symbol));
		}
		else {
			row->match_count = __latency_fn_structure_row_ids_none_id();
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->match_count), static_cast<int_t<> >(1)))) {
		row->resolution_kind_id = __latency_fn_project_reference_resolution_resolution_kind_direct_function_symbol_id();
		row->status_id = __latency_fn_project_reference_resolution_status_resolved_id();
		artifact->resolved_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->resolved_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->match_count), static_cast<int_t<> >(0)))) {
			row->resolution_kind_id = __latency_fn_project_reference_resolution_resolution_kind_no_matching_symbol_id();
			row->status_id = __latency_fn_project_reference_resolution_status_missing_id();
			artifact->missing_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->missing_count) + static_cast<int_t<> >(1)));
		}
		else {
			row->resolved_symbol_id = __latency_fn_structure_row_ids_none_id();
			row->resolved_source_unit_id = __latency_fn_structure_row_ids_none_id();
			row->resolved_symbol_key_id = __latency_fn_structure_row_ids_none_id();
			row->resolved_source_unit_key_id = __latency_fn_structure_row_ids_none_id();
			row->resolution_kind_id = __latency_fn_project_reference_resolution_resolution_kind_multiple_matching_symbols_id();
			row->status_id = __latency_fn_project_reference_resolution_status_ambiguous_id();
			artifact->ambiguous_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ambiguous_count) + static_cast<int_t<> >(1)));
		}
	}
	(void) artifact->rows.append(row);
	artifact->reference_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
ProjectReferenceResolutionRow __latency_fn_project_reference_resolution_append_direct_call_from_call_expression_source_range(shared_p<ProjectReferenceResolution> artifact, shared_p<ProjectSymbolIndex> symbols, const string_t& sourceText, ProjectSymbolIndexRow fromSymbol, int_t<std::uint32_t> callExpressionNodeId, int_t<std::uint32_t> calleeStartOffset, int_t<std::uint32_t> calleeLength, int_t<std::uint32_t> actualArgCount) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_direct_call_from_call_expression_source_range", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[57]);
	string_t calleeName = required_cast<string_t>(__latency_fn_project_reference_resolution_callee_name_from_source_range(sourceText, cast<int_t<std::uint32_t>>(calleeStartOffset), cast<int_t<std::uint32_t>>(calleeLength)));
	return __latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields(artifact, symbols, fromSymbol, cast<int_t<std::uint32_t>>(callExpressionNodeId), calleeName, cast<int_t<std::uint32_t>>(actualArgCount));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_project_reference_resolution_reference_contract_publication_row(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[58]);
	int_t<std::uint32_t> publishedRowCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(((cast<int_t<>>(references->reference_count) + php::count(references->actual_arguments)) + cast<int_t<>>(contracts->contract_count))));
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(php::identical(cast<int_t<>>(publishedRowCount), static_cast<int_t<> >(0)))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id();
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_uint32_from_int((static_cast<int_t<> >(9000) + cast<int_t<>>(entrySymbol->symbol_id))), ownerRunId, __latency_fn_partition_readiness_owner_kind_symbol_body_id(), entrySymbol->source_unit_id, entrySymbol->symbol_id, entrySymbol->symbol_id, statusId, blockedReasonId);
	row->input_snapshot_generation = ownerRunId;
	row->local_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->local_row_count = publishedRowCount;
	row->merge_order_key = __latency_fn_structure_row_ids_uint64_from_int((static_cast<int_t<> >(6200000) + cast<int_t<>>(entrySymbol->symbol_id)));
	row->published_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->published_row_count = publishedRowCount;
	return row;
}

}
