#pragma once

namespace scpp {
	// NUL-terminated inputs are borrowed synchronously; the thrown error owns copies.
	// Null inputs denote empty fields. This primitive does not define module policy.
	[[noreturn]] void raise_runtime_error(const char *message, const char *code,
		const char *component, const char *operation);
}
