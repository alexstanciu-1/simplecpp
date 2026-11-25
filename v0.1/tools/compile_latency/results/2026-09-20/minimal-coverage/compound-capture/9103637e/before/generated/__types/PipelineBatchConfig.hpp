#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class PipelineBatchConfig {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t project_root = string_t("");
	string_t build_root = string_t("");
	vector_t<string_t> run_labels = vector_t<string_t>{};
};
}
