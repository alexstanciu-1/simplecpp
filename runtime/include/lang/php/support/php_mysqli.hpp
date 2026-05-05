#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/detail.hpp"
#include "scpp/float_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/string_t.hpp"
#include "scpp/support/db/mysql_module.hpp"

#include <functional>
#include <memory>
#include <type_traits>
#include <utility>
#include <vector>


namespace scpp {

class mysqli_result;
class mysqli_stmt;

class mysqli {
public:
	int_t connect_errno = int_t(0);
	string_t connect_error = string_t("");
	int_t errno_code = int_t(0);
	string_t error = string_t("");
	int_t insert_id = int_t(0);
	int_t affected_rows = int_t(0);

	mysqli(
		string_t host = string_t("127.0.0.1"),
		string_t username = string_t(""),
		string_t password = string_t(""),
		string_t database = string_t(""),
		int_t port = int_t(3306),
		string_t socket = string_t(""));

	result_or_bool<shared_p<mysqli_result>> query(const string_t &sql);
	result_or_false<shared_p<mysqli_stmt>> prepare(const string_t &sql);
	void close();
	bool_t set_charset(const string_t &charset);
	bool_t begin_transaction();
	bool_t commit();
	bool_t rollback();

private:
	std::shared_ptr<db::mysql_module::connection_handle> handle_;

	void apply_connect_status(const db::mysql_module::status &status_value);
	void apply_runtime_status(const db::mysql_module::status &status_value);

	friend class mysqli_stmt;
};

class mysqli_result {
public:
	explicit mysqli_result(std::shared_ptr<db::mysql_module::result_handle> handle);

	int_t num_rows = int_t(0);

	dynamic_t<> fetch_assoc();
	dynamic_t<> fetch_row();

private:
	std::shared_ptr<db::mysql_module::result_handle> handle_;
};

class mysqli_stmt {
public:
	explicit mysqli_stmt(
		std::shared_ptr<db::mysql_module::statement_handle> handle,
		mysqli *owner);

	int_t errno_code = int_t(0);
	string_t error = string_t("");
	int_t insert_id = int_t(0);
	int_t affected_rows = int_t(0);

	template <typename... TArgs>
	bool_t bind_param(const string_t &types, TArgs &...args) {
		clear_local_error();
		const auto native_types = types.native_value();
		if (native_types.size() != sizeof...(args)) {
			set_local_error(1, "mysqli_stmt::bind_param type count does not match bound argument count");
			return bool_t(false);
		}

		bound_types_ = native_types;
		bound_getters_.clear();
		bound_getters_.reserve(sizeof...(args));
		bind_param_impl(native_types, 0U, args...);
		return bool_t(true);
	}

	bool_t execute();
	result_or_false<shared_p<mysqli_result>> get_result();
	void close();

private:
	using bound_getter_t = std::function<db::mysql_module::bound_value()>;

	std::shared_ptr<db::mysql_module::statement_handle> handle_;
	mysqli *owner_ = nullptr;
	std::string bound_types_;
	std::vector<bound_getter_t> bound_getters_;

	void clear_local_error();
	void set_local_error(std::int64_t errno_code, const char *message);
	void apply_status(const db::mysql_module::status &status_value);

	template <typename TValue>
	void push_bound_getter(char type_code, TValue &value) {
		bound_getters_.push_back([type_code, &value]() -> db::mysql_module::bound_value {
			return make_bound_value(type_code, value);
		});
	}

	void bind_param_impl(const std::string &, std::size_t) {}

	template <typename TValue, typename... TRest>
	void bind_param_impl(const std::string &types, std::size_t index, TValue &value, TRest &...rest) {
		push_bound_getter(types[index], value);
		bind_param_impl(types, index + 1U, rest...);
	}

	template <typename TValue>
	static db::mysql_module::bound_value make_bound_value(char type_code, TValue &value) {
		using bare_t = std::remove_cvref_t<TValue>;

		if constexpr (std::is_same_v<bare_t, int_t>) {
			return db::mysql_module::bound_value::make_int(value.native_value());
		} else if constexpr (std::is_same_v<bare_t, float_t>) {
			return db::mysql_module::bound_value::make_float(value.native_value());
		} else if constexpr (std::is_same_v<bare_t, string_t>) {
			return db::mysql_module::bound_value::make_string(value.native_value());
		} else if constexpr (std::is_same_v<bare_t, mixed_t>) {
			if (value.is_null()) {
				return db::mysql_module::bound_value::make_null();
			}
			switch (type_code) {
				case 'i': return db::mysql_module::bound_value::make_int(static_cast<int_t>(value).native_value());
				case 'd': return db::mysql_module::bound_value::make_float(static_cast<float_t>(value).native_value());
				case 's':
				case 'b': return db::mysql_module::bound_value::make_string(static_cast<string_t>(value).native_value());
				default: return db::mysql_module::bound_value::make_null();
			}
		} else {
			static_assert(!sizeof(TValue *), "Unsupported mysqli_stmt::bind_param argument type");
		}
	}
};

} // namespace scpp
