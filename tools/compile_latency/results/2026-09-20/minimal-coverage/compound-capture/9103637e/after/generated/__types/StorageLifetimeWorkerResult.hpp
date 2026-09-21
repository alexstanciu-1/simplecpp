#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class StorageLifetimeWorkerResult {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> symbol_id = static_cast<int_t<> >(0);
	int_t<> request_rows = static_cast<int_t<> >(0);
	int_t<> ready_rows = static_cast<int_t<> >(0);
	int_t<> blocked_rows = static_cast<int_t<> >(0);
	int_t<> storage_context_rows = static_cast<int_t<> >(0);
	int_t<> copy_policy_rows = static_cast<int_t<> >(0);
	int_t<> cleanup_policy_rows = static_cast<int_t<> >(0);
	int_t<> lifetime_policy_rows = static_cast<int_t<> >(0);
	int_t<> published_rows = static_cast<int_t<> >(0);
	int_t<> semantic_hash = static_cast<int_t<> >(0);
};
}
