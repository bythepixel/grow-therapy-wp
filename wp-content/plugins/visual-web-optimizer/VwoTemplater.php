<?php
defined( 'ABSPATH' ) or exit;

class VwoTemplater {
    protected $dirs = [];

    public function __construct( array $dirs = [ '' ] ) {
        $this->dirs = $dirs;
    }

    protected function find( $file ) {
        foreach ( $this->dirs as $dir ) {
            if ( is_file( $path = ( $dir ? trailingslashit( $dir ) : '' ) . $file ) ) return $path;
            elseif ( is_file( $path .= '.php' ) ) return $path;
        }
        return false;
    }

    public function render( string $file, array $vars = [] ) {
        if ( ! $file = $this->find( $file ) ) {
            return false;
        }
        if ( $vars ) extract( $vars, EXTR_SKIP );

        ob_start();
        include $file;

        return ob_get_clean();
    }
}
