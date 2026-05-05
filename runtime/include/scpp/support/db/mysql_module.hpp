#pragma once

#include "scpp/dynamic_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"

#include <cstdint>
#include <functional>
#include <memory>
#include <optional>
#include <string>
#include <utility>
#include <vector>

namespace scpp::db::mysql_module {

struct status final {
	std::int64_t errno_code = 0;
	std::string error_message;
};

struct query_outcome final {
	bool has_result = false;
	std::int64_t errno_code = 0;
	std::string error_message;
	std::int64_t affected_rows = 0;
	std::int64_t insert_id = 0;
};

struct bound_value final {
	enum class kind_t {
		null_v,
		int_v,
		float_v,
		string_v,
	};

	kind_t kind = kind_t::null_v;
	std::int64_t int_value = 0;
	double float_value = 0.0;
	std::string string_value;

	[[nodiscard]] static bound_value make_null();
	[[nodiscard]] static bound_value make_int(std::int64_t value);
	[[nodiscard]] static bound_value make_float(double value);
	[[nodiscard]] static bound_value make_string(std::string value);
};

class result_handle {
public:
	virtual ~result_handle() = default;

	[[nodiscard]] virtual std::int64_t num_rows() const = 0;
	[[nodiscard]] virtual dynamic_t<> fetch_row() = 0;
	[[nodiscard]] virtual dynamic_t<> fetch_assoc() = 0;
	[[nodiscard]] virtual bool has_error() const = 0;
	[[nodiscard]] virtual status last_status() const = 0;
};

class statement_handle {
public:
	virtual ~statement_handle() = default;

	virtual void close() = 0;
	virtual void execute(const std::string &types, const std::vector<bound_value> &values) = 0;
	[[nodiscard]] virtual std::shared_ptr<result_handle> get_result() = 0;
	[[nodiscard]] virtual status last_status() const = 0;
	[[nodiscard]] virtual std::int64_t affected_rows() const = 0;
	[[nodiscard]] virtual std::int64_t insert_id() const = 0;
};

class connection_handle {
public:
	virtual ~connection_handle() = default;

	virtual void close() = 0;
	virtual query_outcome query(const std::string &sql) = 0;
	[[nodiscard]] virtual std::shared_ptr<result_handle> take_last_result() = 0;
	[[nodiscard]] virtual std::shared_ptr<statement_handle> prepare(const std::string &sql) = 0;
	virtual bool set_charset(const std::string &charset) = 0;
	virtual bool begin_transaction() = 0;
	virtual bool commit() = 0;
	virtual bool rollback() = 0;
	[[nodiscard]] virtual status connect_status() const = 0;
	[[nodiscard]] virtual status last_status() const = 0;
	[[nodiscard]] virtual std::int64_t affected_rows() const = 0;
	[[nodiscard]] virtual std::int64_t insert_id() const = 0;
};

[[nodiscard]] std::shared_ptr<connection_handle> connect(
	const std::string &host,
	const std::string &username,
	const std::string &password,
	const std::string &database,
	std::int64_t port,
	const std::string &socket,
	status &connect_status);

} // namespace scpp::db::mysql_module
