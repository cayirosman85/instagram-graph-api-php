<?php



/**
 * Register the autoloader for the Instagram Graph API PHP SDK classes.
 *
 * @param string $class rhe fully-qualified class name.
 * @return void
 */
spl_autoload_register( function ( $class ) {
    // project-specific namespace prefix
    $prefix = 'Instagram\\';

    // require our file
    require substr( $class, strlen( $prefix ) ) . '.php';
} );
?>