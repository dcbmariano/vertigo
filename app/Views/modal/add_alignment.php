<div class="modal fade" id="addAlignmentModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Add Alignment
                </h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">
                        Chain
                    </label>
                    <select
                        id="newAlignmentChain"
                        class="form-select">
                    </select>
                </div>
                <div class="mb-3">
                    <label>
                        Start Position
                    </label>
                    <input id="newAlignmentStart" type="number" min="1" class="form-control" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button id="confirmAddAlignmentBtn" class="btn btn-primary">
                    Align
                </button>
            </div>
        </div>
    </div>
</div>