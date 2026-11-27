// Structural reader adapted from the audited JSON donor. All public transports live outside this header.
#pragma once
#include "reader.hpp"
#include "../sequence/utf8_codepoints.hpp"
#include <array>
#include <charconv>
#include <unordered_set>
#include <vector>

namespace scpp::detail::json {
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
		// Small objects need no hash nodes or bucket array. Only overflow keys
		// enter the set; index counts inline keys here and elements in array frames.
		std::array<std::string, 2> inline_keys{};
		std::unordered_set<std::string> overflow_keys{};

		bool insert_key(const std::string& key) {
			for (std::size_t i = 0; i < index; ++i)
				if (inline_keys[i] == key) return false;
			if (index == inline_keys.size()) return overflow_keys.insert(key).second;
			inline_keys[index++] = key;
			return true;
		}
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
		frames_.push_back(frame{object, true, 0, path_});
		pending_ = false;
	}
	template<class Insert> bool next(bool object, Insert insert) {
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
			key_ = parse_string();
			// Bracket paths preserve arbitrary decoded member names without ambiguity.
			path_.push_back('[');
			path_.append(input_.substr(start, pos_ - start));
			path_.push_back(']');
			if (!insert(scope, key_))
				reader_detail::fail_json_parse("duplicate object key " + std::string(input_.substr(start, pos_ - start)), start, "duplicate_key");
			skip_whitespace();
			if (get() != ':') reader_detail::fail_json_parse("expected ':'", pos_ - 1);
		} else {
			path_ += "[" + std::to_string(scope.index++) + "]";
		}
		pending_ = true;
		return true;
	}
	bool next(bool object) {
		return next(object, [](frame& scope, const std::string& key) { return scope.insert_key(key); });
	}
	// Keep the common array index on success. Replay from the delimiter on a miss
	// so scalar traversal owns diagnostic precedence, position and element path.
	template<class TryElement, class ScalarElement> void read_array(TryElement fast, ScalarElement scalar) {
		begin_array();
		auto& scope = frames_.back();
		std::size_t checkpoint;
		for (;;) {
			checkpoint = pos_;
			skip_whitespace();
			if (peek() == ']') {
				++pos_;
				path_ = scope.path;
				frames_.pop_back();
				return;
			}
			if (!scope.first) {
				if (peek() != ',') break;
				++pos_;
				skip_whitespace();
			}
			if (!fast()) break;
			scope.first = false;
			++scope.index;
		}
		pos_ = checkpoint;
		while (next_element()) scalar();
	}
	void validate_input(std::int64_t limit) {
		checked([&] {
			if (limit < 1 || limit > 256)
				reader_detail::fail_json_parse("max_depth must be 1..256", 0, "invalid_limit");
			limit_ = static_cast<std::size_t>(limit);
			for (std::size_t i = 0; i < input_.size();) {
				std::uint32_t scalar;
				const auto width = utf8::decode(input_.substr(i), scalar);
				if (!width) reader_detail::fail_json_parse("invalid UTF-8", i, "utf8");
				i += width;
			}
		});
	}
