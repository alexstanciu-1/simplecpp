#include "scpp/failure.hpp"
#include "scpp/runtime_error.hpp"

namespace scpp {
	[[noreturn]] void raise_runtime_error(const char *message, const char *code,
		const char *component, const char *operation)
	{
		throw runtime_error(message ? message : "", code ? code : "",
			component ? component : "", operation ? operation : "");
	}
}
