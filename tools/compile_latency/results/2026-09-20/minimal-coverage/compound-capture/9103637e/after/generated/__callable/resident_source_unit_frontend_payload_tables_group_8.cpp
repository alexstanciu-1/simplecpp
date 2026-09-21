#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/SourceUnitFrontendWorkerLockedPublicationStats.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_avg_result_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_frontend_publish_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_input_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_result_parser_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_result_source_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_result_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_to_avg_result_x100.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_parallel_efficiency_x100.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_parser_worker_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_publication_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_requested_workers.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_result_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_result_worker_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_results.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_symbol_publish_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_symbol_worker_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_tail_gap_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_task_run_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_tokenizer_worker_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_total_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_batches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_largest_batch.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_local_payload_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_locked_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_published_results.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_batches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_callback_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_deferred_flushes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_failed_try_locks.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_largest_batch.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_lock_hold_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_lock_wait_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_published_results.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_selected.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_real_worker_locked_publication_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_single_pipeline_publication_summary_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_copy_boundary_audit_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_input_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_inputs_use_source_unit_sidecars.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_for_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptors.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_for_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptors_sequential.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_texts_for_payload_tables.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_owner_kind_worker_local_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_id_for_source_unit.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_segments.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_nodes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_parser_errors.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_segments.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_table_row_from_descriptor.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_real_worker_locked_publication_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs, shared_p<SourceUnitFrontendWorkerLockedPublicationStats> stats, int_t<> workerCount, bool_t selected, int_t<std::uint32_t> taskRunElapsedUs, int_t<std::uint32_t> totalElapsedUs) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_real_worker_locked_publication_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[91]);
	int_t<std::uint32_t> inputCopyBytes = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_input_payload_copy_byte_total(inputs)));
	__latency_fn_resident_source_unit_frontend_payload_tables_record_single_pipeline_publication_summary_metrics(report, workerCount, bool_t(selected), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->published_results)), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_copy_boundary_audit_metrics(report, php::count(inputs), cast<int_t<>>(stats->published_results), cast<int_t<std::uint32_t>>(inputCopyBytes), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->result_payload_copy_bytes)), __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_inputs_use_source_unit_sidecars(inputs), bool_t(selected));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_requested_workers(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_selected(), php::ternary_eval([&]() -> decltype(auto) { return selected; }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_inputs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(sourceUnits->source_unit_count)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_results(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->published_results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_input_copy_bytes(), inputCopyBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_result_payload_copy_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_tokenizer_worker_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->tokenizer_worker_us)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_parser_worker_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->parser_worker_us)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_symbol_worker_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->symbol_worker_us)));
	int_t<> avgResultUs = required_cast<int_t<>>(php::ternary_eval([&]() -> decltype(auto) { return (cast<int_t<>>(stats->published_results) > static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return cast<int_t<>>((cast<int_t<>>(stats->result_worker_us) / cast<int_t<>>(stats->published_results))); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }));
	int_t<> tailGapUs = required_cast<int_t<>>(php::ternary_eval([&]() -> decltype(auto) { return (cast<int_t<>>(taskRunElapsedUs) > cast<int_t<>>(stats->max_result_us)); }, [&]() -> decltype(auto) { return (cast<int_t<>>(taskRunElapsedUs) - cast<int_t<>>(stats->max_result_us)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }));
	int_t<> efficiencyDenominator = required_cast<int_t<>>((cast<int_t<>>(taskRunElapsedUs) * workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_result_worker_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->result_worker_us)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_avg_result_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(avgResultUs));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_result_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->max_result_us)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_result_parser_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->max_result_parser_us)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_result_source_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->max_result_source_bytes)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_tail_gap_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tailGapUs));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_parallel_efficiency_x100(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::ternary_eval([&]() -> decltype(auto) { return (efficiencyDenominator > static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return cast<int_t<>>(((cast<int_t<>>(stats->result_worker_us) * static_cast<int_t<> >(100)) / efficiencyDenominator)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); })));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_to_avg_result_x100(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::ternary_eval([&]() -> decltype(auto) { return (avgResultUs > static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return cast<int_t<>>(((cast<int_t<>>(stats->max_result_us) * static_cast<int_t<> >(100)) / avgResultUs)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); })));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_task_run_elapsed_us(), taskRunElapsedUs);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_frontend_publish_elapsed_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->frontend_publish_elapsed_us)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_symbol_publish_elapsed_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->symbol_publish_elapsed_us)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_publication_elapsed_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->locked_publish_elapsed_us)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_total_elapsed_us(), totalElapsedUs);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_selected(), php::ternary_eval([&]() -> decltype(auto) { return selected; }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_batches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->publish_batches)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_published_results(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->published_results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_largest_batch(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->largest_publish_batch)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_locked_elapsed_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->locked_publish_elapsed_us)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_local_payload_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(stats->result_payload_copy_bytes)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_lock_wait_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tasks::publish_lock_wait_us()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_lock_hold_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tasks::publish_lock_hold_us()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_callback_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tasks::publish_callback_us()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_batches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tasks::publish_batch_count()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_published_results(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tasks::publish_published_count()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_largest_batch(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tasks::publish_max_batch_size()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_failed_try_locks(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tasks::publish_failed_try_lock_count()));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_locked_publication_runtime_deferred_flushes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tasks::publish_deferred_flush_count()));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<int_t<>> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptors(const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[92]);
	vector_t<int_t<>> result = required_cast<vector_t<int_t<>>>(tasks::run(inputs, workerCount, [](shared_p<SourceUnitFrontendWorkerPayloadInput> input) -> int_t<> {
	return __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_for_input(input);
}));
	return result;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<int_t<>> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptors_sequential(const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptors_sequential", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[93]);
	vector_t<int_t<>> result = {};
	php::vector_reserve(result, php::count(inputs));
	auto& __latency_local_0 = inputs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto input = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_for_input(input);
		(void) result.push_back(__latency_local_2);
		}
	}
	return result;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<string_t> __latency_fn_resident_source_unit_frontend_payload_tables_source_texts_for_payload_tables(shared_p<SourceUnitTable> sourceUnits, const vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_texts_for_payload_tables", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[94]);
	vector_t<string_t> sourceTexts = {};
	php::vector_reserve(sourceTexts, php::count(payloadTables));
	auto& __latency_local_0 = payloadTables;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto payloadTable = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_source_units_source_text_by_id(sourceUnits, payloadTable->source_unit_id);
		(void) sourceTexts.push_back(__latency_local_2);
		}
	}
	return sourceTexts;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_table_row_from_descriptor(ResidentSourceUnitFrontendPayloadTableRow coordinatorHandle, int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_table_row_from_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[95]);
	ResidentSourceUnitFrontendPayloadTableRow row = ResidentSourceUnitFrontendPayloadTableRow{};
	row->payload_table_id = coordinatorHandle->payload_table_id;
	row->owner_run_id = coordinatorHandle->owner_run_id;
	row->source_unit_id = coordinatorHandle->source_unit_id;
	row->source_unit_key_id = coordinatorHandle->source_unit_key_id;
	row->worker_id = __latency_fn_resident_source_unit_frontend_payload_tables_worker_id_for_source_unit(coordinatorHandle->source_unit_id);
	row->input_snapshot_generation = coordinatorHandle->input_snapshot_generation;
	row->token_list_id = coordinatorHandle->token_list_id;
	row->token_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_rows(descriptor));
	row->token_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_segments(descriptor));
	row->token_reserved_segment_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->token_segment_slack_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->frontend_node_list_id = coordinatorHandle->frontend_node_list_id;
	row->frontend_node_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_nodes(descriptor));
	row->frontend_node_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_segments(descriptor));
	row->frontend_node_reserved_segment_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->frontend_node_segment_slack_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->parser_error_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_parser_errors(descriptor));
	row->payload_owner_kind_id = __latency_fn_resident_source_unit_frontend_payload_tables_owner_kind_worker_local_id();
	row->status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
	return row;
}

}
