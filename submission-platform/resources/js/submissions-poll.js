/**
 * Atualiza estado/classificação/detalhes das submissões em pending ou processing.
 */
export function initSubmissionsPoll() {
    const table = document.getElementById('submissions-table');
    if (!table) {
        return;
    }

    const pollUrl = table.dataset.pollUrl;
    if (!pollUrl) {
        return;
    }

    const intervalMs = parseInt(table.dataset.pollInterval || '3000', 10);

    const poll = async () => {
        const rows = [...table.querySelectorAll('tr[data-submission-id][data-poll="1"]')];
        if (rows.length === 0) {
            return;
        }

        const ids = rows.map((r) => r.dataset.submissionId);
        const url = new URL(pollUrl, window.location.origin);
        ids.forEach((id) => url.searchParams.append('ids[]', id));

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const submissions = data.submissions || {};

            rows.forEach((row) => {
                const id = row.dataset.submissionId;
                const payload = submissions[id];
                if (!payload) {
                    return;
                }

                const statusCell = row.querySelector('[data-cell="status"]');
                const gradeCell = row.querySelector('[data-cell="grade"]');
                const detailsCell = row.querySelector('[data-cell="details"]');

                if (statusCell && payload.status_html) {
                    statusCell.innerHTML = payload.status_html;
                }
                if (gradeCell && payload.grade_html !== undefined) {
                    gradeCell.innerHTML = payload.grade_html;
                }
                if (detailsCell && payload.details_html !== undefined) {
                    detailsCell.innerHTML = payload.details_html;
                }

                if (payload.finished) {
                    row.dataset.poll = '0';
                }
            });
        } catch {
            // ignorar falhas de rede pontuais
        }
    };

    poll();
    window.setInterval(poll, intervalMs);
}
