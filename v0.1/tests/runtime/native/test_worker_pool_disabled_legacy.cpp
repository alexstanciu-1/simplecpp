#include "modules/tasks/tasks.hpp"

int check_worker_pool_error();

int main()
{
	try { scpp::tasks::configure_default_worker_pool(scpp::int_t<>(4)); }
	catch (...) { return check_worker_pool_error(); }
	return 3;
}
