namespace str {
@signature@ {
    const int64 size = value.size();
    const auto position = [size](int64 index) -> int64 {
        if (index < 0) return index < -size ? 0 : size + index;
        return index > size ? size : index;
    };
    const int64 begin = position(start);
    int64 end = size;
    if (mode == slice_bound::exclusive_end || (mode == slice_bound::length && bound < 0))
        end = position(bound);
    else if (mode == slice_bound::length && bound < size - begin)
        end = begin + bound;
    if (end <= begin) return string{};
    return string{value.data() + begin, end - begin};
}
}
