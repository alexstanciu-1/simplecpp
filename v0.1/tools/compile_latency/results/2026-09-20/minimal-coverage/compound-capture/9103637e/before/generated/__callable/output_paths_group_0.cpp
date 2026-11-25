#include <scpp/lang/php.hpp>
#include "__types/output_paths.hpp"
#include "__callable/__latency_fn_output_paths_ensure_dir.hpp"
#include "__callable/__latency_fn_output_paths_ensure_dir.hpp"
#include "__callable/__latency_fn_output_paths_ensure_parent_and_dir.hpp"
namespace scpp { extern const int __latency_lines_output_paths[]; }
namespace scpp {
bool_t output_paths::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == output_paths::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_output_paths[]; }
namespace scpp {
bool_t __latency_fn_output_paths_ensure_dir(const string_t& path) {
	SCPP_CALL_DEPTH_GUARD("output_paths::ensure_dir", "/tmp/scpp-edit-latency-20260919/app/compile/support/output_paths.phs", __latency_lines_output_paths[0]);
	if (static_cast<bool>(php::condition_truthy(fs::exists(path)))) {
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(fs::mkdir(path)))) {
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::condition_truthy(fs::exists(path)))) {
		return bool_t(static_cast<bool_t>(true));
	}
	php::echo_one(string_t("v2_mkdir_failed: "));
	php::echo_one(path);
	php::echo_one(string_t("\n"));
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_output_paths[]; }
namespace scpp {
bool_t __latency_fn_output_paths_ensure_parent_and_dir(const string_t& parentPath, const string_t& path) {
	SCPP_CALL_DEPTH_GUARD("output_paths::ensure_parent_and_dir", "/tmp/scpp-edit-latency-20260919/app/compile/support/output_paths.phs", __latency_lines_output_paths[1]);
	if (static_cast<bool>((!__latency_fn_output_paths_ensure_dir(parentPath)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return __latency_fn_output_paths_ensure_dir(path);
}

}
