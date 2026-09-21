#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/SourceUnitFrontendWorkerBuildResult.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/source_units.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_source_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_source_byte_max.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_inputs_use_source_unit_sidecars.hpp"
#include "__callable/__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_multi_worker_coordinator_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_one_worker_coordinator_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_publication_requested_workers.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_published_results.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_worker_publication_selected.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_single_pipeline_publication_summary_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_input_sidecar_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_input_source_text_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_live_result_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_local_worker_payload_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_locked_publication_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_published_results.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_task_value_boundary_open.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_copy_boundary_audit_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
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
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_real_worker_publication_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_single_pipeline_publication_summary_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_copy_boundary_audit_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_source_byte_max.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_input_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_inputs_use_source_unit_sidecars.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us_max.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_parser_elapsed_us_max.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_parser_elapsed_us_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_symbol_elapsed_us_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_tokenizer_elapsed_us_total.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_source_byte_total(shared_p<SourceUnitTable> sourceUnits) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_unit_source_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[85]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(sourceUnit->source_length));
	}
	return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_source_byte_max(shared_p<SourceUnitTable> sourceUnits) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_unit_source_byte_max", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[86]);
	int_t<> max = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(sourceUnit->source_length) > max))) {
			max = cast<int_t<>>(sourceUnit->source_length);
		}
	}
	return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(max);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_inputs_use_source_unit_sidecars(const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_inputs_use_source_unit_sidecars", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[87]);
	if (static_cast<bool>(php::identical(php::count(inputs), static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	auto& __latency_local_0 = inputs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto input = __latency_local_1.value_copy();
		if (static_cast<bool>(((cast<int_t<>>(input->source_units->source_unit_count) <= static_cast<int_t<> >(0)) || php::not_identical(str::byte_length(input->source_text), static_cast<int_t<> >(0))))) {
			return bool_t(static_cast<bool_t>(false));
		}
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_single_pipeline_publication_summary_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> workerCount, bool_t selected, int_t<std::uint32_t> publishedResults, int_t<std::uint32_t> payloadCopyBytes) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_single_pipeline_publication_summary_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[88]);
	int_t<std::uint32_t> summaryPublishedResults = required_cast<int_t<std::uint32_t>>(php::ternary_eval([&]() -> decltype(auto) { return selected; }, [&]() -> decltype(auto) { return publishedResults; }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_mt_single_pipeline_live_publication_summary(report, workerCount, selected, (!selected), static_cast<bool_t>(false), summaryPublishedResults, payloadCopyBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_publication_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_publication_requested_workers(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_worker_publication_selected(), php::ternary_eval([&]() -> decltype(auto) { return selected; }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)); }));
	if (static_cast<bool>((selected && php::identical(workerCount, static_cast<int_t<> >(1))))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_one_worker_coordinator_selected(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_multi_worker_coordinator_selected(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		if (static_cast<bool>((selected && (workerCount > static_cast<int_t<> >(1))))) {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_one_worker_coordinator_selected(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_multi_worker_coordinator_selected(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		}
		else {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_one_worker_coordinator_selected(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_multi_worker_coordinator_selected(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
		}
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_published_results(), publishedResults);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_single_pipeline_payload_copy_bytes(), payloadCopyBytes);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_worker_copy_boundary_audit_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> inputRows, int_t<> publishedResults, int_t<std::uint32_t> inputSourceTextBytes, int_t<std::uint32_t> liveResultPayloadCopyBytes, int_t<std::uint32_t> localWorkerPayloadBytes, bool_t inputSidecarSelected, bool_t lockedPublicationSelected) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_worker_copy_boundary_audit_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[89]);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_input_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(inputRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_published_results(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(publishedResults));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_input_source_text_bytes(), inputSourceTextBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_live_result_payload_copy_bytes(), liveResultPayloadCopyBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_local_worker_payload_bytes(), localWorkerPayloadBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_input_sidecar_selected(), php::ternary_eval([&]() -> decltype(auto) { return inputSidecarSelected; }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_locked_publication_selected(), php::ternary_eval([&]() -> decltype(auto) { return lockedPublicationSelected; }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_copy_boundary_audit_task_value_boundary_open(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_real_worker_publication_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs, const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results, int_t<> workerCount, bool_t selected, int_t<std::uint32_t> taskRunElapsedUs, int_t<std::uint32_t> frontendPublishElapsedUs, int_t<std::uint32_t> symbolPublishElapsedUs, int_t<std::uint32_t> publicationElapsedUs, int_t<std::uint32_t> totalElapsedUs) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_real_worker_publication_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[90]);
	int_t<std::uint32_t> resultPayloadCopyBytes = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_payload_copy_byte_total(results)));
	int_t<std::uint32_t> inputCopyBytes = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_input_payload_copy_byte_total(inputs)));
	__latency_fn_resident_source_unit_frontend_payload_tables_record_single_pipeline_publication_summary_metrics(report, workerCount, bool_t(selected), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(results)), cast<int_t<std::uint32_t>>(resultPayloadCopyBytes));
	__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_copy_boundary_audit_metrics(report, php::count(inputs), php::count(results), cast<int_t<std::uint32_t>>(inputCopyBytes), cast<int_t<std::uint32_t>>(resultPayloadCopyBytes), cast<int_t<std::uint32_t>>(resultPayloadCopyBytes), __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_inputs_use_source_unit_sidecars(inputs), bool_t(static_cast<bool_t>(false)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_requested_workers(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_selected(), php::ternary_eval([&]() -> decltype(auto) { return selected; }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_inputs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(cast<int_t<>>(sourceUnits->source_unit_count)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_results(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_input_copy_bytes(), inputCopyBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_result_payload_copy_bytes(), resultPayloadCopyBytes);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_tokenizer_worker_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_tokenizer_elapsed_us_total(results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_parser_worker_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_parser_elapsed_us_total(results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_symbol_worker_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_symbol_elapsed_us_total(results)));
	int_t<> resultWorkerUs = required_cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us_total(results));
	int_t<> maxResultUs = required_cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us_max(results));
	int_t<> avgResultUs = required_cast<int_t<>>(php::ternary_eval([&]() -> decltype(auto) { return (php::count(results) > static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return cast<int_t<>>((resultWorkerUs / php::count(results))); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }));
	int_t<> tailGapUs = required_cast<int_t<>>(php::ternary_eval([&]() -> decltype(auto) { return (cast<int_t<>>(taskRunElapsedUs) > maxResultUs); }, [&]() -> decltype(auto) { return (cast<int_t<>>(taskRunElapsedUs) - maxResultUs); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); }));
	int_t<> efficiencyDenominator = required_cast<int_t<>>((cast<int_t<>>(taskRunElapsedUs) * workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_result_worker_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(resultWorkerUs));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_avg_result_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(avgResultUs));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_result_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(maxResultUs));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_result_parser_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_parser_elapsed_us_max(results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_result_source_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_source_byte_max(sourceUnits));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_tail_gap_us(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(tailGapUs));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_parallel_efficiency_x100(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::ternary_eval([&]() -> decltype(auto) { return (efficiencyDenominator > static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return cast<int_t<>>(((resultWorkerUs * static_cast<int_t<> >(100)) / efficiencyDenominator)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); })));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_max_to_avg_result_x100(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::ternary_eval([&]() -> decltype(auto) { return (avgResultUs > static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return cast<int_t<>>(((maxResultUs * static_cast<int_t<> >(100)) / avgResultUs)); }, [&]() -> decltype(auto) { return static_cast<int_t<> >(0); })));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_task_run_elapsed_us(), taskRunElapsedUs);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_frontend_publish_elapsed_us(), frontendPublishElapsedUs);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_symbol_publish_elapsed_us(), symbolPublishElapsedUs);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_publication_elapsed_us(), publicationElapsedUs);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_real_worker_publication_total_elapsed_us(), totalElapsedUs);
}

}
