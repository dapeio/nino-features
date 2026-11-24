<?php
// The class is not declared here: features/Files/ can only serve \Nino\Modules\Files
return [
	'key'					=> 'files',
	'name'				=> [ 'en_US' => 'Files', 'de_DE' => 'Dateien' ],
	'description'	=> [
		'en_US' => 'Files gives each group of the Members feature its folders: the owner puts files in them in the panel, and a member sees the folders of their own group, downloads what is visible to them and - where a folder allows it - uploads, in chunks. The file browser and the upload form are components, a file is shown or saved by the server of the website, and a mail tells a group of new files.',
		'de_DE' => 'Dateien gibt jeder Gruppe des Features Mitglieder ihre Ordner: Der Inhaber legt im Panel Dateien hinein, und ein Mitglied sieht die Ordner der eigenen Gruppe, lädt herunter, was für es sichtbar ist, und lädt - wo ein Ordner es erlaubt - in Teilen hoch. Der Dateibrowser und das Upload-Formular sind Komponenten, eine Datei zeigt oder speichert der Server der Website, und eine Mail meldet einer Gruppe neue Dateien.',
	],
	// What the Description tab shows: all six sections, the empty ones too
	'manual'			=> [
		'shortcodes' => [
			'[files-browser]' => [
				'en_US' => 'The folders of the group of the member who is signed in, each with the files visible to them: the name as a link, the size, the day and a link that saves it; nothing for a visitor.',
				'de_DE' => 'Die Ordner der Gruppe des angemeldeten Mitglieds, jeder mit den für es sichtbaren Dateien: der Name als Link, die Größe, der Tag und ein Link, der sie speichert; für einen Besucher nichts.',
			],
			'[files-browser downloads="0"]' => [
				'en_US' => 'Without the link that saves a file beside the name; the name is a link to the file all the same.',
				'de_DE' => 'Ohne den Link zum Speichern neben dem Namen; der Name bleibt trotzdem ein Link zur Datei.',
			],
			'[files-upload]' => [
				'en_US' => 'The form a member uploads with, for a member whose group has an upload folder: a file field, a button and a list of progress; nothing for a visitor or for a group without one.',
				'de_DE' => 'Das Formular, mit dem ein Mitglied hochlädt, für ein Mitglied, dessen Gruppe einen Upload-Ordner hat: ein Dateifeld, ein Knopf und eine Fortschrittsliste; für Besucher und andere Gruppen nichts.',
			],
			'[files-upload multiple="0"]' => [
				'en_US' => 'One file at a time: the script does not let a member choose several at once, and a form without the script takes one anyway.',
				'de_DE' => 'Eine Datei nach der anderen: Das Skript lässt kein Mitglied mehrere auf einmal wählen, und ein Formular ohne das Skript nimmt ohnehin eine.',
			],
		],
		'markup' => [
			'--files-browser-space, --files-browser-radius' => [
				'en_US' => 'The tokens of the standard part for the browser: the space inside and the corners, which the knobs of the group turn; the form has --files-upload-space and --files-upload-radius.',
				'de_DE' => 'Die Tokens des Standard-Teils für den Browser: der Innenabstand und die Ecken, die die Regler der Gruppe drehen; das Formular hat --files-upload-space und --files-upload-radius.',
			],
			'.files-browser, .files-upload' => [
				'en_US' => 'The classes of the two components, with -folder, -title, -list, -row, -link, -size, -date and -download for the browser and -label, -input, -hint, -state, -list and -button for the form.',
				'de_DE' => 'Die Klassen der beiden Komponenten, mit -folder, -title, -list, -row, -link, -size, -date und -download für den Browser und -label, -input, -hint, -state, -list und -button für das Formular.',
			],
			'data-files-id, data-files-mime' => [
				'en_US' => 'On a row of the browser: the id and the MIME type of its file, for the features and scripts that build on it.',
				'de_DE' => 'An einer Zeile des Browsers: die Kennung und der MIME-Typ ihrer Datei, für die Features und Skripte, die darauf aufbauen.',
			],
			'data-files-chunk, data-files-max' => [
				'en_US' => 'On the form: the size of one chunk of an upload and of the largest file in bytes, which files.js reads; a web server that takes less makes the script halve the chunk.',
				'de_DE' => 'Am Formular: die Größe eines Teils eines Uploads und der größten Datei in Byte, die files.js liest; nimmt der Webserver weniger, halbiert das Skript den Teil.',
			],
			'Title.03.ext' => [
				'en_US' => 'A name that says a version: the title, a dot, one to three digits, a dot and the extension; the files of a folder with the same title and extension are the versions of one title.',
				'de_DE' => 'Ein Name, der eine Version sagt: der Titel, ein Punkt, ein bis drei Ziffern, ein Punkt und die Endung; Dateien eines Ordners mit gleichem Titel und gleicher Endung sind Versionen eines Titels.',
			],
			'Files::version( $name )' => [
				'en_US' => 'Reads a name for its title and its version, pure and nothing looked up; a name that says no version is a title of its own.',
				'de_DE' => 'Liest aus einem Namen den Titel und die Version, rein und ohne etwas nachzuschlagen; ein Name ohne Version ist ein Titel für sich.',
			],
			'Files::grouped( $files )' => [
				'en_US' => 'Puts the files of one folder together as titles with their versions, the versions from the highest number down and the titles by the day of the version they show.',
				'de_DE' => 'Bringt die Dateien eines Ordners als Titel mit ihren Versionen zusammen, die Versionen von der höchsten Nummer abwärts und die Titel nach dem Tag der gezeigten Version.',
			],
		],
		'routes' => [
			'GET /.files/<id>' => [
				'en_US' => 'A file for a member of the group of its folder where it is visible, for the member who uploaded it and for the owner at the workbench; the kernel streams it, ranges included.',
				'de_DE' => 'Eine Datei für ein Mitglied der Gruppe ihres Ordners, wo sie sichtbar ist, für den, der sie hochgeladen hat, und für den Inhaber an der Workbench; der Kernel streamt sie mit Ranges.',
			],
			'GET /.files/<id> (everybody else)' => [
				'en_US' => 'A visitor, a member of another group, a switched-off account and an id nobody has are all the same 404 with a plain body; a file removed from the server is a 410 for those who may have it.',
				'de_DE' => 'Besucher, Mitglied einer anderen Gruppe, abgeschaltetes Konto und unbekannte Kennung bekommen dieselbe 404 mit schlichtem Text; eine entfernte Datei ist für Berechtigte eine 410.',
			],
			'GET /.files/<id>?download=1' => [
				'en_US' => 'Always saved, under the cleaned name; shown otherwise only for audio, video, images and pdf, never for what a browser runs, and with nosniff and no cache but the browser\'s own.',
				'de_DE' => 'Immer gespeichert, unter dem bereinigten Namen; sonst nur bei Audio, Video, Bildern und PDF angezeigt, nie bei dem, was ein Browser ausführt, und mit nosniff und ohne Cache außer dem des Browsers.',
			],
			'POST /.files/upload' => [
				'en_US' => 'One chunk of a member\'s upload with the token of the form: a signed-in member of the group of the upload folder, the chunks in order, the last one assembles the file; refusals are codes of files_*.',
				'de_DE' => 'Ein Teil des Uploads eines Mitglieds mit dem Token des Formulars: Mitglied der Gruppe des Upload-Ordners, Teile der Reihe nach, das letzte setzt zusammen; Ablehnungen sind Codes files_*.',
			],
			'POST /.files/upload (a plain form)' => [
				'en_US' => 'The same request without the script: one chunk that is the whole file, answered with a redirect to the page of the form with ?files=sent or ?files=<code>.',
				'de_DE' => 'Dieselbe Anfrage ohne das Skript: ein Teil, das die ganze Datei ist, beantwortet mit einer Umleitung auf die Seite des Formulars mit ?files=sent oder ?files=<Code>.',
			],
		],
		'panel' => [
			'Files' => [
				'en_US' => 'The folders of all groups as a table narrowed by a group, with a dialog to make a folder, rename it, mark it as the upload folder of its group and delete it.',
				'de_DE' => 'Die Ordner aller Gruppen als Tabelle, nach Gruppe eingegrenzt, mit einem Dialog, um einen Ordner anzulegen, umzubenennen, als Upload-Ordner seiner Gruppe zu markieren und zu löschen.',
			],
			'Folder' => [
				'en_US' => 'A folder as a view of its own: its files as a table with a selection, the upload in chunks, and a download link for each file.',
				'de_DE' => 'Ein Ordner als eigene Ansicht: seine Dateien als Tabelle mit Auswahl, der Upload in Teilen und für jede Datei ein Link zum Herunterladen.',
			],
			'Files of a folder' => [
				'en_US' => 'Show to the members or hide, move to another folder of the group, remove from the server, which keeps the row with the day the bytes went, or delete; for one file or a selection.',
				'de_DE' => 'Für die Mitglieder sichtbar machen oder verbergen, in einen anderen Ordner der Gruppe verschieben, vom Server entfernen (die Zeile bleibt) oder löschen; einzeln oder als Auswahl.',
			],
			'The upload and its limits' => [
				'en_US' => 'A file is cut into chunks of the setting chunk, never more than php and the web server take in one request, and the largest file is the setting maxSize.',
				'de_DE' => 'Eine Datei wird in Teile der Einstellung chunk geschnitten, nie mehr als php und der Webserver in einer Anfrage nehmen, und die größte Datei ist die Einstellung maxSize.',
			],
			'The quota and unfinished uploads' => [
				'en_US' => 'A file that is being uploaded holds its declared size against the quota until it is in or has had no chunk for 15 minutes.',
				'de_DE' => 'Eine Datei, die hochgeladen wird, belegt ihre erklärte Größe im Kontingent, bis sie fertig ist oder 15 Minuten kein Teil kam.',
			],
			'The mail to the owner about an upload' => [
				'en_US' => 'With the setting notifyOwner a member\'s upload puts one mail for the contact address into the outbox of the kernel, which delivers it after the answer.',
				'de_DE' => 'Mit der Einstellung notifyOwner legt der Upload eines Mitglieds eine Mail für die Kontaktadresse in den Postausgang des Kernels, der sie nach der Antwort ausliefert.',
			],
			'The mail to the group: the page it links' => [
				'en_US' => 'The actions files/upload, files/save and files/notify take the field page, a path of this site, as the page the mail to the group links; anything else is the page members land on.',
				'de_DE' => 'Die Aktionen files/upload, files/save und files/notify nehmen das Feld page, einen Pfad dieser Website, als Seite der Mail an die Gruppe; alles andere ist die Seite, auf der Mitglieder landen.',
			],
			'The mail to the group: one mail for an upload' => [
				'en_US' => 'The files of one upload are put in with notify false and the action files/notify tells the group of all of them in one mail; ids is the list of at most 200 files of one folder.',
				'de_DE' => 'Die Dateien eines Uploads werden mit notify false eingestellt, und die Aktion files/notify meldet der Gruppe alle in einer Mail; ids ist die Liste von höchstens 200 Dateien eines Ordners.',
			],
		],
		'callbacks' => [
			'/files/upload' => [
				'en_US' => 'Fired when a member\'s upload is complete, with the entry of the file: id, folder, name, ext, size, mime, sha256, by (kind and id), visible, final and created.',
				'de_DE' => 'Wird ausgelöst, wenn der Upload eines Mitglieds vollständig ist, mit dem Eintrag der Datei: id, folder, name, ext, size, mime, sha256, by (kind und id), visible, final und created.',
			],
			'/files/add' => [
				'en_US' => 'Fired when the owner\'s upload in the panel is complete, with the same entry as the argument.',
				'de_DE' => 'Wird ausgelöst, wenn der Upload des Inhabers im Panel vollständig ist, mit demselben Eintrag als Argument.',
			],
			'/files/delete' => [
				'en_US' => 'Fired when a file is deleted - and for each file of a folder that is deleted with its files - with the entry as it was.',
				'de_DE' => 'Wird ausgelöst, wenn eine Datei gelöscht wird - und für jede Datei eines Ordners, der mit seinen Dateien gelöscht wird -, mit dem Eintrag, wie er war.',
			],
			'/files/granted' => [
				'en_US' => 'Asked by allowed() when its rule says no for a visible file, with file, member, download, listing and granted; a callback that sets granted to true gives that member read access.',
				'de_DE' => 'Wird von allowed() gefragt, wenn die Regel für eine sichtbare Datei Nein sagt, mit file, member, download, listing und granted; ein Callback, der granted auf true setzt, gibt dem Mitglied Lesezugriff.',
			],
			'/files/allowed' => [
				'en_US' => 'Asked by allowed() when the rule would say yes, with file, member, download and allowed; a callback may set allowed to false to withhold the file and can never give one.',
				'de_DE' => 'Wird von allowed() gefragt, wenn die Regel Ja sagen würde, mit file, member, download und allowed; ein Callback darf allowed auf false setzen und nie eine Datei zusprechen.',
			],
			'/members/removed' => [
				'en_US' => 'Heard for every account Members deletes: its uploads stay files of their folder, and the sender of each is a member who has no id any more.',
				'de_DE' => 'Wird für jedes Konto gehört, das Mitglieder löscht: Seine Uploads bleiben Dateien ihres Ordners, und der Absender jeder ist ein Mitglied ohne Kennung.',
			],
			'/nino/admin/restore' => [
				'en_US' => 'A restore keeps the index of the backup and shows a file whose bytes are not on the server as removed from it, as well as what the live index has removed since.',
				'de_DE' => 'Eine Wiederherstellung behält den Index der Sicherung und zeigt eine Datei, deren Bytes nicht auf dem Server sind, als entfernt, ebenso was der aktuelle Index seither entfernt hat.',
			],
			'For other features: \Nino\Modules\Files, read' => [
				'en_US' => 'Read: folders, files, file, path, allowed, url, version, grouped and disposition.',
				'de_DE' => 'Lesen: folders, files, file, path, allowed, url, version, grouped und disposition.',
			],
			'For other features: \Nino\Modules\Files, tell' => [
				'en_US' => 'Tell: page, notifyOwner and notifyMembers.',
				'de_DE' => 'Melden: page, notifyOwner und notifyMembers.',
			],
			'For other features: \Nino\Modules\Files, the owner\'s operations' => [
				'en_US' => 'The owner\'s operations: overview, createFolder, renameFolder, markFolder, deleteFolder, upload, change, notify, deleteFile and removeFile.',
				'de_DE' => 'Die Operationen des Inhabers: overview, createFolder, renameFolder, markFolder, deleteFolder, upload, change, notify, deleteFile und removeFile.',
			],
			'For other features: the owner\'s operations check for themselves' => [
				'en_US' => 'Each asks the permission of the panel itself and holds its inputs to their rules, and the panel\'s actions are made of them.',
				'de_DE' => 'Jede fragt die Berechtigung des Panels selbst und prüft ihre Eingaben, und die Aktionen des Panels bestehen daraus.',
			],
			'For other features: the kernel\'s methods' => [
				'en_US' => 'init, adminPanels, doShortcodeMailHead, the callbacks and the two components belong to the kernel and are no interface.',
				'de_DE' => 'init, adminPanels, doShortcodeMailHead, die Callbacks und die beiden Komponenten gehören dem Kernel und sind keine Schnittstelle.',
			],
			'For other features: \Nino\Modules\Files\Store, data' => [
				'en_US' => 'The data layer asks no permission: parse, read, folders, folder, files, file, bytes, path, addFolder, renameFolder, markUpload, deleteFolder and wipeGroups.',
				'de_DE' => 'Die Datenschicht fragt keine Berechtigung: parse, read, folders, folder, files, file, bytes, path, addFolder, renameFolder, markUpload, deleteFolder und wipeGroups.',
			],
			'For other features: \Nino\Modules\Files\Store, files' => [
				'en_US' => 'The files of the data layer: addFile, changeFile, moveFile, copyFile, writeBytes, renameFile, deleteFile, removeBytes, reserve and reservation.',
				'de_DE' => 'Die Dateien der Datenschicht: addFile, changeFile, moveFile, copyFile, writeBytes, renameFile, deleteFile, removeBytes, reserve und reservation.',
			],
			'For other features: \Nino\Modules\Files\Store, rules' => [
				'en_US' => 'The rules of the data layer: used, held, types, cleanName, cleanFolder, sniff, inline, chunkBytes, clamp, maxBytes, quotaBytes, size, sweep and dropParts.',
				'de_DE' => 'Die Regeln der Datenschicht: used, held, types, cleanName, cleanFolder, sniff, inline, chunkBytes, clamp, maxBytes, quotaBytes, size, sweep und dropParts.',
			],
		],
		'install' => [
			'text/<locale>.php' => [
				'en_US' => 'Its words go into the Text panel: the units of a size, the states of the form and the refusals of an upload, the words of the script and the two mails.',
				'de_DE' => 'Seine Worte kommen ins Panel Texte: die Einheiten einer Größe, die Meldungen des Formulars und die Ablehnungen eines Uploads, die Worte des Skripts und die beiden Mails.',
			],
			'blocks/files-library/' => [
				'en_US' => 'A block: a headline, a line of text, the files of the member\'s group and the upload form.',
				'de_DE' => 'Ein Block: eine Überschrift, eine Zeile Text, die Dateien der Gruppe des Mitglieds und das Formular zum Hochladen.',
			],
			'/data/files.php' => [
				'en_US' => 'The index of the folders and files, which the daily backup carries; the format is a plain php file.',
				'de_DE' => 'Der Index der Ordner und Dateien, den die tägliche Sicherung mitnimmt; das Format ist eine einfache php-Datei.',
			],
			'/data/files/' => [
				'en_US' => 'The bytes of the files, one file per id; the daily backup leaves them out, so the operator saves data/files with the server.',
				'de_DE' => 'Die Bytes der Dateien, eine Datei je Kennung; die tägliche Sicherung lässt sie aus, die Dateien unter data/files sichert der Betreiber mit dem Server.',
			],
			'elements/privacy.php' => [
				'en_US' => 'Brings its section of the privacy policy for the Legal module; a starting point, no legal advice.',
				'de_DE' => 'Bringt den Abschnitt der Datenschutzerklärung für das Modul Legal mit; ein Ausgangspunkt, keine Rechtsberatung.',
			],
		],
	],
	'category'		=> 'content',
	'version'			=> '2.0.0',
	'nino'				=> '^2.0',
	'php'					=> [ 'ext' => [ 'fileinfo' ] ],
	'requires'		=> [ 'members', 'basis' ],
	// The index only. The daily backup of the kernel reads its whole archive into memory, so the bytes of the files (up to the setting maxSize each) stay out of it and the operator saves /data/files
	// with the server; the counter of the chunk requests (files-tries.php) is an hour long and stays out as well
	'data'				=> [ '/data/files.php' ],
	'assets'			=> [ '/features/Files/assets/files.js' ],
	'settings'		=> [
		'types' => [
			'type'	=> 'lines',
			'label'	=> [ 'en_US' => 'File types', 'de_DE' => 'Dateitypen' ],
			'hint'	=> [
				'en_US' => 'The extensions that may be uploaded, one per line, without the dot, in lower case. Only extensions the feature knows are taken, and the bytes of a file have to be what its extension says: jpg jpeg png gif webp pdf docx xlsx pptx odt ods odp txt md csv zip mp3 wav flac aiff aif m4a ogg opus aac mp4 m4v mov webm.',
				'de_DE' => 'Die Endungen, die hochgeladen werden dürfen, eine pro Zeile, ohne Punkt, klein geschrieben. Es werden nur Endungen genommen, die das Feature kennt, und die Bytes einer Datei müssen sein, was ihre Endung sagt: jpg jpeg png gif webp pdf docx xlsx pptx odt ods odp txt md csv zip mp3 wav flac aiff aif m4a ogg opus aac mp4 m4v mov webm.',
			],
			'default'	=> [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'txt', 'md', 'csv', 'zip', 'mp3', 'wav', 'm4a', 'ogg', 'mp4', 'webm', 'mov' ],
		],
		'maxSize' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Largest file', 'de_DE' => 'Größte Datei' ],
			'hint'		=> [
				'en_US' => 'How large one file may be, in megabytes. A file is cut into chunks, so it is not bound by the post size of the server.',
				'de_DE' => 'Wie groß eine Datei sein darf, in Megabyte. Eine Datei wird in Teile geschnitten, die Post-Größe des Servers bindet sie also nicht.',
			],
			'min'			=> 1,
			'max'			=> 4096,
			'unit'		=> 'megabytes',
			'default'	=> 500,
		],
		'chunk' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Size of a chunk', 'de_DE' => 'Größe eines Teils' ],
			'hint'		=> [
				'en_US' => 'How much of a file one request carries, in megabytes. It has to stay below upload_max_filesize and post_max_size of the server, which the panel shows; a larger value is held under them. A limit of the web server below the chunk (client_max_body_size of nginx, LimitRequestBody of Apache, a proxy of the host) is invisible to php: the upload finds it itself and halves its chunks when the server answers 413 - every halving multiplies the requests, which count against the setting for the requests per hour.',
				'de_DE' => 'Wie viel von einer Datei eine Anfrage trägt, in Megabyte. Der Wert muss unter upload_max_filesize und post_max_size des Servers bleiben, die das Panel zeigt; ein größerer Wert wird darunter gehalten. Eine Grenze des Webservers unterhalb des Teils (client_max_body_size von nginx, LimitRequestBody von Apache, ein Proxy des Hosters) sieht php nicht: Der Upload findet sie selbst und halbiert seine Teile, wenn der Server mit 413 antwortet - jede Halbierung vervielfacht die Anfragen, die auf die Einstellung für die Anfragen pro Stunde zählen.',
			],
			'min'			=> 1,
			'max'			=> 32,
			'unit'		=> 'megabytes',
			'default'	=> 4,
		],
		'quota' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Quota per group', 'de_DE' => 'Kontingent je Gruppe' ],
			'hint'		=> [
				'en_US' => 'How much the files of one group may take together, in megabytes. 0 is no limit.',
				'de_DE' => 'Wie viel die Dateien einer Gruppe zusammen belegen dürfen, in Megabyte. 0 heißt unbegrenzt.',
			],
			'min'			=> 0,
			'max'			=> 100000,
			'unit'		=> 'megabytes',
			'default'	=> 0,
		],
		'attempts' => [
			'type'		=> 'int',
			'label'		=> [ 'en_US' => 'Requests', 'de_DE' => 'Anfragen' ],
			'hint'		=> [
				'en_US' => 'Chunk requests one visitor may send per hour before the upload refuses for the rest of that hour. The address of the visitor is counted as a hash. A file of the largest size in chunks of the size above takes as many requests as this allows.',
				'de_DE' => 'Teile-Anfragen, die ein Besucher pro Stunde senden darf, bevor der Upload für den Rest dieser Stunde verweigert. Die Adresse des Besuchers wird als Hash gezählt. Eine Datei der größten Größe in Teilen der oben genannten Größe braucht so viele Anfragen, wie dies erlaubt.',
			],
			'min'			=> 1,
			'max'			=> 1000,
			'unit'		=> 'per-hour',
			'default'	=> 300,
		],
		'notifyMembers' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Mail the group', 'de_DE' => 'Gruppe benachrichtigen' ],
			'hint'		=> [
				'en_US' => 'Mail every active member of a group when the owner uploads a file that is visible or makes one visible. The files of one request in the panel are one mail.',
				'de_DE' => 'Jedem aktiven Mitglied einer Gruppe eine Mail schicken, wenn der Betreiber eine sichtbare Datei hochlädt oder eine sichtbar macht. Die Dateien einer Anfrage im Panel sind eine Mail.',
			],
			'default'	=> false,
		],
		'notifyOwner' => [
			'type'		=> 'bool',
			'label'		=> [ 'en_US' => 'Mail the owner', 'de_DE' => 'Betreiber benachrichtigen' ],
			'hint'		=> [
				'en_US' => 'Mail the contact address of the website when a member uploads a file - one mail for each upload, sent after the member has the answer.',
				'de_DE' => 'Der Kontaktadresse der Website eine Mail schicken, wenn ein Mitglied eine Datei hochlädt - eine Mail für jeden Upload, gesendet, nachdem das Mitglied die Antwort hat.',
			],
			'default'	=> false,
		],
	],
];
