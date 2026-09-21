#include <scpp/lang/php.hpp>
#include "__types/PipelineBatchConfig.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/pipeline_config_helpers.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_legacy_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_dir_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_legacy_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_run_label_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_legacy_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_root_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_legacy_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_run_labels_text_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_legacy_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_run_rows_tsv_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_legacy_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_run_artifact_path_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_legacy_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_run_execution_dir_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_selected_artifact_keys_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_dir_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_run_label_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_selected_artifact_keys_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_batch_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_root_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_run_labels_text_from_env.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_is_complete.hpp"
namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
bool_t pipeline_config_helpers::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == pipeline_config_helpers::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
string_t __latency_fn_pipeline_config_helpers_env_or_empty(const string_t& name) {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::env_or_empty", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[0]);
	string_t value = required_cast<string_t>(string_t(""));
	if (static_cast<bool>(php::condition_truthy(php::take(value, php::getenv(name))))) {
		return value;
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
string_t __latency_fn_pipeline_config_helpers_project_dir_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::project_dir_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[1]);
	return __latency_fn_pipeline_config_helpers_env_or_legacy_empty(string_t("SCPP_V2_PROJECT_DIR"), string_t("SCPP_V2_CASE_DIR"));
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
string_t __latency_fn_pipeline_config_helpers_run_label_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::run_label_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[2]);
	return __latency_fn_pipeline_config_helpers_env_or_legacy_empty(string_t("SCPP_V2_RUN_LABEL"), string_t("SCPP_V2_CASE_KEY"));
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
string_t __latency_fn_pipeline_config_helpers_project_root_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::project_root_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[3]);
	return __latency_fn_pipeline_config_helpers_env_or_legacy_empty(string_t("SCPP_V2_PROJECT_ROOT"), string_t("SCPP_V2_CASE_ROOT"));
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
string_t __latency_fn_pipeline_config_helpers_run_labels_text_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::run_labels_text_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[4]);
	return __latency_fn_pipeline_config_helpers_env_or_legacy_empty(string_t("SCPP_V2_RUN_LABELS"), string_t("SCPP_V2_CASE_KEYS"));
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
string_t __latency_fn_pipeline_config_helpers_project_run_rows_tsv_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::project_run_rows_tsv_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[5]);
	return __latency_fn_pipeline_config_helpers_env_or_legacy_empty(string_t("SCPP_V2_PROJECT_RUN_ROWS_TSV"), string_t("SCPP_V2_CASE_RUNNER_ROWS_TSV"));
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
string_t __latency_fn_pipeline_config_helpers_project_run_artifact_path_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::project_run_artifact_path_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[6]);
	return __latency_fn_pipeline_config_helpers_env_or_legacy_empty(string_t("SCPP_V2_PROJECT_RUN_ARTIFACT_PATH"), string_t("SCPP_V2_CASE_RUNNER_ARTIFACT_PATH"));
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
string_t __latency_fn_pipeline_config_helpers_project_run_execution_dir_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::project_run_execution_dir_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[7]);
	return __latency_fn_pipeline_config_helpers_env_or_legacy_empty(string_t("SCPP_V2_PROJECT_RUN_EXECUTION_DIR"), string_t("SCPP_V2_CASE_RUNNER_EXECUTION_DIR"));
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
vector_t<string_t> __latency_fn_pipeline_config_helpers_selected_artifact_keys_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::selected_artifact_keys_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[8]);
	vector_t<string_t> keys = {};
	string_t text = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SELECTED_ARTIFACT_KEYS")));
	if (static_cast<bool>(php::identical(text, string_t("")))) {
		return keys;
	}
	vector_t<string_t> parts = required_cast<vector_t<string_t>>(php::explode(string_t(","), text));
	auto& __latency_local_0 = parts;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto part = __latency_local_1.value_copy();
		string_t key = required_cast<string_t>(str::trim(part));
		if (static_cast<bool>(php::condition_truthy(php::not_identical(key, string_t(""))))) {
			(void) keys.push_back(key);
		}
	}
	return keys;
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
shared_p<PipelineConfig> __latency_fn_pipeline_config_helpers_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[9]);
	shared_p<PipelineConfig> config = create<PipelineConfig>();
	config->project_dir = __latency_fn_pipeline_config_helpers_project_dir_from_env();
	config->frontend_out_dir = __latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_FRONTEND_OUT_DIR"));
	config->analyzer_out_dir = __latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_ANALYZER_OUT_DIR"));
	config->lowering_out_dir = __latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_LOWERING_OUT_DIR"));
	config->native_out_dir = __latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_NATIVE_OUT_DIR"));
	config->run_label = __latency_fn_pipeline_config_helpers_run_label_from_env();
	config->selected_artifact_keys = __latency_fn_pipeline_config_helpers_selected_artifact_keys_from_env();
	return config;
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
shared_p<PipelineBatchConfig> __latency_fn_pipeline_config_helpers_batch_from_env() {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::batch_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[10]);
	shared_p<PipelineBatchConfig> config = create<PipelineBatchConfig>();
	config->project_root = __latency_fn_pipeline_config_helpers_project_root_from_env();
	config->build_root = __latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_BUILD_ROOT"));
	string_t runLabelsText = required_cast<string_t>(__latency_fn_pipeline_config_helpers_run_labels_text_from_env());
	if (static_cast<bool>(php::condition_truthy(php::not_identical(runLabelsText, string_t(""))))) {
		vector_t<string_t> parts = required_cast<vector_t<string_t>>(php::explode(string_t(","), runLabelsText));
		auto& __latency_local_0 = parts;
		for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
			auto part = __latency_local_1.value_copy();
			string_t key = required_cast<string_t>(str::trim(part));
			if (static_cast<bool>(php::condition_truthy(php::not_identical(key, string_t(""))))) {
				(void) config->run_labels.append(key);
			}
		}
	}
	return config;
}

}

namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
bool_t __latency_fn_pipeline_config_helpers_is_complete(shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::is_complete", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[11]);
	return (((((php::not_identical(config->project_dir, string_t("")) && php::not_identical(config->frontend_out_dir, string_t(""))) && php::not_identical(config->analyzer_out_dir, string_t(""))) && php::not_identical(config->lowering_out_dir, string_t(""))) && php::not_identical(config->native_out_dir, string_t(""))) && php::not_identical(config->run_label, string_t("")));
}

}
