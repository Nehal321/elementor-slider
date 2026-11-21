(() => {
	'use strict';

	const name = 'feather-slider';
	if (customElements.get(name)) {
		return;
	}

	class Slider {
		constructor(root, track, slides) {
			this.root = root;
			this.track = track;
			this.slides = slides;
			this.previous = root.querySelector('.fs-previous');
			this.next = root.querySelector('.fs-next');
			this.dots = [...root.querySelectorAll('.fs-dot')];
			this.playback = root.querySelector('.fs-playback');
			this.status = root.querySelector('.fs-status');
			this.motion = matchMedia('(prefers-reduced-motion: reduce)');
			this.events = new AbortController();
			this.index = Math.max(0, slides.findIndex((slide) => !slide.hidden));
			this.delay = Number(root.dataset.delay);
			this.duration = Number(root.dataset.duration);
			this.loop = root.dataset.loop === '1';
			this.playing = root.dataset.autoplay === '1';
			this.pauseOnHover = root.dataset.pauseHover === '1';
			this.hovered = this.pauseOnHover && root.matches(':hover');

			this.revealControls();
			this.bindEvents();
			this.updateControls();
			this.schedule();
		}

		revealControls() {
			if (this.previous && this.next) {
				this.root.dataset.hasArrows = '';
				this.previous.hidden = false;
				this.next.hidden = false;
			}
			if (this.dots.length) {
				this.root.dataset.hasDots = '';
				this.dots[0].parentElement.hidden = false;
			}
		}

		bindEvents() {
			const options = { signal: this.events.signal };
			this.root.addEventListener('click', (event) => this.onClick(event), options);
			this.root.addEventListener('keydown', (event) => this.onKeyDown(event), options);
			this.root.addEventListener('focusin', () => this.schedule(), options);
			this.root.addEventListener('focusout', () => queueMicrotask(() => this.schedule()), options);
			this.track.addEventListener('animationend', (event) => {
				if (this.transition?.animated === event.target && event.animationName.startsWith('fs-')) {
					this.finishTransition();
				}
			}, options);
			if (this.pauseOnHover) {
				this.root.addEventListener('mouseenter', () => {
					this.hovered = true;
					this.schedule();
				}, options);
				this.root.addEventListener('mouseleave', () => {
					this.hovered = false;
					this.schedule();
				}, options);
			}
			document.addEventListener('visibilitychange', () => this.schedule(), options);
			this.motion.addEventListener('change', () => {
				if (this.motion.matches) {
					this.finishTransition();
				}
				this.updateControls();
				this.schedule();
			}, options);
		}

		onClick(event) {
			const button = event.target.closest('button');
			if (button?.closest('feather-slider') !== this.root) {
				return;
			}
			if (button === this.previous) {
				this.goTo(this.index - 1, true);
			} else if (button === this.next) {
				this.goTo(this.index + 1, true);
			} else if (button === this.playback) {
				if (this.atEnd()) {
					this.playing = true;
					this.goTo(0, true);
				} else {
					this.playing = !this.playing;
				}
				this.updateControls();
				this.schedule();
			} else if (button.classList.contains('fs-dot')) {
				this.goTo(Number(button.dataset.index), true);
			}
		}

		onKeyDown(event) {
			if (
				event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey ||
				event.target.closest('feather-slider') !== this.root ||
				event.target.closest('input, textarea, select, [contenteditable]')
			) {
				return;
			}

			const targets = {
				ArrowLeft: this.index - 1,
				ArrowRight: this.index + 1,
				Home: 0,
				End: this.slides.length - 1,
			};
			if (Object.hasOwn(targets, event.key)) {
				event.preventDefault();
				this.goTo(targets[event.key], true);
			}
		}

		atEnd() {
			return !this.loop && this.index === this.slides.length - 1;
		}

		schedule() {
			clearTimeout(this.autoplayTimer);
			if (
				this.events.signal.aborted || !this.playing || this.atEnd() || this.motion.matches ||
				this.hovered || document.hidden || this.root.contains(document.activeElement)
			) {
				return;
			}
			this.autoplayTimer = setTimeout(() => this.goTo(this.index + 1), this.delay);
		}

		goTo(requested, announce = false) {
			const count = this.slides.length;
			const target = this.loop ? (requested + count) % count : Math.min(count - 1, Math.max(0, requested));
			if (target === this.index) {
				this.schedule();
				return;
			}

			this.finishTransition();
			const outgoing = this.slides[this.index];
			const incoming = this.slides[target];
			if (outgoing.contains(document.activeElement)) {
				this.track.focus({ preventScroll: true });
			}
			outgoing.setAttribute('aria-hidden', 'true');
			outgoing.inert = true;
			incoming.hidden = false;
			incoming.inert = false;
			incoming.removeAttribute('aria-hidden');
			this.index = target;

			if (!this.motion.matches && this.duration > 0) {
				this.startTransition(outgoing, incoming);
			} else {
				outgoing.hidden = true;
			}

			if (announce && this.status) {
				this.status.textContent = incoming.getAttribute('aria-label');
			}
			this.updateControls();
			this.schedule();
		}

		startTransition(outgoing, incoming) {
			outgoing.classList.add('fs-leave');
			incoming.classList.add('fs-enter');
			this.transition = {
				outgoing,
				incoming,
				animated: this.root.dataset.effect === 'fade-out' ? outgoing : incoming,
			};
			this.transitionTimer = setTimeout(() => this.finishTransition(), this.duration + 60);
		}

		finishTransition() {
			if (!this.transition) {
				return;
			}
			clearTimeout(this.transitionTimer);
			this.transition.outgoing.hidden = true;
			this.transition.outgoing.classList.remove('fs-leave');
			this.transition.incoming.classList.remove('fs-enter');
			this.transition = null;
		}

		updateControls() {
			this.previous?.setAttribute('aria-disabled', String(!this.loop && this.index === 0));
			this.next?.setAttribute('aria-disabled', String(this.atEnd()));
			this.dots.forEach((dot, index) => {
				if (index === this.index) {
					dot.setAttribute('aria-current', 'true');
				} else {
					dot.removeAttribute('aria-current');
				}
			});
			if (this.playback) {
				const paused = !this.playing || this.atEnd();
				this.playback.hidden = this.motion.matches;
				this.root.toggleAttribute('data-has-playback', !this.motion.matches);
				this.playback.toggleAttribute('data-paused', paused);
				this.playback.setAttribute('aria-label', paused ? this.playback.dataset.playLabel : this.playback.dataset.pauseLabel);
			}
		}

		destroy() {
			this.events.abort();
			clearTimeout(this.autoplayTimer);
			this.finishTransition();
		}
	}

	customElements.define(name, class extends HTMLElement {
		connectedCallback() {
			queueMicrotask(() => {
				if (!this.isConnected || this.slider) {
					return;
				}
				const track = this.querySelector('.fs-track');
				const slides = track ? [...track.children] : [];
				if (slides.length > 1) {
					this.slider = new Slider(this, track, slides);
				}
			});
		}

		disconnectedCallback() {
			this.slider?.destroy();
			this.slider = null;
		}
	});
})();
