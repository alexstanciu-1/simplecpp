{
    const auto offset = ::@symbol@(arg0, arg1);
    if (offset == -1) return ::scpp::@result@::from_false();
    return ::scpp::@result@{offset};
}
