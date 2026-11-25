#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/ResidentSourceUnitEarlySkipArtifact.hpp"
#include "__types/ResidentSourceUnitEarlySkipDecisionRow.hpp"
#include "__types/ResidentSourceUnitFrontendWorkDecisionRow.hpp"
#include "__types/ResidentTokenListSnapshotRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_source_change_by_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_source_change_by_owner_and_source.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_source_change_from_lookup.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_token_lists_snapshot_by_owner_and_source.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_token_snapshot_by_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_token_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_by_owner_and_source.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_frontend_snapshot_by_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_frontend_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_action_from_decision_kind.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_kind_from_early_skip.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_frontend_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_row_from_early_skip.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_source_change_from_lookup.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_token_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_append_decision.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_build_new_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_cleanup_deleted_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_parse_replacement_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_decision_reuse_previous_lists_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_append_memory_estimate.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_append_decision.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_append_from_early_skip.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_append_memory_estimate.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_build_frontend_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_build_source_change_lookup_ids.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_build_token_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_max_source_unit_id_from_early_skip.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_row_from_early_skip.hpp"
namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
ResidentSourceUnitChangeRow __latency_fn_source_unit_frontend_work_decisions_source_change_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> changeReport, vector_t<int_t<std::uint32_t>>& changeIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::source_change_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[22]);
	metricsReport->resident_source_unit_frontend_work_lookup_probe_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_frontend_work_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(changeIds))))) {
		int_t<std::uint32_t> changeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(changeIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(changeId), static_cast<int_t<> >(0)))) {
			ResidentSourceUnitChangeRow empty = ResidentSourceUnitChangeRow{};
			return empty;
		}
		ResidentSourceUnitChangeRow change = __latency_fn_source_unit_frontend_work_decisions_source_change_by_id(changeReport, cast<int_t<std::uint32_t>>(changeId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(change->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return change;
		}
	}
	metricsReport->resident_source_unit_frontend_work_lookup_fallback_scan_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_frontend_work_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_source_unit_frontend_work_decisions_source_change_by_owner_and_source(changeReport, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(sourceUnitId));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
ResidentTokenListSnapshotRow __latency_fn_source_unit_frontend_work_decisions_token_snapshot_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::token_snapshot_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[23]);
	metricsReport->resident_source_unit_frontend_work_lookup_probe_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_frontend_work_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(snapshotIds))))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			ResidentTokenListSnapshotRow empty = ResidentTokenListSnapshotRow{};
			return empty;
		}
		ResidentTokenListSnapshotRow snapshot = __latency_fn_source_unit_frontend_work_decisions_token_snapshot_by_id(snapshotReport, cast<int_t<std::uint32_t>>(snapshotId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return snapshot;
		}
	}
	metricsReport->resident_source_unit_frontend_work_lookup_fallback_scan_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_frontend_work_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_resident_token_lists_snapshot_by_owner_and_source(snapshotReport, ownerRunId, sourceUnitId);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
ResidentFrontendNodeListSnapshotRow __latency_fn_source_unit_frontend_work_decisions_frontend_snapshot_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::frontend_snapshot_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[24]);
	metricsReport->resident_source_unit_frontend_work_lookup_probe_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_frontend_work_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(snapshotIds))))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			ResidentFrontendNodeListSnapshotRow empty = ResidentFrontendNodeListSnapshotRow{};
			return empty;
		}
		ResidentFrontendNodeListSnapshotRow snapshot = __latency_fn_source_unit_frontend_work_decisions_frontend_snapshot_by_id(snapshotReport, cast<int_t<std::uint32_t>>(snapshotId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return snapshot;
		}
	}
	metricsReport->resident_source_unit_frontend_work_lookup_fallback_scan_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_unit_frontend_work_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_resident_frontend_node_lists_snapshot_by_owner_and_source(snapshotReport, ownerRunId, sourceUnitId);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
