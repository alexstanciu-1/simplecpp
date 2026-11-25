<?php
namespace scpp\compiler;
final class Nested_Access
{
    public static function exercise(Nested_Root $root): void
    {
        $row = new Nested_Row();
        $row->value = 7;
        $position = $root->rows->append($row);
        $root->names['01'] = $root->rows[$position];
        $root->names['01']->value = 9;
        $again /** Storage<Nested_Row> */ = $root->rows;
        $named_again /** Keyed_Storage<Nested_Row> */ = $root->names;
        echo $position, ':', $again[$position]->value, ':', $named_again['01'] === $row ? 'same' : 'bad', "\n";
        unset($root->rows[$position]);
        echo isset($again[$position]) ? 'bad' : 'removed', ':', $named_again['01']->value, "\n";
    }
}
Nested_Access::exercise(new Nested_Root());
