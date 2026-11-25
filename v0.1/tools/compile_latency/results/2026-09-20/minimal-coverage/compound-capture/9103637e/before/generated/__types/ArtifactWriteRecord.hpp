#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct ArtifactWriteRecord {
	ArtifactWriteRecord* operator->() { return this; }
	const ArtifactWriteRecord* operator->() const { return this; }
	int_t<std::uint32_t> record_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> artifact_key_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> path_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> byte_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
};
}
