<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
/**
 * @package dompdf
 */
class Table_Row extends Block
{
    public function render(Frame $frame): void
    {
        $style = $frame->get_style();
        $node = $frame->get_node();
        $this->_set_opacity($frame->get_opacity($style->opacity));
        $border_box = $frame->get_border_box();
        // FIXME: Render background onto the area consisting of all spanned
        // cells. In the separated border model, the border-spacing area should
        // be left out. Currently, the background is inherited by the table
        // cells instead, which does not handle transparent backgrounds and
        // background images correctly.
        // See https://www.w3.org/TR/CSS21/tables.html#table-layers
        $this->_render_outline($frame, $border_box);
        $this->add_named_dest($node);
        $this->add_hyperlink($node, $border_box);
    }
}