<?php

// Mock WordPress functions if they don't exist
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, ...$args ) {
		return $args[0];
	}
}

if ( ! function_exists( 'has_action' ) ) {
	function has_action( $hook, $callback = false ) {
		return true;
	}
}

if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( $hook, $callback = false ) {
		return true;
	}
}
