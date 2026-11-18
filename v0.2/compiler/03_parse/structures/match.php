<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One owned match arm, e.g. Kind::Word => classify(); a null label denotes default. */
final class ast_match_arm extends ast_node
{
	public readonly ?ast_expression $label;
	public readonly ast_expression $result;

	public function __construct(?ast_expression $label, ast_expression $result,
		int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
		$this->label = $label;
		$this->result = $result;
		$label?->attach($this);
		$result->attach($this);
	}
}

/** One lazy selection; the syntax inventory owns arms, with no retained label index or prepared tree. */
final class ast_match_expression extends ast_expression
{
	public readonly ast_expression $subject;
	/** @var ast_match_arm[] Owning arms in written order, including erroneous duplicate labels/defaults. */
	public readonly array $arms /** list<ast_match_arm> */;

	/** @param ast_match_arm[] $arms */
	public function __construct(ast_expression $subject, array $arms /** list<ast_match_arm> */, int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
		$this->subject = $subject;
		$this->arms = $arms;
		$subject->attach($this);
		foreach ($arms as $arm) $arm->attach($this);
	}

	public function settle_match(type_definition $type): void
	{
		$this->settle_value($type, null);
	}
}
