#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PipelineConfig;
bool_t __latency_fn_pure_tdd_stage_artifacts_write_text(shared_p<PipelineConfig> config, const string_t& fileName, const string_t& text);
}
