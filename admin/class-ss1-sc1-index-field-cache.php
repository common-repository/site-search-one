<?php
/**
 * Class for caching stored/enumerable fields temporarily during a sync.
 *
 * @package Site_Search_One
 */

/**
 * Class for caching stored/enumerable fields temporarily during a sync.
 */
class SS1_SC1_Index_Field_Cache {
	/**
	 * Strided list of index_uuid, enumerable field.
	 *
	 * @var array
	 */
	private $enumerable_fields;

	/**
	 * Stided list of index_uuid, stored field.
	 *
	 * @var array
	 */
	private $stored_fields;

	/**
	 * Empty constructor.
	 */
	public function __construct() {
		$this->enumerable_fields = array();
		$this->stored_fields     = array();
	}

	/**
	 * Add an enumerable field to the cache.
	 *
	 * @param string $index_uuid Index UUID.
	 * @param string $enumerable_field Field name.
	 */
	public function add_enumerable_field( $index_uuid, $enumerable_field ) {
		array_push( $this->enumerable_fields, array( $index_uuid, $enumerable_field ) );
	}

	/**
	 * Get the Enumerable fields for a given index.
	 *
	 * @param string $index_uuid The SC1 index uuid.
	 *
	 * @return false|mixed
	 */
	public function get_cached_enumerable_fields( $index_uuid ) {
		foreach ( $this->enumerable_fields as $enumerable_field ) {
			$uuid = $enumerable_field[0];
			if ( $index_uuid === $uuid ) {
				return $enumerable_field[1];
			}
		}
		return false;
	}

	/**
	 * Add a stored field to cache.
	 *
	 * @param string $index_uuid The index uuid.
	 * @param string $stored_field The stored field to add.
	 */
	public function add_stored_fields( $index_uuid, $stored_field ) {
		array_push( $this->stored_fields, array( $index_uuid, $stored_field ) );
	}

	/**
	 * Get the cached stored fields for a given index uuid.
	 *
	 * @param string $index_uuid The index uuid.
	 * @return false|mixed
	 */
	public function get_cached_stored_fields( $index_uuid ) {
		foreach ( $this->stored_fields as $stored_field ) {
			$uuid = $stored_field[0];
			if ( $index_uuid === $uuid ) {
				return $stored_field[1];
			}
		}
		return false;
	}

	/**
	 * Invalidate the cache (Clears it.)
	 */
	public function invalidate() {
		$this->enumerable_fields = array();
		$this->stored_fields     = array();
	}

}
