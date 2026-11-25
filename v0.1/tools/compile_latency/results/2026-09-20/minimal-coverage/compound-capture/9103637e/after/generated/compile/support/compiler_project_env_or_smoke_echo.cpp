#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PipelineBatchConfig.hpp"
#include "__types/PipelineConfig.hpp"
#include "compile/support/compiler_project_env_or_smoke_echo.hpp"
#include "compile/support/structure_kernel_smoke_runner.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_write_execution_jsons_if_requested.hpp"
#include "__callable/__latency_fn_compiler_pipeline_runner_run_batch.hpp"
#include "__callable/__latency_fn_compiler_pipeline_runner_run_single.hpp"
#include "__callable/__latency_fn_compiler_project_run_row_artifacts_write_rows_tsv_if_requested.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_batch_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_batch_is_complete.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_is_complete.hpp"
#include "compile/support/compiler_project_env_or_smoke_echo.hpp"

namespace scpp {
	using namespace ::scpp;

void structure_kernel_run_compiler_project_env_or_smoke() {
	SCPP_CALL_DEPTH_GUARD("structure_kernel_run_compiler_project_env_or_smoke", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_project_env_or_smoke_echo.phs", 2);
	shared_p<PipelineBatchConfig> envBatch = __latency_fn_pipeline_config_helpers_batch_from_env();
	if (static_cast<bool>(php::condition_truthy(__latency_fn_pipeline_config_helpers_batch_is_complete(envBatch)))) {
		shared_p<CompilerProjectRunReport> envReport = __latency_fn_compiler_pipeline_runner_run_batch(envBatch);
		__latency_fn_compiler_project_run_row_artifacts_write_rows_tsv_if_requested(envReport);
		__latency_fn_compiler_export_artifacts_write_execution_jsons_if_requested(envReport);
		php::echo_one(string_t("v2_compiler_project_runner\n"));
		php::echo_one(string_t("compiler_project_runner=active\n"));
		php::echo_one(string_t("compiler_project_runner_rows="));
		php::echo_one(cast<int_t<>>(envReport->row_count));
		php::echo_one(string_t("\n"));
		php::echo_one(string_t("compiler_project_runner_completed="));
		php::echo_one(cast<int_t<>>(envReport->completed_count));
		php::echo_one(string_t("\n"));
		php::echo_one(string_t("compiler_project_runner_blocked="));
		php::echo_one(cast<int_t<>>(envReport->blocked_count));
		php::echo_one(string_t("\n"));
	}
	else {
		shared_p<PipelineConfig> envConfig = __latency_fn_pipeline_config_helpers_from_env();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_pipeline_config_helpers_is_complete(envConfig)))) {
			shared_p<CompilerProjectRunReport> envReport = __latency_fn_compiler_pipeline_runner_run_single(envConfig);
			__latency_fn_compiler_project_run_row_artifacts_write_rows_tsv_if_requested(envReport);
			__latency_fn_compiler_export_artifacts_write_execution_jsons_if_requested(envReport);
			php::echo_one(string_t("v2_compiler_project_runner\n"));
			php::echo_one(string_t("compiler_project_runner=active\n"));
			php::echo_one(string_t("compiler_project_runner_rows="));
			php::echo_one(cast<int_t<>>(envReport->row_count));
			php::echo_one(string_t("\n"));
			php::echo_one(string_t("compiler_project_runner_completed="));
			php::echo_one(cast<int_t<>>(envReport->completed_count));
			php::echo_one(string_t("\n"));
			php::echo_one(string_t("compiler_project_runner_blocked="));
			php::echo_one(cast<int_t<>>(envReport->blocked_count));
			php::echo_one(string_t("\n"));
		}
		else {
			structure_kernel_run_smoke();
		}
	}
}

}

