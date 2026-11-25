#include <scpp/lang/php.hpp>
#include "__types/source_files.hpp"
#include "__callable/__latency_fn_source_files_read_text__exec.hpp"
#include "__callable/__latency_fn_source_files_content_or_empty.hpp"
#include "__callable/__latency_fn_source_files_read_text.hpp"
namespace scpp { extern const int __latency_lines_source_files[]; }
namespace scpp {
bool_t source_files::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == source_files::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_source_files[]; }
namespace scpp {
bool_t __latency_fn_source_files_read_text__exec(const string_t& path, string_t& text) {
	text = string_t("");
	error_t err;
	if (static_cast<bool>(php::condition_truthy(php::take(text, err, fs::get(path))))) {
		return bool_t(static_cast<bool_t>(true));
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_source_files[]; }
namespace scpp {
string_t __latency_fn_source_files_content_or_empty(const string_t& path) {
	SCPP_CALL_DEPTH_GUARD("source_files::content_or_empty", "/tmp/scpp-edit-latency-20260919/app/compile/support/source_files.phs", __latency_lines_source_files[0]);
	string_t text = required_cast<string_t>(string_t(""));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_source_files_read_text(path, text)))) {
		return text;
	}
	return string_t("");
}

}
