#pragma once

struct AsyncTrace {
    int events{};
    int parent_drops{};
    int child_drops{};
};

int report(AsyncTrace& trace, bool fail);
