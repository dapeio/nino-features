<form class="[[class]]" method="post" action="[[action]]" enctype="multipart/form-data" data-files-chunk="[[chunk]]" data-files-max="[[max]]" data-files-multiple="[[multiple]]">
	<input type="hidden" name="folder" value="[[folder]]">
	<input type="hidden" name="count" value="1">
	<input type="hidden" name="return" value="[[return]]">
	<input type="hidden" name="_csrf" value="[[csrf]]">
	<label for="[[id]]" class="files-upload-label">[[/feature/files/upload/label]]</label>
	<input type="file" id="[[id]]" name="chunk" class="files-upload-input" aria-invalid="[[invalid]]" aria-describedby="[[described]]" required>
	<p class="files-upload-hint" id="[[id]]-size">[[/feature/files/upload/hint]] [[size]]</p>
	<p class="files-upload-hint" id="[[id]]-types">[[/feature/files/upload/types]] [[types]]</p>
	<ul class="files-upload-chosen" hidden></ul>
	<p class="files-upload-chosen-hint" hidden></p>
	[[state]]
	<button type="submit" class="files-upload-button">[[/feature/files/upload/button]]</button>
	<ul class="files-upload-list" hidden></ul>
</form>
