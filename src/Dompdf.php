<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

use Dom_Document;
use Dom_Node;
use Dompdf\Adapter\CPDF;
use Dompdf\Css\Stylesheet;
use Dompdf\Frame\Factory;
use Dompdf\Frame\Frame_Tree;
use Dompdf\Image\Cache;
use Domx_Path;
use Masterminds\HTML5;
/**
 * Dompdf - PHP5 HTML to PDF renderer
 *
 * Dompdf loads HTML and does its best to render it as a PDF.  It gets its
 * name from the new DomDocument PHP5 extension.  Source HTML is first
 * parsed by a DomDocument object.  Dompdf takes the resulting DOM tree and
 * attaches a {@link Frame} object to each node.  {@link Frame} objects store
 * positioning and layout information and each has a reference to a {@link
 * Style} object.
 *
 * Style information is loaded and parsed (see {@link Stylesheet}) and is
 * applied to the frames in the tree by using XPath.  CSS selectors are
 * converted into XPath queries, and the computed {@link Style} objects are
 * applied to the {@link Frame}s.
 *
 * {@link Frame}s are then decorated (in the design pattern sense of the
 * word) based on their CSS display property ({@link
 * http://www.w3.org/TR/CSS21/visuren.html#propdef-display}).
 * Frame_Decorators augment the basic {@link Frame} class by adding
 * additional properties and methods specific to the particular type of
 * {@link Frame}.  For example, in the CSS layout model, block frames
 * (display: block;) contain line boxes that are usually filled with text or
 * other inline frames.  The Block therefore adds a $lines
 * property as well as methods to add {@link Frame}s to lines and to add
 * additional lines.  {@link Frame}s also are attached to specific
 * AbstractPositioner and {@link AbstractFrameReflower} objects that contain the
 * positioining and layout algorithm for a specific type of frame,
 * respectively.  This is an application of the Strategy pattern.
 *
 * Layout, or reflow, proceeds recursively (post-order) starting at the root
 * of the document.  Space constraints (containing block width & height) are
 * pushed down, and resolved positions and sizes bubble up.  Thus, every
 * {@link Frame} in the document tree is traversed once (except for tables
 * which use a two-pass layout algorithm).  If you are interested in the
 * details, see the reflow() method of the Reflower classes.
 *
 * Rendering is relatively straightforward once layout is complete. {@link
 * Frame}s are rendered using an adapted {@link Cpdf} class, originally
 * written by Wayne Munro, http://www.ros.co.nz/pdf/.  (Some performance
 * related changes have been made to the original {@link Cpdf} class, and
 * the {@link Dompdf\Adapter\CPDF} class provides a simple, stateless interface to
 * PDF generation.)  PDFLib support has now also been added, via the {@link
 * Dompdf\Adapter\PDFLib}.
 *
 *
 * @package dompdf
 */
