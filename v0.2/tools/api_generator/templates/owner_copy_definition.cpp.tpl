@n@::@n@(const @n@& other) noexcept { detail::ownership_access::construct<@native@>(*this, @other@); }
@n@& @n@::operator=(const @n@& other) noexcept { @this@ = @other@; return *this; }
