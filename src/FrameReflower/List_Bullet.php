<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Frame_Reflower;

use Dompdf\Frame_Decorator\Block as BlockFrameDecorator;
use Dompdf\Frame_Decorator\List_Bullet as ListBulletFrameDecorator;
/**
 * Reflows list bullets
 *
 * @package dompdf
 */
class List_Bullet extends Abstract_Frame_Reflower
{
    /**
     * ListBullet constructor.
     */
    public function __construct(List_Bullet_Frame_Decorator $frame)
    {
        parent::__construct($frame);
    }
    public function reflow(?Block_Frame_Decorator $block = null): void
    {
        if ($block === null) {
            return;
        }
        /** @var ListBulletFrameDecorator */
        $frame = $this->_frame;
        $style = $frame->get_style();
        $style->set_used('width', $frame->get_width());
        $frame->position();
        if ($style->list_style_position === 'inside') {
            $block->add_frame_to_line($frame);
        } else {
            $block->add_dangling_marker($frame);
        }
    }
}