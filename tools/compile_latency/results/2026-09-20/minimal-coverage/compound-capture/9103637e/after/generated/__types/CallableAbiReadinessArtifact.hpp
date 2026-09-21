#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/CallableAbiReadinessRow.hpp"
namespace scpp {
struct CallableAbiReadinessArtifact {
	CallableAbiReadinessArtifact* operator->() { return this; }
	const CallableAbiReadinessArtifact* operator->() const { return this; }
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> abi_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> row_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> abi_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> emission_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<CallableAbiReadinessRow> rows = vector_t<CallableAbiReadinessRow>{};
};
}
