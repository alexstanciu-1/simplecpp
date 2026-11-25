#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/LoweringBlockedRequestRow.hpp"
#include "__types/LoweringStep.hpp"
namespace scpp {
class LoweringPlan {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> plan_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> backend_emit_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> owner_source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> owner_symbol_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> step_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> work_ref_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> blocked_request_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> binary_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> local_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> call_argument_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> control_flow_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<int_t<std::uint32_t>> work_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<LoweringStep> steps = vector_t<LoweringStep>{};
	vector_t<LoweringBlockedRequestRow> blocked_requests = vector_t<LoweringBlockedRequestRow>{};
	vector_t<BackendBinaryOperandRow> binary_operands = vector_t<BackendBinaryOperandRow>{};
	vector_t<BackendLocalOperandRow> local_operands = vector_t<BackendLocalOperandRow>{};
	vector_t<BackendCallArgumentRow> call_arguments = vector_t<BackendCallArgumentRow>{};
	vector_t<BackendControlFlowOperandRow> control_flow_operands = vector_t<BackendControlFlowOperandRow>{};
};
}
