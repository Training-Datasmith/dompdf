<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

use Dompdf\Renderer\Abstract_Renderer;
use Dompdf\Renderer\Block;
use Dompdf\Renderer\Image;
use Dompdf\Renderer\Inline;
use Dompdf\Renderer\List_Bullet;
use Dompdf\Renderer\Table_Cell;
use Dompdf\Renderer\Table_Row;
use Dompdf\Renderer\Table_Row_Group;
use Dompdf\Renderer\Text;
/**
 * Concrete renderer
 *
 * Instantiates several specific renderers in order to render any given frame.
 *
 * @package dompdf
 */
class Renderer extends Abstract_Renderer
{
    /**
     * Array of renderers for specific frame types
     *
     * @var AbstractRenderer[]
     */
    protected $_renderers;
    /**
     * Cache of the callbacks array
     *
     * @var array
     */
    private $_callbacks;
    /**
     * Advance the canvas to the next page
     */
    public function new_page(): void
    {
        $this->_canvas->new_page();
    }
    /**
     * Render frames recursively
     *
     * @param Frame $frame the frame to render
     */
    public function render(Frame $frame): void
    {
        global $_dompdf_debug;
        $this->_check_callbacks('begin_frame', $frame);
        if ($_dompdf_debug) {
            echo $frame;
            flush();
        }
        $style = $frame->get_style();
        if (in_array($style->visibility, ['hidden', 'collapse'], true)) {
            return;
        }
        $display = $style->display;
        $transform_list = $style->transform;
        $has_transform = $transform_list !== [];
        // Starts the CSS transformation
        if ($has_transform) {
            $this->_canvas->save();
            [$x, $y] = $frame->get_padding_box();
            [$origin_x, $origin_y] = $style->transform_origin;
            $w = (float) $style->length_in_pt($style->width);
            $h = (float) $style->length_in_pt($style->height);
            foreach ($transform_list as $transform) {
                [$function, $values] = $transform;
                if ($function === 'matrix') {
                    $function = 'transform';
                } elseif ($function === 'translate') {
                    $values[0] = $style->length_in_pt($values[0], $w);
                    $values[1] = $style->length_in_pt($values[1], $h);
                }
                $values[] = $x + $style->length_in_pt($origin_x, $w);
                $values[] = $y + $style->length_in_pt($origin_y, $h);
                call_user_func_array([$this->_canvas, $function], $values);
            }
        }
        switch ($display) {
            case 'block':
            case 'list-item':
            case 'inline-block':
            case 'table':
            case 'inline-table':
                $this->_render_frame('block', $frame);
                break;
            case 'inline':
                if ($frame->is_text_node()) {
                    $this->_render_frame('text', $frame);
                } else {
                    $this->_render_frame('inline', $frame);
                }
                break;
            case 'table-cell':
                $this->_render_frame('table-cell', $frame);
                break;
            case 'table-row':
                $this->_render_frame('table-row', $frame);
                break;
            case 'table-row-group':
            case 'table-header-group':
            case 'table-footer-group':
                $this->_render_frame('table-row-group', $frame);
                break;
            case '-dompdf-list-bullet':
                $this->_render_frame('list-bullet', $frame);
                break;
            case '-dompdf-image':
                $this->_render_frame('image', $frame);
                break;
            case 'none':
                $node = $frame->get_node();
                if ($node->node_name === 'script') {
                    if ($node->get_attribute('type') === 'text/php' || $node->get_attribute('language') === 'php') {
                        // Evaluate embedded php scripts
                        $this->_render_frame('php', $frame);
                    } elseif ($node->get_attribute('type') === 'text/javascript' || $node->get_attribute('language') === 'javascript') {
                        // Insert JavaScript
                        $this->_render_frame('javascript', $frame);
                    }
                }
                // Don't render children, so skip to next iter
                return;
            default:
                break;
        }
        // Starts the overflow: hidden box
        if ($style->overflow === 'hidden') {
            $padding_box = $frame->get_padding_box();
            [$x, $y, $w, $h] = $padding_box;
            $style = $frame->get_style();
            if ($style->has_border_radius()) {
                $border_box = $frame->get_border_box();
                [$tl, $tr, $br, $bl] = $style->resolve_border_radius($border_box, $padding_box);
                $this->_canvas->clipping_roundrectangle($x, $y, $w, $h, $tl, $tr, $br, $bl);
            } else {
                $this->_canvas->clipping_rectangle($x, $y, $w, $h);
            }
        }
        $stack = [];
        foreach ($frame->get_children() as $child) {
            // < 0 : negative z-index
            // = 0 : no z-index, no stacking context
            // = 1 : stacking context without z-index
            // > 1 : z-index
            $child_style = $child->get_style();
            $child_z_index = $child_style->z_index;
            $z_index = 0;
            if ($child_z_index !== 'auto') {
                $z_index = $child_z_index + 1;
            } elseif ($child_style->float !== 'none' || $child->is_positioned()) {
                $z_index = 1;
            }
            $stack[$z_index][] = $child;
        }
        ksort($stack);
        foreach ($stack as $by_index) {
            foreach ($by_index as $child) {
                $this->render($child);
            }
        }
        // Ends the overflow: hidden box
        if ($style->overflow === 'hidden') {
            $this->_canvas->clipping_end();
        }
        if ($has_transform) {
            $this->_canvas->restore();
        }
        // Check for end frame callback
        $this->_check_callbacks('end_frame', $frame);
    }
    /**
     * Check for callbacks that need to be performed when a given event
     * gets triggered on a frame
     *
     * @param string $event The type of event
     * @param Frame  $frame The frame that event is triggered on
     */
    protected function _check_callbacks(string $event, Frame $frame): void
    {
        if (!isset($this->_callbacks)) {
            $this->_callbacks = $this->_dompdf->get_callbacks();
        }
        if (isset($this->_callbacks[$event])) {
            $fs = $this->_callbacks[$event];
            $canvas = $this->_canvas;
            $font_metrics = $this->_dompdf->get_font_metrics();
            foreach ($fs as $f) {
                $f($frame, $canvas, $font_metrics);
            }
        }
    }
    /**
     * Render a single frame
     *
     * Creates Renderer objects on demand
     *
     * @param string $type type of renderer to use
     * @param Frame $frame the frame to render
     */
    protected function _render_frame($type, \Dompdf\Frame $frame)
    {
        if (!isset($this->_renderers[$type])) {
            switch ($type) {
                case 'block':
                    $this->_renderers[$type] = new Block($this->_dompdf);
                    break;
                case 'inline':
                    $this->_renderers[$type] = new Inline($this->_dompdf);
                    break;
                case 'text':
                    $this->_renderers[$type] = new Text($this->_dompdf);
                    break;
                case 'image':
                    $this->_renderers[$type] = new Image($this->_dompdf);
                    break;
                case 'table-cell':
                    $this->_renderers[$type] = new Table_Cell($this->_dompdf);
                    break;
                case 'table-row':
                    $this->_renderers[$type] = new Table_Row($this->_dompdf);
                    break;
                case 'table-row-group':
                    $this->_renderers[$type] = new Table_Row_Group($this->_dompdf);
                    break;
                case 'list-bullet':
                    $this->_renderers[$type] = new List_Bullet($this->_dompdf);
                    break;
                case 'php':
                    $this->_renderers[$type] = new Php_Evaluator($this->_canvas);
                    break;
                case 'javascript':
                    $this->_renderers[$type] = new Javascript_Embedder($this->_dompdf);
                    break;
            }
        }
        $this->_renderers[$type]->render($frame);
    }
}