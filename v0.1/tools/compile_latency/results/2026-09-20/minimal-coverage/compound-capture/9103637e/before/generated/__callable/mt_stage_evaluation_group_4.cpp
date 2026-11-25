#include <scpp/lang/php.hpp>
#include "__types/MtStageEvaluationArtifact.hpp"
#include "__types/MtStageEvaluationRow.hpp"
#include "__types/MtStageMeasurementRow.hpp"
#include "__types/MtWorkerSegmentRow.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_publication_model_descriptor_commit_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_read_only_input_enforced_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stable_published_hash.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stage_row_from_segments.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_ready_for_worker_eval_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_real_worker_stage_row_from_segments.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_stage_row_from_segments.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_real_worker_measured_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_execution_model_not_applicable_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_execution_model_one_thread_proof_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_execution_model_real_workers_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_execution_model_simulated_workers_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_measurement_execution_model.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_ready_for_worker_eval_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_real_worker_measured_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_simulated_only_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_measurement_execution_model.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_measurement_from_stage.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_append_measurement.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_append_measurement.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_append_measurements_from_stages.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_measurement_from_stage.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_blocked_stage_row.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_publication_model_descriptor_commit_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_read_only_input_not_proven_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_append_stage.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_blocked_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_ready_for_worker_eval_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_real_worker_measured_id.hpp"
#include "__callable/__latency_fn_mt_stage_evaluation_status_simulated_only_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
MtStageEvaluationRow __latency_fn_mt_stage_evaluation_stage_row_from_segments(shared_p<MtStageEvaluationArtifact> artifact, int_t<std::uint32_t> rowId, int_t<std::uint16_t> stageKindId, int_t<std::uint16_t> parallelUnitId, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> previousRowCount) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::stage_row_from_segments", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[52]);
	MtStageEvaluationRow row = MtStageEvaluationRow{};
	row->row_id = rowId;
	row->owner_run_id = ownerRunId;
	row->previous_row_count = previousRowCount;
	row->stage_kind_id = stageKindId;
	row->parallel_unit_id = parallelUnitId;
	row->read_only_input_status_id = __latency_fn_mt_stage_evaluation_read_only_input_enforced_id();
	row->publication_model_id = __latency_fn_mt_stage_evaluation_publication_model_descriptor_commit_id();
	row->status_id = __latency_fn_mt_stage_evaluation_status_ready_for_worker_eval_id();
	row->blocked_reason_id = __latency_fn_mt_stage_evaluation_blocked_reason_none_id();
	auto __latency_local_0 = artifact->segments;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto segment = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(segment->stage_kind_id), cast<int_t<>>(stageKindId)))) {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->input_snapshot_generation), static_cast<int_t<> >(0)))) {
				row->input_snapshot_generation = segment->input_snapshot_generation;
			}
			row->worker_segment_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->worker_segment_count) + static_cast<int_t<> >(1)));
			row->local_row_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->local_row_count) + cast<int_t<>>(segment->local_row_count)));
			row->published_row_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->published_row_count) + cast<int_t<>>(segment->published_row_count)));
		}
	}
	row->metadata_commit_bytes = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->worker_segment_count) * static_cast<int_t<> >(sizeof(MtWorkerSegmentRow))));
	row->payload_copy_bytes = __latency_fn_structure_row_ids_none_id();
	row->worker_compute_cost_units = row->local_row_count;
	row->coordinator_commit_cost_units = row->worker_segment_count;
	row->published_output_hash = __latency_fn_mt_stage_evaluation_stable_published_hash(artifact, cast<int_t<std::uint16_t>>(stageKindId));
	return row;
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
MtStageEvaluationRow __latency_fn_mt_stage_evaluation_real_worker_stage_row_from_segments(shared_p<MtStageEvaluationArtifact> artifact, int_t<std::uint32_t> rowId, int_t<std::uint16_t> stageKindId, int_t<std::uint16_t> parallelUnitId, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> previousRowCount) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::real_worker_stage_row_from_segments", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[53]);
	MtStageEvaluationRow row = __latency_fn_mt_stage_evaluation_stage_row_from_segments(artifact, cast<int_t<std::uint32_t>>(rowId), cast<int_t<std::uint16_t>>(stageKindId), cast<int_t<std::uint16_t>>(parallelUnitId), cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(previousRowCount));
	row->status_id = __latency_fn_mt_stage_evaluation_status_real_worker_measured_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_mt_stage_evaluation_measurement_execution_model(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::measurement_execution_model", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[54]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_mt_stage_evaluation_status_simulated_only_id())))) {
		return __latency_fn_mt_stage_evaluation_execution_model_one_thread_proof_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_mt_stage_evaluation_status_ready_for_worker_eval_id())))) {
		return __latency_fn_mt_stage_evaluation_execution_model_simulated_workers_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_mt_stage_evaluation_status_real_worker_measured_id())))) {
		return __latency_fn_mt_stage_evaluation_execution_model_real_workers_id();
	}
	return __latency_fn_mt_stage_evaluation_execution_model_not_applicable_id();
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
MtStageMeasurementRow __latency_fn_mt_stage_evaluation_measurement_from_stage(MtStageEvaluationRow stage, int_t<std::uint32_t> measurementId) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::measurement_from_stage", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[55]);
	MtStageMeasurementRow row = MtStageMeasurementRow{};
	row->measurement_id = measurementId;
	row->stage_row_id = stage->row_id;
	row->owner_run_id = stage->owner_run_id;
	row->one_thread_worker_cost_units = stage->worker_compute_cost_units;
	row->simulated_parallel_worker_cost_units = stage->worker_compute_cost_units;
	row->coordinator_wait_cost_units = __latency_fn_structure_row_ids_none_id();
	row->coordinator_publish_cost_units = stage->coordinator_commit_cost_units;
	row->metadata_commit_bytes = stage->metadata_commit_bytes;
	row->payload_copy_bytes = stage->payload_copy_bytes;
	row->published_row_count = stage->published_row_count;
	row->worker_segment_count = stage->worker_segment_count;
	row->peak_rss_or_pss_bytes = __latency_fn_structure_row_ids_none_id();
	row->incremental_no_change_cost_units = __latency_fn_structure_row_ids_none_id();
	row->incremental_small_edit_cost_units = __latency_fn_structure_row_ids_none_id();
	row->stage_kind_id = stage->stage_kind_id;
	row->execution_model_id = __latency_fn_mt_stage_evaluation_measurement_execution_model(stage->status_id);
	row->status_id = stage->status_id;
	return row;
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
void __latency_fn_mt_stage_evaluation_append_measurement(shared_p<MtStageEvaluationArtifact>& artifact, MtStageMeasurementRow row) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::append_measurement", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[56]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->measurement_id), static_cast<int_t<> >(0)))) {
		row->measurement_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->measurements));
	}
	(void) artifact->measurements.append(row);
	artifact->measurement_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->measurements));
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
void __latency_fn_mt_stage_evaluation_append_measurements_from_stages(shared_p<MtStageEvaluationArtifact>& artifact) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::append_measurements_from_stages", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[57]);
	auto __latency_local_0 = artifact->stages;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto stage = __latency_local_1.value_copy();
		__latency_fn_mt_stage_evaluation_append_measurement(artifact, __latency_fn_mt_stage_evaluation_measurement_from_stage(stage, __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->measurements))));
	}
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
MtStageEvaluationRow __latency_fn_mt_stage_evaluation_blocked_stage_row(int_t<std::uint32_t> rowId, int_t<std::uint16_t> stageKindId, int_t<std::uint16_t> parallelUnitId, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::blocked_stage_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[58]);
	MtStageEvaluationRow row = MtStageEvaluationRow{};
	row->row_id = rowId;
	row->owner_run_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(91));
	row->stage_kind_id = stageKindId;
	row->parallel_unit_id = parallelUnitId;
	row->read_only_input_status_id = __latency_fn_mt_stage_evaluation_read_only_input_not_proven_id();
	row->publication_model_id = __latency_fn_mt_stage_evaluation_publication_model_descriptor_commit_id();
	row->status_id = __latency_fn_mt_stage_evaluation_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_mt_stage_evaluation[]; }
namespace scpp {
void __latency_fn_mt_stage_evaluation_append_stage(shared_p<MtStageEvaluationArtifact>& artifact, MtStageEvaluationRow row) {
	SCPP_CALL_DEPTH_GUARD("mt_stage_evaluation::append_stage", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/mt_stage_evaluation.phs", __latency_lines_mt_stage_evaluation[59]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_id), static_cast<int_t<> >(0)))) {
		row->row_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->stages));
	}
	(void) artifact->stages.append(row);
	artifact->stage_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->stages));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_mt_stage_evaluation_status_blocked_id())))) {
		artifact->blocked_stage_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_stage_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->ready_stage_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_stage_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_mt_stage_evaluation_status_ready_for_worker_eval_id())) || php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_mt_stage_evaluation_status_real_worker_measured_id()))))) {
		artifact->worker_ready_stage_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->worker_ready_stage_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_mt_stage_evaluation_status_simulated_only_id())))) {
		artifact->simulated_stage_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->simulated_stage_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_mt_stage_evaluation_status_real_worker_measured_id())))) {
		artifact->real_worker_stage_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->real_worker_stage_count) + static_cast<int_t<> >(1)));
	}
}

}
