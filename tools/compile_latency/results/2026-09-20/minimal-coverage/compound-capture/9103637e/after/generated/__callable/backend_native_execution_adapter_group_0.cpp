#include <scpp/lang/php.hpp>
#include "__types/NativeExecutionArtifact.hpp"
#include "__types/backend_native_execution_adapter.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_shell_clean.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_not_executable_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_native_compile_failed_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_executed_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_executed_json.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_json_from_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_native_compile_failed_json.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_rows_tsv_from_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_json_from_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_not_executable_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_not_executable_json.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_native_compile_failed_json.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_executed_json.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_compile_and_run_llvm_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_executed_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_native_compile_failed_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_not_executable_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_shell_clean.hpp"
#include "__callable/__latency_fn_output_paths_ensure_parent_and_dir.hpp"
namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
bool_t backend_native_execution_adapter::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == backend_native_execution_adapter::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
string_t __latency_fn_backend_native_execution_adapter_shell_clean(const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::shell_clean", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[0]);
	string_t clean = required_cast<string_t>(str::replace(string_t("'"), string_t("'\\''"), value));
	return (string_t("'") + cast<string_t>(clean) + string_t("'"));
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
shared_p<NativeExecutionArtifact> __latency_fn_backend_native_execution_adapter_not_executable_artifact(const string_t& reason) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::not_executable_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[1]);
	shared_p<NativeExecutionArtifact> artifact = create<NativeExecutionArtifact>();
	artifact->status = string_t("not_executable");
	artifact->reason = reason;
	artifact->llvm_text_status = string_t("not_started");
	artifact->native_compile_status = string_t("not_started");
	artifact->native_link_status = string_t("not_started");
	artifact->native_exit_status = string_t("not_started");
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
shared_p<NativeExecutionArtifact> __latency_fn_backend_native_execution_adapter_native_compile_failed_artifact(int_t<> compileExitCode) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::native_compile_failed_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[2]);
	shared_p<NativeExecutionArtifact> artifact = create<NativeExecutionArtifact>();
	artifact->status = string_t("not_executable");
	artifact->reason = string_t("native_compile_failed");
	artifact->llvm_text_status = string_t("available");
	artifact->native_compile_status = string_t("failed");
	artifact->native_link_status = string_t("failed");
	artifact->native_exit_status = string_t("not_started");
	artifact->native_compile_exit_code = compileExitCode;
	artifact->has_native_compile_exit_code = static_cast<bool_t>(true);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
shared_p<NativeExecutionArtifact> __latency_fn_backend_native_execution_adapter_executed_artifact(int_t<> exitCode) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::executed_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[3]);
	shared_p<NativeExecutionArtifact> artifact = create<NativeExecutionArtifact>();
	artifact->status = string_t("executed");
	artifact->reason = string_t("native_exit_code");
	artifact->llvm_text_status = string_t("available");
	artifact->native_compile_status = string_t("ok");
	artifact->native_link_status = string_t("ok");
	artifact->native_exit_status = string_t("exited");
	artifact->exit_code = exitCode;
	artifact->has_exit_code = static_cast<bool_t>(true);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
string_t __latency_fn_backend_native_execution_adapter_json_from_artifact(shared_p<NativeExecutionArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::json_from_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[4]);
	if (static_cast<bool>(php::identical(artifact->status, string_t("executed")))) {
		return __latency_fn_backend_native_execution_adapter_executed_json(artifact->exit_code);
	}
	if (static_cast<bool>(php::identical(artifact->reason, string_t("native_compile_failed")))) {
		return __latency_fn_backend_native_execution_adapter_native_compile_failed_json(artifact->native_compile_exit_code);
	}
	return (string_t("{\n  \"status\": \"not_executable\",\n  \"reason\": \"") + cast<string_t>(artifact->reason) + string_t("\",\n") + string_t("  \"native_compile_status\": \"not_started\",\n") + string_t("  \"native_link_status\": \"not_started\",\n") + string_t("  \"native_exit_status\": \"not_started\"\n") + string_t("}\n"));
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
string_t __latency_fn_backend_native_execution_adapter_rows_tsv_from_artifact(shared_p<NativeExecutionArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::rows_tsv_from_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[5]);
	string_t text = required_cast<string_t>(string_t("stage\tstatus\treason\texit_code\n"));
	text = (text + cast<string_t>((string_t("llvm_text\t") + cast<string_t>(artifact->llvm_text_status) + string_t("\t") + cast<string_t>(artifact->reason) + string_t("\t\n"))));
	text = (text + cast<string_t>((string_t("native_compile\t") + cast<string_t>(artifact->native_compile_status) + string_t("\t") + cast<string_t>(artifact->reason) + string_t("\t"))));
	if (static_cast<bool>(php::condition_truthy(artifact->has_native_compile_exit_code))) {
		text = (text + cast<string_t>(cast<string_t>(artifact->native_compile_exit_code)));
	}
	text = (text + string_t("\n"));
	text = (text + cast<string_t>((string_t("native_link\t") + cast<string_t>(artifact->native_link_status) + string_t("\t") + cast<string_t>(artifact->reason) + string_t("\t\n"))));
	text = (text + cast<string_t>((string_t("native_run\t") + cast<string_t>(artifact->native_exit_status) + string_t("\t") + cast<string_t>(artifact->reason) + string_t("\t"))));
	if (static_cast<bool>(php::condition_truthy(artifact->has_exit_code))) {
		text = (text + cast<string_t>(cast<string_t>(artifact->exit_code)));
	}
	text = (text + string_t("\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
string_t __latency_fn_backend_native_execution_adapter_not_executable_json(const string_t& reason) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::not_executable_json", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[6]);
	return __latency_fn_backend_native_execution_adapter_json_from_artifact(__latency_fn_backend_native_execution_adapter_not_executable_artifact(reason));
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
string_t __latency_fn_backend_native_execution_adapter_native_compile_failed_json(int_t<> compileExitCode) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::native_compile_failed_json", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[7]);
	return (string_t("{\n  \"status\": \"not_executable\",\n  \"reason\": \"native_compile_failed\",\n  \"native_compile_status\": \"failed\",\n  \"native_link_status\": \"failed\",\n  \"native_exit_status\": \"not_started\",\n  \"native_compile_exit_code\": ") + cast<string_t>(compileExitCode) + string_t("\n") + string_t("}\n"));
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
string_t __latency_fn_backend_native_execution_adapter_executed_json(int_t<> exitCode) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::executed_json", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[8]);
	return (string_t("{\n  \"status\": \"executed\",\n  \"reason\": \"native_exit_code\",\n  \"native_compile_status\": \"ok\",\n  \"native_link_status\": \"ok\",\n  \"native_exit_status\": \"exited\",\n  \"exit_code\": ") + cast<string_t>(exitCode) + string_t("\n") + string_t("}\n"));
}

}

namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
shared_p<NativeExecutionArtifact> __latency_fn_backend_native_execution_adapter_compile_and_run_llvm_artifact(const string_t& nativeOutDir, const string_t& runLabel, const string_t& nativeCompiler) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::compile_and_run_llvm_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[9]);
	if (static_cast<bool>((!__latency_fn_output_paths_ensure_parent_and_dir(fs::dirname(nativeOutDir), nativeOutDir)))) {
		return __latency_fn_backend_native_execution_adapter_not_executable_artifact(string_t("native_output_dir_not_ready"));
	}
	string_t llPath = required_cast<string_t>((cast<string_t>(nativeOutDir) + string_t("/compiler.ll")));
	string_t exePath = required_cast<string_t>((cast<string_t>(nativeOutDir) + string_t("/") + cast<string_t>(runLabel)));
	if (static_cast<bool>((!fs::exists(llPath)))) {
		shared_p<NativeExecutionArtifact> missingArtifact = __latency_fn_backend_native_execution_adapter_not_executable_artifact(string_t("llvm_text_not_written"));
		missingArtifact->llvm_text_status = string_t("missing");
		return missingArtifact;
	}
	if (static_cast<bool>(php::identical(nativeCompiler, string_t("")))) {
		shared_p<NativeExecutionArtifact> compilerMissingArtifact = __latency_fn_backend_native_execution_adapter_not_executable_artifact(string_t("native_compiler_command_missing"));
		compilerMissingArtifact->llvm_text_status = string_t("available");
		return compilerMissingArtifact;
	}
	string_t compileCommand = required_cast<string_t>((string_t("TMPDIR=") + cast<string_t>(__latency_fn_backend_native_execution_adapter_shell_clean(nativeOutDir)) + string_t(" ") + cast<string_t>(__latency_fn_backend_native_execution_adapter_shell_clean(nativeCompiler)) + string_t(" ") + cast<string_t>(__latency_fn_backend_native_execution_adapter_shell_clean(llPath)) + string_t(" -o ") + cast<string_t>(__latency_fn_backend_native_execution_adapter_shell_clean(exePath)) + string_t(" >/dev/null 2>&1; printf '%s' $?")));
	string_t output = required_cast<string_t>(string_t(""));
	int_t<> compileExitCode = required_cast<int_t<>>((-static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(php::take(output, php::shell_exec(compileCommand))))) {
		compileExitCode = cast<int_t<>>(str::trim(output));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(compileExitCode, static_cast<int_t<> >(0))))) {
		return __latency_fn_backend_native_execution_adapter_native_compile_failed_artifact(compileExitCode);
	}
	string_t runCommand = required_cast<string_t>((cast<string_t>(__latency_fn_backend_native_execution_adapter_shell_clean(exePath)) + string_t(" >/dev/null 2>&1; printf '%s' $?")));
	output = string_t("");
	int_t<> exitCode = required_cast<int_t<>>((-static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(php::take(output, php::shell_exec(runCommand))))) {
		exitCode = cast<int_t<>>(str::trim(output));
	}
	return __latency_fn_backend_native_execution_adapter_executed_artifact(exitCode);
}

}
