#include "runtime_containers.hpp"
#include "scalar_access.hpp"
#include "scpp_compiler/vector_string.hpp"
#include "string_state.hpp"
#include <limits>
#include <stdexcept>

namespace scpp::compiler_support
{

	struct vector_string::state {
		bindings::native_vector_string value;
	};

	vector_string::vector_string() : state_(new state)
	{
	}

	vector_string::vector_string(const vector_string &other) : state_(new state{other.state_->value})
	{
	}

	vector_string &vector_string::operator=(const vector_string &other)
	{
		this->state_->value = other.state_->value;
		return *this;
	}

	vector_string::~vector_string() noexcept
	{
		delete this->state_;
	}

	void vector_string::append(const string &value)
	{
		this->state_->value.append(value.state_->value);
	}

	void vector_string::reserve(int64 capacity)
	{
		if (capacity < 0 || uint64(capacity) > std::numeric_limits<std::size_t>::max()) {
			throw std::length_error("vector capacity exceeds native size range");
		}
		this->state_->value.reserve(static_cast<std::size_t>(scalar_access::get(capacity).native_value()));
	}

	int64 vector_string::size() const
	{
		const auto size = this->state_->value.size();
		if (size > static_cast<decltype(size)>(std::numeric_limits<std::int64_t>::max())) {
			throw std::length_error("Collection size exceeds signed index range");
		}
		return static_cast<std::int64_t>(size);
	}

	/**
	 * Compare native string values without allocating temporary compiler wrappers.
	 */
	boolean vector_string::contains(const string &value) const
	{
		for (const auto &item : this->state_->value.native_value()) {
			if (item.native_value() == value.state_->value.native_value()) {
				return true;
			}
		}
		return false;
	}

	void vector_string::swap(vector_string &other) noexcept
	{
		std::swap(this->state_, other.state_);
	}

	string vector_string::read(int64 index) const
	{
		string result;
		result.state_->value = this->state_->value.at(scalar_access::get(index));
		return result;
	}

}
