#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__types/StorageLifetimeWorkerInput.hpp"
#include "__types/StorageLifetimeWorkerResult.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_consumer_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_provider_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_consumer_from_worker_input.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_operation_from_worker_input.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_provider_from_worker_input.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_blocked_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_ready_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_semantic_hash_for_requests.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_worker_result_for_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_worker_result_for_input.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_worker_results.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_first_worker_result.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_policy_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_request_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_snapshot_consumer_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_snapshot_operation_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_snapshot_provider_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_cleanup_policy_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_copy_policy_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_lifetime_policy_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_request_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_storage_context_rows.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_first_worker_result.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_blocked_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_ready_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_record_storage_lifetime_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_storage_lifetime_readiness_consumer_from_worker_input(shared_p<StorageLifetimeWorkerInput> input, bool_t ready) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::consumer_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[63]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	if (static_cast<bool>(php::condition_truthy(ready))) {
		row->capability_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_consumer_capability_id);
		row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->ready_consumer_source_row_id);
		row->provider_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->ready_consumer_provider_source_row_id);
		row->feature_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_consumer_feature_id);
		row->source_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_consumer_source_key_id);
		row->provider_source_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_consumer_provider_source_key_id);
		row->provider_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->ready_consumer_provider_type_ref_id);
		row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_consumer_status_id);
		row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->ready_consumer_blocked_reason_id);
		return row;
	}
	row->capability_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_consumer_capability_id);
	row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->blocked_consumer_source_row_id);
	row->provider_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->blocked_consumer_provider_source_row_id);
	row->feature_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_consumer_feature_id);
	row->source_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_consumer_source_key_id);
	row->provider_source_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_consumer_provider_source_key_id);
	row->provider_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->blocked_consumer_provider_type_ref_id);
	row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_consumer_status_id);
	row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->blocked_consumer_blocked_reason_id);
	return row;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
CapabilityProviderRow __latency_fn_storage_lifetime_readiness_provider_from_worker_input(shared_p<StorageLifetimeWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::provider_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[64]);
	CapabilityProviderRow row = CapabilityProviderRow{};
	row->capability_id = __latency_fn_structure_row_ids_uint16_from_int(input->provider_capability_id);
	row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->provider_source_row_id);
	row->type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->provider_type_ref_id);
	row->source_key_id = __latency_fn_structure_row_ids_uint16_from_int(input->provider_source_key_id);
	row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->provider_status_id);
	row->adapter_id = __latency_fn_structure_row_ids_uint16_from_int(input->provider_adapter_id);
	row->evidence_id = __latency_fn_structure_row_ids_uint16_from_int(input->provider_evidence_id);
	row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->provider_blocked_reason_id);
	return row;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
shared_p<StorageLifetimeWorkerResult> __latency_fn_storage_lifetime_readiness_worker_result_for_input(shared_p<StorageLifetimeWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::worker_result_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[65]);
	OperationReadiness readyOperation = __latency_fn_storage_lifetime_readiness_operation_from_worker_input(input, bool_t(static_cast<bool_t>(true)));
	OperationReadiness blockedOperation = __latency_fn_storage_lifetime_readiness_operation_from_worker_input(input, bool_t(static_cast<bool_t>(false)));
	CapabilityConsumerRow readyConsumer = __latency_fn_storage_lifetime_readiness_consumer_from_worker_input(input, bool_t(static_cast<bool_t>(true)));
	CapabilityConsumerRow blockedConsumer = __latency_fn_storage_lifetime_readiness_consumer_from_worker_input(input, bool_t(static_cast<bool_t>(false)));
	CapabilityProviderRow provider = __latency_fn_storage_lifetime_readiness_provider_from_worker_input(input);
	StorageLifetimeRequestRow readyRow = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), readyOperation, readyConsumer, provider);
	StorageLifetimeRequestRow blockedRow = __latency_fn_storage_lifetime_readiness_request_from_operation(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(2)), blockedOperation, blockedConsumer, provider);
	int_t<> requestRows = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_storage_lifetime_readiness_publication_request_count(readyRow, blockedRow)));
	shared_p<StorageLifetimeWorkerResult> result = create<StorageLifetimeWorkerResult>();
	result->source_unit_id = input->source_unit_id;
	result->symbol_id = input->symbol_id;
	result->request_rows = requestRows;
	result->ready_rows = cast<int_t<>>(__latency_fn_storage_lifetime_readiness_publication_ready_request_count(readyRow, blockedRow));
	result->blocked_rows = cast<int_t<>>(__latency_fn_storage_lifetime_readiness_publication_blocked_request_count(readyRow, blockedRow));
	result->storage_context_rows = requestRows;
	result->copy_policy_rows = requestRows;
	result->cleanup_policy_rows = requestRows;
	result->lifetime_policy_rows = requestRows;
	result->published_rows = requestRows;
	result->semantic_hash = cast<int_t<>>(__latency_fn_storage_lifetime_readiness_semantic_hash_for_requests(readyRow, blockedRow));
	return result;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
