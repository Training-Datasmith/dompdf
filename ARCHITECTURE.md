# Architecture: dompdf

## Purpose

A PHP library that converts HTML + CSS to PDF. It parses HTML into a DOM tree, applies CSS styles (including floats, tables, and positioned elements), performs a CSS layout pass, and renders the result to a PDF canvas using the CPDF PDF generation library.

## Directory Structure

```
src/
  Dompdf.php                     — Primary API: load_html(), render(), output(), stream()
  Options.php                    — Configuration: paper size, DPI, font directories, remote content policy
  Helpers.php                    — URL normalisation, color parsing, length conversion utilities

  Css/
    Stylesheet.php               — CSS parser and cascade resolver
    Style.php                    — Computed style container for a single element
    Attribute_Translator.php     — Converts deprecated HTML attributes (align, border) to CSS
    Color.php                    — Color format parsing and normalisation
    Content/                     — CSS `content:` property value objects (attr(), counter(), url(), etc.)

  Frame.php                      — Single DOM node wrapper (element + computed style + box model data)
  Frame/
    Frame_Tree.php               — Builds the frame tree from DOMDocument
    Factory.php                  — Creates Frame objects from DOM nodes
    Frame_Tree_Iterator.php      — Traverses the frame tree

  FrameDecorator/
    Abstract_Frame_Decorator.php — Decorator base: wraps a Frame with layout-type-specific behaviour
    Block.php / Inline.php / Table.php / Image.php / Text.php / ...

  FrameReflower/
    Abstract_Frame_Reflower.php  — Performs CSS layout (reflowing) for a given frame type
    Block.php / Inline.php / Table.php / Image.php / Text.php / ...

  Positioner/
    Abstract_Positioner.php      — Positions a frame on the page per CSS positioning model
    Block.php / Absolute.php / Fixed.php / ...

  Renderer/
    Abstract_Renderer.php        — Draws a laid-out frame to the PDF canvas
    Block.php / Inline.php / Text.php / Image.php / Table*.php / ...

  Canvas.php                     — Interface for PDF output (draws lines, text, images)
  Canvas_Factory.php             — Creates the correct canvas implementation
  Adapter/
    CPDF.php                     — Main canvas: PDF output via Phenx\PhpFontLib + CPDF
    GD.php                       — Raster canvas (PNG output, for debugging)
    PDF_Lib.php                  — Alternative PDF library adapter

  Font_Metrics.php               — Font width/height measurement for text layout
  Image/Cache.php                — Downloads and caches remote images
  Line_Box.php                   — Inline formatting context: collects inline frames into lines
  Cellmap.php                    — Table cell coordinate map for colspan/rowspan resolution
```

## Key Design Decisions

- **Three-pass rendering** — (1) build frame tree from DOMDocument, (2) reflow (layout) each frame computing width/height/position, (3) render each frame to the PDF canvas. Reflowing and rendering are decoupled.
- **Decorator pattern** — `FrameDecorator` classes add layout-type behaviour to raw `Frame` objects without subclassing the frame itself.
- **CSS cascade** — `Stylesheet` resolves specificity, inheritance, and `!important` to produce computed styles per element.
- **CPDF canvas** — the default output backend writes raw PDF bytes via the bundled CPDF library; no external binary required.
- **Remote content policy** — the `Options` class controls whether remote stylesheets, images, and fonts are fetched, mitigating SSRF risks.

## Extension Points

- Implement `Canvas` to add a new output backend (e.g., SVG).
- Subclass `Options` or inject a custom `GuzzleHttp\Client` for custom HTTP behaviour when fetching remote resources.

## Dependency Flow

```
Dompdf::load_html(html)
  └── DOMDocument::loadHTML()
        └── Frame_Tree::build()  → Frame tree
              └── Stylesheet::apply_styles()  → computed styles per frame
                    └── FrameReflower::reflow()  → dimensions + positions
                          └── Renderer::render()  → CPDF canvas commands
                                → PDF bytes output
```
