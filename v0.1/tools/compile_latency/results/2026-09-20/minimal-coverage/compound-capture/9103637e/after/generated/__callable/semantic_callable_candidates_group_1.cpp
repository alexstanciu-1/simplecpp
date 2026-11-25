#include <scpp/lang/php.hpp>
#include "__types/SemanticCallableCandidateRow.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_row_by_helper_key.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_rows.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_candidate_count.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_debug_string.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_generator_allowed_candidate_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_count.hpp"
namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
shared_p<SemanticCallableCandidateRow> __latency_fn_semantic_callable_candidates_row_by_helper_key(const string_t& helperKey) {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::row_by_helper_key", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[12]);
	auto __latency_local_0 = __latency_fn_semantic_callable_candidates_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(row->helper_key, helperKey))) {
			return row;
		}
	}
	shared_p<SemanticCallableCandidateRow> empty = create<SemanticCallableCandidateRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
string_t __latency_fn_semantic_callable_candidates_debug_string() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::debug_string", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[13]);
	return (string_t("semantic_callable_candidates:rows=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_callable_candidates_candidate_count())) + string_t(":runtime_helpers=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_declarations_helper_count())) + string_t(":generator_allowed=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_callable_candidates_generator_allowed_candidate_count())) + string_t(":source_consumption_blocked=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_callable_candidates_candidate_count())) + string_t(":state=runtime_helper_candidates_ready_source_resolution_blocked"));
}

}
