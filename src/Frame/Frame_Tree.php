<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Frame;

use Dom_Document;
use Dom_Element;
use Dom_Node;
use Dompdf\Exception;
use Dompdf\Frame;
use Domx_Path;
use IteratorAggregate;
/**
 * Represents an entire document as a tree of frames
 *
 * The FrameTree consists of {@link Frame} objects each tied to specific
 * DOMNode objects in a specific DomDocument.  The FrameTree has the same
 * structure as the DomDocument, but adds additional capabilities for
 * styling and layout.
 *
 * @package dompdf
 */
class Frame_Tree implements IteratorAggregate
{
    /**
     * Tags to ignore while parsing the tree
     *
     * @var array
     */
    protected static $HIDDEN_TAGS = ['area', 'base', 'basefont', 'head', 'style', 'meta', 'title', 'colgroup', 'noembed', 'param', '#comment'];
    /**
     * The main DomDocument
     *
     * @see http://ca2.php.net/manual/en/ref.dom.php
     * @var DOMDocument
     */
    protected $_dom;
    /**
     * The root node of the FrameTree.
     *
     * @var Frame
     */
    protected $_root;
    /**
     * Subtrees of absolutely positioned elements
     *
     * @var array of Frames
     */
    protected $_absolute_frames;
    /**
     * A mapping of {@link Frame} objects to DOMNode objects
     *
     * @var array
     */
    protected $_registry;
    /**
     * Class constructor
     *
     * @param DOMDocument $dom the main DomDocument object representing the current html document
     */
    public function __construct(Dom_Document $dom)
    {
        $this->_dom = $dom;
        $this->_root = null;
        $this->_registry = [];
    }
    /**
     * Returns the DOMDocument object representing the current html document
     *
     * @return DOMDocument
     */
    public function get_dom()
    {
        return $this->_dom;
    }
    /**
     * Returns the root frame of the tree
     *
     * @return Frame
     */
    public function get_root()
    {
        return $this->_root;
    }
    /**
     * Returns a specific frame given its id
     *
     * @param string $id
     *
     * @return Frame|null
     */
    public function get_frame($id)
    {
        return $this->_registry[$id] ?? null;
    }
    /**
     * Returns a post-order iterator for all frames in the tree
     *
     * @deprecated Iterate the tree directly instead
     */
    public function get_frames(): Frame_Tree_Iterator
    {
        return new Frame_Tree_Iterator($this->_root);
    }
    /**
     * Returns a post-order iterator for all frames in the tree
     */
    public function getIterator(): Frame_Tree_Iterator
    {
        return new Frame_Tree_Iterator($this->_root);
    }
    /**
     * Builds the tree
     */
    public function build_tree(): void
    {
        $html = $this->_dom->get_elements_by_tag_name('html')->item(0);
        if (is_null($html)) {
            $html = $this->_dom->first_child;
        }
        if (is_null($html)) {
            throw new Exception('Requested HTML document contains no data.');
        }
        $this->fix_tables();
        $this->_root = $this->_build_tree_r($html);
    }
    /**
     * Adds missing TBODYs around TR
     */
    protected function fix_tables()
    {
        $xp = new Domx_Path($this->_dom);
        // Move table caption before the table
        // FIXME find a better way to deal with it...
        $captions = $xp->query('//table/caption');
        foreach ($captions as $caption) {
            $table = $caption->parent_node;
            $table->parent_node->insert_before($caption, $table);
        }
        $first_rows = $xp->query('//table/tr[1]');
        /** @var DOMElement $tableChild */
        foreach ($first_rows as $table_child) {
            $tbody = $this->_dom->create_element('tbody');
            $table_node = $table_child->parent_node;
            do {
                if ($table_child->node_name === 'tr') {
                    $tmp_node = $table_child;
                    $table_child = $table_child->next_sibling;
                    $table_node->remove_child($tmp_node);
                    $tbody->append_child($tmp_node);
                } else {
                    if ($tbody->has_child_nodes() === true) {
                        $table_node->insert_before($tbody, $table_child);
                        $tbody = $this->_dom->create_element('tbody');
                    }
                    $table_child = $table_child->next_sibling;
                }
            } while ($table_child);
            if ($tbody->has_child_nodes() === true) {
                $table_node->append_child($tbody);
            }
        }
    }
    // FIXME: temporary hack, preferably we will improve rendering of sequential #text nodes
    /**
     * Remove a child from a node
     *
     * Remove a child from a node. If the removed node results in two
     * adjacent #text nodes then combine them.
     *
     * @param DOMNode $node the current DOMNode being considered
     * @param array $children an array of nodes that are the children of $node
     * @param int $index index from the $children array of the node to remove
     */
    protected function _remove_node(Dom_Node $node, array &$children, $index)
    {
        $child = $children[$index];
        $previous_child = $child->previous_sibling;
        $next_child = $child->next_sibling;
        $node->remove_child($child);
        if (isset($previous_child, $next_child)) {
            if ($previous_child->node_name === '#text' && $next_child->node_name === '#text') {
                $previous_child->node_value .= $next_child->node_value;
                $this->_remove_node($node, $children, $index + 1);
            }
        }
        array_splice($children, $index, 1);
    }
    /**
     * Recursively adds {@link Frame} objects to the tree
     *
     * Recursively build a tree of Frame objects based on a dom tree.
     * No layout information is calculated at this time, although the
     * tree may be adjusted (i.e. nodes and frames for generated content
     * and images may be created).
     *
     * @param DOMNode $node the current DOMNode being considered
     */
    protected function _build_tree_r(Dom_Node $node): \Dompdf\Frame
    {
        $frame = new Frame($node);
        $id = $frame->get_id();
        $this->_registry[$id] = $frame;
        if (!$node->has_child_nodes()) {
            return $frame;
        }
        // Store the children in an array so that the tree can be modified
        $children = [];
        $length = $node->child_nodes->length;
        for ($i = 0; $i < $length; $i++) {
            $children[] = $node->child_nodes->item($i);
        }
        $index = 0;
        // INFO: We don't advance $index if a node is removed to avoid skipping nodes
        while ($index < count($children)) {
            $child = $children[$index];
            $node_name = strtolower($child->node_name);
            // Skip non-displaying nodes
            if (in_array($node_name, self::$HIDDEN_TAGS)) {
                if ($node_name !== 'head' && $node_name !== 'style') {
                    $this->_remove_node($node, $children, $index);
                } else {
                    $index++;
                }
                continue;
            }
            // Skip empty text nodes
            if ($node_name === '#text' && $child->node_value === '') {
                $this->_remove_node($node, $children, $index);
                continue;
            }
            // Skip empty image nodes
            if ($node_name === 'img' && $child->get_attribute('src') === '') {
                $this->_remove_node($node, $children, $index);
                continue;
            }
            if (is_object($child)) {
                $frame->append_child($this->_build_tree_r($child), false);
            }
            $index++;
        }
        return $frame;
    }
    /**
     * @param string $pos
     *
     * @return mixed
     */
    public function insert_node(Dom_Element $node, Dom_Element $new_node, $pos)
    {
        if ($pos === 'after' || !$node->first_child) {
            $node->append_child($new_node);
        } else {
            $node->insert_before($new_node, $node->first_child);
        }
        $this->_build_tree_r($new_node);
        $frame_id = $new_node->get_attribute('frame_id');
        $frame = $this->get_frame($frame_id);
        $parent_id = $node->get_attribute('frame_id');
        $parent = $this->get_frame($parent_id);
        if ($parent) {
            if ($pos === 'before') {
                $parent->prepend_child($frame, false);
            } else {
                $parent->append_child($frame, false);
            }
        }
        return $frame_id;
    }
}