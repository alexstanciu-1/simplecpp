#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessWorkerInput.hpp"
#include "__types/CapabilityReadinessWorkerResult.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_selected_publication_owner.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_worker_gate.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_finish.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_capability_readiness_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_capability_readiness_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_start.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_publication_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_publication_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_publication_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_publication_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_publication_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_enabled.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_publication_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_publication_published_row_total.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_worker_inputs.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_worker_results.hpp"
#include "__callable/__latency_fn_type_capability_readiness_first_capability_readiness_worker_result.hpp"
#include "__callable/__latency_fn_type_capability_readiness_publication_published_row_count.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_capability_readiness_publication_metrics__exec.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_capability_readiness_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_type_capability_readiness_semantic_hash_for_type_refs_and_coverage.hpp"
#include "__callable/__latency_fn_type_capability_readiness_adapter_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_evidence_type_ref_table_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_synthetic_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row.hpp"
#include "__callable/__latency_fn_type_refs_row_from_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_adapter_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_evidence_type_ref_table_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_synthetic_load_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_callable_contract_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_synthetic_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row.hpp"
#include "__callable/__latency_fn_type_refs_row_from_id.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_record_capability_readiness_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, TypeRefTable typeRefs, shared_p<ProjectCallableContractArtifact> contracts, shared_p<CapabilityCoverageArtifact> coverage, int_t<> workerCount, bool_t upstreamReferenceContractWorkerReady, bool_t& capabilityReadinessWorkerCandidateReady) {
	capabilityReadinessWorkerCandidateReady = bool_t(static_cast<bool_t>(false));
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_type_capability_readiness_capability_readiness_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, coverage);
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_type_capability_readiness_capability_readiness_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, coverage);
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_capability_readiness_publication_published_row_total(publication));
	bool_t hasWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	bool_t workerHandoffMatches = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	bool_t productionMtEnabled = required_cast<bool_t>(__latency_fn_source_unit_frontend_scheduler_production_mt_enabled());
	if (static_cast<bool>(((productionMtEnabled && hasWorkerCandidate) && upstreamReferenceContractWorkerReady))) {
		int_t<std::uint64_t> capabilitySnapshotStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_capability_readiness_snapshot_build(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(typeRefs->type_count) + cast<int_t<>>(contracts->contract_count)))));
		vector_t<shared_p<CapabilityReadinessWorkerInput>> workerInputs = required_cast<vector_t<shared_p<CapabilityReadinessWorkerInput>>>(__latency_fn_type_capability_readiness_capability_readiness_worker_inputs(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, typeRefs, contracts));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_capability_readiness_snapshot_build(), capabilitySnapshotStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs)));
		int_t<std::uint64_t> capabilityWorkerTaskStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_capability_readiness_worker_task(), __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs))));
		vector_t<shared_p<CapabilityReadinessWorkerResult>> workerResults = required_cast<vector_t<shared_p<CapabilityReadinessWorkerResult>>>(__latency_fn_type_capability_readiness_capability_readiness_worker_results(workerInputs, workerCount));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_capability_readiness_worker_task(), capabilityWorkerTaskStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerResults)));
		shared_p<CapabilityReadinessWorkerResult> workerResult = __latency_fn_type_capability_readiness_first_capability_readiness_worker_result(workerResults);
		int_t<std::uint32_t> coordinatorSemanticHash = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_semantic_hash_for_type_refs_and_coverage(typeRefs, coverage));
		workerHandoffMatches = (((((((((php::identical(php::count(workerResults), static_cast<int_t<> >(1)) && php::identical(workerResult->type_ref_rows, cast<int_t<>>(typeRefs->type_count))) && php::identical(workerResult->type_arg_rows, cast<int_t<>>(typeRefs->arg_count))) && php::identical(workerResult->capability_rows, cast<int_t<>>(coverage->capability_count))) && php::identical(workerResult->provider_rows, cast<int_t<>>(coverage->provider_count))) && php::identical(workerResult->consumer_rows, cast<int_t<>>(coverage->consumer_count))) && php::identical(workerResult->readiness_rows, cast<int_t<>>(coverage->readiness_count))) && php::identical(workerResult->blocked_consumer_rows, cast<int_t<>>(coverage->blocked_consumer_count))) && php::identical(workerResult->published_rows, cast<int_t<>>(__latency_fn_type_capability_readiness_publication_published_row_count(coverage)))) && php::identical(workerResult->semantic_hash, cast<int_t<>>(coordinatorSemanticHash)));
		__latency_fn_type_capability_readiness_record_capability_readiness_worker_handoff_metrics(report, workerInputs, workerResults, typeRefs, coverage, cast<int_t<std::uint32_t>>(coordinatorSemanticHash), bool_t(workerHandoffMatches));
	}
	bool_t workerCandidateReady = required_cast<bool_t>((((((productionMtEnabled && hasWorkerCandidate) && upstreamReferenceContractWorkerReady) && workerHandoffMatches) && outputsMatch) && (cast<int_t<>>(publishedRows) > static_cast<int_t<> >(0))));
	capabilityReadinessWorkerCandidateReady = bool_t(workerCandidateReady);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_metadata_commit_bytes(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_mt_publication_metrics_record_selected_publication_owner(report, string_t("capability_readiness_publication"), workerCandidateReady);
	__latency_fn_mt_publication_metrics_record_worker_gate(report, string_t("capability_readiness_publication"), string_t("worker_blocked_by_upstream_payload_source"), string_t("upstream_reference_contract_worker_ready"), string_t("upstream_reference_contract_coordinator_blocked"), string_t("worker_candidate_ready"), string_t("o3_measurement_required"), string_t("speedup_claim_blocked"), string_t("payload_copy_bytes"), hasWorkerCandidate, upstreamReferenceContractWorkerReady, workerCandidateReady);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityProviderRow __latency_fn_type_capability_readiness_synthetic_provider(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::synthetic_provider", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[182]);
	CapabilityProviderRow row = CapabilityProviderRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_type_ref_known_id();
	row->source_row_id = typeRefId;
	row->type_ref_id = typeRefId;
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->adapter_id = __latency_fn_type_capability_readiness_adapter_none_id();
	row->evidence_id = __latency_fn_type_capability_readiness_evidence_type_ref_table_id();
	TypeRefRow typeRef = __latency_fn_type_refs_row_from_id(typeRefId);
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row(typeRef));
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
	}
	else {
		row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
		row->blocked_reason_id = blockedReasonId;
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityProviderRow __latency_fn_type_capability_readiness_synthetic_load_provider(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::synthetic_load_provider", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[183]);
	CapabilityProviderRow row = CapabilityProviderRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_type_ref_known_id();
	row->source_row_id = typeRefId;
	row->type_ref_id = typeRefId;
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->adapter_id = __latency_fn_type_capability_readiness_adapter_none_id();
	row->evidence_id = __latency_fn_type_capability_readiness_evidence_type_ref_table_id();
	row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
	row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_synthetic_consumer(int_t<std::uint32_t> sourceRowId, int_t<std::uint16_t> featureId, int_t<std::uint32_t> providerTypeRefId, bool_t blocked) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::synthetic_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[184]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_type_ref_known_id();
	row->source_row_id = sourceRowId;
	row->provider_source_row_id = providerTypeRefId;
	row->feature_id = featureId;
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_callable_contract_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = providerTypeRefId;
	if (static_cast<bool>(php::condition_truthy(blocked))) {
		row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id();
	}
	else {
		TypeRefRow typeRef = __latency_fn_type_refs_row_from_id(providerTypeRefId);
		int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row(typeRef));
		if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())))) {
			row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
			row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
		}
		else {
			row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
			row->blocked_reason_id = blockedReasonId;
		}
	}
	return row;
}

}
