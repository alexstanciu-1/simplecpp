#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PipelineConfig;
string_t __latency_fn_artifact_writes_path_from_config(shared_p<PipelineConfig> config, int_t<std::uint16_t> pathId);
}