ResidentSourceUnitFrontendWorkDecisionRow __latency_fn_source_unit_frontend_work_decisions_row_from_early_skip(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentSourceUnitEarlySkipDecisionRow earlySkip, vector_t<int_t<std::uint32_t>>& sourceChangeIds, vector_t<int_t<std::uint32_t>>& previousTokenSnapshotIds, vector_t<int_t<std::uint32_t>>& currentTokenSnapshotIds, vector_t<int_t<std::uint32_t>>& previousFrontendSnapshotIds, vector_t<int_t<std::uint32_t>>& currentFrontendSnapshotIds) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::row_from_early_skip", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[25]);
	int_t<std::uint16_t> decisionKindId = required_cast<int_t<std::uint16_t>>(__latency_fn_source_unit_frontend_work_decisions_decision_kind_from_early_skip(earlySkip));
	int_t<std::uint16_t> actionId = required_cast<int_t<std::uint16_t>>(__latency_fn_source_unit_frontend_work_decisions_action_from_decision_kind(cast<int_t<std::uint16_t>>(decisionKindId)));
	ResidentSourceUnitChangeRow change = __latency_fn_source_unit_frontend_work_decisions_source_change_from_lookup(report, report, sourceChangeIds, earlySkip->owner_run_id, earlySkip->source_unit_id);
	ResidentTokenListSnapshotRow previousToken = __latency_fn_source_unit_frontend_work_decisions_token_snapshot_from_lookup(report, previous, previousTokenSnapshotIds, earlySkip->owner_run_id, earlySkip->source_unit_id);
	ResidentTokenListSnapshotRow currentToken = __latency_fn_source_unit_frontend_work_decisions_token_snapshot_from_lookup(report, report, currentTokenSnapshotIds, earlySkip->owner_run_id, earlySkip->source_unit_id);
	ResidentFrontendNodeListSnapshotRow previousFrontend = __latency_fn_source_unit_frontend_work_decisions_frontend_snapshot_from_lookup(report, previous, previousFrontendSnapshotIds, earlySkip->owner_run_id, earlySkip->source_unit_id);
	ResidentFrontendNodeListSnapshotRow currentFrontend = __latency_fn_source_unit_frontend_work_decisions_frontend_snapshot_from_lookup(report, report, currentFrontendSnapshotIds, earlySkip->owner_run_id, earlySkip->source_unit_id);
	ResidentSourceUnitFrontendWorkDecisionRow row = ResidentSourceUnitFrontendWorkDecisionRow{};
	row->owner_run_id = earlySkip->owner_run_id;
	row->source_unit_id = earlySkip->source_unit_id;
	row->source_unit_key_id = earlySkip->source_unit_key_id;
	row->early_skip_decision_id = earlySkip->decision_id;
	row->source_change_id = change->change_id;
	row->previous_token_list_snapshot_id = previousToken->snapshot_id;
	row->current_token_list_snapshot_id = currentToken->snapshot_id;
	row->previous_frontend_node_list_snapshot_id = previousFrontend->snapshot_id;
	row->current_frontend_node_list_snapshot_id = currentFrontend->snapshot_id;
	row->work_decision_kind_id = decisionKindId;
	row->token_list_action_id = actionId;
	row->frontend_node_list_action_id = actionId;
	row->status_id = __latency_fn_source_unit_frontend_work_decisions_status_ready_id();
	row->blocked_reason_id = __latency_fn_source_unit_frontend_work_decisions_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_work_decisions_append_decision(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitFrontendWorkDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::append_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[26]);
	row->decision_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_source_unit_frontend_work_decisions));
	(void) report->resident_source_unit_frontend_work_decisions.append(row);
	report->resident_source_unit_frontend_work_decision_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int(php::count(report->resident_source_unit_frontend_work_decisions));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->work_decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_frontend_work_decisions_decision_reuse_previous_lists_id())))) {
		report->resident_source_unit_frontend_work_reuse_previous_list_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_source_unit_frontend_work_reuse_previous_list_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->work_decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_frontend_work_decisions_decision_parse_replacement_lists_id())))) {
			report->resident_source_unit_frontend_work_parse_replacement_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_source_unit_frontend_work_parse_replacement_count) + static_cast<int_t<> >(1)));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->work_decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_frontend_work_decisions_decision_cleanup_deleted_lists_id())))) {
				report->resident_source_unit_frontend_work_cleanup_deleted_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_source_unit_frontend_work_cleanup_deleted_count) + static_cast<int_t<> >(1)));
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(row->work_decision_kind_id), cast<int_t<>>(__latency_fn_source_unit_frontend_work_decisions_decision_build_new_lists_id())))) {
					report->resident_source_unit_frontend_work_build_new_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_source_unit_frontend_work_build_new_count) + static_cast<int_t<> >(1)));
				}
			}
		}
	}
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_work_decisions_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[27]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_source_unit_frontend_work_decisions_uint32_from_int(rowCount), __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentSourceUnitFrontendWorkDecisionRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_work_decisions_append_from_early_skip(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentSourceUnitEarlySkipArtifact artifact, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::append_from_early_skip", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[28]);
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_source_unit_frontend_work_decisions));
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_source_unit_frontend_work_decisions_max_source_unit_id_from_early_skip(artifact, cast<int_t<std::uint32_t>>(ownerRunId)));
	vector_t<int_t<std::uint32_t>> sourceChangeIds = {};
	vector_t<int_t<std::uint32_t>> previousTokenSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> currentTokenSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> previousFrontendSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> currentFrontendSnapshotIds = {};
	__latency_fn_source_unit_frontend_work_decisions_build_source_change_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, sourceChangeIds);
	__latency_fn_source_unit_frontend_work_decisions_build_token_snapshot_lookup_ids(previous, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, previousTokenSnapshotIds);
	__latency_fn_source_unit_frontend_work_decisions_build_token_snapshot_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, currentTokenSnapshotIds);
	__latency_fn_source_unit_frontend_work_decisions_build_frontend_snapshot_lookup_ids(previous, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, previousFrontendSnapshotIds);
	__latency_fn_source_unit_frontend_work_decisions_build_frontend_snapshot_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, currentFrontendSnapshotIds);
	__latency_fn_source_unit_frontend_work_decisions_append_lookup_metrics(report, (slotCount * static_cast<int_t<> >(5)));
	auto __latency_local_0 = artifact->decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto earlySkip = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(earlySkip->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_source_unit_frontend_work_decisions_append_decision(report, __latency_fn_source_unit_frontend_work_decisions_row_from_early_skip(report, previous, earlySkip, sourceChangeIds, previousTokenSnapshotIds, currentTokenSnapshotIds, previousFrontendSnapshotIds, currentFrontendSnapshotIds));
		}
	}
	int_t<> addedCount = required_cast<int_t<>>((php::count(report->resident_source_unit_frontend_work_decisions) - startCount));
	__latency_fn_source_unit_frontend_work_decisions_append_memory_estimate(report, addedCount);
}

}
