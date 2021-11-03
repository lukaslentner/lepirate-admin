<?php

require_once(dirname(__FILE__) . '/include.php');

$oAuth = new OAuth();
$graph = new Graph($oAuth);

?>
<!doctype html>
<html>
<head>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, user-scalable=no" />
	<meta name="theme-color" content="#000000" />
	
	<title>Le Pirate Admin</title>
	
	<link rel="icon" href="favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="css/styles.css">
	
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/events.js"></script>
	
</head>
<body> 

	<header>
		<a class="apps" href="https://www.office.com/apps"><img src="images/apps.png" /></a>
		<a class="brand" href="#"><img src="images/logo.png" /><h1>Le Pirate Admin</h1></a>
		<a class="menuitem active" href="#"><img src="images/events.png" /></a>
	</header>
	
	<main id="view-list">
	
		<aside class="filter">
			<section class="year"></section>
			<section class="year-buttons"><a class="previous-year" href="#"></a><a class="next-year" href="#"></a></section>
			<ul class="month-picker">
				<li><a data-month="1"  href="#">Jan</a></li>
				<li><a data-month="2"  href="#">Feb</a></li>
				<li><a data-month="3"  href="#">Mrz</a></li>
				<li><a data-month="4"  href="#">Apr</a></li>
				<li><a data-month="5"  href="#">Mai</a></li>
				<li><a data-month="6"  href="#">Jun</a></li>
				<li><a data-month="7"  href="#">Jul</a></li>
				<li><a data-month="8"  href="#">Aug</a></li>
				<li><a data-month="9"  href="#">Sep</a></li>
				<li><a data-month="10"  href="#">Okt</a></li>
				<li><a data-month="11" href="#">Nov</a></li>
				<li><a data-month="12" href="#">Dez</a></li>
			</ul>
		</aside>
	
		<article class="content">
	
			<section class="title">
				<h2>
					<a class="previous-month" href="#"></a>
					<a class="next-month" href="#"></a>
					<span class="prefix">Veranstaltungen: </span>
					<span class="year"></span>
					<span class="month"></span>
				</h2>
			</section>
		
			<section class="days"></section>
	
		</article>
		
	</main>
	
	<main id="view-form">
	
		<article class="content">
	
			<section class="title">
				<h2></h2>
			</section>
	
			<section class="form">
				<h3>Allgemein</h3>
				<input id="view-form-input-id" type="hidden" />
				<input id="view-form-input-version" type="hidden" />
				<div class="formitem size-middle">
					<label for="view-form-input-startTime-date">Datum & Spielbeginn</label>
					<input id="view-form-input-startTime-date" type="date" required="required" />
					<input id="view-form-input-startTime-time" type="time" required="required" style="flex: 1; " />
				</div>
				<div class="formitem size-small">
					<label for="view-form-input-entry">Einlass</label>
					<input id="view-form-input-entry" type="text" maxlength="255" />
				</div>
				<div class="formitem size-middle">
					<label for="view-form-input-title">Titel</label>
					<input id="view-form-input-title" type="text" maxlength="255" required="required" />
				</div>
				<div class="formitem size-middle">
					<label for="view-form-input-subtitle">Untertitel</label>
					<input id="view-form-input-subtitle" type="text" maxlength="255" />
				</div>
				<div class="formitem size-middle">
					<label for="view-form-input-series">Reihe</label>
					<input id="view-form-input-series" type="text" maxlength="255" />
				</div>
			</section>
	
			<section class="form">
				<h3>Inhalt</h3>
				<div class="formitem">
					<label for="view-form-input-text">Text</label>
					<textarea id="view-form-input-text" maxlength="16777215"></textarea>
				</div>
				<div class="formitem">
					<label for="view-form-input-lineup">Besetzung</label>
					<textarea id="view-form-input-lineup" maxlength="16777215"></textarea>
				</div>
				<div class="formitem">
					<label for="view-form-input-notes">Bemerkung</label>
					<textarea id="view-form-input-notes" maxlength="16777215"></textarea>
				</div>
			</section>
			
			<section class="form imageform">
				<h3>Bild</h3>
				<div class="formitem size-small">
					<label>Vorschau</label>
					<div class="image-wrapper"></div>
				</div>
				<div class="image-controls">
					<span><a href="#">Lokale Datei auswählen</a></span>
					<span><a href="#">Aktuelles Bild löschen</a></span>
					<input id="view-form-input-image" type="hidden" />
					<input id="view-form-input-image-browse" type="file" />
				</div>
			</section>
	
			<section class="form linkform">
				<h3>Links</h3>
				<div class="formitem">
					<a href="#">Link hinzufügen</a>
				</div>
			</section>
	
			<section class="buttonbar">
				<a id="view-form-button-delete" class="emergency" href="#">Löschen</a>
				<span class="flex-filler"></span>
				<div class="group">
					<a id="view-form-button-back" href="#">Zurück</a>
					<a id="view-form-button-save" href="#">Speichern</a>
				</div>
			</section>
	
		</article>
		
	</main>
	
	<main id="view-loading"></main>
	
</body>
</html>