#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/LlvmApiToolchainProbeRow.hpp"
namespace scpp {
struct LlvmApiToolchainProbeArtifact {
	LlvmApiToolchainProbeArtifact* operator->() { return this; }
	const LlvmApiToolchainProbeArtifact* operator->() const { return this; }
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> explicit_path_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> versioned_command_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> unversioned_command_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<LlvmApiToolchainProbeRow> probes = vector_t<LlvmApiToolchainProbeRow>{};
};
}
