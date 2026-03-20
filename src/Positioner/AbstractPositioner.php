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
 * Base AbstractPositioner class
 *
 * Defines positioner interface
 *
 * @package dompdf
 */
abstract class Abstract_Positioner
{
    abstract public function position(Abstract_Frame_Decorator $frame): void;
    public function move(Abstract_Frame_Decorator $frame, float $offset_x, float $offset_y, bool $ignore_self = false): void
    {
        [$x, $y] = $frame->get_position();
        if (!$ignore_self) {
            $frame->set_position($x + $offset_x, $y + $offset_y);
        }
        foreach ($frame->get_children() as $child) {
            $child->move($offset_x, $offset_y);
        }
    }
}