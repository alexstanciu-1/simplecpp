#pragma once
struct SharedOwner;
SharedOwner *owner_create(void *, void (*)(void *));
SharedOwner *owner_copy(const SharedOwner *);
void owner_drop(SharedOwner *);
void *owner_get(const SharedOwner *);
