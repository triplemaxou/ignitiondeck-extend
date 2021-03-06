<?php

function id_projectGrid_responsive($attrs) {
	ob_start();
	$class_bootstrap = apply_filters("bii_bootstrap_class", $attrs, $md = 3, $sm = 2, $xs = 1, $xss = 1, $lg = 4);

	// project category 
	if (isset($attrs['category'])) {
		$category = $attrs['category'];
		if (is_int($category)) {
			$args = array(
				'post_type' => 'ignition_product',
				'tax_query' => array(
					array(
						'taxonomy' => 'project_category',
						'field' => 'id',
						'terms' => $category
					)
				)
			);
		} else {
			$args = array(
				'post_type' => 'ignition_product',
				'tax_query' => array(
					array(
						'taxonomy' => 'project_category',
						'field' => 'name',
						'terms' => $category
					)
				)
			);
		}
	} else {
		// in case category isn't defined, query args must contain post type
		$args['post_type'] = 'ignition_product';
	}

	if (isset($attrs['max'])) {
		$args['posts_per_page'] = $attrs['max'];
	}
	//// --> Custom args - START
	// orderby possible values - days_left, percent_raised, funds_raised, rand, title, date (default)
	if (isset($attrs['orderby'])) {
		if ($attrs['orderby'] == 'days_left') {
			$args['orderby'] = 'meta_value_num';
			$args['meta_key'] = 'ign_days_left';
		} else if ($attrs['orderby'] == 'percent_raised') {
			$args['orderby'] = 'meta_value_num';
			$args['meta_key'] = 'ign_percent_raised';
		} else if ($attrs['orderby'] == 'funds_raised') {
			$args['orderby'] = 'meta_value_num';
			$args['meta_key'] = 'ign_fund_raised';
		} else {
			// reserved for later use
			$args['orderby'] = $attrs['orderby'];
		}
	}

	// order possible values = ASC, DESC (default)
	if (isset($attrs['order'])) {
		$args['order'] = $attrs['order'];
	}

	// author (single name)
	if (isset($attrs['author'])) {
		$args['author_name'] = $attrs['author'];
	}

	// --> Custom args - END
	// moved this block before the query call

	require '/var/www/upyourtown/wp-content/plugins/ignitiondeck-crowdfunding/languages/text_variables.php';
	$custom = false;
	if (isset($attrs['deck'])) {
		$deck_id = $attrs['deck'];
		$settings = Deck::get_deck_attrs($deck_id);
		if (!empty($settings)) {
			$attrs = unserialize($settings->attributes);
			$custom = true;
		}
	}

	// start the actual query, which will also output decks

	$posts = get_posts($args);
	$project_ids = array();
	?>
	<div class="ignitiondeck">
		<div class="grid_wrap_responsive">
			<?php
			$i = 1;
//			pre($args);
			foreach ($posts as $post) {

				$post_id = $post->ID;
				$project_id = get_post_meta($post_id, 'ign_project_id', true);

				// no more "pass" checks are required, because the query gets all proper projects in proper order and settings

				$deck = new Deck($project_id);
				$mini_deck = $deck->mini_deck();
				$post_id = $deck->get_project_postid();
				$status = get_post_status($post_id);
				$custom = apply_filters('idcf_custom_deck', $custom, $post_id);
				$attrs = apply_filters('idcf_deck_attrs', (isset($attrs) ? $attrs : null), $post_id);
				if (strtoupper($status) == 'PUBLISH') {
					$settings = getSettings();
					?><div class='grid_item <?= $class_bootstrap ?>'> <?php
					include IDCE_PATH . '/templates/_miniWidget.php';
					?></div><?php
					$i++;
				}
			}

			// end with query and continue with original code
			?>
		</div>
	</div>
	<br style="clear: both"/><?php
	$grid = ob_get_contents();
	ob_end_clean();
	return $grid;
}

