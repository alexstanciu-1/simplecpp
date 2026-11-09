{
    const auto offset = ::@symbol@(@args@);
    if (offset == -1) return ::scpp::@result@::from_false();
    return ::scpp::@result@{offset};
}
