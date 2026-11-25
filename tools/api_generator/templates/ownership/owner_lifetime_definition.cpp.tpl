static_assert(sizeof(@native@) == @size@ && alignof(@native@) == @align@);
static_assert(sizeof(@n@) == sizeof(@native@) && alignof(@n@) == alignof(@native@));
@n@::@n@() noexcept { detail::ownership_access::construct<@native@>(*this); }
@n@::~@n@() noexcept { detail::ownership_access::destroy<@native@>(*this); }
@n@::@n@(@n@&& other) noexcept { detail::ownership_access::construct<@native@>(*this, std::move(@other@)); }
@n@& @n@::operator=(@n@&& other) noexcept { if (this != &other) @this@ = std::move(@other@); return *this; }
