#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct ProjectManifestSourceRow {
	ProjectManifestSourceRow* operator->() { return this; }
	const ProjectManifestSourceRow* operator->() const { return this; }
	int_t<std::uint32_t> source_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> language_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> relative_path_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_unit_key_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
};
}
