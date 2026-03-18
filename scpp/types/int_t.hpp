#pragma once

#include <cctype>
#include <cstddef>
#include <limits>
#include <ostream>
#include <stdexcept>
#include <string_view>

namespace scpp {

	/**
	 * Simple C++ runtime integer type.
	 *
	 * Design goals:
	 * - represent the Simple C++ `int` type
	 * - avoid leaking broad native C++ conversion behavior
	 * - keep arithmetic and comparison rules explicit
	 * - support explicit string-based construction for runtime casts
	 *
	 * Notes:
	 * - storage is `long long`, matching the current spec
	 * - there is intentionally no implicit conversion operator back to `long long`
	 * - generated code should enter this type explicitly, e.g. `(int_t)12`
	 */
	class int_t {
	private:
		long long m_value;

		/**
		 * Parse a base-10 signed integer from text.
		 *
		 * Accepted forms:
		 * - "0"
		 * - "12"
		 * - "-12"
		 * - "+12"
		 *
		 * Rejected forms:
		 * - empty strings
		 * - whitespace-padded strings
		 * - decimal values
		 * - non-digit content
		 */
		static long long parse_decimal(std::string_view text) {
			if (text.empty()) {
				throw std::runtime_error("Simple C++ Type Error: Invalid explicit int cast");
			}

			std::size_t pos = 0;
			bool negative = false;

			if (text[pos] == '+' || text[pos] == '-') {
				negative = (text[pos] == '-');
				++pos;

				if (pos >= text.size()) {
					throw std::runtime_error("Simple C++ Type Error: Invalid explicit int cast");
				}
			}

			long long value = 0;

			for (; pos < text.size(); ++pos) {
				const unsigned char ch = static_cast<unsigned char>(text[pos]);

				if (!std::isdigit(ch)) {
					throw std::runtime_error("Simple C++ Type Error: Invalid explicit int cast");
				}

				const int digit = static_cast<int>(ch - '0');

				/* Overflow-safe accumulation. */
				if (!negative) {
					if (value > (std::numeric_limits<long long>::max() - digit) / 10) {
						throw std::runtime_error("Simple C++ Type Error: Invalid explicit int cast");
					}
					value = (value * 10) + digit;
				} else {
					if (value < (std::numeric_limits<long long>::min() + digit) / 10) {
						throw std::runtime_error("Simple C++ Type Error: Invalid explicit int cast");
					}
					value = (value * 10) - digit;
				}
			}

			return value;
		}

	public:
		/**
		 * Default / direct integer construction.
		 *
		 * Examples:
		 * - int_t a;
		 * - int_t b(12);
		 * - auto x = (int_t)12;
		 */
		constexpr int_t(long long value = 0) noexcept
			: m_value(value) {
		}

		/**
		 * Explicit string-based construction.
		 *
		 * This is intended for explicit runtime casts only.
		 */
		explicit int_t(const char* text)
			: m_value(parse_decimal(std::string_view(text != nullptr ? text : ""))) {
		}

		/**
		 * Explicit string-view-based construction.
		 *
		 * Useful for internal runtime helpers.
		 */
		explicit int_t(std::string_view text)
			: m_value(parse_decimal(text)) {
		}

		/**
		 * Invalid direct assignment from C strings is forbidden.
		 *
		 * This prevents accidental native-looking assignments such as:
		 *     int_t x;
		 *     x = "12";
		 */
		int_t& operator=(const char* text) = delete;

		/**
		 * Controlled raw accessor.
		 *
		 * Runtime helpers may use this to implement higher-level operators.
		 * Generated code should not rely on this as a general escape hatch.
		 */
		[[nodiscard]] constexpr long long raw_value() const noexcept {
			return m_value;
		}

		/* Unary operators. */

		[[nodiscard]] constexpr int_t operator+() const noexcept {
			return *this;
		}

		[[nodiscard]] constexpr int_t operator-() const noexcept {
			return int_t(-m_value);
		}

		/* Arithmetic operators. */

		friend constexpr int_t operator+(const int_t& lhs, const int_t& rhs) noexcept {
			return int_t(lhs.m_value + rhs.m_value);
		}

		friend constexpr int_t operator-(const int_t& lhs, const int_t& rhs) noexcept {
			return int_t(lhs.m_value - rhs.m_value);
		}

		friend constexpr int_t operator*(const int_t& lhs, const int_t& rhs) noexcept {
			return int_t(lhs.m_value * rhs.m_value);
		}

		friend int_t operator/(const int_t& lhs, const int_t& rhs) {
			if (rhs.m_value == 0) {
				throw std::runtime_error("Simple C++ Runtime Error: Division by zero");
			}

			return int_t(lhs.m_value / rhs.m_value);
		}

		/* Compound assignment operators. */

		int_t& operator+=(const int_t& rhs) noexcept {
			m_value += rhs.m_value;
			return *this;
		}

		int_t& operator-=(const int_t& rhs) noexcept {
			m_value -= rhs.m_value;
			return *this;
		}

		int_t& operator*=(const int_t& rhs) noexcept {
			m_value *= rhs.m_value;
			return *this;
		}

		int_t& operator/=(const int_t& rhs) {
			if (rhs.m_value == 0) {
				throw std::runtime_error("Simple C++ Runtime Error: Division by zero");
			}

			m_value /= rhs.m_value;
			return *this;
		}

		/* Comparison operators. */

		friend constexpr bool operator==(const int_t& lhs, const int_t& rhs) noexcept {
			return lhs.m_value == rhs.m_value;
		}

		friend constexpr bool operator!=(const int_t& lhs, const int_t& rhs) noexcept {
			return lhs.m_value != rhs.m_value;
		}

		friend constexpr bool operator<(const int_t& lhs, const int_t& rhs) noexcept {
			return lhs.m_value < rhs.m_value;
		}

		friend constexpr bool operator<=(const int_t& lhs, const int_t& rhs) noexcept {
			return lhs.m_value <= rhs.m_value;
		}

		friend constexpr bool operator>(const int_t& lhs, const int_t& rhs) noexcept {
			return lhs.m_value > rhs.m_value;
		}

		friend constexpr bool operator>=(const int_t& lhs, const int_t& rhs) noexcept {
			return lhs.m_value >= rhs.m_value;
		}

		/* Output support. */

		friend std::ostream& operator<<(std::ostream& os, const int_t& value) {
			os << value.m_value;
			return os;
		}
	};

} /* namespace scpp */
