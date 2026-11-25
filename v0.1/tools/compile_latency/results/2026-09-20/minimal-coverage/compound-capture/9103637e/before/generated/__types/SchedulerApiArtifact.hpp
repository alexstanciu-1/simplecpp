#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/SchedulerSessionRow.hpp"
#include "__types/SchedulerTaskRow.hpp"
#include "__types/SchedulerWorkerRow.hpp"
namespace scpp {
class SchedulerApiArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> scheduler_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> session_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> worker_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> task_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> design_ready_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> execution_blocked_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> execution_completed_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> stable_output_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	int_t<std::uint64_t> stable_api_hash = cast<int_t<std::uint64_t>>(static_cast<int_t<> >(0));
	vector_t<SchedulerSessionRow> sessions = vector_t<SchedulerSessionRow>{};
	vector_t<SchedulerWorkerRow> workers = vector_t<SchedulerWorkerRow>{};
	vector_t<SchedulerTaskRow> tasks = vector_t<SchedulerTaskRow>{};
};
}
