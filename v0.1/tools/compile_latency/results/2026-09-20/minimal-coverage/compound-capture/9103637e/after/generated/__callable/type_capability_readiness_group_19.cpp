#include <scpp/lang/php.hpp>
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityReadinessWorkerInput.hpp"
#include "__types/CapabilityReadinessWorkerResult.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/TypeArgRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_worker_input.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_worker_inputs.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_refs_from_capability_readiness_worker_input.hpp"
#include "__callable/__latency_fn_type_refs_new_table.hpp"
#include "__callable/__latency_fn_project_callable_contracts_new_artifact.hpp"
#include "__callable/__latency_fn_project_callable_contracts_status_compatible_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_contracts_from_capability_readiness_worker_input.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_worker_result_for_input.hpp"
#include "__callable/__latency_fn_type_capability_readiness_contracts_from_capability_readiness_worker_input.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_contracts.hpp"
#include "__callable/__latency_fn_type_capability_readiness_publication_published_row_count.hpp"
#include "__callable/__latency_fn_type_capability_readiness_semantic_hash_for_type_refs_and_coverage.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_refs_from_capability_readiness_worker_input.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_worker_result_for_input.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_worker_results.hpp"
#include "__callable/__latency_fn_type_capability_readiness_first_capability_readiness_worker_result.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_blocked_consumer_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_capability_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_consumer_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_provider_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_readiness_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_type_ref_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_snapshot_contract_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_snapshot_type_ref_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_blocked_consumer_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_capability_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_consumer_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_provider_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_readiness_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_type_ref_rows.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_first_capability_readiness_worker_result.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_capability_readiness_worker_handoff_metrics.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
vector_t<shared_p<CapabilityReadinessWorkerInput>> __latency_fn_type_capability_readiness_capability_readiness_worker_inputs(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, TypeRefTable typeRefs, shared_p<ProjectCallableContractArtifact> contracts) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_readiness_worker_inputs", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[175]);
	vector_t<shared_p<CapabilityReadinessWorkerInput>> inputs = {};
	php::vector_reserve(inputs, static_cast<int_t<> >(1));
	{
	auto __latency_local_0 = __latency_fn_type_capability_readiness_capability_readiness_worker_input(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, typeRefs, contracts);
	(void) inputs.push_back(__latency_local_0);
	}
	return inputs;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
