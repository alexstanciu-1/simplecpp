#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/EmissionLLVMCompositeTextSnapshot.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_composite_text_mode_none_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_composite_text_mode_direct_call_target_requests_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_composite_text_mode_fixed_arg_parameter_add_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_composite_text_mode_none_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_empty_composite_text_snapshot.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_composite_text_mode_direct_call_target_requests_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_direct_call_composite_text_snapshot.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_empty_composite_text_snapshot.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_composite_text_mode_fixed_arg_parameter_add_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_empty_composite_text_snapshot.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_fixed_arg_parameter_add_composite_text_snapshot.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_composite_text_mode_none_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::composite_text_mode_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[203]);
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_composite_text_mode_direct_call_target_requests_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::composite_text_mode_direct_call_target_requests_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[204]);
	return static_cast<int_t<> >(1);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_composite_text_mode_fixed_arg_parameter_add_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::composite_text_mode_fixed_arg_parameter_add_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[205]);
	return static_cast<int_t<> >(2);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<EmissionLLVMCompositeTextSnapshot> __latency_fn_llvm_text_from_plan_empty_composite_text_snapshot() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::empty_composite_text_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[206]);
	shared_p<EmissionLLVMCompositeTextSnapshot> snapshot = create<EmissionLLVMCompositeTextSnapshot>();
	snapshot->mode_id = __latency_fn_llvm_text_from_plan_composite_text_mode_none_id();
	snapshot->target_requests = create<BackendRequestAuthorizationArtifact>();
	snapshot->target_plan = create<LoweringPlan>();
	return snapshot;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<EmissionLLVMCompositeTextSnapshot> __latency_fn_llvm_text_from_plan_direct_call_composite_text_snapshot(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow targetSymbol, shared_p<BackendRequestAuthorizationArtifact>& targetRequests, shared_p<LoweringPlan> targetPlan) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::direct_call_composite_text_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[207]);
	shared_p<EmissionLLVMCompositeTextSnapshot> snapshot = __latency_fn_llvm_text_from_plan_empty_composite_text_snapshot();
	snapshot->mode_id = __latency_fn_llvm_text_from_plan_composite_text_mode_direct_call_target_requests_id();
	snapshot->target_llvm_function_name = __latency_fn_llvm_text_from_plan_llvm_function_name_for_symbol(symbols, targetSymbol->symbol_id);
	snapshot->target_source_unit_id = cast<int_t<>>(targetSymbol->source_unit_id);
	snapshot->target_symbol_id = cast<int_t<>>(targetSymbol->symbol_id);
	snapshot->target_requests = targetRequests;
	snapshot->target_plan = targetPlan;
	return snapshot;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<EmissionLLVMCompositeTextSnapshot> __latency_fn_llvm_text_from_plan_fixed_arg_parameter_add_composite_text_snapshot(const string_t& targetName, int_t<> rightLiteralValue) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::fixed_arg_parameter_add_composite_text_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[208]);
	shared_p<EmissionLLVMCompositeTextSnapshot> snapshot = __latency_fn_llvm_text_from_plan_empty_composite_text_snapshot();
	snapshot->mode_id = __latency_fn_llvm_text_from_plan_composite_text_mode_fixed_arg_parameter_add_id();
	snapshot->target_llvm_function_name = targetName;
	snapshot->fixed_arg_right_literal_value = rightLiteralValue;
	return snapshot;
}

}
