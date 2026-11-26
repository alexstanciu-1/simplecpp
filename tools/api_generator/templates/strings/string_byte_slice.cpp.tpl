    if (arg1 < 0 || arg2 < 0 || arg1 > arg0.size()) return {};
    return string{arg0.data() + arg1, std::min(arg2, arg0.size() - arg1)};
