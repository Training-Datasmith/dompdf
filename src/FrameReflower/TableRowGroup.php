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
use Dompdf\Frame_Decorator\Table_Row_Group as TableRowGroupFrameDecorator;
/**
 * Reflows table row groups (e.g. tbody tags)
 *
 * @package dompdf
 */
class Table_Row_Group extends Abstract_Frame_Reflower
{
    /**
     * TableRowGroup constructor.
     */
    public function __construct(Table_Row_Group_Frame_Decorator $frame)
    {
        parent::__construct($frame);
    }
    public function reflow(?Block_Frame_Decorator $block = null): void
    {
        /** @var TableRowGroupFrameDecorator */
        $frame = $this->_frame;
        $page = $frame->get_root();
        $parent = $frame->get_parent();
        $dompdf_generated = $parent->get_frame()->get_node()->node_name === 'dompdf_generated';
        // Counters and generated content
        $this->_set_content();
        $style = $frame->get_style();
        $cb = $frame->get_containing_block();
        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($cb['x'], $cb['y'], $cb['w'], $cb['h']);
            $child->reflow();
            // Check if a split has occurred
            $page->check_page_break($child);
            if ($page->is_full()) {
                break;
            }
        }
        if ($page->is_full() && $dompdf_generated && $frame->get_parent() === null) {
            return;
        }
        $table = Table_Frame_Decorator::find_parent_table($frame);
        if ($table === null) {
            throw new Exception('Parent table not found for table row group');
        }
        $cellmap = $table->get_cellmap();
        // Stop reflow if a page break has occurred before the frame, in which
        // case it is not part of its parent table's cell map yet
        if ($page->is_full() && !$cellmap->frame_exists_in_cellmap($frame)) {
            return;
        }
        $style->set_used('width', $cellmap->get_frame_width($frame));
        $style->set_used('height', $cellmap->get_frame_height($frame));
        $frame->set_position($cellmap->get_frame_position($frame));
    }
}