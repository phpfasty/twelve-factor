<?php

// Define your application routes here

Flight::route('/', function(){
    // Access config if needed: $config = Flight::get('config'); // Requires registering config in bootstrap
    // Access template engine:
    echo Flight::template()->render('home', [
        'title' => 'Speed, Security, Minimalism',
        'description' => 'Flight CMS is a modern, lightweight content management system.'
    ]);
});

// Add other routes...
// Flight::route('/about', function(){ ... });
// Flight::route('/admin', ['AdminController', 'index']); // Example using a controller 