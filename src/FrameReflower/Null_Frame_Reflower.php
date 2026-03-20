<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Frame_Reflower;

use Dompdf\Frame;
use Dompdf\Frame_Decorator\Block as BlockFrameDecorator;
/**
 * Dummy reflower
 *
 * @package dompdf
 */
class Null_Frame_Reflower extends Abstract_Frame_Reflower
{
    /**
     * NullFrameReflower constructor.
     */
    public function __construct(Frame $frame)
    {
        parent::__construct($frame);
    }
    public function reflow(?Block_Frame_Decorator $block = null)
    {
    }
}