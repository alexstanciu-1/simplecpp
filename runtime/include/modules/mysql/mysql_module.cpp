#include "scpp/support/db/mysql_module.hpp"

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/string_t.hpp"
#include "lang/php/support/php.hpp"

#include <algorithm>
#include <cctype>
#include <cstring>
#include <limits>
#include <stdexcept>
#include <string>
#include <utility>
#include <vector>

#if defined(SCPP_HAS_MYSQLI) && SCPP_HAS_MYSQLI
#include <mysql.h>
#endif

namespace scpp::db::mysql_module {

bound_value bound_value::make_null() {
	return bound_value{};
}

bound_value bound_value::make_int(std::int64_t value) {
	bound_value out;
	out.kind = kind_t::int_v;
	out.int_value = value;
	return out;
}

bound_value bound_value::make_float(double value) {
	bound_value out;
	out.kind = kind_t::float_v;
	out.float_value = value;
	return out;
}

bound_value bound_value::make_string(std::string value) {
	bound_value out;
	out.kind = kind_t::string_v;
	out.string_value = std::move(value);
	return out;
}

namespace {

[[nodiscard]] inline dynamic_t make_empty_dynamic() {
	return ::scpp::php::to_dynamic(hash_t<mixed_t>{});
}

[[nodiscard]] inline mixed_t cell_to_mixed_from_text(
	const char *value,
	unsigned long length,
	int field_type) {
	if (value == nullptr) {
		return mixed_t(null_t{});
	}

	const std::string text(value, length);
	switch (field_type) {
		case MYSQL_TYPE_TINY:
		case MYSQL_TYPE_SHORT:
		case MYSQL_TYPE_LONG:
		case MYSQL_TYPE_INT24:
		case MYSQL_TYPE_LONGLONG:
		case MYSQL_TYPE_YEAR: {
			try {
				return mixed_t(int_t(std::stoll(text)));
			} catch (...) {
				return mixed_t(string_t(text));
			}
		}
		case MYSQL_TYPE_DECIMAL:
		case MYSQL_TYPE_NEWDECIMAL:
		case MYSQL_TYPE_FLOAT:
		case MYSQL_TYPE_DOUBLE: {
			try {
				return mixed_t(float_t(std::stod(text)));
			} catch (...) {
				return mixed_t(string_t(text));
			}
		}
		case MYSQL_TYPE_BIT: {
			if (length == 1U) {
				return mixed_t(int_t(static_cast<std::uint8_t>(value[0])));
			}
			return mixed_t(string_t(text));
		}
		default:
			return mixed_t(string_t(text));
	}
}

[[nodiscard]] inline std::string normalize_optional_string(const std::string &value) {
	return value.empty() ? std::string{} : value;
}

#if defined(SCPP_HAS_MYSQLI) && SCPP_HAS_MYSQLI

class mysql_result_set final : public result_handle {
public:
	explicit mysql_result_set(MYSQL_RES *result)
		: result_(result),
		field_count_(mysql_num_fields(result_)),
		fields_(mysql_fetch_fields(result_)),
		num_rows_(static_cast<std::int64_t>(mysql_num_rows(result_))) {}

	~mysql_result_set() override {
		if (result_ != nullptr) {
			mysql_free_result(result_);
			result_ = nullptr;
		}
	}

	[[nodiscard]] std::int64_t num_rows() const override {
		return num_rows_;
	}

	[[nodiscard]] dynamic_t fetch_row() override {
		return fetch_one(false);
	}

	[[nodiscard]] dynamic_t fetch_assoc() override {
		return fetch_one(true);
	}

	[[nodiscard]] bool has_error() const override {
		return last_status_.errno_code != 0;
	}

	[[nodiscard]] status last_status() const override {
		return last_status_;
	}

private:
	MYSQL_RES *result_ = nullptr;
	unsigned int field_count_ = 0;
	MYSQL_FIELD *fields_ = nullptr;
	std::int64_t num_rows_ = 0;
	status last_status_{};

