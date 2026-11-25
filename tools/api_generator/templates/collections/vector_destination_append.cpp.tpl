struct @helper@ final {
    static void append(@vector@& target, @parameters@) {
        auto* storage = target.storage_;
        if (storage && storage->count < storage->capacity) {
            ::new (storage->data + storage->count * sizeof(@element@)) @element@{@inputs@};
            ++storage->count;
            return;
        }
        target.append({@inputs@});
    }
};
