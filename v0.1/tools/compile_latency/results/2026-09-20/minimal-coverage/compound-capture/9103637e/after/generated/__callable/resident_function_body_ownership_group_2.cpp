#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ProjectFrontendModel.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentSourceUnitFrontendStateRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_built_current_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_from_frontend_state.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_symbol_has_body_owner_metadata.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_all_symbols_have_body_owner_metadata.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_symbol_has_body_owner_metadata.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_row_from_symbol_metadata.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_from_frontend_state.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_status_current_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_body_end_offset_from_siblings.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_body_shape_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_export_shape_key.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_resident_function_body_ownership_full_model_fallback_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_resident_function_body_ownership_symbol_metadata_selected.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_body_end_offset_from_siblings.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_frontend_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_frontend_state_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_model_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_row_from_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_row_from_symbol_metadata.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_from_frontend_state.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_status_current_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_symbol_has_body_owner_metadata.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_append_snapshot.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_ownership_snapshot_kind_from_frontend_state(ResidentSourceUnitFrontendStateRow state) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::snapshot_kind_from_frontend_state", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[20]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(state->state_kind_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id())))) {
		return __latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id();
	}
	return __latency_fn_resident_function_body_ownership_snapshot_kind_built_current_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_ownership_symbol_has_body_owner_metadata(ProjectSymbolIndexRow symbol) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::symbol_has_body_owner_metadata", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[21]);
	return ((((cast<int_t<>>(symbol->body_first_node_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(symbol->body_source_range_id) > static_cast<int_t<> >(0))) && (cast<int_t<>>(symbol->public_surface_hash) > static_cast<int_t<> >(0))) && (cast<int_t<>>(symbol->body_hash) > static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_ownership_all_symbols_have_body_owner_metadata(shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::all_symbols_have_body_owner_metadata", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[22]);
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		if (static_cast<bool>((!__latency_fn_resident_function_body_ownership_symbol_has_body_owner_metadata(symbol)))) {
			return bool_t(static_cast<bool_t>(false));
		}
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_ownership_row_from_symbol_metadata(ProjectSymbolIndexRow symbol, ResidentSourceUnitFrontendStateRow frontendState, ResidentFrontendNodeListSnapshotRow frontendSnapshot, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::row_from_symbol_metadata", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[23]);
	ResidentFunctionBodySnapshotRow row = ResidentFunctionBodySnapshotRow{};
	row->owner_run_id = ownerRunId;
	row->source_unit_id = symbol->source_unit_id;
	row->symbol_id = symbol->symbol_id;
	row->declaration_node_id = symbol->source_row_id;
	row->body_first_node_id = symbol->body_first_node_id;
	row->body_source_range_id = symbol->body_source_range_id;
	row->body_start_offset = symbol->body_start_offset;
	row->body_length = symbol->body_length;
	row->body_node_count = symbol->body_node_count;
	row->public_surface_hash = symbol->public_surface_hash;
	row->body_hash = symbol->body_hash;
	row->frontend_state_id = frontendState->state_id;
	row->frontend_node_list_snapshot_id = frontendSnapshot->snapshot_id;
	row->snapshot_kind_id = __latency_fn_resident_function_body_ownership_snapshot_kind_from_frontend_state(frontendState);
	row->status_id = __latency_fn_resident_function_body_ownership_status_current_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_ownership_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_ownership_body_end_offset_from_siblings(shared_p<FrontendModel> model, int_t<std::uint32_t> bodyFirstNodeId, int_t<std::uint32_t> bodyStartOffset, int_t<std::uint32_t>& bodyNodeCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::body_end_offset_from_siblings", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[24]);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	int_t<std::uint32_t> nodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(bodyFirstNodeId));
	int_t<std::uint32_t> endOffset = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(bodyStartOffset));
	while (static_cast<bool>((cast<int_t<>>(nodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow node = __latency_fn_frontend_model_tables_node_by_id(model, nodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(node->node_id), static_cast<int_t<> >(0)))) {
			break;
		}
		bodyNodeCount = __latency_fn_resident_function_body_ownership_uint32_from_int((cast<int_t<>>(bodyNodeCount) + static_cast<int_t<> >(1)));
		if (static_cast<bool>((cast<int_t<>>(node->source_range_id) > static_cast<int_t<> >(0)))) {
			SourceRangeRow range = __latency_fn_frontend_model_tables_source_range_by_id(model, node->source_range_id, counters);
			int_t<> rangeEnd = required_cast<int_t<>>((cast<int_t<>>(range->start_offset) + cast<int_t<>>(range->length)));
			if (static_cast<bool>((rangeEnd > cast<int_t<>>(endOffset)))) {
				endOffset = __latency_fn_resident_function_body_ownership_uint32_from_int(rangeEnd);
			}
		}
		nodeId = node->next_sibling_node_id;
	}
	return cast<int_t<std::uint32_t>>(endOffset);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_ownership_row_from_symbol(shared_p<ProjectFrontendModel> project, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow symbol, shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, vector_t<int_t<std::uint32_t>>& modelIndexIds, vector_t<int_t<std::uint32_t>>& frontendStateIds, vector_t<int_t<std::uint32_t>>& frontendSnapshotIds) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::row_from_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[25]);
	ResidentSourceUnitFrontendStateRow frontendState = __latency_fn_resident_function_body_ownership_frontend_state_from_lookup(report, report, frontendStateIds, cast<int_t<std::uint32_t>>(ownerRunId), symbol->source_unit_id);
	ResidentFrontendNodeListSnapshotRow frontendSnapshot = __latency_fn_resident_function_body_ownership_frontend_snapshot_from_lookup(report, report, frontendSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), symbol->source_unit_id);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_function_body_ownership_symbol_has_body_owner_metadata(symbol)))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_resident_function_body_ownership_symbol_metadata_selected(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		return __latency_fn_resident_function_body_ownership_row_from_symbol_metadata(symbol, frontendState, frontendSnapshot, cast<int_t<std::uint32_t>>(ownerRunId));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_resident_function_body_ownership_full_model_fallback_selected(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	shared_p<FrontendModel> model = __latency_fn_resident_function_body_ownership_model_from_lookup(report, project, modelIndexIds, symbol->source_unit_id);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendNodeRow declarationNode = __latency_fn_frontend_model_tables_node_by_id(model, symbol->source_row_id, counters);
	FrontendDeclarationPayloadRow declaration = __latency_fn_frontend_model_tables_declaration_by_id(model, declarationNode->payload_row_id, counters);
	FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, declaration->body_node_id, counters);
	SourceRangeRow bodyRange = __latency_fn_frontend_model_tables_source_range_by_id(model, bodyNode->source_range_id, counters);
	int_t<std::uint32_t> bodyNodeCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> bodyStartOffset = required_cast<int_t<std::uint32_t>>(bodyRange->start_offset);
	int_t<std::uint32_t> bodyEndOffset = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_function_body_ownership_body_end_offset_from_siblings(model, bodyNode->node_id, cast<int_t<std::uint32_t>>(bodyStartOffset), bodyNodeCount));
	int_t<std::uint32_t> bodyLength = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>((cast<int_t<>>(bodyEndOffset) > cast<int_t<>>(bodyStartOffset)))) {
		bodyLength = __latency_fn_resident_function_body_ownership_uint32_from_int((cast<int_t<>>(bodyEndOffset) - cast<int_t<>>(bodyStartOffset)));
	}
	ResidentFunctionBodySnapshotRow row = ResidentFunctionBodySnapshotRow{};
	row->owner_run_id = ownerRunId;
	row->source_unit_id = symbol->source_unit_id;
	row->symbol_id = symbol->symbol_id;
	row->declaration_node_id = symbol->source_row_id;
	row->body_first_node_id = bodyNode->node_id;
	row->body_source_range_id = bodyNode->source_range_id;
	row->body_start_offset = bodyStartOffset;
	row->body_length = bodyLength;
	row->body_node_count = bodyNodeCount;
	row->public_surface_hash = __latency_fn_source_buffers_content_hash32(__latency_fn_project_symbol_index_export_shape_key(symbols, symbol));
	row->body_hash = __latency_fn_source_buffers_content_hash32(__latency_fn_project_symbol_index_body_shape_key(symbols, symbol));
	row->frontend_state_id = frontendState->state_id;
	row->frontend_node_list_snapshot_id = frontendSnapshot->snapshot_id;
	row->snapshot_kind_id = __latency_fn_resident_function_body_ownership_snapshot_kind_from_frontend_state(frontendState);
	row->status_id = __latency_fn_resident_function_body_ownership_status_current_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_ownership_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_append_snapshot(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodySnapshotRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::append_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[26]);
	row->snapshot_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_snapshots));
	(void) report->resident_function_body_snapshots.append(row);
	report->resident_function_body_snapshot_count = __latency_fn_resident_function_body_ownership_uint32_from_int(php::count(report->resident_function_body_snapshots));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_ownership_snapshot_kind_reused_previous_id())))) {
		report->resident_function_body_snapshot_reuse_previous_count = __latency_fn_resident_function_body_ownership_uint32_from_int((cast<int_t<>>(report->resident_function_body_snapshot_reuse_previous_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->resident_function_body_snapshot_built_current_count = __latency_fn_resident_function_body_ownership_uint32_from_int((cast<int_t<>>(report->resident_function_body_snapshot_built_current_count) + static_cast<int_t<> >(1)));
	}
}

}
