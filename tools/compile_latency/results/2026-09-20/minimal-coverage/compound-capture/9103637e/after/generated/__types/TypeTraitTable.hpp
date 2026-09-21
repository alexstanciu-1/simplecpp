#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/TypeTraitRow.hpp"
namespace scpp {
struct TypeTraitTable {
	TypeTraitTable* operator->() { return this; }
	const TypeTraitTable* operator->() const { return this; }
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> lookup_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> lookup_policy_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> lookup_index_threshold = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lookup_index_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> trait_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> numeric_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> numeric_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<TypeTraitRow> traits = vector_t<TypeTraitRow>{};
	vector_t<int_t<std::uint32_t>> trait_row_ids_by_type_ref_id = vector_t<int_t<std::uint32_t>>{};
};
}
