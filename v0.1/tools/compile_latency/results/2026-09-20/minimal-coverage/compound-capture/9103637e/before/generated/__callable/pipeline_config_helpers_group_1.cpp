#include <scpp/lang/php.hpp>
#include "__types/PipelineBatchConfig.hpp"
#include "__types/PipelineConfig.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_batch_is_complete.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_from_batch_run.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_selected_artifact_keys_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_structure_smoke_config.hpp"
namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
bool_t __latency_fn_pipeline_config_helpers_batch_is_complete(shared_p<PipelineBatchConfig> config) {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::batch_is_complete", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[12]);
	return ((php::not_identical(config->project_root, string_t("")) && php::not_identical(config->build_root, string_t(""))) && (php::count(config->run_labels) > static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
shared_p<PipelineConfig> __latency_fn_pipeline_config_helpers_from_batch_run(shared_p<PipelineBatchConfig> batch, const string_t& runLabel) {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::from_batch_run", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[13]);
	shared_p<PipelineConfig> config = create<PipelineConfig>();
	config->run_label = runLabel;
	config->project_dir = (cast<string_t>(batch->project_root) + string_t("/") + cast<string_t>(runLabel));
	config->frontend_out_dir = (cast<string_t>(batch->build_root) + string_t("/phs_") + cast<string_t>(runLabel) + string_t("_frontend"));
	config->analyzer_out_dir = (cast<string_t>(batch->build_root) + string_t("/phs_") + cast<string_t>(runLabel) + string_t("_analyzer"));
	config->lowering_out_dir = (cast<string_t>(batch->build_root) + string_t("/phs_") + cast<string_t>(runLabel) + string_t("_lowering"));
	config->native_out_dir = (cast<string_t>(batch->build_root) + string_t("/phs_") + cast<string_t>(runLabel) + string_t("_native"));
	config->selected_artifact_keys = __latency_fn_pipeline_config_helpers_selected_artifact_keys_from_env();
	return config;
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
shared_p<PipelineConfig> __latency_fn_pipeline_config_helpers_structure_smoke_config(const string_t& root) {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::structure_smoke_config", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[14]);
	shared_p<PipelineConfig> config = create<PipelineConfig>();
	config->run_label = string_t("structure_kernel_reset");
	config->project_dir = (cast<string_t>(root) + string_t("/case"));
	config->frontend_out_dir = (cast<string_t>(root) + string_t("/frontend"));
	config->analyzer_out_dir = (cast<string_t>(root) + string_t("/analyzer"));
	config->lowering_out_dir = (cast<string_t>(root) + string_t("/lowering"));
	config->native_out_dir = (cast<string_t>(root) + string_t("/native"));
	return config;
}

}
