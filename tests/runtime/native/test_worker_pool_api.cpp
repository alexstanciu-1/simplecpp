#include "public_api_guard.hpp"
#include "modules/tasks/worker_pool.hpp"

// Implemented in a separate TU that exercises the existing generic task API.
int exercise_worker_pool();

int main()
{
	scpp::tasks::worker_pool::shutdown();
	if (scpp::tasks::worker_pool::size() != 0) { return 1; }
	scpp::tasks::worker_pool::configure(scpp::int64(-1));
	if (scpp::tasks::worker_pool::size() != 0) { return 2; }
	scpp::tasks::worker_pool::configure(scpp::int64(4));
	if (scpp::tasks::worker_pool::size() != 4) { return 3; }
	const auto result = exercise_worker_pool();
	scpp::tasks::worker_pool::configure(scpp::int64(0));
	if (scpp::tasks::worker_pool::size() != 0) { return 4; }
	scpp::tasks::worker_pool::shutdown();
	scpp::tasks::worker_pool::shutdown();
	if (scpp::tasks::worker_pool::size() != 0) { return 5; }
	return result;
}
