<?php
/**
 * Class for handling SC1 Files
 *
 * @package Site_Search_One
 */

/**
 * Class for handling SC1 Files
 */
class SC1_Field {

	/**
	 * The field key.
	 *
	 * @var string
	 */
	public $meta_key;
	/**
	 * The field value.
	 *
	 * @var string
	 */
	public $meta_value;

	/**
	 * Whether or not field is image.
	 *
	 * @var bool
	 */
	private $is_img = false;

	/**
	 * Overriden field data.
	 *
	 * @var string|false
	 */
	private $override_data = false;

	/**
	 * Constructor.
	 *
	 * @param string $meta_key The field key.
	 * @param string $meta_value The field value.
	 */
	public function __construct( $meta_key, $meta_value ) {
		$this->meta_key   = $meta_key;
		$this->meta_value = $meta_value;
	}

	/**
	 * Override the data that SC1 will index instead of using WordPress db
	 *
	 * @param string $dsp_name Display name of field.
	 * @param string $field_value Overriden field value.
	 * @param false  $is_enumerable Whether or not to mark the field as enumerable on SC1.
	 * @param false  $is_select Whether or not to mark the field as a select on SC1.
	 */
	public function override_return_data( $dsp_name, $field_value, $is_enumerable = false, $is_select = false ) {
		$this->override_data = array(
			'dsp_name'      => $dsp_name,
			'field_value'   => $field_value,
			'is_enumerable' => $is_enumerable,
			'is_select'     => $is_select,
		);
	}

	/**
	 * Only call this function AFTER calling get_field_name otherwise result will be wrong.
	 *
	 * @return boolean
	 */
	public function is_img() {
		return $this->is_img;
	}

