#pragma once
#include <stddef.h>

// Source payload inheritance must not expose native ownership templates.
namespace scpp { class string_t; }
namespace scpp::php {
class exception_payload {
public:
	virtual ~exception_payload() = default;
	[[nodiscard]] virtual const char *exception_message() const noexcept { return "Exception"; }
	// Length-aware transport preserves embedded zero bytes in source messages.
	virtual void copy_message(void *target, void (*write)(void *, const char *, size_t)) const {
		write(target, "Exception", 9);
	}
	// Defined at the compiled runtime boundary, where string_t is complete.
	[[nodiscard]] string_t message_string() const;
};
} // namespace scpp::php
