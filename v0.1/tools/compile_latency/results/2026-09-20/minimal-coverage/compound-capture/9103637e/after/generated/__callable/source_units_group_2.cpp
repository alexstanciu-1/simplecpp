#include <scpp/lang/php.hpp>
#include "__types/ProjectManifest.hpp"
#include "__types/ProjectManifestSourceRow.hpp"
#include "__types/SourceReadDescriptorRow.hpp"
#include "__types/SourceReadResultRow.hpp"
#include "__types/SourceReadTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_source_units_dirty_signal_source_text_or_manifest_id.hpp"
#include "__callable/__latency_fn_source_units_partition_source_unit_id.hpp"
#include "__callable/__latency_fn_source_units_reuse_signal_source_unit_rows_id.hpp"
#include "__callable/__latency_fn_source_units_table_row_from_source_read_result.hpp"
#include "__callable/__latency_fn_source_units_source_read_descriptor_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_source_units_line_count.hpp"
#include "__callable/__latency_fn_source_units_source_read_error_io_id.hpp"
#include "__callable/__latency_fn_source_units_source_read_error_none_id.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_row.hpp"
#include "__callable/__latency_fn_source_units_source_read_status_failed_id.hpp"
#include "__callable/__latency_fn_source_units_source_read_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_files_read_text.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_relative_path_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key_from_manifest.hpp"
#include "__callable/__latency_fn_source_units_reserve_source_read_table.hpp"
#include "__callable/__latency_fn_source_units_source_read_descriptor_row.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_row.hpp"
#include "__callable/__latency_fn_source_units_source_read_table_from_manifest.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_relative_path_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key_from_manifest.hpp"
#include "__callable/__latency_fn_source_units_reserve_source_read_table.hpp"
#include "__callable/__latency_fn_source_units_source_read_descriptor_row.hpp"
#include "__callable/__latency_fn_source_units_source_read_descriptor_table_from_manifest.hpp"
#include "__callable/__latency_fn_source_units_source_read_result_metadata_bytes.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_units_source_read_line_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
SourceUnitTableRow __latency_fn_source_units_table_row_from_source_read_result(SourceReadResultRow result) {
	SCPP_CALL_DEPTH_GUARD("source_units::table_row_from_source_read_result", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[25]);
	SourceUnitTableRow row = SourceUnitTableRow{};
	row->source_unit_id = result->source_id;
	row->language_id = result->language_id;
	row->status_id = result->status_id;
	row->dirty_signal_id = __latency_fn_source_units_dirty_signal_source_text_or_manifest_id();
	row->reuse_signal_id = __latency_fn_source_units_reuse_signal_source_unit_rows_id();
	row->partition_id = __latency_fn_source_units_partition_source_unit_id();
	row->source_unit_key_id = result->source_unit_key_id;
	row->relative_path_id = result->relative_path_id;
	row->path_id = result->path_id;
	row->source_length = result->source_length;
	row->line_count = result->line_count;
	return row;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
SourceReadDescriptorRow __latency_fn_source_units_source_read_descriptor_row(ProjectManifestSourceRow source, int_t<> manifestOrder) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_descriptor_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[26]);
	SourceReadDescriptorRow row = SourceReadDescriptorRow{};
	row->descriptor_id = __latency_fn_structure_row_ids_uint32_from_int(manifestOrder);
	row->source_id = source->source_id;
	row->manifest_order_id = __latency_fn_structure_row_ids_uint32_from_int(manifestOrder);
	row->language_id = source->language_id;
	row->status_id = source->status_id;
	row->source_unit_key_id = source->source_unit_key_id;
	row->relative_path_id = source->relative_path_id;
	row->path_id = source->relative_path_id;
	row->expected_source_unit_key_id = source->source_unit_key_id;
	return row;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
