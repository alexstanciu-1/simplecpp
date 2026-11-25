#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct LlvmApiToolchainProbeRow;
LlvmApiToolchainProbeRow __latency_fn_llvm_api_toolchain_probe_probe_row_from_candidate(int_t<std::uint32_t> toolchainId, int_t<std::uint16_t> candidateSourceId, const string_t& commandName, int_t<std::uint16_t> versionMajor, int_t<std::uint16_t> versionMinor, int_t<std::uint16_t> commandStatusId, int_t<std::uint16_t> headerStatusId, int_t<std::uint16_t> libraryStatusId);
}
