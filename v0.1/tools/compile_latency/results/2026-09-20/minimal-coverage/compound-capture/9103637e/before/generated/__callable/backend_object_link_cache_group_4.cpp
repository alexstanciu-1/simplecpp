#include <scpp/lang/php.hpp>
#include "__types/BackendObjectCacheDecisionRow.hpp"
#include "__types/BackendObjectLinkCacheArtifact.hpp"
#include "__types/BackendPartitionExecutionArtifact.hpp"
#include "__types/BackendPartitionExecutionRow.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ObjectOutputWorkerInput.hpp"
#include "__types/ObjectOutputWorkerResult.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_row_from_worker_result.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_row_from_partition.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_result_for_input.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_result_from_row.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_partition_from_object_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_result_for_input.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_results.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_append_object.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_new_artifact.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_cache_from_worker_results.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_row_from_worker_result.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_record_object_output_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_compile_deferred_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_intent_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_reuse_deferred_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_snapshot_partition_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_worker_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_worker_compile_deferred_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_worker_intent_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_worker_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_worker_reuse_deferred_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_worker_handoff_worker_semantic_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_from_backend_partitions.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_cache_from_worker_results.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_cache_rows_match.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_output_publication_artifact_from_cache.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_output_publication_published_row_total.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_inputs_from_partitions.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_worker_results.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_record_object_output_publication_metrics__exec.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_record_object_output_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_semantic_hash_for_objects.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_simulated_object_output_publication_artifact_from_cache.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_selected_publication_owner.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_worker_gate.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_finish.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_object_output_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_object_output_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_start.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_actual_writes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_coordinator_owned_outputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_object_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_object_compile_deferred_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_object_intent_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_object_reuse_deferred_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_object_output_publication_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_enabled.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
BackendObjectCacheDecisionRow __latency_fn_backend_object_link_cache_object_row_from_worker_result(shared_p<ObjectOutputWorkerResult> result) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_row_from_worker_result", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[45]);
	BackendObjectCacheDecisionRow row = BackendObjectCacheDecisionRow{};
	row->cache_object_id = __latency_fn_structure_row_ids_uint32_from_int(result->cache_object_id);
	row->partition_execution_id = __latency_fn_structure_row_ids_uint32_from_int(result->partition_execution_id);
	row->owner_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(result->owner_symbol_id);
	row->source_unit_id = __latency_fn_structure_row_ids_uint32_from_int(result->source_unit_id);
	row->input_partition_hash = result->input_partition_hash;
	row->cache_key_hash = result->cache_key_hash;
	row->logical_object_key_hash = result->logical_object_key_hash;
	row->cache_status_id = __latency_fn_structure_row_ids_uint16_from_int(result->cache_status_id);
	row->dirty_status_id = __latency_fn_structure_row_ids_uint16_from_int(result->dirty_status_id);
	row->object_action_id = __latency_fn_structure_row_ids_uint16_from_int(result->object_action_id);
	row->status_id = __latency_fn_structure_row_ids_uint16_from_int(result->status_id);
	row->owner_key_id = __latency_fn_structure_row_ids_uint16_from_int(result->owner_key_id);
	row->source_unit_key_id = __latency_fn_structure_row_ids_uint16_from_int(result->source_unit_key_id);
	row->partition_key_id = __latency_fn_structure_row_ids_uint16_from_int(result->partition_key_id);
	row->backend_row_key_id = __latency_fn_structure_row_ids_uint16_from_int(result->backend_row_key_id);
	row->output_object_key_id = __latency_fn_structure_row_ids_uint16_from_int(result->output_object_key_id);
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<ObjectOutputWorkerResult> __latency_fn_backend_object_link_cache_object_worker_result_for_input(shared_p<ObjectOutputWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_worker_result_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[46]);
	BackendPartitionExecutionRow partition = __latency_fn_backend_object_link_cache_partition_from_object_worker_input(input);
	BackendObjectCacheDecisionRow row = __latency_fn_backend_object_link_cache_object_row_from_partition(__latency_fn_structure_row_ids_uint32_from_int(input->cache_object_id), partition);
	return __latency_fn_backend_object_link_cache_object_worker_result_from_row(row);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
