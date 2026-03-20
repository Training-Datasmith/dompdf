<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
// FIXME: Need to sanity check inputs to this class
namespace Dompdf\Adapter;

use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\Exception;
use Dompdf\Font_Metrics;
use Dompdf\Helpers;
use Dompdf\Image\Cache;
use Font_Lib\Exception\Font_Not_Found_Exception;
/**
 * PDF rendering interface
 *
 * Dompdf\Adapter\CPDF provides a simple stateless interface to the stateful one
 * provided by the Cpdf class.
 *
 * Unless otherwise mentioned, all dimensions are in points (1/72 in).  The
 * coordinate origin is in the top left corner, and y values increase
 * downwards.
 *
 * See {@link http://www.ros.co.nz/pdf/} for more complete documentation
 * on the underlying {@link Cpdf} class.
 *
 * @package dompdf
 */
class CPDF implements Canvas
{
    /**
     * Dimensions of paper sizes in points
     *
     * @var array
     */
    public static $PAPER_SIZES = ['4a0' => [0.0, 0.0, 4767.87, 6740.79], '2a0' => [0.0, 0.0, 3370.39, 4767.87], 'a0' => [0.0, 0.0, 2383.94, 3370.39], 'a1' => [0.0, 0.0, 1683.78, 2383.94], 'a2' => [0.0, 0.0, 1190.55, 1683.78], 'a3' => [0.0, 0.0, 841.89, 1190.55], 'a4' => [0.0, 0.0, 595.28, 841.89], 'a5' => [0.0, 0.0, 419.53, 595.28], 'a6' => [0.0, 0.0, 297.64, 419.53], 'a7' => [0.0, 0.0, 209.76, 297.64], 'a8' => [0.0, 0.0, 147.4, 209.76], 'a9' => [0.0, 0.0, 104.88, 147.4], 'a10' => [0.0, 0.0, 73.7, 104.88], 'b0' => [0.0, 0.0, 2834.65, 4008.19], 'b1' => [0.0, 0.0, 2004.09, 2834.65], 'b2' => [0.0, 0.0, 1417.32, 2004.09], 'b3' => [0.0, 0.0, 1000.63, 1417.32], 'b4' => [0.0, 0.0, 708.66, 1000.63], 'b5' => [0.0, 0.0, 498.9, 708.66], 'b6' => [0.0, 0.0, 354.33, 498.9], 'b7' => [0.0, 0.0, 249.45, 354.33], 'b8' => [0.0, 0.0, 175.75, 249.45], 'b9' => [0.0, 0.0, 124.72, 175.75], 'b10' => [0.0, 0.0, 87.87, 124.72], 'c0' => [0.0, 0.0, 2599.37, 3676.54], 'c1' => [0.0, 0.0, 1836.85, 2599.37], 'c2' => [0.0, 0.0, 1298.27, 1836.85], 'c3' => [0.0, 0.0, 918.4299999999999, 1298.27], 'c4' => [0.0, 0.0, 649.13, 918.4299999999999], 'c5' => [0.0, 0.0, 459.21, 649.13], 'c6' => [0.0, 0.0, 323.15, 459.21], 'c7' => [0.0, 0.0, 229.61, 323.15], 'c8' => [0.0, 0.0, 161.57, 229.61], 'c9' => [0.0, 0.0, 113.39, 161.57], 'c10' => [0.0, 0.0, 79.37, 113.39], 'ra0' => [0.0, 0.0, 2437.8, 3458.27], 'ra1' => [0.0, 0.0, 1729.13, 2437.8], 'ra2' => [0.0, 0.0, 1218.9, 1729.13], 'ra3' => [0.0, 0.0, 864.5700000000001, 1218.9], 'ra4' => [0.0, 0.0, 609.45, 864.5700000000001], 'sra0' => [0.0, 0.0, 2551.18, 3628.35], 'sra1' => [0.0, 0.0, 1814.17, 2551.18], 'sra2' => [0.0, 0.0, 1275.59, 1814.17], 'sra3' => [0.0, 0.0, 907.09, 1275.59], 'sra4' => [0.0, 0.0, 637.8, 907.09], 'letter' => [0.0, 0.0, 612.0, 792.0], 'half-letter' => [0.0, 0.0, 396.0, 612.0], 'legal' => [0.0, 0.0, 612.0, 1008.0], 'ledger' => [0.0, 0.0, 1224.0, 792.0], 'tabloid' => [0.0, 0.0, 792.0, 1224.0], 'executive' => [0.0, 0.0, 521.86, 756.0], 'folio' => [0.0, 0.0, 612.0, 936.0], 'commercial #10 envelope' => [0.0, 0.0, 684.0, 297.0], 'catalog #10 1/2 envelope' => [0.0, 0.0, 648.0, 864.0], '8.5x11' => [0.0, 0.0, 612.0, 792.0], '8.5x14' => [0.0, 0.0, 612.0, 1008.0], '11x17' => [0.0, 0.0, 792.0, 1224.0]];
    /**
     * The Dompdf object
     *
     * @var Dompdf
     */
    protected $_dompdf;
    /**
     * Instance of Cpdf class
     *
     * @var \Dompdf\Cpdf
     */
    protected $_pdf;
    /**
     * PDF width, in points
     *
     * @var float
     */
    protected $_width;
    /**
     * PDF height, in points
     *
     * @var float
     */
    protected $_height;
    /**
     * Current page number
     *
     * @var int
     */
    protected $_page_number;
    /**
     * Total number of pages
     *
     * @var int
     */
    protected $_page_count;
    /**
     * Array of pages for accessing after rendering is initially complete
     *
     * @var array
     */
    protected $_pages;
    /**
     * Currently-applied opacity level (0 - 1)
     *
     * @var float
     */
    protected $_current_opacity = 1;
    public function __construct($paper = 'letter', string $orientation = 'portrait', ?Dompdf $dompdf = null)
    {
        if (is_array($paper)) {
            $size = array_map('floatval', $paper);
        } else {
            $paper = strtolower($paper);
            $size = self::$PAPER_SIZES[$paper] ?? self::$PAPER_SIZES['letter'];
        }
        if (strtolower($orientation) === 'landscape') {
            [$size[2], $size[3]] = [$size[3], $size[2]];
        }
        if ($dompdf === null) {
            $this->_dompdf = new Dompdf();
        } else {
            $this->_dompdf = $dompdf;
        }
        $this->_pdf = new \Dompdf\Cpdf($size, true, $this->_dompdf->get_options()->get_font_cache(), $this->_dompdf->get_options()->get_temp_dir());
        $this->_pdf->add_info('Producer', sprintf('%s + CPDF', $this->_dompdf->version));
        $time = substr_replace(date('YmdHisO'), '\'', -2, 0) . '\'';
        $this->_pdf->add_info('CreationDate', "D:{$time}");
        $this->_pdf->add_info('ModDate', "D:{$time}");
        if ($this->_dompdf->get_options()->is_pdf_a_enabled()) {
            $this->_pdf->enable_pdf_a_compliance();
        }
        $this->_width = $size[2] - $size[0];
        $this->_height = $size[3] - $size[1];
        $this->_page_number = $this->_page_count = 1;
        $this->_pages = [$this->_pdf->get_first_page_id()];
    }
    public function get_dompdf()
    {
        return $this->_dompdf;
    }
    /**
     * Returns the Cpdf instance
     *
     * @return \Dompdf\Cpdf
     */
    public function get_cpdf()
    {
        return $this->_pdf;
    }
    public function add_info(string $label, string $value): void
    {
        $this->_pdf->add_info($label, $value);
    }
    /**
     * Opens a new 'object'
     *
     * While an object is open, all drawing actions are recorded in the object,
     * as opposed to being drawn on the current page.  Objects can be added
     * later to a specific page or to several pages.
     *
     * The return value is an integer ID for the new object.
     *
     * @see CPDF::close_object()
     * @see CPDF::add_object()
     *
     * @return int
     */
    public function open_object()
    {
        $ret = $this->_pdf->open_object();
        $this->_pdf->save_state();
        return $ret;
    }
    /**
     * Reopens an existing 'object'
     *
     * @see CPDF::open_object()
     * @param int $object the ID of a previously opened object
     */
    public function reopen_object($object): void
    {
        $this->_pdf->reopen_object($object);
        $this->_pdf->save_state();
    }
    /**
     * Closes the current 'object'
     *
     * @see CPDF::open_object()
     */
    public function close_object(): void
    {
        $this->_pdf->restore_state();
        $this->_pdf->close_object();
    }
    /**
     * Adds a specified 'object' to the document
     *
     * $object int specifying an object created with {@link
     * CPDF::open_object()}.  $where can be one of:
     * - 'add' add to current page only
     * - 'all' add to every page from the current one onwards
     * - 'odd' add to all odd numbered pages from now on
     * - 'even' add to all even numbered pages from now on
     * - 'next' add the object to the next page only
     * - 'nextodd' add to all odd numbered pages from the next one
     * - 'nexteven' add to all even numbered pages from the next one
     *
     * @see Cpdf::addObject()
     *
     * @param int $object
     * @param string $where
     */
    public function add_object($object, $where = 'all'): void
    {
        $this->_pdf->add_object($object, $where);
    }
    /**
     * Stops the specified 'object' from appearing in the document.
     *
     * The object will stop being displayed on the page following the current
     * one.
     *
     * @param int $object
     */
    public function stop_object($object): void
    {
        $this->_pdf->stop_object($object);
    }
    /**
     * Serialize the pdf object's current state for retrieval later
     */
    public function serialize_object($id)
    {
        return $this->_pdf->serialize_object($id);
    }
    public function reopen_serialized_object($obj)
    {
        return $this->_pdf->restore_serialized_object($obj);
    }
    //........................................................................
    public function get_width()
    {
        return $this->_width;
    }
    public function get_height()
    {
        return $this->_height;
    }
    public function get_page_number()
    {
        return $this->_page_number;
    }
    public function get_page_count()
    {
        return $this->_page_count;
    }
    /**
     * Sets the current page number
     *
     * @param int $num
     */
    public function set_page_number($num): void
    {
        $this->_page_number = $num;
    }
    public function set_page_count($count): void
    {
        $this->_page_count = $count;
    }
    /**
     * Sets the stroke color
     *
     * See {@link Style::set_color()} for the format of the color array.
     */
    protected function _set_stroke_color(array $color)
    {
        $this->_pdf->set_stroke_color($color);
        $alpha = $color['alpha'] ?? 1;
        $alpha *= $this->_current_opacity;
        $this->_set_line_transparency('Normal', $alpha);
    }
    /**
     * Sets the fill colour
     *
     * See {@link Style::set_color()} for the format of the colour array.
     */
    protected function _set_fill_color(array $color)
    {
        $this->_pdf->set_color($color);
        $alpha = $color['alpha'] ?? 1;
        $alpha *= $this->_current_opacity;
        $this->_set_fill_transparency('Normal', $alpha);
    }
    /**
     * Sets line transparency
     * @see Cpdf::setLineTransparency()
     *
     * Valid blend modes are (case-sensitive):
     *
     * Normal, Multiply, Screen, Overlay, Darken, Lighten,
     * ColorDodge, ColorBurn, HardLight, SoftLight, Difference,
     * Exclusion
     *
     * @param string $mode    the blending mode to use
     * @param float  $opacity 0.0 fully transparent, 1.0 fully opaque
     */
    protected function _set_line_transparency(string $mode, float $opacity)
    {
        $this->_pdf->set_line_transparency($mode, $opacity);
    }
    /**
     * Sets fill transparency
     * @see Cpdf::setFillTransparency()
     *
     * Valid blend modes are (case-sensitive):
     *
     * Normal, Multiply, Screen, Overlay, Darken, Lighten,
     * ColorDogde, ColorBurn, HardLight, SoftLight, Difference,
     * Exclusion
     *
     * @param string $mode    the blending mode to use
     * @param float  $opacity 0.0 fully transparent, 1.0 fully opaque
     */
    protected function _set_fill_transparency(string $mode, float $opacity)
    {
        $this->_pdf->set_fill_transparency($mode, $opacity);
    }
    /**
     * Sets the line style
     *
     * @see Cpdf::setLineStyle()
     *
     * @param float  $width
     * @param string $cap
     * @param string $join
     * @param array  $dash
     */
    protected function _set_line_style($width, $cap, $join, $dash)
    {
        $this->_pdf->set_line_style($width, $cap, $join, $dash);
    }
    public function set_opacity(float $opacity, string $mode = 'Normal'): void
    {
        $this->_set_line_transparency($mode, $opacity);
        $this->_set_fill_transparency($mode, $opacity);
        $this->_current_opacity = $opacity;
    }
    public function set_default_view($view, $options = []): void
    {
        array_unshift($options, $view);
        call_user_func_array([$this->_pdf, 'openHere'], $options);
    }
    /**
     * Remaps y coords from 4th to 1st quadrant
     *
     * @param float $y
     * @return float
     */
    protected function y($y)
    {
        return $this->_height - $y;
    }
    public function line($x1, $y1, $x2, $y2, $color, $width, $style = [], $cap = 'butt'): void
    {
        $this->_set_stroke_color($color);
        $this->_set_line_style($width, $cap, '', $style);
        $this->_pdf->line($x1, $this->y($y1), $x2, $this->y($y2));
        $this->_set_line_transparency('Normal', $this->_current_opacity);
    }
    public function arc($x, $y, $r1, $r2, $astart, $aend, $color, $width, $style = [], $cap = 'butt'): void
    {
        $this->_set_stroke_color($color);
        $this->_set_line_style($width, $cap, '', $style);
        $this->_pdf->ellipse($x, $this->y($y), $r1, $r2, 0, 8, $astart, $aend, false, false, true, false);
        $this->_set_line_transparency('Normal', $this->_current_opacity);
    }
    public function rectangle($x1, $y1, $w, $h, $color, $width, $style = [], $cap = 'butt'): void
    {
        $this->_set_stroke_color($color);
        $this->_set_line_style($width, $cap, '', $style);
        $this->_pdf->rectangle($x1, $this->y($y1) - $h, $w, $h);
        $this->_set_line_transparency('Normal', $this->_current_opacity);
    }
    public function filled_rectangle($x1, $y1, $w, $h, $color): void
    {
        $this->_set_fill_color($color);
        $this->_pdf->filled_rectangle($x1, $this->y($y1) - $h, $w, $h);
        $this->_set_fill_transparency('Normal', $this->_current_opacity);
    }
    public function clipping_rectangle($x1, $y1, $w, $h): void
    {
        $this->_pdf->clipping_rectangle($x1, $this->y($y1) - $h, $w, $h);
    }
    public function clipping_roundrectangle($x1, $y1, $w, $h, $r_tl, $r_tr, $r_br, $r_bl): void
    {
        $this->_pdf->clipping_rectangle_rounded($x1, $this->y($y1) - $h, $w, $h, $r_tl, $r_tr, $r_br, $r_bl);
    }
    public function clipping_polygon(array $points): void
    {
        // Adjust y values
        for ($i = 1; $i < count($points); $i += 2) {
            $points[$i] = $this->y($points[$i]);
        }
        $this->_pdf->clipping_polygon($points);
    }
    public function clipping_end(): void
    {
        $this->_pdf->clipping_end();
    }
    public function save(): void
    {
        $this->_pdf->save_state();
    }
    public function restore(): void
    {
        $this->_pdf->restore_state();
    }
    public function rotate($angle, $x, $y): void
    {
        $this->_pdf->rotate($angle, $x, $y);
    }
    public function skew($angle_x, $angle_y, $x, $y): void
    {
        $this->_pdf->skew($angle_x, $angle_y, $x, $y);
    }
    public function scale($s_x, $s_y, $x, $y): void
    {
        $this->_pdf->scale($s_x, $s_y, $x, $y);
    }
    public function translate($t_x, $t_y): void
    {
        $this->_pdf->translate($t_x, $t_y);
    }
    public function transform($a, $b, $c, $d, $e, $f): void
    {
        $this->_pdf->transform([$a, $b, $c, $d, $e, $f]);
    }
    public function polygon($points, $color, $width = null, $style = [], $fill = false): void
    {
        $this->_set_fill_color($color);
        $this->_set_stroke_color($color);
        if (!$fill && isset($width)) {
            $this->_set_line_style($width, 'square', 'miter', $style);
        }
        // Adjust y values
        for ($i = 1; $i < count($points); $i += 2) {
            $points[$i] = $this->y($points[$i]);
        }
        $this->_pdf->polygon($points, $fill);
        $this->_set_fill_transparency('Normal', $this->_current_opacity);
        $this->_set_line_transparency('Normal', $this->_current_opacity);
    }
    public function circle($x, $y, $r, $color, $width = null, $style = [], $fill = false): void
    {
        $this->_set_fill_color($color);
        $this->_set_stroke_color($color);
        if (!$fill && isset($width)) {
            $this->_set_line_style($width, 'round', 'round', $style);
        }
        $this->_pdf->ellipse($x, $this->y($y), $r, 0, 0, 8, 0, 360, 1, $fill);
        $this->_set_fill_transparency('Normal', $this->_current_opacity);
        $this->_set_line_transparency('Normal', $this->_current_opacity);
    }
    /**
     * Convert image to a PNG image
     *
     * @param string $type
     * @return string|null The url of the newly converted image
     */
    protected function _convert_to_png(string $image_url, $type)
    {
        $filename = Cache::get_temp_image($image_url);
        if ($filename !== null && file_exists($filename)) {
            return $filename;
        }
        $func_name = "imagecreatefrom{$type}";
        set_error_handler([Helpers::class, 'record_warnings']);
        if (method_exists(Helpers::class, $func_name)) {
            $func_name = [Helpers::class, $func_name];
        } elseif (!function_exists($func_name)) {
            throw new Exception("Function {$func_name}() not found.  Cannot convert {$type} image: {$image_url}.  Please install the image PHP extension.");
        }
        try {
            $im = call_user_func($func_name, $image_url);
            if ($im) {
                imageinterlace($im, false);
                $tmp_dir = $this->_dompdf->get_options()->get_temp_dir();
                $tmp_name = @tempnam($tmp_dir, "{$type}_dompdf_img_");
                @unlink($tmp_name);
                $filename = "{$tmp_name}.png";
                imagepng($im, $filename);
                if (PHP_MAJOR_VERSION < 8) {
                    imagedestroy($im);
                }
            } else {
                $filename = null;
            }
        } finally {
            restore_error_handler();
        }
        if ($filename !== null) {
            Cache::add_temp_image($image_url, $filename);
        }
        return $filename;
    }
    public function image($img, $x, $y, $w, $h, $resolution = 'normal'): void
    {
        [$width, $height, $type] = Helpers::dompdf_getimagesize($img, $this->get_dompdf()->get_http_context());
        $debug_png = $this->_dompdf->get_options()->get_debug_png();
        if ($debug_png) {
            print "[image:{$img}|{$width}|{$height}|{$type}]";
        }
        switch ($type) {
            case 'jpeg':
                if ($debug_png) {
                    print '!!!jpg!!!';
                }
                $this->_pdf->add_jpeg_from_file($img, $x, $this->y($y) - $h, $w, $h);
                break;
            case 'webp':
            /** @noinspection PhpMissingBreakStatementInspection */
            // no break
            case 'gif':
            /** @noinspection PhpMissingBreakStatementInspection */
            // no break
            case 'bmp':
                if ($debug_png) {
                    print "!!!{$type}!!!";
                }
                $img = $this->_convert_to_png($img, $type);
                if ($img === null) {
                    if ($debug_png) {
                        print '!!!conversion to PDF failed!!!';
                    }
                    $this->image(Cache::$broken_image, $x, $y, $w, $h, $resolution);
                    break;
                }
            // no break
            case 'png':
                if ($debug_png) {
                    print '!!!png!!!';
                }
                $this->_pdf->add_png_from_file($img, $x, $this->y($y) - $h, $w, $h);
                break;
            case 'svg':
                if ($debug_png) {
                    print '!!!SVG!!!';
                }
                $this->_pdf->add_svg_from_file($img, $x, $this->y($y) - $h, $w, $h);
                break;
            default:
                if ($debug_png) {
                    print '!!!unknown!!!';
                }
        }
    }
    public function select($x, $y, $w, $h, $font, $size, $color = [0, 0, 0], $opts = []): void
    {
        $pdf = $this->_pdf;
        $pdf->select_font($font);
        if (!isset($pdf->acro_form_id)) {
            $pdf->add_form();
        }
        $ft = \Dompdf\Cpdf::ACROFORM_FIELD_CHOICE;
        $ff = \Dompdf\Cpdf::ACROFORM_FIELD_CHOICE_COMBO;
        $id = $pdf->add_form_field($ft, random_int(0, mt_getrandmax()), $x, $this->y($y) - $h, $x + $w, $this->y($y), $ff, $size, $color);
        $pdf->set_form_field_opt($id, $opts);
    }
    public function textarea($x, $y, $w, $h, $font, $size, $color = [0, 0, 0]): void
    {
        $pdf = $this->_pdf;
        $pdf->select_font($font);
        if (!isset($pdf->acro_form_id)) {
            $pdf->add_form();
        }
        $ft = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT;
        $ff = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT_MULTILINE;
        $pdf->add_form_field($ft, random_int(0, mt_getrandmax()), $x, $this->y($y) - $h, $x + $w, $this->y($y), $ff, $size, $color);
    }
    public function input($x, $y, $w, $h, $type, $font, $size, $color = [0, 0, 0]): void
    {
        $pdf = $this->_pdf;
        $pdf->select_font($font);
        if (!isset($pdf->acro_form_id)) {
            $pdf->add_form();
        }
        $ft = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT;
        $ff = 0;
        switch ($type) {
            case 'text':
                $ft = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT;
                break;
            case 'password':
                $ft = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT;
                $ff = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT_PASSWORD;
                break;
            case 'submit':
                $ft = \Dompdf\Cpdf::ACROFORM_FIELD_BUTTON;
                break;
        }
        $pdf->add_form_field($ft, random_int(0, mt_getrandmax()), $x, $this->y($y) - $h, $x + $w, $this->y($y), $ff, $size, $color);
    }
    public function text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0): void
    {
        $pdf = $this->_pdf;
        $this->_set_fill_color($color);
        $is_font_subsetting = $this->_dompdf->get_options()->get_is_font_subsetting_enabled();
        $pdf->select_font($font, '', true, $is_font_subsetting);
        $pdf->add_text($x, $this->y($y) - $pdf->get_font_height($size), $size, $text, $angle, $word_space, $char_space);
        $this->_set_fill_transparency('Normal', $this->_current_opacity);
    }
    public function javascript($code): void
    {
        $this->_pdf->add_javascript($code);
    }
    //........................................................................
    public function add_named_dest($anchorname): void
    {
        $this->_pdf->add_destination($anchorname, 'Fit');
    }
    public function add_link($url, $x, $y, $width, $height): void
    {
        $y = $this->y($y) - $height;
        if (strpos($url, '#') === 0) {
            // Local link
            $name = substr($url, 1);
            if ($name) {
                $this->_pdf->add_internal_link($name, $x, $y, $x + $width, $y + $height);
            }
        } else {
            $this->_pdf->add_link($url, $x, $y, $x + $width, $y + $height);
        }
    }
    public function font_supports_char(string $font, string $char): bool
    {
        if ($char === '') {
            return true;
        }
        $subsetting = $this->_dompdf->get_options()->get_is_font_subsetting_enabled();
        $this->_pdf->select_font($font, '', false, $subsetting);
        if (!\array_key_exists($font, $this->_pdf->fonts)) {
            return false;
        }
        $font_info = $this->_pdf->fonts[$font];
        $char_code = Helpers::uniord($char, 'UTF-8');
        if (!$font_info['isUnicode']) {
            // The core fonts use Windows ANSI encoding. The char map uses the
            // position of the character in the encoding's mapping table in this
            // case, not the Unicode code point, which is different for the
            // characters outside ISO-8859-1 (positions 0x80-0x9F)
            // https://www.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/WINDOWS/CP1252.TXT
            $mapping = [0x20ac => 0x80, 0x201a => 0x82, 0x192 => 0x83, 0x201e => 0x84, 0x2026 => 0x85, 0x2020 => 0x86, 0x2021 => 0x87, 0x2c6 => 0x88, 0x2030 => 0x89, 0x160 => 0x8a, 0x2039 => 0x8b, 0x152 => 0x8c, 0x17d => 0x8e, 0x2018 => 0x91, 0x2019 => 0x92, 0x201c => 0x93, 0x201d => 0x94, 0x2022 => 0x95, 0x2013 => 0x96, 0x2014 => 0x97, 0x2dc => 0x98, 0x2122 => 0x99, 0x161 => 0x9a, 0x203a => 0x9b, 0x153 => 0x9c, 0x17e => 0x9e, 0x178 => 0x9f];
            $char_code = $mapping[$char_code] ?? $char_code;
            if ($char_code > 0xff) {
                return false;
            }
        }
        return \array_key_exists($char_code, $font_info['C']);
    }
    /**
     * @throws FontNotFoundException
     */
    public function get_text_width($text, $font, $size, $word_spacing = 0.0, $char_spacing = 0.0): float
    {
        $this->_pdf->select_font($font, '', true, $this->_dompdf->get_options()->get_is_font_subsetting_enabled());
        return $this->_pdf->get_text_width($size, $text, $word_spacing, $char_spacing);
    }
    /**
     * @throws FontNotFoundException
     */
    public function get_font_height($font, $size)
    {
        $options = $this->_dompdf->get_options();
        $this->_pdf->select_font($font, '', true, $options->get_is_font_subsetting_enabled());
        return $this->_pdf->get_font_height($size) * $options->get_font_height_ratio();
    }
    /*function get_font_x_height($font, $size) {
        $this->_pdf->selectFont($font);
        $ratio = $this->_dompdf->getOptions()->getFontHeightRatio();
        return $this->_pdf->getFontXHeight($size) * $ratio;
      }*/
    /**
     * @throws FontNotFoundException
     */
    public function get_font_baseline($font, $size)
    {
        $ratio = $this->_dompdf->get_options()->get_font_height_ratio();
        return $this->get_font_height($font, $size) / $ratio;
    }
    /**
     * Processes a callback or script on every page.
     *
     * The callback function receives the four parameters `int $pageNumber`,
     * `int $pageCount`, `Canvas $canvas`, and `FontMetrics $fontMetrics`, in
     * that order. If a script is passed as string, the variables `$PAGE_NUM`,
     * `$PAGE_COUNT`, `$pdf`, and `$fontMetrics` are available instead. Passing
     * a script as string is deprecated and will be removed in a future version.
     *
     * This function can be used to add page numbers to all pages after the
     * first one, for example.
     *
     * @param callable|string $callback The callback function or PHP script to process on every page
     */
    public function page_script($callback): void
    {
        if (is_string($callback)) {
            $this->process_page_script(function (int $PAGE_NUM, int $PAGE_COUNT, self $pdf, Font_Metrics $font_metrics) use ($callback): void {
                eval($callback);
            });
            return;
        }
        $this->process_page_script($callback);
    }
    public function page_text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0): void
    {
        $this->process_page_script(function (int $page_number, int $page_count) use ($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle): void {
            $text = str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], [$page_number, $page_count], $text);
            $this->text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        });
    }
    public function page_line($x1, $y1, $x2, $y2, $color, $width, $style = []): void
    {
        $this->process_page_script(function () use ($x1, $y1, $x2, $y2, $color, $width, $style): void {
            $this->line($x1, $y1, $x2, $y2, $color, $width, $style);
        });
    }
    /**
     * @return int
     */
    public function new_page()
    {
        $this->_page_number++;
        $this->_page_count++;
        $ret = $this->_pdf->new_page();
        $this->_pages[] = $ret;
        return $ret;
    }
    protected function process_page_script(callable $callback): void
    {
        $page_number = 1;
        foreach ($this->_pages as $pid) {
            $this->reopen_object($pid);
            $font_metrics = $this->_dompdf->get_font_metrics();
            $callback($page_number, $this->_page_count, $this, $font_metrics);
            $this->close_object();
            $page_number++;
        }
    }
    public function stream($filename = 'document.pdf', $options = []): void
    {
        if (headers_sent()) {
            die('Unable to stream pdf: headers already sent');
        }
        if (!isset($options['compress'])) {
            $options['compress'] = true;
        }
        if (!isset($options['Attachment'])) {
            $options['Attachment'] = true;
        }
        $debug = !$options['compress'];
        $tmp = ltrim($this->_pdf->output($debug));
        header('Content-Type: application/pdf');
        header('Content-Length: ' . mb_strlen($tmp, '8bit'));
        $filename = str_replace(["\n", "'"], '', basename($filename, '.pdf')) . '.pdf';
        $attachment = $options['Attachment'] ? 'attachment' : 'inline';
        header(Helpers::build_content_disposition_header($attachment, $filename));
        echo $tmp;
        flush();
    }
    public function output($options = [])
    {
        if (!isset($options['compress'])) {
            $options['compress'] = true;
        }
        $debug = !$options['compress'];
        return $this->_pdf->output($debug);
    }
    /**
     * Returns logging messages generated by the Cpdf class
     *
     * @return string
     */
    public function get_messages()
    {
        return $this->_pdf->messages;
    }
}