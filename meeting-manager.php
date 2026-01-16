<?php
/**
 * Plugin Name:       Meeting Manager
 * Description:       A comprehensive block for managing single or recurring virtual meetings with Jitsi integration, email notifications, and push notifications.
 * Version:           1.0.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            WordPress Telex
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       meeting-manager
 *
 * @package MeetingManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 */
function meeting_manager_block_init() {
	register_block_type( __DIR__ . '/build/' );
}
add_action( 'init', 'meeting_manager_block_init' );

/**
 * Pass current user data to the frontend script.
 */
function meeting_manager_enqueue_user_data() {
	$user_name = 'Guest';

	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$user_name    = $current_user->first_name ? $current_user->first_name : $current_user->display_name;
	}

	wp_add_inline_script(
		'telex-block-meeting-manager-view-script',
		'window.meetingManagerUserData = ' . wp_json_encode( array( 'userName' => $user_name ) ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'meeting_manager_enqueue_user_data' );

/**
 * Format a PEM private key that may have had newlines stripped.
 *
 * @param string $key The PEM key string.
 * @return string The properly formatted PEM key.
 */
function meeting_manager_format_pem_key( $key ) {
	// If the key already has proper newlines, return as-is.
	if ( preg_match( '/-----BEGIN[^-]+-----\s*\n/', $key ) ) {
		return $key;
	}

	// Match the header and footer, supporting both PRIVATE KEY and RSA PRIVATE KEY formats.
	if ( preg_match( '/(-----BEGIN [A-Z ]+-----)(.+)(-----END [A-Z ]+-----)/', $key, $matches ) ) {
		$header  = $matches[1];
		$content = trim( $matches[2] );
		$footer  = $matches[3];

		// Remove any existing whitespace from the base64 content.
		$content = preg_replace( '/\s+/', '', $content );

		// Wrap at 64 characters per line (PEM standard).
		$content = chunk_split( $content, 64, "\n" );

		return $header . "\n" . $content . $footer . "\n";
	}

	// Return original if we can't parse it.
	return $key;
}

/**
 * Generate JWT token for JaaS authentication
 *
 * @param string $app_id    The JaaS App ID (e.g., vpaas-magic-cookie-xxx).
 * @param string $api_key   The RSA private key in PEM format.
 * @param string $key_id    The Key ID from the JaaS console.
 * @param string $room_name The room name for the meeting.
 * @param array  $user_data User data including name, email, avatar, id.
 * @return string|null The JWT token or null on failure.
 */
function meeting_manager_generate_jaas_jwt( $app_id, $api_key, $key_id, $room_name, $user_data = array() ) {
	if ( empty( $app_id ) || empty( $api_key ) || empty( $key_id ) ) {
		return null;
	}

	if ( str_contains($key_id, '/')) {
		$key_parts = explode('/', $key_id);
		$key_id = array_pop($key_parts);
	}

	// JaaS requires RS256 algorithm with the key ID in the header.
	$header = array(
		'alg' => 'RS256',
		'typ' => 'JWT',
		'kid' => $app_id . '/' . $key_id,
	);

	$now = time();
	$exp = $now + 7200;

	$payload = array(
		'aud' => 'jitsi',
		'iss' => 'chat',
		'sub' => $app_id,
		'room' => '*',
		'exp' => $exp,
		'nbf' => $now - 10,
		'context' => array(
			'user' => array(
				'moderator' => 'true',
				'name' => $user_data['name'] ?? 'Guest',
				'email' => $user_data['email'] ?? '',
				'avatar' => $user_data['avatar'] ?? '',
				'id' => $user_data['id'] ?? uniqid(),
			),
			'features' => array(
				'livestreaming' => false,
				'recording' => false,
				'transcription' => false,
				'outbound-call' => false,
			),
		),
	);

	$base64_header  = rtrim( strtr( base64_encode( wp_json_encode( $header ) ), '+/', '-_' ), '=' );
	$base64_payload = rtrim( strtr( base64_encode( wp_json_encode( $payload ) ), '+/', '-_' ), '=' );

	$signature_input = $base64_header . '.' . $base64_payload;

	// Sign with RSA private key using RS256.
	// Reformat the PEM key in case newlines were stripped when pasting.
	$formatted_key = meeting_manager_format_pem_key( $api_key );
	$private_key   = openssl_pkey_get_private( $formatted_key );
	if ( ! $private_key ) {
		return null;
	}

	$signature = '';
	if ( ! openssl_sign( $signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256 ) ) {
		return null;
	}

	$base64_signature = rtrim( strtr( base64_encode( $signature ), '+/', '-_' ), '=' );

	return $signature_input . '.' . $base64_signature;
}

/**
 * Handle email subscription via REST API
 */
function meeting_manager_register_rest_routes() {
	register_rest_route( 'meeting-manager/v1', '/subscribe', array(
		'methods'             => 'POST',
		'callback'            => 'meeting_manager_handle_subscription',
		'permission_callback' => '__return_true',
		'args'                => array(
			'email'          => array(
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => function( $param ) {
					return is_email( $param );
				},
			),
			'block_id'       => array(
				'required' => true,
				'type'     => 'string',
			),
			'join_list'      => array(
				'required' => false,
				'type'     => 'boolean',
			),
		),
	) );

	register_rest_route( 'meeting-manager/v1', '/next-meeting', array(
		'methods'             => 'GET',
		'callback'            => 'meeting_manager_get_next_meeting',
		'permission_callback' => '__return_true',
		'args'                => array(
			'block_id' => array(
				'required' => true,
				'type'     => 'string',
			),
		),
	) );

	register_rest_route( 'meeting-manager/v1', '/jaas-token', array(
		'methods'             => 'POST',
		'callback'            => 'meeting_manager_get_jaas_token',
		'permission_callback' => '__return_true',
		'args'                => array(
			'block_id' => array(
				'required' => true,
				'type'     => 'string',
			),
			'user_name' => array(
				'required' => false,
				'type'     => 'string',
				'default'  => 'Guest',
			),
		),
	) );
}
add_action( 'rest_api_init', 'meeting_manager_register_rest_routes' );

/**
 * Get JaaS token callback
 */
function meeting_manager_get_jaas_token( $request ) {
	$block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
	$user_name = sanitize_text_field( $request->get_param( 'user_name' ) );
	$block_data = get_option( 'meeting_manager_block_' . $block_id );

	if ( ! $block_data ) {
		return new WP_Error( 'block_not_found', 'Block configuration not found', array( 'status' => 404 ) );
	}

	if ( $block_data['jitsi_provider'] !== 'jaas' ) {
		return new WP_Error( 'not_jaas', 'This block is not configured for JaaS', array( 'status' => 400 ) );
	}

	$user_data = array(
		'name' => $user_name,
		'id' => uniqid(),
	);

	$jwt = meeting_manager_generate_jaas_jwt(
		$block_data['jaas_app_id'],
		$block_data['jaas_api_key'],
		$block_data['jaas_key_id'] ?? '',
		$block_data['jitsi_room'],
		$user_data
	);

	if ( ! $jwt ) {
		return new WP_Error( 'jwt_generation_failed', 'Failed to generate JWT token', array( 'status' => 500 ) );
	}

	return array(
		'jwt' => $jwt,
		'app_id' => $block_data['jaas_app_id'],
		'room' => $block_data['jitsi_room'],
	);
}

/**
 * Handle subscription callback
 */
function meeting_manager_handle_subscription( $request ) {
	$email     = sanitize_email( $request->get_param( 'email' ) );
	$block_id  = sanitize_text_field( $request->get_param( 'block_id' ) );
	$join_list = $request->get_param( 'join_list' );

	if ( ! is_email( $email ) ) {
		return new WP_Error( 'invalid_email', 'Invalid email address', array( 'status' => 400 ) );
	}

	$subscribers = get_option( 'meeting_manager_subscribers_' . $block_id, array() );

	if ( ! in_array( $email, $subscribers ) ) {
		$subscribers[] = $email;
		update_option( 'meeting_manager_subscribers_' . $block_id, $subscribers );
	}

	// Add to newsletter as confirmed subscriber if join_list is set
	if ( $join_list && class_exists( 'TNP' ) ) {
		TNP::add_subscriber( array(
			'email'  => $email,
			'status' => 'C', // Confirmed
		) );
	}

	return array(
		'success' => true,
		'message' => 'Successfully subscribed to meeting notifications',
	);
}

/**
 * Calculate next meeting occurrence
 */
function meeting_manager_calculate_next_meeting( $is_recurring, $schedule_type, $start_time, $timezone, $meeting_date = null, $recurrence_day = null, $recurrence_week = null, $end_time = null ) {
	$tz = new DateTimeZone( $timezone );
	$now = new DateTime( 'now', $tz );

	if ( ! $is_recurring ) {
		if ( $meeting_date ) {
			$meeting_time = new DateTime( $meeting_date . ' ' . $start_time, $tz );
			$meeting_end_time = new DateTime( $meeting_date . ' ' . $end_time, $tz );
			if ( $meeting_time > $now || $meeting_end_time > $now) {
				return $meeting_time->format( 'c' );
			}
		}
		return null;
	}

	$current = clone $now;

	for ( $i = 0; $i < 365; $i++ ) {
		$current->modify( '+1 day' );

		if ( $schedule_type === 'weekly' && $recurrence_day !== null ) {
			$day_of_week = strtolower( $current->format( 'l' ) );
			if ( $day_of_week === strtolower( $recurrence_day ) ) {

				list( $end_hour, $end_minute ) = explode( ':', $end_time );
				$end_target = clone $current;
				$end_target->setTime( intval( $end_hour ), intval( $end_minute ) );

				list( $hour, $minute ) = explode( ':', $start_time );
				$current->setTime( intval( $hour ), intval( $minute ) );

				if ( $current > $now || $end_target > $now  ) {
					return $current->format( 'c' );
				}
			}
		} elseif ( $schedule_type === 'monthly' && $recurrence_week !== null && $recurrence_day !== null ) {
			$first_day = new DateTime( $current->format( 'Y-m-01' ), $tz );
			$month = $current->format( 'm' );

			$occurrences = array();
			$temp = clone $first_day;

			while ( $temp->format( 'm' ) === $month ) {
				if ( strtolower( $temp->format( 'l' ) ) === strtolower( $recurrence_day ) ) {
					$occurrences[] = clone $temp;
				}
				$temp->modify( '+1 day' );
			}

			if ( ! empty( $occurrences ) ) {
				$week_index = intval( $recurrence_week ) - 1;
				if ( $week_index === -1 ) {
					$week_index = count( $occurrences ) - 1;
				}

				if ( isset( $occurrences[ $week_index ] ) ) {
					$target = $occurrences[ $week_index ];
					if ( $target->format( 'Y-m-d' ) === $current->format( 'Y-m-d' ) ) {
						list( $hour, $minute ) = explode( ':', $start_time );
						list( $end_hour, $end_minute ) = explode( ':', $end_time );
						$end_target = clone $target;
						$end_target->setTime( intval( $end_hour ), intval( $end_minute ) );
						$target->setTime( intval( $hour ), intval( $minute ) );

						if ( $target > $now || $end_target > $now ) {
							return $target->format( 'c' );
						}
					}
				}
			}
		}
	}

	return null;
}

/**
 * Get next meeting callback
 */
function meeting_manager_get_next_meeting( $request ) {
	$block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
	$block_data = get_option( 'meeting_manager_block_' . $block_id );

	if ( ! $block_data ) {
		return new WP_Error( 'block_not_found', 'Block configuration not found', array( 'status' => 404 ) );
	}

	$next_meeting = meeting_manager_calculate_next_meeting(
		$block_data['is_recurring'],
		$block_data['schedule_type'],
		$block_data['start_time'],
		$block_data['timezone'],
		$block_data['meeting_date'] ?? null,
		$block_data['recurrence_day'] ?? null,
		$block_data['recurrence_week'] ?? null,
		$block_data['end_time'] ?? null
	);

	return array(
		'next_meeting' => $next_meeting,
		'timezone' => $block_data['timezone'],
		'jitsi_provider' => $block_data['jitsi_provider'],
		'jitsi_domain' => $block_data['jitsi_domain'],
		'jitsi_room' => $block_data['jitsi_room'],
		'jaas_app_id' => $block_data['jaas_app_id'] ?? '',
	);
}

/**
 * Schedule notification cron job
 */
function meeting_manager_schedule_notifications() {
	if ( ! wp_next_scheduled( 'meeting_manager_send_notifications' ) ) {
		wp_schedule_event( time(), 'hourly', 'meeting_manager_send_notifications' );
	}
}
add_action( 'wp', 'meeting_manager_schedule_notifications' );

/**
 * Send notifications for upcoming meetings
 */
function meeting_manager_send_notifications_callback() {
	$all_options = wp_load_alloptions();

	foreach ( $all_options as $key => $value ) {
		if ( strpos( $key, 'meeting_manager_block_' ) === 0 ) {
			$block_data = maybe_unserialize( $value );
			$block_id = str_replace( 'meeting_manager_block_', '', $key );

			$next_meeting = meeting_manager_calculate_next_meeting(
				$block_data['is_recurring'],
				$block_data['schedule_type'],
				$block_data['start_time'],
				$block_data['timezone'],
				$block_data['meeting_date'] ?? null,
				$block_data['recurrence_day'] ?? null,
				$block_data['recurrence_week'] ?? null,
				$block_data['end_time'] ?? null
			);

			if ( $next_meeting ) {
				$meeting_time = new DateTime( $next_meeting );
				$now = new DateTime();
				$diff = $meeting_time->getTimestamp() - $now->getTimestamp();
				$notification_minutes = intval( $block_data['notification_time'] ?? 30 );

				$sent_key = 'meeting_manager_sent_' . $block_id . '_' . $meeting_time->format( 'YmdHi' );
				$already_sent = get_transient( $sent_key );

				if ( ! $already_sent && $diff > 0 && $diff <= ( $notification_minutes * 60 + 300 ) ) {
					$subscribers = get_option( 'meeting_manager_subscribers_' . $block_id, array() );

					foreach ( $subscribers as $email ) {
						$subject = 'Meeting Reminder: Starting in ' . $notification_minutes . ' minutes';
						$message = "Your meeting is starting soon!\n\n";
						$message .= "Meeting starts at: " . $meeting_time->format( 'F j, Y g:i A' ) . " " . $block_data['timezone'] . "\n";
						$message .= "Join the meeting at your scheduled time on the website.\n";

						wp_mail( $email, $subject, $message );
					}

					set_transient( $sent_key, true, 86400 );
				}
			}
		}
	}
}
add_action( 'meeting_manager_send_notifications', 'meeting_manager_send_notifications_callback' );

/**
 * Cleanup on plugin deactivation
 */
function meeting_manager_deactivate() {
	wp_clear_scheduled_hook( 'meeting_manager_send_notifications' );
}
register_deactivation_hook( __FILE__, 'meeting_manager_deactivate' );
