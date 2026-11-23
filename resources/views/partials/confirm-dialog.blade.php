{{--
    Styled confirmation for every form with data-confirm="Question? Detail." (app.js).
    Optional on the form: data-confirm-ok="Yes, apply" and data-confirm-tone="danger".
--}}
<dialog class="dialog" data-confirm-dialog aria-labelledby="confirm-title" aria-describedby="confirm-text">
    <div class="dlg-body">
        <span class="dlg-icon" data-confirm-icon aria-hidden="true">
            <x-icon name="check-circle" class="dlg-i-ok" />
            <x-icon name="alert" class="dlg-i-danger" />
        </span>
        <h2 class="dlg-title" id="confirm-title">Are you sure?</h2>
        <p class="dlg-text" id="confirm-text"></p>
    </div>
    <div class="dlg-actions">
        <button type="button" class="btn btn-outline" data-confirm-cancel>Cancel</button>
        <button type="button" class="btn btn-primary" data-confirm-ok>Confirm</button>
    </div>
</dialog>
