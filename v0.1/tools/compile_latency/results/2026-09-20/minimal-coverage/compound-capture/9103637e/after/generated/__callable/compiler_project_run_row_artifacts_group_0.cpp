#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/compiler_project_run_row_artifacts.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_rows_tsv_path_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_run_artifact_path_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_run_rows_tsv_from_env.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_clean_tsv_value.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_status_name.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_status_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_status_id.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_active_layer_name.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_artifact_writes_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_backend_text_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_frontend_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_symbol_index_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_status_id.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_active_layer_name.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_clean_tsv_value.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_rows_tsv.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_status_name.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_rows_tsv.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_rows_tsv_path_from_env.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_write_rows_tsv_if_requested.hpp"
#include "__callable/__latency_fn_output_paths_ensure_dir.hpp"
namespace scpp { extern const int __latency_lines_compiler_project_run_row_artifacts[]; }
namespace scpp {
bool_t compiler_project_run_row_artifacts::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == compiler_project_run_row_artifacts::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_compiler_project_run_row_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_project_run_row_artifacts_rows_tsv_path_from_env() {
	SCPP_CALL_DEPTH_GUARD("compiler_project_run_row_artifacts::rows_tsv_path_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_project_run_row_artifacts.phs", __latency_lines_compiler_project_run_row_artifacts[0]);
	string_t exactPath = required_cast<string_t>(__latency_fn_pipeline_config_helpers_project_run_rows_tsv_from_env());
	if (static_cast<bool>(php::condition_truthy(php::not_identical(exactPath, string_t(""))))) {
		return exactPath;
	}
	string_t artifactRoot = required_cast<string_t>(__latency_fn_pipeline_config_helpers_project_run_artifact_path_from_env());
	if (static_cast<bool>(php::identical(artifactRoot, string_t("")))) {
		return string_t("");
	}
	return (cast<string_t>(artifactRoot) + string_t("/compiler_project_runner_rows.tsv"));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_run_row_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_project_run_row_artifacts_clean_tsv_value(const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_run_row_artifacts::clean_tsv_value", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_project_run_row_artifacts.phs", __latency_lines_compiler_project_run_row_artifacts[1]);
	string_t clean = required_cast<string_t>(str::replace(string_t("\t"), string_t(" "), value));
	clean = str::replace(string_t("\r"), string_t(" "), clean);
	clean = str::replace(string_t("\n"), string_t(" "), clean);
	return clean;
}

}

namespace scpp { extern const int __latency_lines_compiler_project_run_row_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_project_run_row_artifacts_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_run_row_artifacts::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_project_run_row_artifacts.phs", __latency_lines_compiler_project_run_row_artifacts[2]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_compiler_project_runner_completed_status_id())))) {
		return string_t("completed_current_layers");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_compiler_project_runner_blocked_status_id())))) {
		return string_t("blocked_current_layers");
	}
	return string_t("unknown_current_layers");
}

}

namespace scpp { extern const int __latency_lines_compiler_project_run_row_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_project_run_row_artifacts_active_layer_name(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_run_row_artifacts::active_layer_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_project_run_row_artifacts.phs", __latency_lines_compiler_project_run_row_artifacts[3]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_compiler_project_runner_completed_status_id())))) {
		return string_t("artifact_writes");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_layer_id), cast<int_t<>>(__latency_fn_compiler_project_runner_blocked_layer_frontend_id())))) {
		return string_t("frontend");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_layer_id), cast<int_t<>>(__latency_fn_compiler_project_runner_blocked_layer_symbol_index_id())))) {
		return string_t("symbol_index");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_layer_id), cast<int_t<>>(__latency_fn_compiler_project_runner_blocked_layer_backend_text_id())))) {
		return string_t("backend_text");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_layer_id), cast<int_t<>>(__latency_fn_compiler_project_runner_blocked_layer_artifact_writes_id())))) {
		return string_t("artifact_writes");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_compiler_project_run_row_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_project_run_row_artifacts_rows_tsv(shared_p<CompilerProjectRunReport> report) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_run_row_artifacts::rows_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_project_run_row_artifacts.phs", __latency_lines_compiler_project_run_row_artifacts[4]);
	string_t text = required_cast<string_t>(string_t("run_id\trun_label\tstatus\tactive_layer\tblocked_reason_id\tcompleted_layer_id\tblocked_layer_id\n"));
	auto __latency_local_0 = report->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		string_t runLabel = required_cast<string_t>(string_t(""));
		int_t<> keyIndex = required_cast<int_t<>>((cast<int_t<>>(row->run_label_id) - static_cast<int_t<> >(1)));
		if (static_cast<bool>(((keyIndex >= static_cast<int_t<> >(0)) && (keyIndex < php::count(report->run_labels))))) {
			runLabel = report->run_labels[keyIndex];
		}
		text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->run_id)) + string_t("\t") + cast<string_t>(__latency_fn_compiler_project_run_row_artifacts_clean_tsv_value(runLabel)) + string_t("\t") + cast<string_t>(__latency_fn_compiler_project_run_row_artifacts_status_name(row->status_id)) + string_t("\t") + cast<string_t>(__latency_fn_compiler_project_run_row_artifacts_active_layer_name(row)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->completed_layer_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->blocked_layer_id)) + string_t("\n"));
	}
	return text;
}

}

namespace scpp { extern const int __latency_lines_compiler_project_run_row_artifacts[]; }
namespace scpp {
bool_t __latency_fn_compiler_project_run_row_artifacts_write_rows_tsv_if_requested(shared_p<CompilerProjectRunReport> report) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_run_row_artifacts::write_rows_tsv_if_requested", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_project_run_row_artifacts.phs", __latency_lines_compiler_project_run_row_artifacts[5]);
	string_t path = required_cast<string_t>(__latency_fn_compiler_project_run_row_artifacts_rows_tsv_path_from_env());
	if (static_cast<bool>(php::identical(path, string_t("")))) {
		return bool_t(static_cast<bool_t>(true));
	}
	string_t parent = required_cast<string_t>(fs::dirname(path));
	if (static_cast<bool>((php::not_identical(parent, string_t("")) && (!__latency_fn_output_paths_ensure_dir(parent))))) {
		php::echo_one(string_t("compiler_project_run_rows_tsv_write=failed\n"));
		php::echo_one(string_t("compiler_project_run_rows_tsv_path="));
		php::echo_one(path);
		php::echo_one(string_t("\n"));
		return bool_t(static_cast<bool_t>(false));
	}
	error_t err;
	int_t<> written;
	if (static_cast<bool>(php::condition_truthy(php::take(written, err, fs::put(path, __latency_fn_compiler_project_run_row_artifacts_rows_tsv(report)))))) {
		php::echo_one(string_t("compiler_project_run_rows_tsv_write=ok\n"));
		php::echo_one(string_t("compiler_project_run_rows_tsv_path="));
		php::echo_one(path);
		php::echo_one(string_t("\n"));
		return bool_t(static_cast<bool_t>(true));
	}
	php::echo_one(string_t("compiler_project_run_rows_tsv_write=failed\n"));
	php::echo_one(string_t("compiler_project_run_rows_tsv_path="));
	php::echo_one(path);
	php::echo_one(string_t("\n"));
	return bool_t(static_cast<bool_t>(false));
}

}
