<?php
$block_id = $attributes['blockId'] ?? uniqid();
$is_recurring = $attributes['isRecurring'] ?? false;
$meeting_date = $attributes['meetingDate'] ?? '';
$schedule_type = $attributes['scheduleType'] ?? 'weekly';
$recurrence_day = $attributes['recurrenceDay'] ?? 'monday';
$recurrence_week = $attributes['recurrenceWeek'] ?? '1';
$start_time = $attributes['startTime'] ?? '10:00';
$end_time = $attributes['endTime'] ?? '11:00';
$timezone = $attributes['timezone'] ?? 'America/New_York';
$jitsi_provider = $attributes['jitsiProvider'] ?? 'meet';
$jitsi_domain = $attributes['jitsiDomain'] ?? 'meet.jit.si';
$jitsi_room = $attributes['jitsiRoom'] ?? 'meeting-' . $block_id;
$jaas_app_id = $attributes['jaasAppId'] ?? '';
$jaas_api_key = $attributes['jaasApiKey'] ?? '';
$jaas_key_id = $attributes['jaasKeyId'] ?? '';
$notification_time = $attributes['notificationTime'] ?? 30;

$block_data = array(
	'is_recurring' => $is_recurring,
	'meeting_date' => $meeting_date,
	'schedule_type' => $schedule_type,
	'recurrence_day' => $recurrence_day,
	'recurrence_week' => $recurrence_week,
	'start_time' => $start_time,
	'end_time' => $end_time,
	'timezone' => $timezone,
	'jitsi_provider' => $jitsi_provider,
	'jitsi_domain' => $jitsi_domain,
	'jitsi_room' => $jitsi_room,
	'jaas_app_id' => $jaas_app_id,
	'jaas_api_key' => $jaas_api_key,
	'jaas_key_id' => $jaas_key_id,
	'notification_time' => $notification_time,
);

update_option( 'meeting_manager_block_' . $block_id, $block_data );

?>
<div <?php echo get_block_wrapper_attributes( array( 'data-block-id' => esc_attr( $block_id ) ) ); ?>>
	<div class="meeting-manager-container">
		<div class="meeting-manager-countdown">
			<h2 class="countdown-title"><?php esc_html_e( 'Next Meeting In:', 'meeting-manager' ); ?></h2>
			<div class="countdown-timer">
				<div class="countdown-unit">
					<span class="countdown-value">0</span>
					<span class="countdown-label"><?php esc_html_e( 'Days', 'meeting-manager' ); ?></span>
				</div>
				<div class="countdown-unit">
					<span class="countdown-value">0</span>
					<span class="countdown-label"><?php esc_html_e( 'Hours', 'meeting-manager' ); ?></span>
				</div>
				<div class="countdown-unit">
					<span class="countdown-value">0</span>
					<span class="countdown-label"><?php esc_html_e( 'Minutes', 'meeting-manager' ); ?></span>
				</div>
				<div class="countdown-unit">
					<span class="countdown-value">0</span>
					<span class="countdown-label"><?php esc_html_e( 'Seconds', 'meeting-manager' ); ?></span>
				</div>
			</div>
			<div class="meeting-start-time">
				<p class="meeting-datetime"></p>
				<p class="meeting-timezone">
					<?php
					printf(
						/* translators: %s: timezone name */
						esc_html__( 'Timezone: %s', 'meeting-manager' ),
						esc_html( $timezone )
					);
					?>
				</p>
			</div>
		</div>

		<div class="meeting-manager-subscription">
			<h3 class="subscription-title"><?php esc_html_e( 'Get Meeting Notifications', 'meeting-manager' ); ?></h3>
			<form class="subscription-form">
				<div class="form-group">
					<label for="meeting-email-<?php echo esc_attr( $block_id ); ?>">
						<?php esc_html_e( 'Email Address', 'meeting-manager' ); ?>
					</label>
					<input 
						type="email" 
						id="meeting-email-<?php echo esc_attr( $block_id ); ?>"
						name="email"
						required
						placeholder="<?php esc_attr_e( 'your@email.com', 'meeting-manager' ); ?>"
					/>
				</div>
				<div class="checkbox-group">
					<input 
						type="checkbox" 
						id="join-list-<?php echo esc_attr( $block_id ); ?>"
						name="join_list"
					/>
					<label for="join-list-<?php echo esc_attr( $block_id ); ?>">
						<?php esc_html_e( 'Also join our mailing list', 'meeting-manager' ); ?>
					</label>
				</div>
				<button type="submit" class="subscribe-button">
					<?php esc_html_e( 'Subscribe', 'meeting-manager' ); ?>
				</button>
				<div class="subscription-message" style="display: none;"></div>
			</form>
		</div>

		<div class="meeting-manager-jitsi" style="display: none;">
			<div class="meeting-info">
				<h3 class="meeting-info-title"><?php esc_html_e( 'Meeting is Live!', 'meeting-manager' ); ?></h3>
				<p class="meeting-info-text">
					<?php esc_html_e( 'Join the meeting below. You may need to allow camera and microphone access.', 'meeting-manager' ); ?>
				</p>
			</div>
			<div class="jitsi-container"></div>
		</div>
	</div>
</div>