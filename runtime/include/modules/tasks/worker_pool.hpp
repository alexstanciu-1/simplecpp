#pragma once
#include "scpp/int_t.hpp"

#ifndef SCPP_HAS_TASKS
#define SCPP_HAS_TASKS 0
#endif

namespace scpp::tasks {
	// Nonpositive counts disable idle-worker retention; running batches are not interrupted.
	void configure_default_worker_pool(const int_t<> &workers);
	// As before, these operations require the compiled tasks module when called.
	void shutdown_default_worker_pool();
	[[nodiscard]] int_t<> default_worker_pool_size();
}

#if !SCPP_HAS_TASKS
#include "detail/worker_pool_disabled.hpp"
#endif
