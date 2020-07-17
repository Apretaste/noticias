// Variables

var emptyPreferences;

// On load function


$(function () {
	$('select').formSelect();
	$('.tabs').tabs();

	setDatePicker('#minDate', onSelectMinDate);
	setDatePicker('#maxDate', onSelectMaxDate);

	$('#sources').change(function (event) {
		var selected = $('#sources option:selected').val();
		return apretaste.send({
			"command": "NOTICIAS",
			"data": {"source": selected}
		});
	});

	emptyPreferences = typeof preferredMedia != "undefined" && preferredMedia.length == 0;
});

function onSelectMinDate(value) {
	setDatePicker('#maxDate', onSelectMaxDate, value);
}

function onSelectMaxDate(value) {
	setDatePicker('#minDate', onSelectMinDate, null, value);
}

function setDatePicker(id, onSelect, minDate, maxDate = new Date()) {
	var internationalization = {
		months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
			'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
		monthsShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
		weekdaysAbbrev: ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
		weekdaysShort: ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab']
	};

	$(id).datepicker({
		autoClose: true,
		format: 'dd/mm/yyyy',
		yearRange: [2020, (new Date()).getFullYear()],
		minDate: minDate,
		maxDate: maxDate,
		firstDay: 1,
		i18n: internationalization,
		onSelect: onSelect,
	});
}

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

function toggleSearchModal() {
	var status = $('#searchModal').attr('status');

	if (status == "closed") {
		if ($('.container:not(#searchModal) > .navbar-fixed').length == 1) {
			var h = $('.container:not(#searchModal) > .navbar-fixed')[0].clientHeight + 1;
			$('#searchModal').css('height', 'calc(100% - ' + h + 'px)');
		}

		$('#searchModal').slideToggle({
			direction: "up"
		}).attr('status', 'opened');
		$('#title').focus();
	} else {
		$('#searchModal').slideToggle({
			direction: "up"
		}).attr('status', 'closed');
	}
}

function filterMedia(value) {
	var options = '<option value="all" selected>Todos</option>\n';
	var mediaSelect = $('#media');

	availableMedia.forEach(function (media) {
		if (value === 'all' || media.type === value) {
			options += '<option value="' + media.id + '" selected>' + media.caption + '</option>\n';
		}
	});

	mediaSelect.html(options);

	mediaSelect.formSelect();
}

function searchArticles() {
	// array of possible values
	var names = ['title', 'media', 'type', 'minDate', 'maxDate', 'minComments'];
	var hasData = false;

	// create object to send to the backend
	var data = {};
	names.forEach(function (prop) {
		var value = $('#' + prop).val();
		if (value != null && value !== "" && value !== "all") {
			data[prop] = value;
			hasData = true;
		}
	});

	if (!hasData) {
		showToast('Ingrese algún parámetro de búsqueda');
		return;
	}

	apretaste.send({
		command: 'noticias', data: {search: data}
	})
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
	if (preferredMedia.length > 0) {
		apretaste.send({
			command: 'noticias medios',
			data: {
				preferredMedia: preferredMedia
			},
			redirect: false,
			callback: {
				name: 'savePreferredMediaCallback'
			}
		})
	} else {
		showToast('Debe seleccionar al menos un medio');
	}
}

function savePreferredMediaCallback() {
	apretaste.send({command: 'noticias'});
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
	var element;

	if (title === 'Comentarios') {
		element = "<div class=\"card\" id=\"last\">\n" +
			"            <div class=\"card-person grey lighten-5\">\n" +
			"                <div class=\"person-avatar circle left\" face=\"" + avatar + "\" color=\"" + avatarColor + "\"\n" +
			"                     size=\"30\"></div>\n" +
			"                <a href=\"#!\" class=\"" + gender + "\"\n" +
			"                   onclick=\"openProfile('" + username + "')\">@" + username + "</a>\n" +
			"                <div class=\"right\">\n" +
			"                    <i class=\"material-icons ultra-tiny\">calendar_today</i> " + moment().format('MMM D, h:mm A') + "\n" +
			"                </div>\n" +
			"            </div>\n" +
			"            <div class=\"card-content\" style=\"padding: 0.75rem\">\n" +
			"                <p>" + comment + "</p>\n" +
			"            </div>\n" +
			"        </div>"
	} else {
		element =
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
	}

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