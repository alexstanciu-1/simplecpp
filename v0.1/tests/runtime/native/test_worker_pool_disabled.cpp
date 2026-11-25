#include "public_api_guard.hpp"
#include "modules/tasks/worker_pool.hpp"

int check_worker_pool_error();

int main()
{
	try { scpp::tasks::worker_pool::configure(scpp::int64(4)); }
	catch (...) { return check_worker_pool_error(); }
	return 3;
}
