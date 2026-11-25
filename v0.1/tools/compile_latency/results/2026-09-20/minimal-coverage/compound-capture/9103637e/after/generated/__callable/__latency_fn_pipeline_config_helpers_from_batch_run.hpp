#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PipelineBatchConfig;
class PipelineConfig;
shared_p<PipelineConfig> __latency_fn_pipeline_config_helpers_from_batch_run(shared_p<PipelineBatchConfig> batch, const string_t& runLabel);
}
