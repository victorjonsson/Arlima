<?php
error_reporting(E_ALL);
include 'jQueryTmpl.php';

$data = array
(
    'movies' => array
    (
        array
        (
            'name' => 'The Red Violin',
            'year' => '1998',
            'director' => 'François Girard'
        ),
        array
        (
            'name' => 'Eyes Wide Shut',
            'year' => '1999',
            'director' => 'Stanley Kubrick'
        ),
        array
        (
            'name' => 'The Inheritance',
            'year' => '1976',
            'director' => 'Mauro Bolognini'
        )
    ),
    'greeting' => array
    (
        'name' => 'Xiao',
        'from' => 'Boston'
    )
);

// Create factory classes
$jQueryTmpl_Factory = new jQueryTmpl_Factory();
$jQueryTmpl_Markup_Factory = new jQueryTmpl_Markup_Factory();
$jQueryTmpl_Data_Factory = new jQueryTmpl_Data_Factory();

// Create jQueryTmpl object
$jQueryTmpl = $jQueryTmpl_Factory->create();

// Create some data from our PHP array
$jQueryTmpl_Data = $jQueryTmpl_Data_Factory->createFromArray($data);

// Compile a template using a shared template file, or pass in text
$jQueryTmpl
    ->template
    (
        'movieTemplate',
        $jQueryTmpl_Markup_Factory->createFromFile(__DIR__.'/example.js')
    )
    ->template
    (
        'nameTemplate',
        $jQueryTmpl_Markup_Factory->createFromString('Hello {{=greeting.name}}!')
    );

// Use pre compiled templates to render
$jQueryTmpl
    ->tmpl('movieTemplate', $jQueryTmpl_Data)
    ->renderHtml();

echo "<hr />\n";

// Mix in the use by non compiled templates as well
$rendered = $jQueryTmpl
    ->tmpl('nameTemplate', $jQueryTmpl_Data)
    ->tmpl
    (
        $jQueryTmpl_Markup_Factory->createFromString(' I hear ${greeting.from} is lovely.'),
        $jQueryTmpl_Data
    )
    ->getHtml();

// Do whatever we want with the output, in this case just print it
echo "<pre>$rendered</pre>\n";

