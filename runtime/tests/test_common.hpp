#pragma once

#include <cassert>
#include <stdexcept>
#include <utility>

#include "scpp/runtime.hpp"

namespace scpp_test {

// Simple record used by pointer and container smoke tests.
// It stays intentionally small so failures isolate runtime behavior rather than test complexity.
struct sample_object final {
	scpp::int_t value;

	// Stores the provided semantic integer payload.
	explicit sample_object(scpp::int_t initial_value)
		: value(std::move(initial_value)) {
	}
};

// Verifies that a callable throws exactly the expected exception family.
template <typename TException, typename TCallable>
void expect_throw(TCallable &&callable) {
	bool did_throw = false;

	try {
		std::forward<TCallable>(callable)();
	} catch (const TException &) {
		did_throw = true;
	}

	assert(did_throw);
}

} // namespace scpp_test
