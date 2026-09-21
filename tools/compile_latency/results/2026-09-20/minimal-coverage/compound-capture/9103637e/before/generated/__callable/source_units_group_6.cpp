#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/SourceReadTable.hpp"
#include "__types/SourceReadWorkerProbeInput.hpp"
#include "__types/SourceReadWorkerPublicationStats.hpp"
#include "__types/SourceReadWorkerResult.hpp"
#include "__callable/__latency_fn_compiler_profile_events_elapsed_us_since.hpp"
#include "__callable/__latency_fn_compiler_profile_events_now_us.hpp"
#include "__callable/__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_coordinator_fallback.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_metadata_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_metadata_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_o3_measurement_required.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_pool_size.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_requested_workers.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_result_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_source_text_adopted.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_publication_speedup_claim_blocked.hpp"
#include "__callable/__latency_fn_source_units_record_source_read_worker_publication_not_selected.hpp"
#include "__callable/__latency_fn_source_units_source_read_table_from_worker_results.hpp"
#include "__callable/__latency_fn_source_units_source_read_table_metadata_mismatch_count.hpp"
#include "__callable/__latency_fn_source_units_source_read_table_text_hash_mismatch_count.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_pool_size.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_inputs.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_results.hpp"
#include "__callable/__latency_fn_source_units_worker_published_source_read_table.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_compiler_profile_events_elapsed_us_since.hpp"
#include "__callable/__latency_fn_compiler_profile_events_now_us.hpp"
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
#include "__callable/__latency_fn_source_units_publish_source_read_worker_batch.hpp"
#include "__callable/__latency_fn_source_units_record_source_read_worker_publication_not_selected.hpp"
#include "__callable/__latency_fn_source_units_reserve_source_read_table.hpp"
#include "__callable/__latency_fn_source_units_source_read_descriptor_result_metadata_mismatch_count.hpp"
#include "__callable/__latency_fn_source_units_source_read_hash_match.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_pool_size.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_inputs.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_publish_batch_cap.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_result_for_input.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_try_lock_publication_enabled.hpp"
#include "__callable/__latency_fn_source_units_worker_published_source_read_table_from_descriptors.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceReadTable> __latency_fn_source_units_worker_published_source_read_table(shared_p<CompilerProjectRunReport>& report, const shared_p<SourceReadTable>& coordinatorSourceReads, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("source_units::worker_published_source_read_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[52]);
	if (static_cast<bool>(((workerCount <= static_cast<int_t<> >(0)) || (cast<int_t<>>(coordinatorSourceReads->descriptor_count) <= static_cast<int_t<> >(0))))) {
		__latency_fn_source_units_record_source_read_worker_publication_not_selected(report, coordinatorSourceReads, workerCount);
		return coordinatorSourceReads;
	}
	vector_t<shared_p<SourceReadWorkerProbeInput>> inputs = required_cast<vector_t<shared_p<SourceReadWorkerProbeInput>>>(__latency_fn_source_units_source_read_worker_probe_inputs(coordinatorSourceReads));
	int_t<> poolSize = required_cast<int_t<>>(__latency_fn_source_units_source_read_worker_pool_size());
	if (static_cast<bool>((poolSize > static_cast<int_t<> >(0)))) {
		tasks::configure_default_worker_pool(poolSize);
	}
	int_t<std::uint64_t> started = required_cast<int_t<std::uint64_t>>(__latency_fn_compiler_profile_events_now_us());
	vector_t<shared_p<SourceReadWorkerResult>> workerResults = required_cast<vector_t<shared_p<SourceReadWorkerResult>>>(__latency_fn_source_units_source_read_worker_results(inputs, workerCount));
	shared_p<SourceReadTable> workerSourceReads = __latency_fn_source_units_source_read_table_from_worker_results(coordinatorSourceReads, workerResults);
	int_t<std::uint32_t> elapsedUs = required_cast<int_t<std::uint32_t>>(__latency_fn_compiler_profile_events_elapsed_us_since(started));
	int_t<> metadataMismatches = required_cast<int_t<>>(__latency_fn_source_units_source_read_table_metadata_mismatch_count(coordinatorSourceReads, workerSourceReads));
	int_t<> textHashMismatches = required_cast<int_t<>>(__latency_fn_source_units_source_read_table_text_hash_mismatch_count(coordinatorSourceReads, workerSourceReads));
	bool_t matches = required_cast<bool_t>((php::identical(metadataMismatches, static_cast<int_t<> >(0)) && php::identical(textHashMismatches, static_cast<int_t<> >(0))));
	int_t<std::uint32_t> payloadCopyBytes = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(php::ternary_eval([&]() -> decltype(auto) { return matches; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(workerSourceReads->result_count)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary(report, workerCount, matches, (!matches), static_cast<bool_t>(false), publishedRows, payloadCopyBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_requested_workers(), __latency_fn_structure_row_ids_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_pool_size(), __latency_fn_structure_row_ids_uint32_from_int(poolSize));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_selected(), php::ternary_eval([&]() -> decltype(auto) { return matches; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_coordinator_fallback(), php::ternary_eval([&]() -> decltype(auto) { return matches; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(coordinatorSourceReads->descriptor_count)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_result_rows(), __latency_fn_structure_row_ids_uint32_from_int(php::count(workerResults)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_metadata_matches(), php::ternary_eval([&]() -> decltype(auto) { return php::identical(metadataMismatches, static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_metadata_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(metadataMismatches));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_matches(), php::ternary_eval([&]() -> decltype(auto) { return php::identical(textHashMismatches, static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(textHashMismatches));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_elapsed_us(), elapsedUs);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_payload_copy_bytes(), payloadCopyBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_source_text_adopted(), php::ternary_eval([&]() -> decltype(auto) { return matches; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_o3_measurement_required(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_speedup_claim_blocked(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(matches))) {
		return workerSourceReads;
	}
	return coordinatorSourceReads;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceReadTable> __latency_fn_source_units_worker_published_source_read_table_from_descriptors(shared_p<CompilerProjectRunReport>& report, const shared_p<SourceReadTable>& descriptorSourceReads, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("source_units::worker_published_source_read_table_from_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[53]);
	if (static_cast<bool>(((workerCount <= static_cast<int_t<> >(0)) || (cast<int_t<>>(descriptorSourceReads->descriptor_count) <= static_cast<int_t<> >(0))))) {
		__latency_fn_source_units_record_source_read_worker_publication_not_selected(report, descriptorSourceReads, workerCount);
		return descriptorSourceReads;
	}
	vector_t<shared_p<SourceReadWorkerProbeInput>> inputs = required_cast<vector_t<shared_p<SourceReadWorkerProbeInput>>>(__latency_fn_source_units_source_read_worker_probe_inputs(descriptorSourceReads));
	int_t<> poolSize = required_cast<int_t<>>(__latency_fn_source_units_source_read_worker_pool_size());
	if (static_cast<bool>((poolSize > static_cast<int_t<> >(0)))) {
		tasks::configure_default_worker_pool(poolSize);
	}
	int_t<std::uint64_t> started = required_cast<int_t<std::uint64_t>>(__latency_fn_compiler_profile_events_now_us());
	shared_p<SourceReadTable> workerSourceReads = create<SourceReadTable>();
	__latency_fn_source_units_reserve_source_read_table(workerSourceReads, cast<int_t<>>(descriptorSourceReads->descriptor_count));
	shared_p<SourceReadWorkerPublicationStats> stats = create<SourceReadWorkerPublicationStats>();
	tasks::configure_publish_try_lock(__latency_fn_source_units_source_read_worker_try_lock_publication_enabled());
	int_t<> publishedCount = required_cast<int_t<>>(tasks::run_publish(inputs, workerCount, [](shared_p<SourceReadWorkerProbeInput> input) -> shared_p<SourceReadWorkerResult> {
	return __latency_fn_source_units_source_read_worker_result_for_input(input);
}, [&workerSourceReads, descriptorSourceReads, &stats](const vector_t<shared_p<SourceReadWorkerResult>>& batch) mutable -> void {
	__latency_fn_source_units_publish_source_read_worker_batch(workerSourceReads, descriptorSourceReads, batch, stats);
}, null, static_cast<int_t<> >(0), __latency_fn_source_units_source_read_worker_publish_batch_cap()));
	tasks::configure_publish_try_lock(static_cast<bool_t>(false));
	int_t<std::uint32_t> elapsedUs = required_cast<int_t<std::uint32_t>>(__latency_fn_compiler_profile_events_elapsed_us_since(started));
	int_t<> metadataMismatches = required_cast<int_t<>>(__latency_fn_source_units_source_read_descriptor_result_metadata_mismatch_count(descriptorSourceReads, workerSourceReads));
	int_t<> textHashMismatches = required_cast<int_t<>>(php::ternary_eval([&]() -> decltype(auto) { return __latency_fn_source_units_source_read_hash_match(workerSourceReads); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(1); }));
	bool_t matches = required_cast<bool_t>(((((php::identical(metadataMismatches, static_cast<int_t<> >(0)) && php::identical(textHashMismatches, static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(workerSourceReads->failure_count), static_cast<int_t<> >(0))) && php::identical(publishedCount, cast<int_t<>>(descriptorSourceReads->descriptor_count))) && php::identical(cast<int_t<>>(stats->published_results), cast<int_t<>>(descriptorSourceReads->descriptor_count))));
	int_t<std::uint32_t> payloadCopyBytes = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(php::ternary_eval([&]() -> decltype(auto) { return matches; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(workerSourceReads->result_count)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary(report, workerCount, matches, (!matches), static_cast<bool_t>(false), publishedRows, payloadCopyBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_requested_workers(), __latency_fn_structure_row_ids_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_pool_size(), __latency_fn_structure_row_ids_uint32_from_int(poolSize));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_selected(), php::ternary_eval([&]() -> decltype(auto) { return matches; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_coordinator_fallback(), php::ternary_eval([&]() -> decltype(auto) { return matches; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(descriptorSourceReads->descriptor_count)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_result_rows(), __latency_fn_structure_row_ids_uint32_from_int(publishedCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_metadata_matches(), php::ternary_eval([&]() -> decltype(auto) { return php::identical(metadataMismatches, static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_metadata_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(metadataMismatches));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_matches(), php::ternary_eval([&]() -> decltype(auto) { return php::identical(textHashMismatches, static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_source_text_hash_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(textHashMismatches));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_elapsed_us(), elapsedUs);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_batches(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(stats->publish_batches)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_largest_batch(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(stats->largest_publish_batch)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_lock_wait_us(), __latency_fn_structure_row_ids_uint32_from_int(tasks::publish_lock_wait_us()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_lock_hold_us(), __latency_fn_structure_row_ids_uint32_from_int(tasks::publish_lock_hold_us()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_callback_us(), __latency_fn_structure_row_ids_uint32_from_int(tasks::publish_callback_us()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_batches(), __latency_fn_structure_row_ids_uint32_from_int(tasks::publish_batch_count()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_published_rows(), __latency_fn_structure_row_ids_uint32_from_int(tasks::publish_published_count()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_largest_batch(), __latency_fn_structure_row_ids_uint32_from_int(tasks::publish_max_batch_size()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_failed_try_locks(), __latency_fn_structure_row_ids_uint32_from_int(tasks::publish_failed_try_lock_count()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_runtime_deferred_flushes(), __latency_fn_structure_row_ids_uint32_from_int(tasks::publish_deferred_flush_count()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_payload_copy_bytes(), payloadCopyBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_source_text_adopted(), php::ternary_eval([&]() -> decltype(auto) { return matches; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_o3_measurement_required(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_publication_speedup_claim_blocked(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(matches))) {
		return workerSourceReads;
	}
	return descriptorSourceReads;
}

}
