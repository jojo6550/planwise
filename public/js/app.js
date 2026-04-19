// Live search for lesson plans
(function () {
    const searchInput = document.getElementById('lessonPlanSearchInput');
    if (!searchInput) return;

    const table = document.getElementById('lessonPlansTable');
    if (!table) return;

    const tableBody = table.querySelector('tbody');
    if (!tableBody) return;

    let debounceTimer;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();

        debounceTimer = setTimeout(async function () {
            try {
                const url = '/planwise/controllers/AjaxController.php?action=searchLessonPlans&q=' + encodeURIComponent(q);
                const res  = await fetch(url);
                const data = await res.json();

                if (!data.success) return;

                if (data.data.length === 0) {
                    tableBody.innerHTML =
                        '<tr><td colspan="6" class="text-center py-4 text-muted">' +
                        '<i class="bi bi-search me-2"></i>No lesson plans found' +
                        '</td></tr>';
                    return;
                }

                tableBody.innerHTML = data.data.map(function (lp) {
                    const statusBadge = {
                        published: 'badge bg-success',
                        draft:     'badge bg-warning text-dark',
                        archived:  'badge bg-secondary'
                    }[lp.status] || 'badge bg-light text-dark';

                    const updated = lp.updated_at ? new Date(lp.updated_at).toLocaleDateString('en-US', {year:'numeric', month:'short', day:'numeric'}) : '';

                    return '<tr>' +
                        '<td><strong>' + escapeHtml(lp.title || '') + '</strong></td>' +
                        '<td>' + escapeHtml(lp.subject || 'N/A') + '</td>' +
                        '<td>' + escapeHtml(lp.grade_level || 'N/A') + '</td>' +
                        '<td><span class="' + statusBadge + '">' + escapeHtml(lp.status || '') + '</span></td>' +
                        '<td>' + updated + '</td>' +
                        '<td>' +
                        '<div class="btn-group btn-group-sm">' +
                        '<a href="/planwise/public/index.php?page=teacher/lesson-plans/view&id=' + lp.lesson_id + '" class="btn btn-outline-primary">View</a>' +
                        '<a href="/planwise/public/index.php?page=teacher/lesson-plans/edit&id=' + lp.lesson_id + '" class="btn btn-outline-secondary">Edit</a>' +
                        '<a href="/planwise/controllers/ExportController.php?action=exportPDF&id=' + lp.lesson_id + '" class="btn btn-outline-info">PDF</a>' +
                        '</div>' +
                        '</td>' +
                        '</tr>';
                }).join('');

            } catch (e) {
                // ignore errors, keep current table
            }
        }, 300);
    });

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }
})();
