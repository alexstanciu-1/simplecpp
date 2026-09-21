#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/LoweringStep.hpp"
#include "__callable/__latency_fn_lowering_plan_append_work_ref.hpp"
#include "__callable/__latency_fn_lowering_plan_refresh_step_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_append_step.hpp"
#include "__callable/__latency_fn_lowering_plan_refresh_step_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_lowering_plan_append_binary_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_append_local_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_append_call_argument.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_binary_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_lowering_plan_local_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_lowering_plan_call_argument_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_append_work_ref(shared_p<LoweringPlan> plan, int_t<std::uint32_t> workId) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::append_work_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[30]);
	(void) plan->work_ids.append(workId);
	plan->work_ref_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(plan->work_ids));
	__latency_fn_lowering_plan_refresh_step_count(plan);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_append_step(shared_p<LoweringPlan> plan, LoweringStep step) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::append_step", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[31]);
	step->step_id = __latency_fn_structure_row_ids_next_dense_id((cast<int_t<>>(plan->work_ref_count) + php::count(plan->steps)));
	(void) plan->steps.append(step);
	__latency_fn_lowering_plan_refresh_step_count(plan);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_append_binary_operand(shared_p<LoweringPlan> plan, BackendBinaryOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::append_binary_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[32]);
	(void) plan->binary_operands.append(row);
	plan->binary_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(plan->binary_operands));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_append_local_operand(shared_p<LoweringPlan> plan, BackendLocalOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::append_local_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[33]);
	(void) plan->local_operands.append(row);
	plan->local_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(plan->local_operands));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_append_call_argument(shared_p<LoweringPlan> plan, BackendCallArgumentRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::append_call_argument", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[34]);
	(void) plan->call_arguments.append(row);
	plan->call_argument_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(plan->call_arguments));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_append_control_flow_operand(shared_p<LoweringPlan> plan, BackendControlFlowOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::append_control_flow_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[35]);
	(void) plan->control_flow_operands.append(row);
	plan->control_flow_operand_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(plan->control_flow_operands));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
BackendBinaryOperandRow __latency_fn_lowering_plan_binary_operand_by_owner_row_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::binary_operand_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[36]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(plan->binary_operand_count))))) {
		BackendBinaryOperandRow row = plan->binary_operands[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	int_t<> low = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> high = required_cast<int_t<>>((cast<int_t<>>(plan->binary_operand_count) - static_cast<int_t<> >(1)));
	while (static_cast<bool>((low <= high))) {
		int_t<> middle = required_cast<int_t<>>(cast<int_t<>>(((low + high) / static_cast<int_t<> >(2))));
		BackendBinaryOperandRow row = plan->binary_operands[middle];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
		if (static_cast<bool>((cast<int_t<>>(row->owner_row_id) < cast<int_t<>>(ownerRowId)))) {
			low = (middle + static_cast<int_t<> >(1));
		}
		else {
			high = (middle - static_cast<int_t<> >(1));
		}
	}
	auto __latency_local_0 = plan->binary_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	BackendBinaryOperandRow empty = BackendBinaryOperandRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
BackendLocalOperandRow __latency_fn_lowering_plan_local_operand_by_owner_row_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::local_operand_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[37]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(plan->local_operand_count))))) {
		BackendLocalOperandRow row = plan->local_operands[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	int_t<> low = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> high = required_cast<int_t<>>((cast<int_t<>>(plan->local_operand_count) - static_cast<int_t<> >(1)));
	while (static_cast<bool>((low <= high))) {
		int_t<> middle = required_cast<int_t<>>(cast<int_t<>>(((low + high) / static_cast<int_t<> >(2))));
		BackendLocalOperandRow row = plan->local_operands[middle];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
		if (static_cast<bool>((cast<int_t<>>(row->owner_row_id) < cast<int_t<>>(ownerRowId)))) {
			low = (middle + static_cast<int_t<> >(1));
		}
		else {
			high = (middle - static_cast<int_t<> >(1));
		}
	}
	auto __latency_local_0 = plan->local_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	BackendLocalOperandRow empty = BackendLocalOperandRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
BackendCallArgumentRow __latency_fn_lowering_plan_call_argument_by_owner_row_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::call_argument_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[38]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(plan->call_argument_count))))) {
		BackendCallArgumentRow row = plan->call_arguments[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	int_t<> low = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> high = required_cast<int_t<>>((cast<int_t<>>(plan->call_argument_count) - static_cast<int_t<> >(1)));
	while (static_cast<bool>((low <= high))) {
		int_t<> middle = required_cast<int_t<>>(cast<int_t<>>(((low + high) / static_cast<int_t<> >(2))));
		BackendCallArgumentRow row = plan->call_arguments[middle];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
		if (static_cast<bool>((cast<int_t<>>(row->owner_row_id) < cast<int_t<>>(ownerRowId)))) {
			low = (middle + static_cast<int_t<> >(1));
		}
		else {
			high = (middle - static_cast<int_t<> >(1));
		}
	}
	auto __latency_local_0 = plan->call_arguments;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	BackendCallArgumentRow empty = BackendCallArgumentRow{};
	return empty;
}

}
