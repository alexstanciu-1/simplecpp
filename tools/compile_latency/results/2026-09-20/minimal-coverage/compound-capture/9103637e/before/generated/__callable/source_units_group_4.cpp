#include <scpp/lang/php.hpp>
#include "__types/SourceReadDescriptorRow.hpp"
#include "__types/SourceReadResultRow.hpp"
#include "__types/SourceReadTable.hpp"
#include "__types/SourceReadWorkerProbeInput.hpp"
#include "__types/SourceReadWorkerResult.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_row_from_worker_result.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_files_read_text.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_row.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_result_for_input.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_result_from_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_result_for_input.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_results.hpp"
#include "__callable/__latency_fn_source_units_source_read_error_io_id.hpp"
#include "__callable/__latency_fn_source_units_source_read_status_failed_id.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_result_for_manifest_order.hpp"
#include "__callable/__latency_fn_source_units_reserve_source_read_table.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_row_from_worker_result.hpp"
#include "__callable/__latency_fn_source_units_source_read_status_ready_id.hpp"
#include "__callable/__latency_fn_source_units_source_read_table_from_worker_results.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_result_for_manifest_order.hpp"
#include "__callable/__latency_fn_source_units_source_text_from_worker_result.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_units_append_source_read_worker_result.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_row_from_worker_result.hpp"
#include "__callable/__latency_fn_source_units_source_read_status_ready_id.hpp"
#include "__callable/__latency_fn_source_units_source_text_from_worker_result.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
SourceReadResultRow __latency_fn_source_units_source_read_result_row_from_worker_result(shared_p<SourceReadWorkerResult> result) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_result_row_from_worker_result", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[39]);
	SourceReadResultRow row = SourceReadResultRow{};
	row->result_id = __latency_fn_structure_row_ids_uint32_from_int(result->result_id);
	row->descriptor_id = __latency_fn_structure_row_ids_uint32_from_int(result->descriptor_id);
	row->source_id = __latency_fn_structure_row_ids_uint32_from_int(result->source_id);
	row->manifest_order_id = __latency_fn_structure_row_ids_uint32_from_int(result->manifest_order_id);
	row->language_id = __latency_fn_structure_row_ids_uint16_from_int(result->language_id);
	row->status_id = __latency_fn_structure_row_ids_uint16_from_int(result->status_id);
	row->read_status_id = __latency_fn_structure_row_ids_uint16_from_int(result->read_status_id);
	row->error_status_id = __latency_fn_structure_row_ids_uint16_from_int(result->error_status_id);
	row->source_unit_key_id = __latency_fn_structure_row_ids_uint32_from_int(result->source_unit_key_id);
	row->relative_path_id = __latency_fn_structure_row_ids_uint32_from_int(result->relative_path_id);
	row->path_id = __latency_fn_structure_row_ids_uint32_from_int(result->path_id);
	row->source_length = __latency_fn_structure_row_ids_uint32_from_int(result->source_length);
	row->line_count = __latency_fn_structure_row_ids_uint32_from_int(result->line_count);
	row->content_hash = __latency_fn_structure_row_ids_uint32_from_int(result->content_hash);
	row->output_order_id = __latency_fn_structure_row_ids_uint32_from_int(result->output_order_id);
	return row;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceReadWorkerResult> __latency_fn_source_units_source_read_worker_result_for_input(shared_p<SourceReadWorkerProbeInput> input) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_result_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[40]);
	SourceReadDescriptorRow descriptor = SourceReadDescriptorRow{};
	descriptor->descriptor_id = __latency_fn_structure_row_ids_uint32_from_int(input->descriptor_id);
	descriptor->source_id = __latency_fn_structure_row_ids_uint32_from_int(input->source_id);
	descriptor->manifest_order_id = __latency_fn_structure_row_ids_uint32_from_int(input->manifest_order_id);
	descriptor->language_id = __latency_fn_structure_row_ids_uint16_from_int(input->language_id);
	descriptor->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->status_id);
	descriptor->source_unit_key_id = __latency_fn_structure_row_ids_uint32_from_int(input->source_unit_key_id);
	descriptor->relative_path_id = __latency_fn_structure_row_ids_uint32_from_int(input->relative_path_id);
	descriptor->path_id = __latency_fn_structure_row_ids_uint32_from_int(input->path_id);
	descriptor->expected_source_unit_key_id = __latency_fn_structure_row_ids_uint32_from_int(input->expected_source_unit_key_id);
	string_t sourceText = required_cast<string_t>(string_t(""));
	bool_t readOk = required_cast<bool_t>(__latency_fn_source_files_read_text(input->path, sourceText));
	SourceReadResultRow row = __latency_fn_source_units_source_read_result_row(descriptor, sourceText, bool_t(readOk));
	return __latency_fn_source_units_source_read_worker_result_from_row(row, sourceText);
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
vector_t<shared_p<SourceReadWorkerResult>> __latency_fn_source_units_source_read_worker_results(const vector_t<shared_p<SourceReadWorkerProbeInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_results", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[41]);
	vector_t<shared_p<SourceReadWorkerResult>> results = required_cast<vector_t<shared_p<SourceReadWorkerResult>>>(tasks::run(inputs, workerCount, [](shared_p<SourceReadWorkerProbeInput> input) -> shared_p<SourceReadWorkerResult> {
	return __latency_fn_source_units_source_read_worker_result_for_input(input);
}));
	return results;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceReadWorkerResult> __latency_fn_source_units_source_read_worker_result_for_manifest_order(const vector_t<shared_p<SourceReadWorkerResult>>& workerResults, int_t<> manifestOrder) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_result_for_manifest_order", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[42]);
	auto& __latency_local_0 = workerResults;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(result->manifest_order_id), manifestOrder))) {
			return result;
		}
	}
	shared_p<SourceReadWorkerResult> missing = create<SourceReadWorkerResult>();
	missing->manifest_order_id = manifestOrder;
	missing->read_status_id = cast<int_t<>>(__latency_fn_source_units_source_read_status_failed_id());
	missing->error_status_id = cast<int_t<>>(__latency_fn_source_units_source_read_error_io_id());
	return missing;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceReadTable> __latency_fn_source_units_source_read_table_from_worker_results(const shared_p<SourceReadTable>& descriptorSourceReads, const vector_t<shared_p<SourceReadWorkerResult>>& workerResults) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_table_from_worker_results", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[43]);
	shared_p<SourceReadTable> table = create<SourceReadTable>();
	__latency_fn_source_units_reserve_source_read_table(table, cast<int_t<>>(descriptorSourceReads->descriptor_count));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = descriptorSourceReads->descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptor = __latency_local_1.value_copy();
		shared_p<SourceReadWorkerResult> workerResult = create<SourceReadWorkerResult>();
		if (static_cast<bool>((index < php::count(workerResults)))) {
			workerResult = workerResults.at(index);
		}
		if (static_cast<bool>(((index >= php::count(workerResults)) || php::not_identical(cast<int_t<>>(workerResult->manifest_order_id), cast<int_t<>>(descriptor->manifest_order_id))))) {
			workerResult = __latency_fn_source_units_source_read_worker_result_for_manifest_order(workerResults, cast<int_t<>>(descriptor->manifest_order_id));
		}
		SourceReadResultRow row = __latency_fn_source_units_source_read_result_row_from_worker_result(workerResult);
		string_t sourceText = required_cast<string_t>(__latency_fn_source_units_source_text_from_worker_result(workerResult));
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(workerResult->read_status_id), cast<int_t<>>(__latency_fn_source_units_source_read_status_ready_id()))))) {
			table->failure_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->failure_count) + static_cast<int_t<> >(1)));
		}
		table->source_byte_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->source_byte_count) + str::byte_length(sourceText)));
		(void) table->descriptors.append(descriptor);
		(void) table->results.append(row);
		{
		auto __latency_local_2 = descriptorSourceReads->source_unit_keys[index];
		(void) table->source_unit_keys.append(__latency_local_2);
		}
		{
		auto __latency_local_3 = descriptorSourceReads->relative_paths[index];
		(void) table->relative_paths.append(__latency_local_3);
		}
		{
		auto __latency_local_4 = descriptorSourceReads->paths[index];
		(void) table->paths.append(__latency_local_4);
		}
		(void) table->source_texts.append(sourceText);
		index = (index + static_cast<int_t<> >(1));
	}
	table->descriptor_count = php::count(table->descriptors);
	table->result_count = php::count(table->results);
	return table;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_append_source_read_worker_result(shared_p<SourceReadTable>& table, const shared_p<SourceReadTable>& descriptorSourceReads, shared_p<SourceReadWorkerResult> workerResult) {
	SCPP_CALL_DEPTH_GUARD("source_units::append_source_read_worker_result", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[44]);
	int_t<> index = required_cast<int_t<>>((cast<int_t<>>(workerResult->manifest_order_id) - static_cast<int_t<> >(1)));
	if (static_cast<bool>(((index < static_cast<int_t<> >(0)) || (index >= cast<int_t<>>(descriptorSourceReads->descriptor_count))))) {
		table->failure_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->failure_count) + static_cast<int_t<> >(1)));
		return;
	}
	SourceReadResultRow row = __latency_fn_source_units_source_read_result_row_from_worker_result(workerResult);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(workerResult->read_status_id), cast<int_t<>>(__latency_fn_source_units_source_read_status_ready_id()))))) {
		table->failure_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->failure_count) + static_cast<int_t<> >(1)));
	}
	string_t sourceText = required_cast<string_t>(__latency_fn_source_units_source_text_from_worker_result(workerResult));
	table->source_byte_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->source_byte_count) + str::byte_length(sourceText)));
	{
	auto __latency_local_0 = descriptorSourceReads->descriptors[index];
	(void) table->descriptors.append(__latency_local_0);
	}
	(void) table->results.append(row);
	{
	auto __latency_local_1 = descriptorSourceReads->source_unit_keys[index];
	(void) table->source_unit_keys.append(__latency_local_1);
	}
	{
	auto __latency_local_2 = descriptorSourceReads->relative_paths[index];
	(void) table->relative_paths.append(__latency_local_2);
	}
	{
	auto __latency_local_3 = descriptorSourceReads->paths[index];
	(void) table->paths.append(__latency_local_3);
	}
	(void) table->source_texts.append(sourceText);
	table->descriptor_count = php::count(table->descriptors);
	table->result_count = php::count(table->results);
}

}