	[[nodiscard]] dynamic_t fetch_one(bool associative) {
		if (result_ == nullptr) {
			return make_empty_dynamic();
		}

		MYSQL_ROW row = mysql_fetch_row(result_);
		if (row == nullptr) {
			last_status_ = {};
			return make_empty_dynamic();
		}

		unsigned long *lengths = mysql_fetch_lengths(result_);
		hash_t<mixed_t> out;
		for (unsigned int index = 0; index < field_count_; ++index) {
			auto cell = cell_to_mixed_from_text(row[index], lengths[index], fields_[index].type);
			if (associative) {
				out.set(string_t(fields_[index].name), std::move(cell));
			} else {
				static_cast<void>(out.append(std::move(cell)));
			}
		}
		last_status_ = {};
		return ::scpp::php::to_dynamic(out);
	}
};

struct stmt_bind_storage final {
	std::vector<MYSQL_BIND> binds;
	std::vector<std::string> strings;
	std::vector<long long> ints;
	std::vector<double> doubles;
	std::vector<unsigned long> string_lengths;
	std::vector<my_bool> is_null;
};

class mysql_stmt_result_set final : public result_handle {
public:
	explicit mysql_stmt_result_set(MYSQL_STMT *stmt) {
		MYSQL_RES *metadata = mysql_stmt_result_metadata(stmt);
		if (metadata == nullptr) {
			last_status_.errno_code = mysql_stmt_errno(stmt);
			last_status_.error_message = mysql_stmt_error(stmt);
			return;
		}

		const unsigned int field_count = mysql_num_fields(metadata);
		MYSQL_FIELD *fields = mysql_fetch_fields(metadata);
		columns_.reserve(field_count);
		binds_.resize(field_count);
		buffers_.resize(field_count);
		buffer_lengths_.resize(field_count);
		actual_lengths_.resize(field_count);
		is_null_.resize(field_count);
		error_flags_.resize(field_count);

		for (unsigned int index = 0; index < field_count; ++index) {
			columns_.push_back(fields[index]);
			auto buffer_size = static_cast<std::size_t>(std::max<unsigned long>(fields[index].max_length + 1U, 1024U));
			buffers_[index].resize(buffer_size);

			MYSQL_BIND bind{};
			bind.buffer_type = MYSQL_TYPE_STRING;
			bind.buffer = buffers_[index].data();
			bind.buffer_length = static_cast<unsigned long>(buffers_[index].size());
			bind.length = &actual_lengths_[index];
			bind.is_null = &is_null_[index];
			bind.error = &error_flags_[index];
			binds_[index] = bind;
		}

		if (mysql_stmt_bind_result(stmt, binds_.data()) != 0) {
			last_status_.errno_code = mysql_stmt_errno(stmt);
			last_status_.error_message = mysql_stmt_error(stmt);
			mysql_free_result(metadata);
			return;
		}
		if (mysql_stmt_store_result(stmt) != 0) {
			last_status_.errno_code = mysql_stmt_errno(stmt);
			last_status_.error_message = mysql_stmt_error(stmt);
			mysql_free_result(metadata);
			return;
		}

		num_rows_ = static_cast<std::int64_t>(mysql_stmt_num_rows(stmt));
		stmt_ = stmt;
		mysql_free_result(metadata);
	}

	[[nodiscard]] std::int64_t num_rows() const override {
		return num_rows_;
	}

	[[nodiscard]] dynamic_t fetch_row() override {
		return fetch_one(false);
	}

	[[nodiscard]] dynamic_t fetch_assoc() override {
		return fetch_one(true);
	}

	[[nodiscard]] bool has_error() const override {
		return last_status_.errno_code != 0;
	}

	[[nodiscard]] status last_status() const override {
		return last_status_;
	}

private:
	MYSQL_STMT *stmt_ = nullptr;
	std::vector<MYSQL_FIELD> columns_;
	std::vector<MYSQL_BIND> binds_;
	std::vector<std::vector<char>> buffers_;
	std::vector<unsigned long> buffer_lengths_;
	std::vector<unsigned long> actual_lengths_;
	std::vector<my_bool> is_null_;
	std::vector<my_bool> error_flags_;
	std::int64_t num_rows_ = 0;
	status last_status_{};

