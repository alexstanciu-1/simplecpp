#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PipelineBatchConfig.hpp"
#include "compile/support/structure_kernel_smoke_runner.hpp"
#include "__callable/__latency_fn_compiler_pipeline_runner_run_batch.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "compile/support/structure_kernel_smoke_runner.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t structure_kernel_run_smoke() {
	SCPP_CALL_DEPTH_GUARD("structure_kernel_run_smoke", "/tmp/scpp-edit-latency-20260919/app/compile/support/structure_kernel_smoke_runner.phs", 4);
	string_t outputRoot = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_STRUCTURE_OUTPUT_ROOT")));
	if (static_cast<bool>(php::identical(outputRoot, string_t("")))) {
		outputRoot = string_t(".prism/v2_structure_kernel_reset_outputs");
	}
	shared_p<PipelineBatchConfig> batch = create<PipelineBatchConfig>();
	batch->project_root = string_t("../fixtures");
	batch->build_root = (cast<string_t>(outputRoot) + string_t("/compiler_project_runner"));
	(void) batch->run_labels.append(string_t("return_42"));
	shared_p<CompilerProjectRunReport> report = __latency_fn_compiler_pipeline_runner_run_batch(batch);
	bool_t ok = required_cast<bool_t>((((php::identical(cast<int_t<>>(report->row_count), static_cast<int_t<> >(1)) && php::identical(cast<int_t<>>(report->completed_count), static_cast<int_t<> >(1))) && php::identical(cast<int_t<>>(report->blocked_count), static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(report->parser_error_count), static_cast<int_t<> >(0))));
	php::echo_one(string_t("v2_structure_kernel_reset\n"));
	php::echo_one(string_t("v2_compiler_project_runner\n"));
	php::echo_one(string_t("behavior_pipeline=fenced\n"));
	php::echo_one(string_t("structure_kernel=active\n"));
	php::echo_one(string_t("compiler_project_runner=active\n"));
	php::echo_one(string_t("compiler_project_runner_rows="));
	php::echo_one(cast<int_t<>>(report->row_count));
	php::echo_one(string_t("\n"));
	php::echo_one(string_t("compiler_project_runner_completed="));
	php::echo_one(cast<int_t<>>(report->completed_count));
	php::echo_one(string_t("\n"));
	php::echo_one(string_t("compiler_project_runner_blocked="));
	php::echo_one(cast<int_t<>>(report->blocked_count));
	php::echo_one(string_t("\n"));
	php::echo_one(string_t("compiler_project_runner_parser_errors="));
	php::echo_one(cast<int_t<>>(report->parser_error_count));
	php::echo_one(string_t("\n"));
	php::echo_one(php::ternary_eval([&]() -> decltype(auto) { return ok; }, [&]() -> decltype(auto) { return string_t("structure_smoke=ok\n"); }, [&]() -> decltype(auto) { return string_t("structure_smoke=failed\n"); }));
	return bool_t(ok);
}

}

