#include "scpp/string.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/vector_string.hpp"
#include "scpp/detail/concrete_access.hpp"
#include "scpp/detail/error_value_access.hpp"
#include <type_traits>
namespace scpp {
using native_vector_string = scpp::vector_t<scpp::string>;
static_assert(sizeof(native_vector_string) == 40 && alignof(native_vector_string) == 8);
static_assert(std::is_nothrow_destructible_v<native_vector_string>);
vector_string::vector_string(void (*construct)(void *, const void *), const void *context) { construct(this->storage_, context); }
vector_string::vector_string() { ::new (this->storage_) native_vector_string(); }
vector_string::vector_string(const vector_string &other) { ::new (this->storage_) native_vector_string(detail::concrete_access::get<native_vector_string>(other)); }
vector_string::vector_string(vector_string &&other) { ::new (this->storage_) native_vector_string(std::move(detail::concrete_access::get<native_vector_string>(other))); }
vector_string &vector_string::operator=(const vector_string &other) { if (this != &other) detail::concrete_access::get<native_vector_string>(*this) = detail::concrete_access::get<native_vector_string>(other); return *this; }
vector_string &vector_string::operator=(vector_string &&other) { if (this != &other) detail::concrete_access::get<native_vector_string>(*this) = std::move(detail::concrete_access::get<native_vector_string>(other)); return *this; }
vector_string::~vector_string() noexcept { detail::concrete_access::get<native_vector_string>(*this).~native_vector_string(); }
using native_vector_string_cursor_ = scpp::iteration_detail::vector_range<scpp::string>;
static_assert(sizeof(native_vector_string_cursor_) == 128 && alignof(native_vector_string_cursor_) == 8);
vector_string_cursor_::vector_string_cursor_(void (*construct)(void *, const void *), const void *context) { construct(this->storage_, context); }
vector_string_cursor_::~vector_string_cursor_() noexcept { detail::concrete_access::get<native_vector_string_cursor_>(*this).~native_vector_string_cursor_(); }
boolean vector_string_cursor_::valid() const { auto &range = const_cast<native_vector_string_cursor_ &>(detail::concrete_access::get<native_vector_string_cursor_>(*this)); return boolean(range.begin() != range.end()); }
int64 vector_string_cursor_::key() const { if (!this->valid()) raise_runtime_error("Finished iteration", "index_out_of_range", "core", "iteration"); auto &range = const_cast<native_vector_string_cursor_ &>(detail::concrete_access::get<native_vector_string_cursor_>(*this)); return int64((*range.begin()).key().native_value()); }
scpp::string vector_string_cursor_::value() { if (!this->valid()) raise_runtime_error("Finished iteration", "index_out_of_range", "core", "iteration"); return (*detail::concrete_access::get<native_vector_string_cursor_>(*this).begin()).value_copy(); }
void vector_string_cursor_::next() { if (!this->valid()) raise_runtime_error("Finished iteration", "index_out_of_range", "core", "iteration"); auto iterator = detail::concrete_access::get<native_vector_string_cursor_>(*this).begin(); ++iterator; }
uint64 vector_string::size() const { return uint64(detail::concrete_access::get<native_vector_string>(*this).size()); }
uint64 vector_string::capacity() const { return uint64(detail::concrete_access::get<native_vector_string>(*this).capacity()); }
uint64 vector_string::estimated_storage_bytes() const { return uint64(detail::concrete_access::get<native_vector_string>(*this).estimated_storage_bytes()); }
boolean vector_string::empty() const { return boolean(detail::concrete_access::get<native_vector_string>(*this).empty().native_value()); }
void vector_string::reserve(uint64 count) { detail::concrete_access::get<native_vector_string>(*this).reserve(detail::concrete_size(count)); }
void vector_string::resize(uint64 count, const scpp::string &fill) { detail::concrete_access::get<native_vector_string>(*this).resize(detail::concrete_size(count), fill); }
void vector_string::clear() { detail::concrete_access::get<native_vector_string>(*this).clear(); }
void vector_string::_unset_() { detail::concrete_access::get<native_vector_string>(*this)._unset_(); }
void vector_string::compact() { detail::concrete_access::get<native_vector_string>(*this).compact(); }
void vector_string::compact(uint64 capacity) { detail::concrete_access::get<native_vector_string>(*this).compact(detail::concrete_size(capacity)); }
scpp::string vector_string::at(uint64 index) const { return detail::concrete_access::get<native_vector_string>(*this).at(detail::concrete_size(index)); }
scpp::string vector_string::index(uint64 index) const { return this->at(index); }
scpp::string vector_string::operator[](uint64 index) const { return this->at(index); }
void vector_string::set(uint64 index, const scpp::string &value) { detail::concrete_access::get<native_vector_string>(*this).at(detail::concrete_size(index)) = value; }
void vector_string::append(const scpp::string &value) { detail::concrete_access::get<native_vector_string>(*this).append(value); }
void vector_string::append(scpp::string &&value) { detail::concrete_access::get<native_vector_string>(*this).append(std::move(value)); }
void vector_string::push_back(const scpp::string &value) { this->append(value); }
void vector_string::push_back(scpp::string &&value) { this->append(std::move(value)); }
boolean vector_string::remove(int64 index) { return boolean(detail::concrete_access::get<native_vector_string>(*this).remove(int_t<>(index.native_value()))); }
vector_string_cursor_ vector_string::iterate() const { return detail::concrete_access::make<vector_string_cursor_, native_vector_string_cursor_>([&] { return detail::concrete_access::get<native_vector_string>(*this).value_range(); }); }
uint64 count(const vector_string &value) { return value.size(); }
boolean empty(const vector_string &value) { return value.empty(); }
boolean isset(const vector_string &value) { return boolean(true); }
}
