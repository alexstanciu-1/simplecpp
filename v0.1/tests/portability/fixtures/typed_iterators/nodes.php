<?php

/* Minimal typed owners for the actual production inspection cursor implementations. */
namespace scpp\compiler;

abstract class ast_node
{
	public int $value = 0;
}

interface optional_node_i
{
	public function optional_node(): ?ast_node;
}

final class parameter_node extends ast_node implements optional_node_i
{
	public function optional_node(): ?ast_node
	{
		return null;
	}
}

final class named_type_node extends ast_node
{
}

final class function_body_node extends ast_node
{
}

final class function_node extends ast_node
{
	public Storage $parameters /** Storage<parameter_node> */;
	public named_type_node $return_type;
	public function_body_node $body;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<parameter_node> */();
		$this->return_type = new named_type_node();
		$this->return_type->value = 11;
		$this->body = new function_body_node();
		$this->body->value = 13;
	}

	public function children(): child_iterator_i
	{
		return new function_children_iterator($this);
	}
}

final class Fixture
{
	/** Return only a cursor, requiring it to retain the source owner. */
	public static function retained(): child_iterator_i
	{
		$source = new function_node();
		$parameters /** Storage<parameter_node> */ = $source->parameters;
		$first = new parameter_node();
		$first->value = 3;
		$hole = new parameter_node();
		$last = new parameter_node();
		$last->value = 7;
		$parameters->append($first);
		$parameters->append($hole);
		$parameters->append($last);
		$parameters->remove(1);
		return $source->children();
	}

	public static function display(child_iterator_i $cursor): void
	{
		foreach ($cursor as $key => $item) {
			echo $key, ':', $item->value, ';';
		}
		echo "\n";
	}
}
