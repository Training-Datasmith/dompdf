<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Positioner;

use Dompdf\Frame_Decorator\Abstract_Frame_Decorator;
/**
 * Positions table rows
 *
 * @package dompdf
 */
class Table_Row extends Abstract_Positioner
{
    public function position(Abstract_Frame_Decorator $frame): void
    {
        $cb = $frame->get_containing_block();
        $p = $frame->get_prev_sibling();
        if ($p) {
            $y = $p->get_position('y') + $p->get_margin_height();
        } else {
            $y = $cb['y'];
        }
        $frame->set_position($cb['x'], $y);
    }
}