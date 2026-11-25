#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_legacy_empty.hpp"
namespace scpp { extern const int __latency_lines_pipeline_config_helpers[]; }
namespace scpp {
string_t __latency_fn_pipeline_config_helpers_env_or_legacy_empty(const string_t& name, const string_t& legacyName) {
	SCPP_CALL_DEPTH_GUARD("pipeline_config_helpers::env_or_legacy_empty", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/pipeline_config_helpers.phs", __latency_lines_pipeline_config_helpers[15]);
	string_t value = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(name));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(value, string_t(""))))) {
		return value;
	}
	return __latency_fn_pipeline_config_helpers_env_or_empty(legacyName);
}

}
