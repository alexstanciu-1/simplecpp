#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_for_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptors.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbols.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_error_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_errors.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_coordinator_symbol_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_worker_parser_errors.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_worker_symbol_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_record_symbol_fact_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_error_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_locked_publication_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_multi_worker_coordinator_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_one_worker_coordinator_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_requested_workers.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_side_effect_boundary_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_worker_owned_symbol_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_record_symbol_fact_live_worker_publication_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_published_row_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_selected_publication_owner.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_worker_gate.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_finish.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_symbol_fact_worker_recompute.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_start.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_recompute_skipped.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_recompute_trial_requested.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_record_symbol_fact_publication_metrics__exec.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_record_symbol_fact_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_published_row_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_error_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptors.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_inputs.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_recompute_trial_requested.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_enabled.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
vector_t<int_t<>> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptors(const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[31]);
	vector_t<int_t<>> descriptors = required_cast<vector_t<int_t<>>>(tasks::run(inputs, workerCount, [](shared_p<SourceUnitFrontendWorkerPayloadInput> input) -> int_t<> {
	return __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_for_input(input);
}));
	return descriptors;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_total(const vector_t<int_t<>>& descriptors) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_descriptor_symbol_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[32]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptor = __latency_local_1.value_copy();
		total = (total + __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbols(cast<int_t<>>(descriptor)));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_error_total(const vector_t<int_t<>>& descriptors) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_descriptor_parser_error_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[33]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptor = __latency_local_1.value_copy();
		total = (total + __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_errors(cast<int_t<>>(descriptor)));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_record_symbol_fact_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, const vector_t<int_t<>>& workerDescriptors, int_t<std::uint32_t> coordinatorSymbolRows, bool_t matches) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::record_symbol_fact_worker_handoff_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[34]);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_runs(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_inputs(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(cast<int_t<>>(sourceUnits->source_unit_count)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_descriptor_rows(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(php::count(workerDescriptors)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_worker_symbol_rows(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_total(workerDescriptors)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_coordinator_symbol_rows(), coordinatorSymbolRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_worker_parser_errors(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_error_total(workerDescriptors)));
	if (static_cast<bool>(php::condition_truthy(matches))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_matches(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_mismatches(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_matches(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_mismatches(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_payload_copy_bytes(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_record_symbol_fact_live_worker_publication_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, const vector_t<int_t<std::uint32_t>>& firstSymbolIds, const vector_t<int_t<std::uint32_t>>& symbolCounts, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::record_symbol_fact_live_worker_publication_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[35]);
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_artifact(sourceUnits, cast<int_t<std::uint32_t>>(ownerRunId), firstSymbolIds, symbolCounts, bool_t(static_cast<bool_t>(false)));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_published_row_total(publication));
	__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary(report, workerCount, static_cast<bool_t>(true), static_cast<bool_t>(false), static_cast<bool_t>(false), publishedRows, __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_runs(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_requested_workers(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_selected(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_one_worker_coordinator_selected(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(php::ternary_eval([&]() -> decltype(auto) { return php::identical(workerCount, static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); })));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_multi_worker_coordinator_selected(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(php::ternary_eval([&]() -> decltype(auto) { return (workerCount > static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); })));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_input_rows(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(cast<int_t<>>(sourceUnits->source_unit_count)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_worker_owned_symbol_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_locked_publication_selected(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_side_effect_boundary_blocked(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_live_worker_publication_payload_copy_bytes(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_symbol_states_record_symbol_fact_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, const vector_t<int_t<std::uint32_t>>& firstSymbolIds, const vector_t<int_t<std::uint32_t>>& symbolCounts, int_t<> workerCount, bool_t frontendPayloadSourceWorkerReady, bool_t& symbolFactWorkerCandidateReady) {
	symbolFactWorkerCandidateReady = bool_t(static_cast<bool_t>(false));
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_artifact(sourceUnits, cast<int_t<std::uint32_t>>(ownerRunId), firstSymbolIds, symbolCounts, bool_t(static_cast<bool_t>(false)));
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_artifact(sourceUnits, cast<int_t<std::uint32_t>>(ownerRunId), firstSymbolIds, symbolCounts, bool_t(static_cast<bool_t>(true)));
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	bool_t hasWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_published_row_total(publication));
	bool_t workerHandoffMatches = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	bool_t recomputeTrialRequested = required_cast<bool_t>(__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_recompute_trial_requested());
	bool_t recomputeSkipped = required_cast<bool_t>((((__latency_fn_source_unit_frontend_scheduler_production_mt_enabled() && hasWorkerCandidate) && frontendPayloadSourceWorkerReady) && (!recomputeTrialRequested)));
	if (static_cast<bool>((((__latency_fn_source_unit_frontend_scheduler_production_mt_enabled() && hasWorkerCandidate) && frontendPayloadSourceWorkerReady) && recomputeTrialRequested))) {
		int_t<std::uint64_t> symbolFactWorkerStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_symbol_fact_worker_recompute(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(cast<int_t<>>(sourceUnits->source_unit_count))));
		vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>> workerInputs = required_cast<vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>>(__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_inputs(sourceUnits));
		vector_t<int_t<>> workerDescriptors = required_cast<vector_t<int_t<>>>(__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptors(workerInputs, workerCount));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_symbol_fact_worker_recompute(), symbolFactWorkerStarted, __latency_fn_resident_source_unit_symbol_states_uint32_from_int(php::count(workerDescriptors)));
		int_t<> workerSymbolRows = required_cast<int_t<>>(__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_total(workerDescriptors));
		int_t<> workerParserErrors = required_cast<int_t<>>(__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_error_total(workerDescriptors));
		workerHandoffMatches = ((php::identical(php::count(workerDescriptors), cast<int_t<>>(sourceUnits->source_unit_count)) && php::identical(workerSymbolRows, cast<int_t<>>(publishedRows))) && php::identical(workerParserErrors, static_cast<int_t<> >(0)));
		__latency_fn_resident_source_unit_symbol_states_record_symbol_fact_worker_handoff_metrics(report, sourceUnits, workerDescriptors, cast<int_t<std::uint32_t>>(publishedRows), bool_t(workerHandoffMatches));
	}
	bool_t workerCandidateReady = required_cast<bool_t>(((((hasWorkerCandidate && frontendPayloadSourceWorkerReady) && workerHandoffMatches) && outputsMatch) && (cast<int_t<>>(publishedRows) > static_cast<int_t<> >(0))));
	symbolFactWorkerCandidateReady = bool_t(workerCandidateReady);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_runs(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_metadata_commit_bytes(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_recompute_trial_requested(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(php::ternary_eval([&]() -> decltype(auto) { return recomputeTrialRequested; }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); })));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_worker_handoff_recompute_skipped(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(php::ternary_eval([&]() -> decltype(auto) { return recomputeSkipped; }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); })));
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_output_matches(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_output_mismatches(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_output_matches(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_symbol_fact_publication_output_mismatches(), __latency_fn_resident_source_unit_symbol_states_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_mt_publication_metrics_record_selected_publication_owner(report, string_t("source_unit_symbol_fact_publication"), workerCandidateReady);
	__latency_fn_mt_publication_metrics_record_worker_gate(report, string_t("source_unit_symbol_fact_publication"), string_t("worker_blocked_by_frontend_payload_source"), string_t("frontend_payload_source_worker_ready"), string_t("frontend_payload_source_coordinator_blocked"), string_t("worker_candidate_ready"), string_t("o3_measurement_required"), string_t("speedup_claim_blocked"), string_t("payload_copy_bytes"), hasWorkerCandidate, frontendPayloadSourceWorkerReady, workerCandidateReady);
}

}
