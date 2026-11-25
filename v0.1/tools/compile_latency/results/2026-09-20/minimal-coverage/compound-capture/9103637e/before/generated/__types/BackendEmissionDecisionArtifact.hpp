#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendEmissionBlockRow.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
namespace scpp {
struct BackendEmissionDecisionArtifact {
	BackendEmissionDecisionArtifact* operator->() { return this; }
	const BackendEmissionDecisionArtifact* operator->() const { return this; }
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> emission_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> owner_source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> owner_symbol_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> decision_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> value_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> block_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> binary_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> local_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> call_argument_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> control_flow_operand_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<BackendEmissionDecisionRow> decisions = vector_t<BackendEmissionDecisionRow>{};
	vector_t<BackendEmissionValueRow> values = vector_t<BackendEmissionValueRow>{};
	vector_t<BackendEmissionBlockRow> blocks = vector_t<BackendEmissionBlockRow>{};
	vector_t<BackendBinaryOperandRow> binary_operands = vector_t<BackendBinaryOperandRow>{};
	vector_t<BackendLocalOperandRow> local_operands = vector_t<BackendLocalOperandRow>{};
	vector_t<BackendCallArgumentRow> call_arguments = vector_t<BackendCallArgumentRow>{};
	vector_t<BackendControlFlowOperandRow> control_flow_operands = vector_t<BackendControlFlowOperandRow>{};
};
}
