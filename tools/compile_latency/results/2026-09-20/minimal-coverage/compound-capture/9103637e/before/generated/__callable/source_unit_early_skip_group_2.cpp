#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentSourceUnitEarlySkipArtifact.hpp"
#include "__types/ResidentSourceUnitEarlySkipDecisionRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_source_unit_key_id_from_snapshots.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_from_decision_kind.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_kind_from_snapshots.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_dirty_status_from_decision_kind.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_gate_stage_before_tokenize_parse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_owner_run_id_from_snapshots.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_reuse_status_from_decision_kind.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_row_from_snapshots.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_source_unit_id_from_snapshots.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_source_unit_key_id_from_snapshots.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_status_ready_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_append_decision.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_content_changed_reparse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_new_source_reparse_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_no_change_skip_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_append_source_snapshot_lookup_metrics.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_by_owner_and_source.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_source_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_build_source_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_max_source_unit_id_for_owner.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_append_decision.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_append_source_snapshot_lookup_metrics.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_from_reports_for_owner.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_new_artifact.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_row_from_snapshots.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_source_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_unit_early_skip_source_unit_key_id_from_snapshots(ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::source_unit_key_id_from_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[25]);
	if (static_cast<bool>((cast<int_t<>>(current->source_unit_key_id) > static_cast<int_t<> >(0)))) {
		return current->source_unit_key_id;
	}
	return previous->source_unit_key_id;
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
ResidentSourceUnitEarlySkipDecisionRow __latency_fn_source_unit_early_skip_row_from_snapshots(int_t<std::uint32_t> decisionId, ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous, int_t<std::uint32_t> fallbackOwnerRunId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::row_from_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[26]);
	int_t<std::uint16_t> decisionKindId = required_cast<int_t<std::uint16_t>>(__latency_fn_source_unit_early_skip_decision_kind_from_snapshots(current, previous));
	ResidentSourceUnitEarlySkipDecisionRow row = ResidentSourceUnitEarlySkipDecisionRow{};
	row->decision_id = decisionId;
	row->owner_run_id = __latency_fn_source_unit_early_skip_owner_run_id_from_snapshots(current, previous, cast<int_t<std::uint32_t>>(fallbackOwnerRunId));
	row->source_unit_id = __latency_fn_source_unit_early_skip_source_unit_id_from_snapshots(current, previous);
	row->source_unit_key_id = __latency_fn_source_unit_early_skip_source_unit_key_id_from_snapshots(current, previous);
	row->previous_snapshot_id = previous->snapshot_id;
	row->current_snapshot_id = current->snapshot_id;
	row->previous_content_hash = previous->content_hash;
	row->current_content_hash = current->content_hash;
	row->previous_source_length = previous->source_length;
	row->current_source_length = current->source_length;
	row->previous_line_count = previous->line_count;
	row->current_line_count = current->line_count;
	row->gate_stage_id = __latency_fn_source_unit_early_skip_gate_stage_before_tokenize_parse_id();
	row->decision_kind_id = decisionKindId;
	row->tokenize_parse_action_id = __latency_fn_source_unit_early_skip_action_from_decision_kind(cast<int_t<std::uint16_t>>(decisionKindId));
	row->dirty_status_id = __latency_fn_source_unit_early_skip_dirty_status_from_decision_kind(cast<int_t<std::uint16_t>>(decisionKindId));
	row->reuse_status_id = __latency_fn_source_unit_early_skip_reuse_status_from_decision_kind(cast<int_t<std::uint16_t>>(decisionKindId));
	row->status_id = __latency_fn_source_unit_early_skip_status_ready_id();
	row->blocked_reason_id = __latency_fn_source_unit_early_skip_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
