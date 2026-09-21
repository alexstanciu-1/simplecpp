#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/LlvmApiHandleMapSummaryRow.hpp"
#include "__types/LlvmApiModuleBuildRow.hpp"
#include "__types/LlvmApiSinkPreflightRow.hpp"
namespace scpp {
struct LlvmApiSinkPreflightArtifact {
	LlvmApiSinkPreflightArtifact* operator->() { return this; }
	const LlvmApiSinkPreflightArtifact* operator->() const { return this; }
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> preflight_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> preflight_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> module_build_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> module_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> module_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> handle_summary_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> handle_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> handle_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<LlvmApiSinkPreflightRow> preflights = vector_t<LlvmApiSinkPreflightRow>{};
	vector_t<LlvmApiModuleBuildRow> module_builds = vector_t<LlvmApiModuleBuildRow>{};
	vector_t<LlvmApiHandleMapSummaryRow> handle_summaries = vector_t<LlvmApiHandleMapSummaryRow>{};
};
}