vector_t<shared_p<ObjectOutputWorkerResult>> __latency_fn_backend_object_link_cache_object_worker_results(const vector_t<shared_p<ObjectOutputWorkerInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_worker_results", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[47]);
	vector_t<shared_p<ObjectOutputWorkerResult>> results = required_cast<vector_t<shared_p<ObjectOutputWorkerResult>>>(tasks::run(inputs, workerCount, [](shared_p<ObjectOutputWorkerInput> input) -> shared_p<ObjectOutputWorkerResult> {
	return __latency_fn_backend_object_link_cache_object_worker_result_for_input(input);
}));
	return results;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<BackendObjectLinkCacheArtifact> __latency_fn_backend_object_link_cache_object_cache_from_worker_results(const vector_t<shared_p<ObjectOutputWorkerResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_cache_from_worker_results", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[48]);
	shared_p<BackendObjectLinkCacheArtifact> artifact = __latency_fn_backend_object_link_cache_new_artifact(php::count(results), static_cast<int_t<> >(0));
	auto& __latency_local_0 = results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		__latency_fn_backend_object_link_cache_append_object(artifact, __latency_fn_backend_object_link_cache_object_row_from_worker_result(result));
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
void __latency_fn_backend_object_link_cache_record_object_output_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<ObjectOutputWorkerInput>>& inputs, const vector_t<shared_p<ObjectOutputWorkerResult>>& results, shared_p<BackendObjectLinkCacheArtifact> coordinatorCache, shared_p<BackendObjectLinkCacheArtifact> workerCache, int_t<std::uint32_t> coordinatorSemanticHash, int_t<std::uint32_t> workerSemanticHash, int_t<std::uint32_t> coordinatorPublishedRows, int_t<std::uint32_t> workerPublishedRows, bool_t matches) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::record_object_output_worker_handoff_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[49]);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_inputs(), __latency_fn_structure_row_ids_uint32_from_int(php::count(inputs)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_snapshot_partition_rows(), __latency_fn_structure_row_ids_uint32_from_int(php::count(inputs)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(php::count(results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_worker_intent_rows(), workerCache->object_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_intent_rows(), coordinatorCache->object_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_worker_compile_deferred_rows(), workerCache->object_compile_deferred_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_compile_deferred_rows(), coordinatorCache->object_compile_deferred_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_worker_reuse_deferred_rows(), workerCache->object_reuse_deferred_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_reuse_deferred_rows(), coordinatorCache->object_reuse_deferred_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_worker_blocked_rows(), workerCache->object_blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_blocked_rows(), coordinatorCache->object_blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_worker_published_rows(), workerPublishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_published_rows(), coordinatorPublishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_worker_semantic_hash(), workerSemanticHash);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_coordinator_semantic_hash(), coordinatorSemanticHash);
	if (static_cast<bool>(php::condition_truthy(matches))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_worker_handoff_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
void __latency_fn_backend_object_link_cache_record_object_output_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, shared_p<BackendPartitionExecutionArtifact> partitions, int_t<> workerCount, bool_t upstreamEmissionLLVMWorkerReady, bool_t& objectOutputWorkerCandidateReady, bool_t& objectOutputWorkerSelected) {
	objectOutputWorkerCandidateReady = bool_t(static_cast<bool_t>(false));
	objectOutputWorkerSelected = bool_t(static_cast<bool_t>(false));
	shared_p<BackendObjectLinkCacheArtifact> cache = __latency_fn_backend_object_link_cache_from_backend_partitions(partitions);
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_backend_object_link_cache_object_output_publication_artifact_from_cache(cache, cast<int_t<std::uint32_t>>(ownerRunId));
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_backend_object_link_cache_simulated_object_output_publication_artifact_from_cache(cache, cast<int_t<std::uint32_t>>(ownerRunId));
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_object_link_cache_object_output_publication_published_row_total(publication));
	bool_t hasWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	bool_t productionMtEnabled = required_cast<bool_t>(__latency_fn_source_unit_frontend_scheduler_production_mt_enabled());
	bool_t workerHandoffMatches = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	if (static_cast<bool>(((productionMtEnabled && hasWorkerCandidate) && upstreamEmissionLLVMWorkerReady))) {
		int_t<std::uint64_t> objectOutputSnapshotStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_object_output_snapshot_build(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(partitions->partition_count))));
		vector_t<shared_p<ObjectOutputWorkerInput>> workerInputs = required_cast<vector_t<shared_p<ObjectOutputWorkerInput>>>(__latency_fn_backend_object_link_cache_object_worker_inputs_from_partitions(cast<int_t<std::uint32_t>>(ownerRunId), partitions));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_object_output_snapshot_build(), objectOutputSnapshotStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs)));
		int_t<std::uint64_t> objectOutputWorkerTaskStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_object_output_worker_task(), __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs))));
		vector_t<shared_p<ObjectOutputWorkerResult>> workerResults = required_cast<vector_t<shared_p<ObjectOutputWorkerResult>>>(__latency_fn_backend_object_link_cache_object_worker_results(workerInputs, workerCount));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_object_output_worker_task(), objectOutputWorkerTaskStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerResults)));
		shared_p<BackendObjectLinkCacheArtifact> workerCache = __latency_fn_backend_object_link_cache_object_cache_from_worker_results(workerResults);
		shared_p<PartitionReadinessArtifact> workerPublication = __latency_fn_backend_object_link_cache_object_output_publication_artifact_from_cache(workerCache, cast<int_t<std::uint32_t>>(ownerRunId));
		shared_p<PartitionMergeReductionArtifact> workerReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(workerPublication);
		int_t<std::uint32_t> coordinatorSemanticHash = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_object_link_cache_semantic_hash_for_objects(cache));
		int_t<std::uint32_t> workerSemanticHash = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_object_link_cache_semantic_hash_for_objects(workerCache));
		int_t<std::uint32_t> workerPublishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_object_link_cache_object_output_publication_published_row_total(workerPublication));
		workerHandoffMatches = ((((((((((php::identical(php::count(workerResults), cast<int_t<>>(cache->object_count)) && php::identical(cast<int_t<>>(workerCache->object_count), cast<int_t<>>(cache->object_count))) && php::identical(cast<int_t<>>(workerCache->object_compile_deferred_count), cast<int_t<>>(cache->object_compile_deferred_count))) && php::identical(cast<int_t<>>(workerCache->object_reuse_deferred_count), cast<int_t<>>(cache->object_reuse_deferred_count))) && php::identical(cast<int_t<>>(workerCache->object_blocked_count), cast<int_t<>>(cache->object_blocked_count))) && __latency_fn_backend_object_link_cache_object_cache_rows_match(workerCache, cache)) && php::identical(cast<int_t<>>(workerPublishedRows), cast<int_t<>>(publishedRows))) && php::identical(cast<int_t<>>(workerReduction->output_row_count), cast<int_t<>>(reduction->output_row_count))) && php::identical(workerReduction->stable_output_hash, reduction->stable_output_hash)) && (cast<int_t<>>(workerSemanticHash) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(workerSemanticHash), cast<int_t<>>(coordinatorSemanticHash)));
		__latency_fn_backend_object_link_cache_record_object_output_worker_handoff_metrics(report, workerInputs, workerResults, cache, workerCache, cast<int_t<std::uint32_t>>(coordinatorSemanticHash), cast<int_t<std::uint32_t>>(workerSemanticHash), cast<int_t<std::uint32_t>>(publishedRows), cast<int_t<std::uint32_t>>(workerPublishedRows), bool_t(workerHandoffMatches));
	}
	bool_t workerCandidateReady = required_cast<bool_t>((((((productionMtEnabled && hasWorkerCandidate) && upstreamEmissionLLVMWorkerReady) && workerHandoffMatches) && outputsMatch) && (cast<int_t<>>(publishedRows) > static_cast<int_t<> >(0))));
	objectOutputWorkerCandidateReady = bool_t(workerCandidateReady);
	objectOutputWorkerSelected = bool_t(workerCandidateReady);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_object_intent_rows(), cache->object_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_object_compile_deferred_rows(), cache->object_compile_deferred_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_object_reuse_deferred_rows(), cache->object_reuse_deferred_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_object_blocked_rows(), cache->object_blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_coordinator_owned_outputs(), cache->object_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_actual_writes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_metadata_commit_bytes(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_object_output_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_mt_publication_metrics_record_selected_publication_owner(report, string_t("object_output_publication"), workerCandidateReady);
	__latency_fn_mt_publication_metrics_record_worker_gate(report, string_t("object_output_publication"), string_t("worker_blocked_by_upstream_payload_source"), string_t("upstream_emission_llvm_worker_ready"), string_t("upstream_emission_llvm_coordinator_blocked"), string_t("worker_candidate_ready"), string_t("o3_measurement_required"), string_t("speedup_claim_blocked"), string_t("payload_copy_bytes"), hasWorkerCandidate, upstreamEmissionLLVMWorkerReady, workerCandidateReady);
}

}
