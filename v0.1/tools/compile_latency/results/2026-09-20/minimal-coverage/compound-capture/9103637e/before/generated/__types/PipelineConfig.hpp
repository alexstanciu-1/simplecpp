#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class PipelineConfig {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t project_dir = string_t("");
	string_t frontend_out_dir = string_t("");
	string_t analyzer_out_dir = string_t("");
	string_t lowering_out_dir = string_t("");
	string_t native_out_dir = string_t("");
	string_t run_label = string_t("");
	vector_t<string_t> selected_artifact_keys = vector_t<string_t>{};
};
}
