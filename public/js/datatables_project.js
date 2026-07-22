$(document).ready(function () {
    const $table = $('#hitsTable');
    // Columns: 0 #, 1 Chain, 2 Reference, 3 Score, 4 Identity, 5 Positives,
    // 6 Coverage, 7 Type, and 8 View (only when a structure is available).
    const hasView = $table.find('thead th').length >= 9;

    // Score column: two decimals.
    const columnDefs = [
        // Block id (#) column: numeric sorting (values are filled in by the
        // alignment editor once the blocks are rendered).
        { targets: 0, type: 'num' },
        {
            targets: 3,
            render: function (data, type) {
                if (type === 'display' && !isNaN(parseFloat(data))) {
                    return parseFloat(data).toFixed(2);
                }
                return data;
            }
        },
        // Identity / Positives / Coverage: one decimal + percent sign.
        {
            targets: [4, 5, 6],
            render: function (data, type) {
                if (type === 'display' && !isNaN(parseFloat(data))) {
                    return parseFloat(data).toFixed(1) + '%';
                }
                return data;
            }
        }
    ];

    // The "View" column (only present when a structure is available) is not
    // sortable or searchable.
    if (hasView) {
        columnDefs.push({ targets: 8, orderable: false, searchable: false });
    }

    // Exposed so the alignment editor can refresh Identity/Positives/Coverage
    // whenever the alignments change (e.g. after "Expand Sequences").
    window.hitsDataTable = $table.DataTable({
        pageLength: 10,
        responsive: true,
        order: [[0, 'asc']],
        scrollX: true,
        columnDefs: columnDefs,
        language: {
            search: "",
            searchPlaceholder: "Search",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });

    // If the alignments finished rendering before the table was ready, sync now.
    if (typeof updateHitsTable === 'function') {
        updateHitsTable();
    }

    // Highlight the chain in the 3D viewer when its eye button is clicked.
    $table.on('click', '.view-chain-btn', function () {
        const chain = this.getAttribute('data-chain');
        const ref = this.getAttribute('data-ref');
        if (typeof highlightChain === 'function') {
            highlightChain(chain, ref);
        }
    });
});
