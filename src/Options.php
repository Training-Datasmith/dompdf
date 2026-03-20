<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

class Options
{
    /**
     * The root of your DOMPDF installation
     *
     * @var string
     */
    private $root_dir;
    /**
     * The location of a temporary directory.
     *
     * The directory specified must be writable by the executing process.
     * The temporary directory is required to download remote images and when
     * using the PFDLib back end.
     *
     * @var string
     */
    private $temp_dir;
    /**
     * The location of the DOMPDF font directory
     *
     * The location of the directory where DOMPDF will store fonts and font metrics
     * Note: This directory must exist and be writable by the executing process.
     *
     * @var string
     */
    private $font_dir;
    /**
     * The location of the DOMPDF font cache directory
     *
     * This directory contains the cached font metrics for the fonts used by DOMPDF.
     * This directory can be the same as $fontDir
     *
     * Note: This directory must exist and be writable by the executing process.
     *
     * @var string
     */
    private $font_cache;
    /**
     * dompdf's "chroot"
     *
     * Utilized by Dompdf's default file:// protocol URI validation rule.
     * All local files opened by dompdf must be in a subdirectory of the directory
     * or directories specified by this option.
     * DO NOT set this value to '/' since this could allow an attacker to use dompdf to
     * read any files on the server.  This should be an absolute path.
     *
     * ==== IMPORTANT ====
     * This setting may increase the risk of system exploit. Do not change
     * this settings without understanding the consequences. Additional
     * documentation is available on the dompdf wiki at:
     * https://github.com/dompdf/dompdf/wiki
     *
     * @var array
     */
    private $chroot;
    /**
     * Protocol whitelist
     *
     * Protocols and PHP wrappers allowed in URIs, and the validation rules
     * that determine if a resource may be loaded. Full support is not guaranteed
     * for the protocols/wrappers specified
     * by this array.
     *
     * @var array
     */
    private $allowed_protocols = ['data://' => ['rules' => []], 'file://' => ['rules' => []], 'http://' => ['rules' => []], 'https://' => ['rules' => []]];
    /**
     * Operational artifact (log files, temporary files) path validation
     *
     * @var callable
     */
    private $artifact_path_validation;
    /**
     * @var string
     */
    private $log_output_file = '';
    /**
     * Styles targeted to this media type are applied to the document.
     * This is on top of the media types that are always applied:
     *    all, static, visual, bitmap, paged, dompdf
     *
     * @var string
     */
    private $default_media_type = 'screen';
    /**
     * The default paper size.
     *
     * North America standard is "letter"; other countries generally "a4"
     * @see \Dompdf\Adapter\CPDF::PAPER_SIZES for valid sizes
     *
     * @var string|float[]
     */
    private $default_paper_size = 'letter';
    /**
     * The default paper orientation.
     *
     * The orientation of the page (portrait or landscape).
     *
     * @var string
     */
    private $default_paper_orientation = 'portrait';
    /**
     * The default font family
     *
     * Used if no suitable fonts can be found. This must exist in the font folder.
     *
     * @var string
     */
    private $default_font = 'serif';
    /**
     * Image DPI setting
     *
     * This setting determines the default DPI setting for images and fonts.  The
     * DPI may be overridden for inline images by explicitly setting the
     * image's width & height style attributes (i.e. if the image's native
     * width is 600 pixels and you specify the image's width as 72 points,
     * the image will have a DPI of 600 in the rendered PDF.  The DPI of
     * background images can not be overridden and is controlled entirely
     * via this parameter.
     *
     * For the purposes of DOMPDF, pixels per inch (PPI) = dots per inch (DPI).
     * If a size in html is given as px (or without unit as image size),
     * this tells the corresponding size in pt at 72 DPI.
     * This adjusts the relative sizes to be similar to the rendering of the
     * html page in a reference browser.
     *
     * In pdf, always 1 pt = 1/72 inch
     *
     * @var int
     */
    private $dpi = 96;
    /**
     * A ratio applied to the fonts height to be more like browsers' line height
     *
     * @var float
     */
    private $font_height_ratio = 1.1;
    /**
     * Enable embedded PHP
     *
     * If this setting is set to true then DOMPDF will automatically evaluate
     * embedded PHP contained within <script type="text/php"> ... </script> tags.
     *
     * ==== IMPORTANT ====
     * Enabling this for documents you do not trust (e.g. arbitrary remote html
     * pages) is a security risk. Embedded scripts are run with the same level of
     * system access available to dompdf. Set this option to false (recommended)
     * if you wish to process untrusted documents.
     *
     * This setting may increase the risk of system exploit. Do not change
     * this settings without understanding the consequences. Additional
     * documentation is available on the dompdf wiki at:
     * https://github.com/dompdf/dompdf/wiki
     *
     * @var bool
     */
    private $is_php_enabled = false;
    /**
     * Enable remote file access
     *
     * If this setting is set to true, DOMPDF will access remote sites for
     * images and CSS files as required.
     *
     * ==== IMPORTANT ====
     * This can be a security risk, in particular in combination with isPhpEnabled and
     * allowing remote html code to be passed to $dompdf = new DOMPDF(); $dompdf->load_html(...);
     * This allows anonymous users to download legally doubtful internet content which on
     * tracing back appears to being downloaded by your server, or allows malicious php code
     * in remote html pages to be executed by your server with your account privileges.
     *
     * This setting may increase the risk of system exploit. Do not change
     * this settings without understanding the consequences. Additional
     * documentation is available on the dompdf wiki at:
     * https://github.com/dompdf/dompdf/wiki
     *
     * @var bool
     */
    private $is_remote_enabled = false;
    /**
     * List of allowed remote hosts
     *
     * Each value of the array must be a valid hostname.
     *
     * This will be used to filter which resources can be loaded in combination with
     * isRemoteEnabled. If isRemoteEnabled is FALSE, then this will have no effect.
     *
     * Leave to NULL to allow any remote host.
     *
     * @var array|null
     */
    private $allowed_remote_hosts;
    /**
     * Enable PDF/A-3 compliance mode
     *
     * ==== EXPERIMENTAL ====
     * This feature is currently only supported with the CPDF backend and will
     * have no effect if used with any other.
     *
     * Currently this mode only takes care of adding the necessary metadata, output intents, etc.
     * It does not enforce font embedding, it's up to you to embed the fonts you plan on using.
     *
     * @var bool
     */
    private $is_pdf_a_enabled = false;
    /**
     * Enable inline JavaScript
     *
     * If this setting is set to true then DOMPDF will automatically insert
     * JavaScript code contained within <script type="text/javascript"> ... </script>
     * tags as written into the PDF.
     *
     * NOTE: This is PDF-based JavaScript to be executed by the PDF viewer,
     * not browser-based JavaScript executed by Dompdf.
     *
     * @var bool
     */
    private $is_javascript_enabled = true;
    /**
     * Use the HTML5 Lib parser
     *
     * @deprecated
     * @var bool
     */
    private $is_html5parser_enabled = true;
    /**
     * Whether to enable font subsetting or not.
     *
     * @var bool
     */
    private $is_font_subsetting_enabled = true;
    /**
     * @var bool
     */
    private $debug_png = false;
    /**
     * @var bool
     */
    private $debug_keep_temp = false;
    /**
     * @var bool
     */
    private $debug_css = false;
    /**
     * @var bool
     */
    private $debug_layout = false;
    /**
     * @var bool
     */
    private $debug_layout_lines = true;
    /**
     * @var bool
     */
    private $debug_layout_blocks = true;
    /**
     * @var bool
     */
    private $debug_layout_inline = true;
    /**
     * @var bool
     */
    private $debug_layout_padding_box = true;
    /**
     * The PDF rendering backend to use
     *
     * Valid settings are 'PDFLib', 'CPDF', 'GD', and 'auto'. 'auto' will
     * look for PDFLib and use it if found, or if not it will fall back on
     * CPDF. 'GD' renders PDFs to graphic files. {@link Dompdf\CanvasFactory}
     * ultimately determines which rendering class to instantiate
     * based on this setting.
     *
     * @var string
     */
    private $pdf_backend = 'CPDF';
    /**
     * PDFlib license key
     *
     * If you are using a licensed, commercial version of PDFlib, specify
     * your license key here.  If you are using PDFlib-Lite or are evaluating
     * the commercial version of PDFlib, comment out this setting.
     *
     * @link http://www.pdflib.com
     *
     * If pdflib present in web server and auto or selected explicitly above,
     * a real license code must exist!
     *
     * @var string
     */
    private $pdflib_license = '';
    /**
     * HTTP context created with stream_context_create()
     * Will be used for file_get_contents
     *
     * @link https://www.php.net/manual/context.php
     *
     * @var resource
     */
    private $http_context;
    /**
     * @param array $attributes
     */
    public function __construct(?array $attributes = null)
    {
        $root_dir = realpath(__DIR__ . '/../');
        $this->set_chroot([$root_dir]);
        $this->set_root_dir($root_dir);
        $this->set_temp_dir(sys_get_temp_dir());
        $this->set_font_dir($root_dir . '/lib/fonts');
        $this->set_font_cache($this->get_font_dir());
        $ver = '';
        $version_file = realpath(__DIR__ . '/../VERSION');
        if (file_exists($version_file) && ($version = file_get_contents($version_file)) !== false) {
            $version = trim($version);
            if ($version !== '$Format:<%h>$') {
                $ver = "/{$version}";
            }
        }
        $this->set_http_context(['http' => ['follow_location' => false, 'user_agent' => "Dompdf{$ver} https://github.com/dompdf/dompdf"]]);
        $this->set_allowed_protocols(['data://', 'file://', 'http://', 'https://']);
        $this->set_artifact_path_validation([$this, 'validateArtifactPath']);
        if (null !== $attributes) {
            $this->set($attributes);
        }
    }
    /**
     * @param array|string $attributes
     * @param null|mixed $value
     * @return $this
     */
    public function set($attributes, $value = null): self
    {
        // Allowlist of permitted setter methods to prevent arbitrary method dispatch
        static $allowed_setters = ['setAllowedProtocols', 'setAllowedRemoteHosts', 'setArtifactPathValidation', 'setChroot', 'setDebugCss', 'setDebugKeepTemp', 'setDebugLayout', 'setDebugLayoutBlocks', 'setDebugLayoutInline', 'setDebugLayoutLines', 'setDebugLayoutPaddingBox', 'setDebugPng', 'setDefaultFont', 'setDefaultMediaType', 'setDefaultPaperOrientation', 'setDefaultPaperSize', 'setDpi', 'setFontCache', 'setFontDir', 'setFontHeightRatio', 'setHttpContext', 'setIsFontSubsettingEnabled', 'setIsHtml5ParserEnabled', 'setIsJavascriptEnabled', 'setIsPdfAEnabled', 'setIsPhpEnabled', 'setIsRemoteEnabled', 'setLogOutputFile', 'setPdfBackend', 'setPdflibLicense', 'setRootDir', 'setTempDir'];
        if (!is_array($attributes)) {
            $attributes = [$attributes => $value];
        }
        foreach ($attributes as $key => $value) {
            $method_for_match = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
            $method_for_call = 'set' . ucfirst($method_for_match);
            if ($method_for_match === 'enablePhp') {
                $method_for_call = 'setIsPhpEnabled';
            } elseif ($method_for_match === 'enableRemote') {
                $method_for_call = 'setIsRemoteEnabled';
            } elseif ($method_for_match === 'enablePdfA') {
                $method_for_call = 'setIsPdfAEnabled';
            } elseif ($method_for_match === 'enableJavascript') {
                $method_for_call = 'setIsJavascriptEnabled';
            } elseif ($method_for_match === 'enableHtml5Parser') {
                $method_for_call = 'setIsHtml5ParserEnabled';
            } elseif ($method_for_match === 'enableFontSubsetting') {
                $method_for_call = 'setIsFontSubsettingEnabled';
            }
            if (in_array($method_for_call, $allowed_setters, true) && method_exists($this, $method_for_call)) {
                $this->{$method_for_call}($value);
            }
        }
        return $this;
    }
    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $method_for_match = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
        $method_for_call = 'get' . ucfirst($method_for_match);
        if ($method_for_match === 'enablePhp') {
            $method_for_call = 'getIsPhpEnabled';
        } elseif ($method_for_match === 'enableRemote') {
            $method_for_call = 'getIsRemoteEnabled';
        } elseif ($method_for_match === 'enablePdfA') {
            $method_for_call = 'getIsPdfAEnabled';
        } elseif ($method_for_match === 'enableJavascript') {
            $method_for_call = 'getIsJavascriptEnabled';
        } elseif ($method_for_match === 'enableHtml5Parser') {
            $method_for_call = 'getIsHtml5ParserEnabled';
        } elseif ($method_for_match === 'enableFontSubsetting') {
            $method_for_call = 'getIsFontSubsettingEnabled';
        }
        if (method_exists($this, $method_for_call)) {
            return $this->{$method_for_call}();
        }
        return null;
    }
    /**
     * @param string $pdfBackend
     * @return $this
     */
    public function set_pdf_backend($pdf_backend): self
    {
        $this->pdf_backend = $pdf_backend;
        return $this;
    }
    /**
     * @return string
     */
    public function get_pdf_backend()
    {
        return $this->pdf_backend;
    }
    /**
     * @param string $pdflibLicense
     * @return $this
     */
    public function set_pdflib_license($pdflib_license): self
    {
        $this->pdflib_license = $pdflib_license;
        return $this;
    }
    /**
     * @return string
     */
    public function get_pdflib_license()
    {
        return $this->pdflib_license;
    }
    /**
     * @param array|string $chroot
     * @return $this
     */
    public function set_chroot($chroot, $delimiter = ','): self
    {
        if (is_string($chroot)) {
            $this->chroot = explode($delimiter, $chroot);
        } elseif (is_array($chroot)) {
            $this->chroot = $chroot;
        }
        return $this;
    }
    /**
     * @return array
     */
    public function get_allowed_protocols()
    {
        return $this->allowed_protocols;
    }
    /**
     * @param array $allowedProtocols The protocols to allow, as an array
     * formatted as ["protocol://" => ["rules" => [callable]], ...]
     * or ["protocol://", ...]
     *
     * @return $this
     */
    public function set_allowed_protocols(array $allowed_protocols): self
    {
        $protocols = [];
        foreach ($allowed_protocols as $protocol => $config) {
            if (is_string($protocol)) {
                $protocols[$protocol] = [];
                if (is_array($config)) {
                    $protocols[$protocol] = $config;
                }
            } elseif (is_string($config)) {
                $protocols[$config] = [];
            }
        }
        $this->allowed_protocols = [];
        foreach ($protocols as $protocol => $config) {
            $this->add_allowed_protocol($protocol, ...$config['rules'] ?? []);
        }
        return $this;
    }
    /**
     * Adds a new protocol to the allowed protocols collection
     *
     * @param string $protocol The scheme to add (e.g. "http://")
     * @param callable $rule A callable that validates the protocol
     * @return $this
     */
    public function add_allowed_protocol(string $protocol, callable ...$rules): self
    {
        $protocol = strtolower($protocol);
        if (empty($rules)) {
            $rules = [];
            switch ($protocol) {
                case 'data://':
                    break;
                case 'file://':
                    $rules[] = [$this, 'validateLocalUri'];
                    break;
                case 'http://':
                case 'https://':
                    $rules[] = [$this, 'validateRemoteUri'];
                    break;
                case 'phar://':
                    $rules[] = [$this, 'validatePharUri'];
                    break;
            }
        }
        $this->allowed_protocols[$protocol] = ['rules' => $rules];
        return $this;
    }
    /**
     * @return array
     */
    public function get_artifact_path_validation()
    {
        return $this->artifact_path_validation;
    }
    /**
     * @param callable $validator
     * @return $this
     */
    public function set_artifact_path_validation($validator): self
    {
        $this->artifact_path_validation = $validator;
        return $this;
    }
    public function get_chroot(): array
    {
        if (is_array($this->chroot)) {
            return $this->chroot;
        }
        return [];
    }
    /**
     * @param boolean $debugCss
     * @return $this
     */
    public function set_debug_css($debug_css): self
    {
        $this->debug_css = $debug_css;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_debug_css()
    {
        return $this->debug_css;
    }
    /**
     * @param boolean $debugKeepTemp
     * @return $this
     */
    public function set_debug_keep_temp($debug_keep_temp): self
    {
        $this->debug_keep_temp = $debug_keep_temp;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_debug_keep_temp()
    {
        return $this->debug_keep_temp;
    }
    /**
     * @param boolean $debugLayout
     * @return $this
     */
    public function set_debug_layout($debug_layout): self
    {
        $this->debug_layout = $debug_layout;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_debug_layout()
    {
        return $this->debug_layout;
    }
    /**
     * @param boolean $debugLayoutBlocks
     * @return $this
     */
    public function set_debug_layout_blocks($debug_layout_blocks): self
    {
        $this->debug_layout_blocks = $debug_layout_blocks;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_debug_layout_blocks()
    {
        return $this->debug_layout_blocks;
    }
    /**
     * @param boolean $debugLayoutInline
     * @return $this
     */
    public function set_debug_layout_inline($debug_layout_inline): self
    {
        $this->debug_layout_inline = $debug_layout_inline;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_debug_layout_inline()
    {
        return $this->debug_layout_inline;
    }
    /**
     * @param boolean $debugLayoutLines
     * @return $this
     */
    public function set_debug_layout_lines($debug_layout_lines): self
    {
        $this->debug_layout_lines = $debug_layout_lines;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_debug_layout_lines()
    {
        return $this->debug_layout_lines;
    }
    /**
     * @param boolean $debugLayoutPaddingBox
     * @return $this
     */
    public function set_debug_layout_padding_box($debug_layout_padding_box): self
    {
        $this->debug_layout_padding_box = $debug_layout_padding_box;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_debug_layout_padding_box()
    {
        return $this->debug_layout_padding_box;
    }
    /**
     * @param boolean $debugPng
     * @return $this
     */
    public function set_debug_png($debug_png): self
    {
        $this->debug_png = $debug_png;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_debug_png()
    {
        return $this->debug_png;
    }
    /**
     * @param string $defaultFont
     * @return $this
     */
    public function set_default_font($default_font): self
    {
        if (!($default_font === null || trim($default_font) === '')) {
            $this->default_font = $default_font;
        } else {
            $this->default_font = 'serif';
        }
        return $this;
    }
    /**
     * @return string
     */
    public function get_default_font()
    {
        return $this->default_font;
    }
    /**
     * @param string $defaultMediaType
     * @return $this
     */
    public function set_default_media_type($default_media_type): self
    {
        $this->default_media_type = $default_media_type;
        return $this;
    }
    /**
     * @return string
     */
    public function get_default_media_type()
    {
        return $this->default_media_type;
    }
    /**
     * @param string|float[] $defaultPaperSize
     * @return $this
     */
    public function set_default_paper_size($default_paper_size): self
    {
        $this->default_paper_size = $default_paper_size;
        return $this;
    }
    /**
     * @return $this
     */
    public function set_default_paper_orientation(string $default_paper_orientation): self
    {
        $this->default_paper_orientation = $default_paper_orientation;
        return $this;
    }
    /**
     * @return string|float[]
     */
    public function get_default_paper_size()
    {
        return $this->default_paper_size;
    }
    public function get_default_paper_orientation(): string
    {
        return $this->default_paper_orientation;
    }
    /**
     * @param int $dpi
     * @return $this
     */
    public function set_dpi($dpi): self
    {
        $this->dpi = $dpi;
        return $this;
    }
    /**
     * @return int
     */
    public function get_dpi()
    {
        return $this->dpi;
    }
    /**
     * @param string $fontCache
     * @return $this
     */
    public function set_font_cache($font_cache): self
    {
        if (!is_callable($this->artifact_path_validation) || ($this->artifact_path_validation)($font_cache, 'fontCache') === true) {
            $this->font_cache = $font_cache;
        }
        return $this;
    }
    /**
     * @return string
     */
    public function get_font_cache()
    {
        return $this->font_cache;
    }
    /**
     * @param string $fontDir
     * @return $this
     */
    public function set_font_dir($font_dir): self
    {
        if (!is_callable($this->artifact_path_validation) || ($this->artifact_path_validation)($font_dir, 'fontDir') === true) {
            $this->font_dir = $font_dir;
        }
        return $this;
    }
    /**
     * @return string
     */
    public function get_font_dir()
    {
        return $this->font_dir;
    }
    /**
     * @param float $fontHeightRatio
     * @return $this
     */
    public function set_font_height_ratio($font_height_ratio): self
    {
        $this->font_height_ratio = $font_height_ratio;
        return $this;
    }
    /**
     * @return float
     */
    public function get_font_height_ratio()
    {
        return $this->font_height_ratio;
    }
    /**
     * @param boolean $isFontSubsettingEnabled
     * @return $this
     */
    public function set_is_font_subsetting_enabled($is_font_subsetting_enabled): self
    {
        $this->is_font_subsetting_enabled = $is_font_subsetting_enabled;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_is_font_subsetting_enabled()
    {
        return $this->is_font_subsetting_enabled;
    }
    /**
     * @return boolean
     */
    public function is_font_subsetting_enabled()
    {
        return $this->get_is_font_subsetting_enabled();
    }
    /**
     * @deprecated
     * @param boolean $isHtml5ParserEnabled
     * @return $this
     */
    public function set_is_html5parser_enabled($is_html5parser_enabled): self
    {
        $this->is_html5parser_enabled = $is_html5parser_enabled;
        return $this;
    }
    /**
     * @deprecated
     * @return boolean
     */
    public function get_is_html5parser_enabled()
    {
        return $this->is_html5parser_enabled;
    }
    /**
     * @deprecated
     * @return boolean
     */
    public function is_html5parser_enabled()
    {
        return $this->get_is_html5parser_enabled();
    }
    /**
     * @param boolean $isJavascriptEnabled
     * @return $this
     */
    public function set_is_javascript_enabled($is_javascript_enabled): self
    {
        $this->is_javascript_enabled = $is_javascript_enabled;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_is_javascript_enabled()
    {
        return $this->is_javascript_enabled;
    }
    /**
     * @return boolean
     */
    public function is_javascript_enabled()
    {
        return $this->get_is_javascript_enabled();
    }
    /**
     * @param boolean $isPhpEnabled
     * @return $this
     */
    public function set_is_php_enabled($is_php_enabled): self
    {
        $this->is_php_enabled = $is_php_enabled;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_is_php_enabled()
    {
        return $this->is_php_enabled;
    }
    /**
     * @return boolean
     */
    public function is_php_enabled()
    {
        return $this->get_is_php_enabled();
    }
    /**
     * @param boolean $isRemoteEnabled
     * @return $this
     */
    public function set_is_remote_enabled($is_remote_enabled): self
    {
        $this->is_remote_enabled = $is_remote_enabled;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_is_remote_enabled()
    {
        return $this->is_remote_enabled;
    }
    /**
     * @return boolean
     */
    public function is_remote_enabled()
    {
        return $this->get_is_remote_enabled();
    }
    /**
     * @param array|null $allowedRemoteHosts
     * @return $this
     */
    public function set_allowed_remote_hosts($allowed_remote_hosts): self
    {
        if (is_array($allowed_remote_hosts)) {
            // Set hosts to lowercase
            foreach ($allowed_remote_hosts as &$host) {
                $host = mb_strtolower($host, 'UTF-8');
            }
            unset($host);
        }
        $this->allowed_remote_hosts = $allowed_remote_hosts;
        return $this;
    }
    /**
     * @return array|null
     */
    public function get_allowed_remote_hosts()
    {
        return $this->allowed_remote_hosts;
    }
    /**
     * @param boolean $isRemoteEnabled
     * @return $this
     */
    public function set_is_pdf_a_enabled($is_pdf_a_enabled): self
    {
        $this->is_pdf_a_enabled = $is_pdf_a_enabled;
        return $this;
    }
    /**
     * @return boolean
     */
    public function get_is_pdf_a_enabled()
    {
        return $this->is_pdf_a_enabled;
    }
    /**
     * @return boolean
     */
    public function is_pdf_a_enabled()
    {
        return $this->get_is_pdf_a_enabled();
    }
    /**
     * @param string $logOutputFile
     * @return $this
     */
    public function set_log_output_file($log_output_file): self
    {
        if (!is_callable($this->artifact_path_validation) || ($this->artifact_path_validation)($log_output_file, 'logOutputFile') === true) {
            $this->log_output_file = $log_output_file;
        }
        return $this;
    }
    /**
     * @return string
     */
    public function get_log_output_file()
    {
        return $this->log_output_file;
    }
    /**
     * @param string $tempDir
     * @return $this
     */
    public function set_temp_dir($temp_dir): self
    {
        if (!is_callable($this->artifact_path_validation) || ($this->artifact_path_validation)($temp_dir, 'tempDir') === true) {
            $this->temp_dir = $temp_dir;
        }
        return $this;
    }
    /**
     * @return string
     */
    public function get_temp_dir()
    {
        return $this->temp_dir;
    }
    /**
     * @param string $rootDir
     * @return $this
     */
    public function set_root_dir($root_dir): self
    {
        if (!is_callable($this->artifact_path_validation) || ($this->artifact_path_validation)($root_dir, 'rootDir') === true) {
            $this->root_dir = $root_dir;
        }
        return $this;
    }
    /**
     * @return string
     */
    public function get_root_dir()
    {
        return $this->root_dir;
    }
    /**
     * Sets the HTTP context
     *
     * @param resource|array $httpContext
     * @return $this
     */
    public function set_http_context($http_context): self
    {
        $this->http_context = is_array($http_context) ? stream_context_create($http_context) : $http_context;
        return $this;
    }
    /**
     * Returns the HTTP context
     *
     * @return resource
     */
    public function get_http_context()
    {
        return $this->http_context;
    }
    public function validate_artifact_path(?string $path, string $option): bool
    {
        if ($path === null) {
            return true;
        }
        $parsed_uri = parse_url($path);
        if ($parsed_uri === false || array_key_exists('scheme', $parsed_uri) && strtolower($parsed_uri['scheme']) === 'phar') {
            return false;
        }
        return true;
    }
    public function validate_local_uri(string $uri): array
    {
        if (strlen($uri) === 0) {
            return [false, 'The URI must not be empty.'];
        }
        $realfile = realpath(str_replace('file://', '', $uri));
        $dirs = $this->chroot;
        $dirs[] = $this->root_dir;
        $chroot_valid = false;
        foreach ($dirs as $chroot_path) {
            $chroot_path = realpath($chroot_path);
            if ($chroot_path !== false && strpos($realfile, $chroot_path . DIRECTORY_SEPARATOR) === 0) {
                $chroot_valid = true;
                break;
            }
        }
        if ($chroot_valid !== true) {
            return [false, 'Permission denied. The file could not be found under the paths specified by Options::chroot.'];
        }
        if (!$realfile) {
            return [false, 'File not found.'];
        }
        return [true, null];
    }
    public function validate_phar_uri(string $uri)
    {
        if (strlen($uri) === 0) {
            return [false, 'The URI must not be empty.'];
        }
        $file = substr(substr($uri, 0, strpos($uri, '.phar') + 5), 7);
        return $this->validate_local_uri($file);
    }
    public function validate_remote_uri(string $uri): array
    {
        if (strlen($uri) === 0) {
            return [false, 'The URI must not be empty.'];
        }
        $scheme = strtolower(parse_url($uri, PHP_URL_SCHEME) ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return [false, 'Remote URI must use http or https protocol.'];
        }
        if (!$this->is_remote_enabled) {
            return [false, 'Remote file requested, but remote file download is disabled.'];
        }
        if (is_array($this->allowed_remote_hosts) && count($this->allowed_remote_hosts) > 0) {
            $host = parse_url($uri, PHP_URL_HOST);
            $host = mb_strtolower($host, 'UTF-8');
            if (!in_array($host, $this->allowed_remote_hosts, true)) {
                return [false, 'Remote host is not in allowed list: ' . $host];
            }
        }
        return [true, null];
    }
}