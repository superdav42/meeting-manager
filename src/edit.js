import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { 
	PanelBody, 
	ToggleControl, 
	SelectControl, 
	TextControl,
	__experimentalNumberControl as NumberControl 
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import './editor.scss';

const TIMEZONES = [
	{ label: 'Eastern Time (ET)', value: 'America/New_York' },
	{ label: 'Central Time (CT)', value: 'America/Chicago' },
	{ label: 'Mountain Time (MT)', value: 'America/Denver' },
	{ label: 'Pacific Time (PT)', value: 'America/Los_Angeles' },
	{ label: 'Alaska Time (AKT)', value: 'America/Anchorage' },
	{ label: 'Hawaii Time (HT)', value: 'Pacific/Honolulu' },
	{ label: 'UTC', value: 'UTC' },
	{ label: 'London (GMT)', value: 'Europe/London' },
	{ label: 'Paris (CET)', value: 'Europe/Paris' },
	{ label: 'Tokyo (JST)', value: 'Asia/Tokyo' },
	{ label: 'Sydney (AEDT)', value: 'Australia/Sydney' },
];

const WEEKDAYS = [
	{ label: 'Monday', value: 'monday' },
	{ label: 'Tuesday', value: 'tuesday' },
	{ label: 'Wednesday', value: 'wednesday' },
	{ label: 'Thursday', value: 'thursday' },
	{ label: 'Friday', value: 'friday' },
	{ label: 'Saturday', value: 'saturday' },
	{ label: 'Sunday', value: 'sunday' },
];

const WEEK_OPTIONS = [
	{ label: '1st', value: '1' },
	{ label: '2nd', value: '2' },
	{ label: '3rd', value: '3' },
	{ label: '4th', value: '4' },
	{ label: 'Last', value: '-1' },
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		blockId,
		isRecurring,
		meetingDate,
		scheduleType,
		recurrenceDay,
		recurrenceWeek,
		startTime,
		endTime,
		timezone,
		jitsiProvider,
		jitsiDomain,
		jitsiRoom,
		jaasAppId,
		jaasApiKey,
		jaasKeyId,
		notificationTime,
	} = attributes;

	useEffect( () => {
		if ( ! blockId ) {
			setAttributes( { blockId: clientId } );
		}
	}, [ blockId, clientId ] );

	useEffect( () => {
		if ( ! jitsiRoom ) {
			const randomRoom = 'meeting-' + Math.random().toString( 36 ).substring( 7 );
			setAttributes( { jitsiRoom: randomRoom } );
		}
	}, [] );

	useEffect( () => {
		if ( ! isRecurring && ! meetingDate ) {
			const today = new Date();
			const dateString = today.toISOString().split('T')[0];
			setAttributes( { meetingDate: dateString } );
		}
	}, [ isRecurring, meetingDate ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Meeting Schedule', 'meeting-manager' ) }>
					<ToggleControl
						label={ __( 'Recurring Meeting', 'meeting-manager' ) }
						checked={ isRecurring }
						onChange={ ( value ) => setAttributes( { isRecurring: value } ) }
					/>
					
					{ ! isRecurring && (
						<TextControl
							label={ __( 'Meeting Date', 'meeting-manager' ) }
							type="date"
							value={ meetingDate }
							onChange={ ( value ) => setAttributes( { meetingDate: value } ) }
						/>
					) }
					
					{ isRecurring && (
						<>
							<SelectControl
								label={ __( 'Schedule Type', 'meeting-manager' ) }
								value={ scheduleType }
								options={ [
									{ label: 'Weekly', value: 'weekly' },
									{ label: 'Monthly', value: 'monthly' },
								] }
								onChange={ ( value ) => setAttributes( { scheduleType: value } ) }
							/>
							
							{ scheduleType === 'weekly' && (
								<SelectControl
									label={ __( 'Day of Week', 'meeting-manager' ) }
									value={ recurrenceDay }
									options={ WEEKDAYS }
									onChange={ ( value ) => setAttributes( { recurrenceDay: value } ) }
								/>
							) }
							
							{ scheduleType === 'monthly' && (
								<>
									<SelectControl
										label={ __( 'Week of Month', 'meeting-manager' ) }
										value={ recurrenceWeek }
										options={ WEEK_OPTIONS }
										onChange={ ( value ) => setAttributes( { recurrenceWeek: value } ) }
									/>
									<SelectControl
										label={ __( 'Day of Week', 'meeting-manager' ) }
										value={ recurrenceDay }
										options={ WEEKDAYS }
										onChange={ ( value ) => setAttributes( { recurrenceDay: value } ) }
									/>
								</>
							) }
						</>
					) }
					
					<TextControl
						label={ __( 'Start Time', 'meeting-manager' ) }
						type="time"
						value={ startTime }
						onChange={ ( value ) => setAttributes( { startTime: value } ) }
					/>
					
					<TextControl
						label={ __( 'End Time', 'meeting-manager' ) }
						type="time"
						value={ endTime }
						onChange={ ( value ) => setAttributes( { endTime: value } ) }
					/>
					
					<SelectControl
						label={ __( 'Timezone', 'meeting-manager' ) }
						value={ timezone }
						options={ TIMEZONES }
						onChange={ ( value ) => setAttributes( { timezone: value } ) }
					/>
				</PanelBody>
				
				<PanelBody title={ __( 'Jitsi Settings', 'meeting-manager' ) }>
					<SelectControl
						label={ __( 'Jitsi Provider', 'meeting-manager' ) }
						value={ jitsiProvider }
						options={ [
							{ label: 'Jitsi Meet (Free, 5min limit)', value: 'meet' },
							{ label: 'JaaS - 8x8 (Paid, no limit)', value: 'jaas' },
						] }
						onChange={ ( value ) => setAttributes( { jitsiProvider: value } ) }
						help={ jitsiProvider === 'jaas' ? __( 'Requires 8x8 JaaS account with App ID and API Key', 'meeting-manager' ) : __( 'Free service with 5 minute meeting limit', 'meeting-manager' ) }
					/>
					
					{ jitsiProvider === 'meet' && (
						<TextControl
							label={ __( 'Jitsi Domain', 'meeting-manager' ) }
							value={ jitsiDomain }
							onChange={ ( value ) => setAttributes( { jitsiDomain: value } ) }
							help={ __( 'Default: meet.jit.si', 'meeting-manager' ) }
						/>
					) }
					
					{ jitsiProvider === 'jaas' && (
						<>
							<TextControl
								label={ __( 'JaaS App ID', 'meeting-manager' ) }
								value={ jaasAppId }
								onChange={ ( value ) => setAttributes( { jaasAppId: value } ) }
								help={ __( 'Your 8x8 App ID (e.g., vpaas-magic-cookie-xxx)', 'meeting-manager' ) }
								placeholder="vpaas-magic-cookie-xxx"
							/>
							<TextControl
								label={ __( 'JaaS Private Key', 'meeting-manager' ) }
								value={ jaasApiKey }
								onChange={ ( value ) => setAttributes( { jaasApiKey: value } ) }
								help={ __( 'Your RSA private key in PEM format (starts with -----BEGIN RSA PRIVATE KEY-----)', 'meeting-manager' ) }
							/>
							<TextControl
								label={ __( 'JaaS Key ID', 'meeting-manager' ) }
								value={ jaasKeyId }
								onChange={ ( value ) => setAttributes( { jaasKeyId: value } ) }
								help={ __( 'The Key ID from your 8x8 console (e.g., 4f4910)', 'meeting-manager' ) }
								placeholder="abc123"
							/>
						</>
					) }
					
					<TextControl
						label={ __( 'Meeting Room URL', 'meeting-manager' ) }
						value={ jitsiRoom }
						onChange={ ( value ) => setAttributes( { jitsiRoom: value } ) }
					/>
				</PanelBody>
				
				<PanelBody title={ __( 'Notifications', 'meeting-manager' ) }>
					<NumberControl
						label={ __( 'Send email reminder (minutes before)', 'meeting-manager' ) }
						value={ notificationTime }
						onChange={ ( value ) => setAttributes( { notificationTime: parseInt( value ) } ) }
						min={ 5 }
						max={ 1440 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<div className="meeting-manager-editor">
					<div className="meeting-manager-icon">📅</div>
					<h3>{ __( 'Meeting Manager', 'meeting-manager' ) }</h3>
					<div className="meeting-details">
						<p>
							<strong>{ __( 'Schedule:', 'meeting-manager' ) }</strong>
							{ ' ' }
							{ isRecurring ? (
								scheduleType === 'weekly' ? 
									`Every ${recurrenceDay}` :
									`${recurrenceWeek === '-1' ? 'Last' : WEEK_OPTIONS.find(w => w.value === recurrenceWeek)?.label} ${recurrenceDay} of every month`
							) : (
								__( 'Single meeting on ', 'meeting-manager' ) + ( meetingDate || 'date not set' )
							) }
						</p>
						<p>
							<strong>{ __( 'Time:', 'meeting-manager' ) }</strong>
							{ ' ' }
							{ startTime } - { endTime } ({ timezone })
						</p>
						<p>
							<strong>{ __( 'Provider:', 'meeting-manager' ) }</strong>
							{ ' ' }
							{ jitsiProvider === 'jaas' ? '8x8 JaaS' : 'Jitsi Meet' }
						</p>
						<p>
							<strong>{ __( 'Room:', 'meeting-manager' ) }</strong>
							{ ' ' }
							{ jitsiProvider === 'jaas' ? `8x8.vc/${jaasAppId}/${jitsiRoom}` : `${jitsiDomain}/${jitsiRoom}` }
						</p>
						<p>
							<strong>{ __( 'Reminder:', 'meeting-manager' ) }</strong>
							{ ' ' }
							{ notificationTime } minutes before
						</p>
					</div>
				</div>
			</div>
		</>
	);
}