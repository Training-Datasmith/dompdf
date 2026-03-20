<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
use Dompdf\Frame_Decorator\List_Bullet as ListBulletFrameDecorator;
use Dompdf\Frame_Decorator\List_Bullet_Image;
use Dompdf\Helpers;
use Dompdf\Image\Cache;
/**
 * Renders list bullets
 *
 * @package dompdf
 */
class List_Bullet extends Abstract_Renderer
{
    /**
     * @param $type
     * @return mixed|string
     * @deprecated
     */
    public static function get_counter_chars($type)
    {
        static $cache = [];
        if (isset($cache[$type])) {
            return $cache[$type];
        }
        $uppercase = false;
        $text = '';
        switch ($type) {
            default:
            case 'decimal':
            case 'decimal-leading-zero':
                return '0123456789';
            case 'upper-alpha':
            case 'upper-latin':
                $uppercase = true;
            // no break
            case 'lower-alpha':
            case 'lower-latin':
                $text = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case 'upper-roman':
                $uppercase = true;
            // no break
            case 'lower-roman':
                $text = 'ivxlcdm';
                break;
            case 'lower-greek':
                for ($i = 0; $i < 24; $i++) {
                    $text .= Helpers::unichr($i + 944);
                }
                break;
        }
        if ($uppercase) {
            $text = strtoupper($text);
        }
        return $cache[$type] = "{$text}.";
    }
    private function make_counter(int $n, string $type, ?int $pad = null): string
    {
        $text = '';
        switch ($type) {
            default:
            case 'decimal':
            case 'decimal-leading-zero':
                if ($pad) {
                    $text = str_pad($n, $pad, '0', STR_PAD_LEFT);
                } else {
                    $text = $n;
                }
                break;
            case 'upper-alpha':
            case 'upper-latin':
                $text = strtoupper(Helpers::dec2base26($n));
                break;
            case 'lower-alpha':
            case 'lower-latin':
                $text = Helpers::dec2base26($n);
                break;
            case 'upper-roman':
                $text = strtoupper(Helpers::dec2roman($n));
                break;
            case 'lower-roman':
                $text = Helpers::dec2roman($n);
                break;
            case 'lower-greek':
                $text = Helpers::unichr($n + 944);
                break;
        }
        return "{$text}.";
    }
    /**
     * @param ListBulletFrameDecorator $frame
     */
    public function render(Frame $frame): void
    {
        $li = $frame->get_parent();
        $style = $frame->get_style();
        $this->_set_opacity($frame->get_opacity($style->opacity));
        // Don't render bullets twice if the list item was split
        if ($li->is_split_off) {
            return;
        }
        $font_family = $style->font_family;
        $font_size = $style->font_size;
        $baseline = $this->_canvas->get_font_baseline($font_family, $font_size);
        // Handle list-style-image
        // If list style image is requested but missing, fall back to predefined types
        if ($frame instanceof List_Bullet_Image && !Cache::is_broken($img = $frame->get_image_url())) {
            [$x, $y] = $frame->get_position();
            $w = $frame->get_width();
            $h = $frame->get_height();
            $y += $baseline - $h;
            $this->_canvas->image($img, $x, $y, $w, $h);
        } else {
            $bullet_style = $style->list_style_type;
            switch ($bullet_style) {
                case 'disc':
                case 'circle':
                    [$x, $y] = $frame->get_position();
                    $offset = $font_size * List_Bullet_Frame_Decorator::BULLET_OFFSET;
                    $r = $font_size * List_Bullet_Frame_Decorator::BULLET_SIZE / 2;
                    $x += $r;
                    $y += $baseline - $r - $offset;
                    $o = $font_size * List_Bullet_Frame_Decorator::BULLET_THICKNESS;
                    $this->_canvas->circle($x, $y, $r, $style->color, $o, null, $bullet_style !== 'circle');
                    break;
                case 'square':
                    [$x, $y] = $frame->get_position();
                    $offset = $font_size * List_Bullet_Frame_Decorator::BULLET_OFFSET;
                    $w = $font_size * List_Bullet_Frame_Decorator::BULLET_SIZE;
                    $y += $baseline - $w - $offset;
                    $this->_canvas->filled_rectangle($x, $y, $w, $w, $style->color);
                    break;
                default:
                case 'decimal':
                case 'decimal-leading-zero':
                case 'lower-alpha':
                case 'lower-latin':
                case 'lower-roman':
                case 'lower-greek':
                case 'upper-alpha':
                case 'upper-latin':
                case 'upper-roman':
                    $pad = null;
                    if ($bullet_style === 'decimal-leading-zero') {
                        $pad = strlen($li->get_parent()->get_node()->get_attribute('dompdf-children-count'));
                    }
                    $node = $frame->get_node();
                    if (!$node->has_attribute('dompdf-counter')) {
                        return;
                    }
                    $index = (int) $node->get_attribute('dompdf-counter');
                    $text = $this->make_counter($index, $bullet_style, $pad);
                    $word_spacing = $style->word_spacing;
                    $letter_spacing = $style->letter_spacing;
                    $text_width = $this->_dompdf->get_font_metrics()->get_text_width($text, $font_family, $font_size, $word_spacing, $letter_spacing);
                    [$x, $y] = $frame->get_position();
                    // Correct for static frame width applied by positioner
                    $x += $frame->get_width() - $text_width;
                    $this->_canvas->text($x, $y, $text, $font_family, $font_size, $style->color, $word_spacing, $letter_spacing);
                    break;
                case 'none':
                    break;
            }
        }
    }
}