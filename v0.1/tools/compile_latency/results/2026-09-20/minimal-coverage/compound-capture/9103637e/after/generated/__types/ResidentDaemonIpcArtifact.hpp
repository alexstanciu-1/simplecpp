#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ResidentDaemonDirtySourceProjectionRow.hpp"
#include "__types/ResidentDaemonLoopPolicyRow.hpp"
#include "__types/ResidentDaemonRequestRow.hpp"
#include "__types/ResidentDaemonResponseRow.hpp"
#include "__types/ResidentDaemonSessionRow.hpp"
#include "__types/ResidentDaemonSourceSlotEventRow.hpp"
#include "__types/ResidentDaemonSourceSlotScanRow.hpp"
#include "__types/ResidentDaemonSourceSlotStatRow.hpp"
namespace scpp {
class ResidentDaemonIpcArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> transport_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> session_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> request_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> response_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_slot_event_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_slot_stat_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_slot_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> dirty_source_projection_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> loop_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> path_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> text_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<ResidentDaemonSessionRow> sessions = vector_t<ResidentDaemonSessionRow>{};
	vector_t<ResidentDaemonRequestRow> requests = vector_t<ResidentDaemonRequestRow>{};
	vector_t<ResidentDaemonResponseRow> responses = vector_t<ResidentDaemonResponseRow>{};
	vector_t<ResidentDaemonSourceSlotEventRow> source_slot_events = vector_t<ResidentDaemonSourceSlotEventRow>{};
	vector_t<ResidentDaemonSourceSlotStatRow> source_slot_stats = vector_t<ResidentDaemonSourceSlotStatRow>{};
	vector_t<ResidentDaemonSourceSlotScanRow> source_slot_scans = vector_t<ResidentDaemonSourceSlotScanRow>{};
	vector_t<ResidentDaemonDirtySourceProjectionRow> dirty_source_projections = vector_t<ResidentDaemonDirtySourceProjectionRow>{};
	vector_t<ResidentDaemonLoopPolicyRow> loops = vector_t<ResidentDaemonLoopPolicyRow>{};
	vector_t<string_t> paths = vector_t<string_t>{};
	vector_t<string_t> texts = vector_t<string_t>{};
};
}
