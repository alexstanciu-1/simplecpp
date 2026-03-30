#include "scpp/support/table_t.hpp"

#include <algorithm>

namespace scpp {

// ===== value_t =====

value_t::value_t() noexcept
	: type_(kind_t::null_v) {
}

value_t::value_t(null_t) noexcept
	: type_(kind_t::null_v) {
}

value_t::value_t(const bool_t &value) noexcept
	: type_(kind_t::bool_v),
	  bool_value_(value.native_value()) {
}

value_t::value_t(const int_t &value) noexcept
	: type_(kind_t::int_v),
	  int_value_(value.native_value()) {
}

value_t::value_t(const float_t &value) noexcept
	: type_(kind_t::float_v),
	  float_value_(value.native_value()) {
}

value_t::value_t(const string_t &value)
	: type_(kind_t::string_v) {
	new (&string_value_) std::unique_ptr<string_t>(std::make_unique<string_t>(value));
}

value_t::value_t(const char *value)
	: value_t(string_t(value)) {
}

value_t::value_t(bool value) noexcept
	: type_(kind_t::bool_v),
	  bool_value_(value) {
}

value_t::value_t(std::int64_t value) noexcept
	: type_(kind_t::int_v),
	  int_value_(value) {
}

value_t::value_t(double value) noexcept
	: type_(kind_t::float_v),
	  float_value_(value) {
}

value_t::value_t(std::unique_ptr<table_t> value) noexcept
	: type_(kind_t::table_v) {
	new (&table_value_) std::unique_ptr<table_t>(std::move(value));
}

value_t::value_t(std::shared_ptr<table_t> value) noexcept
	: type_(kind_t::shared_table_v) {
	new (&shared_table_value_) std::shared_ptr<table_t>(std::move(value));
}

value_t::value_t(std::weak_ptr<table_t> value) noexcept
	: type_(kind_t::weak_table_v) {
	new (&weak_table_value_) std::weak_ptr<table_t>(std::move(value));
}

value_t::value_t(std::any value)
	: type_(kind_t::any_v) {
	new (&any_value_) std::any(std::move(value));
}

value_t::~value_t() {
	destroy();
}

value_t::value_t(const value_t &other)
	: type_(kind_t::null_v) {
	*this = other;
}

value_t::value_t(value_t &&other) noexcept
	: type_(kind_t::null_v) {
	move_construct(std::move(other));
}

value_t &value_t::operator=(const value_t &other) {
	if (this == &other) return *this;

	destroy();
	*this = other.clone();
	return *this;
}

value_t &value_t::operator=(value_t &&other) noexcept {
	if (this == &other) return *this;

	destroy();
	move_construct(std::move(other));
	return *this;
}

value_t &value_t::operator=(null_t) noexcept {
	destroy();
	type_ = kind_t::null_v;
	return *this;
}

value_t &value_t::operator=(const bool_t &value) noexcept {
	destroy();
	type_ = kind_t::bool_v;
	bool_value_ = value.native_value();
	return *this;
}

value_t &value_t::operator=(const int_t &value) noexcept {
	destroy();
	type_ = kind_t::int_v;
	int_value_ = value.native_value();
	return *this;
}

value_t &value_t::operator=(const float_t &value) noexcept {
	destroy();
	type_ = kind_t::float_v;
	float_value_ = value.native_value();
	return *this;
}

value_t &value_t::operator=(const string_t &value) {
	destroy();
	type_ = kind_t::string_v;
	new (&string_value_) std::unique_ptr<string_t>(std::make_unique<string_t>(value));
	return *this;
}

value_t &value_t::operator=(const char *value) {
	return (*this = string_t(value));
}

value_t &value_t::operator=(bool value) noexcept {
	destroy();
	type_ = kind_t::bool_v;
	bool_value_ = value;
	return *this;
}

value_t &value_t::operator=(std::int64_t value) noexcept {
	destroy();
	type_ = kind_t::int_v;
	int_value_ = value;
	return *this;
}

value_t &value_t::operator=(double value) noexcept {
	destroy();
	type_ = kind_t::float_v;
	float_value_ = value;
	return *this;
}

value_t value_t::clone() const {
	switch (type_) {
		case kind_t::null_v:
			return value_t(null_t{});
		case kind_t::bool_v:
			return value_t(bool_value_);
		case kind_t::int_v:
			return value_t(int_value_);
		case kind_t::float_v:
			return value_t(float_value_);
		case kind_t::string_v:
			return value_t(*string_value_);
		case kind_t::table_v:
			return value_t(std::make_unique<table_t>(*table_value_));
		case kind_t::shared_table_v:
			return value_t(shared_table_value_);
		case kind_t::weak_table_v:
			return value_t(weak_table_value_);
		case kind_t::any_v:
			return value_t(any_value_);
	}
	return value_t(null_t{});
}

value_t::kind_t value_t::kind() const noexcept {
	return type_;
}

bool_t value_t::is_null() const noexcept {
	return bool_t(type_ == kind_t::null_v);
}

bool_t value_t::bool_value() const {
	if (type_ != kind_t::bool_v) {
		throw std::logic_error("value_t does not hold bool");
	}
	return bool_t(bool_value_);
}

int_t value_t::int_value() const {
	if (type_ != kind_t::int_v) {
		throw std::logic_error("value_t does not hold int");
	}
	return int_t(int_value_);
}

float_t value_t::float_value() const {
	if (type_ != kind_t::float_v) {
		throw std::logic_error("value_t does not hold float");
	}
	return float_t(float_value_);
}

const string_t *value_t::string_if() const noexcept {
	if (type_ != kind_t::string_v) {
		return nullptr;
	}
	return string_value_.get();
}

table_t *value_t::table_if() noexcept {
	switch (type_) {
		case kind_t::table_v:
			return table_value_.get();
		case kind_t::shared_table_v:
			return shared_table_value_.get();
		default:
			return nullptr;
	}
}

const table_t *value_t::table_if() const noexcept {
	switch (type_) {
		case kind_t::table_v:
			return table_value_.get();
		case kind_t::shared_table_v:
			return shared_table_value_.get();
		default:
			return nullptr;
	}
}

void value_t::destroy() noexcept {
	switch (type_) {
		case kind_t::string_v:
			string_value_.~unique_ptr();
			break;
		case kind_t::table_v:
			table_value_.~unique_ptr();
			break;
		case kind_t::shared_table_v:
			shared_table_value_.~shared_ptr();
			break;
		case kind_t::weak_table_v:
			weak_table_value_.~weak_ptr();
			break;
		case kind_t::any_v:
			any_value_.~any();
			break;
		default:
			break;
	}

	type_ = kind_t::null_v;
}

void value_t::move_construct(value_t &&other) noexcept {
	type_ = other.type_;

	switch (type_) {
		case kind_t::null_v:
			break;
		case kind_t::bool_v:
			bool_value_ = other.bool_value_;
			break;
		case kind_t::int_v:
			int_value_ = other.int_value_;
			break;
		case kind_t::float_v:
			float_value_ = other.float_value_;
			break;
		case kind_t::string_v:
			new (&string_value_) std::unique_ptr<string_t>(std::move(other.string_value_));
			break;
		case kind_t::table_v:
			new (&table_value_) std::unique_ptr<table_t>(std::move(other.table_value_));
			break;
		case kind_t::shared_table_v:
			new (&shared_table_value_) std::shared_ptr<table_t>(std::move(other.shared_table_value_));
			break;
		case kind_t::weak_table_v:
			new (&weak_table_value_) std::weak_ptr<table_t>(std::move(other.weak_table_value_));
			break;
		case kind_t::any_v:
			new (&any_value_) std::any(std::move(other.any_value_));
			break;
	}

	other.type_ = kind_t::null_v;
}

// ===== table_t::global_string_pool_t =====

table_t::global_string_pool_t &table_t::global_string_pool_t::instance() {
	static global_string_pool_t pool;
	return pool;
}

std::uint32_t table_t::global_string_pool_t::intern(const string_t &value) {
	auto found = lookup_.find(value.native_value());
	if (found != lookup_.end()) {
		return found->second | string_key_flag;
	}

	const std::uint32_t new_id = static_cast<std::uint32_t>(strings_.size());
	if (new_id > string_id_mask) {
		throw std::overflow_error("table_t string pool capacity exceeded");
	}

	auto [inserted, success] = lookup_.emplace(value.native_value(), new_id);
	(void)success;
	strings_.push_back(&(inserted->first));
	return new_id | string_key_flag;
}

const std::string &table_t::global_string_pool_t::get_string_native(std::uint32_t tagged_id) const {
	const auto real_id = tagged_id & string_id_mask;
	return *(strings_[real_id]);
}

bool table_t::global_string_pool_t::is_string_id(std::uint32_t tagged_id) {
	return (tagged_id & string_key_flag) != 0;
}

// ===== table_t =====

table_t::table_t()
	: keys_(std::monostate{}),
	  hash_index_(std::monostate{}) {
}

table_t::table_t(const table_t &other)
	: keys_(std::monostate{}),
	  hash_index_(std::monostate{}) {
	values_.reserve(other.values_.size());
	for (const auto &value : other.values_) {
		values_.push_back(value.clone());
	}

	if (std::holds_alternative<native_keys_t>(other.keys_)) {
		keys_ = std::get<native_keys_t>(other.keys_);
		const auto current_size = static_cast<std::uint32_t>(values_.size());
		const auto starting_capacity = current_size > 0 ? (current_size * 2) : 4;
		rehash(starting_capacity);
	}
}

table_t &table_t::operator=(const table_t &other) {
	if (this == &other) return *this;

	table_t copy(other);
	*this = std::move(copy);
	return *this;
}

bool_t table_t::empty() const noexcept {
	if (std::holds_alternative<std::monostate>(keys_)) {
		return bool_t(values_.empty());
	}

	const auto &active_keys = std::get<native_keys_t>(keys_);
	for (std::size_t i = 0; i < values_.size(); ++i) {
		if (active_keys[i] != TOMBSTONE_KEY) {
			return bool_t(false);
		}
	}
	return bool_t(true);
}

std::size_t table_t::size() const noexcept {
	if (std::holds_alternative<std::monostate>(keys_)) {
		return values_.size();
	}

	std::size_t count = 0;
	const auto &active_keys = std::get<native_keys_t>(keys_);
	for (const auto key : active_keys) {
		if (key != TOMBSTONE_KEY) ++count;
	}
	return count;
}

bool_t table_t::is_packed() const noexcept {
	return bool_t(std::holds_alternative<std::monostate>(keys_));
}

void table_t::clear() noexcept {
	values_.clear();
	keys_ = std::monostate{};
	hash_index_ = std::monostate{};
}

int_t table_t::append(const value_t &value) {
	const auto next_key = next_append_key();
	insert_or_assign_int(next_key, value.clone());
	return int_t(static_cast<std::int64_t>(next_key));
}

table_t &table_t::set(const int_t &key, const value_t &value) {
	const auto native_key = static_cast<std::uint32_t>(key.native_value());
	insert_or_assign_int(native_key, value.clone());
	return *this;
}

table_t &table_t::set(const string_t &key, const value_t &value) {
	insert_or_assign_int(make_string_key(key), value.clone());
	return *this;
}

bool_t table_t::has(const int_t &key) const {
	return bool_t(find_int(static_cast<std::uint32_t>(key.native_value())).first);
}

bool_t table_t::has(const string_t &key) const {
	return bool_t(find_int(make_string_key(key)).first);
}

maybe_value_t table_t::find(const int_t &key) const {
	auto [found, value] = find_int(static_cast<std::uint32_t>(key.native_value()));
	if (!found) return maybe_value_t(nullopt);
	return maybe_value_t(value->clone());
}

maybe_value_t table_t::find(const string_t &key) const {
	auto [found, value] = find_int(make_string_key(key));
	if (!found) return maybe_value_t(nullopt);
	return maybe_value_t(value->clone());
}

value_t &table_t::at(const int_t &key) {
	auto [found, value] = find_int(static_cast<std::uint32_t>(key.native_value()));
	if (!found) {
		throw std::out_of_range("scpp::table_t::at(int_t): key not found");
	}
	return *value;
}

value_t &table_t::at(const string_t &key) {
	auto [found, value] = find_int(make_string_key(key));
	if (!found) {
		throw std::out_of_range("scpp::table_t::at(string_t): key not found");
	}
	return *value;
}

const value_t &table_t::at(const int_t &key) const {
	auto [found, value] = find_int(static_cast<std::uint32_t>(key.native_value()));
	if (!found) {
		throw std::out_of_range("scpp::table_t::at(int_t) const: key not found");
	}
	return *value;
}

const value_t &table_t::at(const string_t &key) const {
	auto [found, value] = find_int(make_string_key(key));
	if (!found) {
		throw std::out_of_range("scpp::table_t::at(string_t) const: key not found");
	}
	return *value;
}

value_t &table_t::operator[](const int_t &key) {
	const auto native_key = static_cast<std::uint32_t>(key.native_value());
	auto [found, value] = find_int(native_key);
	if (!found) {
		insert_or_assign_int(native_key, value_t(null_t{}));
		return *find_int(native_key).second;
	}
	return *value;
}

value_t &table_t::operator[](const string_t &key) {
	const auto native_key = make_string_key(key);
	auto [found, value] = find_int(native_key);
	if (!found) {
		insert_or_assign_int(native_key, value_t(null_t{}));
		return *find_int(native_key).second;
	}
	return *value;
}

bool table_t::remove(const int_t &key) {
	return erase_int(static_cast<std::uint32_t>(key.native_value()));
}

bool table_t::remove(const string_t &key) {
	return erase_int(make_string_key(key));
}

void table_t::wake_up_associative_mode() {
	const auto current_size = static_cast<std::uint32_t>(values_.size());

	keys_.emplace<native_keys_t>();
	auto &active_keys = std::get<native_keys_t>(keys_);
	active_keys.reserve(current_size > 0 ? current_size : 4);

	for (std::uint32_t implicit_key = 0; implicit_key < current_size; ++implicit_key) {
		active_keys.push_back(implicit_key);
	}

	const auto starting_capacity = current_size > 0 ? (current_size * 2) : 4;
	rehash(starting_capacity);
}

void table_t::add_to_index(std::uint32_t key, std::uint32_t physical_index) {
	check_and_rehash();

	std::visit([&](auto &index) {
		using index_t = std::decay_t<decltype(index)>;
		if constexpr (!std::is_same_v<index_t, std::monostate>) {
			const auto hash = fast_int_hash(key);
			auto h1 = static_cast<std::uint32_t>(hash % index.capacity_);
			const auto h2 = static_cast<std::uint8_t>(hash & 0x7F);

			while (index.ctrl_bytes_[h1] < 128 && index.ctrl_bytes_[h1] != static_cast<std::uint8_t>(ctrl_state_t::deleted)) {
				h1 = (h1 + 1) % index.capacity_;
			}

			index.ctrl_bytes_[h1] = h2;
			index.buckets_[h1] = static_cast<typename std::decay_t<decltype(index.buckets_)>::value_type>(physical_index);
			index.size_++;
		}
	}, hash_index_);
}

bool table_t::erase_packed(std::uint32_t index) {
	if (index >= values_.size()) return false;

	// Packed erase must preserve logical keys.
	// Removing physical slot N from a packed vector would shift later values left
	// and silently renumber their keys. Promote once to associative mode, then
	// remove by logical key using the tombstone path.
	wake_up_associative_mode();
	return erase_int(index);
}

void table_t::check_and_rehash() {
	if (std::holds_alternative<std::monostate>(hash_index_)) return;

	std::uint32_t capacity = 0;
	std::uint32_t size = 0;
	std::uint32_t deleted = 0;

	std::visit([&](auto &index) {
		using index_t = std::decay_t<decltype(index)>;
		if constexpr (!std::is_same_v<index_t, std::monostate>) {
			capacity = index.capacity_;
			size = index.size_;
			deleted = index.deleted_count_;
		}
	}, hash_index_);

	const auto occupied = size + deleted;
	if (occupied >= (capacity * 3) / 4) {
		auto new_capacity = capacity;
		if (size >= capacity / 2) {
			new_capacity = capacity * 2;
		}
		rehash(new_capacity == 0 ? 4 : new_capacity);
	}
}

void table_t::rehash(std::uint32_t new_capacity) {
	decltype(hash_index_) new_index;

	if (new_capacity <= 256) {
		new_index.emplace<flat_hash_index_t<std::uint8_t>>();
	} else if (new_capacity <= 65536) {
		new_index.emplace<flat_hash_index_t<std::uint16_t>>();
	} else {
		new_index.emplace<flat_hash_index_t<std::uint32_t>>();
	}

	auto &active_keys = std::get<native_keys_t>(keys_);

	std::visit([&](auto &index) {
		using index_t = std::decay_t<decltype(index)>;
		if constexpr (!std::is_same_v<index_t, std::monostate>) {
			index.capacity_ = new_capacity;
			index.size_ = 0;
			index.deleted_count_ = 0;
			index.ctrl_bytes_.assign(new_capacity, static_cast<std::uint8_t>(ctrl_state_t::empty));
			index.buckets_.resize(new_capacity);

			for (std::uint32_t i = 0; i < values_.size(); ++i) {
				if (active_keys[i] == TOMBSTONE_KEY) continue;

				const auto hash = fast_int_hash(active_keys[i]);
				auto h1 = static_cast<std::uint32_t>(hash % new_capacity);
				const auto h2 = static_cast<std::uint8_t>(hash & 0x7F);

				while (index.ctrl_bytes_[h1] < 128) {
					h1 = (h1 + 1) % new_capacity;
				}

				index.ctrl_bytes_[h1] = h2;
				index.buckets_[h1] = static_cast<typename std::decay_t<decltype(index.buckets_)>::value_type>(i);
				index.size_++;
			}
		}
	}, new_index);

	hash_index_ = std::move(new_index);
}

void table_t::insert_or_assign_int(std::uint32_t key, value_t value) {
	if (std::holds_alternative<std::monostate>(keys_)) {
		// Packed mode keeps vector-fast writes for contiguous integer keys only.
		if (!global_string_pool_t::is_string_id(key)) {
			if (key < values_.size()) {
				values_[key] = std::move(value);
				return;
			}
			if (key == values_.size()) {
				values_.push_back(std::move(value));
				return;
			}
		}

		// Any non-packed shape promotes storage once, then uses associative rules.
		wake_up_associative_mode();
	}

	// Associative mode uses overwrite-or-append semantics.
	if (auto [found, existing] = find_int(key); found) {
		*existing = std::move(value);
		return;
	}

	auto &active_keys = std::get<native_keys_t>(keys_);
	const auto physical_index = static_cast<std::uint32_t>(values_.size());
	active_keys.push_back(key);
	values_.push_back(std::move(value));
	add_to_index(key, physical_index);
}

std::pair<bool, value_t *> table_t::find_int(std::uint32_t key) {
	if (std::holds_alternative<std::monostate>(keys_)) {
		if (!global_string_pool_t::is_string_id(key) && key < values_.size()) {
			return {true, &values_[key]};
		}
		return {false, nullptr};
	}

	auto &active_keys = std::get<native_keys_t>(keys_);
	std::pair<bool, value_t *> result = {false, nullptr};

	std::visit([&](auto &index) {
		using index_t = std::decay_t<decltype(index)>;
		if constexpr (!std::is_same_v<index_t, std::monostate>) {
			if (index.capacity_ == 0) return;

			const auto hash = fast_int_hash(key);
			auto h1 = static_cast<std::uint32_t>(hash % index.capacity_);
			const auto h2 = static_cast<std::uint8_t>(hash & 0x7F);

			while (index.ctrl_bytes_[h1] != static_cast<std::uint8_t>(ctrl_state_t::empty)) {
				if (index.ctrl_bytes_[h1] == h2) {
					const auto physical_index = static_cast<std::uint32_t>(index.buckets_[h1]);
					if (active_keys[physical_index] == key) {
						result = {true, &values_[physical_index]};
						return;
					}
				}
				h1 = (h1 + 1) % index.capacity_;
			}
		}
	}, hash_index_);

	return result;
}

std::pair<bool, const value_t *> table_t::find_int(std::uint32_t key) const {
	if (std::holds_alternative<std::monostate>(keys_)) {
		if (!global_string_pool_t::is_string_id(key) && key < values_.size()) {
			return {true, &values_[key]};
		}
		return {false, nullptr};
	}

	const auto &active_keys = std::get<native_keys_t>(keys_);
	std::pair<bool, const value_t *> result = {false, nullptr};

	std::visit([&](const auto &index) {
		using index_t = std::decay_t<decltype(index)>;
		if constexpr (!std::is_same_v<index_t, std::monostate>) {
			if (index.capacity_ == 0) return;

			const auto hash = fast_int_hash(key);
			auto h1 = static_cast<std::uint32_t>(hash % index.capacity_);
			const auto h2 = static_cast<std::uint8_t>(hash & 0x7F);

			while (index.ctrl_bytes_[h1] != static_cast<std::uint8_t>(ctrl_state_t::empty)) {
				if (index.ctrl_bytes_[h1] == h2) {
					const auto physical_index = static_cast<std::uint32_t>(index.buckets_[h1]);
					if (active_keys[physical_index] == key) {
						result = {true, &values_[physical_index]};
						return;
					}
				}
				h1 = (h1 + 1) % index.capacity_;
			}
		}
	}, hash_index_);

	return result;
}

bool table_t::erase_int(std::uint32_t key) {
	if (std::holds_alternative<std::monostate>(keys_)) {
		return erase_packed(key);
	}

	bool found = false;
	auto &active_keys = std::get<native_keys_t>(keys_);

	std::visit([&](auto &index) {
		using index_t = std::decay_t<decltype(index)>;
		if constexpr (!std::is_same_v<index_t, std::monostate>) {
			if (index.capacity_ == 0) return;

			const auto hash = fast_int_hash(key);
			auto h1 = static_cast<std::uint32_t>(hash % index.capacity_);
			const auto h2 = static_cast<std::uint8_t>(hash & 0x7F);

			while (index.ctrl_bytes_[h1] != static_cast<std::uint8_t>(ctrl_state_t::empty)) {
				if (index.ctrl_bytes_[h1] == h2) {
					const auto physical_index = static_cast<std::uint32_t>(index.buckets_[h1]);
					if (active_keys[physical_index] == key) {
						index.ctrl_bytes_[h1] = static_cast<std::uint8_t>(ctrl_state_t::deleted);
						index.size_--;
						index.deleted_count_++;

						active_keys[physical_index] = TOMBSTONE_KEY;
						values_[physical_index] = value_t(null_t{});
						found = true;
						return;
					}
				}
				h1 = (h1 + 1) % index.capacity_;
			}
		}
	}, hash_index_);

	return found;
}

std::uint32_t table_t::next_append_key() const {
	if (std::holds_alternative<std::monostate>(keys_)) {
		return static_cast<std::uint32_t>(values_.size());
	}

	std::uint32_t max_key = 0;
	bool have_int_key = false;
	const auto &active_keys = std::get<native_keys_t>(keys_);

	for (const auto key : active_keys) {
		if (key == TOMBSTONE_KEY) continue;
		if (global_string_pool_t::is_string_id(key)) continue;

		if (!have_int_key || key > max_key) {
			max_key = key;
			have_int_key = true;
		}
	}

	return have_int_key ? (max_key + 1) : 0;
}

std::uint32_t table_t::make_string_key(const string_t &key) {
	return global_string_pool_t::instance().intern(key);
}

} // namespace scpp
