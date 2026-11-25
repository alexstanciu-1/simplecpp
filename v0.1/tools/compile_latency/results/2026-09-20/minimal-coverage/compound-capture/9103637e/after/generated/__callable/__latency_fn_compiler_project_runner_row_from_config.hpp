#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CompilerProjectRunRow;
class PipelineConfig;
CompilerProjectRunRow __latency_fn_compiler_project_runner_row_from_config(int_t<std::uint32_t> runId, shared_p<PipelineConfig> config);
}