	[[nodiscard]] dynamic_t fetch_one(bool associative) {
		if (stmt_ == nullptr) {
			return make_empty_dynamic();
		}

		const int fetch_code = mysql_stmt_fetch(stmt_);
		if (fetch_code == MYSQL_NO_DATA) {
			last_status_ = {};
			return make_empty_dynamic();
		}
		if (fetch_code == 1) {
			last_status_.errno_code = mysql_stmt_errno(stmt_);
			last_status_.error_message = mysql_stmt_error(stmt_);
			return make_empty_dynamic();
		}

		hash_t<mixed_t> out;
		for (std::size_t index = 0; index < columns_.size(); ++index) {
			mixed_t cell;
			if (is_null_[index]) {
				cell = mixed_t(null_t{});
			} else {
				cell = cell_to_mixed_from_text(
					buffers_[index].data(),
					actual_lengths_[index],
					columns_[index].type);
			}

			if (associative) {
				out.set(string_t(columns_[index].name), std::move(cell));
			} else {
				static_cast<void>(out.append(std::move(cell)));
			}
		}
		last_status_ = {};
		return ::scpp::php::to_dynamic(out);
	}
};

class mysql_statement final : public statement_handle {
public:
	explicit mysql_statement(MYSQL_STMT *stmt)
		: stmt_(stmt) {}

	~mysql_statement() override {
		close();
	}

	void close() override {
		last_result_.reset();
		if (stmt_ != nullptr) {
			mysql_stmt_close(stmt_);
			stmt_ = nullptr;
		}
	}

	void execute(const std::string &types, const std::vector<bound_value> &values) override {
		last_result_.reset();
		last_status_ = {};
		affected_rows_ = 0;
		insert_id_ = 0;

		if (stmt_ == nullptr) {
			last_status_.errno_code = 1;
			last_status_.error_message = "mysqli_stmt::execute on closed statement";
			return;
		}
		if (types.size() != values.size()) {
			last_status_.errno_code = 1;
			last_status_.error_message = "mysqli_stmt::execute bound value count mismatch";
			return;
		}

		stmt_bind_storage storage;
		storage.binds.resize(values.size());
		storage.strings.resize(values.size());
		storage.ints.resize(values.size());
		storage.doubles.resize(values.size());
		storage.string_lengths.resize(values.size());
		storage.is_null.resize(values.size());

		for (std::size_t index = 0; index < values.size(); ++index) {
			storage.is_null[index] = 0;
			MYSQL_BIND bind{};

			if (values[index].kind == bound_value::kind_t::null_v) {
				storage.is_null[index] = 1;
				bind.buffer_type = MYSQL_TYPE_NULL;
				bind.is_null = &storage.is_null[index];
				storage.binds[index] = bind;
				continue;
			}

			switch (types[index]) {
				case 'i':
					storage.ints[index] = values[index].int_value;
					bind.buffer_type = MYSQL_TYPE_LONGLONG;
					bind.buffer = &storage.ints[index];
					bind.is_unsigned = 0;
					break;
				case 'd':
					storage.doubles[index] = values[index].float_value;
					bind.buffer_type = MYSQL_TYPE_DOUBLE;
					bind.buffer = &storage.doubles[index];
					break;
				case 's':
				case 'b':
					storage.strings[index] = values[index].string_value;
					storage.string_lengths[index] = static_cast<unsigned long>(storage.strings[index].size());
					bind.buffer_type = MYSQL_TYPE_STRING;
					bind.buffer = storage.strings[index].empty() ? nullptr : storage.strings[index].data();
					bind.buffer_length = storage.string_lengths[index];
					bind.length = &storage.string_lengths[index];
					break;
				default:
					last_status_.errno_code = 1;
					last_status_.error_message = "mysqli_stmt::execute unsupported bind type";
					return;
			}

			bind.is_null = &storage.is_null[index];
			storage.binds[index] = bind;
		}

		if (!storage.binds.empty() && mysql_stmt_bind_param(stmt_, storage.binds.data()) != 0) {
			last_status_.errno_code = mysql_stmt_errno(stmt_);
			last_status_.error_message = mysql_stmt_error(stmt_);
			return;
		}
		if (mysql_stmt_execute(stmt_) != 0) {
			last_status_.errno_code = mysql_stmt_errno(stmt_);
			last_status_.error_message = mysql_stmt_error(stmt_);
			return;
		}

		affected_rows_ = static_cast<std::int64_t>(mysql_stmt_affected_rows(stmt_));
		insert_id_ = static_cast<std::int64_t>(mysql_stmt_insert_id(stmt_));
		last_status_ = {};
	}

