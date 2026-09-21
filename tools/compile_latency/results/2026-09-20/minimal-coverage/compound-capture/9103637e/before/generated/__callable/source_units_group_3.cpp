#include <scpp/lang/php.hpp>
#include "__types/SourceReadDescriptorRow.hpp"
#include "__types/SourceReadResultRow.hpp"
#include "__types/SourceReadTable.hpp"
#include "__types/SourceReadWorkerProbeInput.hpp"
#include "__types/SourceReadWorkerResult.hpp"
#include "__callable/__latency_fn_source_units_source_read_manifest_order_match.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_source_units_source_read_hash_match.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_signature.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_input.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_input.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_inputs.hpp"
#include "__callable/__latency_fn_source_files_read_text.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_row.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_signature.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_probe_descriptor_for_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_result_from_row__exec.hpp"
#include "__callable/__latency_fn_source_units_source_read_status_ready_id.hpp"
#include "__callable/__latency_fn_source_units_source_text_from_worker_result.hpp"
namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
bool_t __latency_fn_source_units_source_read_manifest_order_match(const shared_p<SourceReadTable>& sourceReads) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_manifest_order_match", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[32]);
	int_t<> expectedOrder = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = sourceReads->results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>((php::not_identical(cast<int_t<>>(result->manifest_order_id), expectedOrder) || php::not_identical(cast<int_t<>>(result->output_order_id), expectedOrder)))) {
			return bool_t(static_cast<bool_t>(false));
		}
		expectedOrder = (expectedOrder + static_cast<int_t<> >(1));
	}
	return bool_t(php::identical(expectedOrder, (cast<int_t<>>(sourceReads->result_count) + static_cast<int_t<> >(1))));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
bool_t __latency_fn_source_units_source_read_hash_match(const shared_p<SourceReadTable>& sourceReads) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_hash_match", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[33]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = sourceReads->results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy((index >= php::count(sourceReads->source_texts))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(result->content_hash), cast<int_t<>>(__latency_fn_source_buffers_content_hash32(sourceReads->source_texts[index])))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(php::identical(index, cast<int_t<>>(sourceReads->result_count)));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_source_read_result_signature(SourceReadResultRow result) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_result_signature", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[34]);
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(17));
	int_t<> mod = required_cast<int_t<>>(static_cast<int_t<> >(2147483647));
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->source_id)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->manifest_order_id)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->language_id)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->status_id)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->read_status_id)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->error_status_id)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->source_unit_key_id)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->relative_path_id)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->path_id)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->source_length)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->line_count)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->content_hash)) % mod);
	hash = (((hash * static_cast<int_t<> >(131)) + cast<int_t<>>(result->output_order_id)) % mod);
	return hash;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceReadWorkerProbeInput> __latency_fn_source_units_source_read_worker_probe_input(SourceReadDescriptorRow descriptor, const string_t& path) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_probe_input", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[35]);
	shared_p<SourceReadWorkerProbeInput> input = create<SourceReadWorkerProbeInput>();
	input->descriptor_id = cast<int_t<>>(descriptor->descriptor_id);
	input->source_id = cast<int_t<>>(descriptor->source_id);
	input->manifest_order_id = cast<int_t<>>(descriptor->manifest_order_id);
	input->language_id = cast<int_t<>>(descriptor->language_id);
	input->status_id = cast<int_t<>>(descriptor->status_id);
	input->source_unit_key_id = cast<int_t<>>(descriptor->source_unit_key_id);
	input->relative_path_id = cast<int_t<>>(descriptor->relative_path_id);
	input->path_id = cast<int_t<>>(descriptor->path_id);
	input->expected_source_unit_key_id = cast<int_t<>>(descriptor->expected_source_unit_key_id);
	input->path = path;
	return input;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
vector_t<shared_p<SourceReadWorkerProbeInput>> __latency_fn_source_units_source_read_worker_probe_inputs(const shared_p<SourceReadTable>& sourceReads) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_probe_inputs", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[36]);
	vector_t<shared_p<SourceReadWorkerProbeInput>> inputs = {};
	php::vector_reserve(inputs, cast<int_t<>>(sourceReads->descriptor_count));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = sourceReads->descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptor = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_source_units_source_read_worker_probe_input(descriptor, sourceReads->paths[index]);
		(void) inputs.push_back(__latency_local_2);
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return inputs;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_source_read_worker_probe_descriptor_for_input(shared_p<SourceReadWorkerProbeInput> input) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_probe_descriptor_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[37]);
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
	SourceReadResultRow result = __latency_fn_source_units_source_read_result_row(descriptor, sourceText, bool_t(readOk));
	return __latency_fn_source_units_source_read_result_signature(result);
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceReadWorkerResult> __latency_fn_source_units_source_read_worker_result_from_row__exec(SourceReadResultRow row, string_t& sourceText) {
	shared_p<SourceReadWorkerResult> result = create<SourceReadWorkerResult>();
	result->result_id = cast<int_t<>>(row->result_id);
	result->descriptor_id = cast<int_t<>>(row->descriptor_id);
	result->source_id = cast<int_t<>>(row->source_id);
	result->manifest_order_id = cast<int_t<>>(row->manifest_order_id);
	result->language_id = cast<int_t<>>(row->language_id);
	result->status_id = cast<int_t<>>(row->status_id);
	result->read_status_id = cast<int_t<>>(row->read_status_id);
	result->error_status_id = cast<int_t<>>(row->error_status_id);
	result->source_unit_key_id = cast<int_t<>>(row->source_unit_key_id);
	result->relative_path_id = cast<int_t<>>(row->relative_path_id);
	result->path_id = cast<int_t<>>(row->path_id);
	result->source_length = cast<int_t<>>(row->source_length);
	result->line_count = cast<int_t<>>(row->line_count);
	result->content_hash = cast<int_t<>>(row->content_hash);
	result->output_order_id = cast<int_t<>>(row->output_order_id);
	result->source_buffer = source::source_buffer_take(sourceText);
	return result;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
string_t __latency_fn_source_units_source_text_from_worker_result(shared_p<SourceReadWorkerResult> workerResult) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_text_from_worker_result", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[38]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(workerResult->read_status_id), cast<int_t<>>(__latency_fn_source_units_source_read_status_ready_id())) && php::identical(cast<int_t<>>(source::source_buffer_byte_len(workerResult->source_buffer)), cast<int_t<>>(workerResult->source_length))))) {
		string_t sourceText = required_cast<string_t>(source::source_buffer_release(workerResult->source_buffer));
		workerResult->source_text = sourceText;
		return sourceText;
	}
	return workerResult->source_text;
}

}
