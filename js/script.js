var KeepOrSweep = KeepOrSweep || {};

(function(window, OC, exports, undefined) {
	'use strict';

	var Manager = function() {
		this.filesClient = OC.Files.getClient();
		this._previewSize = this._calculatePreviewSize();
		this.scopePath = this._loadStoredScopePath();
	};

	Manager.prototype = {

		_currentIndex: 0,
		_containerBefore: 4,
		_containerCurrent: 1,
		_containerAfter: 2,
		_containerActive: '.active .element-preview',
		_previewSize: 256,
		_lastShownFileId: null,

		_calculatePreviewSize: function() {
			var shortestViewportSide = Math.min(window.innerWidth || 1024, window.innerHeight || 768);
			return Math.max(160, Math.min(320, Math.round(shortestViewportSide * 0.35)));
		},

		_loadStoredScopePath: function() {
			try {
				return (window.localStorage.getItem('keeporsweep.scopePath') || '').trim();
			} catch (e) {
				return '';
			}
		},

		setScopePath: function(scopePath) {
			this.scopePath = (scopePath || '').trim().replace(/^\/+|\/+$/g, '');
			try {
				window.localStorage.setItem('keeporsweep.scopePath', this.scopePath);
			} catch (e) {
				// ignore storage errors
			}
		},

		load: function() {
			return this._loadList();
		},
		_loadList: function() {
			var self = this;

			var baseUrl = OC.generateUrl('/apps/keeporsweep');
			var query = {
				limit: 120
			};

			if (self.scopePath) {
				query.folder = self.scopePath;
			}

			return (
				$.getJSON(baseUrl + '/files?' + $.param(query))
				.then(function(result) {
					var files = Array.isArray(result) ? result : (result.files || []);
					self._list = _.shuffle(self._dedupeFiles(files));
				})
				.catch(function() {
					self._list = [];
					OC.Notification.showTemporary(t('keeporsweep', 'Could not load your files.'));
				})
			);
		},
		_dedupeFiles: function(files) {
			var seen = {};
			return files.filter(function(file) {
				var key = file && file.id ? 'id:' + file.id : 'path:' + (file.path || '') + (file.name || '');
				if (seen[key]) {
					return false;
				}
				seen[key] = true;
				return true;
			});
		},
		_onPreviewLoad: function($target, url) {
			$target.css('background-image', 'url("' + url + '")');
		},
		_loadPreview: function(file) {
			var self = this;
			var $target = $(this._containerActive);
			var params = {
				file: file.path + file.name,
				fileId: file.id,
				x: this._previewSize,
				y: this._previewSize,
				forceIcon: 0
			};

			// Default
			var iconImg = new Image();
			const iconUrl = OC.MimeType.getIconUrl(file.mimetype);
			iconImg.src = iconUrl;
			$target.css('background-image', 'url("' + iconUrl + '")');

			// Try to get the preview if it is an image
			if(file.mimetype == 'image/jpeg' ||
				file.mimetype == 'image/png' ||
				file.mimetype == 'image/gif'){
				var previewImg = new Image();
				const previewUrl = OC.generateUrl('/core/preview.png?') + $.param(params);
				previewImg.onload = function() {
					self._onPreviewLoad($target, previewUrl);
				};
				previewImg.src = previewUrl;
			}
		},

		nextElement: function() {
			var file = null;

			while (this._currentIndex < this._list.length) {
				file = this._list[this._currentIndex++];
				if (!file) {
					continue;
				}

				if (this._lastShownFileId !== null && file.id === this._lastShownFileId && this._currentIndex < this._list.length) {
					continue;
				}

				this._lastShownFileId = file.id || null;
				this._loadPreview(file);
				return file;
			}

			return null;
		},

		keepElement: function() {
			if (this._currentIndex > this._list.length) {
				return;
			}

			this.moveContainer('Right');
		},

		sweepElement: function(path) {
			if (this._currentIndex > this._list.length) {
				return;
			}

			this.moveContainer('Left');
			this.filesClient.remove(path)
				.catch(function() {
					OC.Notification.showTemporary(t('keeporsweep', 'Could not delete file.'));
				});
		},

		moveContainer: function(direction) {
			const container = '.element-container-';

			if(this._currentIndex == 0) {
				return;
			}

			if(this._containerCurrent > 4) {
				this._containerCurrent = 1;
			}
			if(this._containerBefore > 4) {
				this._containerBefore = 1;
			}
			if(this._containerAfter > 4) {
				this._containerAfter = 1;
			}

			// Move card out in specified direction
			$(container + this._containerCurrent)
				.removeClass('fadeIn active')
				.addClass('bounceOut' + direction);

			// Card on the bottom of the stack gets cleaned up
			// Emptycontent is shown when stack is over
			if(!(this._currentIndex >= (this._list.length-2))) {
				$(container + (this._containerBefore))
					.removeClass('bounceOutRight bounceOutLeft')
					.addClass('fadeIn')
					.attr('style', 'z-index: -' + this._currentIndex);
			}

			// Next card set as active
			$(container + (this._containerAfter))
				.addClass('active');

			this._containerCurrent++;
			this._containerBefore++;
			this._containerAfter++;
		}
	}

	var manager = new Manager();

	var app = new Vue({
		el: '#app-content',
		container: '#app-content .element-container',
		data: {
			file: {},
			scopePath: manager.scopePath,
			actionKeepHover: false,
			actionSweepHover: false
		},
		methods: {
			applyScope: function() {
				manager.setScopePath(this.scopePath);
				window.location.reload();
			},
			next: function() {
				var file = manager.nextElement();
				if(file) {
					this.file = file;
				}
			},
			keep: function() {
				manager.keepElement();
				this.next();
			},
			sweep: function() {
				var path = this.file.path + this.file.name;
				manager.sweepElement(path);
				this.next();
			},
			// Keyboard shortcuts thanks to https://vuejsdevelopers.com/2017/05/01/vue-js-cant-help-head-body/
			keyListener: function(evt) {
				// Keep: Space, →, Enter
				if(evt.keyCode === 32 || evt.keyCode === 39 || evt.keyCode === 13) {
					this.keep();
				}
				// Sweep: Delete, ←
				if(evt.keyCode === 46 || evt.keyCode === 37) {
					this.sweep();
				}
			}
		},
		created: function() {
			document.addEventListener('keyup', this.keyListener);
		},
		destroyed: function() {
			document.removeEventListener('keyup', this.keyListener);
		}
	});

	manager.load()
	.then(app.next);

})(window, OC, KeepOrSweep);
