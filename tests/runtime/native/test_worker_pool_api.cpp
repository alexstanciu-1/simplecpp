#include "modules/tasks/worker_pool.hpp"

// Implemented in a separate TU that exercises the existing generic task API.
int exercise_worker_pool();

int main()
{
	scpp::tasks::shutdown_default_worker_pool();
	if (scpp::tasks::default_worker_pool_size().native_value() != 0) { return 1; }
	scpp::tasks::configure_default_worker_pool(scpp::int_t<>(-1));
	if (scpp::tasks::default_worker_pool_size().native_value() != 0) { return 2; }
	scpp::tasks::configure_default_worker_pool(scpp::int_t<>(4));
	if (scpp::tasks::default_worker_pool_size().native_value() != 4) { return 3; }
	const auto result = exercise_worker_pool();
	scpp::tasks::configure_default_worker_pool(scpp::int_t<>(0));
	if (scpp::tasks::default_worker_pool_size().native_value() != 0) { return 4; }
	scpp::tasks::shutdown_default_worker_pool();
	scpp::tasks::shutdown_default_worker_pool();
	if (scpp::tasks::default_worker_pool_size().native_value() != 0) { return 5; }
	return result;
}
