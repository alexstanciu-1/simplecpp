#pragma once
#include "scpp/integers.hpp"

#ifndef SCPP_HAS_TASKS
#define SCPP_HAS_TASKS 0
#endif

namespace scpp::tasks::worker_pool {
	// Nonpositive counts disable idle-worker retention; running batches are not interrupted.
	void configure(const int64 &workers);
	// As before, these operations require the compiled tasks module when called.
	void shutdown();
	[[nodiscard]] int64 size();
}

#if !SCPP_HAS_TASKS
#include "scpp/failure.hpp"

namespace scpp::tasks::worker_pool {
	inline void configure(const int64 &)
	{
		scpp::raise_runtime_error(
			"task_set_worker_pool_size(): tasks runtime module is not enabled in this build",
			"tasks_module_disabled", "scpp::tasks", "task_set_worker_pool_size");
	}
}
#endif
