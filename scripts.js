// on page load...
//
$(document).ready(function() {
	// initialize components
	$('.tabs').tabs();
	$('.modal').modal();
	$('select').formSelect();

	// todo
});

// search for an article
//
function searchArticles() {
	// array of possible values
	var names = ['title', 'media', 'type', 'minDate', 'maxDate', 'minComments'];
	var hasData = false;
	var data = {};

	// create object to send to the backend
	names.forEach(function (prop) {
		var value = $('#'+prop).val();
		if (value != null && value !== "" && value !== "all") {
			data[prop] = value;
			hasData = true;
		}
	});

	// error if no data was passed
	if (!hasData) {
		M.toast({html: 'Ingrese algún parámetro de búsqueda'});
		return;
	}

	// start the search
	apretaste.send({
		command: 'NOTICIAS',
		data: {search: data}
	})
}

// save the media types
//
function saveMedia() {
	// get the selected IDs
	var selectedId = [];
	$('.media-checker input').each(function(i, e){
		if($(e).prop('checked')) selectedId.push($(e).attr('id'));
	});

	// display error if no media was selected
	if (selectedId.length <= 0) {
		M.toast({html: 'Seleccione al menos un medio'});
		return false
	}

	// send data to backend and redirect
	apretaste.send({
		command: 'NOTICIAS GUARDAR',
		data: {ids: selectedId}
	});
}

// create a teaser to share
//
function teaser(text) {
	return text.length <= 50 ? text.trim() : text.trim().substr(0, 50).trim() + "...";
}

// share a news article in Pizarra
//
function share(id, text) {
	// clean and shorten texts
	var articleId = $('#articleId').val();
	var message = $('#message').val();
	var title = teaser($('#shareModal .title').text());

	// share in pizarra
	apretaste.send({
		command: 'PIZARRA PUBLICAR',
		redirect: false,
		data: {
			text: message,
			image: '',
			link: {
				command: btoa(JSON.stringify({
					command: 'NOTICIAS HISTORIA',
					data: {id: articleId}
				})),
				icon: 'newspaper',
				text: title
			}
		}
	});

	// show message
	M.toast({html: 'La noticia fue compartida en Pizarra'});
}

// comment on a note or interact on the comment board
//
function comment() {
	// get the comment
	var comment = $('#comment').val().trim();
	var articleId = $('#articleId').val();

	// error if comment is blank
	if (comment.length <= 0) {
		M.toast({html: '¡Escribe algo!'});
		return false;
	}

	// choose if there is a comment or a note
	var callback = (typeof articleId == 'undefined') ? "sendPostCallback" : "sendCommentCallback";

	// post the comment
	apretaste.send({
		'command': 'NOTICIAS COMENTAR',
		'data': {
			'comment': comment,
			'article': articleId
		},
		'redirect': false,
		'callback': {'name': callback}
	});
}

// callback to comment on a note
//
function sendCommentCallback() {
	// get data from the view
	var comment = escapeHTML($('#comment').val().trim());
	var username = $('#username').val().trim();
	var gender = $('#gender').val().trim();
	var avatar = $('#avatar').val().trim();
	var avatarColor = $('#avatarColor').val().trim();

	// create comment HTML
	var element =
		"<li class='right' id='last'>" +
		"	<div class='person-avatar circle' face='"+avatar+"' color='"+avatarColor+"' size='30'></div>" +
		"	<div class='head'>" +
		"		<a class='"+gender+"'>@"+username+"</a>" +
		"		<span class='date'>"+moment().format('MMM D, YYYY h:mm A')+"</span>" +
		"	</div>" +
		"	<span class='text'>"+comment+"</span>" +
		"</li>";

	// add comment to the list
	$('#no-comments').remove();
	$('#comments').prepend(element);

	// clean the field
	$('#comment').val('');

	// scroll to the comment
	$('html, body').animate({scrollTop: $("#last").offset().top - 64}, 1000);

	// re-create avatar
	setElementAsAvatar($('#last .person-avatar').get());

	// increase number of comments
	var commentsCounter = $('#comments-counter');
	commentsCounter.html(parseInt(commentsCounter.html())+1);
}

// callback to post on the board
//
function sendPostCallback() {
	// get data from the view
	var comment = escapeHTML($('#comment').val().trim());
	var username = $('#username').val().trim();
	var gender = $('#gender').val().trim();
	var avatar = $('#avatar').val().trim();
	var avatarColor = $('#avatarColor').val().trim();

	// create comment HTML
	var element =
		"<div class='card' id='last'>" +
		"	<div class='card-person grey lighten-5'>" +
		"		<div class='person-avatar circle left' face='"+avatar+"' color='"+avatarColor+"' size='30'></div>" +
		"		<a href='#!' class='"+gender+"'>@"+username+"</a>" +
		"		<span class='chip tiny clear right'><i class='material-icons icon'>perm_contact_calendar</i> "+moment().format('MMM D, h:mm A')+"</span>" +
		"	</div>" +
		"	<div class='card-content'><p>"+comment+"</p></div>" +
		"</div>";

	// add comment to the list
	$('#comments').prepend(element);

	// clean the field
	$('#comment').val('');

	// scroll to the comment
	$('html, body').animate({scrollTop: $("#last").offset().top - 64}, 1000);

	// re-create avatar
	setElementAsAvatar($('#last .person-avatar').get());
}

// escape HTML data
//
function escapeHTML(text) {
	var htmlEscapes = {'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#x27;','/': '&#x2F;'};
	var htmlEscaper = /[&<>"'\/]/g;
	return ('' + text).replace(htmlEscaper, function (match) {
		return htmlEscapes[match];
	});
};
