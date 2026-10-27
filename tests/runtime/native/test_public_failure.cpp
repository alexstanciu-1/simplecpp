#include "public_api_guard.hpp"
#include "scpp/failure.hpp"

int check_runtime_error_fields(const char *, const char *, const char *, const char *);

int main()
{
	char message[] = "owned message";
	try { scpp::raise_runtime_error(message, "code", "component", "operation"); }
	catch (...) {
		message[0] = 'X';
		if (check_runtime_error_fields("owned message", "code", "component", "operation") != 0) { return 1; }
	}
	try { scpp::raise_runtime_error(nullptr, nullptr, nullptr, nullptr); }
	catch (...) { return check_runtime_error_fields("", "", "", ""); }
	return 2;
}
