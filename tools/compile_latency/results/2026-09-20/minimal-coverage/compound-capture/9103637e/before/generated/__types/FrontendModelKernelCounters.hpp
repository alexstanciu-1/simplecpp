#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class FrontendModelKernelCounters {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> reserve_call_count = static_cast<int_t<> >(0);
	int_t<> append_call_count = static_cast<int_t<> >(0);
	int_t<> lookup_call_count = static_cast<int_t<> >(0);
	int_t<> update_call_count = static_cast<int_t<> >(0);
	int_t<> materializer_call_count = static_cast<int_t<> >(0);
};
}
