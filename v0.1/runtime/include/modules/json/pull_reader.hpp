#pragma once
#include "modules/json/detail/reader.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/result.hpp"
#include "scpp/shared_p.hpp"
#include <charconv>
#include <memory>
#include <unordered_set>
#include <vector>

namespace scpp::json {
// A failed reader is terminal. Consumers publish only after finish_document().
class read_error final : public std::runtime_error {
public:
	std::size_t position;
	std::string category, path, detail;
	read_error(const reader_detail::json_parse_failure &failure, const std::string &file, std::string where)
		: std::runtime_error(file + ": byte " + std::to_string(failure.position) + " at " + where + ": " + failure.message),
		position(failure.position), category(failure.category), path(std::move(where)), detail(failure.message) {}
};

class pull_reader final : private reader_detail::json_reader {
	struct frame {
		bool object, first = true;
		std::size_t index = 0;
		std::string path;
		std::unordered_set<std::string> keys;
	};
	std::string source_, file_, path_ = "$", key_;
	std::vector<frame> frames_;
	std::size_t limit_;
	bool pending_ = true, failed_ = false, finished_ = false;

	template<class F> decltype(auto) checked(F &&work) {
		try {
			if (failed_) reader_detail::fail_json_parse("JSON reader is failed", pos_, "protocol");
			return std::forward<F>(work)();
		}
		catch (const reader_detail::json_parse_failure &failure) {
			failed_ = true;
			throw read_error(failure, file_, path_);
		}
	}
	void need_value() {
		if (!pending_ || finished_) reader_detail::fail_json_parse("no selected JSON value", pos_, "protocol");
		skip_whitespace();
	}
	std::string kind_impl() {
		need_value();
		switch (peek()) {
			case '{': return "object"; case '[': return "array";
			case '"': return "string"; case 't': case 'f': return "boolean"; case 'n': return "null";
			default:
				if (peek() == '-' || reader_detail::is_ascii_digit(peek())) return "number";
				reader_detail::fail_json_parse("unexpected character", pos_);
		}
	}
	void require_kind(const char *kind) {
		if (kind_impl() != kind) reader_detail::fail_json_parse(std::string("expected ") + kind, pos_, "type");
	}
	void begin(bool object) {
		require_kind(object ? "object" : "array");
		if (frames_.size() >= limit_) reader_detail::fail_json_parse("nesting limit exceeded", pos_, "depth");
		++pos_;
		frames_.push_back(frame{object, true, 0, path_, {}});
		pending_ = false;
	}
	bool next(bool object) {
		if (pending_ || frames_.empty() || frames_.back().object != object)
			reader_detail::fail_json_parse("consume selected value before advancing the matching container", pos_, "protocol");
		auto &scope = frames_.back();
		path_ = scope.path;
		skip_whitespace();
		const char close = object ? '}' : ']';
		if (peek() == close) { ++pos_; frames_.pop_back(); return false; }
		if (!scope.first) {
			if (peek() != ',') reader_detail::fail_json_parse("expected comma or container end", pos_);
			++pos_; skip_whitespace();
		}
		scope.first = false;
		if (object) {
			const auto start = pos_;
			key_ = parse_string().native_value();
			// Bracket paths preserve arbitrary decoded member names without ambiguity.
			path_ += "[" + std::string(input_.substr(start, pos_ - start)) + "]";
			if (!scope.keys.insert(key_).second)
				reader_detail::fail_json_parse("duplicate object key " + std::string(input_.substr(start, pos_ - start)), start, "duplicate_key");
			skip_whitespace();
			if (get() != ':') reader_detail::fail_json_parse("expected ':'", pos_ - 1);
		} else {
			path_ += "[" + std::to_string(scope.index++) + "]";
		}
		pending_ = true;
		return true;
	}
public:
	explicit pull_reader(const string_t &text, const string_t &file = string_t("<json>"), const int_t<> &limit = int_t<>(128))
		: json_reader({}), source_(text.native_value()), file_(file.native_value()), limit_(0) {
		input_ = source_;
		checked([&] {
			if (limit.native_value() < 1 || limit.native_value() > 256)
				reader_detail::fail_json_parse("max_depth must be 1..256", 0, "invalid_limit");
			limit_ = static_cast<std::size_t>(limit.native_value());
			reader_detail::validate_utf8(input_);
		});
	}
	pull_reader(const pull_reader &) = delete;
	pull_reader &operator=(const pull_reader &) = delete;
	string_t kind() { return checked([&] { return string_t(kind_impl()); }); }
	void begin_object() { checked([&] { begin(true); }); }
	void begin_array() { checked([&] { begin(false); }); }
	bool_t next_property() { return checked([&] { return bool_t(next(true)); }); }
	bool_t next_element() { return checked([&] { return bool_t(next(false)); }); }
	string_t property_name() {
		return checked([&] {
			if (!pending_ || frames_.empty() || !frames_.back().object)
				reader_detail::fail_json_parse("no selected object property", pos_, "protocol");
			return string_t(key_);
		});
	}
	string_t read_string() {
		return checked([&] { require_kind("string"); auto value = parse_string(); pending_ = false; return value; });
	}
	string_t read_number() {
		return checked([&] { require_kind("number"); auto value = string_t(std::string(parse_number_token())); pending_ = false; return value; });
	}
	int_t<> read_integer() {
		return checked([&] {
			require_kind("number"); const auto start = pos_; const auto token = parse_number_token();
			std::int64_t value = 0;
			const auto result = std::from_chars(token.data(), token.data() + token.size(), value);
			if (result.ec != std::errc{} || result.ptr != token.data() + token.size())
				reader_detail::fail_json_parse("expected signed 64-bit integer spelling", start, "integer");
			pending_ = false; return int_t<>(value);
		});
	}
	bool_t read_boolean() {
		return checked([&] { require_kind("boolean"); const bool value = peek() == 't'; expect_literal(value ? "true" : "false"); pending_ = false; return bool_t(value); });
	}
	void read_null() { checked([&] { require_kind("null"); expect_literal("null"); pending_ = false; }); }
	void reject(const string_t &message) {
		checked([&] { reader_detail::fail_json_parse(message.native_value(), pos_, "schema"); });
	}
	void finish_document() {
		checked([&] {
			if (pending_ || !frames_.empty()) reader_detail::fail_json_parse("unconsumed JSON value", pos_, "protocol");
			skip_whitespace();
			if (!at_end()) reader_detail::fail_json_parse("trailing non-whitespace after JSON value", pos_);
			finished_ = true;
		});
	}
};
} // namespace scpp::json
namespace scpp {
// Checked language boundary. The core also serves native consumers (document
// construction); portable callers translate these Results to their exception family.
class json_pull_reader final {
	std::unique_ptr<json::pull_reader> cursor_;
	bool started_ = false;
	template<class F> auto invoke(F &&work) -> result<decltype(work())> {
		if (!cursor_) return error_t(string_t("JSON reader is not initialized"));
		try { return work(); }
		catch (const json::read_error &failure) { return error_t(string_t(failure.what())); }
	}
public:
	result<bool_t> start(const string_t &text, const string_t &file, const int_t<> &limit) {
		if (started_) return error_t(string_t("JSON reader cannot be restarted"));
		started_ = true;
		try { cursor_ = std::make_unique<json::pull_reader>(text, file, limit); return bool_t(true); }
		catch (const json::read_error &failure) { return error_t(string_t(failure.what())); }
	}
	result<string_t> kind() { return invoke([&] { return cursor_->kind(); }); }
	result<bool_t> begin_object() { return invoke([&] { cursor_->begin_object(); return bool_t(true); }); }
	result<bool_t> begin_array() { return invoke([&] { cursor_->begin_array(); return bool_t(true); }); }
	result<bool_t> next_property() { return invoke([&] { return cursor_->next_property(); }); }
	result<bool_t> next_element() { return invoke([&] { return cursor_->next_element(); }); }
	result<string_t> property_name() { return invoke([&] { return cursor_->property_name(); }); }
	result<string_t> read_string() { return invoke([&] { return cursor_->read_string(); }); }
	result<string_t> read_number() { return invoke([&] { return cursor_->read_number(); }); }
	result<int_t<>> read_integer() { return invoke([&] { return cursor_->read_integer(); }); }
	result<bool_t> read_boolean() { return invoke([&] { return cursor_->read_boolean(); }); }
	result<bool_t> read_null() { return invoke([&] { cursor_->read_null(); return bool_t(true); }); }
	result<bool_t> finish_document() { return invoke([&] { cursor_->finish_document(); return bool_t(true); }); }
	result<bool_t> reject(const string_t &message) { return invoke([&] { cursor_->reject(message); return bool_t(false); }); }
};
namespace json {
// Allocate a fresh shared wrapper; parsing begins only through start().
inline shared_p<json_pull_reader> pull_reader_create() {
	return shared_p<json_pull_reader>(std::make_shared<json_pull_reader>());
}
}
}
