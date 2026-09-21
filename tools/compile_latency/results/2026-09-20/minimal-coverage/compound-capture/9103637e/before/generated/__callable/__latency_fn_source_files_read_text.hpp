#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_source_files__norm_read_text__text.hpp"
#include "__callable/__latency_fn_source_files_read_text__exec.hpp"
#pragma once
namespace scpp {
	template <typename T_text>
	bool_t __latency_fn_source_files_read_text(const string_t& path, T_text&& _text) {
	string_t& text = __latency_fn_source_files__norm_read_text__text(std::forward<T_text>(_text));
		return __latency_fn_source_files_read_text__exec(path, text);
	}

}
