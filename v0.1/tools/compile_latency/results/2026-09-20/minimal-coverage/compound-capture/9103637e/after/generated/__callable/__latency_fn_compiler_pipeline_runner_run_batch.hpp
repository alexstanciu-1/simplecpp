#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class PipelineBatchConfig;
shared_p<CompilerProjectRunReport> __latency_fn_compiler_pipeline_runner_run_batch(shared_p<PipelineBatchConfig> batch);
}