class Dompdf
{
    /**
     * Version string for dompdf
     *
     * @var string
     */
    private $version = 'dompdf';
    /**
     * DomDocument representing the HTML document
     *
     * @var DOMDocument
     */
    private $dom;
    /**
     * FrameTree derived from the DOM tree
     *
     * @var FrameTree
     */
    private $tree;
    /**
     * Stylesheet for the document
     *
     * @var Stylesheet
     */
    private $css;
    /**
     * Actual PDF renderer
     *
     * @var Canvas
     */
    private $canvas;
    /**
     * Desired paper size ('letter', 'legal', 'A4', etc.)
     *
     * @var string|float[]
     */
    private $paper_size;
    /**
     * Paper orientation ('portrait' or 'landscape')
     *
     * @var string
     */
    private $paper_orientation = 'portrait';
    /**
     * Callbacks on new page and new element
     *
     * @var array
     */
    private $callbacks = [];
    /**
     * Base hostname
     *
     * Used for relative paths/urls
     * @var string
     */
    private $base_host = '';
    /**
     * Absolute base path
     *
     * Used for relative paths/urls
     * @var string
     */
    private $base_path = '';
    /**
     * Protocol used to request file (file://, http://, etc)
     *
     * @var string
     */
    private $protocol = '';
    /**
     * The system's locale
     *
     * @var string
     */
    private $system_locale;
    /**
     * The system's mbstring internal encoding
     *
     * @var string
     */
    private $mbstring_encoding;
    /**
     * The system's PCRE JIT configuration
     *
     * @var string
     */
    private $pcre_jit;
    /**
     * The default view of the PDF in the viewer
     *
     * @var string
     */
    private $default_view = 'Fit';
    /**
     * The default view options of the PDF in the viewer
     *
     * @var array
     */
    private $default_view_options = [];
    /**
     * Tells whether the DOM document is in quirksmode (experimental)
     *
     * @var bool
     */
    private $quirksmode = false;
    /**
     * Local file extension whitelist
     *
     * File extensions supported by dompdf for local files.
     *
     * @var array
     */
    private $allowed_local_file_extensions = ['htm', 'html'];
    /**
     * @var Options
     */
    private $options;
    /**
     * @var FontMetrics
     */
    private $font_metrics;
    /**
     * The list of built-in fonts
     *
     * @var array
     * @deprecated
     */
    public static $native_fonts = ['courier', 'courier-bold', 'courier-oblique', 'courier-boldoblique', 'helvetica', 'helvetica-bold', 'helvetica-oblique', 'helvetica-boldoblique', 'times-roman', 'times-bold', 'times-italic', 'times-bolditalic', 'symbol', 'zapfdinbats'];
    /**
     * The list of built-in fonts
     *
     * @var array
     */
    public static $native_fonts = ['courier', 'courier-bold', 'courier-oblique', 'courier-boldoblique', 'helvetica', 'helvetica-bold', 'helvetica-oblique', 'helvetica-boldoblique', 'times-roman', 'times-bold', 'times-italic', 'times-bolditalic', 'symbol', 'zapfdinbats'];
    /**
     * Class constructor
     *
     * @param Options|array|null $options
     */
    public function __construct($options = null)
    {
        if (isset($options) && $options instanceof Options) {
            $this->set_options($options);
        } elseif (is_array($options)) {
            $this->set_options(new Options($options));
        } else {
            $this->set_options(new Options());
        }
        $version_file = realpath(__DIR__ . '/../VERSION');
        if (($version = file_get_contents($version_file)) !== false) {
            $version = trim($version);
            if ($version !== '$Format:<%h>$') {
                $this->version = sprintf('dompdf %s', $version);
            }
        }
        $this->set_php_config();
        $this->paper_size = $this->options->get_default_paper_size();
        $this->paper_orientation = $this->options->get_default_paper_orientation();
        $this->canvas = Canvas_Factory::get_instance($this, $this->paper_size, $this->paper_orientation);
        $this->font_metrics = new Font_Metrics($this->canvas, $this->options);
        $this->css = new Stylesheet($this);
        $this->restore_php_config();
    }
    /**
     * Save the system's existing locale, PCRE JIT, and MBString encoding
     * configuration and configure the system for Dompdf processing
     */
    private function set_php_config(): void
    {
        if (sprintf('%.1f', 1.0) !== '1.0') {
            $this->system_locale = setlocale(LC_NUMERIC, '0');
            setlocale(LC_NUMERIC, 'C');
        }
        if (function_exists('ini_get') && function_exists('ini_set')) {
            $this->pcre_jit = @ini_get('pcre.jit');
            @ini_set('pcre.jit', '0');
        }
        $this->mbstring_encoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');
    }
    /**
     * Restore the system's locale configuration
     */
    private function restore_php_config(): void
    {
        if ($this->system_locale !== null) {
            setlocale(LC_NUMERIC, $this->system_locale);
            $this->system_locale = null;
        }
        if (function_exists('ini_get') && function_exists('ini_set')) {
            if ($this->pcre_jit !== null) {
                @ini_set('pcre.jit', $this->pcre_jit);
                $this->pcre_jit = null;
            }
        }
        if ($this->mbstring_encoding !== null) {
            mb_internal_encoding($this->mbstring_encoding);
            $this->mbstring_encoding = null;
        }
    }
    /**
     * @param $file
     * @deprecated
     */
    public function load_html_file($file): void
    {
        $this->load_html_file($file);
    }
    /**
     * Loads an HTML file.
     *
     * If no encoding is given or set via `Content-Type` header, the document
     * encoding specified via `<meta>` tag is used. An existing Unicode BOM
     * always takes precedence.
     *
     * Parse errors are stored in the global array `$_dompdf_warnings`.
     *
     * @param string      $file     A filename or URL to load.
     * @param string|null $encoding Encoding of the file.
     */
    public function load_html_file($file, $encoding = null): void
    {
        $this->set_php_config();
        if (!$this->protocol && !$this->base_host && !$this->base_path) {
            [$this->protocol, $this->base_host, $this->base_path] = Helpers::explode_url($file);
        }
        $protocol = strtolower($this->protocol);
        $uri = Helpers::build_url($this->protocol, $this->base_host, $this->base_path, $file, $this->options->get_chroot());
        $allowed_protocols = $this->options->get_allowed_protocols();
        if (!array_key_exists($protocol, $allowed_protocols)) {
            throw new Exception("Permission denied on {$file}. The communication protocol is not supported.");
        }
        if ($protocol === 'file://') {
            $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowed_local_file_extensions)) {
                throw new Exception("Permission denied on {$file}: The file extension is forbidden.");
            }
        }
        foreach ($allowed_protocols[$protocol]['rules'] as $rule) {
            [$result, $message] = $rule($uri);
            if (!$result) {
                throw new Exception("Error loading {$file}: {$message}");
            }
        }
        [$contents, $http_response_header] = Helpers::get_file_content($uri, $this->options->get_http_context());
        if ($contents === null) {
            throw new Exception("File '{$file}' not found.");
        }
        // See http://the-stickman.com/web-development/php/getting-http-response-headers-when-using-file_get_contents/
        if (isset($http_response_header)) {
            foreach ($http_response_header as $_header) {
                if (preg_match("@Content-Type:\\s*[\\w/]+;\\s*?charset=([^\\s]+)@i", $_header, $matches)) {
                    $encoding = strtoupper($matches[1]);
                    break;
                }
            }
        }
        $this->restore_php_config();
        $this->load_html($contents, $encoding);
    }
    /**
     * @param string $str
     * @param string $encoding
     * @deprecated
     */
    public function load_html($str, $encoding = null): void
    {
        $this->load_html($str, $encoding);
    }
    /**
     * @param DOMDocument $doc
     * @param bool        $quirksmode
     */
    public function load_dom($doc, $quirksmode = false): void
    {
        // Remove #text children nodes in nodes that shouldn't have
        $tag_names = ['html', 'head', 'table', 'tbody', 'thead', 'tfoot', 'tr'];
        foreach ($tag_names as $tag_name) {
            $nodes = $doc->get_elements_by_tag_name($tag_name);
            foreach ($nodes as $node) {
                self::remove_text_nodes($node);
            }
        }
        $this->dom = $doc;
        $this->quirksmode = $quirksmode;
        $this->tree = new Frame_Tree($this->dom);
    }
    /**
     * Loads an HTML document from a string.
     *
     * If no encoding is given, the document encoding specified via `<meta>`
     * tag is used. An existing Unicode BOM always takes precedence.
     *
     * Parse errors are stored in the global array `$_dompdf_warnings`.
     *
     * @param string      $str      The HTML to load.
     * @param string|null $encoding Encoding of the string.
     */
    public function load_html($str, $encoding = null): void
    {
        $this->set_php_config();
        // Detect Unicode via BOM, taking precedence over the given encoding.
        // Remove the mark, as it is treated as document text by DOMDocument.
        // http://us2.php.net/manual/en/function.mb-detect-encoding.php#91051
        if (strncmp($str, "\xfe\xff", 2) === 0) {
            $str = substr($str, 2);
            $encoding = 'UTF-16BE';
        } elseif (strncmp($str, "\xff\xfe", 2) === 0) {
            $str = substr($str, 2);
            $encoding = 'UTF-16LE';
        } elseif (strncmp($str, "﻿", 3) === 0) {
            $str = substr($str, 3);
            $encoding = 'UTF-8';
        }
        // Convert document using the given encoding
        $encoding_given = $encoding !== null && $encoding !== '';
        if ($encoding_given && !in_array(strtoupper($encoding), ['UTF-8', 'UTF8'], true)) {
            $converted = mb_convert_encoding($str, 'UTF-8', $encoding);
            if ($converted !== false) {
                $str = $converted;
            }
        }
        // Parse document encoding from `<meta>` tag ...
        $charset = "(?<charset>[a-z0-9\\-]+)";
        $content_type = "http-equiv\\s*=\\s* ([\"']?)\\s* Content-Type";
        $content_start = "content\\s*=\\s* ([\"']?)\\s* [\\w\\/]+ \\s*;\\s* charset\\s*=\\s*";
        $meta_tags = [
            "/<meta \\s[^>]* {$content_type} \\s*\\g1\\s* {$content_start} {$charset} \\s*\\g2 [^>]*>/isx",
            // <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            "/<meta \\s[^>]* {$content_start} {$charset} \\s*\\g1\\s* {$content_type} \\s*\\g3 [^>]*>/isx",
            // <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
            "/<meta \\s[^>]* charset\\s*=\\s* ([\"']?)\\s* {$charset} \\s*\\g1 [^>]*>/isx",
        ];
        foreach ($meta_tags as $pattern) {
            if (preg_match($pattern, $str, $matches, PREG_OFFSET_CAPTURE)) {
                [$document_encoding, $offset] = $matches['charset'];
                break;
            }
        }
        // ... and replace it with UTF-8; add a corresponding `<meta>` tag if
        // missing. This is to ensure that `DOMDocument` handles the document
        // encoding properly, as it will mess up the encoding if the charset
        // declaration is missing or different from the actual encoding
        if (isset($document_encoding) && isset($offset)) {
            if (!in_array(strtoupper($document_encoding), ['UTF-8', 'UTF8'], true)) {
                $str = substr($str, 0, $offset) . 'UTF-8' . substr($str, $offset + strlen($document_encoding));
            }
        } elseif (($head_pos = stripos($str, '<head>')) !== false) {
            $str = substr($str, 0, $head_pos + 6) . '<meta charset="UTF-8">' . substr($str, $head_pos + 6);
        } else {
            $str = '<meta charset="UTF-8">' . $str;
        }
        // If no encoding was passed, use the document encoding, falling back to
        // auto-detection
        $fallback_encoding = $document_encoding ?? 'auto';
        if (!$encoding_given && !in_array(strtoupper($fallback_encoding), ['UTF-8', 'UTF8'], true)) {
            $converted = mb_convert_encoding($str, 'UTF-8', $fallback_encoding);
            if ($converted !== false) {
                $str = $converted;
            }
        }
        // Store parsing warnings as messages
        set_error_handler([Helpers::class, 'record_warnings']);
        try {
            // @todo Take the quirksmode into account
            // https://quirks.spec.whatwg.org/
            // http://hsivonen.iki.fi/doctype/
            $quirksmode = false;
            $html5 = new HTML5(['encoding' => 'UTF-8', 'disable_html_ns' => true]);
            $dom = $html5->load_html($str);
            // extra step to normalize the HTML document structure
            // see Masterminds/html5-php#166
            $doc = new Dom_Document('1.0', 'UTF-8');
            $doc->preserve_white_space = true;
            $doc->load_html($html5->save_html($dom), LIBXML_NOWARNING | LIBXML_NOERROR);
            $this->load_dom($doc, $quirksmode);
        } finally {
            restore_error_handler();
            $this->restore_php_config();
        }
    }
    /**
     * @deprecated
     */
    public static function remove_text_nodes(Dom_Node $node): void
    {
        self::remove_text_nodes($node);
    }
    public static function remove_text_nodes(Dom_Node $node): void
    {
        $children = [];
        for ($i = 0; $i < $node->child_nodes->length; $i++) {
            $child = $node->child_nodes->item($i);
            if ($child->node_name === '#text') {
                $children[] = $child;
            }
        }
        foreach ($children as $child) {
            $node->remove_child($child);
        }
    }
    /**
     * Builds the {@link FrameTree}, loads any CSS and applies the styles to
     * the {@link FrameTree}
     */
    private function process_html(): void
    {
        $this->tree->build_tree();
        $this->css->load_css_file($this->css->get_default_stylesheet(), Stylesheet::ORIG_UA);
        $acceptedmedia = Stylesheet::$ACCEPTED_GENERIC_MEDIA_TYPES;
        $acceptedmedia[] = $this->options->get_default_media_type();
        // <base href="" />
        /** @var \DOMElement|null */
        $base_node = $this->dom->get_elements_by_tag_name('base')->item(0);
        $base_href = $base_node ? $base_node->get_attribute('href') : '';
        if ($base_href !== '') {
            [$this->protocol, $this->base_host, $this->base_path] = Helpers::explode_url($base_href);
        }
        // Set the base path of the Stylesheet to that of the file being processed
        $this->css->set_protocol($this->protocol);
        $this->css->set_host($this->base_host);
        $this->css->set_base_path($this->base_path);
        // Get all the stylesheets so that they are processed in document order
        $xpath = new Domx_Path($this->dom);
        $stylesheets = $xpath->query("//*[name() = 'link' or name() = 'style']");
        /** @var \DOMElement $tag */
        foreach ($stylesheets as $tag) {
            switch (strtolower($tag->node_name)) {
                // load <link rel="STYLESHEET" ... /> tags
                case 'link':
                    if ((stripos($tag->get_attribute('rel'), 'stylesheet') !== false || mb_strtolower($tag->get_attribute('type')) === 'text/css') && stripos($tag->get_attribute('rel'), 'alternate') === false) {
                        //Check if the css file is for an accepted media type
                        //media not given then always valid
                        $formedialist = preg_split("/[\\s\n,]/", $tag->get_attribute('media'), -1, PREG_SPLIT_NO_EMPTY);
                        if (count($formedialist) > 0) {
                            $accept = false;
                            foreach ($formedialist as $type) {
                                if (in_array(mb_strtolower(trim($type)), $acceptedmedia)) {
                                    $accept = true;
                                    break;
                                }
                            }
                            if (!$accept) {
                                //found at least one mediatype, but none of the accepted ones
                                //Skip this css file.
                                break;
                            }
                        }
                        $url = $tag->get_attribute('href');
                        $url = Helpers::build_url($this->protocol, $this->base_host, $this->base_path, $url, $this->options->get_chroot());
                        if ($url !== null) {
                            $this->css->load_css_file($url, Stylesheet::ORIG_AUTHOR);
                        }
                    }
                    break;
                // load <style> tags
                case 'style':
                    // Accept all <style> tags by default (note this is contrary to W3C
                    // HTML 4.0 spec:
                    // http://www.w3.org/TR/REC-html40/present/styles.html#adef-media
                    // which states that the default media type is 'screen'
                    if ($tag->has_attributes() && ($media = $tag->get_attribute('media')) && !in_array($media, $acceptedmedia)) {
                        break;
                    }
                    $css = '';
                    if ($tag->has_child_nodes()) {
                        $child = $tag->first_child;
                        while ($child) {
                            $css .= $child->node_value;
                            // Handle <style><!-- blah --></style>
                            $child = $child->next_sibling;
                        }
                    } else {
                        $css = $tag->node_value;
                    }
                    // Set the base path of the Stylesheet to that of the file being processed
                    $this->css->set_protocol($this->protocol);
                    $this->css->set_host($this->base_host);
                    $this->css->set_base_path($this->base_path);
                    $this->css->load_css($css, Stylesheet::ORIG_AUTHOR);
                    break;
            }
            // Set the base path of the Stylesheet to that of the file being processed
            $this->css->set_protocol($this->protocol);
            $this->css->set_host($this->base_host);
            $this->css->set_base_path($this->base_path);
        }
    }
    /**
     * @param string $cacheId
     * @deprecated
     */
    public function enable_caching($cache_id): void
    {
        $this->enable_caching($cache_id);
    }
    /**
     * Enable experimental caching capability
     *
     * @param string $cacheId
     */
    public function enable_caching($cache_id)
    {
    }
    /**
     * @param string $value
     * @return bool
     * @deprecated
     */
    public function parse_default_view($value)
    {
        return $this->parse_default_view($value);
    }
    /**
     * @param string $value
     */
    public function parse_default_view($value): bool
    {
        $valid = ['XYZ', 'Fit', 'FitH', 'FitV', 'FitR', 'FitB', 'FitBH', 'FitBV'];
        $options = preg_split("/\\s*,\\s*/", trim($value));
        $default_view = array_shift($options);
        if (!in_array($default_view, $valid)) {
            return false;
        }
        $this->set_default_view($default_view, $options);
        return true;
    }
    /**
     * Renders the HTML to PDF
     */
    public function render(): void
    {
        $this->set_php_config();
        $log_output_file = $this->options->get_log_output_file();
        if ($log_output_file) {
            if (!file_exists($log_output_file) && is_writable(dirname($log_output_file))) {
                touch($log_output_file);
            }
            $start_time = microtime(true);
            if (is_writable($log_output_file)) {
                ob_start();
            }
        }
        $this->process_html();
        $this->css->apply_styles($this->tree);
        // @page style rules : size, margins
        $page_styles = $this->css->get_page_styles();
        $base_page_style = $page_styles['base'];
        unset($page_styles['base']);
        foreach ($page_styles as $page_style) {
            $page_style->inherit($base_page_style);
        }
        // Set paper size if defined via CSS
        if (is_array($base_page_style->size)) {
            // Orientation is already applied when reading the computed CSS
            // `size` value. The `Canvas` back ends, however, unconditionally
            // swap with an orientation of `landscape` and leave the defined
            // size as-is with `portrait`; so passing `portrait` as orientation
            // here (via the default value) is correct
            [$width, $height] = $base_page_style->size;
            $this->set_paper([0, 0, $width, $height]);
        }
        // Create a new canvas instance if the current one does not match the
        // desired paper size
        $canvas_width = $this->canvas->get_width();
        $canvas_height = $this->canvas->get_height();
        $size = $this->get_paper_size();
        if (\Dompdf\Helpers::length_equal($canvas_width, $size[2]) === false || \Dompdf\Helpers::length_equal($canvas_height, $size[3]) === false) {
            $this->canvas = Canvas_Factory::get_instance($this, $this->paper_size, $this->paper_orientation);
            $this->font_metrics->set_canvas($this->canvas);
        }
        $canvas = $this->canvas;
        Line_Box::reset_float_reflow_limit();
        // FIXME smelly hack
        $root_frame = $this->tree->get_root();
        $root = Factory::decorate_root($root_frame, $this);
        foreach ($this->tree as $frame) {
            if ($frame === $root_frame) {
                continue;
            }
            Factory::decorate_frame($frame, $this, $root);
        }
        // Add meta information
        $title = $this->dom->get_elements_by_tag_name('title');
        if ($title->length) {
            $canvas->add_info('Title', trim($title->item(0)->node_value));
        }
        $metas = $this->dom->get_elements_by_tag_name('meta');
        $labels = ['author' => 'Author', 'keywords' => 'Keywords', 'description' => 'Subject'];
        /** @var \DOMElement $meta */
        foreach ($metas as $meta) {
            $name = mb_strtolower($meta->get_attribute('name'));
            $value = trim($meta->get_attribute('content'));
            if (isset($labels[$name])) {
                $canvas->add_info($labels[$name], $value);
                continue;
            }
            if ($name === 'dompdf.view' && $this->parse_default_view($value)) {
                $canvas->set_default_view($this->default_view, $this->default_view_options);
            }
        }
        $root->set_containing_block(0, 0, $canvas->get_width(), $canvas->get_height());
        $root->set_renderer(new Renderer($this));
        // This is where the magic happens:
        $root->reflow();
        if (isset($this->callbacks['end_document'])) {
            $fs = $this->callbacks['end_document'];
            foreach ($fs as $f) {
                $canvas->page_script($f);
            }
        }
        // Clean up cached images
        if (!$this->options->get_debug_keep_temp()) {
            Cache::clear($this->options->get_debug_png());
        }
        global $_dompdf_warnings, $_dompdf_show_warnings;
        if ($_dompdf_show_warnings && isset($_dompdf_warnings)) {
            echo '<b>Dompdf Warnings</b><br><pre>';
            foreach ($_dompdf_warnings as $msg) {
                echo $msg . "\n";
            }
            if ($canvas instanceof CPDF) {
                echo $canvas->get_cpdf()->messages;
            }
            echo '</pre>';
            flush();
        }
        if ($log_output_file && is_writable($log_output_file)) {
            $this->write_log($log_output_file, $start_time);
            ob_end_clean();
        }
        $this->restore_php_config();
    }
    /**
     * Writes the output buffer in the log file
     */
    private function write_log(string $log_output_file, float $start_time): void
    {
        $frames = Frame::$ID_COUNTER;
        $memory = memory_get_peak_usage(true) / 1024;
        $time = (microtime(true) - $start_time) * 1000;
        $out = sprintf("<span style='color: #000' title='Frames'>%6d</span>" . "<span style='color: #009' title='Memory'>%10.2f KB</span>" . "<span style='color: #900' title='Time'>%10.2f ms</span>" . "<span  title='Quirksmode'>  " . ($this->quirksmode ? "<span style='color: #d00'> ON</span>" : "<span style='color: #0d0'>OFF</span>") . '</span><br />', $frames, $memory, $time);
        $out .= ob_get_contents();
        ob_clean();
        file_put_contents($log_output_file, $out);
    }
    /**
     * Add meta information to the PDF after rendering.
     *
     * @deprecated
     */
    public function add_info(string $label, string $value): void
    {
        $this->add_info($label, $value);
    }
    /**
     * Add meta information to the PDF after rendering.
     *
     * @param string $label Label of the value (Creator, Producer, etc.)
     * @param string $value The text to set
     */
    public function add_info(string $label, string $value): void
    {
        $this->canvas->add_info($label, $value);
    }
    /**
     * Streams the PDF to the client.
     *
     * The file will open a download dialog by default. The options
     * parameter controls the output. Accepted options (array keys) are:
     *
     * 'compress' = > 1 (=default) or 0:
     *   Apply content stream compression
     *
     * 'Attachment' => 1 (=default) or 0:
     *   Set the 'Content-Disposition:' HTTP header to 'attachment'
     *   (thereby causing the browser to open a download dialog)
     *
     * @param string $filename the name of the streamed file
     * @param array $options header options (see above)
     */
    public function stream($filename = 'document.pdf', $options = []): void
    {
        $this->set_php_config();
        $this->canvas->stream($filename, $options);
        $this->restore_php_config();
    }
    /**
     * Returns the PDF as a string.
     *
     * The options parameter controls the output. Accepted options are:
     *
     * 'compress' = > 1 or 0 - apply content stream compression, this is
     *    on (1) by default
     *
     * @param array $options options (see above)
     *
     * @return string
     */
    public function output($options = [])
    {
        $this->set_php_config();
        $output = $this->canvas->output($options);
        $this->restore_php_config();
        return $output;
    }
    /**
     * @return string
     * @deprecated
     */
    public function output_html()
    {
        return $this->output_html();
    }
    /**
     * Returns the underlying HTML document as a string
     *
     * @return string
     */
    public function output_html()
    {
        return $this->dom->save_html();
    }
    /**
     * Get the dompdf option value
     *
     * @param string $key
     * @return mixed
     * @deprecated
     */
    public function get_option($key)
    {
        return $this->options->get($key);
    }
    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     * @deprecated
     */
    public function set_option($key, $value): self
    {
        $new_options = clone $this->options;
        $new_options->set($key, $value);
        $this->set_options($new_options);
        return $this;
    }
    /**
     * @return $this
     * @deprecated
     */
    public function set_options(array $options): self
    {
        $new_options = clone $this->options;
        $new_options->set($options);
        $this->set_options($new_options);
        return $this;
    }
    /**
     * @param string $size
     * @deprecated
     */
    public function set_paper($size, string $orientation = 'portrait'): void
    {
        $this->set_paper($size, $orientation);
    }
    /**
     * Sets the paper size & orientation
     *
     * @param string|float[] $size 'letter', 'legal', 'A4', etc. {@link Dompdf\Adapter\CPDF::$PAPER_SIZES}
     * @param string $orientation 'portrait' or 'landscape'
     * @return $this
     */
    public function set_paper($size, string $orientation = 'portrait'): self
    {
        $current_size = $this->get_paper_size();
        $this->paper_size = $size;
        $this->paper_orientation = $orientation;
        $new_size = $this->get_paper_size();
        if (\Dompdf\Helpers::length_equal($current_size[2], $new_size[2]) === false || \Dompdf\Helpers::length_equal($current_size[3], $new_size[3]) === false) {
            $this->canvas = Canvas_Factory::get_instance($this, $this->paper_size, $this->paper_orientation);
        }
        return $this;
    }
    /**
     * Gets the paper size
     *
     * @return float[] A four-element float array
     */
    public function get_paper_size(): array
    {
        $paper = $this->paper_size;
        $orientation = $this->paper_orientation;
        if (is_array($paper)) {
            $size = array_map('floatval', $paper);
        } else {
            $paper = strtolower($paper);
            $size = CPDF::$PAPER_SIZES[$paper] ?? CPDF::$PAPER_SIZES['letter'];
        }
        if (strtolower($orientation) === 'landscape') {
            [$size[2], $size[3]] = [$size[3], $size[2]];
        }
        return $size;
    }
    /**
     * Gets the paper orientation
     *
     * @return string Either "portrait" or "landscape"
     */
    public function get_paper_orientation(): string
    {
        return $this->paper_orientation;
    }
    /**
     * @return $this
     */
    public function set_tree(Frame_Tree $tree): self
    {
        $this->tree = $tree;
        return $this;
    }
    /**
     * @return FrameTree
     * @deprecated
     */
    public function get_tree()
    {
        return $this->get_tree();
    }
    /**
     * Returns the underlying {@link FrameTree} object
     *
     * @return FrameTree
     */
    public function get_tree()
    {
        return $this->tree;
    }
    /**
     * @return $this
     * @deprecated
     */
    public function set_protocol(string $protocol)
    {
        return $this->set_protocol($protocol);
    }
    /**
     * Sets the protocol to use
     * FIXME validate these
     *
     * @return $this
     */
    public function set_protocol(string $protocol): self
    {
        $this->protocol = $protocol;
        return $this;
    }
    /**
     * @return string
     * @deprecated
     */
    public function get_protocol()
    {
        return $this->get_protocol();
    }
    /**
     * Returns the protocol in use
     *
     * @return string
     */
    public function get_protocol()
    {
        return $this->protocol;
    }
    /**
     * @deprecated
     */
    public function set_host(string $host): void
    {
        $this->set_base_host($host);
    }
    /**
     * Sets the base hostname
     *
     * @return $this
     */
    public function set_base_host(string $base_host): self
    {
        $this->base_host = $base_host;
        return $this;
    }
    /**
     * @return string
     * @deprecated
     */
    public function get_host()
    {
        return $this->get_base_host();
    }
    /**
     * Returns the base hostname
     *
     * @return string
     */
    public function get_base_host()
    {
        return $this->base_host;
    }
    /**
     * Sets the base path
     *
     * @deprecated
     */
    public function set_base_path(string $path): void
    {
        $this->set_base_path($path);
    }
    /**
     * Sets the base path
     *
     * @return $this
     */
    public function set_base_path(string $base_path): self
    {
        $this->base_path = $base_path;
        return $this;
    }
    /**
     * @return string
     * @deprecated
     */
    public function get_base_path()
    {
        return $this->get_base_path();
    }
    /**
     * Returns the base path
     *
     * @return string
     */
    public function get_base_path()
    {
        return $this->base_path;
    }
    /**
     * @param string $default_view The default document view
     * @param array $options The view's options
     * @return $this
     * @deprecated
     */
    public function set_default_view($default_view, $options)
    {
        return $this->set_default_view($default_view, $options);
    }
    /**
     * Sets the default view
     *
     * @param string $defaultView The default document view
     * @param array $options The view's options
     * @return $this
     */
    public function set_default_view($default_view, $options): self
    {
        $this->default_view = $default_view;
        $this->default_view_options = $options;
        return $this;
    }
    /**
     * @param resource $http_context
     * @return $this
     * @deprecated
     */
    public function set_http_context($http_context)
    {
        return $this->set_http_context($http_context);
    }
    /**
     * Sets the HTTP context
     *
     * @param resource|array $httpContext
     * @return $this
     */
    public function set_http_context($http_context): self
    {
        $this->options->set_http_context($http_context);
        return $this;
    }
    /**
     * @return resource
     * @deprecated
     */
    public function get_http_context()
    {
        return $this->get_http_context();
    }
    /**
     * Returns the HTTP context
     *
     * @return resource
     */
    public function get_http_context()
    {
        return $this->options->get_http_context();
    }
    /**
     * Set a custom `Canvas` instance to render the document to.
     *
     * Be aware that the instance will be replaced on render if the document
     * defines a paper size different from the canvas.
     *
     * @return $this
     */
    public function set_canvas(Canvas $canvas): self
    {
        $this->canvas = $canvas;
        $canvas_width = $this->canvas->get_width();
        $canvas_height = $this->canvas->get_height();
        $this->paper_size = [0, 0, $canvas_width, $canvas_height];
        $this->paper_orientation = 'portrait';
        return $this;
    }
    /**
     * @return Canvas
     * @deprecated
     */
    public function get_canvas()
    {
        return $this->get_canvas();
    }
    /**
     * Return the underlying Canvas instance (e.g. Dompdf\Adapter\CPDF, Dompdf\Adapter\GD)
     *
     * @return Canvas
     */
    public function get_canvas()
    {
        return $this->canvas;
    }
    /**
     * @return $this
     */
    public function set_css(Stylesheet $css): self
    {
        $this->css = $css;
        return $this;
    }
    /**
     * @return Stylesheet
     * @deprecated
     */
    public function get_css()
    {
        return $this->get_css();
    }
    /**
     * Returns the stylesheet
     *
     * @return Stylesheet
     */
    public function get_css()
    {
        return $this->css;
    }
    /**
     * @return $this
     */
    public function set_dom(Dom_Document $dom): self
    {
        $this->dom = $dom;
        return $this;
    }
    /**
     * @return DOMDocument
     * @deprecated
     */
    public function get_dom()
    {
        return $this->get_dom();
    }
    /**
     * @return DOMDocument
     */
    public function get_dom()
    {
        return $this->dom;
    }
    /**
     * @return $this
     */
    public function set_options(Options $options): self
    {
        // For backwards compatibility
        if ($this->options && $this->options->get_http_context() && !$options->get_http_context()) {
            $options->set_http_context($this->options->get_http_context());
        }
        $this->options = $options;
        $font_metrics = $this->font_metrics;
        if (isset($font_metrics)) {
            $font_metrics->set_options($options);
        }
        if (isset($this->canvas)) {
            $this->canvas = Canvas_Factory::get_instance($this, $this->paper_size, $this->paper_orientation);
            if (isset($font_metrics)) {
                $this->font_metrics->set_canvas($this->canvas)->set_options($options);
            }
        }
        return $this;
    }
    /**
     * @return Options
     */
    public function get_options()
    {
        return $this->options;
    }
    /**
     * @return array
     * @deprecated
     */
    public function get_callbacks()
    {
        return $this->get_callbacks();
    }
    /**
     * Returns the callbacks array
     *
     * @return array
     */
    public function get_callbacks()
    {
        return $this->callbacks;
    }
    /**
     * @param array $callbacks the set of callbacks to set
     * @deprecated
     */
    public function set_callbacks(array $callbacks): \Dompdf\Dompdf
    {
        return $this->set_callbacks($callbacks);
    }
    /**
     * Define callbacks that allow modifying the document during render.
     *
     * The callbacks array should contain arrays with `event` set to a callback
     * event name and `f` set to a function or any other callable.
     *
     * The available callback events are:
     * * `begin_page_reflow`: called before page reflow
     * * `begin_frame`: called before a frame is rendered
     * * `end_frame`: called after frame rendering is complete
     * * `begin_page_render`: called before a page is rendered
     * * `end_page_render`: called after page rendering is complete
     * * `end_document`: called for every page after rendering is complete
     *
     * The function `f` receives three arguments `Frame $frame`, `Canvas $canvas`,
     * and `FontMetrics $fontMetrics` for all events but `end_document`. For
     * `end_document`, the function receives four arguments `int $pageNumber`,
     * `int $pageCount`, `Canvas $canvas`, and `FontMetrics $fontMetrics` instead.
     *
     * @param array $callbacks The set of callbacks to set.
     * @return $this
     */
    public function set_callbacks(array $callbacks): self
    {
        $this->callbacks = [];
        foreach ($callbacks as $c) {
            if (is_array($c) && isset($c['event']) && isset($c['f'])) {
                $event = $c['event'];
                $f = $c['f'];
                if (is_string($event) && is_callable($f)) {
                    $this->callbacks[$event][] = $f;
                }
            }
        }
        return $this;
    }
    /**
     * @return boolean
     * @deprecated
     */
    public function get_quirksmode()
    {
        return $this->get_quirksmode();
    }
    /**
     * Get the quirks mode
     *
     * @return boolean true if quirks mode is active
     */
    public function get_quirksmode()
    {
        return $this->quirksmode;
    }
    /**
     * @return $this
     */
    public function set_font_metrics(Font_Metrics $font_metrics): self
    {
        $this->font_metrics = $font_metrics;
        return $this;
    }
    /**
     * @return FontMetrics
     */
    public function get_font_metrics()
    {
        return $this->font_metrics;
    }
    /**
     * PHP5 overloaded getter
     * Along with {@link Dompdf::__set()} __get() provides access to all
     * properties directly.  Typically __get() is not called directly outside
     * of this class.
     *
     *
     * @throws Exception
     * @return mixed
     */
    public function __get(string $prop)
    {
        switch ($prop) {
            case 'version':
                return $this->version;
            default:
                throw new Exception('Invalid property: ' . $prop);
        }
    }
}