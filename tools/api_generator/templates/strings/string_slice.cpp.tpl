namespace str {
@signature@ {
    const int64 size = value.size();
    const int64 begin = byte_position(size, start);
    int64 end = size;
    if (mode == slice_bound::exclusive_end || (mode == slice_bound::length && bound < 0))
        end = byte_position(size, bound);
    else if (mode == slice_bound::length && bound < size - begin)
        end = begin + bound;
    if (end <= begin) return string{};
    return string{value.data() + begin, end - begin};
}
}
