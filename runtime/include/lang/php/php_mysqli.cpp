#include "scpp/support/php_mysqli.hpp"
#include "scpp/support/php.hpp"

#include <cstdint>
#include <utility>
#include <vector>

namespace scpp {

mysqli::mysqli(
	string_t host,
	string_t username,
	string_t password,
	string_t database,
	int_t port,
	string_t socket) {
	db::mysql_module::status status_value;
	handle_ = db::mysql_module::connect(
		host.native_value(),
		username.native_value(),
		password.native_value(),
		database.native_value(),
		port.native_value(),
		socket.native_value(),
		status_value);
	apply_connect_status(status_value);
	apply_runtime_status(status_value);
}

result_or_bool<shared_p<mysqli_result>> mysqli::query(const string_t &sql) {
	if (handle_ == nullptr) {
		apply_runtime_status({1, "mysqli::query on disconnected connection"});
		return false_sentinel;
	}

	const auto outcome = handle_->query(sql.native_value());
	apply_runtime_status({outcome.errno_code, outcome.error_message});
	affected_rows = int_t(outcome.affected_rows);
	insert_id = int_t(outcome.insert_id);
	if (!outcome.has_result) {
		return bool_t(true);
	}

	auto result_handle = handle_->take_last_result();
	if (result_handle == nullptr) {
		apply_runtime_status({1, "mysqli::query did not surface a buffered result"});
		return false_sentinel;
	}
	return create<mysqli_result>(std::move(result_handle));
}

result_or_false<shared_p<mysqli_stmt>> mysqli::prepare(const string_t &sql) {
	if (handle_ == nullptr) {
		apply_runtime_status({1, "mysqli::prepare on disconnected connection"});
		return false_sentinel;
	}

	auto stmt_handle = handle_->prepare(sql.native_value());
	apply_runtime_status(handle_->last_status());
	if (stmt_handle == nullptr) {
		return false_sentinel;
	}
	return create<mysqli_stmt>(std::move(stmt_handle), this);
}

void mysqli::close() {
	if (handle_ != nullptr) {
		handle_->close();
		handle_.reset();
	}
	errno_code = int_t(0);
	error = string_t("");
}

bool_t mysqli::set_charset(const string_t &charset) {
	if (handle_ == nullptr) {
		apply_runtime_status({1, "mysqli::set_charset on disconnected connection"});
		return bool_t(false);
	}
	const bool ok = handle_->set_charset(charset.native_value());
	apply_runtime_status(handle_->last_status());
	return bool_t(ok);
}

bool_t mysqli::begin_transaction() {
	if (handle_ == nullptr) {
		apply_runtime_status({1, "mysqli::begin_transaction on disconnected connection"});
		return bool_t(false);
	}
	const bool ok = handle_->begin_transaction();
	apply_runtime_status(handle_->last_status());
	affected_rows = int_t(handle_->affected_rows());
	insert_id = int_t(handle_->insert_id());
	return bool_t(ok);
}

bool_t mysqli::commit() {
	if (handle_ == nullptr) {
		apply_runtime_status({1, "mysqli::commit on disconnected connection"});
		return bool_t(false);
	}
	const bool ok = handle_->commit();
	apply_runtime_status(handle_->last_status());
	affected_rows = int_t(handle_->affected_rows());
	insert_id = int_t(handle_->insert_id());
	return bool_t(ok);
}

bool_t mysqli::rollback() {
	if (handle_ == nullptr) {
		apply_runtime_status({1, "mysqli::rollback on disconnected connection"});
		return bool_t(false);
	}
	const bool ok = handle_->rollback();
	apply_runtime_status(handle_->last_status());
	affected_rows = int_t(handle_->affected_rows());
	insert_id = int_t(handle_->insert_id());
	return bool_t(ok);
}

void mysqli::apply_connect_status(const db::mysql_module::status &status_value) {
	connect_errno = int_t(status_value.errno_code);
	connect_error = string_t(status_value.error_message);
}

void mysqli::apply_runtime_status(const db::mysql_module::status &status_value) {
	errno_code = int_t(status_value.errno_code);
	error = string_t(status_value.error_message);
}

mysqli_result::mysqli_result(std::shared_ptr<db::mysql_module::result_handle> handle)
	: handle_(std::move(handle)) {
	if (handle_ != nullptr) {
		num_rows = int_t(handle_->num_rows());
	}
}

dynamic_t mysqli_result::fetch_assoc() {
	if (handle_ == nullptr) {
		return ::scpp::php::to_dynamic(hash_t<mixed_t>{});
	}
	return handle_->fetch_assoc();
}

dynamic_t mysqli_result::fetch_row() {
	if (handle_ == nullptr) {
		return ::scpp::php::to_dynamic(hash_t<mixed_t>{});
	}
	return handle_->fetch_row();
}

mysqli_stmt::mysqli_stmt(
	std::shared_ptr<db::mysql_module::statement_handle> handle,
	mysqli *owner)
	: handle_(std::move(handle)), owner_(owner) {}

bool_t mysqli_stmt::execute() {
	if (handle_ == nullptr) {
		set_local_error(1, "mysqli_stmt::execute on closed statement");
		return bool_t(false);
	}
	if (errno_code.native_value() != 0) {
		return bool_t(false);
	}

	std::vector<db::mysql_module::bound_value> values;
	values.reserve(bound_getters_.size());
	for (const auto &getter : bound_getters_) {
		values.push_back(getter());
	}

	handle_->execute(bound_types_, values);
	apply_status(handle_->last_status());
	affected_rows = int_t(handle_->affected_rows());
	insert_id = int_t(handle_->insert_id());
	if (owner_ != nullptr) {
		owner_->apply_runtime_status(handle_->last_status());
		owner_->affected_rows = affected_rows;
		owner_->insert_id = insert_id;
	}
	return bool_t(errno_code.native_value() == 0);
}

result_or_false<shared_p<mysqli_result>> mysqli_stmt::get_result() {
	if (handle_ == nullptr) {
		set_local_error(1, "mysqli_stmt::get_result on closed statement");
		return false_sentinel;
	}

	auto result_handle = handle_->get_result();
	apply_status(handle_->last_status());
	if (owner_ != nullptr) {
		owner_->apply_runtime_status(handle_->last_status());
	}
	if (result_handle == nullptr) {
		return false_sentinel;
	}
	return create<mysqli_result>(std::move(result_handle));
}

void mysqli_stmt::close() {
	if (handle_ != nullptr) {
		handle_->close();
		handle_.reset();
	}
	clear_local_error();
	affected_rows = int_t(0);
	insert_id = int_t(0);
}

void mysqli_stmt::clear_local_error() {
	errno_code = int_t(0);
	error = string_t("");
}

void mysqli_stmt::set_local_error(std::int64_t errno_value, const char *message) {
	errno_code = int_t(errno_value);
	error = string_t(message);
	if (owner_ != nullptr) {
		owner_->apply_runtime_status({errno_value, message});
	}
}

void mysqli_stmt::apply_status(const db::mysql_module::status &status_value) {
	errno_code = int_t(status_value.errno_code);
	error = string_t(status_value.error_message);
}

} // namespace scpp