	/**
	 * Some fields (like ACF fields) can be 'unpublished' - This method returns true so long as field
	 * should be visible to user.
	 *
	 * @return bool
	 */
	public function is_field_visible() {
		if ( $this->override_data ) {
			return true;
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-acf-parser.php';
		if ( Site_Search_One_ACF_Parser::is_acf_field( $this->meta_key ) ) {
			if ( ! Site_Search_One_ACF_Parser::is_published_field( $this->meta_key ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Determine if SC1 should treat this field as a taxonomy.
	 *
	 * @return bool
	 */
	public function is_field_taxonomy() {
		if ( $this->override_data ) {
			return $this->override_data['is_enumerable'] && ! $this->override_data['is_select'];
		}
		return taxonomy_exists( $this->meta_key ) || $this->is_field_true_false();
	}

	/**
	 * Get fields associated with post.
	 *
	 * @param int    $post_id The post id.
	 * @param string $display_name The display name that will be used in the display name field.
	 * @return SC1_Field[]
	 */
	public static function get_post_fields( $post_id, $display_name ) {
		$fields       = array();
		$wp_post_meta = get_post_meta( $post_id );
		$wp_post_meta = array_combine( array_keys( $wp_post_meta ), array_column( $wp_post_meta, '0' ) );
		foreach ( $wp_post_meta as $key => $val ) {
			if (
				strlen( $key ) === 0 // Empty field name?
				|| 'Published' === $key
				|| 'Tags' === $key
				|| 'Categories' === $key
				|| 'Link' === $key
				|| 'panels_data' === $key
				|| '' === $key
			) {
				continue;
			}
			$field      = new SC1_Field( $key, $val );
			$field_name = $field->get_field_name();
			if ( strpos( $field_name, '_' ) !== false && $field->is_img() === false ) {
				continue;
			}

			array_push( $fields, $field );
		}
		// Also add in Published manually.
		$format          = 'Y-m-d\TH:i:s.v\Z'; // ISO 8601.
		$published_date  = get_the_date( $format, $post_id );
		$published_field = new SC1_Field( 'Published', $published_date );
		array_push( $fields, $published_field );
		// Also add in Tags manually.
		$wp_post_tags = get_the_tags( $post_id );
		if ( ! is_wp_error( $wp_post_tags ) && false !== $wp_post_tags ) {
			$tags = array();
			foreach ( $wp_post_tags as $wp_post_tag ) {
				$name = $wp_post_tag->name;
				array_push( $tags, $name );
			}
			if ( count( $tags ) > 0 ) {
				$tags_str   = implode( '|', $tags );
				$tags_field = new SC1_Field( 'Tags', $tags_str );
				array_push( $fields, $tags_field );
			}
		}
		// If the post is sticky, this should be passed as a field.
		if ( is_sticky( $post_id ) ) {
			$sticky_field = new SC1_Field( 'Sticky', 'Sticky' );
			array_push( $fields, $sticky_field );
		}
		// Also, add in Excerpt field manually.
		if ( get_post_type( $post_id ) !== 'attachment' ) {
			$excerpt_field = new SC1_Field( '_excerpt', wp_strip_all_tags( get_the_excerpt( $post_id ) ) );
			$fields[]      = $excerpt_field;
		} else {
			// It's an attachment. We use the description as the excerpt, if available.
			// $excerpt_str = wp_get_attachment_caption($post_id);
			// if ($excerpt_str !== false && trim($excerpt_str) !== '') {
			// $excerpt_field = new SC1_Field('_excerpt', wp_strip_all_tags($excerpt_str));
			// $fields[]      = $excerpt_field;
			// }.
			$query = get_post( $post_id );
			$desc  = apply_filters( 'the_content', $query->post_content );
			if ( false !== $desc && null !== $desc && '' !== $desc ) {
				if ( strlen( $desc ) > 512 ) {
					$desc = substr( $desc, 0, 512 );
				}
				// In case no excerpt but desc provided, use desc as excerpt.
				$excerpt_field = new SC1_Field( '_excerpt', wp_strip_all_tags( $desc ) );
				$fields[]      = $excerpt_field;
			}
		}
		// Also add in Categories manually.
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-post.php';
		$ss1_post        = new Site_Search_One_Post( $post_id );
		$post_categories = $ss1_post->get_all_category_ids_including_parents();
		$cat_names       = array();
		foreach ( $post_categories as $c ) {
			$name        = get_cat_name( $c );
			$cat_names[] = $name;
		}
		if ( count( $post_categories ) > 0 ) {
			$cats_str   = implode( '|', $cat_names );
			$cats_field = new SC1_Field( 'Categories', $cats_str );
			$fields[]   = $cats_field;
		}
		// Also add URL field.
		$link       = get_permalink( $post_id );
		$link_field = new SC1_Field( '_link', $link );
		$fields[]   = $link_field;
		// Also, add in featured image url, if present.
		if ( ! is_wp_error( self::try_require_premium_functions() ) ) {
			// Premium.
			$fields = Site_Search_One_Premium_Functions::add_premium_fields( $post_id, $fields, get_post_type( $post_id ), false );
		}
		// Also indicate if this post is an attachment.
		$attachment = 'Non-attachment';
		if ( get_post_type( $post_id ) === 'attachment' ) {
			$attachment  = 'Attachment';
			$post        = get_post( $post_id );
			$parent_id   = $post->post_parent;
			$attached_to = new SC1_Field( '_attached_to', strval( $parent_id ) );
			$fields[]    = $attached_to;
		}
		$is_attachment = new SC1_Field( 'is_attachment', $attachment );
		$is_attachment->override_return_data( 'Attachment', $attachment, true, true );
		$fields[] = $is_attachment;
		// Also indicate the mime type.
		$mime      = get_post_mime_type( $post_id );
		$mime_type = new SC1_Field( 'mimetype', $mime );
		$mime_type->override_return_data( 'Mimetype', $mime, true, true );
		$fields[]      = $mime_type;
		$post_id_field = new SC1_Field( '_post_id', strval( $post_id ) );
		$fields[]      = $post_id_field;
		// Also, Send _SS1DisplayName field...
		$ss1_display_name = new SC1_Field( '_SS1DisplayName', $display_name );
		$fields[]         = $ss1_display_name;
		return $fields;
	}

	/**
	 * Attempt to require the premium functions in the premium plugin. May fail if premium is not installed.
	 *
	 * @return bool|WP_Error
	 */
	private static function try_require_premium_functions() {
		if ( class_exists( 'Site_Search_One_Premium_Functions' ) ) {
			return true;
		} else {
			$plugins = get_plugins();
			foreach ( $plugins as $plugin_dir => $plugin ) {
				if ( 'Site Search ONE Premium' === $plugin['Name'] ) {
					$install_loc = get_option( 'site-search-one-premium-install-location' );
					if ( false !== $install_loc ) {
						$path = $install_loc . '/admin/class-site-search-one-p-functions.php';
						if ( file_exists( $path ) ) {
							require_once $path;

							return true;
						} else {
							return new WP_Error( 'plugin_file_not_found', 'Site Search ONE Installation is missing a required file', $path );
						}
					}
				}
			}
		}
		return new WP_Error( 'plugin_not_installed', 'Site Search ONE Premium does not appear to be installed' );
	}

	/**
	 * Get the taxonomies for a given post.
	 *
	 * @param int $post_id the post id.
	 *
	 * @return array|SC1_Field[]
	 */
	public static function get_post_taxonomies( $post_id ) {
		$taxonomy_names = get_post_taxonomies( $post_id );
		$taxonomies     = array();
		foreach ( $taxonomy_names as $taxonomy_name ) {
			if ( 'category' === $taxonomy_name ) {
				continue; // Ignore the default ones.
			}
			if ( 'post_tag' === $taxonomy_name ) {
				continue;
			}
			if ( 'post_format' === $taxonomy_name ) {
				continue;
			}
			$taxonomy_values = array();
			$terms           = get_the_terms( $post_id, $taxonomy_name );
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( '' !== $term->name ) {
						array_push( $taxonomy_values, $term->name );
					}
				}
			}
			if ( count( $taxonomy_values ) > 0 ) {
				$taxonomy_label = self::get_taxonomy_label( $taxonomy_name );
				$taxonomy_value = implode( '|', $taxonomy_values );
				$taxonomy       = new SC1_Field( $taxonomy_name, $taxonomy_value );
				array_push( $taxonomies, $taxonomy );
			}
		}
		if ( ! is_wp_error( self::try_require_premium_functions() ) ) {
			$taxonomies = Site_Search_One_Premium_Functions::add_premium_fields( $post_id, $taxonomies, get_post_type( $post_id ), true );
		}
		return $taxonomies;
	}

	/**
	 * Get taxonomy label for a given taxonomy.
	 *
	 * @param string $taxonomy_name The taxonomy name.
	 *
	 * @return string
	 */
	private static function get_taxonomy_label( $taxonomy_name ) {
		$taxonomy = get_taxonomy( $taxonomy_name );
		$label    = $taxonomy->label;
		if ( null === $label || '' === $label ) {
			return $taxonomy->name;
		} else {
			return $taxonomy->label;
		}
	}

	/**
	 * Get the fields name.
	 * May be changed by override data, Pods/ACF fields may also return a different name.
	 *
	 * @return mixed|string
	 */
	public function get_field_name() {
		if ( $this->override_data ) {
			return $this->override_data['dsp_name'];
		}
		if ( $this->is_field_taxonomy() && ! $this->is_field_true_false() ) {
			return self::get_taxonomy_label( $this->meta_key );
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-acf-parser.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-pods-parser.php';
		$acf_field = Site_Search_One_ACF_Parser::is_acf_field( $this->meta_key );

		if ( false !== $acf_field ) {
			$type = Site_Search_One_ACF_Parser::get_acf_field_type( $this->meta_key );
			switch ( $type ) {
				case 'image':
					$this->is_img = true;
					return '_' . $acf_field;
				default:
					return $acf_field;
			}
		}
		$pods_field = Site_Search_One_Pods_Parser::is_pods_field( $this->meta_key );
		if ( false !== $pods_field ) {
			return get_the_title( $pods_field );
		}
		$raw_field_name  = $this->meta_key;
		$field_name_path = explode( '/', $raw_field_name );

		return $field_name_path[ count( $field_name_path ) - 1 ];
	}

	/**
	 * This function returns get_field_name() but with whitespace, commas, full stops, hyphens and underscores removed.
	 * dtSearch has trouble with enumerable fields with spaces and other separators in the name.
	 */
	public function get_dts_enum_safe_field_name() {
		$field_name = $this->get_field_name();
		$field_name = preg_replace( '/\s+/', '', $field_name ); // Remove all whitespace.
		$field_name = str_replace( ',', '', $field_name );
		$field_name = str_replace( '.', '', $field_name );
		$field_name = str_replace( '-', '', $field_name );
		return str_replace( '_', '', $field_name );
	}

	/**
	 * Determine if field is to displayed as multiple choice drop-down
	 * If it is, the field should be a stored enumerable field with
	 * ExtraParam 'SS1-Display':'Multi-Choice'
	 */
	public function is_field_multi_choice() {
		if ( $this->override_data && $this->override_data['is_select'] ) {
			return true;
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-acf-parser.php';
		$field_type = Site_Search_One_ACF_Parser::get_acf_field_type( $this->meta_key );

		return ( $field_type && ( 'select' === $field_type ) );
	}

	/**
	 * Determine if field is an ACF true/false field.
	 *
	 * @return bool
	 */
	public function is_field_true_false() {
		if ( $this->override_data ) {
			return false;
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-acf-parser.php';
		$field_type = Site_Search_One_ACF_Parser::get_acf_field_type( $this->meta_key );
		return ( $field_type && ( 'true_false' === $field_type ) );
	}

	/**
	 * Determine if field is a date.
	 *
	 * @return bool
	 */
	public function is_date() {
		if ( $this->override_data ) {
			return false;
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-acf-parser.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-pods-parser.php';
		if ( 'Published' === $this->meta_key ) {
			return true;
		}
		$pods_date = Site_Search_One_Pods_Parser::is_field_pods_date( $this->meta_key );
		if ( false !== $pods_date ) {
			return true;
		}
		$acf_date = Site_Search_One_ACF_Parser::is_acf_field_a_date( $this->meta_key );
		if ( false !== $acf_date ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the processed field value as should be sent to SC1 for indexing
	 * May return false for certain fields, indicating that this field data should not be indexed.
	 *
	 * @return false|string
	 */
	public function get_field_value() {
		if ( $this->override_data ) {
			return $this->override_data['field_value'];
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-acf-parser.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-pods-parser.php';

		if ( 'Published' === $this->meta_key ) {
			return $this->meta_value; // Short circuit in case someone's being 'smart'.
		}

		$pods_date = Site_Search_One_Pods_Parser::is_field_pods_date( $this->meta_key );
		if ( false !== $pods_date ) {
			return Site_Search_One_Pods_Parser::to_sc1_date( $this->meta_value, $pods_date );
		}

		// If the field is an acf field, return an empty string if the field is no longer published.
		if ( Site_Search_One_ACF_Parser::is_acf_field( $this->meta_key ) ) {
			if ( Site_Search_One_ACF_Parser::is_published_field( $this->meta_key ) ) {
				$acf_date = Site_Search_One_ACF_Parser::is_acf_field_a_date( $this->meta_key );
				if ( false !== $acf_date ) {
					// ACF Dates require special handling.
					return Site_Search_One_ACF_Parser::to_sc1_date( $this->meta_value, $acf_date );
				}
				// For ACF Yes/No fields, special logic required..
				$type = Site_Search_One_ACF_Parser::get_acf_field_type( $this->meta_key );
				switch ( $type ) {
					case 'true_false':
						return Site_Search_One_ACF_Parser::get_yes_no( $this->meta_key, $this->meta_value );
					case 'image':
						return Site_Search_One_ACF_Parser::get_acf_img_url( $this->meta_value );
					default:
						// Other ACF fields should be treated as normal field data.
						break;
				}
			} else {
				// The ACF field is unpublished and should not be indexed.
				return false;
			}
		}

		if ( strlen( $this->meta_value ) > 256 ) {
			$val  = substr( $this->meta_value, 0, 256 );
			$val .= '...';
			return $val;
		}
		// Handle the case that the data is a PHP array.
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		$unserialized_meta_val = @unserialize( $this->meta_value );
		// phpcs:enable
		if ( 'b:0;' === $unserialized_meta_val || false !== $unserialized_meta_val ) {
			// It's an array of string.
			$type = gettype( $unserialized_meta_val );
			if ( 'array' === $type ) {
				return join( '|', $unserialized_meta_val );
			} else {
				return strval( $unserialized_meta_val );
			}
		} else {
			// It's just raw data.
			if ( '' !== $this->meta_value ) {
				return $this->meta_value;
			} else {
				return false;
			}
		}

	}
}
