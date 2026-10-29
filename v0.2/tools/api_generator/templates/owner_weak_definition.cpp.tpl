@n@::@n@(const @shared@& owner) noexcept { detail::ownership_access::construct<@native@>(*this, @strong@(owner)); }
@shared@ @n@::lock() const noexcept { @shared@ result; @strong@(result) = @this@.lock(); return result; }
