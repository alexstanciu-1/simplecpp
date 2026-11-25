#include <scpp/lang/php.hpp>
#include "__types/ArtifactWriteRecord.hpp"
#include "__types/ArtifactWriteReportRecord.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__types/PipelineConfig.hpp"
#include "__callable/__latency_fn_artifact_writes_append_record.hpp"
#include "__callable/__latency_fn_artifact_writes_new_report.hpp"
#include "__callable/__latency_fn_artifact_writes_record_from_llvm_text_preflight.hpp"
#include "__callable/__latency_fn_artifact_writes_report_from_llvm_text_preflight.hpp"
#include "__callable/__latency_fn_artifact_writes_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_artifact_writes_equals.hpp"
#include "__callable/__latency_fn_artifact_writes_artifact_key_name.hpp"
#include "__callable/__latency_fn_artifact_writes_debug_string.hpp"
#include "__callable/__latency_fn_artifact_writes_path_key_name.hpp"
#include "__callable/__latency_fn_artifact_writes_status_name.hpp"
#include "__callable/__latency_fn_artifact_writes_report_debug_string.hpp"
#include "__callable/__latency_fn_artifact_writes_stable_hash.hpp"
#include "__callable/__latency_fn_artifact_writes_report_stable_hash.hpp"
#include "__callable/__latency_fn_artifact_writes_artifact_key_name.hpp"
#include "__callable/__latency_fn_artifact_writes_path_from_config.hpp"
#include "__callable/__latency_fn_artifact_writes_path_key_name.hpp"
#include "__callable/__latency_fn_artifact_writes_report_artifact_key_name.hpp"
#include "__callable/__latency_fn_artifact_writes_report_artifact_kind_name.hpp"
#include "__callable/__latency_fn_artifact_writes_selected_report_json.hpp"
#include "__callable/__latency_fn_artifact_writes_status_name.hpp"
#include "__callable/__latency_fn_artifact_writes_write_policy_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
shared_p<ArtifactWriteReportRecord> __latency_fn_artifact_writes_report_from_llvm_text_preflight(shared_p<PipelineConfig> config, FunctionBodyTextEmissionPreflightRow preflight, const string_t& moduleText) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::report_from_llvm_text_preflight", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[24]);
	shared_p<ArtifactWriteReportRecord> report = __latency_fn_artifact_writes_new_report(static_cast<int_t<> >(1));
	ArtifactWriteRecord row = __latency_fn_artifact_writes_record_from_llvm_text_preflight(config, preflight, moduleText);
	__latency_fn_artifact_writes_append_record(report, row);
	return report;
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
ArtifactWriteRecord __latency_fn_artifact_writes_row_by_id(shared_p<ArtifactWriteReportRecord> report, int_t<std::uint32_t> recordId) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[25]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(recordId, report->row_count)))) {
		ArtifactWriteRecord row = report->rows[__latency_fn_structure_row_ids_dense_index(recordId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->record_id), cast<int_t<>>(recordId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->record_id), cast<int_t<>>(recordId)))) {
			return row;
		}
	}
	ArtifactWriteRecord empty = ArtifactWriteRecord{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
bool_t __latency_fn_artifact_writes_equals(ArtifactWriteRecord left, ArtifactWriteRecord right) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::equals", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[26]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->record_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->record_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return (((php::identical(cast<int_t<>>(left->artifact_key_id), cast<int_t<>>(right->artifact_key_id)) && php::identical(cast<int_t<>>(left->path_id), cast<int_t<>>(right->path_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id))) && php::identical(cast<int_t<>>(left->byte_count), cast<int_t<>>(right->byte_count)));
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
string_t __latency_fn_artifact_writes_debug_string(ArtifactWriteRecord row) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[27]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->record_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("artifact_write:") + cast<string_t>(__latency_fn_artifact_writes_artifact_key_name(row->artifact_key_id)) + string_t(":") + cast<string_t>(__latency_fn_artifact_writes_path_key_name(row->path_id)) + string_t(":") + cast<string_t>(__latency_fn_artifact_writes_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
string_t __latency_fn_artifact_writes_report_debug_string(shared_p<ArtifactWriteReportRecord> report) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::report_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[28]);
	return (string_t("artifact_write_report:") + cast<string_t>(report->row_count) + string_t(":") + cast<string_t>(report->written_count) + string_t(":") + cast<string_t>(report->reused_count) + string_t(":") + cast<string_t>(report->failed_count) + string_t(":") + cast<string_t>(report->skipped_count));
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_artifact_writes_stable_hash(ArtifactWriteRecord row) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[29]);
	string_t identity = required_cast<string_t>((string_t("artifact_write:v2:") + cast<string_t>(cast<int_t<>>(row->record_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->artifact_key_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->path_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->byte_count))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_artifact_writes_report_stable_hash(shared_p<ArtifactWriteReportRecord> report) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::report_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[30]);
	string_t identity = required_cast<string_t>((string_t("artifact_write_report:v2:") + cast<string_t>(report->row_count) + string_t(":") + cast<string_t>(report->written_count) + string_t(":") + cast<string_t>(report->reused_count) + string_t(":") + cast<string_t>(report->failed_count) + string_t(":") + cast<string_t>(report->skipped_count) + string_t(":") + cast<string_t>(report->total_byte_count)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
string_t __latency_fn_artifact_writes_selected_report_json(shared_p<PipelineConfig> config, shared_p<ArtifactWriteReportRecord> report) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::selected_report_json", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[31]);
	vector_t<string_t> lines = {};
	(void) lines.push_back(string_t("{\n"));
	{
	auto __latency_local_0 = (string_t("  \"artifact_kind\": \"") + cast<string_t>(__latency_fn_artifact_writes_report_artifact_kind_name(__latency_fn_structure_row_ids_uint16_from_int(report->artifact_kind_id))) + string_t("\",\n"));
	(void) lines.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = (string_t("  \"artifact_key\": \"") + cast<string_t>(__latency_fn_artifact_writes_report_artifact_key_name(__latency_fn_structure_row_ids_uint16_from_int(report->artifact_key_id))) + string_t("\",\n"));
	(void) lines.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = (string_t("  \"schema_version\": ") + cast<string_t>(report->schema_version) + string_t(",\n"));
	(void) lines.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = (string_t("  \"write_policy\": \"") + cast<string_t>(__latency_fn_artifact_writes_write_policy_name(__latency_fn_structure_row_ids_uint16_from_int(report->write_policy_id))) + string_t("\",\n"));
	(void) lines.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = (string_t("  \"row_count\": ") + cast<string_t>(report->row_count) + string_t(",\n"));
	(void) lines.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = (string_t("  \"written_count\": ") + cast<string_t>(report->written_count) + string_t(",\n"));
	(void) lines.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = (string_t("  \"reused_count\": ") + cast<string_t>(report->reused_count) + string_t(",\n"));
	(void) lines.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = (string_t("  \"failed_count\": ") + cast<string_t>(report->failed_count) + string_t(",\n"));
	(void) lines.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = (string_t("  \"skipped_count\": ") + cast<string_t>(report->skipped_count) + string_t(",\n"));
	(void) lines.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = (string_t("  \"total_byte_count\": ") + cast<string_t>(report->total_byte_count) + string_t(",\n"));
	(void) lines.push_back(__latency_local_9);
	}
	(void) lines.push_back(string_t("  \"rows\": [\n"));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_10 = report->rows;
	for (auto __latency_local_11 : foreach_range(__latency_local_10)) {
		auto row = __latency_local_11.value_copy();
		if (static_cast<bool>((index > static_cast<int_t<> >(0)))) {
			(void) lines.push_back(string_t(",\n"));
		}
		(void) lines.push_back(string_t("    {"));
		{
		auto __latency_local_12 = (string_t("\"record_id\": ") + cast<string_t>(row->record_id) + string_t(", "));
		(void) lines.push_back(__latency_local_12);
		}
		{
		auto __latency_local_13 = (string_t("\"artifact_key\": \"") + cast<string_t>(__latency_fn_artifact_writes_artifact_key_name(row->artifact_key_id)) + string_t("\", "));
		(void) lines.push_back(__latency_local_13);
		}
		{
		auto __latency_local_14 = (string_t("\"path_key\": \"") + cast<string_t>(__latency_fn_artifact_writes_path_key_name(row->path_id)) + string_t("\", "));
		(void) lines.push_back(__latency_local_14);
		}
		{
		auto __latency_local_15 = (string_t("\"path\": \"") + cast<string_t>(__latency_fn_artifact_writes_path_from_config(config, row->path_id)) + string_t("\", "));
		(void) lines.push_back(__latency_local_15);
		}
		{
		auto __latency_local_16 = (string_t("\"status\": \"") + cast<string_t>(__latency_fn_artifact_writes_status_name(row->status_id)) + string_t("\", "));
		(void) lines.push_back(__latency_local_16);
		}
		{
		auto __latency_local_17 = (string_t("\"byte_count\": ") + cast<string_t>(row->byte_count));
		(void) lines.push_back(__latency_local_17);
		}
		(void) lines.push_back(string_t("}"));
		index = (index + static_cast<int_t<> >(1));
	}
	(void) lines.push_back(string_t("\n  ]\n"));
	(void) lines.push_back(string_t("}\n"));
	return php::implode(string_t(""), lines);
}

}
