#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class BackendStackValue;
class BackendValueStack {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> count = static_cast<int_t<> >(0);
	int_t<> max_count = static_cast<int_t<> >(0);
	int_t<> capacity = static_cast<int_t<> >(0);
	int_t<> status_id = static_cast<int_t<> >(1);
	bool_t overflowed = bool_t(static_cast<bool_t>(false));
	bool_t underflowed = bool_t(static_cast<bool_t>(false));
	vector_t<shared_p<BackendStackValue>> values = vector_t<shared_p<BackendStackValue>>{};
};
}
