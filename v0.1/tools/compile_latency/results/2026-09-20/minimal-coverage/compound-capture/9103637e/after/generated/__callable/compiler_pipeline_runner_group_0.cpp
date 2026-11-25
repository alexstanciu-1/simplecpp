#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PipelineBatchConfig.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/compiler_pipeline_runner.hpp"
#include "__callable/__latency_fn_compiler_pipeline_runner_run_batch.hpp"
#include "__callable/__latency_fn_compiler_project_runner_run_batch.hpp"
#include "__callable/__latency_fn_compiler_pipeline_runner_run_single.hpp"
#include "__callable/__latency_fn_compiler_project_runner_run_single.hpp"
#include "__callable/__latency_fn_compiler_pipeline_runner_run_batch_against_previous.hpp"
#include "__callable/__latency_fn_compiler_project_runner_run_batch_against_previous.hpp"
namespace scpp { extern const int __latency_lines_compiler_pipeline_runner[]; }
namespace scpp {
bool_t compiler_pipeline_runner::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == compiler_pipeline_runner::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_compiler_pipeline_runner[]; }
namespace scpp {
shared_p<CompilerProjectRunReport> __latency_fn_compiler_pipeline_runner_run_batch(shared_p<PipelineBatchConfig> batch) {
	SCPP_CALL_DEPTH_GUARD("compiler_pipeline_runner::run_batch", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_pipeline_runner.phs", __latency_lines_compiler_pipeline_runner[0]);
	return __latency_fn_compiler_project_runner_run_batch(batch);
}

}

namespace scpp { extern const int __latency_lines_compiler_pipeline_runner[]; }
namespace scpp {
shared_p<CompilerProjectRunReport> __latency_fn_compiler_pipeline_runner_run_single(shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("compiler_pipeline_runner::run_single", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_pipeline_runner.phs", __latency_lines_compiler_pipeline_runner[1]);
	return __latency_fn_compiler_project_runner_run_single(config);
}

}

namespace scpp { extern const int __latency_lines_compiler_pipeline_runner[]; }
namespace scpp {
shared_p<CompilerProjectRunReport> __latency_fn_compiler_pipeline_runner_run_batch_against_previous(shared_p<PipelineBatchConfig> batch, shared_p<CompilerProjectRunReport> previous) {
	SCPP_CALL_DEPTH_GUARD("compiler_pipeline_runner::run_batch_against_previous", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_pipeline_runner.phs", __latency_lines_compiler_pipeline_runner[2]);
	return __latency_fn_compiler_project_runner_run_batch_against_previous(batch, previous);
}

}
