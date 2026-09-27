<?php

/* Role: share structural traversal for specializations backed by one ordered child list. */
namespace scpp\compiler;

/** Consumers expose their existing list through child_list(); no extra child storage is created. */
trait Child_List
{
	/** Append direct syntax children in grammar order before links are published. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$items /** Storage<ast_node> */ = $this->child_list();
		foreach ($items as $child) {
			$result->append($child);
		}
	}
}
