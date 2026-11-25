#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/ProjectManifest.hpp"
#include "__types/ProjectManifestSourceRow.hpp"
#include "__types/SourceReadResultRow.hpp"
#include "__types/SourceReadTable.hpp"
#include "__types/SourceReadWorkerProbeInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_compiler_profile_events_elapsed_us_since.hpp"
#include "__callable/__latency_fn_compiler_profile_events_now_us.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_blocked_by_worker_count.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_metadata_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_metadata_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_o3_measurement_required.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_production_publication_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_requested_workers.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_result_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_source_text_adopted.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_worker_probe_speedup_claim_blocked.hpp"
#include "__callable/__latency_fn_source_units_record_source_read_worker_probe_metrics.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_descriptors.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_inputs.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_mismatch_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_append_unique_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_elapsed_us.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_failure_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_hash_audit_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_hash_match.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_light_metrics_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_line_count.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_manifest_order_match.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_result_metadata_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_result_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_source_bytes.hpp"
#include "__callable/__latency_fn_source_units_record_source_read_metrics.hpp"
#include "__callable/__latency_fn_source_units_source_read_hash_audit_enabled.hpp"
#include "__callable/__latency_fn_source_units_source_read_hash_match.hpp"
#include "__callable/__latency_fn_source_units_source_read_light_metrics_enabled.hpp"
#include "__callable/__latency_fn_source_units_source_read_line_count.hpp"
#include "__callable/__latency_fn_source_units_source_read_manifest_order_match.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_metadata_bytes.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_manifest_entry_source.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key_from_manifest.hpp"
#include "__callable/__latency_fn_source_units_reserve_table.hpp"
#include "__callable/__latency_fn_source_units_scope_project_manifest_sources_id.hpp"
#include "__callable/__latency_fn_source_units_table_from_source_read_table.hpp"
#include "__callable/__latency_fn_source_units_table_row_from_source_read_result.hpp"
#include "__callable/__latency_fn_project_manifest_entry_source.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key_from_manifest.hpp"
#include "__callable/__latency_fn_source_units_reserve_table.hpp"
#include "__callable/__latency_fn_source_units_scope_project_manifest_sources_id.hpp"
#include "__callable/__latency_fn_source_units_table_from_source_read_table_adopting_texts.hpp"
#include "__callable/__latency_fn_source_units_table_row_from_source_read_result.hpp"
namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_record_source_read_worker_probe_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceReadTable> sourceReads, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("source_units::record_source_read_worker_probe_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[54]);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_requested_workers(), __latency_fn_structure_row_ids_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(sourceReads->descriptor_count)));
	if (static_cast<bool>(((workerCount <= static_cast<int_t<> >(1)) || (cast<int_t<>>(sourceReads->descriptor_count) <= static_cast<int_t<> >(1))))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_selected(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_blocked_by_worker_count(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_result_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_metadata_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_metadata_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_elapsed_us(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_source_text_adopted(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_production_publication_blocked(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_o3_measurement_required(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_speedup_claim_blocked(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		return;
	}
	vector_t<shared_p<SourceReadWorkerProbeInput>> inputs = required_cast<vector_t<shared_p<SourceReadWorkerProbeInput>>>(__latency_fn_source_units_source_read_worker_probe_inputs(sourceReads));
	int_t<std::uint64_t> started = required_cast<int_t<std::uint64_t>>(__latency_fn_compiler_profile_events_now_us());
	vector_t<int_t<>> workerDescriptors = required_cast<vector_t<int_t<>>>(__latency_fn_source_units_source_read_worker_probe_descriptors(inputs, workerCount));
	int_t<std::uint32_t> elapsedUs = required_cast<int_t<std::uint32_t>>(__latency_fn_compiler_profile_events_elapsed_us_since(started));
	int_t<> mismatches = required_cast<int_t<>>(__latency_fn_source_units_source_read_worker_probe_mismatch_count(sourceReads, workerDescriptors));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_selected(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_blocked_by_worker_count(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_result_rows(), __latency_fn_structure_row_ids_uint32_from_int(php::count(workerDescriptors)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_metadata_matches(), php::ternary_eval([&]() -> decltype(auto) { return php::identical(mismatches, static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_metadata_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(mismatches));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_elapsed_us(), elapsedUs);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_source_text_adopted(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_production_publication_blocked(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_o3_measurement_required(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_read_worker_probe_speedup_claim_blocked(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_record_source_read_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceReadTable> sourceReads, int_t<std::uint32_t> elapsedUs) {
	SCPP_CALL_DEPTH_GUARD("source_units::record_source_read_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[55]);
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(sourceReads->descriptor_count)));
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_result_rows(), __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(sourceReads->result_count)));
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_source_bytes(), sourceReads->source_byte_count);
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_failure_rows(), sourceReads->failure_count);
	bool_t hashAuditSelected = required_cast<bool_t>(__latency_fn_source_units_source_read_hash_audit_enabled());
	bool_t lightMetricsSelected = required_cast<bool_t>(__latency_fn_source_units_source_read_light_metrics_enabled());
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_light_metrics_selected(), php::ternary_eval([&]() -> decltype(auto) { return lightMetricsSelected; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	if (static_cast<bool>((!lightMetricsSelected))) {
		__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_line_count(), __latency_fn_source_units_source_read_line_count(sourceReads));
		__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_result_metadata_bytes(), __latency_fn_source_units_source_read_result_metadata_bytes(sourceReads));
		__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_manifest_order_match(), php::ternary_eval([&]() -> decltype(auto) { return __latency_fn_source_units_source_read_manifest_order_match(sourceReads); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_none_id(); }));
	}
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_hash_audit_selected(), php::ternary_eval([&]() -> decltype(auto) { return hashAuditSelected; }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)); }));
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_hash_match(), php::ternary_eval([&]() -> decltype(auto) { return (((!lightMetricsSelected) && hashAuditSelected) && __latency_fn_source_units_source_read_hash_match(sourceReads)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_none_id(); }));
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_elapsed_us(), elapsedUs);
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_payload_copy_bytes(), __latency_fn_structure_row_ids_none_id());
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceUnitTable> __latency_fn_source_units_table_from_source_read_table(shared_p<ProjectManifest> manifest, shared_p<SourceReadTable> sourceReads) {
	SCPP_CALL_DEPTH_GUARD("source_units::table_from_source_read_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[56]);
	shared_p<SourceUnitTable> table = create<SourceUnitTable>();
	table->scope_id = __latency_fn_source_units_scope_project_manifest_sources_id();
	__latency_fn_source_units_reserve_table(table, sourceReads->result_count);
	ProjectManifestSourceRow entry = __latency_fn_project_manifest_entry_source(manifest);
	table->entry_source_unit_key = __latency_fn_source_identity_manifest_source_unit_key_from_manifest(manifest, entry);
	auto __latency_local_0 = sourceReads->results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		int_t<> index = required_cast<int_t<>>((cast<int_t<>>(result->manifest_order_id) - static_cast<int_t<> >(1)));
		{
		auto __latency_local_2 = sourceReads->source_unit_keys[index];
		(void) table->source_unit_keys.append(__latency_local_2);
		}
		{
		auto __latency_local_3 = sourceReads->relative_paths[index];
		(void) table->relative_paths.append(__latency_local_3);
		}
		{
		auto __latency_local_4 = sourceReads->paths[index];
		(void) table->paths.append(__latency_local_4);
		}
		{
		auto __latency_local_5 = sourceReads->source_texts[index];
		(void) table->source_texts.append(__latency_local_5);
		}
		{
		auto __latency_local_6 = __latency_fn_source_units_table_row_from_source_read_result(result);
		(void) table->rows.append(__latency_local_6);
		}
	}
	table->source_unit_count = php::count(table->rows);
	return table;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceUnitTable> __latency_fn_source_units_table_from_source_read_table_adopting_texts(shared_p<ProjectManifest> manifest, shared_p<SourceReadTable>& sourceReads) {
	SCPP_CALL_DEPTH_GUARD("source_units::table_from_source_read_table_adopting_texts", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[57]);
	shared_p<SourceUnitTable> table = create<SourceUnitTable>();
	table->scope_id = __latency_fn_source_units_scope_project_manifest_sources_id();
	__latency_fn_source_units_reserve_table(table, sourceReads->result_count);
	ProjectManifestSourceRow entry = __latency_fn_project_manifest_entry_source(manifest);
	table->entry_source_unit_key = __latency_fn_source_identity_manifest_source_unit_key_from_manifest(manifest, entry);
	auto __latency_local_0 = sourceReads->results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		int_t<> index = required_cast<int_t<>>((cast<int_t<>>(result->manifest_order_id) - static_cast<int_t<> >(1)));
		{
		auto __latency_local_2 = sourceReads->source_unit_keys[index];
		(void) table->source_unit_keys.append(__latency_local_2);
		}
		{
		auto __latency_local_3 = sourceReads->relative_paths[index];
		(void) table->relative_paths.append(__latency_local_3);
		}
		{
		auto __latency_local_4 = sourceReads->paths[index];
		(void) table->paths.append(__latency_local_4);
		}
		source::source_text_vector_move_append(table->source_texts, sourceReads->source_texts, index);
		{
		auto __latency_local_5 = __latency_fn_source_units_table_row_from_source_read_result(result);
		(void) table->rows.append(__latency_local_5);
		}
	}
	table->source_unit_count = php::count(table->rows);
	return table;
}

}
