<?php 

if ( !class_exists( 'bkap_approve_booking' ) ) {
	
	class bkap_approve_booking {
		private $slug = NULL;
		private $title = NULL;
		private $content = NULL;
		private $author = NULL;
		private $date = NULL;
		private $type = NULL;

		public function __construct( $args ) {
		    
			if ( !isset( $args[ 'slug' ] ) ) {
				throw new Exception( 'No slug given for page' );
			}

			$this->slug    = $args[ 'slug' ];
			$this->title   = isset( $args[ 'title' ] ) ? $args[ 'title' ] : '';
			$this->content = isset( $args[ 'content' ] ) ? $args[ 'content' ] : '';
			$this->author  = isset( $args[ 'author' ] ) ? $args[ 'author' ] : 1;
			$this->date    = isset( $args[ 'date' ] ) ? $args[ 'date' ] : current_time( 'mysql' );
			$this->dategmt = isset( $args[ 'date' ] ) ? $args[ 'date' ] : current_time( 'mysql', 1 );
			$this->type    = isset( $args[ 'type' ] ) ? $args[ 'type' ] : 'page';

			add_action( 'booking_page_woocommerce_history_page', array( &$this, 'create_virtual_page' ) );
			add_action( 'booking_page_operator_bookings', array( &$this, 'create_virtual_page' ) );
		}

		// filter to create virtual page content for Tell a Friend page
		public function create_virtual_page( ) {
		    echo $this->content;
		}
	}
}