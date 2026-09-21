#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct ContractRegistryRow {
	ContractRegistryRow* operator->() { return this; }
	const ContractRegistryRow* operator->() const { return this; }
	int_t<std::uint16_t> contract_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
