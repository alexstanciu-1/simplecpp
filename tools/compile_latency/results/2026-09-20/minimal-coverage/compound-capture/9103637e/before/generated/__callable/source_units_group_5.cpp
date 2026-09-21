#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/SourceReadDescriptorRow.hpp"
#include "__types/SourceReadResultRow.hpp"
#include "__types/SourceReadTable.hpp"
#include "__types/SourceReadWorkerProbeInput.hpp"
#include "__types/SourceReadWorkerPublicationStats.hpp"
#include "__types/SourceReadWorkerResult.hpp"
#include "__callable/__latency_fn_source_units_append_source_read_worker_result.hpp"
#include "__callable/__latency_fn_source_units_publish_source_read_worker_batch.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_descriptor_for_input.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_descriptors.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_signature.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_mismatch_count.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_signature.hpp"
#include "__callable/__latency_fn_source_units_source_read_table_metadata_mismatch_count.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_source_units_source_read_table_text_hash_mismatch_count.hpp"
#include "__callable/__latency_fn_source_units_source_read_descriptor_result_metadata_mismatch_count.hpp"
#include "__callable/__latency_fn_source_units_source_read_error_none_id.hpp"
#include "__callable/__latency_fn_source_units_source_read_status_ready_id.hpp"
#include "__callable/__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_batches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_coordinator_fallback.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_largest_batch.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_metadata_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_metadata_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_o3_measurement_required.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_pool_size.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_requested_workers.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_result_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runtime_batches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runtime_callback_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runtime_deferred_flushes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runtime_failed_try_locks.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runtime_largest_batch.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runtime_lock_hold_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runtime_lock_wait_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runtime_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_source_text_adopted.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_speedup_claim_blocked.hpp"
#include "__callable/__latency_fn_source_units_record_source_read_worker_publication_not_selected.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_publish_source_read_worker_batch(shared_p<SourceReadTable>& table, const shared_p<SourceReadTable>& descriptorSourceReads, const vector_t<shared_p<SourceReadWorkerResult>>& batch, shared_p<SourceReadWorkerPublicationStats>& stats) {
	SCPP_CALL_DEPTH_GUARD("source_units::publish_source_read_worker_batch", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[45]);
	stats->publish_batches = (stats->publish_batches + static_cast<int_t<> >(1));
	if (static_cast<bool>((php::count(batch) > cast<int_t<>>(stats->largest_publish_batch)))) {
		stats->largest_publish_batch = php::count(batch);
	}
	int_t<> batchIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((batchIndex < php::count(batch)))) {
		shared_p<SourceReadWorkerResult> result = batch.at(batchIndex);
		__latency_fn_source_units_append_source_read_worker_result(table, descriptorSourceReads, result);
		stats->published_results = (stats->published_results + static_cast<int_t<> >(1));
		batchIndex = (batchIndex + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
vector_t<int_t<>> __latency_fn_source_units_source_read_worker_probe_descriptors(const vector_t<shared_p<SourceReadWorkerProbeInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_probe_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[46]);
	vector_t<int_t<>> descriptors = required_cast<vector_t<int_t<>>>(tasks::run(inputs, workerCount, [](shared_p<SourceReadWorkerProbeInput> input) -> int_t<> {
	return __latency_fn_source_units_source_read_worker_probe_descriptor_for_input(input);
}));
	return descriptors;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_source_read_worker_probe_mismatch_count(const shared_p<SourceReadTable>& sourceReads, const vector_t<int_t<>>& workerDescriptors) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_probe_mismatch_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[47]);
	int_t<> mismatches = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(php::count(workerDescriptors), cast<int_t<>>(sourceReads->result_count))))) {
		mismatches = (mismatches + static_cast<int_t<> >(1));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((index < php::count(workerDescriptors)) && (index < cast<int_t<>>(sourceReads->result_count))))) {
		int_t<> coordinatorSignature = required_cast<int_t<>>(__latency_fn_source_units_source_read_result_signature(sourceReads->results[index]));
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(workerDescriptors.at(index)), coordinatorSignature)))) {
			mismatches = (mismatches + static_cast<int_t<> >(1));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return mismatches;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_source_read_table_metadata_mismatch_count(const shared_p<SourceReadTable>& left, const shared_p<SourceReadTable>& right) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_table_metadata_mismatch_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[48]);
	int_t<> mismatches = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(left->result_count), cast<int_t<>>(right->result_count))))) {
		mismatches = (mismatches + static_cast<int_t<> >(1));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((index < cast<int_t<>>(left->result_count)) && (index < cast<int_t<>>(right->result_count))))) {
		if (static_cast<bool>(php::condition_truthy(php::not_identical(__latency_fn_source_units_source_read_result_signature(left->results[index]), __latency_fn_source_units_source_read_result_signature(right->results[index]))))) {
			mismatches = (mismatches + static_cast<int_t<> >(1));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return mismatches;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_source_read_table_text_hash_mismatch_count(const shared_p<SourceReadTable>& left, const shared_p<SourceReadTable>& right) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_table_text_hash_mismatch_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[49]);
	int_t<> mismatches = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(php::count(left->source_texts), php::count(right->source_texts))))) {
		mismatches = (mismatches + static_cast<int_t<> >(1));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((index < php::count(left->source_texts)) && (index < php::count(right->source_texts))))) {
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(__latency_fn_source_buffers_content_hash32(left->source_texts[index])), cast<int_t<>>(__latency_fn_source_buffers_content_hash32(right->source_texts[index])))))) {
			mismatches = (mismatches + static_cast<int_t<> >(1));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return mismatches;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_source_read_descriptor_result_metadata_mismatch_count(const shared_p<SourceReadTable>& descriptorSourceReads, const shared_p<SourceReadTable>& workerSourceReads) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_descriptor_result_metadata_mismatch_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[50]);
	int_t<> mismatches = required_cast<int_t<>>(static_cast<int_t<> >(0));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(descriptorSourceReads->descriptor_count), cast<int_t<>>(workerSourceReads->result_count))))) {
		mismatches = (mismatches + static_cast<int_t<> >(1));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((index < cast<int_t<>>(descriptorSourceReads->descriptor_count)) && (index < cast<int_t<>>(workerSourceReads->result_count))))) {
		SourceReadDescriptorRow descriptor = descriptorSourceReads->descriptors[index];
		SourceReadResultRow result = workerSourceReads->results[index];
		if (static_cast<bool>(((((((((((php::not_identical(cast<int_t<>>(result->descriptor_id), cast<int_t<>>(descriptor->descriptor_id)) || php::not_identical(cast<int_t<>>(result->source_id), cast<int_t<>>(descriptor->source_id))) || php::not_identical(cast<int_t<>>(result->manifest_order_id), cast<int_t<>>(descriptor->manifest_order_id))) || php::not_identical(cast<int_t<>>(result->language_id), cast<int_t<>>(descriptor->language_id))) || php::not_identical(cast<int_t<>>(result->status_id), cast<int_t<>>(descriptor->status_id))) || php::not_identical(cast<int_t<>>(result->source_unit_key_id), cast<int_t<>>(descriptor->source_unit_key_id))) || php::not_identical(cast<int_t<>>(result->relative_path_id), cast<int_t<>>(descriptor->relative_path_id))) || php::not_identical(cast<int_t<>>(result->path_id), cast<int_t<>>(descriptor->path_id))) || php::not_identical(cast<int_t<>>(result->output_order_id), cast<int_t<>>(descriptor->manifest_order_id))) || php::not_identical(cast<int_t<>>(result->read_status_id), cast<int_t<>>(__latency_fn_source_units_source_read_status_ready_id()))) || php::not_identical(cast<int_t<>>(result->error_status_id), cast<int_t<>>(__latency_fn_source_units_source_read_error_none_id()))))) {
			mismatches = (mismatches + static_cast<int_t<> >(1));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return mismatches;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_record_source_read_worker_publication_not_selected(shared_p<CompilerProjectRunReport>& report, const shared_p<SourceReadTable>& sourceReads, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("source_units::record_source_read_worker_publication_not_selected", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[51]);
	__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary(report, workerCount, static_cast<bool_t>(false), static_cast<bool_t>(true), static_cast<bool_t>(false), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_requested_workers(), __latency_fn_structure_row_ids_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_pool_size(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_selected(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_coordinator_fallback(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(sourceReads->descriptor_count)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_result_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_published_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_metadata_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_metadata_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_elapsed_us(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_batches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_largest_batch(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_lock_wait_us(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_lock_hold_us(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_callback_us(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_batches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_published_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_largest_batch(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_failed_try_locks(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_deferred_flushes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_source_text_adopted(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_o3_measurement_required(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_speedup_claim_blocked(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
}

}
