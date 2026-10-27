#pragma once
#include "scpp/runtime_error.hpp"

// Header-only disabled behavior deliberately does not require linking the tasks module.
namespace scpp::tasks {
inline void configure_default_worker_pool(const int_t<> &)
{
	throw runtime_error(
		"task_set_worker_pool_size(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_set_worker_pool_size"
	);
}

}
