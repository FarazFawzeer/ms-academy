<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** Server-side SVG QR codes (crisp when printed, no JS needed). Needs: composer require bacon/bacon-qr-code */
class Qr
{
    public static function svg(string $text, int $size = 120): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 0), new SvgImageBackEnd());
        $svg      = (new Writer($renderer))->writeString($text);

        // drop the XML declaration at the top so the SVG can be inlined in HTML
        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }
}