SourceReadResultRow __latency_fn_source_units_source_read_result_row(SourceReadDescriptorRow descriptor, const string_t& sourceText, bool_t readOk) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_result_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[27]);
	SourceReadResultRow row = SourceReadResultRow{};
	row->result_id = descriptor->descriptor_id;
	row->descriptor_id = descriptor->descriptor_id;
	row->source_id = descriptor->source_id;
	row->manifest_order_id = descriptor->manifest_order_id;
	row->language_id = descriptor->language_id;
	row->status_id = descriptor->status_id;
	row->read_status_id = php::ternary_eval([&]() -> decltype(auto) { return readOk; }, [&]() -> decltype(auto) { return __latency_fn_source_units_source_read_status_ready_id(); }, [&]() -> decltype(auto) { return __latency_fn_source_units_source_read_status_failed_id(); });
	row->error_status_id = php::ternary_eval([&]() -> decltype(auto) { return readOk; }, [&]() -> decltype(auto) { return __latency_fn_source_units_source_read_error_none_id(); }, [&]() -> decltype(auto) { return __latency_fn_source_units_source_read_error_io_id(); });
	row->source_unit_key_id = descriptor->source_unit_key_id;
	row->relative_path_id = descriptor->relative_path_id;
	row->path_id = descriptor->path_id;
	row->source_length = __latency_fn_structure_row_ids_uint32_from_int(str::byte_length(sourceText));
	row->line_count = __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_source_units_line_count(sourceText));
	row->content_hash = __latency_fn_source_buffers_content_hash32(sourceText);
	row->output_order_id = descriptor->manifest_order_id;
	return row;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceReadTable> __latency_fn_source_units_source_read_table_from_manifest(shared_p<ProjectManifest> manifest) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_table_from_manifest", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[28]);
	shared_p<SourceReadTable> table = create<SourceReadTable>();
	__latency_fn_source_units_reserve_source_read_table(table, manifest->source_count);
	int_t<> manifestOrder = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = manifest->sources;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto source = __latency_local_1.value_copy();
		string_t relativePath = required_cast<string_t>(__latency_fn_source_identity_manifest_source_relative_path_from_manifest(manifest, source));
		string_t path = required_cast<string_t>((cast<string_t>(manifest->project_dir) + string_t("/") + cast<string_t>(relativePath)));
		string_t sourceText = required_cast<string_t>(string_t(""));
		bool_t readOk = required_cast<bool_t>(__latency_fn_source_files_read_text(path, sourceText));
		SourceReadDescriptorRow descriptor = __latency_fn_source_units_source_read_descriptor_row(source, manifestOrder);
		SourceReadResultRow result = __latency_fn_source_units_source_read_result_row(descriptor, sourceText, bool_t(readOk));
		if (static_cast<bool>((!readOk))) {
			table->failure_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->failure_count) + static_cast<int_t<> >(1)));
		}
		table->source_byte_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->source_byte_count) + str::byte_length(sourceText)));
		(void) table->descriptors.append(descriptor);
		(void) table->results.append(result);
		{
		auto __latency_local_2 = __latency_fn_source_identity_manifest_source_unit_key_from_manifest(manifest, source);
		(void) table->source_unit_keys.append(__latency_local_2);
		}
		(void) table->relative_paths.append(relativePath);
		(void) table->paths.append(path);
		(void) table->source_texts.append(sourceText);
		manifestOrder = (manifestOrder + static_cast<int_t<> >(1));
	}
	table->descriptor_count = php::count(table->descriptors);
	table->result_count = php::count(table->results);
	return table;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceReadTable> __latency_fn_source_units_source_read_descriptor_table_from_manifest(shared_p<ProjectManifest> manifest) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_descriptor_table_from_manifest", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[29]);
	shared_p<SourceReadTable> table = create<SourceReadTable>();
	__latency_fn_source_units_reserve_source_read_table(table, manifest->source_count);
	int_t<> manifestOrder = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = manifest->sources;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto source = __latency_local_1.value_copy();
		string_t relativePath = required_cast<string_t>(__latency_fn_source_identity_manifest_source_relative_path_from_manifest(manifest, source));
		string_t path = required_cast<string_t>((cast<string_t>(manifest->project_dir) + string_t("/") + cast<string_t>(relativePath)));
		SourceReadDescriptorRow descriptor = __latency_fn_source_units_source_read_descriptor_row(source, manifestOrder);
		(void) table->descriptors.append(descriptor);
		{
		auto __latency_local_2 = __latency_fn_source_identity_manifest_source_unit_key_from_manifest(manifest, source);
		(void) table->source_unit_keys.append(__latency_local_2);
		}
		(void) table->relative_paths.append(relativePath);
		(void) table->paths.append(path);
		manifestOrder = (manifestOrder + static_cast<int_t<> >(1));
	}
	table->descriptor_count = php::count(table->descriptors);
	table->result_count = static_cast<int_t<> >(0);
	return table;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_units_source_read_result_metadata_bytes(shared_p<SourceReadTable> sourceReads) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_result_metadata_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[30]);
	int_t<> descriptorBytes = required_cast<int_t<>>((cast<int_t<>>(sourceReads->descriptor_count) * static_cast<int_t<> >(32)));
	int_t<> resultBytes = required_cast<int_t<>>((cast<int_t<>>(sourceReads->result_count) * static_cast<int_t<> >(52)));
	return __latency_fn_structure_row_ids_uint32_from_int((descriptorBytes + resultBytes));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_units_source_read_line_count(shared_p<SourceReadTable> sourceReads) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_line_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[31]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = sourceReads->results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(result->line_count));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}
