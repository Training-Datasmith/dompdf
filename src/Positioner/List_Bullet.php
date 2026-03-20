<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Positioner;

use Dompdf\Frame_Decorator\Abstract_Frame_Decorator;
use Dompdf\Frame_Decorator\List_Bullet as ListBulletFrameDecorator;
/**
 * Positions list bullets
 *
 * @package dompdf
 */
class List_Bullet extends Abstract_Positioner
{
    /**
     * @param ListBulletFrameDecorator $frame
     */
    public function position(Abstract_Frame_Decorator $frame): void
    {
        // List markers are positioned to the left of the border edge of their
        // parent element (FIXME: right for RTL)
        $parent = $frame->get_parent();
        $style = $parent->get_style();
        $cbw = $parent->get_containing_block('w');
        $margin_left = (float) $style->length_in_pt($style->margin_left, $cbw);
        $border_edge = $parent->get_position('x') + $margin_left;
        // This includes the marker indentation
        $x = $border_edge - $frame->get_margin_width();
        // The marker is later vertically aligned with the corresponding line
        // box and its vertical position is fine-tuned in the renderer
        $p = $frame->find_block_parent();
        $y = $p->get_current_line_box()->y;
        $frame->set_position($x, $y);
    }
}