#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PipelineConfig;
bool_t __latency_fn_compiler_export_artifacts_write_stage_text(shared_p<PipelineConfig> config, const string_t& fileName, const string_t& text);
}
