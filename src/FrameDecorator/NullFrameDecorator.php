<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Frame_Decorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
/**
 * Dummy decorator
 *
 * @package dompdf
 */
class Null_Frame_Decorator extends Abstract_Frame_Decorator
{
    /**
     * NullFrameDecorator constructor.
     */
    public function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $style = $this->_frame->get_style();
        $style->width = 0;
        $style->height = 0;
        $style->margin = 0;
        $style->padding = 0;
    }
}