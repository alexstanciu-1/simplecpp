#include "modules/tasks/worker_pool.hpp"
#ifdef TEST_TASKS_UMBRELLA
#include "modules/tasks/tasks.hpp"
#endif
#include <string>

int main()
{
	try {
		scpp::tasks::configure_default_worker_pool(scpp::int_t<>(4));
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
