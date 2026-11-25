#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
struct LlvmApiToolchainProbeRow {
	LlvmApiToolchainProbeRow* operator->() { return this; }
	const LlvmApiToolchainProbeRow* operator->() const { return this; }
	int_t<std::uint32_t> toolchain_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> candidate_source_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> command_name_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> version_major = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> version_minor = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> command_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> header_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> library_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> blocked_reason_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
};
}