function id_submissionFormFront($post_id = null) {
//	pre(get_permalink(), "red");
	global $wpdb;
	global $permalink_structure;
	if (is_multisite()) {
		require (ABSPATH . WPINC . '/pluggable.php');
	}
	global $current_user;
	if ($current_user->ID) {
		get_currentuserinfo();
		if (empty($permalink_structure)) {
			$prefix = '&';
		} else {
			$prefix = '?';
		}
		$wp_upload_dir = wp_upload_dir();
		if (!function_exists('wp_handle_upload'))
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		if (empty($post_id)) {
			if (isset($_GET['numero_projet'])) {
				$post_id = $_GET['numero_projet'];
				$post = get_post($post_id);

				if (current_user_can('create_edit_projects') && $user_id) {
					if ($user_id == $post->post_author || apply_filters('ide_fes_edit_project_editor', false, $post_id, $user_id)) {
						// allows user to post iframe and embed code in long descriptions
						add_filter('wp_kses_allowed_html', 'idcf_filter_wp_kses', 11, 2);
					}
				}
			} else {
				if (isset($_GET['create_project']) && $_GET['create_project']) {
					if (current_user_can('create_edit_projects')) {
						// allows user to post iframe and embed code in long descriptions
						add_filter('wp_kses_allowed_html', 'idcf_filter_wp_kses', 11, 2);
					}
				}
			}
		} else {
			// post_id is coming in arguments, check that user can edit post, and it's his post as well
			$post = get_post($post_id);
			$user_id = $current_user->ID;
			if (current_user_can('create_edit_projects')) {
				if ($user_id == $post->post_author) {
					// allows user to post iframe and embed code in long descriptions
					add_filter('wp_kses_allowed_html', 'idcf_filter_wp_kses', 11, 2);
				}
			}
		}
		$memberdeck_gateways = get_option('memberdeck_gateways');
		$fund_types = get_option('idc_cf_fund_type');
		if (empty($fund_types)) {
			$fund_types = 'capture';
		}
		$vars = array('fund_types' => $fund_types);

		if (!empty($post_id) && $post_id > 0) {
			if (empty($post)) {
				$post = get_post($post_id);
			}
			$status = $post->post_status;
			$company_name = get_post_meta($post_id, 'ign_company_name', true);
			$company_logo = get_post_meta($post_id, 'ign_company_logo', true);
			$company_location = get_post_meta($post_id, 'ign_company_location', true);
			$company_url = get_post_meta($post_id, 'ign_company_url', true);
			$company_fb = get_post_meta($post_id, 'ign_company_fb', true);
			$company_twitter = get_post_meta($post_id, 'ign_company_twitter', true);
			$project_name = get_the_title($post_id);
			$categories = wp_get_post_terms($post_id, 'project_category');
			if (!empty($categories) && is_array($categories)) {
				$project_category = $categories[0]->slug;
			} else {
				$project_category = null;
			}
			$project_start = get_post_meta($post_id, 'ign_start_date', true);
			$project_end = get_post_meta($post_id, 'ign_fund_end', true);
			$project_goal = get_post_meta($post_id, 'ign_fund_goal', true);
			$project_short_description = get_post_meta($post_id, 'ign_project_description', true);
			$project_long_description = get_post_meta($post_id, 'ign_project_long_description', true);
			$project_faq = get_post_meta($post_id, 'ign_faqs', true);
			$project_updates = get_post_meta($post_id, 'ign_updates', true);
			$project_video = get_post_meta($post_id, 'ign_product_video', true);
			$project_hero = ID_Project::get_project_thumbnail($post_id);
			$project_image2 = get_post_meta($post_id, 'ign_product_image2', true);
			$project_image3 = get_post_meta($post_id, 'ign_product_image3', true);
			$project_image4 = get_post_meta($post_id, 'ign_product_image4', true);
			$project_id = get_post_meta($post_id, 'ign_project_id', true);
			$project_type = get_post_meta($post_id, 'ign_project_type', true);
			$project_end_type = get_post_meta($post_id, 'ign_end_type', true);
			$purchase_form = get_post_meta($post_id, 'ign_option_purchase_url', true);
			// levels
			$disable_levels = get_post_meta($post_id, 'ign_disable_levels', true);
			$project_levels = get_post_meta($post_id, 'ign_product_level_count', true);

			$levels = array();
			$levels[0] = array();
			$levels[0]['title'] = get_post_meta($post_id, 'ign_product_title', true); /* level 1 */
			$levels[0]['price'] = get_post_meta($post_id, 'ign_product_price', true); /* level 1 */
			$levels[0]['short'] = get_post_meta($post_id, 'ign_product_short_description', true); /* level 1 */
			$levels[0]['long'] = get_post_meta($post_id, 'ign_product_details', true); /* level 1 */
			$levels[0]['limit'] = get_post_meta($post_id, 'ign_product_limit', true); /* level 1 */
			// Project fund type for the levels
			$levels_project_fund_type = get_post_meta($post_id, 'mdid_levels_fund_type', true);
			if (!empty($levels_project_fund_type)) {
				$levels[0]['fund_type'] = $levels_project_fund_type[0];
			}
			for ($i = 1; $i <= $project_levels - 1; $i++) {
				$levels[$i] = array();
				$levels[$i]['title'] = get_post_meta($post_id, 'ign_product_level_' . ($i + 1) . '_title', true);
				$levels[$i]['price'] = get_post_meta($post_id, 'ign_product_level_' . ($i + 1) . '_price', true);
				$levels[$i]['short'] = get_post_meta($post_id, 'ign_product_level_' . ($i + 1) . '_short_desc', true);
				$levels[$i]['long'] = get_post_meta($post_id, 'ign_product_level_' . ($i + 1) . '_desc', true);
				$levels[$i]['limit'] = get_post_meta($post_id, 'ign_product_level_' . ($i + 1) . '_limit', true);
				if (!empty($levels_project_fund_type[$i])) {
					$levels[$i]['fund_type'] = $levels_project_fund_type[$i];
				}
			}

			$new_vars = array('post_id' => $post_id,
				'company_name' => $company_name,
				'company_logo' => $company_logo,
				'company_location' => $company_location,
				'company_url' => $company_url,
				'company_fb' => $company_fb,
				'company_twitter' => $company_twitter,
				'project_name' => $project_name,
				'project_category' => $project_category,
				'project_start' => $project_start,
				'project_end' => $project_end,
				'project_goal' => $project_goal,
				'project_short_description' => $project_short_description,
				'project_long_description' => $project_long_description,
				'project_faq' => $project_faq,
				'project_updates' => $project_updates,
				'project_video' => $project_video,
				'project_hero' => $project_hero,
				'project_image2' => $project_image2,
				'project_image3' => $project_image3,
				'project_image4' => $project_image4,
				'project_id' => $project_id,
				'project_type' => $project_type,
				'project_end_type' => $project_end_type,
				'fund_types' => $fund_types,
				'disable_levels' => $disable_levels,
				'project_levels' => $project_levels,
				'levels' => $levels,
				'status' => $status);
			$vars = array_merge($new_vars);
		}
		if (isset($_POST['project_fesubmit']) || isset($_POST['project_fesave'])) {
			// Checking nonce field first, before going further
			if (wp_verify_nonce(sanitize_text_field($_POST['idcf_fes_wp_nonce']), 'idcf_fes_section_nonce')) {
				// prep for file inputs
				// Create team variables
				if (isset($_POST['company_name'])) {
					$company_name = sanitize_text_field($_POST['company_name']);
				}

				if (isset($_FILES['company_logo']) && $_FILES['company_logo']['size'] > 0) {
					$company_logo = wp_handle_upload($_FILES['company_logo'], array('test_form' => false));
					$logo_filetype = wp_check_filetype(basename($company_logo['file']), null);
					if ($logo_filetype['ext'] == strtolower('png') || $logo_filetype['ext'] == strtolower('jpg') || $logo_filetype['ext'] == strtolower('gif') || $logo_filetype['ext'] == strtolower('jpeg')) {
						$logo_attachment = array(
							'guid' => $wp_upload_dir['url'] . '/' . basename($company_logo['file']),
							'post_mime_type' => $logo_filetype['type'],
							'post_title' => preg_replace('/\.[^.]+$/', '', basename($company_logo['file'])),
							'post_content' => '',
							'post_status' => 'inherit'
						);
						$company_logo_posted = true;
					} else {
						$company_logo_posted = false;
					}
				} else {
					$company_logo_posted = false;
					if (empty($vars['company_logo'])) {
						$company_logo = null;
					} else {
						$company_logo = $vars['company_logo'];
					}
				}
				if (isset($_POST['company_location'])) {
					$company_location = sanitize_text_field($_POST['company_location']);
				}
				if (isset($_POST['company_url'])) {
					$company_url = sanitize_text_field($_POST['company_url']);
				}
				if (isset($_POST['company_fb'])) {
					$company_fb = sanitize_text_field($_POST['company_fb']);
				}
				if (isset($_POST['company_twitter'])) {
					$company_twitter = sanitize_text_field($_POST['company_twitter']);
				}
				// Create project variables
				if (isset($_POST['project_name'])) {
					$project_name = sanitize_text_field($_POST['project_name']);
				}
				if (isset($_POST['project_category'])) {
					$project_category = sanitize_text_field($_POST['project_category']);
				} else if (!empty($vars['project_category'])) {
					$project_category = $vars['project_category'];
				} else {
					$project_category = null;
				}
				if (isset($_POST['project_goal'])) {
					$project_goal = sanitize_text_field(str_replace(',', '', $_POST['project_goal']));
				}
				if (isset($_POST['project_start'])) {
					$project_start = sanitize_text_field($_POST['project_start']);
				}
				if (isset($_POST['project_end'])) {
					$project_end = sanitize_text_field($_POST['project_end']);
				}
				$project_short_description = sanitize_text_field($_POST['project_short_description']);
				$project_long_description = wpautop(wp_kses_post(balanceTags($_POST['project_long_description'])));
				$project_faq = wpautop(wp_kses_post(balanceTags($_POST['project_faq'])));
				if (isset($_POST['project_updates'])) {
					$project_updates = wpautop(wp_kses_post(balanceTags($_POST['project_updates'])));
				} else {
					$project_updates = '';
				}
				$project_video = wp_kses_post($_POST['project_video']);
				if (isset($_FILES['project_hero']) && $_FILES['project_hero']['size'] > 0) {
					//$project_hero = sanitize_text_field($_POST['project_hero']);
					$project_hero = wp_handle_upload($_FILES['project_hero'], array('test_form' => false));
					$hero_filetype = wp_check_filetype(basename($project_hero['file']), null);
					if ($hero_filetype['ext'] == strtolower('png') || $hero_filetype['ext'] == strtolower('jpg') || $hero_filetype['ext'] == strtolower('gif') || $hero_filetype['ext'] == strtolower('jpeg')) {
						$hero_attachment = array(
							'guid' => $wp_upload_dir['url'] . '/' . basename($project_hero['file']),
							'post_mime_type' => $hero_filetype['type'],
							'post_title' => preg_replace('/\.[^.]+$/', '', basename($project_hero['file'])),
							'post_content' => '',
							'post_status' => 'inherit'
						);
						$hero_posted = true;
					} else {
						$hero_posted = false;
					}
				} else {
					$hero_posted = false;
					if (empty($vars['project_hero'])) {
						$project_hero = null;
					} else {
						$project_hero = $vars['project_hero'];
					}
					// Check if the already present image is removed
					if (isset($_POST['project_hero_removed']) && $_POST['project_hero_removed'] == "yes") {
						$project_hero_removed = true;
					}
				}
				if (isset($_FILES['project_image2']) && $_FILES['project_image2']['size'] > 0) {
					//$project_image2 = sanitize_text_field($_POST['project_image2']);
					$project_image2 = wp_handle_upload($_FILES['project_image2'], array('test_form' => false));
					$image2_filetype = wp_check_filetype(basename($project_image2['file']), null);
					if ($image2_filetype['ext'] == strtolower('png') || $image2_filetype['ext'] == strtolower('jpg') || $image2_filetype['ext'] == strtolower('gif') || $image2_filetype['ext'] == strtolower('jpeg')) {
						$image2_attachment = array(
							'guid' => $wp_upload_dir['url'] . '/' . basename($project_image2['file']),
							'post_mime_type' => $image2_filetype['type'],
							'post_title' => preg_replace('/\.[^.]+$/', '', basename($project_image2['file'])),
							'post_content' => '',
							'post_status' => 'inherit'
						);
						$project_image2_posted = true;
					} else {
						$project_image2_posted = false;
					}
				} else {
					$project_image2_posted = false;
					if (empty($vars['project_image2'])) {
						$project_image2 = null;
					} else {
						$project_image2 = $vars['project_image2'];
					}
					// Check if the already present image is removed
					if (isset($_POST['project_image2_removed']) && $_POST['project_image2_removed'] == "yes") {
						$project_image2_removed = true;
					}
				}
				if (isset($_FILES['project_image3']) && $_FILES['project_image3']['size'] > 0) {
					//$project_image3 = sanitize_text_field($_POST['project_image3']);
					$project_image3 = wp_handle_upload($_FILES['project_image3'], array('test_form' => false));
					$image3_filetype = wp_check_filetype(basename($project_image3['file']), null);
					if ($image3_filetype['ext'] == strtolower('png') || $image3_filetype['ext'] == strtolower('jpg') || $image3_filetype['ext'] == strtolower('gif') || $image3_filetype['ext'] == strtolower('jpeg')) {
						$image3_attachment = array(
							'guid' => $wp_upload_dir['url'] . '/' . basename($project_image3['file']),
							'post_mime_type' => $image3_filetype['type'],
							'post_title' => preg_replace('/\.[^.]+$/', '', basename($project_image3['file'])),
							'post_content' => '',
							'post_status' => 'inherit'
						);
						$project_image3_posted = true;
					} else {
						$project_image3_posted = false;
					}
				} else {
					$project_image3_posted = false;
					if (empty($vars['project_image3'])) {
						$project_image3 = null;
					} else {
						$project_image3 = $vars['project_image3'];
					}
					// Check if the already present image is removed
					if (isset($_POST['project_image3_removed']) && $_POST['project_image3_removed'] == "yes") {
						$project_image3_removed = true;
					}
				}
				if (isset($_FILES['project_image4']) && $_FILES['project_image4']['size'] > 0) {
					//$project_image4 = sanitize_text_field($_POST['project_image4']);
					$project_image4 = wp_handle_upload($_FILES['project_image4'], array('test_form' => false));
					$image4_filetype = wp_check_filetype(basename($project_image4['file']), null);
					if ($image4_filetype['ext'] == strtolower('png') || $image4_filetype['ext'] == strtolower('jpg') || $image4_filetype['ext'] == strtolower('gif') || $image4_filetype['ext'] == strtolower('jpeg')) {
						$image4_attachment = array(
							'guid' => $wp_upload_dir['url'] . '/' . basename($project_image4['file']),
							'post_mime_type' => $image4_filetype['type'],
							'post_title' => preg_replace('/\.[^.]+$/', '', basename($project_image4['file'])),
							'post_content' => '',
							'post_status' => 'inherit'
						);
						$project_image4_posted = true;
					} else {
						$project_image4_posted = false;
					}
				} else {
					$project_image4_posted = false;
					if (empty($vars['project_image4'])) {
						$project_image4 = null;
					} else {
						$project_image4 = $vars['project_image4'];
					}
					// Check if the already present image is removed
					if (isset($_POST['project_image4_removed']) && $_POST['project_image4_removed'] == "yes") {
						$project_image4_removed = true;
					}
				}
				//$type = sanitize_text_field($_POST['project_type']);
				$project_type = 'level-based';
				if (isset($_POST['project_end_type'])) {
					$project_end_type = sanitize_text_field($_POST['project_end_type']);
				}
				if (isset($_POST['disable_levels'])) {
					$disable_levels = absint($_POST['disable_levels']);
					$project_levels = 0;
				} else {
					$disable_levels = 0;
				}
				if (isset($_POST['project_levels']) && !$disable_levels) {
					$project_levels = absint($_POST['project_levels']);
					$saved_levels = array();
					$saved_funding_types = array();

					// Removing last element of project_fund_type array posted, because that's of cloned level
					if (isset($_POST['project_fund_type'])) {
						array_pop($_POST['project_fund_type']);
					}
					$indexsaved = [];
					$project_levels = 0;
					foreach ($_POST["project_level_title"] as $key => $level_title) {
						$level_price = $_POST["project_level_price"][$key];
						$level_limit = $_POST["project_level_limit"][$key];
						$level_description = $_POST["level_description"][$key];
						$level_long_description = $_POST["level_long_description"][$key];
						$saved_levels[$key] = array();
						$saved_levels[$key]['title'] = $level_title;
						$saved_levels[$key]['price'] = floatval($level_price);
						$saved_levels[$key]['short'] = sanitize_text_field($level_description);
						$saved_levels[$key]['long'] = wpautop(wp_kses_post(balanceTags(($level_long_description))));
						$saved_levels[$key]['limit'] = absint($level_limit);
						$saved_levels[$key]['enable_multiples'] = 1;
						$indexsaved[] = $key;
						++$project_levels;
					}
				}

				// Create user variables
				if (is_user_logged_in()) {
					global $current_user;
					get_currentuserinfo();
					$user_id = $current_user->ID;
					$comment_status = get_option('default_comment_status');
					// Create a New Post
					$args = array(
						'post_author' => $user_id,
						'post_title' => $project_name,
						'post_name' => str_replace(' ', '-', $project_name),
						'post_type' => 'ignition_product',
						'tax_input' => array('project_category' => $project_category),
						'comment_status' => $comment_status);

					if (isset($_POST['project_post_id'])) {
						$args['ID'] = absint($_POST['project_post_id']);
						$post = get_post($post_id);
						$status = $post->post_status;
						if ((strtoupper($status) == 'DRAFT') && (isset($_POST['project_fesubmit']))) {
							//If the project was previously saved, and is now being submitted, update the status
							$status = 'pending';
						}
						/* else if ((strtoupper($status) == 'PENDING') && (isset($_POST['project_fesave']))){
						  //If the project is pending review, and is being saved, revert it to draft
						  $status = 'draft';
						  } */
						$args['post_status'] = $status;
						$args['tax_input'] = array('project_category' => $project_category);
						$args['comment_status'] = $post->comment_status;
					} else {
						if (isset($_POST['project_fesave'])) {
							$args['post_status'] = 'draft';
						} else if (isset($_POST['project_fesubmit'])) {
							$args['post_status'] = 'pending';
						}
					}

					// update posted date (update cases)
					if (isset($post_id) && isset($_GET['numero_projet'])) {
						$args['post_date'] = $post->post_date;
					}
					$post_id = wp_insert_post($args);
					if (!current_user_can('manage_categories')) {
						wp_set_object_terms($post_id, $project_category, 'project_category');
					}
					if (isset($post_id)) {
						if ($company_logo_posted) {
							$logo_id = wp_insert_attachment($logo_attachment, $company_logo['file'], $post_id);
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							$logo_data = wp_generate_attachment_metadata($logo_id, $company_logo['file']);
							$metadata = wp_update_attachment_metadata($logo_id, $logo_data);
						}
						if ($hero_posted) {
							$hero_id = wp_insert_attachment($hero_attachment, $project_hero['file'], $post_id);
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							$hero_data = wp_generate_attachment_metadata($hero_id, $project_hero['file']);
							$metadata = wp_update_attachment_metadata($hero_id, $hero_data);
						}
						if ($project_image2_posted) {
							$image2_id = wp_insert_attachment($image2_attachment, $project_image2['file'], $post_id);
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							$image2_data = wp_generate_attachment_metadata($image2_id, $project_image2['file']);
							wp_update_attachment_metadata($image2_id, $image2_data);
						}
						if ($project_image3_posted) {
							$image3_id = wp_insert_attachment($image3_attachment, $project_image3['file'], $post_id);
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							$image3_data = wp_generate_attachment_metadata($image3_id, $project_image3['file']);
							wp_update_attachment_metadata($image3_id, $image3_data);
						}
						if ($project_image4_posted) {
							$image4_id = wp_insert_attachment($image4_attachment, $project_image4['file'], $post_id);
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							$image4_data = wp_generate_attachment_metadata($image4_id, $project_image4['file']);
							wp_update_attachment_metadata($image4_id, $image4_data);
						}
						// Insert to ign_products
						$proj_args = array('product_name' => $project_name);
						if (isset($saved_levels[0])) {
							$proj_args['ign_product_title'] = $saved_levels[0]['title'];
							$proj_args['ign_product_limit'] = $saved_levels[0]['limit'];
							$proj_args['product_details'] = $saved_levels[0]['short'];
							$proj_args['product_price'] = $saved_levels[0]['price'];
						}
						$proj_args['goal'] = $project_goal;

//						bii_write_log("proj_args");
//						bii_write_log($proj_args);
//						bii_write_log("is");
//						bii_write_log($indexsaved);
						$project_id = get_post_meta($post_id, 'ign_project_id', true);
						if (!empty($project_id)) {
							$project = new ID_Project($project_id);
							$project->update_project($proj_args);
						} else {
							$project_id = ID_Project::insert_project($proj_args);
						}
						if (isset($project_id)) {
							// Update postmeta
							update_post_meta($post_id, 'ign_company_name', $company_name);
							if (isset($company_logo['url']) && is_array($company_logo)) {
								$company_logo = sanitize_text_field($company_logo['url']);
								update_post_meta($post_id, 'ign_company_logo', $company_logo);
							} else if (!isset($company_logo)) {
								delete_post_meta($post_id, 'ign_company_logo');
							}
							update_post_meta($post_id, 'ign_company_location', $company_location);
							update_post_meta($post_id, 'ign_company_url', $company_url);
							update_post_meta($post_id, 'ign_company_fb', $company_fb);
							update_post_meta($post_id, 'ign_company_twitter', $company_twitter);

							//update_post_meta($post_id, 'ign_product_name', $project_name);
							update_post_meta($post_id, 'ign_start_date', $project_start);
							update_post_meta($post_id, 'ign_fund_end', $project_end);
							update_post_meta($post_id, 'ign_fund_goal', $project_goal);
							update_post_meta($post_id, 'ign_project_description', $project_short_description);
							update_post_meta($post_id, 'ign_project_long_description', $project_long_description);
							update_post_meta($post_id, 'ign_faqs', $project_faq);
							update_post_meta($post_id, 'ign_updates', $project_updates);
							update_post_meta($post_id, 'ign_product_video', $project_video);
							if (isset($project_hero['url']) && is_array($project_hero)) {
								$project_hero = sanitize_text_field($project_hero['url']);
								//update_post_meta($post_id, 'ign_product_image1', $project_hero);
								$sql = $wpdb->prepare('SELECT ID FROM ' . $wpdb->prefix . 'posts WHERE guid = %s', $project_hero);
								$res = $wpdb->get_row($sql);
								if (!empty($res)) {
									$attachment_id = $res->ID;
									set_post_thumbnail($post_id, $attachment_id);
								}
							} else if (!isset($project_hero)) {
								//delete_post_meta($post_id, 'ign_product_image1');
								delete_post_thumbnail($post_id);
							} else if (isset($project_hero_removed) && $project_hero_removed) {
								delete_post_thumbnail($post_id);
							}
							if (isset($project_image2['url']) && is_array($project_image2)) {
								$project_image2 = sanitize_text_field($project_image2['url']);
								update_post_meta($post_id, 'ign_product_image2', $project_image2);
							} else if (!isset($project_image2)) {
								delete_post_meta($post_id, 'ign_product_image2');
							} else if (isset($project_image2_removed) && $project_image2_removed) {
								delete_post_meta($post_id, 'ign_product_image2');
							}

							if (isset($project_image3['url']) && is_array($project_image3)) {
								$project_image3 = sanitize_text_field($project_image3['url']);
								update_post_meta($post_id, 'ign_product_image3', $project_image3);
							} else if (!isset($project_image3)) {
								delete_post_meta($post_id, 'ign_product_image3');
							} else if (isset($project_image3_removed) && $project_image3_removed) {
								delete_post_meta($post_id, 'ign_product_image3');
							}

							if (isset($project_image4['url']) && is_array($project_image4)) {
								$project_image4 = sanitize_text_field($project_image4['url']);
								update_post_meta($post_id, 'ign_product_image4', $project_image4);
							} else if (!isset($project_image4)) {
								delete_post_meta($post_id, 'ign_product_image4');
							} else if (isset($project_image4_removed) && $project_image4_removed) {
								delete_post_meta($post_id, 'ign_product_image4');
							}

							update_post_meta($post_id, 'ign_project_id', $project_id);
							update_post_meta($post_id, 'ign_project_type', $project_type);
							update_post_meta($post_id, 'ign_end_type', $project_end_type);
							if (empty($purchase_form)) {
								update_post_meta($post_id, 'ign_option_purchase_url', 'default');
							}
							// levels
							update_post_meta($post_id, 'ign_disable_levels', $disable_levels);
							update_post_meta($post_id, 'ign_product_level_count', $project_levels);
							update_post_meta($post_id, 'ign_product_title', $saved_levels[0]['title']); /* level 1 */
							update_post_meta($post_id, 'ign_product_price', $saved_levels[0]['price']); /* level 1 */
							update_post_meta($post_id, 'ign_product_short_description', $saved_levels[0]['short']); /* level 1 */
							update_post_meta($post_id, 'ign_product_details', $saved_levels[0]['long']); /* level 1 */
							update_post_meta($post_id, 'ign_product_limit', $saved_levels[0]['limit']); /* level 1 */


							$arrayvals = ["title", "price", "short", "long", "limit"];
							foreach ($indexsaved as $index) {
								if ($index != 0) { // $indexsaved[0] a déjà été inséré...
									foreach ($arrayvals as $tpsll) {
										$baseval = "ign_product_level_$index" . "_$tpsll";
										update_post_meta($post_id, $baseval, $saved_levels[$index][$tpsll]);
									}
								}
							}

							// Saving project fund type for all the levels in postmeta
							update_post_meta($post_id, 'mdid_levels_fund_type', $saved_funding_types);

							// Attach product to user
							set_user_projects($post_id, $user_id);
							if (!isset($status)) {
								do_action('ide_fes_create', $user_id, $project_id, $post_id, $proj_args, $saved_levels, $saved_funding_types);
							} else {
								do_action('ide_fes_update', $user_id, $project_id, $post_id, $proj_args, $saved_levels, $saved_funding_types);
							}
							$vars = array('post_id' => $post_id,
								'company_name' => $company_name,
								'company_logo' => $company_logo,
								'company_location' => $company_location,
								'company_url' => $company_url,
								'company_fb' => $company_fb,
								'company_twitter' => $company_twitter,
								'project_name' => $project_name,
								'project_category' => $project_category,
								'project_start' => $project_start,
								'project_end' => $project_end,
								'project_goal' => $project_goal,
								'project_short_description' => $project_short_description,
								'project_long_description' => $project_long_description,
								'project_faq' => $project_faq,
								'project_updates' => $project_updates,
								'project_video' => $project_video,
								'project_hero' => $project_hero,
								'project_image2' => $project_image2,
								'project_image3' => $project_image3,
								'project_image4' => $project_image4,
								'project_id' => $project_id,
								'project_type' => $project_type,
								/* 'project_fund_type' => $project_fund_type, */
								'project_end_type' => $project_end_type,
								'disable_levels' => $disable_levels,
								'project_levels' => $project_levels,
								'levels' => $saved_levels
							);
							do_action('ide_fes_submit', $post_id, $project_id, $vars);
							echo '<script>location.href="' . get_bloginfo('url') . "/mes-projets/?project_submitted=true" . '";</script>';
						} else {
							// return some error
						}
					} else {
						// return some error
					}
				}
			}
		}
		/* if (isset($_GET['ide_fes_create']) && $_GET['ide_fes_create'] == 1) {
		  $output = '<p class="fes saved">'.$tr_Project_Submitted.'</p>';
		  }
		  else {
		  $form = new ID_FES(null, $vars);
		  $output = '<div class="ignitiondeck"><div class="id-fes-form-wrapper">';
		  $output .= '<form name="fes" id="fes" action="" method="POST" enctype="multipart/form-data">';
		  $output .= $form->display_form();
		  $output .= '</form>';
		  $output .= '</div></div>';
		  } */
//	pre($vars,'blue');
		$form = new bii_ID_FES(null, (isset($vars) ? $vars : null));
//	do_action('ide_before_fes_display');
		$output = '<div class="ignitiondeck"><div class="id-fes-form-wrapper">';
		$output .= '<form name="fes" id="fes" action="" method="POST" enctype="multipart/form-data">';
		$output .= $form->display_form_short();
		$output .= '</form>';
		$output .= '</div></div>';
		return apply_filters('ide_fes_display', $output);
	} else {

		include_once IDCE_PATH . 'templates/_protectedPage.php';
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}

function bii_memberdeck_checkout($attrs) {
	ob_start();
//	consoleLog("extendsh");
	$url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$customer_id = customer_id();
	$instant_checkout = instant_checkout();
	$renewable = false;
	global $crowdfunding;
	global $first_data;
	global $pwyw;
	global $global_currency;
	global $stripe_api_version;
	// use the shortcode attr to get our level id
//	pre($attrs);
	$product_id = $attrs['product'];
//	if(isset($_GET["mdid_checkout"])){
//		$product_id = $_GET["mdid_checkout"];
//	}
	
	if (isset($pwyw) && $pwyw) {
		if (isset($_GET['price']) && $_GET['price'] > 0) {
			if ($global_currency == 'BTC' || $global_currency == 'credits') {
				$pwyw_price = number_format(sprintf('%f', floatval($_GET['price'])), 2, ".", "");
			} else {
				$pwyw_price = number_format(floatval(esc_attr($_GET['price'])), 2, ".", "");
			}
		} else if (isset($_POST['price']) && $_POST['price'] > 0) {
			if ($global_currency == 'BTC' || $global_currency == 'credits') {
				$pwyw_price = number_format(sprintf('%f', floatval($_POST['price'])), 2, ".", "");
			} else {
				$pwyw_price = number_format(floatval(esc_attr($_POST['price'])), 2, ".", "");
			}
		}
	}

	// get the user info
	if (is_user_logged_in()) {
		global $current_user;
		get_currentuserinfo();
		$email = $current_user->user_email;
		$fname = $current_user->user_firstname;
		$lname = $current_user->user_lastname;
		// Check first if this user is allowed to purchase
		$is_purchases_blocked = get_user_meta($current_user->ID, 'block_purchasing', true);
		if (!empty($is_purchases_blocked) && $is_purchases_blocked == "1") {
			include_once 'templates/_purchasesBlocked.php';
			$content = ob_get_contents();
			ob_clean();
			return $content;
		}
		$member = new ID_Member($current_user->ID);
		$user_data = ID_Member::user_levels($current_user->ID);

		$user_levels = null;
	}
	$settings = get_option('md_receipt_settings');
	if (!empty($settings)) {
		if (!is_array($settings)) {
			$settings = unserialize($settings);
		}
		$coname = $settings['coname'];
		$guest_checkout = $settings['guest_checkout'];
	} else {
		$coname = '';
		$guest_checkout = 0;
	}
	// Settings assigning to general variable
	$general = maybe_unserialize($settings);

	$gateways = get_option('memberdeck_gateways');
	
	if (!empty($gateways)) {
		// gateways are saved and we can now get settings from Stripe and Paypal
		if (is_array($gateways)) {
			$mc = (isset($gateways['manual_checkout']) ? $gateways['manual_checkout'] : 0);
			$pp_email = (isset($gateways['pp_email']) ? $gateways['pp_email'] : '');
			$test_email = (isset($gateways['test_email']) ? $gateways['test_email'] : '');
			$pk = (isset($gateways['pk']) ? $gateways['pk'] : '');
			$sk = (isset($gateways['sk']) ? $gateways['sk'] : '');
			$tpk = (isset($gateways['tpk']) ? $gateways['tpk'] : '');
			$tsk = (isset($gateways['tsk']) ? $gateways['tsk'] : '');
			$test = (isset($gateways['test']) ? $gateways['test'] : 0);
			$epp = (isset($gateways['epp']) ? $gateways['epp'] : 0);
			$es = (isset($gateways['es']) ? $gateways['es'] : 0);
			$esc = (isset($gateways['esc']) ? $gateways['esc'] : 0);
			$ecb = (isset($gateways['ecb']) ? $gateways['ecb'] : '');
			$eauthnet = (isset($gateways['eauthnet']) ? $gateways['eauthnet'] : '0');
			$eppadap = (isset($gateways['eppadap']) ? $gateways['eppadap'] : '0');
			$efd = (isset($gateways['efd']) ? $gateways['efd'] : '0');
			if (isset($efd) && $efd) {
				$gateway_id = $gateways['gateway_id'];
				$fd_pw = $gateways['fd_pw'];
				$efd = $gateways['efd'];
			}
		}
	}
//	pre($eppadap);
	
	// Now we check for Stripe connect data
	if (function_exists('is_id_pro') && is_id_pro()) {
		$settings = get_option('memberdeck_gateways');
		if (!empty($settings)) {
			if (is_array($settings)) {
				if ($es) {
					// Stripe is active, so we use the id that is set above
					$customer_id = $customer_id;
				} else if ($efd) {
					$fd_card_details = fd_customer_id();
					if (!empty($fd_card_details)) {
						$customer_id = $fd_card_details['fd_token'];
					}
				} else if ($eauthnet) {
					$authorize_customer_id = authnet_customer_id();
					if (!empty($authorize_customer_id)) {
						$customer_id = $authorize_customer_id['authorizenet_payment_profile_id'];
					} else {
						$customer_id = "";
					}
				}
				$customer_id = apply_filters('idc_checkout_form_customer_id', $customer_id, $product_id, $settings);

				$esc = $esc;
				$check_claim = apply_filters('md_level_owner', get_option('md_level_' . $product_id . '_owner'));
				if (!empty($check_claim)) {
					if ($esc == '1') {
						$md_sc_creds = get_sc_params($check_claim);
						if (!empty($md_sc_creds)) {
							$sc_accesstoken = $md_sc_creds->access_token;
							$sc_pubkey = $md_sc_creds->stripe_publishable_key;
						}
					}
					if ($epp == '1') {
						$claimed_paypal = get_user_meta($check_claim, 'md_paypal_email', true);
					}
				}
			}
		}
	}
	$cc_currency_symbol = '$';
	$cc_currency = 'USD';
	if ($es == 1) {
		if (!class_exists('Stripe')) {
			require_once IDC_PATH . 'lib/Stripe.php';
		}
		if (isset($test) && $test == '1') {
			Stripe::setApiKey($tsk);
			Stripe::setApiVersion($stripe_api_version);
		} else {
			Stripe::setApiKey($sk);
			Stripe::setApiVersion($stripe_api_version);
		}
		// get stripe currency
		$stripe_currency = 'USD';
		$stripe_symbol = '$';
		$settings = get_option('memberdeck_gateways');
		if (!empty($settings)) {
			if (is_array($settings)) {
				$stripe_currency = $settings['stripe_currency'];
				$stripe_symbol = md_currency_symbol($stripe_currency);
			}
		}
	} else if (isset($efd) && $efd == 1) {
		$endpoint = 'https://api.globalgatewaye4.firstdata.com/transaction/v12';
		$wsdl = 'https://api.globalgatewaye4.firstdata.com/transaction/v12/wsdl';
	}

	// use that id to get our level data
	$all_levels = bii_ID_Member_Level::get_AllLevels($product_id);
//	pre(bii_ID_Member_Level::get_level(2));
	$levelindex = 1;
	if (isset($_GET["level"])) {
		$levelindex = $_GET["level"] * 1;
	}
//	pre($levelindex,"grey");
	$returnarray = $all_levels[$levelindex];
	$return = apply_filters("bii_looklikevanillalevel",$returnarray);
//	pre($return, "green");
	// we have that data, lets store it in vars
	$level_name = $returnarray["title"];
	$level_price = $returnarray["price"];
	$limit_term = $returnarray["limit"];


	$txn_type = $return->txn_type;
	$currency = memberdeck_pp_currency();
	if (!empty($currency)) {
		$pp_currency = $currency['code'];
		$pp_symbol = $currency['symbol'];
	} else {
		$pp_currency = 'USD';
		$pp_symbol = '$';
	}
	// If payment gateway for CC payments is Authorize.Net, and level is recurring, make instant_checkout false
	if ($return->level_type == 'recurring' && $gateways['eauthnet'] == 1) {
		$instant_checkout = false;
	}

	$type = $return->level_type;
	$recurring = $return->recurring_type;
	$limit_term = $return->limit_term;
	$term_length = $return->term_length;
	$combined_product = $return->combined_product;	

	$credit_value = $return->credit_value;
	$cf_level = false;
	if ($crowdfunding) {
		$cf_assignments = get_assignments_by_level($product_id);
		if (!empty($cf_assignments)) {
			$project_id = $cf_assignments[0]->project_id;
			$project = new ID_Project($project_id);
			$the_project = $project->the_project();
			$post_id = $project->get_project_postid();
			$id_disclaimer = get_post_meta($post_id, 'ign_disclaimer', true);
		}
	}

	// Getting credits value, if the product can be purchased using credits and if the user have credits, then add an option to purhcase using credits
	$paybycrd = 0;
	$member_credits = 0;
	if (isset($general['enable_credits']) && $general['enable_credits'] == 1) {
		if (isset($member)) {
			$member_credits = $member->get_user_credits();
		}
		if ($member_credits > 0) {
			if (isset($pwyw_price) && $global_currency == 'credits') {
				$credit_value = $pwyw_price;
			}
			if ($credit_value > 0 && $credit_value <= $member_credits) {
				$paybycrd = 1;
			}
		}
	}

	if ($ecb) {
		$cb_currency = (isset($gateways['cb_currency']) ? $gateways['cb_currency'] : 'BTC');
		$cb_symbol = md_currency_symbol($cb_currency);
	}

	// If there is a combined product for currency loaded product, then we have to see if payment gateway supports it or not
	// then show text in General text that this product is combined with another
	if ($combined_product) {
		$combined_level = ID_Member_Level::get_level($combined_product);
		// Now see if any CreditCard gateway is active which supports recurring products, we just need to see if we have
		// to show that text or not in General text of different payment methods
		$combined_purchase_gateways = idc_combined_purchase_allowed($settings);
	} else {
		$combined_purchase_gateways = array();
	}

	if (1) {
		// they don't own this level, send forth the template
//		pre($level_price,"blue");
		$level_price = apply_filters('idc_product_price', $level_price, $product_id, $return);
//		pre($level_price,"red");
		if ($level_price !== '' && $level_price > 0) {
			$level_price = number_format(floatval($level_price), 2, '.', ',');
		}
//		pre($level_price,"orange");
		// Getting the option to show terms checkbox and page content
		$receipt_settings = get_option("md_receipt_settings");
		if (!is_array($receipt_settings)) {
			$receipt_settings = unserialize($receipt_settings);
		}
		// Getting the content of the terms page
		if (!empty($receipt_settings['terms_page'])) {
			$terms_content = get_post($receipt_settings['terms_page']);
		}
		if (!empty($receipt_settings['privacy_page'])) {
			$privacy_content = get_post($receipt_settings['privacy_page']);
		}

		include_once IDCE_PATH . 'templates/_checkoutForm.php';
		$content = ob_get_contents();
	} else {
		// they already own this one
		$content = '<form method="POST" id="idc_already_purchased tets" name="idc_already_purchased">';
		$content .= '<p>' . __('You already own this product. Please', 'memberdeck') . ' <a href="' . wp_logout_url() . '">' . __('logout', 'memberdeck') . '</a> ' . __('and create a new account in order to purchase again', 'memberdeck') . '.</p>';
		$content .= '<input type="hidden" name="user_email" class="user_vars" value="' . $email . '"/>';
		$content .= '<input type="hidden" name="user_login" class="user_vars" value="' . $current_user->user_login . '"/>';
		$content .= '</form>';
	}
	ob_end_clean();

	return $content;
}

function rev_slider_alias_homeslider() {
	return do_shortcode('[rev_slider alias="homeslider"]');
}

function bii_SC_menu_prequel($attrs) {
	if ($_SERVER["REMOTE_ADDR"] == "77.154.194.84") {
		ob_start();
		include_once IDCE_PATH . 'templates/_mdProfileTabs.php';
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
}

function bii_SC_prequel_dashboard() {

	ob_start();
	global $crowdfunding;
	if (function_exists('idf_get_querystring_prefix')) {
		$prefix = idf_get_querystring_prefix();
	} else {
		$prefix = '?';
	}
	$instant_checkout = instant_checkout();
	/* Mange Dashboard Visibility */
	if (is_user_logged_in()) {
		global $current_user;
		$user_id = $current_user->ID;
		//global $customer_id; --> will trigger 1cc notice
		get_currentuserinfo();
		$fname = $current_user->user_firstname;
		$lname = $current_user->user_lastname;
		$registered = $current_user->user_registered;
		$key = md5($registered . $current_user->ID);
		// expire any levels that they have not renewed
		$level_check = memberdeck_exp_checkondash($current_user->ID);
		// this is an array user options
		$user_levels = ID_Member::user_levels($current_user->ID);
	}

	if (isset($user_levels)) {
		// this is an array of levels a user has access to
		$access_levels = unserialize($user_levels->access_level);
		if (is_array($access_levels)) {
			$unique_levels = array_unique($access_levels);
		}
	}

	$downloads = ID_Member_Download::get_downloads();
	// we have a list of downloads, but we need to get to the levels by unserializing and then restoring as an array
	if (!empty($downloads)) {
		// this will be a new array of downloads with array of levels
		$download_array = array();
		foreach ($downloads as $download) {
			$new_levels = unserialize($download->download_levels);
			unset($download->download_levels);
			// lets loop through each level of each download to see if it matches
			$pass = false;
			if (!empty($new_levels)) {
				foreach ($new_levels as $single_level) {
					if (isset($unique_levels) && in_array($single_level, $unique_levels)) {
						// if this download belongs to our list of user levels, add it to array
						//$download->download_levels = $new_levels;
						$pass = true;
						$e_date = ID_Member_Order::get_expiration_data($user_id, $single_level);
					}
				}
			}
			if (isset($user_id))
				$license_key = MD_Keys::get_license($user_id, $download->id);

			// Putting image URL on image_link according to new changes, as attachment_id might be stored in that field instead of URL
			if (!empty($download->image_link) && stristr($download->image_link, "http") === false) {
				$download_thumb = wp_get_attachment_image_src($download->image_link, 'idc_dashboard_download_image_size');
				if (!empty($download_thumb)) {
					$download->image_link = $download_thumb[0];
					$width = $download_thumb[1];
					$height = $download_thumb[2];
					if (function_exists('idf_image_layout_by_dimensions')) {
						$image_layout = idf_image_layout_by_dimensions($width, $height);
					} else {
						$image_layout = 'landscape';
					}
					$download->image_width = $width;
					$download->image_height = $height;
					$download->image_layout = $image_layout;
				}
			} else if (empty($download->image_link)) {
				$download->image_link = plugins_url('images/dashboard-download-placeholder.jpg', __FILE__);
				$download->image_layout = 'landscape';
			} else {
				$download->image_layout = 'landscape';
			}
			if ($pass) {
				$days_left = idmember_e_date_format($e_date);
				$download->key = $license_key;
				$download->days_left = $days_left;
				$download_array['visible'][] = $download;
			} else {
				$download_array['invisible'][] = $download;
			}
		}
		// we should now have an array of downloads that this user has accces to
	}
	if (is_user_logged_in()) {
		$dash = get_option('md_dash_settings');
		$general = maybe_unserialize(get_option('md_receipt_settings'));
		if (!empty($dash)) {
			if (!is_array($dash)) {
				$dash = unserialize($dash);
			}
			if (isset($dash['layout'])) {
				$layout = $dash['layout'];
			} else {
				$layout = 1;
			}
			if (isset($dash['alayout'])) {
				$alayout = $dash['alayout'];
			} else {
				$alayout = 'md-featured';
			}
			$aname = $dash['aname'];
			if (isset($dash['blayout'])) {
				$blayout = $dash['blayout'];
			} else {
				$blayout = 'md-featured';
			}
			$bname = $dash['bname'];
			if (isset($dash['clayout'])) {
				$clayout = $dash['clayout'];
			} else {
				$clayout = 'md-featured';
			}
			$cname = $dash['cname'];
			if ($layout == 1) {
				$p_width = 'half';
				$a_width = 'half';
				$b_width = 'half';
				$c_width = 'half';
			} else if ($layout == 2) {
				$p_width = 'half';
				$a_width = 'half';
				$b_width = 'full';
				$c_width = 'full';
			} else if ($layout == 3) {
				$p_width = 'full';
				$a_width = 'full';
				$b_width = 'full';
				$c_width = 'full';
			} else if ($layout == 4) {
				$p_width = 'half';
				$a_width = 'half-tall';
				$b_width = 'half';
				$c_width = 'hidden';
			}
			if (isset($dash['powered_by'])) {
				$powered_by = $dash['powered_by'];
			} else {
				$powered_by = 1;
			}
		}

		// If credits are enabled from settings, then get available credits, else set them to 0
		if (isset($general['enable_credits']) && $general['enable_credits'] == 1) {
			$md_credits = md_credits();
		} else {
			$md_credits = 0;
		}
		$settings = get_option('memberdeck_gateways', true);
		if (isset($settings)) {
			$es = (isset($settings['es']) ? $settings['es'] : 0);
			$efd = (isset($settings['efd']) ? $settings['efd'] : 0);
			$eauthnet = (isset($settings['eauthnet']) ? $settings['eauthnet'] : 0);
			if ($es == 1) {
				$customer_id = customer_id();
			} else if ($efd == 1) {
				$fd_card_details = fd_customer_id();
				if (!empty($fd_card_details)) {
					$fd_token = $fd_card_details['fd_token'];
					$customer_id = $fd_card_details;
				}
			} else if ($eauthnet == 1) {
				$authorize_customer_id = authnet_customer_id();
				if (!empty($authorize_customer_id)) {
					$customer_id = $authorize_customer_id['authorizenet_payment_profile_id'];
				} else {
					$customer_id = "";
				}
			}
			$customer_id = apply_filters('idc_checkout_form_customer_id', (isset($customer_id) ? $customer_id : ''), '', $settings);
		}
		if ($md_credits > 0 || !empty($customer_id)) {
			$show_occ = true;
		} else {
			$show_occ = false;
		}
		include_once IDCE_PATH . 'templates/admin/_memberDashboard.php';
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	} else {
		include_once IDCE_PATH . 'templates/_protectedPage.php';
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}

function bii_SC_projet_mini($attrs) {
	$post_id = $attrs["id"];
	if (get_post_status($post_id) !== false) {


		$post = get_post($post_id);

		$status = $post->post_status;
		if ($status != "trash") {
			if (isset($attrs["nobootsrap"]) && $attrs["nobootsrap"]) {
				$class_bootstrap = "bii-screenwidth-listener";
			} else {
				$class_bootstrap = apply_filters("bii_bootstrap_class", $attrs, $md = 2, $sm = 2, $xs = 1, $xss = 1, $lg = 2);
			}
			$user_id = get_current_user_id();
			$thumb_id = get_post_thumbnail_id($post);

//	pre($thumb_id,"red");
			$thumb_url_array = wp_get_attachment_image_src($thumb_id);
			$thumb = $thumb_url_array[0];
			$project_raised = get_post_meta($post_id, "ign_fund_raised")[0] . " €";
			$percent_raised = round(get_post_meta($post_id, "ign_percent_raised")[0]) . " %";
			$daysleft = get_post_meta($post_id, "ign_days_left")[0];
			$project_id = get_post_meta($post_id, "ign_project_id")[0];
			$company_location = get_post_meta($post_id, "ign_company_location")[0];
			$project_goal = ((int) get_post_meta($post_id, "ign_fund_goal")[0]) . " €";
			$urlmodif = get_bloginfo("url") . "/modifier-un-projet/?numero_projet=$post_id";
			$actions = "<a title='Edit Project' href='$urlmodif'>Modifier ce projet <i class='fa fa-edit'></i></a>";
			$project = bii_project::fromIdPost($post_id);
			$project_cat = utf8_encode($project->getCategorieName());
			$project_nb_contributeurs = (int) bii_SC_Project_Supporters(["product" => $project_id]);

			ob_start();
			?>
			<li class="myprojects project-dashboard <?= $class_bootstrap ?> author-<?php echo $post->post_author; ?>" data-author="<?php echo $post->post_author; ?>">
				<div class="myproject_wrapper">
					<div class="project-item">
						<div class="project-thumb image" style="<?php echo (!empty($thumb) ? 'background-image: url(\'' . $thumb . '\');' : ''); ?>"></div>
						<div class="project-wrapper">
							<div class="project-name"><?php echo get_the_title($post_id); ?></div>
							<div class="project-status <?php echo strtolower($status); ?>">
			<?= ucfirst(rfidc_status($status)); ?>
								<div class="project-item-wrapper <?php echo strtolower($status); ?>">
									<div class="option-list">
			<?= apply_filters('id_myprojects_actions', $actions, $post, $user_id); ?>
									</div>
								</div>
							</div>
							<div class="haspicto project-category"><?= $project_cat; ?></div>
							<div class="haspicto project-place"><?= $company_location; ?></div>
							<div class="wrapper-objectifandbar">
								<div class="haspicto project-goal">Objectif : <?= $project_goal; ?></div>
			<?= do_shortcode("[project_percentage_bar product='$project_id']"); ?>
							</div>
							<div class="haspicto percent-raised" title="Le projet est terminé à <?= $percent_raised; ?>"><?= $percent_raised; ?></div>
							<div class="haspicto project-raised" title="Les différentes donations vous ont permis de collecter <?= $project_raised; ?>"><?= $project_raised; ?></div>
							<div class="haspicto project-days-left" title="Il reste <?= $daysleft; ?> jours pour finir votre projet"><?= $daysleft; ?></div>
							<div class="haspicto project-supporters" title="Les différentes donations ont été effectuées par <?= $project_nb_contributeurs; ?> personnes"><?= $project_nb_contributeurs; ?></div>
							<div style="clear:both;display:table;"></div>
						</div>
					</div>
				</div>

			</li>
			<?php
			$contents = ob_get_contents();
			ob_end_clean();
			return $contents;
		}
	}
}

function bii_SC_projets_all($attrs) {
	if ($_SERVER["REMOTE_ADDR"] == "77.154.194.84") {
//		pre(get_current_user_id());
		if (get_current_user_id()) {
			$projects = getProjects();
			if ((bool) $projects) {

//				$output = "";
//				foreach ($projects as $key => $id) {
//					$array = ["id" => $id, "nobootsrap" => "1"];
//					$output .= bii_SC_projet_mini($array);
//				}
				$output = do_shortcode('[ess_grid alias="griduser"]');
				return $output;
			} else {
				$output = '<div class="ignitiondeck"><p class="notification orange">Vous n\'avez pas encore de projets !<br />'
					. '<strong>N\'hésitez plus, creez votre projet en suivant <a href="/preinscription/">ce lien</a></strong></p></div>';
				return $output;
			}
		} else {
			include_once IDCE_PATH . 'templates/_protectedPage.php';
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
}

function bii_SC_moteur_recherche($attrs) {
	if (!isset($attrs["el_class"])) {
		$attrs["el_class"] = "";
	}
	$class_bootstrap = apply_filters("bii_bootstrap_class", $attrs, $md = 2, $sm = 2, $xs = 1, $xss = 1, $lg = 2);

	$class = $attrs["el_class"];
	?>
	<div class="<?= $class ?>">

		<form id="recherche-projets">
			<div id="minimum-recherche">

				<label for="recherche-simple"><h2 class="recherche-titre">Votre recherche</h2><span class="fa fa-search"></span> </label>

				<input id="recherche-simple" placeholder="Votre recherche"/>
				<button class="recherche-submit"><span class="fa fa-check"></span> Rechercher</button>
				<button class="recherche-btn elargir"><span class="fa fa-caret-down"></span></button>

			</div>
			<div id="maximum-recherche">
				<div class="<?= $class_bootstrap ?>">
					<h3>Date de début</h3>

				</div>

			</div>

		</form>
	</div>
	<?php
}

function bii_SC_Project_Supporters($args) {
	$project_id = false;
	$nb = 0;
	if (isset($args["product"])) {
		$project_id = $args['product'];
	}
	if (isset($args["id_post"])) {
		$project_id = get_post_meta($args["id_post"], "ign_project_id")[0];
	}

	if ($project_id) {
		$nb = ign_pay_info::nb("product_id = $project_id");
	}
	return $nb;
}

function bii_SC_id_purchaseForm($attrs) {
	ob_start();
	require '/var/www/upyourtown/wp-content/plugins/ignitiondeck-crowdfunding/languages/text_variables.php';
	if (isset($attrs['product'])) {
		$project_id = absint($attrs['product']);
	}
	if (isset($_GET['prodid'])) {
		$project_id = absint($_GET['prodid']);
	}
	if (isset($_GET['level'])) {
		$level = absint($_GET['level']);
	}

	if (isset($project_id)) {
		$form = new ID_Purchase_Form($project_id);
		$purchase_form = $form->id_purchase_form();
		$post_id = $purchase_form->post_id;
	} else {
		$project_id = null;
	}

	if (!isset($_SESSION['paypal_errors_content'])) {
		$_SESSION['paypal_errors_content'] = "";
	}
	if (empty($purchase_form->form_settings) || empty($purchase_form->form_settings['email'])) {
		$_SESSION['paypal_errors_content'] = __('The email field must be enabled via the project settings menu.', 'ignitiondeck');
	}
	include IDCE_PATH . '/templates/_purchaseForm.php';
	$purchase_form = ob_get_contents();
	ob_end_clean();
	$purchase_form = apply_filters('id_purchase_form', $purchase_form, $project_id);
	return $purchase_form;
}

remove_shortcode('project_purchase_form');
add_shortcode('project_purchase_form', 'bii_SC_id_purchaseForm');

add_shortcode('project_grid_responsive', 'id_projectGrid_responsive');
add_shortcode('jordan', "rev_slider_alias_homeslider");
add_shortcode('rev_slider_alias_homeslider', "rev_slider_alias_homeslider");

add_shortcode('project_submission_form_frontpage', 'id_submissionFormFront');
add_shortcode('prequel_menu', 'bii_SC_menu_prequel');
add_shortcode('prequel_dashboard', 'bii_SC_prequel_dashboard');
add_shortcode('prequel_projet', 'bii_SC_projet_mini');
add_shortcode('prequel_projets', 'bii_SC_projets_all');
add_shortcode('prequel_supporters', function($args) {
	$nb = (int) bii_SC_Project_Supporters($args);
	if (isset($args["text"])) {
		$text = $args["text"];
		if ($nb < 2 && $args["text-singulier"]) {
			$text = $args["text-singulier"];
		}
		return str_replace("%nombre%", "<span class='value'>" . $nb . "</span>", $text);
	}
	return $nb;
});

remove_shortcode('idc_checkout');
add_shortcode('idc_checkout', 'bii_memberdeck_checkout');

add_shortcode('projet_jours_restants', function($args) {
	if (isset($args["product"])) {
		$project_id = $args['product'];
		$project = new ID_Project($project_id);
		$post_id = $project->get_project_postid();
		$dl = get_post_meta($post_id, "ign_days_left")[0] * 1;

		$s = "s";
		if ($dl == 1) {
			$s = "";
		}
		if ($dl === 0) {

			$dl = "&infin;";
			$classspan = "inf";
		}
		return "<div class='projet_jours_restants'><span class='value $classspan'>$dl</span><span class='legend'> jour$s restant$s</span></div>";
	}
});
add_shortcode('projet_fonds_sur_but', function($args) {
	if (isset($args["product"])) {
		$project_id = $args['product'];
		$project = new ID_Project($project_id);
		$post_id = $project->get_project_postid();
//		$recoltes = get_post_meta($post_id, "ign_fund_raised")[0] * 1;
		$restants = ((int) get_post_meta($post_id, "ign_fund_goal")[0]);
//		pre("[projet_fonds_sur_but product='$post_id']");
		$return_text = "";
		$return_text .= "<span class='value restants'>$restants €</span>";
		$return_text .= "<span class='legend'>Objectif</span>";


		return "<div class='projet_fonds_sur_but'>$return_text</div>";
	}
});
add_shortcode('projet_propose_par', function($args) {
	if (isset($args["product"])) {
		$project_id = $args['product'];
		$project = new ID_Project($project_id);
		$post_id = $project->get_project_postid();
//		pre($post_id,"green");
		$nom = get_post_meta($post_id, "ign_company_name")[0];
		$ville = get_post_meta($post_id, "ign_company_location")[0];
		$ret = "";
		if ($nom) {
			$ret.= "<div class='projet_propose_par'><span class='legend'>Proposé par </span><span class='value'>$nom</span> </div>";
			if ($ville) {
				$ret.= "<div class='projet_ville'><span class='legend'>à </span><span class='value'>$ville</span></div>";
			}
		} elseif ($ville) {
			$ret.= "<div class='projet_ville'><span class='legend'>Projet sur </span><span class='value'>$ville</span></div>";
		}
		return "$ret";
	}
});

add_shortcode("projet_jauge", function($args) {
	if (isset($args["product"])) {
		return "<div class='projet_jauge'>" . do_shortcode("[project_percentage_bar product='" . $args["product"] . "']") . "</div>";
	}
});
add_shortcode('projet_pourcentrealise', function($args) {
	if (isset($args["product"])) {
		$ret = "";
		$project_id = $args['product'];
		$project = new ID_Project($project_id);
		$post_id = $project->get_project_postid();
//		pre($post_id);
		$pourcent = round(get_post_meta($post_id, "ign_percent_raised")[0] * 1);
		$ret .= "<div class='projet_pourcentrealise'><span class='value'>" . $pourcent . " %</span><span class='legend'> récoltés</span></div>";
		return $ret;
	}
});
add_shortcode('projet_mini_description', function($args) {
	if (isset($args["product"])) {
		$ret = "";
		if (!isset($args["cut_at"])) {
			$args["cut_at"] = 100;
		}
		$project_id = $args['product'];
		$project = new ID_Project($project_id);
		$post_id = $project->get_project_postid();

		$desc = global_class::tronquer(get_post_meta($post_id, "ign_project_description")[0], $args["cut_at"]);
//		pre($desc);

		$ret = "<div class='projet_mini_description'><span class='value'>$desc</span></div>";
		return $ret;
	}
});
add_shortcode('projet_lien_edit', function($args) {
	if (isset($args["product"])) {
		$ret = "";
		if (!isset($args["cut_at"])) {
			$args["cut_at"] = 100;
		}
		$project_id = $args['product'];
		$project = new ID_Project($project_id);
		$post_id = $project->get_project_postid();
		$urlmodif = get_bloginfo("url") . "/modifier-un-projet/?numero_projet=$post_id";
		$actions = "<a title='Edit Project' href='$urlmodif'>Modifier ce projet <i class='fa fa-edit'></i></a>";

		$ret = "<div class='projet_lien_edit'>$actions</div>";
		return $ret;
	}
});

add_shortcode('projet_etat', function($args) {
	if (isset($args["product"])) {
		$ret = "";
		$project_id = $args['product'];
		$project = new ID_Project($project_id);
		$post_id = $project->get_project_postid();
		$endifclose = $nom = get_post_meta($post_id, "ign_project_closed")[0];
		$encours = "en cours";
		$termine = "terminé";
		$bientot = "bientôt terminé";
		$dl = get_post_meta($post_id, "ign_days_left")[0];
		$pourcent = get_post_meta($post_id, "ign_percent_raised")[0] * 1;
		$fe = get_post_meta($post_id, "ign_fund_end")[0];
		$fee = explode("/", $fe);
		$jour = $fee[1];
		$mois = $fee[0];
		$annee = $fee[2];
		$timestamp = mktime(23, 59, 59, $mois, $jour, $annee);

		$statut = $encours;

		if ($endifclose && $pourcent >= 100) {
			$statut = $termine;
		} elseif ($timestamp < time()) {
			$statut = $termine;
		} elseif ($dl && $dl < 10) {
			$statut = $bientot;
		}
		$ret .= "<div class='projet_etat'><span class='value'>$statut</span></div>";

		return $ret;
	}
});

add_shortcode('projet_categories', function($args) {
	if (isset($args["product"])) {
		$ret = "<ul class='projet_categorie'>";
		$project_id = $args['product'];
		$project = new ID_Project($project_id);
		$post_id = $project->get_project_postid();

		$posts = new posts($post_id);
//		pre($post_id);
//		$cats = [new terms(15)];
		$cats = $posts->getCategories(" AND taxonomy = 'project_category'");
		$limit = false;

		if (isset($args["limit"])) {
			$limit = $args["limit"];
		}
		$i = 0;
		foreach ($cats as $cat) {
			if ($limit === false || $i > $limit) {
				$ret.= "<li class='projet_categorie-item projet_categorie-" . $cat->slug() . "'>" . utf8_encode($cat->name()) . "</li>";
			}
			++$i;
		}
		$ret.="</ul>";
		return $ret;
	}
});



add_shortcode('moteur-recherche', 'bii_SC_moteur_recherche');

add_action("bii_specific_shortcodes", function() {
	?>
	<tr>
		<td><strong>[rev_slider_alias_homeslider]</strong></td>
		<td>affiche le shortcode [rev_slider alias="homeslider"] (les guillemets étaient échappés, et le shortcode ne fonctionnait pas)</td>
	</tr>
	<tr>
		<td><strong>[prequel_menu]</strong></td>
		<td>affiche le menu  Mon compte Mes projets Nouveau projet</td>
	</tr>
	<tr>
		<td><strong>[project_submission_form_frontpage]</strong></td>
		<td>affiche le formulaire de submission court</td>
	</tr>
	<tr>
		<td><strong>[prequel_projets]</strong></td>
		<td>affiche les projets de l'utilisateur actif<!--, il est possible d'inqiquer le nombre de colonnes responsive avec les paramètres columns columns-large columns-tablet et columns-phone--></td>
	</tr>
	<tr>
		<td><strong>[prequel_projet id='X']</strong></td>
		<td>affiche le projet d'id post = X<!--, les mêmes options que [prequel_projets] pour les colonnes sont disponibles --></td>
	</tr>
	<tr>
		<td><strong>[projet_propose_par project='X']</strong></td>
		<td>affiche le nom de l'entreprise qui a proposé le projet d'identifiant = X</td>
	</tr>
	<tr>
		<td><strong>[projet_fonds_sur_but project='X']</strong></td>
		<td>affiche {somme_recoltée} collectés sur {somme_demandée} pour le projet d'identifiant = X</td>
	</tr>
	<tr>
		<td><strong>[projet_jours_restants project='X']</strong></td>
		<td>affiche le nombre de jours restants pour le projet d'identifiant = X</td>
	</tr>
	<tr>
		<td><strong>[prequel_supporters project='X' &lt; text="texte" text-singulier="texte" &gt;]</strong></td>
		<td>affiche le nombre de contributeurs pour le projet d'identifiant = X, text-singulier s'il existe s'affichera si le nombre de contributeurs est inférieur à 2, sinon text est affiché. Si text n'existe pas seul le nombre d'affiche. Entrez %nombre% pour afficher le nombre</td>
	</tr>

	<?php
}, 1);