	[[nodiscard]] std::shared_ptr<result_handle> get_result() override {
		if (stmt_ == nullptr) {
			last_status_.errno_code = 1;
			last_status_.error_message = "mysqli_stmt::get_result on closed statement";
			return nullptr;
		}
		last_result_ = std::make_shared<mysql_stmt_result_set>(stmt_);
		if (last_result_ != nullptr && last_result_->has_error()) {
			last_status_ = last_result_->last_status();
			return nullptr;
		}
		last_status_ = {};
		return last_result_;
	}

	[[nodiscard]] status last_status() const override {
		return last_status_;
	}

	[[nodiscard]] std::int64_t affected_rows() const override {
		return affected_rows_;
	}

	[[nodiscard]] std::int64_t insert_id() const override {
		return insert_id_;
	}

private:
	MYSQL_STMT *stmt_ = nullptr;
	status last_status_{};
	std::int64_t affected_rows_ = 0;
	std::int64_t insert_id_ = 0;
	std::shared_ptr<result_handle> last_result_;
};

class mysql_connection final : public connection_handle {
public:
	explicit mysql_connection(MYSQL *connection)
		: connection_(connection) {}

	~mysql_connection() override {
		close();
	}

	void close() override {
		last_result_.reset();
		if (connection_ != nullptr) {
			mysql_close(connection_);
			connection_ = nullptr;
		}
	}

	query_outcome query(const std::string &sql) override {
		query_outcome outcome;
		last_result_.reset();
		last_status_ = {};
		affected_rows_ = 0;
		insert_id_ = 0;

		if (connection_ == nullptr) {
			outcome.errno_code = 1;
			outcome.error_message = "mysqli::query on closed connection";
			last_status_ = {outcome.errno_code, outcome.error_message};
			return outcome;
		}

		if (mysql_query(connection_, sql.c_str()) != 0) {
			outcome.errno_code = mysql_errno(connection_);
			outcome.error_message = mysql_error(connection_);
			last_status_ = {outcome.errno_code, outcome.error_message};
			return outcome;
		}

		MYSQL_RES *result = mysql_store_result(connection_);
		if (result != nullptr) {
			last_result_ = std::make_shared<mysql_result_set>(result);
			outcome.has_result = true;
			outcome.affected_rows = static_cast<std::int64_t>(mysql_affected_rows(connection_));
			outcome.insert_id = static_cast<std::int64_t>(mysql_insert_id(connection_));
			affected_rows_ = outcome.affected_rows;
			insert_id_ = outcome.insert_id;
			return outcome;
		}

		if (mysql_field_count(connection_) != 0U) {
			outcome.errno_code = mysql_errno(connection_);
			outcome.error_message = mysql_error(connection_);
			last_status_ = {outcome.errno_code, outcome.error_message};
			return outcome;
		}

		outcome.has_result = false;
		outcome.affected_rows = static_cast<std::int64_t>(mysql_affected_rows(connection_));
		outcome.insert_id = static_cast<std::int64_t>(mysql_insert_id(connection_));
		affected_rows_ = outcome.affected_rows;
		insert_id_ = outcome.insert_id;
		return outcome;
	}

	[[nodiscard]] std::shared_ptr<result_handle> take_last_result() override {
		return last_result_;
	}

	[[nodiscard]] std::shared_ptr<statement_handle> prepare(const std::string &sql) override {
		last_status_ = {};
		if (connection_ == nullptr) {
			last_status_.errno_code = 1;
			last_status_.error_message = "mysqli::prepare on closed connection";
			return nullptr;
		}

		MYSQL_STMT *stmt = mysql_stmt_init(connection_);
		if (stmt == nullptr) {
			last_status_.errno_code = mysql_errno(connection_);
			last_status_.error_message = mysql_error(connection_);
			return nullptr;
		}
		if (mysql_stmt_prepare(stmt, sql.c_str(), static_cast<unsigned long>(sql.size())) != 0) {
			last_status_.errno_code = mysql_stmt_errno(stmt);
			last_status_.error_message = mysql_stmt_error(stmt);
			mysql_stmt_close(stmt);
			return nullptr;
		}
		return std::make_shared<mysql_statement>(stmt);
	}

