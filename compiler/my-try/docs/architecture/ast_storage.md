# AST object graph
Doc Status: supporting

parsed_file.root is the AST entry. ast_node.specialization directly owns its concrete
payload. Payloads hold direct child-node references and Storage<ast_node> child lists:
block.children, function.parameters, call.arguments, call.template_arguments,
array_literal.elements and struct.fields. These are ordinary mutable object lists.

Parser productions return ast_node, so the same object goes to its parent and the
occurrence collector. There is no node_at lookup, node ID, parallel payload registry,
view membership or read-only bypass. parsed_file.scopes remains the local-scope owner.

Token indexes/spans remain positions into token_list.tokens. Collected occurrences
retain their existing local entry positions. Child lists preserve source/evaluation
order, independently of object allocation order. Node-kind/payload validation stays.

Tests traverse the graph, check payload coverage, source order, spans, scope membership,
collector identity, file separation and unchanged prior results after reuse/failure.
Native allocation and serialization choices are deferred; shared_p<T> is the initial
object representation. Future specialization must not silently turn alias reads
into copies. This replaces the earlier file-owned node/payload-store/view migration.
