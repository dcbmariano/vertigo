<div class="modal fade" id="createAlignmentBlockModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Create Alignment Block
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary mb-3" id="createBlockMessage">
                    Fill in the information below to create a new alignment block.
                </div>
                <div class="mb-3">
                    <label class="form-label badge bg-secondary mb-1">
                        Reference Header
                    </label>
                    <input placeholder="Type the sequence ID (without '>')"
                        id="newBlockHeader"
                        class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label badge bg-secondary mb-1">
                        Reference Sequence
                    </label>
                    <textarea placeholder="Type the sequence (e.g., MALWMRLLPLLALLALWGPDPAAAFVNQHLCGSHLVEALYLVCGERGFFYTPKTRREAEDLQVGQVELGGGPGAGSLQPLALEGSLQKRGIVEQCCTSICSLYQLENYCN)"
                        id="newBlockSequence"
                        class="form-control"
                        rows="5"
                        style="font-family:monospace"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label badge bg-secondary mb-1">
                        Chain
                    </label>
                    <select
                        id="newBlockChain"
                        class="form-select">
                    </select>
                </div>
                <div class="mb-3">
                    <pre
                        id="newBlockChainPreview"
                        class="border rounded p-2 bg-light"
                        style="
                            display:none;
                            font-family:monospace;
                            max-height:180px;
                            overflow:auto;">
                    </pre>
                </div>
                <div class="mb-3">
                    <label class="form-label badge bg-secondary mb-1">
                        Alignment Position (default: 1)
                    </label>
                    <input
                        id="newBlockStart"
                        type="number"
                        min="1"
                        value="1"
                        class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Cancel
                </button>
                <button
                    id="confirmCreateBlockBtn"
                    class="btn btn-primary">
                    Create Alignment Block
                </button>
            </div>
        </div>
    </div>
</div>