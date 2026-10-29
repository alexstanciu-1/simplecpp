static_assert(sizeof(@n@) == sizeof(@rep@));
static_assert(alignof(@n@) == alignof(@rep@));
static_assert(std::numeric_limits<@rep@>::digits == @digits@);
