<?php
/*
Plugin Name: VDZ Contact Us
Plugin URI:  http://online-services.org.ua
Description: Плагин для вывода контактов
Version:     1.4.3
Author:      VadimZ
Author URI:  http://online-services.org.ua#vdz-contact-us
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VDZ_CU_API', 'vdz_info_contact_us' );

require_once 'api.php';
require_once 'updated_plugin_admin_notices.php';

// Код активации плагина
register_activation_hook( __FILE__, 'vdz_cu_activate_plugin' );
function vdz_cu_activate_plugin() {
	global $wp_version;
	if ( version_compare( $wp_version, '3.8', '<' ) ) {
		// Деактивируем плагин
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'This plugin required WordPress version 3.8 or higher' );
	}
	add_option( 'vdz_contact_us_front_show', 1 );

	do_action( VDZ_CU_API, 'on', plugin_basename( __FILE__ ) );
}

// Код деактивации плагина
register_deactivation_hook( __FILE__, function () {
	$plugin_name = preg_replace( '|\/(.*)|', '', plugin_basename( __FILE__ ));
	$response = wp_remote_get( "http://api.online-services.org.ua/off/{$plugin_name}" );
	if ( ! is_wp_error( $response ) && isset( $response['body'] ) && ( json_decode( $response['body'] ) !== null ) ) {
		//TODO Вывод сообщения для пользователя
	}
} );
//Сообщение при отключении плагина
add_action( 'admin_init', function (){
	if(is_admin()){
		$plugin_data = get_plugin_data(__FILE__);
		$plugin_name = isset($plugin_data['Name']) ? $plugin_data['Name'] : ' us';
		$plugin_dir_name = preg_replace( '|\/(.*)|', '', plugin_basename( __FILE__ ));
		$handle = 'admin_'.$plugin_dir_name;
		wp_register_script( $handle, '', null, false, true );
		wp_enqueue_script( $handle );
		$msg = '';
		if ( function_exists( 'get_locale' ) && in_array( get_locale(), array( 'uk', 'ru_RU' ), true ) ) {
			$msg .= "Спасибо, что были с нами! ({$plugin_name}) Хорошего дня!";
		}else{
			$msg .= "Thanks for your time with us! ({$plugin_name}) Have a nice day!";
		}
		wp_add_inline_script( $handle, "document.getElementById('deactivate-".esc_attr($plugin_dir_name)."').onclick=function (e){alert('".esc_attr( $msg )."');}" );
	}
} );




/*Добавляем новые поля для в настройках шаблона шаблона для верификации сайта*/
function vdz_cu_theme_customizer( $wp_customize ) {

	if ( ! class_exists( 'WP_Customize_Control' ) ) {
		exit;
	}

	// Добавляем секцию для идетнтификатора YS
	$wp_customize->add_section(
		'vdz_contact_us_section',
		array(
			'title'    => __( 'VDZ Contact Us' ),
			'priority' => 10,
		// 'description' => __( 'Contact Us code on your site' ),
		)
	);
	// Добавляем настройки
	$wp_customize->add_setting(
		'vdz_contact_us_front_show',
		array(
			'type'              => 'option',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	// Добавляем настройки
	$wp_customize->add_setting(
		'vdz_contact_us_phone',
		array(
			'type'              => 'option',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	// Google
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'vdz_contact_us_phone',
			array(
				'label'       => __( 'Phone' ),
				'section'     => 'vdz_contact_us_section',
				'settings'    => 'vdz_contact_us_phone',
				'type'        => 'text',
				'description' => __( 'Add Phone here:' ),
				'input_attrs' => array(
					'placeholder' => '+38(044)555-55-55', // для примера
				),
			)
		)
	);

	// Show/Hide
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'vdz_contact_us_front_show',
			array(
				'label'       => __( 'VDZ Contact Us' ),
				'section'     => 'vdz_contact_us_section',
				'settings'    => 'vdz_contact_us_front_show',
				'type'        => 'select',
				'description' => __( 'ON/OFF' ),
				'choices'     => array(
					1 => __( 'Show' ),
					0 => __( 'Hide' ),
				),
			)
		)
	);

	// Добавляем ссылку на сайт
	$wp_customize->add_setting(
		'vdz_contact_us_link',
		array(
			'type' => 'option',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'vdz_contact_us_link',
			array(
				// 'label'    => __( 'Link' ),
									'section' => 'vdz_contact_us_section',
				'settings'                    => 'vdz_contact_us_link',
				'type'                        => 'hidden',
				'description'                 => '<br/><a href="//online-services.org.ua#vdz-contact-us" target="_blank">VadimZ</a>',
			)
		)
	);
}
add_action( 'customize_register', 'vdz_cu_theme_customizer', 1 );


// Виджет
add_action( 'wp_footer', 'vdz_contact_us_show', 1100 );
function vdz_contact_us_show() {
	$vdz_contact_us_front_show = (int) get_option( 'vdz_contact_us_front_show' );
	if ( empty( $vdz_contact_us_front_show ) ) {
		return;
	}
	$phone = get_option( 'vdz_contact_us_phone' );
	$phone = trim( $phone );
	?>
	<div id="vdz_contact_us">

		<ul>
			<li>
				<a href="tel:+<?php echo esc_attr( str_replace( array( '+', ' ', '.', '-', '(', ')', ';' ), '', $phone ) ); ?>" title="<?php echo esc_attr( $phone ); ?>">
					<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
						<path d="M352 320c-32 32-32 64-64 64s-64-32-96-64-64-64-64-96 32-32 64-64-64-128-96-128-96 96-96 96c0 64 65.75 193.75 128 256s192 128 256 128c0 0 96-64 96-96s-96-128-128-96z"></path>
					</svg>
					<span><?php echo esc_attr( $phone ); ?></span>
				</a>
			</li>
		</ul>

	</div>
	<style>
		#vdz_contact_us ul li a{
			display: flex;
			align-items: center;
			justify-content: space-between;
			text-decoration: none;
		}
		#vdz_contact_us ul li a span{
			color: #000;
			margin-left: 15px;
			white-space: nowrap;
			color: #0F9E5E;
		}
		#vdz_contact_us ul li{
			margin: 0;
		}
		#vdz_contact_us ul{
			list-style-type: none;
			margin: 0;
			margin-right: 15px;
		}
		#vdz_contact_us svg{
			width: 40px;
			height: 40px;
			fill: #0F9E5E;
		}
		#vdz_contact_us{
			display: block;
			width: 275px;
			padding: 10px 15px;
			position: fixed;
			right: -210px;
			top: 35%;
			transition: all .5s ease-out 0s;
			border: 2px solid #0F9E5E;
			border-radius: 8px;
			font-weight: bold;
			opacity: .7;
			background-color: #fff;
			font-size: 20px;
		}
		#vdz_contact_us:hover {
			right: -14px;
			opacity: 1;
		}
	</style>
	<?php
}


// Добавляем допалнительную ссылку настроек на страницу всех плагинов
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'customize.php?autofocus[section]=vdz_contact_us_section' ) ) . '">' . esc_html__( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		array_walk( $links, 'wp_kses_post' );
		return $links;
	}
);

