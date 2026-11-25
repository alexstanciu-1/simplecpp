#pragma once

#include <cassert>
#include <cstdint>
#include <stdexcept>
#include <type_traits>
#include <utility>

#include "scpp/lang/php.hpp"

namespace runtime_test {

struct sample_object final {
	scpp::int_t<> value;

	explicit sample_object(scpp::int_t<> initial_value)
		: value(std::move(initial_value)) {
	}
};

struct base_reader {
	virtual ~base_reader() = default;
	virtual scpp::int_t<> read() const = 0;
};

struct counter_reader final : base_reader {
	scpp::int_t<> value;

	explicit counter_reader(scpp::int_t<> initial_value)
		: value(std::move(initial_value)) {
	}

	scpp::int_t<> read() const override {
		return value;
	}
};

struct lifetime_probe final {
	static inline std::int32_t constructions = 0;
	static inline std::int32_t copies = 0;
	static inline std::int32_t moves = 0;
	static inline std::int32_t destructions = 0;
	static inline std::int32_t alive = 0;

	scpp::int_t<> value;

	explicit lifetime_probe(scpp::int_t<> initial_value = scpp::int_t<>(0))
		: value(std::move(initial_value)) {
		++constructions;
		++alive;
	}

	lifetime_probe(const lifetime_probe &other)
		: value(other.value) {
		++constructions;
		++copies;
		++alive;
	}

	lifetime_probe(lifetime_probe &&other) noexcept
		: value(std::move(other.value)) {
		++constructions;
		++moves;
		++alive;
	}

	lifetime_probe &operator=(const lifetime_probe &) = default;
	lifetime_probe &operator=(lifetime_probe &&) noexcept = default;

	~lifetime_probe() {
		++destructions;
		--alive;
	}

	static void reset_counts() {
		constructions = 0;
		copies = 0;
		moves = 0;
		destructions = 0;
		alive = 0;
	}
};

inline void assert_lifetime_balanced() {
	assert(lifetime_probe::alive == 0);
	assert(lifetime_probe::destructions == lifetime_probe::constructions);
}

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

} // namespace runtime_test
