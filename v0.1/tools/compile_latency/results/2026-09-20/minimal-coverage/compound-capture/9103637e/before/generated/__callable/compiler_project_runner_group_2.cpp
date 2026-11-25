#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/PipelineBatchConfig.hpp"
#include "__types/PipelineConfig.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_config_result.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_row.hpp"
#include "__callable/__latency_fn_compiler_project_runner_row_from_config.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_missing_config.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_row.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_frontend_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_missing_config_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_config_result.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_missing_config.hpp"
#include "__callable/__latency_fn_compiler_project_runner_run_batch.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_batch_is_complete.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_from_batch_run.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_config_result.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_missing_config.hpp"
#include "__callable/__latency_fn_compiler_project_runner_run_single.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_is_complete.hpp"
#include "__callable/__latency_fn_compiler_project_runner_run_batch.hpp"
#include "__callable/__latency_fn_compiler_project_runner_run_batch_against_previous.hpp"
namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
void __latency_fn_compiler_project_runner_append_config_result(shared_p<CompilerProjectRunReport>& report, shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::append_config_result", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[19]);
	int_t<std::uint32_t> runId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(report->rows)));
	CompilerProjectRunRow row = __latency_fn_compiler_project_runner_row_from_config(cast<int_t<std::uint32_t>>(runId), config);
	__latency_fn_compiler_project_runner_append_row(report, config->run_label, row);
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
void __latency_fn_compiler_project_runner_append_missing_config(shared_p<CompilerProjectRunReport>& report, const string_t& runLabel) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::append_missing_config", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[20]);
	CompilerProjectRunRow row = __latency_fn_compiler_project_runner_blocked_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->rows)), __latency_fn_compiler_project_runner_blocked_layer_frontend_id(), __latency_fn_compiler_project_runner_blocked_reason_missing_config_id());
	__latency_fn_compiler_project_runner_append_row(report, runLabel, row);
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
shared_p<CompilerProjectRunReport> __latency_fn_compiler_project_runner_run_batch(shared_p<PipelineBatchConfig> batch) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::run_batch", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[21]);
	shared_p<CompilerProjectRunReport> report = create<CompilerProjectRunReport>();
	if (static_cast<bool>((!__latency_fn_pipeline_config_helpers_batch_is_complete(batch)))) {
		__latency_fn_compiler_project_runner_append_missing_config(report, string_t(""));
		return report;
	}
	auto __latency_local_0 = batch->run_labels;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto runLabel = __latency_local_1.value_copy();
		shared_p<PipelineConfig> config = __latency_fn_pipeline_config_helpers_from_batch_run(batch, runLabel);
		__latency_fn_compiler_project_runner_append_config_result(report, config);
	}
	return report;
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
shared_p<CompilerProjectRunReport> __latency_fn_compiler_project_runner_run_single(shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::run_single", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[22]);
	shared_p<CompilerProjectRunReport> report = create<CompilerProjectRunReport>();
	if (static_cast<bool>((!__latency_fn_pipeline_config_helpers_is_complete(config)))) {
		__latency_fn_compiler_project_runner_append_missing_config(report, config->run_label);
		return report;
	}
	__latency_fn_compiler_project_runner_append_config_result(report, config);
	return report;
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
shared_p<CompilerProjectRunReport> __latency_fn_compiler_project_runner_run_batch_against_previous(shared_p<PipelineBatchConfig> batch, shared_p<CompilerProjectRunReport> previous) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::run_batch_against_previous", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[23]);
	return __latency_fn_compiler_project_runner_run_batch(batch);
}

}
