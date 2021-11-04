const weekdayNicks = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
const monthNames = ['Januar', 'Feburar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

const list_filter = {};
const list_init = () => {
	$('#view-list .previous-month').on('click', e => {
		e.preventDefault();
		setFilter(list_filter.month - 1 === 0 ? list_filter.year - 1 : null, ((list_filter.month - 1 + 11) % 12) + 1);
	});
	$('#view-list .next-month').on('click', e => {
		e.preventDefault();
		setFilter(list_filter.month + 1 === 13 ? list_filter.year + 1 : null, ((list_filter.month - 1 + 1) % 12) + 1);
	});
	$('#view-list .previous-year').on('click', e => {
		e.preventDefault();
		setFilter(list_filter.year - 1, null);
	});
	$('#view-list .next-year').on('click', e => {
		e.preventDefault();
		setFilter(list_filter.year + 1, null);
	});
	$('#view-list .month-picker a').on('click', e => {
		e.preventDefault();
		setFilter(null, parseInt($(e.currentTarget).data('month')));
	});
};

const form_setImage = image => {
	
	if(image === null) {
		$('#view-form .imageform .image-wrapper').empty();
		$('#view-form .imageform .image-controls :nth-child(2)').hide();
		$('#view-form .imageform .image-controls input[type="hidden"]').val('');
		$('#view-form .imageform .image-controls input[type="file"]').val('');
	} else {
		$('#view-form .imageform .image-wrapper').empty();
		$('#view-form .imageform .image-wrapper').append(`<img src="${image}" />`);
		$('#view-form .imageform .image-controls :nth-child(2)').show();
		$('#view-form .imageform .image-controls input[type="hidden"]').val(image);
	}
	
};

const form_clearLinks = () => {
	
	$('#view-form .linkform > .link').remove();
	
};

const form_addLink = link => {
	
	if($('#view-form .linkform > .link').length >= 10) {
		console.error('Too much links');
		return;
	}
	
	const id = uuid();
	$('#view-form .linkform > .formitem:last-child').before(`
		<div class="formitem link grow-equal">
			<label for="view-form-input-link-text-${id}">Linktext und -ziel (<a href="#">Link entfernen</a>)</label>
			<input id="view-form-input-link-text-${id}" type="text" maxlength="255" required="required" />
			<input id="view-form-input-link-target-${id}" type="url" pattern="^https?://.+$" maxlength="255" required="required" />
		</div>`);
	$('#view-form-input-link-text-' + id).prev().find('a').on('click', e => {
		e.preventDefault();
		$(e.currentTarget).parent().parent().remove();
	});
	$('#view-form-input-link-text-' + id).val(link.text);
	$('#view-form-input-link-target-' + id).val(link.target);
	
};

const createPopup = content => {
	const lifeTime = 10000;
	const timerTime = 50;
	let timerCount = 0;
	const id = uuid();
	$('#popups').append(`<section id="popup-${id}" class="popup">${content}<div class="timer"></div></section>`);
	const interval = window.setInterval(() => {
		timerCount++;
		const newWidth = (timerCount * 100 * timerTime / lifeTime).toString() + '%';
		$('#popup-' + id + ' .timer').width(newWidth);
	}, timerTime);
	window.setTimeout(() => {
		clearInterval(interval);
		$('#popup-' + id).remove();
	}, lifeTime);
};

const form_init = () => {
	$('#view-form .imageform .image-controls :nth-child(1) a').on('click', e => {
		e.preventDefault();
		$('#view-form .imageform .image-controls input[type="file"]').click();
	});
	$('#view-form .imageform .image-controls :nth-child(2) a').on('click', e => {
		e.preventDefault();
		form_setImage(null);
	});
	$('#view-form .imageform .image-controls input[type="file"]').on('change', e => {
		$('#view-form .imageform .image-wrapper').addClass('loading').empty();
		const files = e.currentTarget.files;
		if(files.length !== 1) return;
		const file = files[0];
		if(!file.type.match(/image\/.+/)) return;
		const fileReader = new FileReader();
		fileReader.onload = e2 => {
			$('#view-form .imageform .image-wrapper').removeClass('loading');
			if(e2.currentTarget.result.length > 16777215) {
				console.error('Image too big');
				form_setImage(null);
				createPopup(`<h2>Bild zu groß</h2>`);
				return;
			}
			form_setImage(e2.currentTarget.result);
		};
		fileReader.readAsDataURL(file);
	});
	$('#view-form .linkform .formitem:last-child a').on('click', e => {
		e.preventDefault();
		form_addLink({text: '', target: ''});
	});
	$('#view-form-button-delete').on('click', e => {
		e.preventDefault();
		
		const formData = form_get();
	
		$('main').hide();
		$('#view-loading').show();
		
		$.ajax({
			dataType: 'json',
			url: 'webservice/events/?id=' + formData.id + '&version=' + formData.version,
			success: (data, status, xhr) => {
				
				const newHashParams = new URLSearchParams();
				newHashParams.set('action', 'list');
				newHashParams.set('year', parseInt(formData.startTime.substr(0, 4)));
				newHashParams.set('month', parseInt(formData.startTime.substr(5, 2)));
				
				window.location.hash = '#' + newHashParams.toString();
		
			},
			error: (xhr, status, error) => {
				
				console.error(error);
				console.error(xhr);
				
				$('main').hide();
				$('#view-form').show();
				
				createPopup(`<h2>Fehler beim Löschen</h2><p>${xhr.responseJSON.message}</p>`);
				
			},
			method: 'DELETE'
		});
	});
	$('#view-form-button-back').on('click', e => {
		e.preventDefault();
		window.history.back();
	});
	$('#view-form-button-save').on('click', e => {
		e.preventDefault();
		if($('#view-form input:invalid').length > 0) {
			console.error('Form invalid:', $('#view-form input:invalid'));
			$('html, body').animate({
				scrollTop: $('#view-form input:invalid').offset().top - 120
			}, 300);
			createPopup(`<h2>Formular nicht ausgefüllt</h2>`);
			return;
		}
		
		const formData = form_get();
	
		$('main').hide();
		$('#view-loading').show();
		
		$.ajax({
			contentType: "application/json",
			data: JSON.stringify(formData),
			dataType: 'json',
			url: 'webservice/events/',
			success: (data, status, xhr) => {
				
				const newHashParams = new URLSearchParams();
				newHashParams.set('action', 'list');
				newHashParams.set('year', parseInt(formData.startTime.substr(0, 4)));
				newHashParams.set('month', parseInt(formData.startTime.substr(5, 2)));
				
				window.location.hash = '#' + newHashParams.toString();
		
			},
			error: (xhr, status, error) => {
				
				console.error(error);
				console.error(xhr);
				
				$('main').hide();
				$('#view-form').show();
				
				createPopup(`<h2>Fehler beim Speichern</h2><p>${xhr.responseJSON.message}</p>`);
				
			},
			method: 'PUT'
		});
	});
};

$(document).ready(function() {

	$(window).on('hashchange', e => {
		route();
	});
	route();
	
	list_init();
	form_init();
	
});

const setFilter = (year, month) => {
	
	if(year !== null) {
		list_filter.year = year;
	}
	
	if(month !== null) {
		list_filter.month = month;
	}
	
	const newHashParams = new URLSearchParams();
	newHashParams.set('action', 'list');
	newHashParams.set('year', list_filter.year);
	newHashParams.set('month', list_filter.month);
	
	window.location.hash = '#' + newHashParams.toString();
	
};

const uuid = () => {
	return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
};

const route = () => {
	
	const hashParams = new URLSearchParams('?' + window.location.hash.substr(1));
	
	if(!hashParams.has('action')) {
		hashParams.set('action', 'list');
		hashParams.set('year', (new Date()).getFullYear().toString());
		hashParams.set('month', ((new Date()).getMonth() + 1).toString());
	}
	
	if(hashParams.get('action') === 'list') {
		route_list(hashParams);
		return;
	} else if(hashParams.get('action') === 'create') {
		route_create(hashParams);
		return;
	} else if(hashParams.get('action') === 'edit') {
		route_edit(hashParams);
		return;
	}
	
};

const route_list = hashParams => {
	
	$('main').hide();
	$('#view-loading').show();
	
	list_filter.year = parseInt(hashParams.get('year'));
	list_filter.month = parseInt(hashParams.get('month'));
	
	$('#view-list .year').text(list_filter.year);
	$('#view-list .month').text(monthNames[list_filter.month - 1]);
	$('#view-list .month-picker a').removeClass('active');
	$('#view-list .month-picker li:nth-child(' + list_filter.month + ') a').addClass('active');
	
	$('#view-list .days').empty();
	const holidays = generateHolidays(list_filter.year, list_filter.month);
	for(let dayDate = new Date(list_filter.year, list_filter.month - 1, 1); dayDate.getMonth() === list_filter.month - 1; dayDate.setDate(dayDate.getDate() + 1)) {
		
		const isWeekend = (dayDate.getDay() + 1) % 7 <= 1;
		const weekendClass = isWeekend ? 'weekend' : '';
		
		const isHoliday = Object.keys(holidays).includes(dayDate.getDate().toString());
		const holidayClass = isHoliday ? 'holiday' : '';
		
		$('#view-list .days').append(`
			<section class="day ${weekendClass} ${holidayClass}" data-day="${dayDate.getDate()}">
				<span class="marker"></span>
				<span class="monthday">${dayDate.getDate()}</span>
				<span class="weekday">${weekdayNicks[dayDate.getDay()]}</span>
				<span class="events"></span>
				<a class="tool create" href="#">+</a>
			</section>`);
		
	}
	
	$('#view-list .content .tool.create').on('click', e => {
		e.preventDefault();
		const day = parseInt($(e.currentTarget).parent().data('day'));
		const newHashParams = new URLSearchParams();
		newHashParams.set('action', 'create');
		newHashParams.set('date', list_filter.year.toString() + '-' + ('0' + list_filter.month).substr(-2) + '-' + ('0' + day).substr(-2));
		window.location.hash = '#' + newHashParams.toString();
	});
	
	$.ajax({
		dataType: 'json',
		url: 'webservice/events/?year=' + list_filter.year + '&month=' + list_filter.month,
		success: (data, status, xhr) => {
			
			data.forEach(event => {
				$('#view-list .days .day[data-day="' + parseInt(event.startTime.substr(8, 2)) + '"] .events').append(`
					<a class="event" data-id="${event.id}" href="#">
						<span class="time">${event.startTime.substr(11, 5)}</span>
						<span class="title">${event.title}</span>
					</a>`);
			});
	
			$('#view-list .content .event').on('click', e => {
				e.preventDefault();
				const newHashParams = new URLSearchParams();
				newHashParams.set('action', 'edit');
				newHashParams.set('id', $(e.currentTarget).data('id'));
				window.location.hash = '#' + newHashParams.toString();
			});
	
			$('main').hide();
			$('#view-list').show();
	
		},
		error: (xhr, status, error) => {
				
			console.error(error);
			console.error(xhr);
			
			createPopup(`<h2>Fehler beim Laden</h2><p>${xhr.responseJSON.message}</p>`);
			
		},
		method: 'GET'
	});
	
};

const form_set = event => {
	$('#view-form-input-id'            ).val(event.id);
	$('#view-form-input-version'       ).val(event.version ?? '');
	$('#view-form-input-startTime-date').val(event.startTime.substr(0, 10));
	$('#view-form-input-startTime-time').val(event.startTime.substr(11, 5));
	$('#view-form-input-entry'         ).val(event.entry);
	$('#view-form-input-title'         ).val(event.title);
	$('#view-form-input-subtitle'      ).val(event.subtitle);
	$('#view-form-input-series'        ).val(event.series);
	$('#view-form-input-text'          ).val(event.text);
	$('#view-form-input-lineup'        ).val(event.lineup);
	$('#view-form-input-notes'         ).val(event.notes);
	form_setImage(event.image);
	form_clearLinks();
	event.links.forEach(link => form_addLink(link));
};

const form_get = () => {
	return {
		id: $('#view-form-input-id').val(),
		version: parseInt($('#view-form-input-version').val()) || null,
		startTime: $('#view-form-input-startTime-date').val() + 'T' + $('#view-form-input-startTime-time').val(),
		entry: $('#view-form-input-entry').val(),
		title: $('#view-form-input-title').val(),
		subtitle: $('#view-form-input-subtitle').val(),
		series: $('#view-form-input-series').val(),
		text: $('#view-form-input-text').val(),
		lineup: $('#view-form-input-lineup').val(),
		notes: $('#view-form-input-notes').val(),
		image: $('#view-form-input-image').val() || null,
		links: $('#view-form .linkform > .link').get().map(link => { return { text: $(link).find('input[type="text"]').val(), target: $(link).find('input[type="url"]').val() }; })
	}
};

const route_create = hashParams => {
	
	$('main').hide();
	$('#view-loading').show();
	
	$('#view-form h2').text('Veranstaltung erstellen');
	$('#view-form-button-delete').hide();
	
	form_set({
		id: uuid(),
		version: null,
		startTime: hashParams.get('date') + 'T00:00',
		entry: '',
		title: '',
		subtitle: '',
		series: '',
		text: '',
		lineup: '',
		notes: '',
		image: null,
		links: []
	});
	$('#view-form-input-startTime-time').val('');
	
	$('main').hide();
	$('#view-form').show();
	
};

const route_edit = hashParams => {
	
	$('main').hide();
	$('#view-loading').show();
	
	$('#view-form h2').text('Veranstaltung bearbeiten');
	$('#view-form-button-delete').show();
	
	$.ajax({
		dataType: 'json',
		url: 'webservice/events/?id=' + hashParams.get('id') + '&include=content,image,links',
		success: (data, status, xhr) => {
			
			form_set(data);
			
			$('main').hide();
			$('#view-form').show();
	
		},
		error: (xhr, status, error) => {
				
			console.error(error);
			console.error(xhr);
			
			window.history.back();
			
			createPopup(`<h2>Fehler beim Laden</h2><p>${xhr.responseJSON.message}</p>`);
			
		},
		method: 'GET'
	});
	
};
	
const generateEaster = year => {
	const date = new Date;
	date.setHours(0, 0, 0, 0);
	date.setFullYear(year);
	const a = year % 19;
	const b = (2200 <= year && year <= 2299) ? ((11 * a) + 4) % 30 : ((11 * a) + 5) % 30;
	const c = ((b === 0) || (b === 1 && a > 10)) ? (b + 1) : b;
	const m = (1 <= c && c <= 19) ? 3 : 2;
	const d = (50 - c) % 31;
	date.setMonth(m, d);
	date.setMonth(m, d + (7 - date.getDay()));
	return date;
};

const generateHolidays = (year, month) => {
	const result = {};
	if(month === 1) {
		result['1'] = 'Neujahr'
		result['6'] = 'Heilig Drei Könige'
	}
	if(month === 5) {
		result['1'] = 'Tag der Arbeit'
	}
	if(month === 8) {
		result['15'] = 'Mariä Himmelfahrt'
	}
	if(month === 10) {
		result['3'] = 'Tag der Einheit'
	}
	if(month === 11) {
		result['1'] = 'Allerheiligen'
	}
	if(month === 12) {
		result['24'] = 'Heilig Abend (nicht gesetzlich)'
		result['25'] = '1. Weihnachtsfeiertag'
		result['26'] = '2. Weihnachtsfeiertag'
		result['31'] = 'Sylvester (nicht gesetzlich)'
	}
	const movableHoliday = generateEaster(year);
	const offsets = [-2, 1, 39, 50, 60];
	const titles = ['Karfreitag', 'Ostermontag', 'Christi Himmelfahrt', 'Pfingstmontag', 'Fronleichnam'];
	for(let offsetIndex = 0; offsetIndex < offsets.length; offsetIndex++) {
		movableHoliday.setDate(movableHoliday.getDate() + offsets[offsetIndex])
		if(movableHoliday.getMonth() === month - 1) result[movableHoliday.getDate().toString()] = titles[offsetIndex];
		movableHoliday.setDate(movableHoliday.getDate() - offsets[offsetIndex])
	}
	return result;
};