<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Frame_Decorator;

use Dompdf\Dompdf;
use Dompdf\Exception;
use Dompdf\Frame;
/**
 * Decorates Frame objects for text layout
 *
 * @package dompdf
 */
class Text extends Abstract_Frame_Decorator
{
    /**
     * @var float
     */
    protected $text_spacing;
    /**
     * @var string|null
     */
    protected $mapped_font;
    /**
     * Saves trailing whitespace trimmed after a line break, so it can be
     * restored when needed.
     *
     * @var string|null
     */
    protected $trailing_ws;
    /**
     * Text constructor.
     * @throws Exception
     */
    public function __construct(Frame $frame, Dompdf $dompdf)
    {
        if (!$frame->is_text_node()) {
            throw new Exception('Text_Decorator can only be applied to #text nodes.');
        }
        parent::__construct($frame, $dompdf);
        $this->text_spacing = 0.0;
    }
    /**
     * Trim trailing white space from the frame text.
     */
    public function trim_trailing_ws(): void
    {
        $text = $this->get_text();
        $trailing = mb_substr($text, -1, null, 'UTF-8');
        // White space is always collapsed to the standard space character
        // currently, so only handle that for now
        if ($trailing === ' ') {
            $this->trailing_ws = $trailing;
            $this->set_text(mb_substr($text, 0, -1, 'UTF-8'));
            $this->recalculate_width();
        }
    }
    public function reset(): void
    {
        parent::reset();
        $this->text_spacing = 0.0;
        $this->mapped_font = null;
        // Restore trimmed trailing white space, as the frame will go through
        // another reflow and line breaks might be different after a split
        if ($this->trailing_ws !== null) {
            $text = $this->get_text();
            $this->set_text($text . $this->trailing_ws);
            $this->trailing_ws = null;
        }
    }
    // Accessor methods
    public function get_text_spacing(): float
    {
        return $this->text_spacing;
    }
    /**
     * @return string
     */
    public function get_text()
    {
        // FIXME: this should be in a child class (and is incorrect)
        //    if ( $this->_frame->get_style()->content !== "normal" ) {
        //      $this->_frame->get_node()->data = $this->_frame->get_style()->content;
        //      $this->_frame->get_style()->content = "normal";
        //    }
        //      Helpers::pre_r("---");
        //      $style = $this->_frame->get_style();
        //      var_dump($text = $this->_frame->get_node()->data);
        //      var_dump($asc = utf8_decode($text));
        //      for ($i = 0; $i < strlen($asc); $i++)
        //        Helpers::pre_r("$i: " . $asc[$i] . " - " . ord($asc[$i]));
        //      Helpers::pre_r("width: " . $this->_dompdf->getFontMetrics()->getTextWidth($text, $style->font_family, $style->font_size));
        return $this->_frame->get_node()->data;
    }
    //........................................................................
    /**
     * Vertical padding, border, and margin do not apply when determining the
     * height for inline frames.
     *
     * http://www.w3.org/TR/CSS21/visudet.html#inline-non-replaced
     *
     * The vertical padding, border and margin of an inline, non-replaced box
     * start at the top and bottom of the content area, not the
     * 'line-height'. But only the 'line-height' is used to calculate the
     * height of the line box.
     */
    public function get_margin_height(): float
    {
        // This function is also called in add_frame_to_line() and is used to
        // determine the line height
        $style = $this->get_style();
        $font = $style->font_family;
        $size = $style->font_size;
        $font_height = $this->_dompdf->get_font_metrics()->get_font_height($font, $size);
        return $style->line_height / ($size > 0 ? $size : 1) * $font_height;
    }
    public function get_padding_box(): array
    {
        $style = $this->_frame->get_style();
        $pb = $this->_frame->get_padding_box();
        $pb[3] = $pb['h'] = (float) $style->length_in_pt($style->height);
        return $pb;
    }
    public function set_text_spacing(float $spacing): void
    {
        $this->text_spacing = $spacing;
        $this->recalculate_width();
    }
    /**
     * Recalculate the text width
     */
    public function recalculate_width(): float
    {
        $font_metrics = $this->_dompdf->get_font_metrics();
        $style = $this->get_style();
        $text = $this->get_text();
        $font = $style->font_family;
        $size = $style->font_size;
        $word_spacing = $this->text_spacing + $style->word_spacing;
        $letter_spacing = $style->letter_spacing;
        $text_width = $font_metrics->get_text_width($text, $font, $size, $word_spacing, $letter_spacing);
        $style->set_used('width', $text_width);
        return $text_width;
    }
    // Text manipulation methods
    /**
     * Split the text in this frame at the offset specified.  The remaining
     * text is added as a sibling frame following this one and is returned.
     *
     * @param bool $split_parent Whether to split parent inline frames.
     * @return Text|null
     */
    public function split_text(int $offset, bool $split_parent = true): ?self
    {
        if ($offset === 0) {
            return null;
        }
        $split = $this->_frame->get_node()->split_text($offset);
        if ($split === false) {
            return null;
        }
        /** @var Text */
        $deco = $this->copy($split);
        $style = $this->_frame->get_style();
        $split_style = $deco->get_style();
        if ($this->mapped_font !== null) {
            $split_style->set_used('font_family', $this->mapped_font);
            $deco->mapped_font = $this->mapped_font;
        }
        // Clear decoration widths at the split point. They might have been
        // copied from the parent frame during inline reflow
        $style->margin_right = 0.0;
        $style->padding_right = 0.0;
        $style->border_right_width = 0.0;
        $split_style->margin_left = 0.0;
        $split_style->padding_left = 0.0;
        $split_style->border_left_width = 0.0;
        $p = $this->get_parent();
        $p->insert_child_after($deco, $this, false);
        if ($split_parent && $p instanceof Inline) {
            $p->split($deco);
        }
        return $deco;
    }
    /**
     * @param int $offset
     * @param int $count
     */
    public function delete_text($offset, $count): void
    {
        $this->_frame->get_node()->delete_data($offset, $count);
    }
    /**
     * @param string $text
     */
    public function set_text($text): void
    {
        $this->_frame->get_node()->data = $text;
    }
    /**
     * Determines the optimal font that applies to the frame and splits
     * the frame where the optimal font changes.
     */
    public function apply_font_mapping(): void
    {
        if ($this->mapped_font !== null) {
            return;
        }
        $font_metrics = $this->_dompdf->get_font_metrics();
        $style = $this->get_style();
        $families = $style->get_font_family_computed();
        $subtype = $font_metrics->get_type($style->font_weight . ' ' . $style->font_style);
        $char_mapping = $font_metrics->map_text_to_fonts($this->get_text(), $families, $subtype, 1);
        if (isset($char_mapping[0])) {
            if ($char_mapping[0]['length'] !== 0) {
                $this->split_text($char_mapping[0]['length'], false);
            }
            $mapped_font = $char_mapping[0]['font'];
            if ($mapped_font !== null) {
                $style->set_used('font_family', $mapped_font);
                $this->mapped_font = $mapped_font;
            }
        }
    }
}