public:
	explicit pull_reader(std::string text, std::string file, std::int64_t limit)
		: json_reader({}), source_(std::move(text)), file_(std::move(file)) {
		input_ = source_;
		validate_input(limit);
	}
	struct borrow_input {};
	pull_reader(borrow_input, std::string_view text, std::string file, std::int64_t limit)
		: json_reader(text), file_(std::move(file)) {
		validate_input(limit);
	}
	pull_reader(const pull_reader &) = delete;
	pull_reader &operator=(const pull_reader &) = delete;
	std::string kind() { return checked([&] { return std::string(kind_impl()); }); }
	void begin_object() { checked([&] { begin(true); }); }
	void begin_array() { checked([&] { begin(false); }); }
	bool next_property() { return checked([&] { return next(true); }); }
	template<class Insert> bool next_record_property(Insert insert) {
		return checked([&] {
			return next(true, [&](frame&, const std::string& key) { return insert(key); });
		});
	}
	bool next_element() { return checked([&] { return next(false); }); }
	// Valid until this reader advances to another property.
	std::string_view property_view() {
		return checked([&]() -> std::string_view {
			if (!pending_ || frames_.empty() || !frames_.back().object)
				reader_detail::fail_json_parse("no selected object property", pos_, "protocol");
			return key_;
		});
	}
	std::string property_name() { return std::string(property_view()); }
	std::string read_string() {
		return checked([&] { require_kind("string"); auto value = parse_string(); pending_ = false; return value; });
	}
	// The plain span is borrowed only during construction; escaped bytes transfer to the destination.
	template<class Plain, class Owned> auto read_string(Plain plain, Owned owned) {
		return checked([&] {
			require_kind("string");
			const auto start = pos_;
			const auto prefix = parse_string_prefix();
			if (peek() == '"') {
				++pos_;
				pending_ = false;
				return plain(prefix);
			}
			auto bytes = parse_string_tail(start, prefix);
			pending_ = false;
			return owned(std::move(bytes));
		});
	}
	std::string read_number() {
		return checked([&] { require_kind("number"); auto value = std::string(parse_number_token()); pending_ = false; return value; });
	}
	std::int64_t read_integer() {
		return checked([&] {
			require_kind("number"); const auto start = pos_; const auto token = parse_number_token();
			std::int64_t value = 0;
			const auto result = std::from_chars(token.data(), token.data() + token.size(), value);
			if (result.ec != std::errc{} || result.ptr != token.data() + token.size())
				reader_detail::fail_json_parse("expected signed 64-bit integer spelling", start, "integer");
			pending_ = false; return value;
		});
	}
	template<class Append> void read_integers(Append append) {
		read_array([&] {
			const char* first = input_.data() + pos_;
			const char* end = input_.data() + input_.size();
			std::int64_t value;
			const auto parsed = std::from_chars(first, end, value);
			if (parsed.ec != std::errc{}) return false;
			const char* digits = first + (*first == '-');
			if (*digits == '0' && parsed.ptr - digits > 1) return false;
			if (parsed.ptr != end && (*parsed.ptr == '.' || *parsed.ptr == 'e' || *parsed.ptr == 'E')) return false;
			pos_ = static_cast<std::size_t>(parsed.ptr - input_.data());
			append(value);
			return true;
		}, [&] { append(read_integer()); });
	}
	template<class Plain, class Owned, class Append> void read_strings(Plain plain, Owned owned, Append append) {
		read_array([&] {
			if (peek() != '"') return false;
			const auto start = pos_;
			const auto prefix = parse_string_prefix();
			if (peek() == '"') {
				++pos_;
				append(plain(prefix));
			} else {
				std::string bytes;
				try {
					bytes = parse_string_tail(start, prefix);
				} catch (const reader_detail::json_parse_failure&) {
					return false;
				}
				append(owned(std::move(bytes)));
			}
			return true;
		}, [&] { append(read_string(plain, owned)); });
	}
	bool read_boolean() {
		return checked([&] { require_kind("boolean"); const bool value = peek() == 't'; expect_literal(value ? "true" : "false"); pending_ = false; return value; });
	}
	void read_null() { checked([&] { require_kind("null"); expect_literal("null"); pending_ = false; }); }
	void reject(const std::string &message) {
		checked([&] { reader_detail::fail_json_parse(message, pos_, "schema"); });
	}
	bool at_root() const noexcept { return frames_.empty(); }
	void finish_document() {
		checked([&] {
			if (pending_ || !frames_.empty()) reader_detail::fail_json_parse("unconsumed JSON value", pos_, "protocol");
			skip_whitespace();
			if (!at_end()) reader_detail::fail_json_parse("trailing non-whitespace after JSON value", pos_);
			finished_ = true;
		});
	}
};
}