	bool set_charset(const std::string &charset) override {
		last_status_ = {};
		if (connection_ == nullptr) {
			last_status_.errno_code = 1;
			last_status_.error_message = "mysqli::set_charset on closed connection";
			return false;
		}
		if (mysql_set_character_set(connection_, charset.c_str()) != 0) {
			last_status_.errno_code = mysql_errno(connection_);
			last_status_.error_message = mysql_error(connection_);
			return false;
		}
		return true;
	}

	bool begin_transaction() override {
		return execute_simple("START TRANSACTION", "mysqli::begin_transaction");
	}

	bool commit() override {
		return execute_simple("COMMIT", "mysqli::commit");
	}

	bool rollback() override {
		return execute_simple("ROLLBACK", "mysqli::rollback");
	}

	[[nodiscard]] status connect_status() const override {
		return connect_status_;
	}

	[[nodiscard]] status last_status() const override {
		return last_status_;
	}

	[[nodiscard]] std::int64_t affected_rows() const override {
		return affected_rows_;
	}

	[[nodiscard]] std::int64_t insert_id() const override {
		return insert_id_;
	}

	void set_connect_status(status status_value) {
		connect_status_ = std::move(status_value);
	}

private:
	MYSQL *connection_ = nullptr;
	status connect_status_{};
	status last_status_{};
	std::int64_t affected_rows_ = 0;
	std::int64_t insert_id_ = 0;
	std::shared_ptr<result_handle> last_result_;

	bool execute_simple(const char *sql, const char *closed_message) {
		last_status_ = {};
		if (connection_ == nullptr) {
			last_status_.errno_code = 1;
			last_status_.error_message = closed_message;
			return false;
		}
		if (mysql_query(connection_, sql) != 0) {
			last_status_.errno_code = mysql_errno(connection_);
			last_status_.error_message = mysql_error(connection_);
			return false;
		}
		affected_rows_ = static_cast<std::int64_t>(mysql_affected_rows(connection_));
		insert_id_ = static_cast<std::int64_t>(mysql_insert_id(connection_));
		return true;
	}
};

#endif

} // namespace

std::shared_ptr<connection_handle> connect(
	const std::string &host,
	const std::string &username,
	const std::string &password,
	const std::string &database,
	std::int64_t port,
	const std::string &socket,
	status &connect_status) {
#if defined(SCPP_HAS_MYSQLI) && SCPP_HAS_MYSQLI
	MYSQL *native = mysql_init(nullptr);
	if (native == nullptr) {
		connect_status.errno_code = 1;
		connect_status.error_message = "mysql_init failed";
		return nullptr;
	}

	const auto normalized_database = normalize_optional_string(database);
	const auto normalized_socket = normalize_optional_string(socket);
	const unsigned int normalized_port = (port < 0 || port > static_cast<std::int64_t>(std::numeric_limits<unsigned int>::max()))
		? 3306U
		: static_cast<unsigned int>(port);

	if (mysql_real_connect(
			native,
			host.c_str(),
			username.c_str(),
			password.c_str(),
			normalized_database.empty() ? nullptr : normalized_database.c_str(),
			normalized_port,
			normalized_socket.empty() ? nullptr : normalized_socket.c_str(),
			0) == nullptr) {
		connect_status.errno_code = mysql_errno(native);
		connect_status.error_message = mysql_error(native);
		mysql_close(native);
		return nullptr;
	}

	connect_status = {};
	auto connection = std::make_shared<mysql_connection>(native);
	connection->set_connect_status(connect_status);
	return connection;
#else
	(void)host;
	(void)username;
	(void)password;
	(void)database;
	(void)port;
	(void)socket;
	connect_status.errno_code = 1;
	connect_status.error_message = "mysqli module not enabled in this build";
	return nullptr;
#endif
}

} // namespace scpp::db::mysql_module
