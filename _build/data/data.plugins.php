<?php
return array(
    array(
        'name' => 'pdfThumbs',
        'description' => 'Handles the fetching and processing of PDF thumbnails',
        'file' => 'plugin.pdfthumbs.php',
        'events' => array(
            array('event' => 'OnPageNotFound', 'priority' => 0, 'propertyset' => 0),
        ),
    ),
);