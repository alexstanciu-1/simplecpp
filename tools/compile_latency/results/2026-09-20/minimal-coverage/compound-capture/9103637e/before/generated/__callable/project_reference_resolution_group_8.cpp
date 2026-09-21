#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ReferenceContractWorkerInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_for_input.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptors.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_semantic_hash.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_errors.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_semantic_hash.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_first_semantic_hash.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_total.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_errors.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_input_summary_selected_total.hpp"
#include "__callable/__latency_fn_project_reference_resolution_snapshot_int_at.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_input_source_text_bytes.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_record_reference_contract_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_total.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_first_semantic_hash.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_input_source_text_bytes.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_input_summary_selected_total.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_coordinator_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_snapshot_symbol_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_source_text_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_summary_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_worker_parser_errors.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_worker_handoff_worker_semantic_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_selected_publication_owner.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_worker_gate.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_finish.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_reference_contract_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_reference_contract_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_start.hpp"
#include "__callable/__latency_fn_project_reference_resolution_record_reference_contract_publication_metrics__exec.hpp"
#include "__callable/__latency_fn_project_reference_resolution_record_reference_contract_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_publication_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_publication_published_row_total.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_total.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptors.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_first_semantic_hash.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_inputs.hpp"
#include "__callable/__latency_fn_project_reference_resolution_semantic_hash_for_reference_contracts.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_publication_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_publication_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_publication_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_publication_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_reference_contract_publication_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_enabled.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
vector_t<int_t<>> __latency_fn_project_reference_resolution_reference_contract_worker_descriptors(const vector_t<shared_p<ReferenceContractWorkerInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[71]);
	vector_t<int_t<>> descriptors = required_cast<vector_t<int_t<>>>(tasks::run(inputs, workerCount, [](shared_p<ReferenceContractWorkerInput> input) -> int_t<> {
	return __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_for_input(input);
}));
	return descriptors;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_semantic_hash(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_descriptor_semantic_hash", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[72]);
	return cast<int_t<>>((descriptor / __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale()));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_errors(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_descriptor_parser_errors", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[73]);
	return (descriptor % __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale());
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_reference_contract_worker_first_semantic_hash(const vector_t<int_t<>>& descriptors) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_first_semantic_hash", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[74]);
	if (static_cast<bool>((php::count(descriptors) > static_cast<int_t<> >(0)))) {
		return __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_semantic_hash(cast<int_t<>>(descriptors.at(static_cast<int_t<> >(0))));
	}
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_total(const vector_t<int_t<>>& descriptors) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_descriptor_parser_error_total", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[75]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptor = __latency_local_1.value_copy();
		total = (total + __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_errors(cast<int_t<>>(descriptor)));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_reference_contract_worker_input_summary_selected_total(const vector_t<shared_p<ReferenceContractWorkerInput>>& inputs) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_input_summary_selected_total", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[76]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = inputs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto input = __latency_local_1.value_copy();
		int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((index < php::count(input->symbol_ids)))) {
			if (static_cast<bool>((php::identical(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_ids, index), cast<int_t<>>(input->entry_symbol_id)) && php::not_identical(input->source_text, string_t(""))))) {
				total = (total + static_cast<int_t<> >(1));
				break;
			}
			index = (index + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_reference_contract_worker_input_source_text_bytes(const vector_t<shared_p<ReferenceContractWorkerInput>>& inputs) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_input_source_text_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[77]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = inputs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto input = __latency_local_1.value_copy();
		total = (total + str::length(input->source_text));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
void __latency_fn_project_reference_resolution_record_reference_contract_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<ReferenceContractWorkerInput>>& inputs, const vector_t<int_t<>>& workerDescriptors, int_t<std::uint32_t> snapshotSymbolRows, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::record_reference_contract_worker_handoff_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[78]);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_inputs(), __latency_fn_structure_row_ids_uint32_from_int(php::count(inputs)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_snapshot_symbol_rows(), snapshotSymbolRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(php::count(workerDescriptors)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_worker_semantic_hash(), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_project_reference_resolution_reference_contract_worker_first_semantic_hash(workerDescriptors)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_coordinator_semantic_hash(), coordinatorSemanticHash);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_worker_parser_errors(), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_total(workerDescriptors)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_summary_selected(), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_project_reference_resolution_reference_contract_worker_input_summary_selected_total(inputs)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_source_text_bytes(), __latency_fn_project_reference_resolution_reference_contract_worker_input_source_text_bytes(inputs));
	if (static_cast<bool>(php::condition_truthy(matches))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_worker_handoff_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
void __latency_fn_project_reference_resolution_record_reference_contract_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, shared_p<ProjectSymbolIndex> projectSymbols, ProjectSymbolIndexRow entrySymbol, const string_t& entryText, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts, int_t<> workerCount, bool_t upstreamSymbolFactWorkerReady, bool_t& referenceContractWorkerCandidateReady) {
	referenceContractWorkerCandidateReady = bool_t(static_cast<bool_t>(false));
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_project_reference_resolution_reference_contract_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, references, contracts);
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_project_reference_resolution_reference_contract_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, references, contracts);
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	bool_t hasWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_project_reference_resolution_reference_contract_publication_published_row_total(publication));
	bool_t workerHandoffMatches = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	bool_t productionMtEnabled = required_cast<bool_t>(__latency_fn_source_unit_frontend_scheduler_production_mt_enabled());
	if (static_cast<bool>(((productionMtEnabled && hasWorkerCandidate) && upstreamSymbolFactWorkerReady))) {
		int_t<std::uint64_t> referenceSnapshotStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_reference_contract_snapshot_build(), projectSymbols->symbol_count));
		vector_t<shared_p<ReferenceContractWorkerInput>> workerInputs = required_cast<vector_t<shared_p<ReferenceContractWorkerInput>>>(__latency_fn_project_reference_resolution_reference_contract_worker_inputs(sourceUnits, projectSymbols, entrySymbol, entryText));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_reference_contract_snapshot_build(), referenceSnapshotStarted, projectSymbols->symbol_count);
		int_t<std::uint64_t> referenceWorkerTaskStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_reference_contract_worker_task(), __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs))));
		vector_t<int_t<>> workerDescriptors = required_cast<vector_t<int_t<>>>(__latency_fn_project_reference_resolution_reference_contract_worker_descriptors(workerInputs, workerCount));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_reference_contract_worker_task(), referenceWorkerTaskStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerDescriptors)));
		int_t<std::uint32_t> coordinatorSemanticHash = required_cast<int_t<std::uint32_t>>(__latency_fn_project_reference_resolution_semantic_hash_for_reference_contracts(references, contracts));
		int_t<> workerSemanticHash = required_cast<int_t<>>(__latency_fn_project_reference_resolution_reference_contract_worker_first_semantic_hash(workerDescriptors));
		int_t<> workerParserErrors = required_cast<int_t<>>(__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_total(workerDescriptors));
		workerHandoffMatches = ((php::identical(php::count(workerDescriptors), static_cast<int_t<> >(1)) && php::identical(workerSemanticHash, cast<int_t<>>(coordinatorSemanticHash))) && php::identical(workerParserErrors, static_cast<int_t<> >(0)));
		__latency_fn_project_reference_resolution_record_reference_contract_worker_handoff_metrics(report, workerInputs, workerDescriptors, projectSymbols->symbol_count, cast<int_t<std::uint32_t>>(coordinatorSemanticHash), bool_t(workerHandoffMatches));
	}
	bool_t workerCandidateReady = required_cast<bool_t>((((((productionMtEnabled && hasWorkerCandidate) && upstreamSymbolFactWorkerReady) && workerHandoffMatches) && outputsMatch) && (cast<int_t<>>(publishedRows) > static_cast<int_t<> >(0))));
	referenceContractWorkerCandidateReady = bool_t(workerCandidateReady);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_metadata_commit_bytes(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_reference_contract_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_mt_publication_metrics_record_selected_publication_owner(report, string_t("reference_contract_publication"), workerCandidateReady);
	__latency_fn_mt_publication_metrics_record_worker_gate(report, string_t("reference_contract_publication"), string_t("worker_blocked_by_upstream_payload_source"), string_t("upstream_symbol_fact_worker_ready"), string_t("upstream_symbol_fact_coordinator_blocked"), string_t("worker_candidate_ready"), string_t("o3_measurement_required"), string_t("speedup_claim_blocked"), string_t("payload_copy_bytes"), hasWorkerCandidate, upstreamSymbolFactWorkerReady, workerCandidateReady);
}

}
