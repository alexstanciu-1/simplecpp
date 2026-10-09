#include <cstdio>

// Deterministic OS-boundary failure, without exhausting host descriptors or
// depending on an invalid command (which still successfully opens a shell pipe).
static FILE* unavailable_pipe(const char*, const char*) { return nullptr; }
#define popen unavailable_pipe
#define _popen unavailable_pipe
#include "lang/php/php_process.hpp"
#undef popen
#undef _popen

int main() {
	return scpp::php::shell_exec(scpp::string_t("unused"))
		.is_false().native_value() ? 0 : 1;
}
