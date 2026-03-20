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
 * Dummy positioner
 *
 * @package dompdf
 */
class Null_Positioner extends Abstract_Positioner
{
    public function position(Abstract_Frame_Decorator $frame): void
    {
    }
}