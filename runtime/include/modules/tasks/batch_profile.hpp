#pragma once
#include <atomic>
#include <chrono>
#include <cstddef>
#include <cstdint>

namespace scpp::tasks::detail {

// Opt-in diagnostic experiment. Coordinator-owned, never shared between batches.
struct batch_profile {
	std::uint64_t startup_ns = 0;
	std::uint64_t work_ns = 0;
	std::uint64_t worker_cpu_ns = 0;
	std::uint64_t completion_tail_ns = 0;
	std::uint64_t join_tail_ns = 0;
	std::uint64_t queue_claim_ns = 0;
	std::uint64_t completion_lock_wait_ns = 0;
	std::uint64_t completion_lock_hold_ns = 0;
	std::uint64_t gather_ns = 0;
	std::size_t workers = 0;
};

struct worker_profile {
	std::uint64_t work_end = 0;
	std::uint64_t completion_end = 0;
	std::uint64_t cpu_ns = 0;
	std::uint64_t queue_claim_ns = 0;
	std::uint64_t lock_wait_ns = 0;
	std::uint64_t lock_hold_ns = 0;
};

inline thread_local batch_profile *active_batch_profile = nullptr;
inline thread_local worker_profile *active_worker_profile = nullptr;

inline std::uint64_t profile_now_ns()
{
	return std::chrono::duration_cast<std::chrono::nanoseconds>(
		std::chrono::steady_clock::now().time_since_epoch()).count();
}

// The Linux profiler uses the calling thread's user+system CPU clock.
std::uint64_t profile_thread_cpu_ns();

class batch_profile_scope final {
	batch_profile *previous_;
public:
	explicit batch_profile_scope(batch_profile *profile) : previous_(active_batch_profile)
	{
		active_batch_profile = profile;
	}
	~batch_profile_scope()
	{
		active_batch_profile = this->previous_;
	}
	batch_profile_scope(const batch_profile_scope &) = delete;
	batch_profile_scope &operator=(const batch_profile_scope &) = delete;
};

inline std::size_t claim_profiled_index(std::atomic<std::size_t> &index)
{
	if (active_worker_profile == nullptr) {
		return index.fetch_add(1, std::memory_order_relaxed);
	}
	const auto start = profile_now_ns();
	const auto result = index.fetch_add(1, std::memory_order_relaxed);
	active_worker_profile->queue_claim_ns += profile_now_ns() - start;
	return result;
}
}
