#include <scpp/lang/php.hpp>
#include "__types/PipelineConfig.hpp"
#include "__types/prepare_outputs.hpp"
#include "__callable/__latency_fn_output_paths_ensure_dir.hpp"
#include "__callable/__latency_fn_prepare_outputs_dirs.hpp"
namespace scpp { extern const int __latency_lines_prepare_outputs[]; }
namespace scpp {
bool_t prepare_outputs::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == prepare_outputs::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_prepare_outputs[]; }
namespace scpp {
bool_t __latency_fn_prepare_outputs_dirs(shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("prepare_outputs::dirs", "/tmp/scpp-edit-latency-20260919/app/compile/01_prepare_outputs.phs", __latency_lines_prepare_outputs[0]);
	bool_t ok = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	if (static_cast<bool>((!__latency_fn_output_paths_ensure_dir(config->frontend_out_dir)))) {
		ok = bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>((!__latency_fn_output_paths_ensure_dir(config->analyzer_out_dir)))) {
		ok = bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>((!__latency_fn_output_paths_ensure_dir(config->lowering_out_dir)))) {
		ok = bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>((!__latency_fn_output_paths_ensure_dir(config->native_out_dir)))) {
		ok = bool_t(static_cast<bool_t>(false));
	}
	return bool_t(ok);
}

}
