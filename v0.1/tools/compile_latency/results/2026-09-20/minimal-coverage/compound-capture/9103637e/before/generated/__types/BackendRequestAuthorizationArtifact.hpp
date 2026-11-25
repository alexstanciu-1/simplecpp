#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
namespace scpp {
class BackendLocalImmediateTextOperandRow;
class BackendRequestRowList;
class BackendStringLiteralOperandRow;
class BackendRequestAuthorizationArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> request_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> request_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> binary_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> local_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> call_argument_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> control_flow_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> string_literal_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> local_immediate_text_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	shared_p<BackendRequestRowList> request_rows;
	vector_t<BackendBinaryOperandRow> binary_operands = vector_t<BackendBinaryOperandRow>{};
	vector_t<BackendLocalOperandRow> local_operands = vector_t<BackendLocalOperandRow>{};
	vector_t<BackendCallArgumentRow> call_arguments = vector_t<BackendCallArgumentRow>{};
	vector_t<BackendControlFlowOperandRow> control_flow_operands = vector_t<BackendControlFlowOperandRow>{};
	vector_t<shared_p<BackendStringLiteralOperandRow>> string_literal_operands = vector_t<shared_p<BackendStringLiteralOperandRow>>{};
	vector_t<shared_p<BackendLocalImmediateTextOperandRow>> local_immediate_text_operands = vector_t<shared_p<BackendLocalImmediateTextOperandRow>>{};
	BackendRequestAuthorizationArtifact();
};
}
