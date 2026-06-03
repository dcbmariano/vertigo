<!-- modal edit sequence -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Edit Sequence
                </h5>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>
            </div>
            <div class="modal-body">
                <label class="form-label">
                    Original sequence
                </label>
                <textarea
                    id="oldSequence"
                    class="form-control mb-3"
                    rows="4"
                    readonly>
                </textarea>
                <label class="form-label">
                    New sequence
                </label>
                <textarea
                    id="newSequence"
                    class="form-control"
                    rows="4">
                </textarea>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Cancel
                </button>
                <button
                    type="button"
                    id="saveEditBtn"
                    class="btn btn-primary">
                    Change sequence
                </button>
            </div>
        </div>
    </div>
</div>