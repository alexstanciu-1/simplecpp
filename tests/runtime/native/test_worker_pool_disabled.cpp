#include "modules/tasks/worker_pool.hpp"
#ifdef TEST_TASKS_UMBRELLA
#include "modules/tasks/tasks.hpp"
#endif
#include <string>

static int check(bool legacy)
{
	try {
#ifdef TEST_TASKS_UMBRELLA
		if (legacy) { scpp::tasks::configure_default_worker_pool(scpp::int_t<>(4)); }
		else
#endif
		{ scpp::tasks::worker_pool::configure(scpp::int64(4)); }
	}
	catch (const scpp::runtime_error &error) {
		return error.code() == "tasks_module_disabled" &&
			error.component() == "scpp::tasks" &&
			error.operator_symbol() == "task_set_worker_pool_size" &&
			std::string(error.what()) == "task_set_worker_pool_size(): tasks runtime module is not enabled in this build"
			? 0 : 1;
	}
	return 2;
}

int main()
{
	const int result = check(false);
#ifdef TEST_TASKS_UMBRELLA
	return result != 0 ? result : check(true);
#else
	return result;
#endif
}
