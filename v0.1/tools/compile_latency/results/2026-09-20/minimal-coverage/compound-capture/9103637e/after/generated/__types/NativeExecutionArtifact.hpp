#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class NativeExecutionArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t status = string_t("");
	string_t reason = string_t("");
	string_t llvm_text_status = string_t("");
	string_t native_compile_status = string_t("");
	string_t native_link_status = string_t("");
	string_t native_exit_status = string_t("");
	int_t<> native_compile_exit_code = static_cast<int_t<> >(0);
	bool_t has_native_compile_exit_code = bool_t(static_cast<bool_t>(false));
	int_t<> exit_code = static_cast<int_t<> >(0);
	bool_t has_exit_code = bool_t(static_cast<bool_t>(false));
};
}
