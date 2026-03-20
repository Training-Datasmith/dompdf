<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Frame_Reflower;

use Dompdf\Exception;
use Dompdf\Frame_Decorator\Block as BlockFrameDecorator;
use Dompdf\Frame_Decorator\Table as TableFrameDecorator;
use Dompdf\Frame_Decorator\Table_Row as TableRowFrameDecorator;
/**
 * Reflows table rows
 *
 * @package dompdf
 */
class Table_Row extends Abstract_Frame_Reflower
{
    /**
     * TableRow constructor.
     */
    public function __construct(Table_Row_Frame_Decorator $frame)
    {
        parent::__construct($frame);
    }
    public function reflow(?Block_Frame_Decorator $block = null): void
    {
        /** @var TableRowFrameDecorator */
        $frame = $this->_frame;
        // Check if a page break is forced
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);
        // Bail if the page is full
        if ($page->is_full()) {
            return;
        }
        // Counters and generated content
        $this->_set_content();
        $frame->position();
        $style = $frame->get_style();
        $cb = $frame->get_containing_block();
        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($cb);
            $child->reflow();
            if ($page->is_full()) {
                break;
            }
        }
        if ($page->is_full()) {
            return;
        }
        $table = Table_Frame_Decorator::find_parent_table($frame);
        if ($table === null) {
            throw new Exception('Parent table not found for table row');
        }
        $cellmap = $table->get_cellmap();
        $style->set_used('width', $cellmap->get_frame_width($frame));
        $style->set_used('height', $cellmap->get_frame_height($frame));
        $frame->set_position($cellmap->get_frame_position($frame));
    }
    /**
     * @throws Exception
     */
    public function get_min_max_width(): array
    {
        throw new Exception('Min/max width is undefined for table rows');
    }
}