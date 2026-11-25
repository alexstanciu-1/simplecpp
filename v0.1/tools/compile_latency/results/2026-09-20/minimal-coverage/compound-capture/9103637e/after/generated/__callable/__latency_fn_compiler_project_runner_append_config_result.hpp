#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class PipelineConfig;
void __latency_fn_compiler_project_runner_append_config_result(shared_p<CompilerProjectRunReport>& report, shared_p<PipelineConfig> config);
}
