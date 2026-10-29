static_assert(static_cast<unsigned>(boxed_kind::null) == static_cast<unsigned>(@native@::kind::null));
static_assert(static_cast<unsigned>(boxed_kind::boolean) == static_cast<unsigned>(@native@::kind::boolean));
static_assert(static_cast<unsigned>(boxed_kind::integer) == static_cast<unsigned>(@native@::kind::integer));
static_assert(static_cast<unsigned>(boxed_kind::floating) == static_cast<unsigned>(@native@::kind::floating));
static_assert(static_cast<unsigned>(boxed_kind::string) == static_cast<unsigned>(@native@::kind::string));
static_assert(static_cast<unsigned>(boxed_kind::container) == static_cast<unsigned>(@native@::kind::container));
static_assert(sizeof(@n@) == sizeof(@native@) && alignof(@n@) == alignof(@native@));
static_assert(sizeof(@native@) == @size@ && alignof(@native@) == @alignment@);
@n@::@n@() noexcept { detail::ownership_access::construct<@native@>(*this); }
@n@::~@n@() noexcept { detail::ownership_access::destroy<@native@>(*this); }
@n@::@n@(const @n@& other) noexcept { detail::ownership_access::construct<@native@>(*this, @other@); }
@n@::@n@(@n@&& other) noexcept { detail::ownership_access::construct<@native@>(*this, std::move(@other@)); }
@n@& @n@::operator=(const @n@& other) noexcept { @self@ = @other@; return *this; }
@n@& @n@::operator=(@n@&& other) noexcept { @self@ = std::move(@other@); return *this; }
@n@ @n@::from_boolean(bool_t value) noexcept { @n@ result; @result@ = @native@::boolean(value.native_value()); return result; }
@n@ @n@::from_integer(int64 value) noexcept { @n@ result; @result@ = @native@::integer(value.native_value()); return result; }
@n@ @n@::from_floating(float64 value) noexcept { @n@ result; @result@ = @native@::floating(value.native_value()); return result; }
@n@ @n@::from_string(const string& value) { @n@ result; @result@ = @native@::string({value.data(), static_cast<std::size_t>(value.size().native_value())}); return result; }
@n@ @n@::container() { @n@ result; @result@ = @native@::container(); return result; }
boxed_kind @n@::kind() const noexcept { return static_cast<boxed_kind>(@self@.type()); }
bool_t @n@::get_boolean() const { return bool_t{@self@.get_boolean()}; }
int64 @n@::get_integer() const { return int64{static_cast<long long>(@self@.get_integer())}; }
float64 @n@::get_floating() const { return float64{@self@.get_floating()}; }
string @n@::get_string() const { const auto bytes = @self@.get_string(); return string{bytes.data(), int64{detail::checked_integer_cast<long long>(bytes.size())}}; }
int64 @n@::append(@n@ value) { return int64{static_cast<long long>(@self@.append(std::move(@value@)))}; }
int64 @n@::container_size() const { return int64{detail::checked_integer_cast<long long>(@self@.container_size())}; }
bool_t @n@::is_packed_container() const { return bool_t{@self@.is_packed_container()}; }
void @n@::clean() noexcept { @self@.clean(); }
