#include <scpp/lang/php.hpp>
#include "__types/ArtifactWriteRecord.hpp"
#include "__types/ArtifactWriteReportRecord.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__types/PipelineConfig.hpp"
#include "__callable/__latency_fn_artifact_writes_status_failed_id.hpp"
#include "__callable/__latency_fn_artifact_writes_status_name.hpp"
#include "__callable/__latency_fn_artifact_writes_status_reused_id.hpp"
#include "__callable/__latency_fn_artifact_writes_status_skipped_blocked_id.hpp"
#include "__callable/__latency_fn_artifact_writes_status_skipped_not_selected_id.hpp"
#include "__callable/__latency_fn_artifact_writes_status_written_id.hpp"
#include "__callable/__latency_fn_artifact_writes_path_from_config.hpp"
#include "__callable/__latency_fn_artifact_writes_path_key_compiler_ll_id.hpp"
#include "__callable/__latency_fn_artifact_writes_artifact_key_name.hpp"
#include "__callable/__latency_fn_artifact_writes_selected.hpp"
#include "__callable/__latency_fn_artifact_writes_new_report.hpp"
#include "__callable/__latency_fn_artifact_writes_report_artifact_key_selected_write_id.hpp"
#include "__callable/__latency_fn_artifact_writes_report_artifact_kind_selected_write_id.hpp"
#include "__callable/__latency_fn_artifact_writes_write_policy_selected_lazy_id.hpp"
#include "__callable/__latency_fn_artifact_writes_compact_record.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_artifact_writes_write_text.hpp"
#include "__callable/__latency_fn_artifact_writes_compact_record.hpp"
#include "__callable/__latency_fn_artifact_writes_path_from_config.hpp"
#include "__callable/__latency_fn_artifact_writes_status_failed_id.hpp"
#include "__callable/__latency_fn_artifact_writes_status_reused_id.hpp"
#include "__callable/__latency_fn_artifact_writes_status_written_id.hpp"
#include "__callable/__latency_fn_artifact_writes_write_if_changed.hpp"
#include "__callable/__latency_fn_artifact_writes_write_text.hpp"
#include "__callable/__latency_fn_source_files_content_or_empty.hpp"
#include "__callable/__latency_fn_artifact_writes_artifact_key_llvm_text_id.hpp"
#include "__callable/__latency_fn_artifact_writes_compact_record.hpp"
#include "__callable/__latency_fn_artifact_writes_path_key_compiler_ll_id.hpp"
#include "__callable/__latency_fn_artifact_writes_record_from_llvm_text_preflight.hpp"
#include "__callable/__latency_fn_artifact_writes_selected.hpp"
#include "__callable/__latency_fn_artifact_writes_status_skipped_blocked_id.hpp"
#include "__callable/__latency_fn_artifact_writes_status_skipped_not_selected_id.hpp"
#include "__callable/__latency_fn_artifact_writes_write_if_changed.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_artifact_writes_append_record.hpp"
#include "__callable/__latency_fn_artifact_writes_status_failed_id.hpp"
#include "__callable/__latency_fn_artifact_writes_status_reused_id.hpp"
#include "__callable/__latency_fn_artifact_writes_status_written_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
string_t __latency_fn_artifact_writes_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[15]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_artifact_writes_status_written_id())))) {
		return string_t("written");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_artifact_writes_status_reused_id())))) {
		return string_t("reused");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_artifact_writes_status_failed_id())))) {
		return string_t("failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_artifact_writes_status_skipped_not_selected_id())))) {
		return string_t("skipped_not_selected");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_artifact_writes_status_skipped_blocked_id())))) {
		return string_t("skipped_blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
string_t __latency_fn_artifact_writes_path_from_config(shared_p<PipelineConfig> config, int_t<std::uint16_t> pathId) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::path_from_config", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[16]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(pathId), cast<int_t<>>(__latency_fn_artifact_writes_path_key_compiler_ll_id())))) {
		return (cast<string_t>(config->native_out_dir) + string_t("/compiler.ll"));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
bool_t __latency_fn_artifact_writes_selected(shared_p<PipelineConfig> config, int_t<std::uint16_t> artifactKeyId) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::selected", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[17]);
	if (static_cast<bool>(php::identical(php::count(config->selected_artifact_keys), static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(true));
	}
	string_t key = required_cast<string_t>(__latency_fn_artifact_writes_artifact_key_name(cast<int_t<std::uint16_t>>(artifactKeyId)));
	auto __latency_local_0 = config->selected_artifact_keys;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto selectedKey = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(selectedKey, key))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
shared_p<ArtifactWriteReportRecord> __latency_fn_artifact_writes_new_report(int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::new_report", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[18]);
	shared_p<ArtifactWriteReportRecord> report = create<ArtifactWriteReportRecord>();
	report->artifact_kind_id = cast<int_t<>>(__latency_fn_artifact_writes_report_artifact_kind_selected_write_id());
	report->artifact_key_id = cast<int_t<>>(__latency_fn_artifact_writes_report_artifact_key_selected_write_id());
	report->schema_version = static_cast<int_t<> >(1);
	report->write_policy_id = cast<int_t<>>(__latency_fn_artifact_writes_write_policy_selected_lazy_id());
	php::vector_reserve(report->rows, rowCapacity);
	return report;
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
ArtifactWriteRecord __latency_fn_artifact_writes_compact_record(int_t<std::uint16_t> statusId, int_t<std::uint16_t> artifactKeyId, int_t<std::uint16_t> pathId, int_t<> byteCount) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::compact_record", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[19]);
	ArtifactWriteRecord row = ArtifactWriteRecord{};
	row->status_id = statusId;
	row->artifact_key_id = artifactKeyId;
	row->path_id = pathId;
	row->byte_count = __latency_fn_structure_row_ids_uint32_from_int(byteCount);
	return row;
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
bool_t __latency_fn_artifact_writes_write_text(const string_t& path, const string_t& text) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::write_text", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[20]);
	int_t<> written = required_cast<int_t<>>(static_cast<int_t<> >(0));
	error_t err;
	if (static_cast<bool>(php::condition_truthy(php::take(written, err, fs::put(path, text))))) {
		return bool_t(static_cast<bool_t>(true));
	}
	php::echo_one(string_t("v2_write_failed: "));
	php::echo_one(path);
	php::echo_one(string_t("\n"));
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
ArtifactWriteRecord __latency_fn_artifact_writes_write_if_changed(shared_p<PipelineConfig> config, int_t<std::uint16_t> artifactKeyId, int_t<std::uint16_t> pathId, const string_t& text) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::write_if_changed", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[21]);
	string_t path = required_cast<string_t>(__latency_fn_artifact_writes_path_from_config(config, cast<int_t<std::uint16_t>>(pathId)));
	int_t<> byteCount = required_cast<int_t<>>(str::byte_length(text));
	if (static_cast<bool>(php::identical(path, string_t("")))) {
		return __latency_fn_artifact_writes_compact_record(__latency_fn_artifact_writes_status_failed_id(), cast<int_t<std::uint16_t>>(artifactKeyId), cast<int_t<std::uint16_t>>(pathId), byteCount);
	}
	if (static_cast<bool>(php::condition_truthy(fs::exists(path)))) {
		string_t previousText = required_cast<string_t>(__latency_fn_source_files_content_or_empty(path));
		if (static_cast<bool>(php::identical(previousText, text))) {
			return __latency_fn_artifact_writes_compact_record(__latency_fn_artifact_writes_status_reused_id(), cast<int_t<std::uint16_t>>(artifactKeyId), cast<int_t<std::uint16_t>>(pathId), byteCount);
		}
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_artifact_writes_write_text(path, text)))) {
		return __latency_fn_artifact_writes_compact_record(__latency_fn_artifact_writes_status_written_id(), cast<int_t<std::uint16_t>>(artifactKeyId), cast<int_t<std::uint16_t>>(pathId), byteCount);
	}
	return __latency_fn_artifact_writes_compact_record(__latency_fn_artifact_writes_status_failed_id(), cast<int_t<std::uint16_t>>(artifactKeyId), cast<int_t<std::uint16_t>>(pathId), byteCount);
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
ArtifactWriteRecord __latency_fn_artifact_writes_record_from_llvm_text_preflight(shared_p<PipelineConfig> config, FunctionBodyTextEmissionPreflightRow preflight, const string_t& moduleText) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::record_from_llvm_text_preflight", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[22]);
	int_t<std::uint16_t> artifactKeyId = required_cast<int_t<std::uint16_t>>(__latency_fn_artifact_writes_artifact_key_llvm_text_id());
	int_t<std::uint16_t> pathId = required_cast<int_t<std::uint16_t>>(__latency_fn_artifact_writes_path_key_compiler_ll_id());
	if (static_cast<bool>((!__latency_fn_artifact_writes_selected(config, cast<int_t<std::uint16_t>>(artifactKeyId))))) {
		return __latency_fn_artifact_writes_compact_record(__latency_fn_artifact_writes_status_skipped_not_selected_id(), cast<int_t<std::uint16_t>>(artifactKeyId), cast<int_t<std::uint16_t>>(pathId), static_cast<int_t<> >(0));
	}
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(preflight->status_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())) || php::identical(moduleText, string_t(""))))) {
		return __latency_fn_artifact_writes_compact_record(__latency_fn_artifact_writes_status_skipped_blocked_id(), cast<int_t<std::uint16_t>>(artifactKeyId), cast<int_t<std::uint16_t>>(pathId), static_cast<int_t<> >(0));
	}
	return __latency_fn_artifact_writes_write_if_changed(config, cast<int_t<std::uint16_t>>(artifactKeyId), cast<int_t<std::uint16_t>>(pathId), moduleText);
}

}

namespace scpp { extern const int __latency_lines_artifact_writes[]; }
namespace scpp {
void __latency_fn_artifact_writes_append_record(shared_p<ArtifactWriteReportRecord> report, ArtifactWriteRecord row) {
	SCPP_CALL_DEPTH_GUARD("artifact_writes::append_record", "/tmp/scpp-edit-latency-20260919/app/compile/support/artifact_writes.phs", __latency_lines_artifact_writes[23]);
	row->record_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->rows));
	(void) report->rows.append(row);
	report->row_count = php::count(report->rows);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_artifact_writes_status_written_id())))) {
		report->written_count = (report->written_count + static_cast<int_t<> >(1));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_artifact_writes_status_reused_id())))) {
			report->reused_count = (report->reused_count + static_cast<int_t<> >(1));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_artifact_writes_status_failed_id())))) {
				report->failed_count = (report->failed_count + static_cast<int_t<> >(1));
			}
			else {
				report->skipped_count = (report->skipped_count + static_cast<int_t<> >(1));
			}
		}
	}
	report->total_byte_count = (report->total_byte_count + cast<int_t<>>(row->byte_count));
}

}
