<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

/**
 * Create canvas instances
 *
 * The canvas factory creates canvas instances based on the
 * availability of rendering backends and config options.
 *
 * @package dompdf
 */
class Canvas_Factory
{
    /**
     * Constructor is private: this is a static class
     */
    private function __construct()
    {
    }
    /**
     * @param string|float[] $paper
     *
     * @return Canvas
     */
    public static function get_instance(Dompdf $dompdf, $paper, string $orientation, ?string $class = null): object
    {
        $backend = strtolower($dompdf->get_options()->get_pdf_backend());
        if (isset($class) && class_exists($class, false)) {
            $class .= '_Adapter';
        } else if (($backend === 'auto' || $backend === 'pdflib') && class_exists('PDFLib', false)) {
            $class = \Dompdf\Adapter\Pdf_Lib::class;
        } else if (class_exists($backend, false)) {
            $class = $backend;
        } elseif ($backend === 'gd' && extension_loaded('gd')) {
            $class = \Dompdf\Adapter\GD::class;
        } else {
            $class = \Dompdf\Adapter\CPDF::class;
        }
        $instance = new $class($paper, $orientation, $dompdf);
        $class_interfaces = class_implements($class, false);
        if (!$class_interfaces || !in_array(\Dompdf\Canvas::class, $class_interfaces)) {
            $class = \Dompdf\Adapter\CPDF::class;
            $instance = new $class($paper, $orientation, $dompdf);
        }
        return $instance;
    }
}