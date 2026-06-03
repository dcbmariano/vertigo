$(document).ready(function () {
    $('#hitsTable').DataTable({
        pageLength: 10,
        responsive: true,
        order: [[1, 'desc']],
        scrollX: true,
        columnDefs: [
            {
                targets: 3,
                render: function (data, type, row) {
                    if (
                        type === 'display' &&
                        !isNaN(parseFloat(data))
                    ) {
                        return parseFloat(data).toFixed(2);
                    }
                    return data;
                }
            }
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
});