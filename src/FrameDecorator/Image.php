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
use Dompdf\Helpers;
use Dompdf\Image\Cache;
/**
 * Decorates frames for image layout and rendering
 *
 * @package dompdf
 */
class Image extends Abstract_Frame_Decorator
{
    /**
     * The path to the image file (note that remote images are
     * downloaded locally to Options:tempDir).
     *
     * @var string
     */
    protected $_image_url;
    /**
     * The image's file error message
     *
     * @var string
     */
    protected $_image_msg;
    /**
     * Class constructor
     *
     * @param Frame $frame the frame to decorate
     * @param DOMPDF $dompdf the document's dompdf object (required to resolve relative & remote urls)
     */
    public function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $node = $frame->get_node();
        $url = $node->get_attribute('src');
        $debug_png = $dompdf->get_options()->get_debug_png();
        if ($debug_png) {
            print '[__construct ' . $url . ']';
        }
        list($this->_image_url, , $this->_image_msg) = Cache::resolve_url($url, $dompdf->get_protocol(), $dompdf->get_base_host(), $dompdf->get_base_path(), $dompdf->get_options());
        if (Cache::is_broken($this->_image_url) && ($alt = $node->get_attribute('alt')) !== '') {
            $font_metrics = $dompdf->get_font_metrics();
            $style = $frame->get_style();
            $font = $style->font_family;
            $size = $style->font_size;
            $word_spacing = $style->word_spacing;
            $letter_spacing = $style->letter_spacing;
            $style->width = $font_metrics->get_text_width($alt, $font, $size, $word_spacing, $letter_spacing);
            $style->height = $font_metrics->get_font_height($font, $size);
        }
    }
    /**
     * Get the intrinsic pixel dimensions of the image.
     *
     * @return array Width and height as `float|int`.
     */
    public function get_intrinsic_dimensions(): array
    {
        [$width, $height] = Helpers::dompdf_getimagesize($this->_image_url, $this->_dompdf->get_http_context());
        return [$width, $height];
    }
    /**
     * Resample the given pixel length according to dpi.
     *
     * @param float|int $length
     */
    public function resample($length): float
    {
        $dpi = $this->_dompdf->get_options()->get_dpi();
        return $length * 72 / $dpi;
    }
    /**
     * Return the image's url
     *
     * @return string The url of this image
     */
    public function get_image_url()
    {
        return $this->_image_url;
    }
    /**
     * Return the image's error message
     *
     * @return string The image's error message
     */
    public function get_image_msg()
    {
        return $this->_image_msg;
    }
}