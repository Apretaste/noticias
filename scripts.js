// Variables

var emptyPreferences;

// On load function


$(function () {
	$('select').formSelect();
	$('.tabs').tabs();

	$('#sources').change(function (event) {
		var selected = $('#sources option:selected').val();
		return apretaste.send({
			"command": "NOTICIAS",
			"data": {"source": selected}
		});
	});

	emptyPreferences = typeof preferredMedia != "undefined" && preferredMedia.length == 0;
});

// Main functions

function toggleWriteModal() {
	var status = $('#writeModal').attr('status');

	if (status == "closed") {
		if ($('.container:not(#writeModal) > .navbar-fixed').length == 1) {
			var h = $('.container:not(#writeModal) > .navbar-fixed')[0].clientHeight + 1;
			$('#writeModal').css('height', 'calc(100% - ' + h + 'px)');
		}

		$('#writeModal').slideToggle({
			direction: "up"
		}).attr('status', 'opened');
		$('#comment').focus();
	} else {
		$('#writeModal').slideToggle({
			direction: "up"
		}).attr('status', 'closed');
	}
}

function getRandomColor() {
	var letters = '0123456789ABCDEF';
	var color = '#';
	for (var i = 0; i < 6; i++) {
		color += letters[Math.floor(Math.random() * 16)];
	}
	return color;
}

function openProfile(username) {
	apretaste.send(
		{
			'command': 'PERFIL',
			'data': {'username': '@' + username}
		}
	);
}

function checkMedia(id) {
	$('#' + id).click();
}

function togglePreferredMedia(checkbox) {
	if (checkbox.checked) preferredMedia.push(checkbox.id);
	else {
		for (var i = 0; i < preferredMedia.length; i++) {
			if (preferredMedia[i] === checkbox.id) {
				preferredMedia.splice(i, 1);
			}
		}
	}
}

function savePreferredMedia() {
	apretaste.send({
		command: 'noticias medios',
		data: {
			preferredMedia: preferredMedia
		},
		redirect: emptyPreferences,
		callback: {
			name: 'showToast',
			data: 'Pereferencias guardadas'
		}
	})
}

function showToast(text) {
	M.toast({
		html: text
	});
}


// FILTER FUNCTIONS

function options() {
	$('.drop-down .options').slideDown('fast');
}

function select(mediaType) {
	// show all categories
	if (mediaType == 'all') {
		$('.media').slideDown();
	}
	// filter by mediaType name
	else {
		$('.media').hide();
		$('.' + mediaType).slideDown();
	}

	// hide the options bar
	$('.drop-down .options').hide();
}

// Request functions

function sendComment() {
	var comment = $('#comment').val().trim();

	if (comment.length >= 2) {
		apretaste.send({
			'command': 'NOTICIAS COMENTAR',
			'data': {
				'comment': comment,
				'article': typeof id != "undefined" ? id : null
			},
			'redirect': false,
			'callback': {
				'name': 'sendCommentCallback',
				'data': comment.escapeHTML()
			}
		});
	} else {
		showToast('Escriba algo');
	}
}

// Callback functions

function sendCommentCallback(comment) {
	var element =
		"<li class=\"right\" id=\"last\">\n" +
		"    <div class=\"person-avatar circle\" face=\"" + avatar + "\" color=\"" + avatarColor + "\"\n" +
		"         size=\"30\" onclick=\"openProfile('" + username + "')\"></div>\n" +
		"    <div class=\"head\">\n" +
		"        <a onclick=\"openProfile('" + username + "')\"\n" +
		"           class=\"" + gender + "\">@" + username + "</a>\n" +
		"        <span class=\"date\">" + moment().format('MMM D, YYYY h:mm A') + "</span>\n" +
		"    </div>\n" +
		"    <span class=\"text\">" + comment + "</span>\n" +
		"</li>"

	$('#no-comments').remove();

	$('#comments').prepend(element);
	$('#comment').val('');
	$('html, body').animate({
		scrollTop: $("#last").offset().top - 64
	}, 1000);

	var commentsCounter = $('#commentsCounter');

	commentsCounter.html(parseInt(commentsCounter.html()) + 1);

	$('.person-avatar').each(function (i, item) {
		setElementAsAvatar(item)
	});

	toggleWriteModal();
}

// Prototype functions

String.prototype.escapeHTML = function () {
	var htmlEscapes = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#x27;',
		'/': '&#x2F;'
	};
	var htmlEscaper = /[&<>"'\/]/g;
	return ('' + this).replace(htmlEscaper, function (match) {
		return htmlEscapes[match];
	});
};

Date.prototype.nowFormated = function () {
	var now = new Date(); // This current millisecond on user's computer.

	var format = "{D}/{M}/{Y} · {h}:{m}{ap}";
	var Month = now.getMonth() + 1;
	format = format.replace(/\{M\}/g, Month);
	var Mday = now.getDate();
	format = format.replace(/\{D\}/g, Mday);
	var Year = now.getFullYear().toString().slice(2);
	format = format.replace(/\{Y\}/g, Year);
	var h = now.getHours();
	var pm = h > 11;

	if (h > 12) {
		h -= 12;
	}

	;
	var ap = pm ? "pm" : "am";
	format = format.replace(/\{ap\}/g, ap);
	var hh = h;
	format = format.replace(/\{h\}/g, hh);
	var mm = now.getMinutes();

	if (mm < 10) {
		mm = "0" + mm;
	}

	format = format.replace(/\{m\}/g, mm);
	return format;
};

/**/

function sendSearch() {
	var query = $('#query').val().trim();
	if (query.length >= 3) {
		apretaste.send({
			'command': 'NOTICIAS BUSCAR',
			'data': {query: query}
		});
	} else {
		M.toast({html: 'Inserte mínimo 3 caracteres'});
	}
}

String.prototype.replaceAll = function (search, replacement) {
	return this.split(search).join(replacement);
};

// Pollyfill

function _typeof(obj) {
	if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
		_typeof = function _typeof(obj) {
			return typeof obj;
		};
	} else {
		_typeof = function _typeof(obj) {
			return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
		};
	}
	return _typeof(obj);
}

if (!Object.keys) {
	Object.keys = function () {
		'use strict';

		var hasOwnProperty = Object.prototype.hasOwnProperty,
			hasDontEnumBug = !{
				toString: null
			}.propertyIsEnumerable('toString'),
			dontEnums = ['toString', 'toLocaleString', 'valueOf', 'hasOwnProperty', 'isPrototypeOf', 'propertyIsEnumerable', 'constructor'],
			dontEnumsLength = dontEnums.length;
		return function (obj) {
			if (_typeof(obj) !== 'object' && (typeof obj !== 'function' || obj === null)) {
				throw new TypeError('Object.keys called on non-object');
			}

			var result = [],
				prop,
				i;

			for (prop in obj) {
				if (hasOwnProperty.call(obj, prop)) {
					result.push(prop);
				}
			}

			if (hasDontEnumBug) {
				for (i = 0; i < dontEnumsLength; i++) {
					if (hasOwnProperty.call(obj, dontEnums[i])) {
						result.push(dontEnums[i]);
					}
				}
			}

			return result;
		};
	}();
}