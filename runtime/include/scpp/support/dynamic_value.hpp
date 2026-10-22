#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/iteration_preservation.hpp"
#include <memory>
#include <variant>

namespace scpp::hash_detail {
template <typename Value> class packed_table;
}

namespace scpp::detail {

// Storage implementation of public dynamic_t<>. No mixed-container bridge.
// Operation semantics stay in their existing operator/cast owners.
class dynamic_value final {
public:
	using table_type = hash_detail::packed_table<dynamic_value>;
	enum class kind_t { null_v, bool_v, int_v, float_v, string_v, table_v };

private:
	using payload_type = std::variant<std::monostate, bool_t, int_t<>, float_t,
		std::unique_ptr<string_t>, std::shared_ptr<table_type>>;
	payload_type payload_;
	mutable std::shared_ptr<iteration_detail::observers> iteration_;
	explicit dynamic_value(std::shared_ptr<table_type> table) noexcept;
	static payload_type copy_payload(const payload_type &source);

public:
	dynamic_value() noexcept = default;
	dynamic_value(null_t) noexcept;
	explicit dynamic_value(bool_t value) noexcept;
	explicit dynamic_value(int_t<> value) noexcept;
	explicit dynamic_value(float_t value) noexcept;
	explicit dynamic_value(const string_t &value);
	dynamic_value(const mixed_t &) = delete;
	dynamic_value(const dynamic_value &other);
	dynamic_value(dynamic_value &&other);
	dynamic_value &operator=(const dynamic_value &other);
	dynamic_value &operator=(dynamic_value &&other);
	~dynamic_value();
	[[nodiscard]] std::shared_ptr<iteration_detail::observers> iteration_binding() const;

	[[nodiscard]] static dynamic_value make_container();
	[[nodiscard]] kind_t kind() const noexcept;
	[[nodiscard]] bool_t is_null() const noexcept;
	[[nodiscard]] bool_t get_bool() const;
	[[nodiscard]] int_t<> get_int() const;
	[[nodiscard]] float_t get_float() const;
	[[nodiscard]] const string_t &get_string() const;
	// Internal checked access only; not a source-language interior-reference API.
	[[nodiscard]] table_type &get_table();
	[[nodiscard]] const table_type &get_table() const;
	[[nodiscard]] const table_type *table_if() const noexcept;
};

} // namespace scpp::detail
