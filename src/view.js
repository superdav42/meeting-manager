(function() {
	'use strict';

	const blocks = document.querySelectorAll('.wp-block-telex-block-meeting-manager');

	blocks.forEach(function(block) {
		const blockId = block.dataset.blockId;
		const countdownEl = block.querySelector('.countdown-timer');
		const meetingDatetimeEl = block.querySelector('.meeting-datetime');
		const subscriptionForm = block.querySelector('.subscription-form');
		const jitsiContainer = block.querySelector('.jitsi-container');
		const meetingContainer = block.querySelector('.meeting-manager-jitsi');
		
		let nextMeetingTime = null;
		let meetingConfig = {};
		let countdownInterval = null;

		function fetchNextMeeting() {
			fetch(`/wp-json/meeting-manager/v1/next-meeting?block_id=${blockId}`)
				.then(response => response.json())
				.then(data => {
					if (data.next_meeting) {
						nextMeetingTime = new Date(data.next_meeting);
						meetingConfig = data;
						updateMeetingDateTime();
						startCountdown();
					}
				})
				.catch(error => {
					console.error('Error fetching next meeting:', error);
				});
		}

		function updateMeetingDateTime() {
			if (meetingDatetimeEl && nextMeetingTime) {
				const options = {
					weekday: 'long',
					year: 'numeric',
					month: 'long',
					day: 'numeric',
					hour: 'numeric',
					minute: '2-digit',
					timeZoneName: 'short'
				};
				
				const formattedDate = nextMeetingTime.toLocaleString('en-US', options);
				meetingDatetimeEl.textContent = `Meeting starts: ${formattedDate}`;
			}
		}

		function startCountdown() {
			if (countdownInterval) {
				clearInterval(countdownInterval);
			}

			function updateCountdown() {
				const now = new Date();
				const diff = nextMeetingTime - now;

				if (diff <= 0) {
					showMeeting();
					return;
				}

				const days = Math.floor(diff / (1000 * 60 * 60 * 24));
				const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
				const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
				const seconds = Math.floor((diff % (1000 * 60)) / 1000);

				if (countdownEl) {
					countdownEl.innerHTML = `
						<div class="countdown-unit">
							<span class="countdown-value">${days}</span>
							<span class="countdown-label">Days</span>
						</div>
						<div class="countdown-unit">
							<span class="countdown-value">${hours}</span>
							<span class="countdown-label">Hours</span>
						</div>
						<div class="countdown-unit">
							<span class="countdown-value">${minutes}</span>
							<span class="countdown-label">Minutes</span>
						</div>
						<div class="countdown-unit">
							<span class="countdown-value">${seconds}</span>
							<span class="countdown-label">Seconds</span>
						</div>
					`;
				}
			}

			updateCountdown();
			countdownInterval = setInterval(updateCountdown, 1000);
		}

		async function showMeeting() {
			if (countdownInterval) {
				clearInterval(countdownInterval);
			}

			const countdownContainer = block.querySelector('.meeting-manager-countdown');
			const subscriptionContainer = block.querySelector('.meeting-manager-subscription');
			
			if (countdownContainer) {
				countdownContainer.style.display = 'none';
			}
			if (subscriptionContainer) {
				subscriptionContainer.style.display = 'none';
			}
			if (meetingContainer) {
				meetingContainer.style.display = 'block';
			}

			if (meetingConfig.jitsi_provider === 'jaas') {
				try {
					const userName = window.meetingManagerUserData?.userName || 'Guest';
					const response = await fetch('/wp-json/meeting-manager/v1/jaas-token', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
						},
						body: JSON.stringify({
							block_id: blockId,
							user_name: userName || 'Guest'
						})
					});

					const tokenData = await response.json();

					if (tokenData.jwt) {
						const domain = '8x8.vc';
						const roomName = `${tokenData.app_id}/${tokenData.room}`;
						const jitsiUrl = `https://${domain}/${roomName}?jwt=${tokenData.jwt}`;
						
						if (jitsiContainer) {
							jitsiContainer.innerHTML = `
								<iframe
									src="${jitsiUrl}"
									allow="camera; microphone; fullscreen; display-capture; autoplay"
									allowfullscreen
								></iframe>
							`;
						}
					} else {
						throw new Error('Failed to get JWT token');
					}
				} catch (error) {
					console.error('Error initializing JaaS meeting:', error);
					if (jitsiContainer) {
						jitsiContainer.innerHTML = '<p style="color: red; padding: 2rem; text-align: center;">Error loading meeting. Please check your JaaS configuration.</p>';
					}
				}
			} else {
				const jitsiUrl = `https://${meetingConfig.jitsi_domain}/${meetingConfig.jitsi_room}`;
				
				if (jitsiContainer) {
					jitsiContainer.innerHTML = `
						<iframe
							src="${jitsiUrl}"
							allow="camera; microphone; fullscreen; display-capture; autoplay"
							allowfullscreen
						></iframe>
					`;
				}
			}

			if ('Notification' in window && Notification.permission === 'granted') {
				new Notification('Meeting Started!', {
					body: 'Your meeting is now live. Join now!',
					icon: '/wp-content/plugins/meeting-manager/icon.png'
				});
			}
		}

		if (subscriptionForm) {
			subscriptionForm.addEventListener('submit', function(e) {
				e.preventDefault();

				const emailInput = subscriptionForm.querySelector('input[type="email"]');
				const joinListCheckbox = subscriptionForm.querySelector('input[name="join_list"]');
				const submitButton = subscriptionForm.querySelector('.subscribe-button');
				const messageEl = block.querySelector('.subscription-message');

				const email = emailInput.value;
				const joinList = joinListCheckbox ? joinListCheckbox.checked : false;

				submitButton.disabled = true;
				submitButton.textContent = 'Subscribing...';

				fetch('/wp-json/meeting-manager/v1/subscribe', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						email: email,
						block_id: blockId,
						join_list: joinList
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						if (messageEl) {
							messageEl.className = 'subscription-message success';
							messageEl.textContent = 'Successfully subscribed! You will receive email notifications.';
							messageEl.style.display = 'block';
						}

						subscriptionForm.reset();

						if ('Notification' in window && Notification.permission === 'default') {
							Notification.requestPermission().then(function(permission) {
								if (permission === 'granted') {
									new Notification('Notifications Enabled!', {
										body: 'You will receive notifications when meetings start.'
									});
								}
							});
						}
					} else {
						throw new Error(data.message || 'Subscription failed');
					}
				})
				.catch(error => {
					if (messageEl) {
						messageEl.className = 'subscription-message error';
						messageEl.textContent = 'Error: ' + error.message;
						messageEl.style.display = 'block';
					}
				})
				.finally(() => {
					submitButton.disabled = false;
					submitButton.textContent = 'Subscribe';
				});
			});
		}

		fetchNextMeeting();
	});
})();