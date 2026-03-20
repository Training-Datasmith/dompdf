<?php

declare (strict_types=1);
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

use Font_Lib\Font;
/**
 * The font metrics class
 *
 * This class provides information about fonts and text.  It can resolve
 * font names into actual installed font files, as well as determine the
 * size of text in a particular font and size.
 *
 * @static
 * @package dompdf
 */
class Font_Metrics
{
    /**
     * Name of the user font families lookup cache file
     *
     * This file must be readable and writable by the executing (webserver)
     * process in order to cache user installed font information.
     */
    public const USER_FONTS_FILE = 'installed-fonts.json';
    /**
     * Underlying {@link Canvas} object to perform text size calculations
     *
     * @var Canvas
     */
    protected $canvas;
    /**
     * Array of bundled font family names to variants
     *
     * @var array
     */
    protected $bundled_fonts = [];
    /**
     * Array of user defined font family names to variants
     *
     * @var array
     */
    protected $user_fonts = [];
    /**
     * combined list of all font families with absolute paths
     *
     * @var array
     */
    protected $font_families;
    /**
     * @var Options
     */
    private $options;
    /**
     * Class initialization
     */
    public function __construct(Canvas $canvas, Options $options)
    {
        $this->set_canvas($canvas);
        $this->set_options($options);
        $this->load_font_families();
    }
    /**
     * @deprecated
     */
    public function save_font_families(): void
    {
        $this->save_font_families();
    }
    /**
     * Saves the stored font family cache
     *
     * The name and location of the cache file are determined by {@link
     * FontMetrics::USER_FONTS_FILE}. This file should be writable by the
     * webserver process.
     *
     * @see FontMetrics::loadFontFamilies()
     */
    public function save_font_families(): void
    {
        file_put_contents($this->get_user_fonts_file_path(), json_encode($this->user_fonts, JSON_PRETTY_PRINT));
    }
    /**
     * @deprecated
     */
    public function load_font_families(): void
    {
        $this->load_font_families();
    }
    /**
     * Loads the stored font family cache
     *
     * @see FontMetrics::saveFontFamilies()
     */
    public function load_font_families(): void
    {
        $file = $this->options->get_root_dir() . '/lib/fonts/installed-fonts.dist.json';
        $this->bundled_fonts = json_decode(file_get_contents($file), true);
        if (is_readable($this->get_user_fonts_file_path())) {
            $this->user_fonts = json_decode(file_get_contents($this->get_user_fonts_file_path()), true);
        } else {
            $this->load_font_families_legacy();
        }
    }
    private function load_font_families_legacy(): void
    {
        $legacy_cache_file = $this->options->get_font_dir() . '/dompdf_font_family_cache.php';
        if (is_readable($legacy_cache_file)) {
            $font_dir = $this->options->get_font_dir();
            $root_dir = $this->options->get_root_dir();
            $cache_data_closure = require $legacy_cache_file;
            $cache_data = is_array($cache_data_closure) ? $cache_data_closure : $cache_data_closure($font_dir, $root_dir);
            if (is_array($cache_data)) {
                foreach ($cache_data as $family => $variants) {
                    if (!isset($this->bundled_fonts[$family]) && is_array($variants)) {
                        foreach ($variants as $variant => $variant_path) {
                            $variant_name = basename($variant_path);
                            $variant_dir = dirname($variant_path);
                            if ($variant_dir == $font_dir) {
                                $this->user_fonts[$family][$variant] = $variant_name;
                            } else {
                                $this->user_fonts[$family][$variant] = $variant_path;
                            }
                        }
                    }
                }
                $this->save_font_families();
            }
        }
    }
    /**
     * @param array $style
     * @param string $remote_file
     * @param resource $context
     * @return bool
     * @deprecated
     */
    public function register_font($style, $remote_file, $context = null)
    {
        return $this->register_font($style, $remote_file);
    }
    /**
     * @param string $remoteFile
     * @param resource $context
     */
    public function register_font(array $style, $remote_file, $context = null): bool
    {
        $fontname = mb_strtolower($style['family'], 'UTF-8');
        $families = $this->get_font_families();
        $entry = [];
        if (isset($families[$fontname])) {
            $entry = $families[$fontname];
        }
        $style_string = $this->get_type("{$style['weight']} {$style['style']}");
        $remote_hash = md5($remote_file);
        $prefix = $fontname . '_' . $style_string;
        $prefix = trim($prefix, '-');
        if (function_exists('iconv')) {
            $prefix = @iconv('utf-8', 'us-ascii//TRANSLIT', $prefix);
        }
        $prefix_encoding = mb_detect_encoding($prefix, mb_detect_order(), true);
        $substchar = mb_substitute_character();
        mb_substitute_character(0x5f);
        $prefix = mb_convert_encoding($prefix, 'ISO-8859-1', $prefix_encoding);
        mb_substitute_character($substchar);
        $prefix = preg_replace("[\\W]", '_', $prefix);
        $prefix = preg_replace("/[^-_\\w]+/", '', $prefix);
        $local_file = $prefix . '_' . $remote_hash;
        $local_file_path = $this->get_options()->get_font_dir() . '/' . $local_file;
        if (isset($entry[$style_string]) && $local_file_path == $entry[$style_string]) {
            return true;
        }
        $entry[$style_string] = $local_file;
        // Download the remote file
        [$protocol] = Helpers::explode_url($remote_file);
        $allowed_protocols = $this->options->get_allowed_protocols();
        if (!array_key_exists($protocol, $allowed_protocols)) {
            Helpers::record_warnings(E_USER_WARNING, "Permission denied on {$remote_file}. The communication protocol is not supported.", __FILE__, __LINE__);
            return false;
        }
        foreach ($allowed_protocols[$protocol]['rules'] as $rule) {
            [$result, $message] = $rule($remote_file);
            if ($result !== true) {
                Helpers::record_warnings(E_USER_WARNING, "Error loading {$remote_file}: {$message}", __FILE__, __LINE__);
                return false;
            }
        }
        [$remote_file_content, $http_response_header] = @Helpers::get_file_content($remote_file, $context);
        if ($remote_file_content === null) {
            return false;
        }
        $local_temp_file = @tempnam($this->options->get('tempDir'), 'dompdf-font-');
        file_put_contents($local_temp_file, $remote_file_content);
        $font = Font::load($local_temp_file);
        if (!$font) {
            unlink($local_temp_file);
            return false;
        }
        $font->parse();
        $font->save_adobe_font_metrics("{$local_file_path}.ufm");
        $font->close();
        unlink($local_temp_file);
        if (!file_exists("{$local_file_path}.ufm")) {
            return false;
        }
        $font_extension = '.ttf';
        switch ($font->get_font_type()) {
            case 'TrueType':
            default:
                $font_extension = '.ttf';
                break;
        }
        // Save the changes
        file_put_contents($local_file_path . $font_extension, $remote_file_content);
        if (!file_exists($local_file_path . $font_extension)) {
            unlink("{$local_file_path}.ufm");
            return false;
        }
        $this->set_font_family($fontname, $entry);
        return true;
    }
    /**
     * @param $text
     * @param $font
     * @param $size
     * @deprecated
     */
    public function get_text_width(string $text, $font, float $size, float $word_spacing = 0.0, float $char_spacing = 0.0): float
    {
        //return self::$_pdf->get_text_width($text, $font, $size, $word_spacing, $char_spacing);
        return $this->get_text_width($text, $font, $size, $word_spacing, $char_spacing);
    }
    /**
     * Calculates text size, in points
     *
     * @param string $text        The text to be sized
     * @param string $font        The font file to use
     * @param float  $size        The font size, in points
     * @param float  $wordSpacing Word spacing, if any
     * @param float  $charSpacing Char spacing, if any
     */
    public function get_text_width(string $text, $font, float $size, float $word_spacing = 0.0, float $char_spacing = 0.0): float
    {
        // @todo Make sure this cache is efficient before enabling it
        static $cache = [];
        if ($text === '') {
            return 0;
        }
        // Don't cache long strings
        $use_cache = !isset($text[50]);
        // Faster than strlen
        // Text-size calculations depend on the canvas used. Make sure to not
        // return wrong values when switching canvas backends
        $canvas_class = get_class($this->canvas);
        $key = "{$canvas_class}/{$font}/{$size}/{$word_spacing}/{$char_spacing}";
        if ($use_cache && isset($cache[$key][$text])) {
            return $cache[$key][$text];
        }
        $width = $this->canvas->get_text_width($text, $font, $size, $word_spacing, $char_spacing);
        if ($use_cache) {
            $cache[$key][$text] = $width;
        }
        return $width;
    }
    /**
     * Maps substrings of text against the provided font list. This is achieved by
     * parsing each character of the string against the supported glyphs for each
     * font. Fonts preference is based on the order of the font list.
     *
     * Returns an array containing substring information that indicates the
     * matched font (if any), start index, substring length, and (optionally)
     * the actual text of the substring.
     *
     * @param string $text            The text to map
     * @param array  $fontFamilies    List of font families to map against
     * @param string $subtype         The font subtype (italic, bold, etc.)
     * @param int    $count           The number of matches to return
     * @param bool   $returnSubstring Should the actual matched text be returned
     */
    public function map_text_to_fonts(string $text, array $font_families, string $subtype = 'normal', int $count = -1, bool $return_substring = false): array
    {
        $char_mapping = [];
        $fonts = [];
        foreach ($font_families as $family) {
            $font = $this->get_font($family, $subtype);
            if ($font !== null) {
                $fonts[] = $font;
            }
        }
        if (function_exists('mb_str_split')) {
            $char_array = mb_str_split($text, 1, 'UTF-8');
        } else {
            $char_array = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        }
        $start_index = 0;
        $char_index = -1;
        while (isset($char_array[++$char_index])) {
            $char = $char_array[$char_index];
            if (preg_match('/[\x00-\x1F\x7F]/u', $char)) {
                //non-printable, moving on
                continue;
            }
            $mapped_font = null;
            foreach ($fonts as $font) {
                if ($this->canvas->font_supports_char($font, $char)) {
                    $mapped_font = $font;
                    break;
                }
            }
            if (!isset($char_mapping[$start_index])) {
                $char_mapping[$start_index] = ['font' => $mapped_font, 'length' => 0, 'text' => null];
            }
            if ($mapped_font !== $char_mapping[$start_index]['font']) {
                $char_mapping[$start_index]['length'] = $char_index - $start_index;
                if ($count > 0 && count($char_mapping) === $count) {
                    break;
                }
                $start_index = $char_index;
                $char_mapping[$start_index] = ['font' => $mapped_font, 'length' => 0, 'text' => null];
            }
        }
        if ($return_substring) {
            // build the string for each mapping
            foreach ($char_mapping as $start_index => &$info) {
                $info['text'] = mb_substr($text, $start_index, $info['length'], 'UTF-8');
            }
        }
        return $char_mapping;
    }
    /**
     * @param $font
     * @param $size
     * @deprecated
     */
    public function get_font_height($font, float $size): float
    {
        return $this->get_font_height($font, $size);
    }
    /**
     * Calculates font height, in points
     *
     * @param string $font The font file to use
     * @param float  $size The font size, in points
     */
    public function get_font_height($font, float $size): float
    {
        return $this->canvas->get_font_height($font, $size);
    }
    /**
     * Calculates font baseline, in points
     *
     * @param string $font The font file to use
     * @param float  $size The font size, in points
     */
    public function get_font_baseline($font, float $size): float
    {
        return $this->canvas->get_font_baseline($font, $size);
    }
    /**
     * @param $family_raw
     * @param string $subtype_raw
     * @return string
     * @deprecated
     */
    public function get_font($family_raw, $subtype_raw = 'normal')
    {
        return $this->get_font($family_raw, $subtype_raw);
    }
    /**
     * Resolves a font family & subtype into an actual font file
     * Subtype can be one of 'normal', 'bold', 'italic' or 'bold_italic'.  If
     * the particular font family has no suitable font file, the default font
     * ({@link Options::defaultFont}) is used.  The font file returned
     * is the absolute pathname to the font file on the system.
     *
     * @param string|null $familyRaw
     * @param string      $subtypeRaw
     *
     * @return string|null
     */
    public function get_font($family_raw, $subtype_raw = 'normal')
    {
        static $cache = [];
        if (!$family_raw) {
            $family_raw = $family_raw === null ? 0 : $this->options->get_default_font();
        }
        if (!$subtype_raw) {
            $subtype_raw = 'normal';
        }
        if (isset($cache[$family_raw][$subtype_raw])) {
            return $cache[$family_raw][$subtype_raw];
        }
        /* Allow calling for various fonts in search path. Therefore not immediately
         * return replacement on non match.
         * Only when called with NULL try replacement.
         * When this is also missing there is really trouble.
         * If only the subtype fails, nevertheless return failure.
         * Only on checking the fallback font, check various subtypes on same font.
         */
        $subtype = strtolower($subtype_raw);
        $families = $this->get_font_families();
        if ($family_raw) {
            $family = str_replace(["'", '"'], '', strtolower($family_raw));
            if (isset($families[$family][$subtype])) {
                return $cache[$family_raw][$subtype_raw] = $families[$family][$subtype];
            }
            return null;
        }
        $fallback_families = [strtolower($this->options->get_default_font()), 'serif'];
        foreach ($fallback_families as $family) {
            if (isset($families[$family][$subtype])) {
                return $cache[$family_raw][$subtype_raw] = $families[$family][$subtype];
            }
            if (!isset($families[$family])) {
                continue;
            }
            $family = $families[$family];
            foreach ($family as $sub => $font) {
                if (strpos($subtype, $sub) !== false) {
                    return $cache[$family_raw][$subtype_raw] = $font;
                }
            }
            if ($subtype !== 'normal') {
                foreach ($family as $sub => $font) {
                    if ($sub !== 'normal') {
                        return $cache[$family_raw][$subtype_raw] = $font;
                    }
                }
            }
            $subtype = 'normal';
            if (isset($family[$subtype])) {
                return $cache[$family_raw][$subtype_raw] = $family[$subtype];
            }
        }
        return null;
    }
    /**
     * @param $family
     * @return null|string
     * @deprecated
     */
    public function get_family($family)
    {
        return $this->get_family($family);
    }
    /**
     * @param string $family
     * @return null|string
     */
    public function get_family($family)
    {
        $family = str_replace(["'", '"'], '', mb_strtolower($family, 'UTF-8'));
        $families = $this->get_font_families();
        return $families[$family] ?? null;
    }
    /**
     * @param $type
     * @return string
     * @deprecated
     */
    public function get_type($type)
    {
        return $this->get_type($type);
    }
    /**
     * @param string $type
     * @return string
     */
    public function get_type($type)
    {
        if (preg_match('/bold/i', $type)) {
            $weight = 700;
        } elseif (preg_match('/([1-9]00)/', $type, $match)) {
            $weight = (int) $match[0];
        } else {
            $weight = 400;
        }
        $weight = $weight === 400 ? 'normal' : $weight;
        $weight = $weight === 700 ? 'bold' : $weight;
        $style = preg_match('/italic|oblique/i', $type) ? 'italic' : null;
        if ($weight === 'normal' && $style !== null) {
            return $style;
        }
        return $style === null ? $weight : $weight . '_' . $style;
    }
    /**
     * @return array
     * @deprecated
     */
    public function get_font_families()
    {
        return $this->get_font_families();
    }
    /**
     * Returns the current font lookup table
     *
     * @return array
     */
    public function get_font_families()
    {
        if (!isset($this->font_families)) {
            $this->set_font_families();
        }
        return $this->font_families;
    }
    /**
     * Convert loaded fonts to font lookup table
     */
    public function set_font_families(): void
    {
        $font_families = [];
        if (isset($this->bundled_fonts) && is_array($this->bundled_fonts)) {
            foreach ($this->bundled_fonts as $family => $variants) {
                if (!isset($font_families[$family])) {
                    $font_families[$family] = array_map(function (string $variant): string {
                        return $this->get_options()->get_root_dir() . '/lib/fonts/' . $variant;
                    }, $variants);
                }
            }
        }
        if (isset($this->user_fonts) && is_array($this->user_fonts)) {
            foreach ($this->user_fonts as $family => $variants) {
                $font_families[$family] = array_map(function ($variant) {
                    $variant_name = basename($variant);
                    if ($variant_name === $variant) {
                        return $this->get_options()->get_font_dir() . '/' . $variant;
                    }
                    return $variant;
                }, $variants);
            }
        }
        $this->font_families = $font_families;
    }
    /**
     * @param string $fontname
     * @param mixed $entry
     * @deprecated
     */
    public function set_font_family($fontname, $entry): void
    {
        $this->set_font_family($fontname, $entry);
    }
    /**
     * @param string $fontname
     * @param mixed $entry
     */
    public function set_font_family($fontname, $entry): void
    {
        $this->user_fonts[mb_strtolower($fontname, 'UTF-8')] = $entry;
        $this->save_font_families();
        unset($this->font_families);
    }
    public function get_user_fonts_file_path(): string
    {
        return $this->options->get_font_dir() . '/' . self::USER_FONTS_FILE;
    }
    /**
     * @return $this
     */
    public function set_options(Options $options): self
    {
        $this->options = $options;
        unset($this->font_families);
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
     * @return $this
     */
    public function set_canvas(Canvas $canvas): self
    {
        $this->canvas = $canvas;
        return $this;
    }
    /**
     * @return Canvas
     */
    public function get_canvas()
    {
        return $this->canvas;
    }
}