/**
 * YGB Bot — widget de chat (JS puro, sin jQuery ni dependencias).
 *
 * Lazy load opcional: si settings.lazyLoad es true, la ventana solo se
 * inicializa cuando el usuario abre el chat por primera vez.
 */
/* global ygbBotConfig */
(function () {
	'use strict';

	var CFG = window.ygbBotConfig || {};
	var S = CFG.settings || {};
	var I18N = (S && S.i18n) || {};

	function $(sel, ctx) { return (ctx || document).querySelector(sel); }
	function $all(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

	function uuid() {
		if (window.crypto && crypto.randomUUID) { return crypto.randomUUID().replace(/-/g, ''); }
		var s = '';
		for (var i = 0; i < 32; i++) {
			s += Math.floor(Math.random() * 16).toString(16);
		}
		return s;
	}

	function esc(str) {
		var d = document.createElement('div');
		d.textContent = str == null ? '' : String(str);
		return d.innerHTML;
	}

	function timeNow() {
		try {
			return new Date().toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
		} catch (e) {
			return '';
		}
	}

	function post(action, data) {
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', CFG.nonce);
		Object.keys(data || {}).forEach(function (k) {
			if (data[k] !== undefined && data[k] !== null) { body.append(k, data[k]); }
		});
		return fetch(CFG.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (r) { return r.json(); });
	}

	/* ------------------------------------------------------------------ */
	/* Widget instance                                                     */
	/* ------------------------------------------------------------------ */
	function YgbWidget(root) {
		this.root = root;
		this.inline = root.getAttribute('data-ygb-instance') === 'inline';
		this.bubble = $('.ygb-bubble', root);
		this.win = $('.ygb-window', root);
		this.msgs = $('.ygb-messages', root);
		this.quick = $('.ygb-quick', root);
		this.form = $('.ygb-input-row', root);
		this.input = $('.ygb-input', root);
		this.consentBox = $('.ygb-consent', root);
		this.supportBtn = $('.ygb-support-persist', root);

		this.session = this.loadSession();
		this.convId = 0;
		this.fails = 0;
		this.started = false;
		this.history = []; // transcript local [{emisor,mensaje}]

		this.bind();
		if (!this.inline) { this.applyBubblePlacement(); }
		if (!this.inline && S.lazyLoad === false) { /* nada que precargar */ }
		if (this.inline) { this.open(); }
	}

	/* Aplica tamaño y desplazamiento de la burbuja (escritorio/móvil, breakpoint 768px) */
	YgbWidget.prototype.applyBubblePlacement = function () {
		var self = this;
		function apply() {
			var mobile = window.matchMedia && window.matchMedia('(max-width:768px)').matches;
			var size   = mobile ? S.bubbleSizeMobile : S.bubbleSize;
			var offX   = mobile ? S.bubbleOffsetXMobile : S.bubbleOffsetX;
			var offY   = mobile ? S.bubbleOffsetYMobile : S.bubbleOffsetY;
			if (size == null || isNaN(size)) { return; }
			self.root.style.setProperty('--ygb-bubble-size', parseInt(size, 10) + 'px');
			self.root.style.setProperty('--ygb-bubble-offset-x', (isNaN(offX) ? 20 : parseInt(offX, 10)) + 'px');
			self.root.style.setProperty('--ygb-bubble-offset-y', (isNaN(offY) ? 20 : parseInt(offY, 10)) + 'px');
		}
		apply();
		if (window.matchMedia) {
			var mq = window.matchMedia('(max-width:768px)');
			if (mq.addEventListener) { mq.addEventListener('change', apply); }
			else if (mq.addListener) { mq.addListener(apply); }
		} else {
			window.addEventListener('resize', apply);
		}
	};

	YgbWidget.prototype.loadSession = function () {
		var id = '';
		try { id = localStorage.getItem('ygb_session') || ''; } catch (e) { /* noop */ }
		if (!/^[a-f0-9-]{8,64}$/i.test(id)) {
			id = uuid();
			try { localStorage.setItem('ygb_session', id); } catch (e) { /* noop */ }
		}
		return id;
	};

	YgbWidget.prototype.bind = function () {
		var self = this;
		if (this.bubble) {
			this.bubble.addEventListener('click', function () { self.open(); });
		}
		var min = $('.ygb-minimize', this.root);
		if (min) {
			min.addEventListener('click', function () { self.close(); });
		}
		document.addEventListener('keydown', function (ev) {
			if (ev.key === 'Escape' && !self.win.hidden) { self.close(); }
		});
		if (this.form) {
			this.form.addEventListener('submit', function (ev) {
				ev.preventDefault();
				self.send(self.input.value.trim());
			});
		}
		if (this.supportBtn) {
			this.supportBtn.addEventListener('click', function () { self.showSupport(); });
		}
		var ok = $('.ygb-consent-ok', this.root);
		var no = $('.ygb-consent-no', this.root);
		if (ok) {
			ok.addEventListener('click', function () {
				try { localStorage.setItem('ygb_consent', '1'); } catch (e) { /* noop */ }
				self.consentBox.hidden = true;
				self.start();
			});
		}
		if (no) {
			no.addEventListener('click', function () {
				try { localStorage.setItem('ygb_consent', '0'); } catch (e) { /* noop */ }
				self.consentBox.hidden = true;
				self.addMsg('bot', '<p>' + esc(I18N.consentDecline || '') + '</p>');
				self.start();
			});
		}
	};

	YgbWidget.prototype.open = function () {
		this.win.hidden = false;
		if (this.bubble) { this.bubble.classList.add('ygb-hidden'); }
		if (this.root.parentElement) {
			this.root.setAttribute('aria-expanded', 'true');
		}
		var self = this;
		if (!this.started) {
			this.checkConsentThenStart();
		} else if (this.history.length === 0) {
			this.restoreHistory();
		}
		setTimeout(function () { self.input.focus(); }, 50);
	};

	YgbWidget.prototype.close = function () {
		this.win.hidden = true;
		if (this.bubble) {
			this.bubble.classList.remove('ygb-hidden');
			this.bubble.focus();
		}
	};

	YgbWidget.prototype.checkConsentThenStart = function () {
		var stored = null;
		try { stored = localStorage.getItem('ygb_consent'); } catch (e) { /* noop */ }
		if (S.requireConsent && S.storeChats && stored === null) {
			this.consentBox.hidden = false;
			return;
		}
		this.start();
	};

	YgbWidget.prototype.start = function () {
		if (this.started) { return; }
		this.started = true;
		var self = this;

		post('ygb_start', { session: this.session }).then(function (res) {
			if (!res || !res.success) { self.showError(); return; }
			self.convId = res.data.conv_id || 0;
			self.session = res.data.session || self.session;
			self.renderWelcome();
			self.renderQuick(res.data);
			self.restoreHistory();
		}).catch(function () { self.showError(); });
	};

	YgbWidget.prototype.renderWelcome = function () {
		this.addMsg('bot', '<p>' + esc(S.welcome || 'Hola') + '</p>', true);
	};

	YgbWidget.prototype.renderQuick = function (data) {
		var self = this;
		var html = '';
		if (S.showTopics && data.temas && data.temas.length) {
			html += '<span class="ygb-quick-label">' + esc(I18N.topicsTitle) + '</span>';
			data.temas.forEach(function (t) {
				html += '<button type="button" class="ygb-chip ygb-chip-topic" data-id="' + t.id + '">' +
					esc(t.icono ? t.icono + ' ' : '') + esc(t.nombre) + '</button>';
			});
		}
		if (S.showFaq && data.faq && data.faq.length) {
			html += '<span class="ygb-quick-label">' + esc(I18N.faqTitle) + '</span>';
			data.faq.forEach(function (f) {
				html += '<button type="button" class="ygb-chip ygb-chip-faq" data-id="' + f.id + '">' +
					esc(f.pregunta) + '</button>';
			});
		}
		this.quick.innerHTML = html;
		this.quick.hidden = !html;
		$all('.ygb-chip-topic,.ygb-chip-faq', this.quick).forEach(function (btn) {
			btn.addEventListener('click', function () {
				self.ask(null, parseInt(btn.getAttribute('data-id'), 10));
			});
		});
	};

	YgbWidget.prototype.addMsg = function (who, html, skipSave) {
		var wrap = document.createElement('div');
		wrap.className = 'ygb-msg ygb-msg-' + who;
		var avatar = who === 'bot'
			? '<span class="ygb-msg-avatar" aria-hidden="true">' + esc(S.avatar || '🤖') + '</span>'
			: '<span class="ygb-msg-avatar" aria-hidden="true">🙂</span>';
		wrap.innerHTML = avatar + '<div class="ygb-bubble-body">' + html +
			'<span class="ygb-msg-time">' + timeNow() + '</span></div>';
		this.msgs.appendChild(wrap);
		this.msgs.scrollTop = this.msgs.scrollHeight;

		if (!skipSave) {
			this.history.push({ emisor: who, mensaje: html.replace(/<[^>]+>/g, ' ') });
			this.saveHistory();
		}
		return wrap;
	};

	YgbWidget.prototype.showError = function () {
		this.addMsg('bot', '<p>' + esc(I18N.error || 'Error') + '</p>', true);
	};

	YgbWidget.prototype.transcript = function () {
		return this.history.map(function (m) {
			return (m.emisor === 'bot' ? (S.botName || 'Bot') : 'Usuario') + ': ' + m.mensaje;
		}).join('\n').slice(0, 4000);
	};

	YgbWidget.prototype.saveHistory = function () {
		if (!S.saveHistory) { return; }
		try {
			localStorage.setItem('ygb_history_' + this.session, JSON.stringify(this.history.slice(-60)));
		} catch (e) { /* noop */ }
	};

	YgbWidget.prototype.restoreHistory = function () {
		if (!S.saveHistory || this.msgs.children.length > 1) { return; }
		var raw = null;
		try { raw = localStorage.getItem('ygb_history_' + this.session); } catch (e) { /* noop */ }
		if (!raw) { return; }
		try {
			var list = JSON.parse(raw);
			var self = this;
			list.forEach(function (m) {
				self.addMsg(m.emisor, '<p>' + esc(m.mensaje) + '</p>', true);
			});
		} catch (e) { /* noop */ }
	};

	YgbWidget.prototype.showTyping = function () {
		var el = document.createElement('div');
		el.className = 'ygb-typing';
		el.setAttribute('aria-live', 'polite');
		el.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>' +
			'<span>' + esc(I18N.typing || 'escribiendo…') + '</span>';
		this.msgs.appendChild(el);
		this.msgs.scrollTop = this.msgs.scrollHeight;
		return el;
	};

	/* Enviar pregunta (texto libre o por question_id) */
	YgbWidget.prototype.ask = function (text, questionId) {
		var self = this;
		if (text) {
			this.input.value = '';
			this.addMsg('user', '<p>' + esc(text) + '</p>');
		} else if (questionId) {
			this.history.push({ emisor: 'user', mensaje: '#' + questionId });
		}

		var typing = this.showTyping();
		var delay = text ? 500 + Math.min(1200, text.length * 15) : 350;

		setTimeout(function () {
			post('ygb_chat', {
				message: text || '',
				question_id: questionId || 0,
				conv_id: self.convId,
				fails: self.fails,
				transcript: self.transcript()
			}).then(function (res) {
				typing.remove();
				if (!res || !res.success) { self.showError(); return; }
				self.handleResponse(res.data);
			}).catch(function () { typing.remove(); self.showError(); });
		}, delay);
	};

	YgbWidget.prototype.send = function (text) {
		if (!text) { return; }
		this.ask(text, 0);
	};

	YgbWidget.prototype.handleResponse = function (data) {
		var self = this;
		if (data.matched) {
			this.fails = 0;
			this.addMsg('bot', data.answer || '');
			this.clearSuggestions();
			return;
		}

		// Fallback / derivación.
		this.fails = data.fails || (this.fails + 1);
		this.addMsg('bot', '<p>' + esc(data.message || '') + '</p>');

		// Sugerencias "¿quisiste decir?".
		this.clearSuggestions();
		if (data.suggestions && data.suggestions.length) {
			var html = '<span class="ygb-quick-label">' + esc(I18N.suggestTitle) + '</span>';
			data.suggestions.forEach(function (s) {
				html += '<button type="button" class="ygb-chip ygb-sugg" data-id="' + s.id + '">' + esc(s.pregunta) + '</button>';
			});
			var box = document.createElement('div');
			box.className = 'ygb-quick ygb-suggest-box';
			box.innerHTML = html;
			this.msgs.parentNode.insertBefore(box, this.msgs.nextSibling);
			$all('.ygb-sugg', box).forEach(function (b) {
				b.addEventListener('click', function () {
					self.ask(null, parseInt(b.getAttribute('data-id'), 10));
				});
			});
		}

		// Canales de soporte.
		if (data.channels && data.channels.length) {
			this.renderChannels(data.channels);
		}
	};

	YgbWidget.prototype.clearSuggestions = function () {
		$all('.ygb-suggest-box', this.root).forEach(function (el) { el.remove(); });
	};

	YgbWidget.prototype.renderChannels = function (channels) {
		var self = this;
		this.clearSuggestions();
		var box = document.createElement('div');
		box.className = 'ygb-quick ygb-suggest-box';
		box.innerHTML = '<span class="ygb-quick-label">' + esc(I18N.support || 'Soporte') + '</span>';
		channels.forEach(function (c) {
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'ygb-chip';
			b.textContent = c.text;
			b.addEventListener('click', function () { self.derive(c); });
			box.appendChild(b);
		});
		this.msgs.parentNode.insertBefore(box, this.msgs.nextSibling);
	};

	YgbWidget.prototype.showSupport = function () {
		var self = this;
		post('ygb_chat', {
			message: 'agente de soporte',
			conv_id: this.convId,
			fails: this.fails,
			transcript: this.transcript()
		}).then(function (res) {
			if (res && res.success && res.data.channels) {
				self.addMsg('bot', '<p>' + esc(res.data.message || '') + '</p>');
				self.renderChannels(res.data.channels);
			}
		});
	};

	YgbWidget.prototype.derive = function (channel) {
		var self = this;
		var lastUser = '';
		for (var i = this.history.length - 1; i >= 0; i--) {
			if (this.history[i].emisor === 'user') { lastUser = this.history[i].mensaje; break; }
		}
		post('ygb_derive', {
			canal: channel.id,
			conv_id: this.convId,
			message: lastUser.slice(0, 500),
			transcript: this.transcript()
		}).then(function (res) {
			if (!res || !res.success) { self.showError(); return; }
			if (res.data.ticket) {
				self.addMsg('bot', '<p>' + esc(res.data.message) + '</p>');
			} else if (res.data.url) {
				window.open(res.data.url, '_blank', 'noopener');
				self.addMsg('bot', '<p>' + esc(I18N.okSent || '¡Listo! Te hemos abierto el canal elegido.') + '</p>');
			}
		});
	};

	/* ------------------------------------------------------------------ */
	/* Bootstrap                                                           */
	/* ------------------------------------------------------------------ */
	function init() {
		var instances = $all('[data-ygb-instance]');
		if (!instances.length) { return; }

		// Si hay instancias flotantes duplicadas (autoload + shortcode),
		// conservar solo la inline y una flotante.
		var floating = instances.filter(function (el) {
			return el.getAttribute('data-ygb-instance') === 'floating';
		});
		var inline = instances.filter(function (el) {
			return el.getAttribute('data-ygb-instance') === 'inline';
		});
		var keep = inline.concat(floating.length ? [floating[0]] : []);
		instances.forEach(function (el) {
			if (keep.indexOf(el) === -1) { el.remove(); }
		});

		keep.forEach(function (el) { new YgbWidget(el); });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
