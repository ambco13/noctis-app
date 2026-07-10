/* NOCTIS — date (flatpickr) + time (two-column picker) v1.6.1 */
(function () {
	'use strict';

	if ( typeof flatpickr === 'undefined' ) return;

	var fr = ( flatpickr.l10ns && flatpickr.l10ns.fr ) ? flatpickr.l10ns.fr : {};
	var chevPrev = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
	var chevNext = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';

	var MONTHS_FULL  = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
	var MONTHS_SHORT = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];

	document.addEventListener( 'DOMContentLoaded', function () {

		/* ── Date — flatpickr avec saisie clavier ──────────────── */
		var dateEl = document.getElementById( 'ntb-date' );
		if ( dateEl ) {
			flatpickr( dateEl, {
				dateFormat:    'Y-m-d',
				altInput:      true,
				altFormat:     'd/m/Y',
				allowInput:    true,
				minDate:       'today',
				locale:        fr,
				disableMobile: true,
				prevArrow:     chevPrev,
				nextArrow:     chevNext,
				onReady: function ( _d, _s, inst ) {
					inst.altInput.id = inst.element.id;
					inst.element.removeAttribute( 'id' );
					inst.altInput.placeholder = 'jj/mm/aaaa';
					inst.altInput.setAttribute( 'autocomplete', 'off' );
					/* Supprimer le pré-rendu PHP — altInput flatpickr prend le relais */
					var pre = inst.altInput.parentNode && inst.altInput.parentNode.querySelector( '.ntb-date-pre' );
					if ( pre ) pre.parentNode.removeChild( pre );
					syncCalendarTheme( dateEl, inst );
					enhanceFlatpickrPickers( inst );
				},
				onOpen: function ( _d, _s, inst ) {
					/* Le calendrier vit dans <body>, hors .ntb-scope : on lui recopie
					   les couleurs résolues du scope pour qu'il suive le design. */
					syncCalendarTheme( dateEl, inst );
					updateHeroFocus();
					/* La largeur du calendrier = largeur du champ date.
					   La grille s'adapte via CSS (flex-basis 100%/7). */
					requestAnimationFrame( function () {
						var cal       = inst.calendarContainer;
						var dateField = dateEl.closest( '.ntb-field' ) || dateEl.parentElement;
						var dateRect  = dateField.getBoundingClientRect();
						var vw        = window.innerWidth;
						var gap       = 8;
						var newW    = Math.max( Math.round( dateRect.width ), 220 );
						var leftPos = Math.round( dateRect.left + window.scrollX );

						/* Anti-débordement droit */
						if ( leftPos + newW > window.scrollX + vw - gap ) {
							leftPos = Math.max( window.scrollX + gap, window.scrollX + vw - newW - gap );
						}
						/* Anti-débordement gauche */
						if ( leftPos < window.scrollX + gap ) {
							leftPos = window.scrollX + gap;
						}

						cal.style.width    = newW + 'px';
						cal.style.minWidth = newW + 'px';
						cal.style.left     = leftPos + 'px';
					} );
				},
				onClose: function () {
					updateHeroFocus();
				},
			} );
		}

		/* ── Heure — rouleau deux colonnes ─────────────────────── */
		var timeEl = document.getElementById( 'ntb-time' );
		if ( timeEl ) { initTimePicker( timeEl ); }

	} );

	/* Recopie les variables --ntb-* résolues du .ntb-scope sur le calendrier
	   (injecté dans <body>, il n'en hérite pas naturellement). */
	function syncCalendarTheme( fieldEl, inst ) {
		var scope = fieldEl.closest( '.ntb-scope' );
		var cal   = inst.calendarContainer;
		if ( ! scope || ! cal ) return;
		var cs = getComputedStyle( scope );
		[
			'--ntb-surf', '--ntb-surf2', '--ntb-bg2', '--ntb-line', '--ntb-line-soft',
			'--ntb-text', '--ntb-muted', '--ntb-faint',
			'--ntb-accent', '--ntb-accent-hi', '--ntb-accent-soft',
			'--ntb-serif', '--ntb-sans', '--ntb-mono',
			'--ntb-r', '--ntb-r-sm', '--ntb-dur', '--ntb-ease',
			'--ntb-surf-glass', '--ntb-glass-blur'
		].forEach( function ( v ) {
			var val = cs.getPropertyValue( v );
			if ( val ) cal.style.setProperty( v, val );
		} );
		/* Le calendrier est déplacé dans <body> par flatpickr : un sélecteur
		   CSS ".ntb-home .flatpickr-calendar" ne peut jamais le cibler (il
		   sort du DOM de .ntb-home). Sur le hero, effet verre appliqué
		   directement ici plutôt que par CSS. */
		if ( fieldEl.closest( '.ntb-home' ) ) {
			cal.style.setProperty( 'background', 'var(--ntb-surf-glass)' );
			cal.style.setProperty( 'backdrop-filter', 'var(--ntb-glass-blur, blur(8px))' );
			cal.style.setProperty( '-webkit-backdrop-filter', 'var(--ntb-glass-blur, blur(8px))' );
		}
	}

	/* Ouvre vers le haut (classe .ntb-pop-up) si la place manque en dessous
	   -- utile quand le champ est proche du bas de l'écran (ex. hero page 1,
	   formulaire ancré en bas du viewport). */
	function positionPopupVertically( anchor, popup ) {
		popup.classList.remove( 'ntb-pop-up' );
		var rect = anchor.getBoundingClientRect();
		var popupHeight = popup.offsetHeight;
		var spaceBelow = window.innerHeight - rect.bottom;
		var spaceAbove = rect.top;
		if ( spaceBelow < popupHeight + 12 && spaceAbove > spaceBelow ) {
			popup.classList.add( 'ntb-pop-up' );
		}
	}

	/* Hero (page 1) : le bloc texte+formulaire est ancré en bas de l'écran.
	   Dès qu'un popup s'ouvre, on mesure de combien remonter tout le bloc
	   (texte + formulaire, déplacés ensemble) pour amener le CENTRE DU
	   FORMULAIRE (pas le bloc entier -- le titre est trop haut pour que
	   centrer le bloc entier place réellement le formulaire au milieu) au
	   centre exact de .ntb-home. Le titre suit ; il peut remonter dans la
	   zone (vide) de la nav, c'est assumé. Seul garde-fou : ne pas faire
	   sortir le haut du bloc hors de l'écran (écrans très courts). La mesure
	   se fait uniquement au front montant (pas encore focused) : une fois
	   remonté, sa position n'est plus la position de repos. */
	function updateHeroFocus() {
		var hero = document.querySelector( '.ntb-home' );
		if ( ! hero ) return;
		var open = document.querySelector( '.ntb-ac-list:not([hidden])' )
			|| document.querySelector( '.nc-time-picker.nc-open' )
			|| document.querySelector( '.flatpickr-calendar.open' );
		if ( open && ! hero.classList.contains( 'ntb-form-focused' ) ) {
			var inner = hero.querySelector( '.ntb-hero-inner' );
			var form  = hero.querySelector( '.ntb-home-form' );
			if ( inner && form ) {
				var heroRect = hero.getBoundingClientRect();
				var innerRect = inner.getBoundingClientRect();
				var formRect = form.getBoundingClientRect();
				var heroCenter = heroRect.top + heroRect.height / 2;
				var formCenter = formRect.top + formRect.height / 2;
				var lift = Math.max( 0, formCenter - heroCenter );
				// Garde-fou : garder au moins 8px du bloc visibles en haut.
				lift = Math.min( lift, Math.max( 0, innerRect.top - 8 ) );
				hero.style.setProperty( '--ntb-hero-lift', lift + 'px' );
			}
		}
		hero.classList.toggle( 'ntb-form-focused', !! open );
	}

	/* ═══════════════════════════════════════════════════════════
	   CUSTOM MONTH + YEAR PICKERS (overlays sur le calendrier)
	══════════════════════════════════════════════════════════════ */
	function enhanceFlatpickrPickers( inst ) {
		var cal = inst.calendarContainer;

		/* ── Remplacer .cur-month par un bouton cliquable ──────── */
		var curMonthEl  = cal.querySelector( '.cur-month' );
		var monthSelect = cal.querySelector( '.flatpickr-monthDropdown-months' );
		var monthBtn    = document.createElement( 'span' );
		monthBtn.className = 'nc-cal-month-btn';

		if ( curMonthEl ) {
			curMonthEl.style.display = 'none';
			curMonthEl.parentNode.insertBefore( monthBtn, curMonthEl );
		} else if ( monthSelect ) {
			monthSelect.style.display = 'none';
			monthSelect.parentNode.insertBefore( monthBtn, monthSelect );
		}

		/* ── Remplacer .cur-year par un bouton cliquable ───────── */
		var yearInput  = cal.querySelector( '.cur-year' );
		var numWrapper = cal.querySelector( '.numInputWrapper' );
		var yearBtn    = document.createElement( 'span' );
		yearBtn.className = 'nc-cal-year-btn';

		if ( numWrapper ) {
			numWrapper.querySelectorAll( '.arrowUp, .arrowDown' ).forEach( function (a) { a.style.display = 'none'; } );
			numWrapper.insertBefore( yearBtn, numWrapper.firstChild );
		}
		if ( yearInput ) {
			yearInput.style.cssText = 'position:absolute;opacity:0;pointer-events:none;width:0;';
		}

		/* ── Overlay grille de mois ────────────────────────────── */
		var monthOverlay = document.createElement( 'div' );
		monthOverlay.className = 'nc-cal-overlay nc-cal-month-overlay';
		MONTHS_SHORT.forEach( function ( name, i ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'nc-cal-mitem';
			b.textContent = name;
			b.dataset.m = i;
			monthOverlay.appendChild( b );
		} );
		cal.appendChild( monthOverlay );

		/* ── Overlay liste d'années (rouleau snap) ─────────────── */
		var yearOverlay = document.createElement( 'div' );
		yearOverlay.className = 'nc-cal-overlay nc-cal-year-overlay';

		var yWrap = document.createElement( 'div' );
		yWrap.className = 'nc-cal-ylist-wrap';
		var yList = document.createElement( 'div' );
		yList.className = 'nc-cal-ylist';
		yWrap.appendChild( yList );
		yearOverlay.appendChild( yWrap );
		cal.appendChild( yearOverlay );

		var baseYear = new Date().getFullYear();
		for ( var y = baseYear; y <= baseYear + 6; y++ ) {
			var yi = document.createElement( 'div' );
			yi.className = 'nc-cal-yitem';
			yi.textContent = y;
			yi.dataset.y = y;
			yList.appendChild( yi );
		}

		/* ── Positionner les overlays sous la barre des mois ───── */
		function positionOverlays() {
			var bar = cal.querySelector( '.flatpickr-months' );
			if ( ! bar ) return;
			var top = bar.offsetTop + bar.offsetHeight;
			monthOverlay.style.top = top + 'px';
			yearOverlay.style.top  = top + 'px';
		}

		/* ── Mettre à jour les boutons et le highlighting ─────── */
		function syncDisplay() {
			monthBtn.textContent = MONTHS_FULL[ inst.currentMonth ];
			yearBtn.textContent  = inst.currentYear;

			monthOverlay.querySelectorAll( '.nc-cal-mitem' ).forEach( function (b) {
				b.classList.toggle( 'nc-on', +b.dataset.m === inst.currentMonth );
			} );
			yList.querySelectorAll( '.nc-cal-yitem' ).forEach( function (b) {
				b.classList.toggle( 'nc-on', +b.dataset.y === inst.currentYear );
			} );
		}

		function closeOverlays() {
			monthOverlay.classList.remove( 'nc-open' );
			yearOverlay.classList.remove( 'nc-open' );
		}

		/* ── Ouvrir grille de mois ─────────────────────────────── */
		monthBtn.addEventListener( 'click', function (e) {
			e.stopPropagation();
			var was = monthOverlay.classList.contains( 'nc-open' );
			closeOverlays();
			if ( ! was ) {
				positionOverlays();
				syncDisplay();
				monthOverlay.classList.add( 'nc-open' );
			}
		} );

		/* ── Ouvrir liste d'années ─────────────────────────────── */
		yearBtn.addEventListener( 'click', function (e) {
			e.stopPropagation();
			var was = yearOverlay.classList.contains( 'nc-open' );
			closeOverlays();
			if ( ! was ) {
				positionOverlays();
				syncDisplay();
				yearOverlay.classList.add( 'nc-open' );
				/* Centrer sur l'année courante */
				setTimeout( function () {
					var active = yList.querySelector( '.nc-cal-yitem.nc-on' );
					if ( active ) {
						yList.scrollTop = active.offsetTop - yList.clientHeight / 2 + active.clientHeight / 2;
					}
				}, 0 );
			}
		} );

		/* ── Sélectionner un mois ──────────────────────────────── */
		monthOverlay.addEventListener( 'click', function (e) {
			var b = e.target.closest( '.nc-cal-mitem' );
			if ( ! b ) return;
			inst.jumpToDate( new Date( inst.currentYear, +b.dataset.m, 1 ) );
			closeOverlays();
			syncDisplay();
		} );

		/* ── Sélectionner une année (clic) ─────────────────────── */
		yList.addEventListener( 'click', function (e) {
			var item = e.target.closest( '.nc-cal-yitem' );
			if ( ! item ) return;
			inst.jumpToDate( new Date( +item.dataset.y, inst.currentMonth, 1 ) );
			closeOverlays();
			syncDisplay();
		} );

		/* ── Sélectionner une année (scroll snap) ──────────────── */
		yList.addEventListener( 'scroll', function () {
			clearTimeout( yList._st );
			yList._st = setTimeout( function () {
				var itemH = 44;
				var i     = Math.round( yList.scrollTop / itemH );
				var items = yList.querySelectorAll( '.nc-cal-yitem' );
				if ( items[ i ] ) {
					inst.jumpToDate( new Date( +items[ i ].dataset.y, inst.currentMonth, 1 ) );
					syncDisplay();
				}
			}, 80 );
		} );

		/* ── Fermer sur clic extérieur ─────────────────────────── */
		document.addEventListener( 'click', function (e) {
			if ( ! cal.contains( e.target ) ) closeOverlays();
		} );

		/* ── Synchro quand l'utilisateur clique les flèches ────── */
		var prevBtn = cal.querySelector( '.flatpickr-prev-month' );
		var nextBtn = cal.querySelector( '.flatpickr-next-month' );
		if ( prevBtn ) prevBtn.addEventListener( 'click', function () { setTimeout( syncDisplay, 0 ); } );
		if ( nextBtn ) nextBtn.addEventListener( 'click', function () { setTimeout( syncDisplay, 0 ); } );

		/* Init initial */
		syncDisplay();
	}

	/* ═══════════════════════════════════════════════════════════
	   ROULEAU HEURE — deux colonnes snap
	══════════════════════════════════════════════════════════════ */
	function initTimePicker( input ) {
		var wrapper = input.closest( '.ntb-field-input' );
		var field   = input.closest( '.ntb-field' );
		if ( ! wrapper || ! field ) return;

		/* Masquer le natif (valeur conservée pour le submit) */
		input.style.cssText = 'position:absolute;opacity:0;pointer-events:none;width:0;height:0;';

		/* Input d'affichage / saisie — réutilise le pré-rendu PHP si présent */
		var display = wrapper.querySelector( '.nc-time-display' );
		if ( ! display ) {
			display = document.createElement( 'input' );
			display.type        = 'text';
			display.className   = 'nc-time-display';
			display.placeholder = '--:--';
			display.maxLength   = 5;
			wrapper.appendChild( display );
		}
		display.id = input.id;
		input.removeAttribute( 'id' );

		/* Popup deux colonnes */
		var picker = document.createElement( 'div' );
		picker.className = 'nc-time-picker';

		var colH = makeCol( 24 );
		var sep  = document.createElement( 'div' );
		sep.className   = 'nc-time-sep';
		sep.textContent = ':';
		var colM = makeCol( 12, 5 );

		picker.appendChild( colH );
		picker.appendChild( sep );
		picker.appendChild( colM );
		field.style.position = 'relative';
		field.appendChild( picker );

		var curH = -1, curM = -1;

		/* Lire la valeur initiale (PHP session ou retour navigateur) */
		if ( input.value ) {
			var _p = input.value.split( ':' );
			var _h = parseInt( _p[0], 10 );
			var _m = parseInt( _p[1], 10 );
			if ( _h >= 0 && _h <= 23 && _m >= 0 && _m <= 59 ) {
				curH = _h; curM = snap5( _m );
			}
		}

		function select( h, m, scroll ) {
			curH = h; curM = m;
			input.value   = pad( h ) + ':' + pad( m );
			display.value = pad( h ) + ':' + pad( m );
			highlight( colH, h );
			highlight( colM, m );
			if ( scroll ) {
				scrollToActive( colH );
				scrollToActive( colM );
			}
		}

		function highlight( col, val ) {
			col.querySelectorAll( '.nc-ti' ).forEach( function (el) {
				el.classList.toggle( 'nc-ti-on', +el.dataset.v === val );
			} );
		}

		function scrollToActive( col ) {
			var a = col.querySelector( '.nc-ti-on' );
			if ( a ) col.scrollTo( { top: a.offsetTop - col.clientHeight / 2 + a.clientHeight / 2, behavior: 'smooth' } );
		}

		function scrollInstant( col, idx ) {
			col.scrollTop = idx * 36;
		}

		/* Afficher la valeur initiale si elle existe */
		if ( curH >= 0 && curM >= 0 ) {
			select( curH, curM, false );
		}

		/* Clics sur les items */
		[ colH, colM ].forEach( function (col, idx) {
			col.addEventListener( 'click', function (e) {
				var el = e.target.closest( '.nc-ti' );
				if ( ! el ) return;
				if ( idx === 0 ) select( +el.dataset.v, curM < 0 ? 0 : curM, false );
				else             select( curH < 0 ? 0 : curH, +el.dataset.v, false );
				scrollToActive( col );
			} );
		} );

		/* Auto-sélection au scroll */
		[ colH, colM ].forEach( function (col, idx) {
			col.addEventListener( 'scroll', function () {
				clearTimeout( col._st );
				col._st = setTimeout( function () {
					var itemH = 36;
					var i   = Math.round( col.scrollTop / itemH );
					var max = idx === 0 ? 23 : 11;
					i = Math.max( 0, Math.min( i, max ) );
					if ( idx === 0 ) select( i, curM < 0 ? 0 : curM, false );
					else             select( curH < 0 ? 0 : curH, i * 5, false );
				}, 80 );
			} );
		} );

		/* Ouverture au clic sur l'input */
		display.addEventListener( 'click', function () {
			var isOpen = picker.classList.contains( 'nc-open' );
			closeAll();
			if ( ! isOpen ) {
				picker.classList.add( 'nc-open' );
				positionPopupVertically( field, picker );
				var h0 = curH >= 0 ? curH : 0;
				var m0 = curM >= 0 ? curM : 0;
				scrollInstant( colH, h0 );
				scrollInstant( colM, m0 / 5 );
			}
			updateHeroFocus();
		} );

		/* Saisie clavier — auto-insère ":" après les 2 premiers chiffres */
		display.addEventListener( 'input', function () {
			var digits = display.value.replace( /[^0-9]/g, '' ).slice( 0, 4 );
			var formatted = digits.length >= 3
				? digits.slice( 0, 2 ) + ':' + digits.slice( 2 )
				: digits;
			display.value = formatted;
			var m = formatted.match( /^(\d{1,2}):(\d{2})$/ );
			if ( m ) {
				var h2 = +m[1], min = +m[2];
				if ( h2 > 23 ) h2 = 23;
				if ( min > 59 ) min = 55;
				select( h2, snap5( min ), picker.classList.contains( 'nc-open' ) );
			}
		} );

		/* Au départ du champ : compléter "12" → "12:00", "9" → "09:00" */
		display.addEventListener( 'blur', function () {
			var digits = display.value.replace( /[^0-9]/g, '' ).slice( 0, 4 );
			if ( ! digits ) return;
			/* Compléter les minutes manquantes */
			if ( digits.length <= 2 ) digits = digits + '00';
			var h2 = +digits.slice( 0, 2 );
			var m2 = +digits.slice( 2, 4 );
			if ( h2 > 23 ) h2 = 23;
			if ( m2 > 59 ) m2 = 55;
			select( h2, snap5( m2 ), false );
		} );

		/* Fermeture hors clic */
		document.addEventListener( 'click', function (e) {
			if ( ! field.contains( e.target ) ) {
				picker.classList.remove( 'nc-open' );
				updateHeroFocus();
			}
		} );
	}

	function makeCol( count, step ) {
		step = step || 1;
		var col = document.createElement( 'div' );
		col.className = 'nc-time-col';
		for ( var i = 0; i < count; i++ ) {
			var v  = i * step;
			var el = document.createElement( 'div' );
			el.className   = 'nc-ti';
			el.textContent = pad( v );
			el.dataset.v   = v;
			col.appendChild( el );
		}
		return col;
	}

	/* Arrondit les minutes au multiple de 5 le plus proche (plafonné à 55). */
	function snap5( m ) {
		return Math.min( 55, Math.round( m / 5 ) * 5 );
	}

	function closeAll() {
		document.querySelectorAll( '.nc-time-picker.nc-open' ).forEach( function (p) {
			p.classList.remove( 'nc-open' );
		} );
	}

	function pad( n ) { return ( '0' + n ).slice( -2 ); }

}() );
