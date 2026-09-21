#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyChangeRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/ResidentSymbolDefinitionSnapshotRow.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_deleted_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_new_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_no_change_reuse_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_public_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_cleanup_deleted_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_reuse_previous_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_work_decision_kind_from_body_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_added_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_deleted_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_kind_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_change.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_deleted_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_new_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_no_change_reuse_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_public_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_cleanup_deleted_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_publish_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_cleanup_deleted_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_from_change.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_reuse_previous_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_work_decision_kind_from_body_change.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_work_decision_kind_from_body_change(int_t<std::uint16_t> changeKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::work_decision_kind_from_body_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[26]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_change_no_change_reuse_id())))) {
		return __latency_fn_resident_function_body_work_decisions_decision_reuse_previous_body_rows_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_change_public_surface_changed_id())))) {
		return __latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_change_deleted_body_id())))) {
		return __latency_fn_resident_function_body_work_decisions_decision_cleanup_deleted_body_rows_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_change_new_body_id())))) {
		return __latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id();
	}
	return __latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
ResidentFunctionBodyChangeRow __latency_fn_resident_function_body_work_decisions_change_from_symbol_change(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentSymbolDefinitionChangeRow change, vector_t<int_t<std::uint32_t>>& currentSnapshotIds, vector_t<int_t<std::uint32_t>>& previousSnapshotIds) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::change_from_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[27]);
	ResidentFunctionBodySnapshotRow currentSnapshot = ResidentFunctionBodySnapshotRow{};
	ResidentFunctionBodySnapshotRow previousSnapshot = ResidentFunctionBodySnapshotRow{};
	int_t<std::uint32_t> currentSymbolId = required_cast<int_t<std::uint32_t>>(change->symbol_id);
	int_t<std::uint32_t> previousSymbolId = required_cast<int_t<std::uint32_t>>(change->symbol_id);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_deleted_id()))))) {
		ResidentSymbolDefinitionSnapshotRow currentSymbol = __latency_fn_resident_definition_granularity_symbol_snapshot_by_id(report, change->current_snapshot_id);
		if (static_cast<bool>((cast<int_t<>>(currentSymbol->symbol_id) > static_cast<int_t<> >(0)))) {
			currentSymbolId = currentSymbol->symbol_id;
		}
		currentSnapshot = __latency_fn_resident_function_body_work_decisions_snapshot_from_lookup(report, report, currentSnapshotIds, change->owner_run_id, cast<int_t<std::uint32_t>>(currentSymbolId));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_added_id()))))) {
		ResidentSymbolDefinitionSnapshotRow previousSymbol = __latency_fn_resident_definition_granularity_symbol_snapshot_by_id(previous, change->previous_snapshot_id);
		if (static_cast<bool>((cast<int_t<>>(previousSymbol->symbol_id) > static_cast<int_t<> >(0)))) {
			previousSymbolId = previousSymbol->symbol_id;
		}
		previousSnapshot = __latency_fn_resident_function_body_work_decisions_snapshot_from_lookup(report, previous, previousSnapshotIds, change->owner_run_id, cast<int_t<std::uint32_t>>(previousSymbolId));
	}
	ResidentFunctionBodyChangeRow row = ResidentFunctionBodyChangeRow{};
	row->owner_run_id = change->owner_run_id;
	row->symbol_definition_change_id = change->change_id;
	row->previous_symbol_definition_snapshot_id = change->previous_snapshot_id;
	row->current_symbol_definition_snapshot_id = change->current_snapshot_id;
	row->previous_function_body_snapshot_id = previousSnapshot->snapshot_id;
	row->current_function_body_snapshot_id = currentSnapshot->snapshot_id;
	row->symbol_id = change->symbol_id;
	row->source_unit_id = change->source_unit_id;
	row->previous_public_surface_hash = previousSnapshot->public_surface_hash;
	row->current_public_surface_hash = currentSnapshot->public_surface_hash;
	row->previous_body_hash = previousSnapshot->body_hash;
	row->current_body_hash = currentSnapshot->body_hash;
	row->change_kind_id = __latency_fn_resident_function_body_work_decisions_change_kind_from_symbol_change(change);
	row->dirty_scope_id = change->dirty_scope_id;
	row->reuse_scope_id = change->reuse_scope_id;
	row->status_id = __latency_fn_resident_function_body_work_decisions_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_work_decisions_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
void __latency_fn_resident_function_body_work_decisions_append_change(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyChangeRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::append_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[28]);
	row->change_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_changes));
	(void) report->resident_function_body_changes.append(row);
	report->resident_function_body_change_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int(php::count(report->resident_function_body_changes));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->change_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_change_no_change_reuse_id())))) {
		report->resident_function_body_reuse_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_reuse_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->change_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_change_body_changed_id())))) {
			report->resident_function_body_body_change_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_body_change_count) + static_cast<int_t<> >(1)));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->change_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_change_public_surface_changed_id())))) {
				report->resident_function_body_public_surface_change_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_public_surface_change_count) + static_cast<int_t<> >(1)));
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(row->change_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_change_new_body_id())))) {
					report->resident_function_body_new_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_new_count) + static_cast<int_t<> >(1)));
				}
				else {
					if (static_cast<bool>(php::identical(cast<int_t<>>(row->change_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_change_deleted_body_id())))) {
						report->resident_function_body_deleted_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_deleted_count) + static_cast<int_t<> >(1)));
					}
				}
			}
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
ResidentFunctionBodyWorkDecisionRow __latency_fn_resident_function_body_work_decisions_decision_from_change(ResidentFunctionBodyChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::decision_from_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[29]);
	int_t<std::uint16_t> decisionKindId = required_cast<int_t<std::uint16_t>>(__latency_fn_resident_function_body_work_decisions_work_decision_kind_from_body_change(change->change_kind_id));
	ResidentFunctionBodyWorkDecisionRow row = ResidentFunctionBodyWorkDecisionRow{};
	row->owner_run_id = change->owner_run_id;
	row->function_body_change_id = change->change_id;
	row->symbol_id = change->symbol_id;
	row->source_unit_id = change->source_unit_id;
	row->previous_function_body_snapshot_id = change->previous_function_body_snapshot_id;
	row->current_function_body_snapshot_id = change->current_function_body_snapshot_id;
	row->work_decision_kind_id = decisionKindId;
	row->body_row_action_id = __latency_fn_resident_function_body_work_decisions_action_none_id();
	row->public_surface_action_id = __latency_fn_resident_function_body_work_decisions_action_none_id();
	row->cleanup_action_id = __latency_fn_resident_function_body_work_decisions_action_none_id();
	if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_reuse_previous_body_rows_id())))) {
		row->body_row_action_id = __latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id();
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id())))) {
			row->body_row_action_id = __latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id();
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id())))) {
				row->body_row_action_id = __latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id();
				row->public_surface_action_id = __latency_fn_resident_function_body_work_decisions_action_publish_public_surface_id();
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(decisionKindId), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_cleanup_deleted_body_rows_id())))) {
					row->cleanup_action_id = __latency_fn_resident_function_body_work_decisions_action_cleanup_deleted_body_rows_id();
				}
				else {
					row->body_row_action_id = __latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id();
				}
			}
		}
	}
	row->status_id = __latency_fn_resident_function_body_work_decisions_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_work_decisions_blocked_reason_none_id();
	return row;
}

}
