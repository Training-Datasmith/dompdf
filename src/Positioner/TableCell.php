<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Positioner;

use Dompdf\Exception;
use Dompdf\Frame_Decorator\Abstract_Frame_Decorator;
use Dompdf\Frame_Decorator\Table;
/**
 * Positions table cells
 *
 * @package dompdf
 */
class Table_Cell extends Abstract_Positioner
{
    public function position(Abstract_Frame_Decorator $frame): void
    {
        $table = Table::find_parent_table($frame);
        if ($table === null) {
            throw new Exception('Parent table not found for table cell');
        }
        $cellmap = $table->get_cellmap();
        $frame->set_position($cellmap->get_frame_position($frame));
    }
}