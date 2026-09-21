#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class EmissionLLVMWorkerResult {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> symbol_id = static_cast<int_t<> >(0);
	int_t<> decision_rows = static_cast<int_t<> >(0);
	int_t<> ready_rows = static_cast<int_t<> >(0);
	int_t<> blocked_rows = static_cast<int_t<> >(0);
	int_t<> value_rows = static_cast<int_t<> >(0);
	int_t<> block_rows = static_cast<int_t<> >(0);
	int_t<> preflight_rows = static_cast<int_t<> >(0);
	int_t<> preflight_ready_rows = static_cast<int_t<> >(0);
	int_t<> preflight_blocked_rows = static_cast<int_t<> >(0);
	int_t<> sink_rows = static_cast<int_t<> >(0);
	int_t<> sink_ready_rows = static_cast<int_t<> >(0);
	int_t<> sink_blocked_rows = static_cast<int_t<> >(0);
	int_t<> text_nonempty_rows = static_cast<int_t<> >(0);
	int_t<> text_bytes = static_cast<int_t<> >(0);
	int_t<> published_rows = static_cast<int_t<> >(0);
	int_t<> semantic_hash = static_cast<int_t<> >(0);
};
}
