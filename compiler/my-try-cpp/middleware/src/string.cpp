#include "operators/empty/empty.hpp"
#include "operators/isset/isset.hpp"
#include "scalar_access.hpp"
#include "string_state.hpp"
#include <limits>
#include <stdexcept>
#include <string_view>

namespace scpp::compiler_support
{

	string::string() : state_(new state)
	{
	}

	string::string(const char *text) : state_(new state{bindings::native_string(text)})
	{
	}

	string::string(const char *bytes, uint64 size) : state_(nullptr)
	{
		if ((bytes == nullptr && size != 0) || size > std::numeric_limits<std::size_t>::max()) {
			throw std::invalid_argument("invalid string byte range");
		}
		this->state_ = new state{
			bindings::native_string(std::string_view(bytes == nullptr ? "" : bytes, static_cast<std::size_t>(scalar_access::get(size).native_value())))};
	}

	string::string(const string &other) : state_(new state{other.state_->value})
	{
	}

	string &string::operator=(const string &other)
	{
		this->state_->value = other.state_->value;
		return *this;
	}

	string::~string() noexcept
	{
		delete this->state_;
	}

	boolean string::equals(const string &other) const
	{
		return this->state_->value.native_value() == other.state_->value.native_value();
	}

	boolean string::equals(const char *text) const
	{
		return this->state_->value.native_value() == text;
	}

	/**
	 * Scan borrowed vocabulary bytes without constructing strings or allocating.
	 */
	boolean string::is_one_of(const char *vocabulary) const
	{
		const std::string_view words(vocabulary);
		const std::string_view key(this->state_->value.native_value());
		std::size_t start = 0;
		while (start < words.size())
		{
			const auto separator = words.find(' ', start);
			const auto end = separator == std::string_view::npos ? words.size() : separator;
			if (end > start && words.substr(start, end - start) == key) {
				return true;
			}
			if (separator == std::string_view::npos) {
				break;
			}
			start = end + 1;
		}
		return false;
	}

	uint32 string::byte_at(uint64 index) const
	{
		if (index >= this->byte_size()) {
			throw std::out_of_range("string byte index");
		}
		return static_cast<unsigned char>(this->state_->value.native_value().at(static_cast<std::size_t>(scalar_access::get(index).native_value())));
	}

	void string::swap(string &other) noexcept
	{
		std::swap(this->state_, other.state_);
	}

	uint64 string::byte_size() const noexcept
	{
		return this->state_->value.byte_size();
	}

	boolean empty(const string &value)
	{
		return scpp::empty(runtime_access::get(value)).native_value();
	}

	boolean isset(const string &value)
	{
		return scpp::isset(runtime_access::get(value)).native_value();
	}

	uint64 strlen(const string &value)
	{
		return runtime_access::get(value).byte_size();
	}
}
