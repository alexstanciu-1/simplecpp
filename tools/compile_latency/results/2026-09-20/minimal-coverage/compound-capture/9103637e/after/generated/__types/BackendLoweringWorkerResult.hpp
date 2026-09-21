#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class BackendLoweringWorkerResult {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> symbol_id = static_cast<int_t<> >(0);
	int_t<> backend_request_rows = static_cast<int_t<> >(0);
	int_t<> backend_request_ready_rows = static_cast<int_t<> >(0);
	int_t<> backend_request_blocked_rows = static_cast<int_t<> >(0);
	int_t<> lowering_plan_rows = static_cast<int_t<> >(0);
	int_t<> lowering_step_rows = static_cast<int_t<> >(0);
	int_t<> lowering_work_refs = static_cast<int_t<> >(0);
	int_t<> lowering_blocked_rows = static_cast<int_t<> >(0);
	int_t<> sidecar_rows = static_cast<int_t<> >(0);
	int_t<> owner_rows = static_cast<int_t<> >(0);
	int_t<> published_rows = static_cast<int_t<> >(0);
	int_t<> semantic_hash = static_cast<int_t<> >(0);
};
}
