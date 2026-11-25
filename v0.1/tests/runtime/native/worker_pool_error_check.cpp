#include "scpp/runtime_error.hpp"
#include <string>

// Called while an exception is active; inspection stays outside the public caller TU.
int check_runtime_error_fields(const char *message, const char *code, const char *component, const char *operation)
{
	try { throw; }
	catch (const scpp::runtime_error &error) {
		return error.code() == code &&
			error.component() == component &&
			error.operator_symbol() == operation &&
			std::string(error.what()) == message
			? 0 : 1;
	}
	catch (...) { return 2; }
}

int check_worker_pool_error()
{
	return check_runtime_error_fields(
		"task_set_worker_pool_size(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled", "scpp::tasks", "task_set_worker_pool_size");
}