vector_t<shared_p<StorageLifetimeWorkerResult>> __latency_fn_storage_lifetime_readiness_worker_results(const vector_t<shared_p<StorageLifetimeWorkerInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::worker_results", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[66]);
	vector_t<shared_p<StorageLifetimeWorkerResult>> results = required_cast<vector_t<shared_p<StorageLifetimeWorkerResult>>>(tasks::run(inputs, workerCount, [](shared_p<StorageLifetimeWorkerInput> input) -> shared_p<StorageLifetimeWorkerResult> {
	return __latency_fn_storage_lifetime_readiness_worker_result_for_input(input);
}));
	return results;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
shared_p<StorageLifetimeWorkerResult> __latency_fn_storage_lifetime_readiness_first_worker_result(const vector_t<shared_p<StorageLifetimeWorkerResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::first_worker_result", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[67]);
	if (static_cast<bool>((php::count(results) > static_cast<int_t<> >(0)))) {
		return results.at(static_cast<int_t<> >(0));
	}
	shared_p<StorageLifetimeWorkerResult> empty = create<StorageLifetimeWorkerResult>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
void __latency_fn_storage_lifetime_readiness_record_storage_lifetime_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<StorageLifetimeWorkerInput>>& inputs, const vector_t<shared_p<StorageLifetimeWorkerResult>>& results, StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::record_storage_lifetime_worker_handoff_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[68]);
	shared_p<StorageLifetimeWorkerResult> worker = __latency_fn_storage_lifetime_readiness_first_worker_result(results);
	int_t<std::uint32_t> coordinatorRequestRows = required_cast<int_t<std::uint32_t>>(__latency_fn_storage_lifetime_readiness_publication_request_count(readyRow, blockedRow));
	int_t<std::uint32_t> coordinatorReadyRows = required_cast<int_t<std::uint32_t>>(__latency_fn_storage_lifetime_readiness_publication_ready_request_count(readyRow, blockedRow));
	int_t<std::uint32_t> coordinatorBlockedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_storage_lifetime_readiness_publication_blocked_request_count(readyRow, blockedRow));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_inputs(), __latency_fn_structure_row_ids_uint32_from_int(php::count(inputs)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_snapshot_operation_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(2)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_snapshot_consumer_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(2)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_snapshot_provider_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(php::count(results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_request_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->request_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_request_rows(), coordinatorRequestRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_ready_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->ready_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_ready_rows(), coordinatorReadyRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_blocked_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->blocked_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_blocked_rows(), coordinatorBlockedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_storage_context_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->storage_context_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_copy_policy_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->copy_policy_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_cleanup_policy_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->cleanup_policy_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_lifetime_policy_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->lifetime_policy_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_policy_rows(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(coordinatorRequestRows)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_worker_semantic_hash(), __latency_fn_structure_row_ids_uint32_from_int(worker->semantic_hash));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_coordinator_semantic_hash(), coordinatorSemanticHash);
	if (static_cast<bool>(php::condition_truthy(matches))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_worker_handoff_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
}

}
