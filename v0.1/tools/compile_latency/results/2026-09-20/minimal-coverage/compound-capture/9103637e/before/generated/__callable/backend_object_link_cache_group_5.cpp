#include <scpp/lang/php.hpp>
#include "__types/BackendLinkCacheDecisionRow.hpp"
#include "__types/BackendObjectCacheDecisionRow.hpp"
#include "__types/BackendObjectLinkCacheArtifact.hpp"
#include "__types/BackendPartitionExecutionArtifact.hpp"
#include "__types/BackendProjectLinkExecutionRow.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_coordinator_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_object_id.hpp"
#include "__callable/__latency_fn_partition_readiness_publication_model_main_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_coordinator_publication_artifact_from_cache.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_coordinator_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_coordinator_publication_row.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_simulated_link_coordinator_publication_artifact_from_cache.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_coordinator_publication_published_row_total.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_from_backend_partitions.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_coordinator_publication_artifact_from_cache.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_coordinator_publication_published_row_total.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_record_link_coordinator_publication_metrics.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_simulated_link_coordinator_publication_artifact_from_cache.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_add_bool.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_coordinator_selection.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_worker_gate.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_actual_links.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_coordinator_owned_boundaries.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_link_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_link_decision_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_link_relink_deferred_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_link_reuse_deferred_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_worker_blocked_by_coordinator_ownership.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_worker_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_link_coordinator_publication_worker_descriptor_rows.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_debug_string.hpp"
namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_backend_object_link_cache_link_coordinator_publication_row(int_t<std::uint32_t> ownerRunId, BackendLinkCacheDecisionRow linkRow) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::link_coordinator_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[50]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(php::identical(cast<int_t<>>(linkRow->cache_link_id), static_cast<int_t<> >(0)))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id();
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_uint32_from_int((static_cast<int_t<> >(15000) + cast<int_t<>>(linkRow->cache_link_id))), ownerRunId, __latency_fn_partition_readiness_owner_kind_object_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), linkRow->cache_link_id, statusId, blockedReasonId);
	row->input_snapshot_generation = ownerRunId;
	row->local_row_first_id = linkRow->cache_link_id;
	row->local_row_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->merge_order_key = __latency_fn_structure_row_ids_uint64_from_int((static_cast<int_t<> >(6800000) + cast<int_t<>>(linkRow->cache_link_id)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
		row->published_row_first_id = linkRow->cache_link_id;
		row->published_row_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	}
	row->publication_model_id = __latency_fn_partition_readiness_publication_model_main_thread_coordinator_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_backend_object_link_cache_link_coordinator_publication_artifact_from_cache(shared_p<BackendObjectLinkCacheArtifact> cache, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::link_coordinator_publication_artifact_from_cache", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[51]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(php::count(cache->links));
	auto __latency_local_0 = cache->links;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto linkRow = __latency_local_1.value_copy();
		__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_backend_object_link_cache_link_coordinator_publication_row(cast<int_t<std::uint32_t>>(ownerRunId), linkRow));
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_backend_object_link_cache_simulated_link_coordinator_publication_artifact_from_cache(shared_p<BackendObjectLinkCacheArtifact> cache, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::simulated_link_coordinator_publication_artifact_from_cache", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[52]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(php::count(cache->links));
	int_t<> index = required_cast<int_t<>>((php::count(cache->links) - static_cast<int_t<> >(1)));
	while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
		__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_backend_object_link_cache_link_coordinator_publication_row(cast<int_t<std::uint32_t>>(ownerRunId), cache->links[index]));
		index = (index - static_cast<int_t<> >(1));
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_object_link_cache_link_coordinator_publication_published_row_total(shared_p<PartitionReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::link_coordinator_publication_published_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[53]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
			total = (total + cast<int_t<>>(row->published_row_count));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
void __latency_fn_backend_object_link_cache_record_link_coordinator_publication_metrics(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, shared_p<BackendPartitionExecutionArtifact> partitions, int_t<> workerCount, bool_t upstreamObjectOutputWorkerReady, bool_t upstreamObjectOutputWorkerSelected) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::record_link_coordinator_publication_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[54]);
	shared_p<BackendObjectLinkCacheArtifact> cache = __latency_fn_backend_object_link_cache_from_backend_partitions(partitions);
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_backend_object_link_cache_link_coordinator_publication_artifact_from_cache(cache, cast<int_t<std::uint32_t>>(ownerRunId));
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_backend_object_link_cache_simulated_link_coordinator_publication_artifact_from_cache(cache, cast<int_t<std::uint32_t>>(ownerRunId));
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t hasWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	int_t<std::uint32_t> workerBlockedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>(php::condition_truthy(hasWorkerCandidate))) {
		workerBlockedRows = cache->link_count;
	}
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_object_link_cache_link_coordinator_publication_published_row_total(publication));
	bool_t sideEffectBlocked = required_cast<bool_t>((hasWorkerCandidate && upstreamObjectOutputWorkerSelected));
	if (static_cast<bool>(php::condition_truthy(sideEffectBlocked))) {
		__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary(report, workerCount, static_cast<bool_t>(false), static_cast<bool_t>(false), static_cast<bool_t>(true), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_link_decision_rows(), cache->link_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_link_relink_deferred_rows(), cache->link_relink_deferred_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_link_reuse_deferred_rows(), cache->link_reuse_deferred_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_link_blocked_rows(), cache->link_blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_coordinator_owned_boundaries(), cache->link_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_worker_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_worker_blocked_rows(), workerBlockedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_actual_links(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_metadata_commit_bytes(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	if (static_cast<bool>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_mt_publication_metrics_record_coordinator_selection(report, string_t("link_coordinator_publication"));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_link_coordinator_publication_worker_blocked_by_coordinator_ownership(), workerBlockedRows);
	__latency_fn_mt_publication_metrics_add_bool(report, string_t("link_coordinator_publication_upstream_object_output_worker_selected"), (hasWorkerCandidate && upstreamObjectOutputWorkerSelected));
	__latency_fn_mt_publication_metrics_add_bool(report, string_t("link_coordinator_publication_worker_selection_blocked_by_link_coordinator_boundary"), (hasWorkerCandidate && upstreamObjectOutputWorkerSelected));
	__latency_fn_mt_publication_metrics_record_worker_gate(report, string_t("link_coordinator_publication"), string_t("worker_blocked_by_upstream_payload_source"), string_t("upstream_object_output_worker_ready"), string_t("upstream_object_output_coordinator_blocked"), string_t("worker_candidate_ready"), string_t("o3_measurement_required"), string_t("speedup_claim_blocked"), string_t("payload_copy_bytes"), hasWorkerCandidate, upstreamObjectOutputWorkerReady, static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
string_t __latency_fn_backend_object_link_cache_object_debug_string(BackendObjectCacheDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[55]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->cache_object_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("backend_object_cache:symbol:") + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t(":source:") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":compile_deferred"));
}

}
