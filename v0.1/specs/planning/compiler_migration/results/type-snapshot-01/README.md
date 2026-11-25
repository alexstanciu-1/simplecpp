# Type snapshot assembly evidence
Doc Status: planning

26 PHP/native scenarios and 26 host invariants pass. The first native
attempt passed without corrections. Final native dependency bytes match the workspace.
The first PHP pass and later host-only fixture correction are retained separately.

Tests cover local/entry/signature coherence, fixed instance membership, exact family
package/type associations, conversion/language indexes, duplicate rejection and
lookup boundaries. Synthetic callable binding fixtures test snapshot indexing;
provider metadata acceptance remains the importer's separately proved responsibility.

Construction-expression lookup, debug serialization, local-type worker/join and
full coordinator execution remain unfinished. This does not prove a complete
analysis pipeline or preparation service.
