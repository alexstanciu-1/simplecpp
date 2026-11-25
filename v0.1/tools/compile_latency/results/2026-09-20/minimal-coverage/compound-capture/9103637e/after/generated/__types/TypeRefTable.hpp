#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/TypeArgRow.hpp"
#include "__types/TypeRefRow.hpp"
namespace scpp {
struct TypeRefTable {
	TypeRefTable* operator->() { return this; }
	const TypeRefTable* operator->() const { return this; }
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> lookup_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> type_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> arg_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<TypeRefRow> types = vector_t<TypeRefRow>{};
	vector_t<TypeArgRow> args = vector_t<TypeArgRow>{};
};
}