TypeRefTable __latency_fn_type_capability_readiness_type_refs_from_capability_readiness_worker_input(shared_p<CapabilityReadinessWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::type_refs_from_capability_readiness_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[176]);
	TypeRefTable table = __latency_fn_type_refs_new_table(php::count(input->type_ref_ids), php::count(input->type_arg_parent_type_ref_ids));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(input->type_ref_ids)))) {
		TypeRefRow row = TypeRefRow{};
		row->type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->type_ref_ids.at(index)));
		row->type_kind_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->type_ref_kind_ids.at(index)));
		row->family_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->type_ref_family_ids.at(index)));
		row->status_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->type_ref_status_ids.at(index)));
		(void) table->types.append(row);
		index = (index + static_cast<int_t<> >(1));
	}
	int_t<> argIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((argIndex < php::count(input->type_arg_parent_type_ref_ids)))) {
		TypeArgRow row = TypeArgRow{};
		row->parent_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->type_arg_parent_type_ref_ids.at(argIndex)));
		row->arg_index = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->type_arg_indices.at(argIndex)));
		row->arg_kind_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->type_arg_kind_ids.at(argIndex)));
		row->type_arg_ref_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->type_arg_ref_ids.at(argIndex)));
		row->value_arg_int = __latency_fn_structure_row_ids_int32_from_int(cast<int_t<>>(input->type_arg_value_ints.at(argIndex)));
		row->status_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->type_arg_status_ids.at(argIndex)));
		(void) table->args.append(row);
		argIndex = (argIndex + static_cast<int_t<> >(1));
	}
	table->type_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(table->types));
	table->arg_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(table->args));
	return table;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<ProjectCallableContractArtifact> __latency_fn_type_capability_readiness_contracts_from_capability_readiness_worker_input(shared_p<CapabilityReadinessWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::contracts_from_capability_readiness_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[177]);
	shared_p<ProjectCallableContractArtifact> artifact = __latency_fn_project_callable_contracts_new_artifact(php::count(input->contract_ids));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(input->contract_ids)))) {
		ProjectCallableContractRow row = ProjectCallableContractRow{};
		row->contract_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->contract_ids.at(index)));
		row->reference_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->contract_reference_ids.at(index)));
		row->from_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->contract_from_symbol_ids.at(index)));
		row->target_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->contract_target_symbol_ids.at(index)));
		row->target_source_unit_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->contract_target_source_unit_ids.at(index)));
		row->argument_count_status_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->contract_argument_count_status_ids.at(index)));
		row->return_type_status_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->contract_return_type_status_ids.at(index)));
		row->backend_lowering_status_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->contract_backend_lowering_status_ids.at(index)));
		row->status_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->contract_status_ids.at(index)));
		row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->contract_blocked_reason_ids.at(index)));
		row->actual_arg_count = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->contract_actual_arg_counts.at(index)));
		row->expected_arg_count = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->contract_expected_arg_counts.at(index)));
		row->return_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(input->contract_return_type_ref_ids.at(index)));
		row->backend_adapter_id = __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(input->contract_backend_adapter_ids.at(index)));
		(void) artifact->rows.append(row);
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_status_compatible_id())))) {
			artifact->compatible_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->compatible_count) + static_cast<int_t<> >(1)));
		}
		else {
			artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	artifact->contract_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityReadinessWorkerResult> __latency_fn_type_capability_readiness_capability_readiness_worker_result_for_input(shared_p<CapabilityReadinessWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_readiness_worker_result_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[178]);
	TypeRefTable typeRefs = __latency_fn_type_capability_readiness_type_refs_from_capability_readiness_worker_input(input);
	shared_p<ProjectCallableContractArtifact> contracts = __latency_fn_type_capability_readiness_contracts_from_capability_readiness_worker_input(input);
	shared_p<CapabilityCoverageArtifact> coverage = __latency_fn_type_capability_readiness_coverage_from_type_refs_and_contracts(typeRefs, contracts);
	shared_p<CapabilityReadinessWorkerResult> result = create<CapabilityReadinessWorkerResult>();
	result->source_unit_id = input->source_unit_id;
	result->symbol_id = input->symbol_id;
	result->type_ref_rows = cast<int_t<>>(typeRefs->type_count);
	result->type_arg_rows = cast<int_t<>>(typeRefs->arg_count);
	result->capability_rows = cast<int_t<>>(coverage->capability_count);
	result->provider_rows = cast<int_t<>>(coverage->provider_count);
	result->consumer_rows = cast<int_t<>>(coverage->consumer_count);
	result->readiness_rows = cast<int_t<>>(coverage->readiness_count);
	result->blocked_consumer_rows = cast<int_t<>>(coverage->blocked_consumer_count);
	result->published_rows = cast<int_t<>>(__latency_fn_type_capability_readiness_publication_published_row_count(coverage));
	result->semantic_hash = cast<int_t<>>(__latency_fn_type_capability_readiness_semantic_hash_for_type_refs_and_coverage(typeRefs, coverage));
	return result;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
vector_t<shared_p<CapabilityReadinessWorkerResult>> __latency_fn_type_capability_readiness_capability_readiness_worker_results(const vector_t<shared_p<CapabilityReadinessWorkerInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_readiness_worker_results", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[179]);
	vector_t<shared_p<CapabilityReadinessWorkerResult>> results = required_cast<vector_t<shared_p<CapabilityReadinessWorkerResult>>>(tasks::run(inputs, workerCount, [](shared_p<CapabilityReadinessWorkerInput> input) -> shared_p<CapabilityReadinessWorkerResult> {
	return __latency_fn_type_capability_readiness_capability_readiness_worker_result_for_input(input);
}));
	return results;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityReadinessWorkerResult> __latency_fn_type_capability_readiness_first_capability_readiness_worker_result(const vector_t<shared_p<CapabilityReadinessWorkerResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::first_capability_readiness_worker_result", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[180]);
	if (static_cast<bool>((php::count(results) > static_cast<int_t<> >(0)))) {
		return results.at(static_cast<int_t<> >(0));
	}
	shared_p<CapabilityReadinessWorkerResult> empty = create<CapabilityReadinessWorkerResult>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_record_capability_readiness_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<CapabilityReadinessWorkerInput>>& inputs, const vector_t<shared_p<CapabilityReadinessWorkerResult>>& results, TypeRefTable typeRefs, shared_p<CapabilityCoverageArtifact> coverage, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::record_capability_readiness_worker_handoff_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[181]);
	shared_p<CapabilityReadinessWorkerResult> worker = __latency_fn_type_capability_readiness_first_capability_readiness_worker_result(results);
	int_t<> snapshotContractRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>((php::count(inputs) > static_cast<int_t<> >(0)))) {
		snapshotContractRows = php::count(inputs.at(static_cast<int_t<> >(0))->contract_ids);
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_inputs(), __latency_fn_structure_row_ids_uint32_from_int(php::count(inputs)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_snapshot_type_ref_rows(), typeRefs->type_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_snapshot_contract_rows(), __latency_fn_structure_row_ids_uint32_from_int(snapshotContractRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(php::count(results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_type_ref_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->type_ref_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_type_ref_rows(), typeRefs->type_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_capability_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->capability_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_capability_rows(), coverage->capability_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_provider_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->provider_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_provider_rows(), coverage->provider_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_consumer_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->consumer_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_consumer_rows(), coverage->consumer_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_readiness_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->readiness_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_readiness_rows(), coverage->readiness_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_blocked_consumer_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->blocked_consumer_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_blocked_consumer_rows(), coverage->blocked_consumer_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_worker_semantic_hash(), __latency_fn_structure_row_ids_uint32_from_int(worker->semantic_hash));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_coordinator_semantic_hash(), coordinatorSemanticHash);
	if (static_cast<bool>(php::condition_truthy(matches))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_capability_readiness_worker_handoff_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
}

}
