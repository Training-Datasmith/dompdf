<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

/**
 * Embeds Javascript into the PDF document
 *
 * @package dompdf
 */
class Javascript_Embedder
{
    /**
     * @var Dompdf
     */
    protected $_dompdf;
    /**
     * JavascriptEmbedder constructor.
     */
    public function __construct(Dompdf $dompdf)
    {
        $this->_dompdf = $dompdf;
    }
    /**
     * @param $script
     */
    public function insert($script): void
    {
        $this->_dompdf->get_canvas()->javascript($script);
    }
    public function render(Frame $frame): void
    {
        if (!$this->_dompdf->get_options()->get_is_javascript_enabled()) {
            return;
        }
        $this->insert($frame->get_node()->node_value);
    }
}