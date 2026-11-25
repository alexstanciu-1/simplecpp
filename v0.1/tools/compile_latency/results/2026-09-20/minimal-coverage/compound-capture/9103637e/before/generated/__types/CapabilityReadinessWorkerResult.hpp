#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class CapabilityReadinessWorkerResult {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> symbol_id = static_cast<int_t<> >(0);
	int_t<> type_ref_rows = static_cast<int_t<> >(0);
	int_t<> type_arg_rows = static_cast<int_t<> >(0);
	int_t<> capability_rows = static_cast<int_t<> >(0);
	int_t<> provider_rows = static_cast<int_t<> >(0);
	int_t<> consumer_rows = static_cast<int_t<> >(0);
	int_t<> readiness_rows = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_rows = static_cast<int_t<> >(0);
	int_t<> published_rows = static_cast<int_t<> >(0);
	int_t<> semantic_hash = static_cast<int_t<> >(0);
};
}
