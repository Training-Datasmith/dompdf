<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
use Dompdf\Frame_Decorator\Block as BlockFrameDecorator;
/**
 * Renders block frames
 *
 * @package dompdf
 */
class Block extends Abstract_Renderer
{
    public function render(Frame $frame): void
    {
        $style = $frame->get_style();
        $node = $frame->get_node();
        $this->_set_opacity($frame->get_opacity($style->opacity));
        [$x, $y, $w, $h] = $frame->get_border_box();
        if ($node->node_name === 'body') {
            // Margins should be fully resolved at this point
            $mt = $style->margin_top;
            $mb = $style->margin_bottom;
            $h = $frame->get_containing_block('h') - $mt - $mb;
        }
        $border_box = [$x, $y, $w, $h];
        // Draw our background, border and content
        $this->_render_background($frame, $border_box);
        $this->_render_border($frame, $border_box);
        $this->_render_outline($frame, $border_box);
        $this->add_named_dest($node);
        $this->add_hyperlink($node, $border_box);
        $this->debug_block_layout($frame, 'red', false);
    }
    /**
     * @param array|string $color
     */
    protected function debug_block_layout(Frame $frame, $color, bool $lines = false): void
    {
        $options = $this->_dompdf->get_options();
        $debug_layout = $options->get_debug_layout();
        if (!$debug_layout) {
            return;
        }
        if ($options->get_debug_layout_blocks()) {
            $this->debug_layout($frame->get_border_box(), $color);
            if ($options->get_debug_layout_padding_box()) {
                $this->debug_layout($frame->get_padding_box(), $color, [0.5, 0.5]);
            }
        }
        if ($lines && $options->get_debug_layout_lines() && $frame instanceof Block_Frame_Decorator) {
            [$cx, , $cw] = $frame->get_content_box();
            foreach ($frame->get_line_boxes() as $line) {
                $lw = $cw - $line->left - $line->right;
                $this->debug_layout([$cx + $line->left, $line->y, $lw, $line->h], 'orange');
            }
        }
    }
}