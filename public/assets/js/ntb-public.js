/**
 * NOCTIS Taxi Booking — script public.
 * Gère : autocomplétion (étape 1), flux SPA des étapes 2-3-4, devis, paiement.
 *
 * Aucune clé secrète n'est jamais manipulée ici. Tous les calculs et appels
 * Google/Stripe/PayPal passent par le serveur via l'API REST.
 */
( function () {
	'use strict';

	var D = window.NTB2_DATA || {};
	var I = D.i18n || {};

	/* ---------------------------------------------------------------------
	 * Utilitaires API REST
	 * ------------------------------------------------------------------ */
	function api( path, method, body ) {
		return fetch( D.restUrl + path, {
			method: method || 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': D.nonce
			},
			body: body ? JSON.stringify( body ) : undefined
		} ).then( function ( r ) {
			return r.json();
		} );
	}

	function el( sel, ctx ) {
		return ( ctx || document ).querySelector( sel );
	}
	function els( sel, ctx ) {
		return Array.prototype.slice.call( ( ctx || document ).querySelectorAll( sel ) );
	}
	function money( v ) {
		return new Intl.NumberFormat( 'fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 } ).format( v ) + ' ' + ( D.currencySymbol || '€' );
	}
	function showErr( node, msg ) {
		if ( ! node ) return;
		node.textContent = msg;
		node.hidden = false;
	}
	function clearErr( node ) {
		if ( ! node ) return;
		node.textContent = '';
		node.hidden = true;
	}

	/* ─────────────────────────────────────────────────────────────
	   Auto-resize textarea (seulement sur retour à la ligne)
	   ───────────────────────────────────────────────────────────── */
	function initTextareaResize( textarea ) {
		if ( ! textarea ) return;
		var minHeight = 52;
		var adjustHeight = function () {
			textarea.style.height = 'auto';
			var newHeight = Math.max( minHeight, textarea.scrollHeight );
			textarea.style.height = newHeight + 'px';
		};
		textarea.addEventListener( 'input', adjustHeight );
	}

	/* =====================================================================
	 * ÉTAPE 1 — autocomplétion + validation
	 * ================================================================== */
	function initStep1() {
		var form = el( '[data-ntb-step1]' );
		if ( ! form ) return;

		els( '[data-ntb-autocomplete]', form ).forEach( initAutocomplete );

		var submitting = false;

		var submitDiv = el( '[data-ntb-step1-submit]', form );
		if ( submitDiv ) {
			submitDiv.addEventListener( 'click', function () {
				try { sessionStorage.removeItem( 'ntb2_confirm_html' ); } catch(e) {}
				if ( form.requestSubmit ) { form.requestSubmit(); } else { form.dispatchEvent( new Event( 'submit', { bubbles: true, cancelable: true } ) ); if ( form.checkValidity() ) { form.submit(); } }
			} );
			submitDiv.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); submitDiv.click(); }
			} );
		}

		form.addEventListener( 'submit', function ( e ) {
			var ok = true;
			els( '.ntb-field', form ).forEach( function ( f ) {
				var input = el( 'input', f );
				var err = el( '.ntb-field-err', f );
				if ( input && input.required && ! input.value.trim() ) {
					f.classList.add( 'has-error' );
					showErr( err, I.required || 'Requis' );
					ok = false;
				} else {
					f.classList.remove( 'has-error' );
					clearErr( err );
				}
			} );
			if ( ! ok ) {
				e.preventDefault();
			} else {
				submitting = true; // Ne pas effacer la session sur pagehide (redirect normal).
			}
			// Sinon : soumission POST classique → redirection serveur (étape 1).
		} );

		// Efface la session quand l'utilisateur ferme l'onglet ou quitte le site.
		// On ignore : soumission du formulaire (submitting=true), BFCache (persisted=true).
		window.addEventListener( 'pagehide', function ( e ) {
			if ( submitting || e.persisted ) return;
			if ( navigator.sendBeacon ) {
				navigator.sendBeacon( D.homeUrl + '?NTB2_exit=1' );
			}
		} );
	}

	function initAutocomplete( wrap ) {
		var input    = el( 'input', wrap );
		var list     = el( '.ntb-ac-list', wrap );
		var targetId = wrap.getAttribute( 'data-place-id-target' );
		var placeEl  = targetId ? document.getElementById( targetId ) : null;
		if ( ! input || ! list ) return;
		var timer = null;

		input.addEventListener( 'input', function () {
			var q = input.value.trim();
			clearTimeout( timer );
			if ( q.length < 3 ) {
				list.hidden = true;
				return;
			}
			timer = setTimeout( function () {
				api( '/autocomplete?q=' + encodeURIComponent( q ) ).then( function ( res ) {
					renderPredictions( list, input, res.predictions || [], placeEl );
				} );
			}, 280 );
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! wrap.contains( e.target ) ) {
				list.hidden = true;
			}
		} );
	}

	function renderPredictions( list, input, preds, placeEl ) {
		if ( ! preds.length ) {
			list.hidden = true;
			return;
		}
		list.innerHTML = '';
		preds.forEach( function ( p ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'ntb-ac-item';
			b.innerHTML = '<span class="ntb-ac-pin"></span><span class="ntb-ac-txt"><b></b><small></small></span>';
			el( 'b', b ).textContent = p.main || p.full;
			el( 'small', b ).textContent = p.secondary || '';
			b.addEventListener( 'click', function () {
				input.value = p.full || ( p.main + ( p.secondary ? ', ' + p.secondary : '' ) );
				if ( placeEl ) placeEl.value = p.id || '';
				list.hidden = true;
			} );
			list.appendChild( b );
		} );
		list.hidden = false;
	}

	/* =====================================================================
	 * ÉTAPES 2-3-4 — flux SPA
	 * ================================================================== */
	var state = {
		root: null,
		pickup: '', dropoff: '', date: '', time: '',
		pickupPid: '', dropoffPid: '',
		distance: 0, duration: 0,
		polyline: '', lat: 0, lng: 0,
		map: null,
		vehicles: [],
		selected: null,
		bookingId: 0,
		bookingRef: '',
		amount: 0,
		currentStep: 2,
		payMethod: 'stripe',
		stripe: null,
		stripeElements: null,
		stripeClientSecret: '',
		paypalRendered: false,
		paymentMethodId: null
	};

	function prefillFromLoggedInUser() {
		if ( ! D.currentUser ) return;
		var user = D.currentUser;
		var nameInput = el( '#ntb-name' );
		var emailInput = el( '#ntb-email' );
		var phoneInput = el( '#ntb-phone' );

		if ( nameInput && user.firstName && user.lastName ) {
			nameInput.value = user.firstName + ' ' + user.lastName;
			nameInput.disabled = true;
		}
		if ( emailInput && user.email ) {
			emailInput.value = user.email;
			emailInput.disabled = true;
		}
		if ( phoneInput && user.phone ) {
			phoneInput.value = user.phone;
		}
	}


	function initFlow() {
		var root = el( '[data-ntb-steps]' );
		if ( ! root ) return;
		state.root = root;
		initTextareaResize( el( '#ntb-message', root ) );
		prefillFromLoggedInUser();
		state.pickup     = root.getAttribute( 'data-pickup' ) || '';
		state.dropoff    = root.getAttribute( 'data-dropoff' ) || '';
		state.date       = root.getAttribute( 'data-date' ) || '';
		state.time       = root.getAttribute( 'data-time' ) || '';
		state.pickupPid  = root.getAttribute( 'data-pickup-pid' ) || '';
		state.dropoffPid = root.getAttribute( 'data-dropoff-pid' ) || '';

		if ( ! state.pickup || ! state.dropoff ) return;

		// Restaurer la confirmation après un refresh.
		try {
			var _savedConfirm = sessionStorage.getItem( 'ntb2_confirm_html' );
			if ( _savedConfirm ) {
				var _box = el( '[data-ntb-confirm]', root );
				if ( _box ) { _box.innerHTML = _savedConfirm; }
				state.confirmed = true;
				goToStep( 4 );
				return;
			}
		} catch(e) {}

		var keepSession = false; // true = navigation interne, pas de reset.

		// Efface la session si l'utilisateur ferme l'onglet depuis les étapes 2-4.
		// On ne réinitialise pas si l'utilisateur revient en arrière via le bouton custom.
		window.addEventListener( 'pagehide', function ( e ) {
			if ( keepSession || e.persisted ) return;
			if ( navigator.sendBeacon ) {
				navigator.sendBeacon( D.homeUrl + '?NTB2_exit=1' );
			}
		} );

		els( '[data-ntb-back]', root ).forEach( function ( back ) {
			back.hidden = false;
			back.addEventListener( 'click', function () {
				if ( state.currentStep === 2 ) {
					keepSession = true;
					var bookingUrl = state.root.getAttribute( 'data-booking-url' );
					if ( document.referrer && document.referrer.indexOf( bookingUrl ) !== -1 ) {
						history.back();
					} else {
						window.location.href = bookingUrl;
					}
				} else {
					goToStep( 2 );
				}
			} );
		} );

		// Bouton confirmation sélection véhicule (étape 2 → 3).
		var selCta = el( '[data-ntb-sel-cta]', root );
		if ( selCta ) {
			selCta.addEventListener( 'click', function () {
				if ( ! state.selected ) return;
				goToStep( 3 );
				preparePayment();
			} );
		}

		// Onglets de paiement.
		els( '.ntb-pay-tab', root ).forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				selectPayMethod( tab.getAttribute( 'data-pay' ) );
			} );
		} );

		// Un seul moyen de paiement (pas d'onglets) : la zone de paiement
		// s'initialise automatiquement dès que les coordonnées sont complètes.
		if ( ! els( '.ntb-pay-tab', root ).length ) {
			state.payMethod = D.hasStripe ? 'stripe' : 'paypal';
			[ '#ntb-name', '#ntb-phone', '#ntb-email' ].forEach( function ( sel ) {
				var input = el( sel, root );
				if ( ! input ) return;
				input.addEventListener( 'blur',  maybeAutoPay );
				input.addEventListener( 'input', maybeAutoPay );
			} );
		}

		// Bouton paiement Stripe.
		var sBtn = el( '[data-ntb-pay-stripe]', root );
		if ( sBtn ) {
			sBtn.onclick = payWithStripe;
		}

		loadQuotes();

		// Re-render la carte quand on bascule light ↔ dark.
		new MutationObserver( function () {
			if ( state.map && ( state.polyline || state.lat ) ) {
				renderRouteMap();
			}
		} ).observe( document.documentElement, { attributes: true, attributeFilter: [ 'class' ] } );
	}

	function goToStep( n ) {
		state.currentStep = n;
		var asideEl = el( '.ntb-step2-aside' );
		if ( asideEl ) asideEl.hidden = ( n !== 2 );
		// En-tête commun à toutes les étapes : seul le titre change.
		var stepTitle = el( '[data-ntb-step-title]', state.root );
		if ( stepTitle ) {
			var t = stepTitle.getAttribute( 'data-title-' + n );
			if ( t ) stepTitle.textContent = t;
		}
		if ( state.root ) state.root.classList.toggle( 'ntb-no-aside', n !== 2 );
		els( '[data-ntb-screen]', state.root ).forEach( function ( s ) {
			s.hidden = parseInt( s.getAttribute( 'data-ntb-screen' ), 10 ) !== n;
			if ( ! s.hidden ) {
				s.classList.remove( 'ntb-anim' );
				void s.offsetWidth; // reflow pour rejouer l'animation.
				s.classList.add( 'ntb-anim' );
			}
		} );
		// Progression.
		els( '.ntb-prog-node', state.root ).forEach( function ( node ) {
			var step = parseInt( node.getAttribute( 'data-step' ), 10 );
			node.classList.toggle( 'active', step === n );
			node.classList.toggle( 'done', step < n );
		} );
		els( '.ntb-prog-line', state.root ).forEach( function ( line, i ) {
			line.classList.toggle( 'done', n > ( i + 2 ) );
		} );
		var confirmed = state.confirmed || false;
		els( '[data-ntb-back]', state.root ).forEach( function ( back ) {
			back.hidden = confirmed || ( n < 2 || n > 3 );
		} );

		if ( n === 3 ) {
			// Champs pré-remplis (utilisateur connecté) : la zone de paiement
			// peut s'initialiser dès l'arrivée sur l'étape.
			maybeAutoPay();
		}

	}

	/* ---------- Étape 2 : devis ---------- */
	function loadQuotes() {
		var loading = el( '[data-ntb-loading]', state.root );
		var errBox = el( '[data-ntb-error]', state.root );
		clearErr( errBox );

		if ( loading ) loading.hidden = false;

		api( '/quotes', 'POST', {
			pickup_address:   state.pickup,
			dropoff_address:  state.dropoff,
			ride_date:        state.date,
			ride_time:        state.time,
			pickup_place_id:  state.pickupPid,
			dropoff_place_id: state.dropoffPid
		} ).then( function ( res ) {
			if ( loading ) loading.hidden = true;
			if ( ! res.success ) {
				showErr( errBox, res.message || I.routeError );
				return;
			}
			state.distance = res.distance_km;
			state.duration = res.duration_min;
			state.polyline = res.polyline || '';
			state.lat      = res.lat || 0;
			state.lng      = res.lng || 0;
			state.vehicles = res.vehicles || [];
			renderDistance();
			renderRouteMap();
			renderVehicles();
		} ).catch( function () {
			if ( loading ) loading.hidden = true;
			showErr( errBox, I.routeError );
		} );
	}

	function renderDistance() {
		var txt = state.distance + ' ' + ( I.kmUnit || 'km' ) + ' · ' + Math.round( state.duration ) + ' ' + ( I.minUnit || 'min' );
		var banner = el( '[data-ntb-distance]', state.root ) || el( '[data-ntb-distance]' );
		if ( banner ) {
			el( '.ntb-db-main', banner ).innerHTML =
				'<b>' + state.distance + ' ' + ( I.kmUnit || 'km' ) + '</b> · ' +
				'<b>' + Math.round( state.duration ) + ' ' + ( I.minUnit || 'min' ) + '</b>';
			banner.hidden = false;
		}
		var sumRoute = el( '[data-ntb-sum-route]' );
		if ( sumRoute ) {
			el( '[data-ntb-sum-route-txt]', sumRoute ).textContent = txt;
			sumRoute.hidden = false;
		}
	}

	function renderVehicles() {
		var wrap = el( '[data-ntb-vehicles]', state.root );
		if ( ! wrap ) return;
		wrap.innerHTML = '';
		if ( ! state.vehicles.length ) {
			showErr( el( '[data-ntb-error]', state.root ), I.noVehicles );
			return;
		}
		state.vehicles.forEach( function ( v ) {
			var card = document.createElement( 'div' );
			card.setAttribute( 'role', 'button' );
			card.setAttribute( 'tabindex', '0' );
			card.className = 'ntb-vcard';
			card.innerHTML =
				'<div class="ntb-vc-photo">' +
				( v.image_url ? '<img src="' + v.image_url + '" alt="" loading="lazy" />' : '<span class="ntb-vc-ph"></span>' ) +
				'</div>' +
				'<div class="ntb-vc-body">' +
				'<div class="ntb-vc-row"><span class="ntb-vc-name"></span><span class="ntb-vc-price"></span></div>' +
				'<p class="ntb-vc-blurb"></p>' +
								'<div class="ntb-vc-specs">' +
				'<span class="ntb-vc-cap">' +
				'<svg class="ntb-vc-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>' +
				'<span class="ntb-vc-cap-n"></span>' +
				'</span>' +
				'<span class="ntb-vc-lug">' +
				'<svg class="ntb-vc-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>' +
				'<span class="ntb-vc-lug-n"></span>' +
				'</span>' +
				'</div>' +
				'</div>';
			el( '.ntb-vc-name', card ).textContent = v.name;
			el( '.ntb-vc-price', card ).textContent = v.price_formatted;
			el( '.ntb-vc-blurb', card ).textContent = v.description || '';
			el( '.ntb-vc-cap-n', card ).textContent = v.capacity;
			el( '.ntb-vc-lug-n', card ).textContent = v.luggage !== undefined ? v.luggage : 2;
			card.addEventListener( 'click', function () {
				selectVehicle( v, card );
			} );
			wrap.appendChild( card );
		} );

		// Flèches carrousel (desktop seulement — masquées en CSS sur mobile).
		var carousel = wrap.parentElement;
		if ( carousel && carousel.classList.contains( 'ntb-veh-carousel' ) ) {
			var prevBtn = el( '.ntb-veh-prev', carousel );
			var nextBtn = el( '.ntb-veh-next', carousel );
			if ( prevBtn && nextBtn ) {
				function updArrows() {
					prevBtn.disabled = wrap.scrollLeft <= 2;
					nextBtn.disabled = wrap.scrollLeft + wrap.clientWidth >= wrap.scrollWidth - 2;
				}
				if ( ! wrap._arrowInit ) {
					wrap._arrowInit = true;
					wrap.addEventListener( 'scroll', updArrows, { passive: true } );
					prevBtn.addEventListener( 'click', function () {
						wrap.scrollBy( { left: -Math.round( wrap.clientWidth / 3 + 16 ), behavior: 'smooth' } );
					} );
					nextBtn.addEventListener( 'click', function () {
						wrap.scrollBy( { left: Math.round( wrap.clientWidth / 3 + 16 ), behavior: 'smooth' } );
					} );
				}
				// Réinitialise le scroll après rechargement des cartes puis met à jour les flèches.
				wrap.scrollLeft = 0;
				requestAnimationFrame( function () {
					updArrows();
					prevBtn.style.visibility = 'visible';
					nextBtn.style.visibility = 'visible';
				} );
			}
		}
	}

	/* ---------- Carte du trajet (Leaflet + tuiles CartoDB) ---------- */
	function decodePolyline( str ) {
		var index = 0, lat = 0, lng = 0, coords = [], shift, result, b, dlat, dlng;
		while ( index < str.length ) {
			shift = 0; result = 0;
			do { b = str.charCodeAt( index++ ) - 63; result |= ( b & 0x1f ) << shift; shift += 5; } while ( b >= 0x20 );
			dlat = ( ( result & 1 ) ? ~( result >> 1 ) : ( result >> 1 ) );
			lat += dlat;
			shift = 0; result = 0;
			do { b = str.charCodeAt( index++ ) - 63; result |= ( b & 0x1f ) << shift; shift += 5; } while ( b >= 0x20 );
			dlng = ( ( result & 1 ) ? ~( result >> 1 ) : ( result >> 1 ) );
			lng += dlng;
			coords.push( [ lat / 1e5, lng / 1e5 ] );
		}
		return coords;
	}

	function renderRouteMap() {
		if ( typeof window.L === 'undefined' ) return;
		var mapEl = document.getElementById( 'ntb-route-map' );
		if ( ! mapEl ) return;

		var hasPolyline = !! state.polyline;
		var hasSingle   = ! hasPolyline && !! state.lat;
		if ( ! hasPolyline && ! hasSingle ) return;

		var coords = hasPolyline ? decodePolyline( state.polyline ) : [];
		if ( hasPolyline && coords.length < 2 ) return;

		if ( state.map ) { state.map.remove(); state.map = null; }

		mapEl.hidden = false;
		mapEl.removeAttribute( 'aria-hidden' );

		var isLight  = document.documentElement.classList.contains( 'nc-light' );
		var tileUrl  = isLight
			? 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png'
			: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
		var accent   = isLight ? '#2563eb' : '#5b8fcf';
		var dotStyle = { radius: 8, fillOpacity: 1, stroke: true, weight: 3 };
		var line     = null;

		var map = window.L.map( mapEl, { zoomControl: false, attributionControl: true } );
		window.L.tileLayer( tileUrl, {
			attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/">CARTO</a>',
			subdomains: 'abcd',
			maxZoom: 18
		} ).addTo( map );

		if ( hasSingle ) {
			window.L.circleMarker( [ state.lat, state.lng ], Object.assign( {}, dotStyle, { color: accent, fillColor: '#ffffff' } ) ).addTo( map );
			map.setView( [ state.lat, state.lng ], 14 );
		} else {
			line = window.L.polyline( coords, { color: accent, weight: 4, opacity: 0.9 } ).addTo( map );
			window.L.circleMarker( coords[0],                   Object.assign( {}, dotStyle, { color: accent, fillColor: '#ffffff' } ) ).addTo( map );
			window.L.circleMarker( coords[ coords.length - 1 ], Object.assign( {}, dotStyle, { color: accent, fillColor: accent   } ) ).addTo( map );
			map.fitBounds( line.getBounds(), { padding: [ 30, 30 ] } );
		}

		state.map = map;

		setTimeout( function () {
			if ( ! state.map ) return;
			state.map.invalidateSize();
			if ( line ) {
				state.map.fitBounds( line.getBounds(), { padding: [ 30, 30 ] } );
			}
		}, 50 );
	}

	function selectVehicle( v, card ) {
		state.selected = v;
		els( '.ntb-vcard', state.root ).forEach( function ( c ) {
			c.classList.remove( 'sel' );
		} );
		card.classList.add( 'sel' );
		// Déplier l'aside sur mobile dès la sélection
		var asideEl = el( '.ntb-step2-aside' );
		if ( asideEl ) {
			asideEl.classList.remove( 'is-collapsed' );
			asideEl.setAttribute( 'data-ntb-selected', '' );
		}
		renderRecap();
		var bar = el( '[data-ntb-sel-bar]', state.root ) || el( '[data-ntb-sel-bar]' );
		if ( bar ) {
			el( '[data-ntb-sel-name]', bar ).textContent = v.name;
			el( '[data-ntb-sel-price]', bar ).textContent = v.price_formatted;
			el( '[data-ntb-sel-cta]', bar ).textContent = 'Choisir ' + v.name;
			var sub = el( '[data-ntb-sel-subtitle]', bar );
			if ( sub ) sub.textContent = v.description || '';
			// Image du véhicule dans la summary card
			var sumImg = el( '[data-ntb-sum-img]', bar );
			if ( sumImg ) {
				if ( v.image_url ) {
					sumImg.src    = v.image_url;
					sumImg.alt    = v.name;
					sumImg.hidden = false;
				} else {
					sumImg.hidden = true;
				}
			}
			bar.hidden = false;
		}
	}

	/* ---------- Étape 3 : récap + paiement ---------- */
	function renderRecap() {
		var box = el( '[data-ntb-recap]', state.root );
		if ( ! box || ! state.selected ) return;
		box.innerHTML =
			'<h3 class="ntb-recap-title">' + ( 'Récapitulatif' ) + '</h3>' +
			'<dl class="ntb-recap-lines">' +
			line( 'Départ', state.pickup ) +
			line( 'Arrivée', state.dropoff ) +
			line( 'Date', state.date + ( state.time ? ' · ' + state.time : '' ) ) +
			line( 'Véhicule', state.selected.name ) +
			line( ( I.distance || 'Distance' ), state.distance + ' ' + ( I.kmUnit || 'km' ) ) +
			'</dl>' +
			'<div class="ntb-recap-total"><span>Total</span><span class="ntb-recap-amount">' + state.selected.price_formatted + '</span></div>';
	}
	function line( k, v ) {
		var d = document.createElement( 'div' );
		var dt = document.createElement( 'dt' );
		var dd = document.createElement( 'dd' );
		dt.textContent = k; dd.textContent = v;
		d.appendChild( dt ); d.appendChild( dd );
		return d.outerHTML;
	}

	/* Coordonnées complètes ? — contrôle silencieux, sans afficher d'erreurs. */
	function customerComplete() {
		var name  = el( '#ntb-name', state.root );
		var phone = el( '#ntb-phone', state.root );
		var email = el( '#ntb-email', state.root );
		return !! ( name && phone && email &&
			name.value.trim() && phone.value.trim() &&
			/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test( email.value.trim() ) );
	}

	/* Sans onglets : déclenche la zone de paiement une fois les coordonnées remplies. */
	function maybeAutoPay() {
		if ( state.autoPayDone || state.currentStep !== 3 ) return;
		if ( els( '.ntb-pay-tab', state.root ).length ) return;
		if ( ! customerComplete() ) return;
		state.autoPayDone = true;
		selectPayMethod( state.payMethod );
	}

	function selectPayMethod( method ) {
		state.payMethod = method;
		els( '.ntb-pay-tab', state.root ).forEach( function ( t ) {
			t.classList.toggle( 'active', t.getAttribute( 'data-pay' ) === method );
		} );
		els( '[data-pay-panel]', state.root ).forEach( function ( p ) {
			p.hidden = p.getAttribute( 'data-pay-panel' ) !== method;
		} );
		// Zone de paiement ouverte : on annule l'égalisation des hauteurs
		// formulaire/récap (le formulaire devient plus grand que le récap).
		var grid = el( '.ntb-step3-grid', state.root );
		if ( grid ) grid.classList.add( 'ntb-pay-open' );
		if ( method === 'stripe' ) {
			initStripeForm();
		}
	}

	function initStripeForm() {
		if ( state.stripeElements ) return Promise.resolve();
		if ( ! validateCustomer() ) return Promise.reject( new Error( 'Validation failed' ) );
		var errBox = el( '[data-ntb-pay-error]', state.root );
		clearErr( errBox );

		return createBooking().then( function ( res ) {
			if ( ! res.success ) {
				throw new Error( res.message || I.paymentError );
			}
			state.bookingId = res.booking_id;
			state.bookingRef = res.booking_ref;
			state.amount = res.amount;
			state.stripeClientSecret = res.stripe_client_secret;

			state.stripe = Stripe( res.stripe_publishable );
			// Couleurs héritées du parent : on résout les variables CSS du design
			// system (--ntb-*) en valeurs concrètes pour l'appearance Stripe.
			var cssColor = function ( varName ) {
				var probe = document.createElement( 'div' );
				probe.style.cssText = 'display:none;color:var(' + varName + ')';
				state.root.appendChild( probe );
				var c = getComputedStyle( probe ).color;
				probe.remove();
				return c;
			};
			var rootCS = getComputedStyle( state.root );
			state.stripeElements = state.stripe.elements( {
				clientSecret: res.stripe_client_secret,
				appearance: {
					theme: 'flat',
					variables: {
						colorPrimary:       cssColor( '--ntb-accent' ),
						colorBackground:    cssColor( '--ntb-bg2' ),
						colorText:          cssColor( '--ntb-text' ),
						colorDanger:        cssColor( '--ntb-danger' ),
						colorTextSecondary: cssColor( '--ntb-muted' ),
						colorIconTab:       cssColor( '--ntb-text' ),
						fontFamily:         rootCS.fontFamily,
						borderRadius:       ( rootCS.getPropertyValue( '--ntb-r-sm' ) || '9px' ).trim(),
						spacingUnit:        '4px',
					},
					rules: {
						'.Input':       { backgroundColor: cssColor( '--ntb-surf' ), border: '1px solid ' + cssColor( '--ntb-line' ), boxShadow: 'none' },
						'.Input:focus': { border: '1px solid ' + cssColor( '--ntb-accent' ), boxShadow: 'none' },
						'.Label':       { color: cssColor( '--ntb-muted' ) },
						'.Block':       { backgroundColor: cssColor( '--ntb-bg2' ), border: '1px solid ' + cssColor( '--ntb-line' ) },
						'.Tab':         { backgroundColor: cssColor( '--ntb-surf' ), border: '1px solid ' + cssColor( '--ntb-line' ), color: cssColor( '--ntb-muted' ) },
						'.Tab--selected': { backgroundColor: cssColor( '--ntb-accent' ), color: cssColor( '--ntb-on-accent' ), border: '1px solid ' + cssColor( '--ntb-accent' ) },
					}
				}
			} );
			var stripeEl = el( '#ntb-stripe-element', state.root );
			if ( stripeEl && ! stripeEl.querySelector( '.StripeElement' ) ) {
				var pe = state.stripeElements.create( 'payment' );
				pe.mount( '#ntb-stripe-element' );
				var btn = el( '[data-ntb-pay-stripe]', state.root );
				pe.on( 'ready', function () {
					btn.disabled = false;
					btn.textContent = ( I.payNow || 'Payer' ) + ' ' + money( state.amount );
					btn.onclick = onStripePayClick;
				} );
			}
		} ).catch( function ( e ) {
			showErr( errBox, e.message || I.paymentError );
			throw e;
		} );
	}

	function collectCustomer() {
		return {
			full_name: ( el( '#ntb-name', state.root ).value || '' ).trim(),
			phone: ( el( '#ntb-phone', state.root ).value || '' ).trim(),
			email: ( el( '#ntb-email', state.root ).value || '' ).trim(),
			customer_message: ( el( '#ntb-message', state.root ).value || '' ).trim()
		};
	}

	function validateCustomer() {
		var ok = true;
		[ '#ntb-name', '#ntb-phone', '#ntb-email' ].forEach( function ( sel ) {
			var input = el( sel, state.root );
			var field = input.closest( '.ntb-field' );
			var err = el( '.ntb-field-err', field );
			var bad = ! input.value.trim();
			if ( sel === '#ntb-email' && input.value.trim() && ! /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test( input.value ) ) {
				bad = true;
				showErr( err, I.invalidEmail );
			} else if ( bad ) {
				showErr( err, I.required );
			} else {
				clearErr( err );
			}
			field.classList.toggle( 'has-error', bad );
			if ( bad ) ok = false;
		} );
		return ok;
	}

	/**
	 * Crée la réservation (en attente) côté serveur et prépare la passerelle.
	 */
	function createBooking() {
		var c = collectCustomer();
		var bookingData = {
			vehicle_id:       state.selected.id,
			pickup_address:   state.pickup,
			dropoff_address:  state.dropoff,
			pickup_place_id:  state.pickupPid,
			dropoff_place_id: state.dropoffPid,
			ride_date:        state.date,
			ride_time:        state.time,
			full_name:        c.full_name,
			phone:            c.phone,
			email:            c.email,
			customer_message: c.customer_message,
			payment_method:   state.payMethod
		};

		return api( '/booking', 'POST', bookingData );
	}

	/**
	 * Pré-initialise PayPal (les boutons doivent exister tôt).
	 */
	function preparePayment() {
		if ( D.hasPaypal && window.paypal && ! state.paypalRendered ) {
			renderPaypalButtons();
		}
	}

	/* ---------- Stripe ---------- */
	function payWithStripe() {
		var btn = el( '[data-ntb-pay-stripe]', state.root );
		btn.disabled = true;
		btn.textContent = I.processing || 'Traitement…';

		initStripeForm().then( function () {
			btn.disabled = false;
			btn.textContent = ( I.payNow || 'Payer' ) + ' ' + money( state.amount );
			btn.onclick = confirmStripePayment;
		} ).catch( function ( e ) {
			btn.disabled = false;
			btn.textContent = I.payNow || 'Payer maintenant';
		} );
	}

	function confirmStripePayment() {
		var errBox = el( '[data-ntb-pay-error]', state.root );
		var btn = el( '[data-ntb-pay-stripe]', state.root );
		btn.disabled = true;
		btn.textContent = I.processing || 'Traitement…';
		clearErr( errBox );

		state.stripe.confirmPayment( {
			elements: state.stripeElements,
			redirect: 'if_required'
		} ).then( function ( result ) {
			if ( result.error ) {
				throw new Error( result.error.message || I.paymentError );
			}
			var intentId = result.paymentIntent ? result.paymentIntent.id : '';
			return api( '/confirm/stripe', 'POST', {
				booking_id: state.bookingId,
				payment_intent_id: intentId
			} );
		} ).then( function ( res ) {
			if ( ! res || ! res.success ) {
				throw new Error( ( res && res.message ) || I.paymentError );
			}
			renderConfirmation( res.summary );
		} ).catch( function ( e ) {
			showErr( errBox, e.message || I.paymentError );
			btn.disabled = false;
			btn.textContent = ( I.payNow || 'Payer' ) + ' ' + money( state.amount );
		} );
	}

	function onStripePayClick() {
		confirmStripePayment();
	}

	// Confirme côté serveur après un paiement par carte enregistrée.
	function finalizeStripePayment() {
		var intentId = ( state.stripeClientSecret || '' ).split( '_secret_' )[ 0 ];
		return api( '/confirm/stripe', 'POST', {
			booking_id: state.bookingId,
			payment_intent_id: intentId
		} ).then( function ( res ) {
			if ( ! res || ! res.success ) {
				throw new Error( ( res && res.message ) || I.paymentError );
			}
			renderConfirmation( res.summary );
		} );
	}

	/* ---------- PayPal ---------- */
	function renderPaypalButtons() {
		var container = el( '#ntb-paypal-buttons', state.root );
		if ( ! container || ! window.paypal ) return;
		state.paypalRendered = true;

		window.paypal.Buttons( {
			createOrder: function () {
				if ( ! validateCustomer() ) {
					return Promise.reject( new Error( I.required ) );
				}
				return createBooking().then( function ( res ) {
					if ( ! res.success ) throw new Error( res.message );
					state.bookingId = res.booking_id;
					return api( '/paypal/order', 'POST', { booking_id: res.booking_id } ).then( function ( o ) {
						if ( ! o.success ) throw new Error( o.message );
						return o.order_id;
					} );
				} );
			},
			onApprove: function ( data ) {
				return api( '/paypal/capture', 'POST', {
					booking_id: state.bookingId,
					order_id: data.orderID
				} ).then( function ( res ) {
					if ( ! res.success ) {
						showErr( el( '[data-ntb-pay-error]', state.root ), res.message || I.paymentError );
						return;
					}
					renderConfirmation( res.summary );
				} );
			},
			onError: function () {
				showErr( el( '[data-ntb-pay-error]', state.root ), I.paymentError );
			}
		} ).render( '#ntb-paypal-buttons' );
	}

	/* ---------- Étape 4 : confirmation ---------- */
	function renderConfirmation( s ) {
		var box = el( '[data-ntb-confirm]', state.root );
		if ( ! box ) return;
		var html =
			'<div class="ntb-confirm-icon"><svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg></div>' +
			'<h2 class="ntb-confirm-title">' + ( I.confirmTitle || 'Réservation confirmée' ) + '</h2>' +
			'<p class="ntb-confirm-ref">' + s.booking_ref + '</p>' +
			'<dl class="ntb-recap-lines">' +
			line( 'Trajet', s.pickup + ' → ' + s.dropoff ) +
			line( 'Date', s.date + ( s.time ? ' · ' + s.time : '' ) ) +
			line( 'Véhicule', s.vehicle ) +
			line( 'Client', s.full_name ) +
			'</dl>' +
			'<div class="ntb-recap-total"><span>Payé</span><span class="ntb-recap-amount">' + s.price + '</span></div>' +
			'<p class="ntb-confirm-note">' + 'Un email de confirmation vous a été envoyé.' + '</p>';
		box.innerHTML = html;
		try { sessionStorage.setItem( 'ntb2_confirm_html', html ); } catch(e) {}
		state.confirmed = true;
		goToStep( 4 );
	}

	/* ===================================================================== */
	function killOutlines() {
		/* Injecter un <style> en fin de <head> — vient après Astra dans le DOM,
		   donc gagne en ordre source pour toute règle de spécificité équivalente. */
		if ( ! document.getElementById( 'ntb-kill-outline' ) ) {
			var s = document.createElement( 'style' );
			s.id = 'ntb-kill-outline';
			s.textContent =
				'.ntb-scope *:focus,.ntb-scope *:focus-visible,.ntb-scope *:focus-within{' +
				'outline:0!important;outline-style:none!important;' +
				'outline-width:0!important;outline-color:transparent!important;' +
				'-webkit-tap-highlight-color:transparent!important}' +
				/* border-style: dotted injecté par Astra sur input:focus */
				'.ntb-scope .ntb-field-input input:focus,' +
				'.ntb-scope .ntb-field-input input:focus-visible,' +
				'.ntb-scope .ntb-field-input textarea:focus,' +
				'.ntb-scope .ntb-field-input textarea:focus-visible{' +
				'border:none!important}' +
				'.ntb-scope select:focus,.ntb-scope button:focus,.ntb-scope button:focus-visible{' +
				'border-style:solid!important}';
			document.head.appendChild( s );
		}

		function stripOutline( el ) {
			el.style.setProperty( 'outline', '0', 'important' );
			el.style.setProperty( 'outline-style', 'none', 'important' );
			el.style.setProperty( 'outline-width', '0', 'important' );
			el.style.setProperty( 'outline-color', 'transparent', 'important' );
		}

		/* Appliquer à tous les descendants focusables dès le chargement */
		document.querySelectorAll( '.ntb-scope *' ).forEach( stripOutline );

		/* Remonter depuis .ntb-scope jusqu'au body */
		document.querySelectorAll( '.ntb-scope' ).forEach( function ( scope ) {
			var el = scope;
			while ( el && el !== document.body ) {
				stripOutline( el );
				el = el.parentElement;
			}
		} );

		/* Sur chaque focus, réappliquer sur la cible ET ses ancêtres */
		document.addEventListener( 'focusin', function ( e ) {
			var scope = e.target.closest( '.ntb-scope' );
			if ( ! scope ) return;
			var el = e.target;
			while ( el && el !== document.body ) {
				stripOutline( el );
				el = el.parentElement;
			}
		} );
	}

	function getNavOffset() {
		var maxBottom = 0;
		var adminBar = document.getElementById( 'wpadminbar' );
		if ( adminBar ) {
			maxBottom = Math.max( maxBottom, adminBar.getBoundingClientRect().bottom );
		}
		// Cast a wide net: body children, header tree (2 levels deep), named theme selectors
		var sel = [
			'body > *',
			'header', 'header > *', 'header > * > *',
			'nav', '[role="navigation"]', '[role="banner"]',
			'#masthead', '.site-header', '.main-navigation',
			'.navbar', '#header', '.header', '.site-header-wrap'
		].join( ',' );
		var seen = [];
		Array.prototype.forEach.call( document.querySelectorAll( sel ), function( el ) {
			if ( seen.indexOf( el ) !== -1 ) return;
			seen.push( el );
			if ( el.closest( '[data-ntb-steps]' ) ) return; // skip plugin
			var cs = window.getComputedStyle( el );
			var isFixedOrSticky = cs.position === 'fixed' || cs.position === 'sticky';
			var isKnownHeader = el.matches( '#masthead,.site-header,header,[role="banner"],#header,.header,.site-header-wrap' );
			if ( !isFixedOrSticky && !isKnownHeader ) return;
			var rect = el.getBoundingClientRect();
			if ( rect.height > 20 && rect.height < window.innerHeight * 0.25 && rect.top >= -1 && rect.top < 100 ) {
				maxBottom = Math.max( maxBottom, rect.bottom );
			}
		} );
		return Math.round( maxBottom );
	}

	/* =====================================================================
	 * ASIDE STEP 2
	 * Mode A (aside_detach_body désactivé) : parcourt les ancêtres et force
	 *   overflow:visible pour que position:sticky fonctionne dans n'importe
	 *   quel thème.
	 * Mode B (aside_detach_body activé) : déplace l'aside directement dans
	 *   <body> et le positionne en fixed calé sur le bord droit du layout.
	 *   Zéro risque d'overflow ou de transform qui casserait la position.
	 * ================================================================== */
	function initAside() {
		var aside = el( '.ntb-step2-aside' );
		if ( ! aside ) return;

		// Détecte si le header est visible (scroll proche du haut) — gère le gap supérieur
		var updateHeaderVisible = null;
		if ( window.innerWidth > 1024 ) {
			updateHeaderVisible = function() {
				var navEl = document.querySelector( 'header, .site-header, #masthead, [class*="header"]' );
				var threshold = navEl ? navEl.getBoundingClientRect().bottom : 0;
				aside.classList.toggle( 'ntb-header-visible', threshold > 0 );
			};
			updateHeaderVisible();
			window.addEventListener( 'scroll', updateHeaderVisible, { passive: true } );
		}

		// Bottom sheet swipe (mobile)
		var handle = el( '[data-ntb-aside-handle]', aside );
		if ( handle ) {

			var ctaOriginalText = null;
			function setCollapsed( v ) {
				aside.classList.toggle( 'is-collapsed', v );
				if ( v ) {
					// Hauteur visible = du haut du panneau au bas de la barre CTA
					// (padding inclus) → même respiration en bas qu'en mode ouvert.
					var bar = el( '.ntb-sum-bar', aside );
					if ( bar && bar.offsetParent !== null ) {
						var h = Math.ceil( bar.getBoundingClientRect().bottom - aside.getBoundingClientRect().top );
						if ( h > 0 ) aside.style.setProperty( '--ntb-collapsed-h', h + 'px' );
					}
				}
				var cta = el( '[data-ntb-sel-cta]', aside );
				if ( ! cta ) return;
				if ( ctaOriginalText === null ) ctaOriginalText = cta.textContent;
				if ( v && aside.hasAttribute( 'data-ntb-selected' ) ) {
					var priceEl = el( '[data-ntb-sel-price]', aside );
					var price = priceEl ? priceEl.textContent.trim() : '';
					cta.textContent = price ? ctaOriginalText + ' · ' + price : ctaOriginalText;
				} else {
					cta.textContent = ctaOriginalText;
				}
			}

			// Démarre collapsed sur mobile
			if ( window.innerWidth <= 1024 ) setCollapsed( true );

			window.addEventListener( 'resize', function () {
				if ( window.innerWidth > 1024 ) aside.classList.remove( 'is-collapsed' );
			}, { passive: true } );

			var startY = 0;
			handle.addEventListener( 'touchstart', function ( e ) { startY = e.touches[0].clientY; }, { passive: true } );
			handle.addEventListener( 'touchend', function ( e ) {
				var dy = e.changedTouches[0].clientY - startY;
				if ( Math.abs( dy ) <= 30 ) return;
				if ( dy < 0 ) {
					// Swipe haut : toujours ouvrir
					setCollapsed( false );
				} else if ( aside.hasAttribute( 'data-ntb-selected' ) && ! aside.classList.contains( 'is-collapsed' ) ) {
					// Swipe bas : collapse seulement si véhicule sélectionné et pas déjà collapsed
					setCollapsed( true );
				}
			}, { passive: true } );
			handle.addEventListener( 'click', function () { setCollapsed( false ); } );

			// Tap n'importe où sur la bande collapsed pour ouvrir (sans bloquer les clicks quand ouvert)
			aside.addEventListener( 'click', function ( e ) {
				if ( window.innerWidth <= 1024 && aside.classList.contains( 'is-collapsed' ) ) {
					e.stopPropagation();
					setCollapsed( false );
				}
			} );
		}

		// Toggle "pour moi / pour un invité"
		els( '[data-ntb-opt]', aside ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				els( '[data-ntb-opt]', aside ).forEach( function ( b ) {
					b.classList.remove( 'is-sel' );
					b.setAttribute( 'aria-checked', 'false' );
				} );
				btn.classList.add( 'is-sel' );
				btn.setAttribute( 'aria-checked', 'true' );
			} );
		} );

		if ( D.asideDetachBody ) {
			var layout = aside.closest( '.ntb-step2-layout' );
			if ( ! layout ) return;

			function pin() {
				if ( window.innerWidth <= 1024 ) {
					aside.style.position = '';
					aside.style.left     = '';
					aside.style.top      = '';
					aside.style.width    = '';
					aside.style.height   = '';
					aside.style.zIndex   = '';
					aside.style.removeProperty( '--ntb-nav-offset' );
					return;
				}
				var navOff = getNavOffset();
				var lr = layout.getBoundingClientRect();
				aside.style.position = 'fixed';
				aside.style.left     = Math.round( lr.right - 440 ) + 'px';
				aside.style.top      = ( navOff + 24 ) + 'px';
				aside.style.width    = '440px';
				aside.style.zIndex   = '50';
				aside.style.height   = 'calc(100vh - ' + navOff + 'px - 48px)';
			}

			document.body.appendChild( aside );
			aside.classList.add( 'ntb-scope' );
			window.addEventListener( 'resize', pin );
			var rafPinPending = false;
			window.addEventListener( 'scroll', function() {
				if ( rafPinPending ) return;
				rafPinPending = true;
				requestAnimationFrame( function() { rafPinPending = false; pin(); } );
			}, { passive: true } );
			pin();

		} else {
			var node = aside.parentElement;
			while ( node && node !== document.body ) {
				var s = window.getComputedStyle( node );
				if ( s.overflow !== 'visible' || s.overflowX !== 'visible' || s.overflowY !== 'visible' ) {
					node.style.setProperty( 'overflow', 'visible', 'important' );
				}
				node = node.parentElement;
			}
			var navOff = getNavOffset();
			aside.style.setProperty( '--ntb-nav-offset', navOff + 'px' );
			// Re-measure on load: some themes (Astra) finalise sticky header height after DOMContentLoaded
			window.addEventListener( 'load', function() {
				requestAnimationFrame( function() {
					aside.style.setProperty( '--ntb-nav-offset', getNavOffset() + 'px' );
					updateHeaderVisible && updateHeaderVisible();
				} );
			} );
			// Re-evaluate on scroll: some themes apply position:fixed to nav only after scroll starts
			var rafPending = false;
			window.addEventListener( 'scroll', function() {
				if ( rafPending ) return;
				rafPending = true;
				requestAnimationFrame( function() {
					rafPending = false;
					aside.style.setProperty( '--ntb-nav-offset', getNavOffset() + 'px' );
				} );
			}, { passive: true } );
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initStep1();
		initFlow();
		// requestAnimationFrame guarantees theme JS (sticky headers) has run before we measure nav height
		requestAnimationFrame( function() {
			initAside();
		} );
		killOutlines();
	} );
} )();