void __latency_fn_source_unit_early_skip_append_decision(ResidentSourceUnitEarlySkipArtifact& artifact, ResidentSourceUnitEarlySkipDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::append_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[27]);
	row->decision_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->decisions));
	(void) artifact->decisions.append(row);
	artifact->decision_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->decisions));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_source_unit_early_skip_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_no_change_skip_id())))) {
		artifact->skipped_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->skipped_owner_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_content_changed_reparse_id())))) {
			artifact->changed_source_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->changed_source_count) + static_cast<int_t<> >(1)));
			artifact->reparse_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->reparse_owner_count) + static_cast<int_t<> >(1)));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_new_source_reparse_id())))) {
				artifact->new_source_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->new_source_count) + static_cast<int_t<> >(1)));
				artifact->reparse_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->reparse_owner_count) + static_cast<int_t<> >(1)));
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(row->decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_early_skip_decision_deleted_source_cleanup_id())))) {
					artifact->deleted_source_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->deleted_source_count) + static_cast<int_t<> >(1)));
					artifact->cleanup_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->cleanup_owner_count) + static_cast<int_t<> >(1)));
				}
			}
		}
	}
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
void __latency_fn_source_unit_early_skip_append_source_snapshot_lookup_metrics(ResidentSourceUnitEarlySkipArtifact& artifact, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::append_source_snapshot_lookup_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[28]);
	artifact->source_snapshot_lookup_slot_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->source_snapshot_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
ResidentSourceUnitSnapshotRow __latency_fn_source_unit_early_skip_source_snapshot_from_lookup(ResidentSourceUnitEarlySkipArtifact& artifact, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::source_snapshot_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[29]);
	artifact->source_snapshot_lookup_probe_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->source_snapshot_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(snapshotIds))))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			ResidentSourceUnitSnapshotRow empty = ResidentSourceUnitSnapshotRow{};
			return empty;
		}
		ResidentSourceUnitSnapshotRow snapshot = __latency_fn_resident_definition_granularity_source_snapshot_by_id(snapshotReport, snapshotId);
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return snapshot;
		}
	}
	artifact->source_snapshot_lookup_fallback_scan_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->source_snapshot_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_resident_definition_granularity_source_snapshot_by_owner_and_source(snapshotReport, ownerRunId, sourceUnitId);
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
ResidentSourceUnitEarlySkipArtifact __latency_fn_source_unit_early_skip_from_reports_for_owner(shared_p<CompilerProjectRunReport> currentReport, shared_p<CompilerProjectRunReport> previousReport, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::from_reports_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[30]);
	ResidentSourceUnitEarlySkipArtifact artifact = __latency_fn_source_unit_early_skip_new_artifact((cast<int_t<>>(currentReport->resident_source_unit_snapshot_count) + cast<int_t<>>(previousReport->resident_source_unit_snapshot_count)));
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_max_source_unit_id_for_owner(currentReport, ownerRunId));
	int_t<> previousSlotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_max_source_unit_id_for_owner(previousReport, ownerRunId));
	if (static_cast<bool>((previousSlotCount > slotCount))) {
		slotCount = previousSlotCount;
	}
	vector_t<int_t<std::uint32_t>> currentSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> previousSnapshotIds = {};
	__latency_fn_resident_definition_granularity_build_source_snapshot_lookup_ids(currentReport, ownerRunId, slotCount, currentSnapshotIds);
	__latency_fn_resident_definition_granularity_build_source_snapshot_lookup_ids(previousReport, ownerRunId, slotCount, previousSnapshotIds);
	__latency_fn_source_unit_early_skip_append_source_snapshot_lookup_metrics(artifact, (slotCount * static_cast<int_t<> >(2)));
	auto __latency_local_0 = currentReport->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto current = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(current->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSourceUnitSnapshotRow previous = __latency_fn_source_unit_early_skip_source_snapshot_from_lookup(artifact, previousReport, previousSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), current->source_unit_id);
			__latency_fn_source_unit_early_skip_append_decision(artifact, __latency_fn_source_unit_early_skip_row_from_snapshots(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->decisions)), current, previous, cast<int_t<std::uint32_t>>(ownerRunId)));
		}
	}
	auto __latency_local_2 = previousReport->resident_source_unit_snapshots;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto previous = __latency_local_3.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(previous->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSourceUnitSnapshotRow current = __latency_fn_source_unit_early_skip_source_snapshot_from_lookup(artifact, currentReport, currentSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), previous->source_unit_id);
			if (static_cast<bool>(php::identical(cast<int_t<>>(current->snapshot_id), static_cast<int_t<> >(0)))) {
				ResidentSourceUnitSnapshotRow emptyCurrent = ResidentSourceUnitSnapshotRow{};
				__latency_fn_source_unit_early_skip_append_decision(artifact, __latency_fn_source_unit_early_skip_row_from_snapshots(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->decisions)), emptyCurrent, previous, cast<int_t<std::uint32_t>>(ownerRunId)));
			}
		}
	}
	return artifact;
}

}
