<?php
/**
 * @package   pdfThumbs
 * @author    Håvard Vidme (havard.vidme@gmail.com)
 */

// Make sure the server has ImageMagick installed
if (!class_exists('Imagick')) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[pdfThumbs] Could not load ImageMagick.');
    return;
}

// Create the base url
$base_url = $modx->getOption('baseUrl').'pdfthumbs/';

$input = !empty($input) ? trim($input) : '';
$options = !empty($options) ? '?'.trim($options) : '';

return empty($input) ? '' : $base_url.$input.$options;