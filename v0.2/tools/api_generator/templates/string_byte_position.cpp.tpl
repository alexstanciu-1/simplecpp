namespace str {
static int64 byte_position(int64 size, int64 index) {
    if (index < 0) return index < -size ? 0 : size + index;
    return index > size ? size : index;
}
}
