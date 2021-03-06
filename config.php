<?php

function bii_listeClass() {
	$list = [
		"rpdo",
		"global_class",
		"bii_project",
		"eg_grids",
		"terms",
		"term_taxonomy",
		"posts",
		"postmeta",
		"ign_pay_info",
		"bii_order",
		"bii_order_meta",
	];
	return $list;
}

function bii_includeClass() {
	$liste = bii_listeClass();
	$pdpf = plugin_dir_path(__FILE__);
	foreach ($liste as $item) {
		require_once($pdpf . "/class/$item.class.php");
	}
}

bii_includeClass();

function rfidc_localeIDC() {

	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-autocomplete');
	wp_enqueue_script('jquery-ui-datepicker');
	wp_enqueue_script('IDCExtend', plugins_url('js/localeIDC.js', __FILE__), array('jquery'), null, true);
	wp_enqueue_style('IDCExtendstyle', plugins_url('css/localeIDC.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'rfidc_localeIDC');



if (!get_option("ignitiondeck_locale")) {
	update_option("ignitiondeck_locale", "fr-FR");
}
if (!get_option("ignitiondeck_lctime")) {
	update_option("ignitiondeck_lctime", "fr-FR");
}
setlocale(LC_TIME, get_option("ignitiondeck_lctime"));

function rfidc_monthlist($lang = "fr-FR") {
	switch ($lang) {
		case "fr-FR":
			$array = [
				"January" => "Janvier",
				"Feburay" => "Février",
				"Marth" => "Mars",
				"April" => "Avril",
				"May" => "Mai",
				"June" => "Juin",
				"July" => "Juillet",
				"August" => "Août",
				"September" => "Septembre",
				"October" => "Octobre",
				"November" => "Novembre",
				"December" => "Décembre",
			];
			break;
		default:
			$array = [
				"January" => "January",
				"Feburay" => "Feburay",
				"Marth" => "Marth",
				"April" => "April",
				"May" => "May",
				"June" => "June",
				"July" => "July",
				"August" => "August",
				"September" => "September",
				"October" => "October",
				"November" => "November",
				"December" => "December",
			];
			break;
	}
	return $array;
}

function rfidc_currency($lang = "fr-FR") {
	switch ($lang) {
		case "fr-FR":
			return ["name" => "euro", "currency" => "€", "position" => "after"];
		case "en-GB":
			return ["name" => "pound", "currency" => "£", "position" => "before"];
		default:
			return ["name" => "dollar", "currency" => "$", "position" => "before"];
	}
}

function rfidc_endmonth($end_time) {
	return rfidc_monthlist(get_option("ignitiondeck_locale"))[$end_time];
}

function rfidc_goal($goal, $id) {
	$unite = rfidc_currency(get_option("ignitiondeck_locale"));
	$amount = get_post_meta($id, 'ign_fund_goal', true) * 1;
	if ($unite["position"] == "before") {
		$amount = $unite["currency"] . $amount;
	} else {
		$amount .= " " . $unite["currency"];
	}
	return $amount;
}

function rfidc_fundraised($goal, $id) {
	$unite = rfidc_currency(get_option("ignitiondeck_locale"));
	$amount = get_post_meta($id, 'ign_fund_raised', true) * 1;
	if ($unite["position"] == "before") {
		$amount = $unite["currency"] . $amount;
	} else {
		$amount .= " " . $unite["currency"];
	}
	return $amount;
}

function rfidc_title_genitif($value) {
//	consoleLog($value);
	if (strpos($value, "' Projects") !== false || strpos($value, "'s Projects") !== false) {
		$value = "Projets de " . str_replace(["' Projects", "'s Projects"], ["", ""], $value);
	}
	return $value;
}

function rfidc_display_currency($value, $id) {
//	pre($value,"red");
	return $value;
}

function rfidc_status($value, $lang = "fr-FR") {
	return rfidc_status_traduction($lang)[$value];
}

function rfidc_status_traduction($lang = "fr-FR") {
	$array = [
		"inherit" => "inherit",
		"draft" => "draft",
		"pending" => "pending",
		"publish" => "publish",
		"trash" => "trash",
		"auto-draft" => "auto-draft",
	];
	switch ($lang) {
		case "fr-FR":
			return [
				"inherit" => "hérité",
				"draft" => "brouillon",
				"pending" => "en attente",
				"publish" => "publié",
				"trash" => "corbeille",
				"auto-draft" => "corbeille",
			];
		default:return $array;
	}
}

add_filter('id_end_month', 'rfidc_endmonth', 10, 1);
add_filter('id_project_goal', 'rfidc_goal', 10, 2);
add_filter('id_funds_raised', 'rfidc_fundraised', 10, 2);
add_filter('id_display_currency', 'rfidc_display_currency', 10, 2);
add_filter('the_title', 'rfidc_title_genitif', 10, 2);

add_action('after_setup_theme', 'bii_ast');

function bii_ast() {
	add_filter('wp_nav_menu_items', 'bii_removepillconnexion', 10, 2);
}

function bii_removepillconnexion($nav, $args) {
	$url = get_bloginfo('url');
	$inscription = $moncompte = $connexion = "";
	if (get_option("bii_inscriptions")) {
		$inscription = '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-id-custom uyt-inscription"><a href="' . $url . '/inscription/"><span class="fa fa-user-plus"></span> S\'inscrire</a></li>';
	}
	if (get_option("bii_acessmoncompte")) {
		$moncompte = '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-id-custom uyt uyt-moncompte"><a href="' . $url . '/mon-compte/"><span class="fa fa-user"></span> Mon compte</a></li>';
		$connexion = '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-id-custom uyt uyt-connexion"><a href="' . $url . '/mon-compte/"><span class="fa fa-sign-in"></span> Se connecter</a></li>';
	}
	$remove = [
		'">Mon compte</a>',
		'">Se connecter</a>',
		'Déconnexion',
		'Create Account',
		'<li class="login right">',
		'?page_id=6',
		'?action=register',
		'createaccount buttonpadding',
	];
	$replace = [
		'"><span class="fa fa-user"></span> Mon compte</a>',
		'"><span class="fa fa-sign-in"></span> Se connecter</a>',
		"<span class='fa fa-sign-out'></span> Se déconnecter",
		"<span class='fa fa-user-plus'></span> S'inscrire",
		'<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-id-custom uyt">',
		"mon-compte",
		"",
		"menu-item menu-item-type-custom menu-item-object-custom menu-item-id-custom uyt uyt-moncompte",
	];
	$nav = str_replace($remove, $replace, $nav);
	return $nav;
}

function bii_menu() {

	add_menu_page(__(global_class::wp_slug_menu()), __(global_class::wp_titre_menu()), global_class::wp_min_role(), global_class::wp_nom_menu(), global_class::wp_dashboard_page(), global_class::wp_dashicon_menu());
}

add_action('admin_menu', 'bii_menu');

function bii_dashboard() {
	wp_enqueue_script('admin-init', plugins_url('/admin/js/dashboard.js', __FILE__), array('jquery'), null, true);
	wp_enqueue_style('bii-admin-css', plugins_url('/admin/css/admin.css', __FILE__));
	include('admin/dashboard.php');
}

function bii_add_new_level_ajax() {
	include("ajax/ajax_add_new_level.php");
	die();
}

add_action('wp_ajax_nopriv_bii_get_post', 'bii_get_post_ajax');
add_action('wp_ajax_bii_add_new_level', 'bii_add_new_level_ajax');
add_action('wp_ajax_nopriv_bii_add_new_level', 'bii_add_new_level_ajax');

function bii_dashboard_button_main() {
	$array_active = ["désactivé", "activé"];
	?>
	<tr><td>Le plugin est </td><td class="notbutton"><strong><?= $array_active[get_option("bii_IDCE_installed")]; ?></strong></td></tr>
	<tr><td>Les inscriptions sont </td><td><?= bii_makebutton("bii_inscriptions", true, true); ?></td></tr>
	<tr><td>L'accès au compte est </td><td><?= bii_makebutton("bii_acessmoncompte"); ?></td></tr>
	<?php
}

function bii_option_submit() {
	logRequestVars();
	?>
	<div class="notice">
		<p>Les modifications ont été enregistrées</p>
	</div>
	<?php
}

add_action("bii_informations", "bii_dashboard_button_main", 1);
add_action("bii_options_submit", "bii_option_submit", 1);

add_filter("bii_h1", function($title = "") {
	if (!$title) {
		$title = get_the_title();
	}
	return $title;
}, 10, 1);

add_filter("bii_bootstrap_class", function($attrs, $md, $sm, $xs, $xss, $lg) {
	if (isset($attrs['columns'])) {
		$md = $attrs['columns'];
	}
	if (isset($attrs['columns-large'])) {
		$lg = $attrs['columns-large'];
	}
	if (isset($attrs['columns-middle'])) {
		$md = $attrs['columns-middle'];
	}
	if (isset($attrs['columns-tablet'])) {
		$sm = $attrs['columns-tablet'];
	}
	if (isset($attrs['columns-phone'])) {
		$xs = $attrs['columns-phone'];
	}
	if (isset($attrs['columns-phone-portrait'])) {
		$xss = $attrs['columns-phone-portrait'];
	}
	$class_bootstrap = bootstrap_builder($md, $sm, $xs, $xss, $lg);

	return $class_bootstrap;
}, 10, 6);

add_filter('id_register_project_post_rewrite', function($value) {
	$array = array('slug' => "nos-projets", 'with_front' => true);
	return $array;
}, 10, 1);

add_action("between_header_and_containerwrapper", function($title = "") {
	$title = apply_filters("bii_h1", $title);
	?>
	<div id="site-description">
		<h1><?= $title ?></h1>
	</div>
	<?php
	if (is_front_page()) {
		echo do_shortcode("[rev_slider_alias_homeslider]");
	}
}, 10, 1);

add_action("bii_before_footer", function() {
	?>
	<div class="bii-left-menu">
		<div class="close-toogle"><i class="fa fa-times"></i></div>
		<div class="search-wrapper">
			<div id="header-search-left" class="search-form">
				<form method="get" id="searchform" action="http://upyourtown.com/">
					<div>
						 <!--<label for="s-foot" class="screen-reader-text"><i class="fa fa-search"></i></label>--> 
						<input type="text" placeholder="Votre recherche" value="" name="s" id="s-foot">
						<input type="submit" id="searchsubmit_footer" value="Chercher">
					</div>
				</form>
			</div>


		</div>

		<?php
		// Using wp_nav_menu() to display menu
		wp_nav_menu(array(
			'menu' => 'main-menu', // Select the menu to show by Name
			'class' => "",
			'container' => false, // Remove the navigation container div
			'theme_location' => 'main-menu',
			'walker' => new Stellar_Sub_Menu(),
			'fallback_cb' => 'stellar_default_menu'
			)
		);
		?>

	</div>
	<?php
});

add_action("idc_below_login_form", function() {
//	if($_SERVER["REMOTE_ADDR"] == "77.154.194.84"){
	do_action('facebook_login_button');
//		}
});



add_filter("idc_class_containerwrapper", function($post_type, $is_home) {
	$class = "";
	if ($post_type) {
		$class.= " $post_type";
	}
	$class .= " containerwrapper";
	if ($is_home) {
		$class .= "-home";
	}
	$uid = get_current_user_id();
	if ($uid) {
		$class .= " bii-uc";
	} else {
		$class .= " bii-ud";
	}
	$class .= apply_filters("idc_moar_class_containerwrapper");
	return $class;
}, 10, 2);

add_action("idf_general_social_buttons", function() {
	global $post;
	$url = get_permalink();
	$text = $titre_campagne = $subject = $bodymail = $nom_entr = "";
	$bloginfoname = get_bloginfo("name");
	$idpost = $post->ID;
//	pre($idpost);
	if ($idpost) {
		$titre_campagne = $post->post_title;
		$nom_entr = get_post_meta($idpost, "ign_company_name")[0];
		$subject = $text = "Venez découvrir le projet de financement partipatif de $nom_entr sur $bloginfoname";
		$bodymail = "Vous vouvez voir ce projet sur l'adresse suivante : $url";
	}

	$array_social = [
		'facebook' => ["icon" => "facebook", "text" => "partager sur facebook", "href" => "https://www.facebook.com/sharer/sharer.php?u=$url"],
		'twitter' => ["icon" => "twitter", "text" => "partager sur twitter", "href" => "https://twitter.com/intent/tweet/?url=$url&text=$text"],
		'google+' => ["icon" => "google-plus", "text" => "partager sur google+", "href" => "https://plus.google.com/share?url=$url&hl=fr"],
//		'pinterest' => ["icon" => "pinterest", "text" => "pin-it","href"=>"https://pinterest.com/pin/create/button/?url=$url&media={media}&description={description}"],
		'mail' => ["icon" => "envelope", "text" => "envoyer par mail", "href" => "mailto:?subject=$subject&body=$bodymail"],
	];
	?>
	<ul>
		<?php foreach ($array_social as $key => $val) { ?>
			<li class="social "><a href="<?= $val["href"] ?>" target="_blank"><span class="fa fa-<?= $val["icon"] ?>"></span> <?= $val["text"] ?></a></li>
			<?php } ?>
	</ul>
	<?php
});
add_filter("bii-esg-additional_query", function($additional_query) {
	if (strpos($additional_query, "bii_currentuserposts") !== false) {

		$imp = implode(",", posts::currentUserPosts());
		$replace = "array($imp)";
		$additional_query = str_replace("bii_currentuserposts", $replace, $additional_query);
	}
	return $additional_query;
}, 10, 1);


add_filter('essgrid_query_caching', 'eg_stop_caching', 10, 2);
add_filter('essgrid_get_posts', 'eg_mod_query', 10, 2);

add_action("ide_fes_submit", function($post_id, $project_id, $vars) {
//	bii_write_log($vars);	
}, 10, 3);
add_action("ide_fes_update", function($user_id, $project_id, $post_id, $proj_args, $saved_levels, $saved_funding_types) {
//	bii_write_log("ide_fes_update");	
//	bii_write_log($saved_levels);	
}, 10, 6);

//
//add_filter('idc_price_format', function($amount, $gateway = null) {
//	if (isset($_GET["price"]) && $_GET["price"]) {
//		$amount = $_GET["price"]*1;
//	}
//	if($amount < 0){
//		$amount = 0;
//	}
//	$amount = number_format($amount,2);
//	return $amount;
//}, 10, 2);
// turn off caching for your grid
function eg_stop_caching($do_cache, $grid_id) {

	$grid = new eg_grids($grid_id);
	$handle = $grid->handle();

	if ($handle == 'griduser') {
		return false;
	}
	return true;
}

function eg_mod_query($query, $grid_id) {
	$grid = new eg_grids($grid_id);
	$handle = $grid->handle();

	if ($handle == 'all_projets') {
		
	}
	if ($handle == 'griduser') {
		$uid = get_current_user_id();
		if ($uid) {
			$query["author"] = $uid;
		} else {
			$query["author_name"] = "none";
		}
	}


	return $query;
}

remove_action('admin_menu', 'memberdeck_add_menus', 11);
add_action('admin_menu', 'bii_memberdeck_add_menus', 11);

function bii_memberdeck_add_menus() {
	//if (current_user_can('manage_options')) {
	$settings = add_menu_page(__('IDC', 'memberdeck'), 'IDC', 'idc_manage_products', 'idc', 'idc_settings', plugins_url('/images/ignitiondeck-menu.png', __FILE__));
	$settings = add_submenu_page('options-general.php', 'MemberDeck', 'MemberDeck', 'manage_options', 'memberdeck-settings', 'memberdeck_settings');
	$users = add_submenu_page('idc', __('Members', 'memberdeck'), __('Members', 'memberdeck'), 'idc_manage_members', 'idc-users', 'idc_users');
	$orders = add_submenu_page('idc', __('Orders', 'memberdeck'), __('Orders', 'memberdeck'), 'idc_manage_orders', 'idc-orders', 'bii_idc_orders');
	$payments = add_submenu_page('idc', __('Gateways', 'memberdeck'), __('Gateways', 'memberdeck'), 'idc_manage_gateways', 'idc-gateways', 'idc_gateways');
	$email = add_submenu_page('idc', __('Email', 'memberdeck'), __('Email', 'memberdeck'), 'idc_manage_email', 'idc-email', 'idc_email');
	$pathways = add_submenu_page('idc', __('Upgrades', 'memberdeck'), __('Upgrades', 'memberdeck'), 'idc_manage_products', 'idc-pathways', 'idc_pathways');
	$view_order = add_submenu_page('', __('View Order', 'memberdeck'), "", 'idc_manage_orders', 'idc-view-order', 'view_order_details');
	$edit_order = add_submenu_page('', __('Edit Order', 'memberdeck'), "", 'idc_manage_orders', 'idc-edit-order', 'edit_order_details');

	add_action('admin_print_styles-' . $email, 'idc_email_scripts');
	global $crowdfunding;
	if ($crowdfunding) {
		$bridge_settings = add_submenu_page('idc', __('Crowdfunding', 'mdid'), __('Crowdfunding', 'mdid'), 'idc_manage_crowdfunding', 'idc-bridge-settings', 'idc_bridge_settings');
		add_action('admin_print_styles-' . $bridge_settings, 'mdid_admin_scripts');
		if (is_id_pro()) {
			$enterprise_settings = add_submenu_page('idc', __('Enterprise Settings', 'mdid'), __('Enterprise Settings', 'mdid'), 'idc_manage_gateways', 'idc-enterprise-settings', 'idc_enterprise_settings');
			add_action('admin_print_styles-' . $enterprise_settings, 'md_sc_scripts');
		}
	}
	$gateways = get_option('memberdeck_gateways');
	if (isset($gateways)) {
		if (!is_array($gateways)) {
			$gateways = unserialize($gateways);
		}
		if (isset($gateways['esc']) && $gateways['esc'] == 1) {
			$sc_menu = add_submenu_page('idc', __('Stripe Connect', 'mdid'), __('Stripe Connect', 'mdid'), 'idc_manage_gateways', 'idc-sc-settings', 'idc_sc_settings');
			add_action('admin_print_styles-' . $sc_menu, 'md_sc_scripts');
		}
	}
	global $s3;
	if ($s3) {
		$s3_menu = add_submenu_page('idc', __('S3 Settings', 'mdid'), __('S3 Settings', 'mdid'), 'idc_manage_extensions', 'idc-s3-settings', 'idc_s3_settings');
	}
	do_action('idc_admin_menu_after');
	//}
}

function bii_idc_orders() {
	bii_order::plugin_list();
}

add_action('memberdeck_free_success', 'bii_save_order_meta', 100, 2);
add_action('memberdeck_payment_success', 'bii_save_order_meta', 100, 5);
add_action('memberdeck_preauth_success', 'bii_save_order_meta', 100, 5);

function bii_save_order_meta($user_id, $order_id, $paykey = '', $fields = null, $source = '') {
	bii_write_log("bii save order meta");
	bii_write_log($fields);
	$product_id = $fields["product_id"];
	$om = new bii_order_meta();
	$om->insert();
	$om->updateChamps(["order_id" => $order_id, "meta_key" => "product_id", "meta_value" => $product_id]);
}

add_filter("idc_product_price", function($level_price, $product_id, $return) {
	if (!$level_price) {
		$level_price = $_GET["price"];
	}
	$level_price *=1;
	if (!$level_price || $level_price < 0) {
		$level_price = 1;
	}
	return $level_price;
}, 10, 3);

add_filter("bii_looklikevanillalevel",function($array){
	pre($array);
	if(!isset($array["title"])){
		$array["title"] = "";
	}
	if(!isset($array["price"])){
		$array["price"] = 1;
	}
	
	$array["product_type"] = "purchase";
	$array["level_name"] = $array["title"];
	$array["level_price"] = $array["price"];
	$array["credit_value"] = 0;
	$array["txn_type"] = "capture";
	$array["level_type"] = "lifetime";
	$array["recurring_type"] = "none";
	$array["limit_term"] = 0;
	$array["term_length"] = 0;
	$array["plan"] = "";
	$array["license_count"] = 0;
	$array["enable_renewals"] = 0;
	$array["renewal_price"] = 0;
	$array["enable_multiples"] = 1;
	$array["combined_product"] = 0;
	$array["custom_message"] = 0;
	
	return (object)$array;
},10,1);

function bii_idc_display_checkout_descriptions($content, $level, $level_price, $user_data, $settings, $general, $credit_value) {
	global $global_currency;
	// Getting the required variables for the Description template
	$customer_id = customer_id();

	$type = $level->level_type;
	$recurring = $level->recurring_type;
	$limit_term = $level->limit_term;
	$term_length = $level->term_length;
	$combined_product = $level->combined_product;
	// $credit_value = $level->credit_value;
//	pre($content,"green");
//	pre($level,"orange");
//	pre($level_price,"blue");
//	pre($user_data,"red");
//	pre($settings,"purple");
//	pre($general,"brown");
//	pre($credit_value,"#3A495E");

	// If there is a combined product, check which active gateways allows recurring transactions
	if ($combined_product) {
		$combined_level = ID_Member_Level::get_level($combined_product);
		
		// Now see if any CreditCard gateway is active which supports recurring products, we just need to see if we have
		// to show that text or not in General text of different payment methods
		$combined_purchase_gateways = idc_combined_purchase_allowed($settings);
	} else {
		$combined_purchase_gateways = array();
	}

	$coname = $general['coname'];
	// Paypal currency
	$pp_currency = 'USD';
	if (!empty($settings)) {
		if (is_array($settings)) {
			$pp_currency = (isset($settings['pp_currency']) ? $settings['pp_currency'] : 'USD');
		}
	}
	$cc_currency_symbol = apply_filters('idc_cc_desc_currency_sym', '$', $settings);
	$cc_currency = apply_filters('idc_cc_desc_currency', 'USD', $settings);
	// Stripe currency
	$es = (isset($settings['es']) ? $settings['es'] : 0);
	$stripe_currency = 'USD';
	$stripe_symbol = '$';
	
	if (!empty($settings)) {
		if (is_array($settings)) {
			$stripe_currency = (isset($settings['stripe_currency']) ? $settings['stripe_currency'] : 'USD');
			$stripe_symbol = md_currency_symbol($stripe_currency);
		}
	}

	// Coinbase currency
	$ecb = (isset($settings['ecb']) ? $settings['ecb'] : '0');
	if ($ecb) {
		$cb_currency = (isset($settings['cb_currency']) ? $settings['cb_currency'] : 'BTC');
		$cb_symbol = md_currency_symbol($cb_currency);
	} else {
		$cb_currency = '';
		$cb_symbol = '';
	}

	// Global currency symbol
	if ($global_currency == "credits") {
		$global_currency_sym = '$';		//ucwords(apply_filters('idc_credits_label', __('credits', 'memberdeck'), true));
	} else {
		$global_currency_sym = md_currency_symbol($global_currency);
	}
	bii_write_log("idc_checkout_descriptions");
	
	ob_start();
	include_once IDC_PATH.'templates/_checkoutFreeDescription.php';
	$free_description = ob_get_contents();
	ob_clean();
	$content .= apply_filters('idc_free_checkout_description', $free_description, $level, $level_price, (isset($user_data) ? $user_data : ''), $settings, $general);
	bii_write_log("idc_free_checkout_description");
	
	ob_start();
	include_once IDC_PATH.'templates/_checkoutPayPalDescription.php';
	$paypal_description = ob_get_contents();
	ob_clean();
	$content .= apply_filters('idc_paypal_checkout_description', $paypal_description, $level, $level_price, (isset($user_data) ? $user_data : ''), $settings, $general);
	bii_write_log("idc_paypal_checkout_description");
	
	ob_start();
	include_once IDC_PATH.'templates/_checkoutCreditCardDescription.php';
	$credit_card_description = ob_get_contents();
	ob_clean();
	$content .= apply_filters('idc_credit_card_checkout_description', $credit_card_description, $level, $level_price, (isset($user_data) ? $user_data : ''), $settings, $general);
	bii_write_log("idc_credit_card_checkout_description");
	
	ob_start();
	include_once IDC_PATH.'templates/_checkoutCreditsDescription.php';
	$credits_description = ob_get_contents();
	ob_clean();
	$content .= apply_filters('idc_credits_checkout_description', $credits_description, $level, $level_price, (isset($user_data) ? $user_data : ''), $settings, $general);
	bii_write_log("idc_credits_checkout_description");
	
	ob_start();
	include_once IDC_PATH.'templates/_checkoutCoinbaseDescription.php';
	$coinbase_description = ob_get_contents();
	ob_clean();
	$content .= apply_filters('idc_coinbase_checkout_description', $coinbase_description, $level, $level_price, (isset($user_data) ? $user_data : ''), $settings, $general);
	bii_write_log("idc_coinbase_checkout_description");
	
	ob_start();
	include_once IDC_PATH.'templates/_checkoutOfflineDescription.php';
	$offline_description = ob_get_contents();
	ob_clean();
	$content .= apply_filters('idc_offline_checkout_description', $offline_description, $level, $level_price, (isset($user_data) ? $user_data : ''), $settings, $general);
	bii_write_log("idc_offline_checkout_description");
	
	
	ob_end_clean();
	return $content;
}
remove_filter('idc_checkout_descriptions', 'idc_display_checkout_descriptions', 10, 7);
add_filter('idc_checkout_descriptions', 'bii_idc_display_checkout_descriptions', 10, 7);