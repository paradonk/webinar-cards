( function () {
	'use strict';

	/* ── Video embedding ─────────────────────────────────────────── */

	/**
	 * Replace thumbnail + play button with an autoplay iframe.
	 * Supports YouTube and Vimeo based on data-platform attribute.
	 *
	 * @param {HTMLElement} wrap  .wbc-webinar-card__video element
	 */
	function embedVideo( wrap ) {
		var videoId  = wrap.dataset.videoId;
		var platform = wrap.dataset.platform || 'youtube';

		if ( ! videoId ) {
			return;
		}

		var iframe = document.createElement( 'iframe' );
		iframe.allowFullscreen = true;
		iframe.className       = 'wbc-webinar-card__iframe';

		if ( 'vimeo' === platform ) {
			iframe.src   = 'https://player.vimeo.com/video/' + videoId + '?autoplay=1&dnt=1';
			iframe.title = 'Vimeo video player';
			iframe.allow = 'autoplay; fullscreen; picture-in-picture';
		} else {
			iframe.src   = 'https://www.youtube-nocookie.com/embed/' + videoId + '?autoplay=1&rel=0&modestbranding=1';
			iframe.title = 'YouTube video player';
			iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
		}

		// Clear thumbnail and play button, then insert the iframe.
		wrap.innerHTML = '';
		wrap.appendChild( iframe );
	}

	/* ── Gate modal ──────────────────────────────────────────────── */

	var wbc         = window.wbcData || {};
	var pendingWrap = null;
	var verifyEmail = '';

	/**
	 * Check for the JS-readable wbc_granted cookie.
	 * Works even when the page HTML (including wbcData.hasAccess) is served
	 * from a cache such as NitroPack or LiteSpeed.
	 *
	 * @return {boolean}
	 */
	function hasGrantCookie() {
		return document.cookie.split( ';' ).some( function ( part ) {
			return part.trim() === 'wbc_granted=1';
		} );
	}

	// Modal element references — populated in initGate().
	var modal, emailStep, verifyStep;
	var emailInput, submitBtn, messageEl, loginLink, registerLink;
	var codeInput, verifySubmit, verifyMessage, verifySubtitle;

	function initGate() {
		modal        = document.getElementById( 'wbc-gate-modal' );
		emailStep    = document.getElementById( 'wbc-gate-email-step' );
		verifyStep   = document.getElementById( 'wbc-gate-verify-step' );
		emailInput   = document.getElementById( 'wbc-gate-email' );
		submitBtn    = document.getElementById( 'wbc-gate-submit' );
		messageEl    = document.getElementById( 'wbc-gate-message' );
		loginLink    = document.getElementById( 'wbc-gate-login' );
		registerLink = document.getElementById( 'wbc-gate-register' );
		codeInput      = document.getElementById( 'wbc-gate-code' );
		verifySubmit   = document.getElementById( 'wbc-gate-verify-submit' );
		verifyMessage  = document.getElementById( 'wbc-gate-verify-message' );
		verifySubtitle = document.getElementById( 'wbc-gate-verify-subtitle' );

		if ( ! modal ) {
			return;
		}

		if ( loginLink    && wbc.loginUrl    ) { loginLink.href    = wbc.loginUrl;    }
		if ( registerLink && wbc.registerUrl ) { registerLink.href = wbc.registerUrl; }

		var emailForm  = document.getElementById( 'wbc-gate-form' );
		var verifyForm = document.getElementById( 'wbc-gate-verify-form' );
		var closeBtn   = modal.querySelector( '.wbc-gate-close' );
		var backdrop   = modal.querySelector( '.wbc-gate-backdrop' );
		var backLink   = document.getElementById( 'wbc-gate-back' );
		var resendLink = document.getElementById( 'wbc-gate-resend' );

		if ( emailForm  ) { emailForm.addEventListener( 'submit', onEmailSubmit ); }
		if ( verifyForm ) { verifyForm.addEventListener( 'submit', onVerifySubmit ); }
		if ( closeBtn   ) { closeBtn.addEventListener( 'click', closeGate ); }
		if ( backdrop   ) { backdrop.addEventListener( 'click', closeGate ); }
		if ( backLink   ) { backLink.addEventListener( 'click', function ( e ) { e.preventDefault(); showEmailStep(); } ); }
		if ( resendLink ) { resendLink.addEventListener( 'click', function ( e ) { e.preventDefault(); resendCode(); } ); }

		// Strip non-digits from code input as the user types.
		if ( codeInput ) {
			codeInput.addEventListener( 'input', function () {
				codeInput.value = codeInput.value.replace( /\D/g, '' );
			} );
		}

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && modal && ! modal.hidden ) {
				closeGate();
			}
		} );
	}

	/* ── Step helpers ────────────────────────────────────────────── */

	function showEmailStep() {
		if ( emailStep  ) { emailStep.removeAttribute( 'hidden' ); }
		if ( verifyStep ) { verifyStep.setAttribute( 'hidden', '' ); }
		if ( emailInput ) { emailInput.value = ''; }
		if ( submitBtn  ) { submitBtn.disabled = false; submitBtn.textContent = 'Continue'; }
		if ( messageEl  ) { messageEl.hidden = true; messageEl.className = 'wbc-gate-message'; messageEl.textContent = ''; }
	}

	function showVerifyStep( email ) {
		verifyEmail = email;
		if ( emailStep  ) { emailStep.setAttribute( 'hidden', '' ); }
		if ( verifyStep ) { verifyStep.removeAttribute( 'hidden' ); }
		if ( verifySubtitle ) {
			verifySubtitle.textContent = 'We sent a 6-digit code to ' + email + '. Check your inbox.';
		}
		if ( codeInput    ) { codeInput.value = ''; }
		if ( verifySubmit ) { verifySubmit.disabled = false; verifySubmit.textContent = 'Verify'; }
		if ( verifyMessage ) { verifyMessage.hidden = true; verifyMessage.className = 'wbc-gate-message'; verifyMessage.textContent = ''; }
		setTimeout( function () { if ( codeInput ) { codeInput.focus(); } }, 50 );
	}

	/* ── Modal open / close ──────────────────────────────────────── */

	function openGate( wrap ) {
		pendingWrap = wrap;
		showEmailStep();
		modal.removeAttribute( 'hidden' );
		modal.removeAttribute( 'aria-hidden' );
		if ( emailInput ) { emailInput.focus(); }
	}

	function closeGate() {
		modal.setAttribute( 'hidden', '' );
		modal.setAttribute( 'aria-hidden', 'true' );
	}

	/* ── Message helpers ─────────────────────────────────────────── */

	function showEmailMessage( text, type ) {
		if ( ! messageEl ) { return; }
		messageEl.textContent = text;
		messageEl.className   = 'wbc-gate-message wbc-gate-message--' + type;
		messageEl.hidden      = false;
	}

	function showVerifyMessage( text, type ) {
		if ( ! verifyMessage ) { return; }
		verifyMessage.textContent = text;
		verifyMessage.className   = 'wbc-gate-message wbc-gate-message--' + type;
		verifyMessage.hidden      = false;
	}

	/* ── Step 1: email submission ────────────────────────────────── */

	function onEmailSubmit( e ) {
		e.preventDefault();

		var email = emailInput ? emailInput.value.trim() : '';
		if ( ! email ) {
			showEmailMessage( 'Please enter a valid email address.', 'error' );
			return;
		}

		if ( submitBtn ) {
			submitBtn.disabled    = true;
			submitBtn.textContent = 'Checking…';
		}
		if ( messageEl ) { messageEl.hidden = true; }

		var body = new FormData();
		body.append( 'action', 'wbc_validate_email' );
		body.append( 'nonce',  wbc.nonce  || '' );
		body.append( 'email',  email );

		fetch( wbc.ajaxUrl || '', { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( resp ) {
				if ( resp.success ) {
					if ( resp.data && resp.data.step === 'verify' ) {
						showVerifyStep( email );
					} else {
						// Direct access granted (shouldn't normally occur after adding verification).
						wbc.hasAccess = true;
						var wrap = pendingWrap;
						closeGate();
						if ( wrap ) { embedVideo( wrap ); }
					}
					return;
				}

				if ( submitBtn ) {
					submitBtn.disabled    = false;
					submitBtn.textContent = 'Continue';
				}

				var d = resp.data || {};

				if ( d.code === 'not_allowed' ) {
					if ( d.login_url    && loginLink    ) { loginLink.href    = d.login_url;    }
					if ( d.register_url && registerLink ) { registerLink.href = d.register_url; }
				}

				showEmailMessage( d.message || 'An error occurred. Please try again.', 'error' );
			} )
			.catch( function () {
				if ( submitBtn ) {
					submitBtn.disabled    = false;
					submitBtn.textContent = 'Continue';
				}
				showEmailMessage( 'Connection error. Please try again.', 'error' );
			} );
	}

	/* ── Step 2: code verification ───────────────────────────────── */

	function onVerifySubmit( e ) {
		e.preventDefault();

		var code = codeInput ? codeInput.value.trim() : '';
		if ( code.length !== 6 ) {
			showVerifyMessage( 'Please enter the 6-digit code from your email.', 'error' );
			return;
		}

		if ( verifySubmit ) {
			verifySubmit.disabled    = true;
			verifySubmit.textContent = 'Verifying…';
		}
		if ( verifyMessage ) { verifyMessage.hidden = true; }

		var body = new FormData();
		body.append( 'action', 'wbc_verify_code' );
		body.append( 'nonce',  wbc.nonce  || '' );
		body.append( 'email',  verifyEmail );
		body.append( 'code',   code );

		fetch( wbc.ajaxUrl || '', { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( resp ) {
				if ( resp.success ) {
					wbc.hasAccess = true;
					var wrap = pendingWrap;
					closeGate();
					if ( wrap ) { embedVideo( wrap ); }
					return;
				}

				if ( verifySubmit ) {
					verifySubmit.disabled    = false;
					verifySubmit.textContent = 'Verify';
				}

				var d = resp.data || {};
				showVerifyMessage( d.message || 'An error occurred. Please try again.', 'error' );
			} )
			.catch( function () {
				if ( verifySubmit ) {
					verifySubmit.disabled    = false;
					verifySubmit.textContent = 'Verify';
				}
				showVerifyMessage( 'Connection error. Please try again.', 'error' );
			} );
	}

	/* ── Resend code ─────────────────────────────────────────────── */

	function resendCode() {
		if ( ! verifyEmail ) { return; }
		if ( verifyMessage ) { verifyMessage.hidden = true; }

		var body = new FormData();
		body.append( 'action', 'wbc_validate_email' );
		body.append( 'nonce',  wbc.nonce  || '' );
		body.append( 'email',  verifyEmail );

		fetch( wbc.ajaxUrl || '', { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( resp ) {
				if ( resp.success ) {
					showVerifyMessage( 'Code resent. Check your inbox.', 'success' );
				} else {
					showVerifyMessage( 'Could not resend code. Please try again.', 'error' );
				}
			} )
			.catch( function () {
				showVerifyMessage( 'Connection error. Please try again.', 'error' );
			} );
	}

	/* ── Card click handler ──────────────────────────────────────── */

	document.addEventListener( 'click', function ( e ) {
		var wrap = e.target.closest( '.wbc-webinar-card__video' );

		// Skip if already replaced by an iframe.
		if ( ! wrap || wrap.querySelector( 'iframe' ) ) {
			return;
		}

		// No gate, visitor is logged in, or already verified this session.
		// hasGrantCookie() reads document.cookie directly — works even when the
		// page HTML is stale from a cache plugin.
		if ( ! wbc.gateEnabled || wbc.hasAccess || hasGrantCookie() ) {
			embedVideo( wrap );
			return;
		}

		// Gate is active and visitor not yet verified — show modal.
		openGate( wrap );
	} );

	/* ── Init ────────────────────────────────────────────────────── */

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initGate );
	} else {
		initGate();
	}

} )();
