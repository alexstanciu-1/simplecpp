#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/NativeExecutionArtifact.hpp"
#include "__types/PipelineBatchConfig.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/compiler_execution_artifacts.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_execution_dir_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_run_artifact_path_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_run_execution_dir_from_env.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_status_name.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_status_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_status_id.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_reason_name.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_status_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_status_id.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_native_compiler_command_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_compile_and_run_llvm_json.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_executable_json.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_native_compiler_command_from_env.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_compile_and_run_llvm_artifact.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_executable_artifact.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_native_compiler_command_from_env.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_executable_json.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_execution_json.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_execution_json_for_config.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_status_id.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_execution_json.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_reason_name.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_status_name.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_not_executable_artifact.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_execution_artifact_for_row.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_reason_name.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_json_from_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_rows_tsv_from_artifact.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_executable_artifact.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_execution_artifact_for_row.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_execution_dir_from_env.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_execution_json.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_write_execution_jsons_if_requested.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_status_id.hpp"
#include "__callable/__latency_fn_output_paths_ensure_dir.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_batch_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_batch_is_complete.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_from_batch_run.hpp"
namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
bool_t compiler_execution_artifacts::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == compiler_execution_artifacts::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_execution_artifacts_execution_dir_from_env() {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::execution_dir_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[0]);
	string_t exactDir = required_cast<string_t>(__latency_fn_pipeline_config_helpers_project_run_execution_dir_from_env());
	if (static_cast<bool>(php::condition_truthy(php::not_identical(exactDir, string_t(""))))) {
		return exactDir;
	}
	string_t artifactRoot = required_cast<string_t>(__latency_fn_pipeline_config_helpers_project_run_artifact_path_from_env());
	if (static_cast<bool>(php::identical(artifactRoot, string_t("")))) {
		return string_t("");
	}
	return (cast<string_t>(artifactRoot) + string_t("/execution"));
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_execution_artifacts_status_name(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[1]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_compiler_project_runner_blocked_status_id())))) {
		return string_t("blocked");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_compiler_project_runner_completed_status_id())))) {
		return string_t("not_executable");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_execution_artifacts_reason_name(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[2]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_compiler_project_runner_blocked_status_id())))) {
		return string_t("compiler_row_blocked");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_compiler_project_runner_completed_status_id())))) {
		return string_t("backend_execution_not_wired");
	}
	return string_t("unknown_compiler_row");
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_execution_artifacts_native_compiler_command_from_env() {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::native_compiler_command_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[3]);
	return __latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_NATIVE_CC"));
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_execution_artifacts_executable_json(shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::executable_json", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[4]);
	string_t nativeCompiler = required_cast<string_t>(__latency_fn_compiler_execution_artifacts_native_compiler_command_from_env());
	return __latency_fn_backend_native_execution_adapter_compile_and_run_llvm_json(config->native_out_dir, config->run_label, nativeCompiler);
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
shared_p<NativeExecutionArtifact> __latency_fn_compiler_execution_artifacts_executable_artifact(shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::executable_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[5]);
	string_t nativeCompiler = required_cast<string_t>(__latency_fn_compiler_execution_artifacts_native_compiler_command_from_env());
	return __latency_fn_backend_native_execution_adapter_compile_and_run_llvm_artifact(config->native_out_dir, config->run_label, nativeCompiler);
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_execution_artifacts_execution_json_for_config(shared_p<PipelineConfig> config, CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::execution_json_for_config", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[6]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_compiler_project_runner_completed_status_id()))))) {
		return __latency_fn_compiler_execution_artifacts_execution_json(row);
	}
	return __latency_fn_compiler_execution_artifacts_executable_json(config);
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_execution_artifacts_execution_json(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::execution_json", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[7]);
	return (string_t("{\n  \"status\": \"") + cast<string_t>(__latency_fn_compiler_execution_artifacts_status_name(row)) + string_t("\",\n") + string_t("  \"reason\": \"") + cast<string_t>(__latency_fn_compiler_execution_artifacts_reason_name(row)) + string_t("\"\n") + string_t("}\n"));
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
shared_p<NativeExecutionArtifact> __latency_fn_compiler_execution_artifacts_execution_artifact_for_row(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::execution_artifact_for_row", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[8]);
	return __latency_fn_backend_native_execution_adapter_not_executable_artifact(__latency_fn_compiler_execution_artifacts_reason_name(row));
}

}

namespace scpp { extern const int __latency_lines_compiler_execution_artifacts[]; }
namespace scpp {
bool_t __latency_fn_compiler_execution_artifacts_write_execution_jsons_if_requested(shared_p<CompilerProjectRunReport> report) {
	SCPP_CALL_DEPTH_GUARD("compiler_execution_artifacts::write_execution_jsons_if_requested", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_execution_artifacts.phs", __latency_lines_compiler_execution_artifacts[9]);
	string_t dir = required_cast<string_t>(__latency_fn_compiler_execution_artifacts_execution_dir_from_env());
	if (static_cast<bool>(php::identical(dir, string_t("")))) {
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>((!__latency_fn_output_paths_ensure_dir(dir)))) {
		php::echo_one(string_t("compiler_execution_write=failed\n"));
		php::echo_one(string_t("compiler_execution_dir="));
		php::echo_one(dir);
		php::echo_one(string_t("\n"));
		return bool_t(static_cast<bool_t>(false));
	}
	shared_p<PipelineBatchConfig> batch = __latency_fn_pipeline_config_helpers_batch_from_env();
	auto __latency_local_0 = report->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		string_t runLabel = required_cast<string_t>(string_t(""));
		int_t<> keyIndex = required_cast<int_t<>>((cast<int_t<>>(row->run_label_id) - static_cast<int_t<> >(1)));
		if (static_cast<bool>(((keyIndex >= static_cast<int_t<> >(0)) && (keyIndex < php::count(report->run_labels))))) {
			runLabel = report->run_labels[keyIndex];
		}
		if (static_cast<bool>(php::identical(runLabel, string_t("")))) {
			continue;
		}
		string_t path = required_cast<string_t>((cast<string_t>(dir) + string_t("/") + cast<string_t>(runLabel) + string_t(".json")));
		string_t json = required_cast<string_t>(__latency_fn_compiler_execution_artifacts_execution_json(row));
		shared_p<NativeExecutionArtifact> artifact = __latency_fn_compiler_execution_artifacts_execution_artifact_for_row(row);
		if (static_cast<bool>(php::condition_truthy(__latency_fn_pipeline_config_helpers_batch_is_complete(batch)))) {
			shared_p<PipelineConfig> config = __latency_fn_pipeline_config_helpers_from_batch_run(batch, runLabel);
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_compiler_project_runner_completed_status_id())))) {
				artifact = __latency_fn_compiler_execution_artifacts_executable_artifact(config);
				json = __latency_fn_backend_native_execution_adapter_json_from_artifact(artifact);
			}
		}
		error_t err;
		int_t<> written;
		if (static_cast<bool>((!php::take(written, err, fs::put(path, json))))) {
			php::echo_one(string_t("compiler_execution_write=failed\n"));
			php::echo_one(string_t("compiler_execution_path="));
			php::echo_one(path);
			php::echo_one(string_t("\n"));
			return bool_t(static_cast<bool_t>(false));
		}
		string_t rowsPath = required_cast<string_t>((cast<string_t>(dir) + string_t("/") + cast<string_t>(runLabel) + string_t(".tsv")));
		string_t rowsText = required_cast<string_t>(__latency_fn_backend_native_execution_adapter_rows_tsv_from_artifact(artifact));
		if (static_cast<bool>((!php::take(written, err, fs::put(rowsPath, rowsText))))) {
			php::echo_one(string_t("compiler_execution_write=failed\n"));
			php::echo_one(string_t("compiler_execution_path="));
			php::echo_one(rowsPath);
			php::echo_one(string_t("\n"));
			return bool_t(static_cast<bool_t>(false));
		}
	}
	php::echo_one(string_t("compiler_execution_write=ok\n"));
	php::echo_one(string_t("compiler_execution_dir="));
	php::echo_one(dir);
	php::echo_one(string_t("\n"));
	return bool_t(static_cast<bool_t>(true));
}

}
