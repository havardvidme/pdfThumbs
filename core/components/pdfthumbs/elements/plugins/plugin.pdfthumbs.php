<?php
/**
 * @package   pdfThumbs
 * @author    Håvard Vidme (havard.vidme@gmail.com)
 */

// Make sure the server has ImageMagick installed
if (!class_exists('Imagick')) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[pdfThumbs] Could not load ImageMagick.');
    return NULL;
}

// Create the base url
$base_url = $modx->getOption('baseUrl').'pdfthumbs/';

// Set cache path, check it and create it if it's not there
$cache_path = $modx->getOption('core_path').'cache/pdfthumbs/';
if (!is_dir($cache_path)) {
    mkdir($cache_path, 0777, true);
}

// Predefined output image formats
$formats = array(
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
);

// Handle events
switch ($modx->event->name) {
    case 'OnPageNotFound':
        if (isset($_REQUEST['q'])) {
            // Readying the url
            $url = ltrim($_SERVER['REQUEST_URI'], '/');
            if (strpos($url, '?') === false) {
                // Make sure there's a question mark to split the url by
                $url .= '?';
            }
            if (strpos($url, $base_url) === 0) {
                // Fetch needed data from url
                $parts = explode('?', $url);
                $src = str_replace($base_url, '', $parts[0]);
                parse_str($parts[1], $options);
                
                // Get all parameters
                $width = abs($modx->getOption('w', $options, 0));
                $height = abs($modx->getOption('h', $options, 0));
                $page = abs($modx->getOption('p', $options, 0));
                $format = $modx->getOption('f', $options, 'jpg');
                
                // Check if either width or height is set
                if ($width || $height) {
                    // Get the filename of the pdf - sans extension – and build a cache file
                    $filename = pathinfo($src, PATHINFO_FILENAME);
                    $cache_file = $cache_path.$filename.'.'.md5('w='.$width.'&h='.$height.'&p='.$page.'&f='.$format).'.'.$format;
                    
                    // If the cache file doesn't exist, do the thing
                    if (!file_exists($cache_file)) {
                        // Initiate ImageMagick, set the dpi and load the pdf
                        $imagick = new imagick();
                        $imagick->setResolution(72, 72);
                        $imagick->readImage($src.'['.$page.']');
                        
                        // Get pdf size
                        $original_width = $imagick->getImageWidth();
                        $original_height = $imagick->getImageHeight();
                        
                        // Set the temporary new sizes
                        $new_width = $width;
                        $new_height = $height;
                        
                        // Bestfit as false, when either width or height is zero
                        $bestfit = false;
                        
                        // Both sizes set - calculate crop
                        if ($width && $height) {
                            // Now we can use bestfit ;)
                            $bestfit = true;
                            
                            // Get the ratios between original and new sizes
                            $ratio_width = $new_width / $original_width;
                            $ratio_height = $new_height / $original_height;
                            
                            // If new width is closer to original width, find actual new height
                            if ($ratio_width > $ratio_height) {
                                $new_width = $original_width * $ratio_width;
                                $new_height = $original_height * $ratio_width;
                            }
                            
                            // If new height is closer to original height, find actual new width
                            if ($ratio_width < $ratio_height) {
                                $new_width = $original_width * $ratio_height;
                                $new_height = $original_height * $ratio_height;
                            }
                        }
                        
                        // Scale the image
                        $imagick->scaleImage($new_width, $new_height, $bestfit);
                        
                        // Check if both sizes is set, again! This time to get the offset for the croping
                        if ($width && $height) {
                            // Default crop offsets
                            $offset_x = 0;
                            $offset_y = 0;
                            
                            // If the new width is greater than the original, calculate the x offset
                            if ($new_width > $width) {
                                $offset_x = round(($new_width - $width) / 2);
                            }
                            
                            // If the new height is greater than the original, calculate the y offset
                            if ($new_height > $height) {
                                $offset_y = round(($new_height - $height) / 1);
                            }
                            
                            // Crop the image
                            $imagick->cropImage($new_width, $new_height, $offset_x, $offset_y);
                        }
                        
                        // Set the image format and save the file to cache
                        $imagick->setImageFormat($format);
                        $imagick->writeImage($cache_file);
                    }
                    
                    // Ready image headers, get the file and print it!
                    header('Content-Type: '.$formats[$format]);
                    header('Content-Disposition: inline; filename='.$filename.'.'.$format);
                    header('Content-Transfer-Encoding: binary');
                    header('Cache-Control: public');
                    header('Pragma: public');
                    header('Content-Length: '.filesize($cache_file));
                    header('Etag: '.md5_file($cache_file));
                    header('Last-Modified: '.gmstrftime('%a, %d %b %Y %T %Z', filemtime($cache_file)));
                    readfile($cache_file);
                }
            }
        }
    break;
    default:
        return NULL;
    break;
}