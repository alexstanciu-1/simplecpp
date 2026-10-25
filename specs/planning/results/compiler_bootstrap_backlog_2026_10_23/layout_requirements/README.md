# Provider layout requirements — 2026-10-25
Doc Status: planning

Approved binding design implemented with optional per-formal argument layout:
declaration/complete (default complete). Native and modifier arguments reject the
field. Unresolved/settled recipes retain one boolean fact; no new type registry.
CPP_Types reuses declaration/stored_value requirements for payload/stored arguments.
Stored inline values remain complete. CPP_Opaque_Representation preserves native
spelling and uses separate layout aliases/dependencies while implementation text
retains complete operation headers. Existing specialization graph/batches are reused.

Storage, Keyed_Storage, Key_Storage_List, Storage_Cursor and weak payload arguments
opt in after native size/alignment and emitted lifecycle/nothrow-trait checks with
an incomplete record. Vector/hash stored arguments opt in without weakening inline
value completeness. Native proofs also construct/appended records and lock weak links.

layout_requirements.py passes all five tasks: direct provider native proof; metadata
invalid-value/target rejection with valid recovery; missing layout field preserving
conservative cycle rejection; ordinary and concrete recursive native fixtures with
Storage-family/weak/vector/object-keyed-hash fields, inline struct values, shared
identity, and warm/fresh backend output equality. Initial fixture corrections used
PHS lock instead of portable PHP weakref_get, Key_Storage_List::items instead of an
unregistered count method, and separate publication from replay. No production
workaround was introduced for fixture mistakes.

The minimal recursive Storage<Self> proof also builds/runs in both modes, where
concrete previously failed. Whole compiler/native generation proof is separate.

Full measured PHP-hosted bootstrap PASSES: 1:45.27 / 796580 KiB RSS / 757 MiB
PHP heap. Preparation, project validation and complete C++ publication succeed.
The report's fatal field is a nonfatal last lstat warning during first publication;
process exits zero, failure is null, emit completion is recorded. Native compiler
build and second-generation behavior are separate outstanding acceptance gates.
